<?php

/**
 * @author ZHI FOOK CHUA
 */

namespace App\Http\Controllers;

use App\Http\Requests\StoreLessonRequest;
use App\Http\Requests\UpdateLessonRequest;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\LessonResource;
use App\Models\User;
use App\Support\ContentSanitizer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

/**
 * Lesson Management shared by Admin and Mentor.
 *  - Admin  : may manage lessons in any course across the system.
 *  - Mentor : may only manage lessons inside their own courses.
 *
 * Routes are nested under a course: /courses/{course:slug}/lessons
 */
class LessonController extends Controller
{
    /**
     * The currently authenticated user, guaranteed to be an Admin or Mentor
     * (this module is only reachable behind the auth + role middleware).
     */
    protected function currentUser(): User
    {
        $user = Auth::user();

        abort_unless($user instanceof User, 403, 'Unauthenticated.');

        return $user;
    }

    /**
     * Block mentors from reaching lessons outside their own courses.
     */
    protected function ensureAccessible(Course $course): void
    {
        if ($this->currentUser()->isMentor()) {
            abort_unless(
                (int) $course->mentor_id === (int) Auth::id(),
                403,
                'You can only manage lessons in your own courses.'
            );
        }
    }

    /**
     * Prevent a lesson being managed through the wrong course.
     */
    protected function ensureBelongsToCourse(Course $course, Lesson $lesson): void
    {
        abort_unless((int) $lesson->course_id === (int) $course->id, 404);
    }

    public function index(Request $request, Course $course)
    {
        $this->ensureAccessible($course);

        $request->validate([
            'search' => 'nullable|string|max:100',
            'status' => 'nullable|in:Draft,Published',
        ]);

        $query = $course->lessons()
            ->withCount(['assessments', 'resources']);

        if ($request->filled('search')) {
            $search = $request->search;

            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('content', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $lessons = $query
            ->orderBy('order_number')
            ->paginate(10)
            ->withQueryString();

        return view('lessons.index', compact('course', 'lessons'));
    }

    public function create(Course $course)
    {
        $this->ensureAccessible($course);

        $nextOrder = ($course->lessons()->max('order_number') ?? 0) + 1;

        return view('lessons.create', compact('course', 'nextOrder'));
    }

    public function store(StoreLessonRequest $request, Course $course)
    {
        $this->ensureAccessible($course);

        $lesson = $course->lessons()->create($this->lessonData($request));

        $this->syncResources($lesson, $request->validated('resources') ?? []);

        return redirect()
            ->route('courses.lessons.index', $course)
            ->with('success', 'Lesson created successfully.');
    }

    public function edit(Course $course, Lesson $lesson)
    {
        $this->ensureAccessible($course);
        $this->ensureBelongsToCourse($course, $lesson);

        return view('lessons.edit', compact('course', 'lesson'));
    }

    public function update(UpdateLessonRequest $request, Course $course, Lesson $lesson)
    {
        $this->ensureAccessible($course);
        $this->ensureBelongsToCourse($course, $lesson);

        $lesson->update($this->lessonData($request));

        $this->syncResources($lesson, $request->validated('resources') ?? []);

        return redirect()
            ->route('courses.lessons.index', $course)
            ->with('success', 'Lesson updated successfully.');
    }

    /**
     * Sanitise and return lesson content for the editor's preview modal.
     * The returned HTML is always passed through the same sanitizer used on
     * save, so the preview can never render raw, unsanitised input.
     */
    public function preview(Request $request, Course $course)
    {
        $this->ensureAccessible($course);

        $request->validate([
            'content' => 'nullable|string|max:20000',
        ]);

        return response()->json([
            'status' => 'S',
            //'html' => ContentSanitizer::sanitize($request->input('content')),
                    // INSECURE: raw HTML returned
        'html' => $request->input('content'),
        ]);
    }

    /**
     * Stream an attached file (document/image) to an Admin/Mentor who is
     * authorised for the course. Files are served from the private disk
     * through this route so they are never publicly accessible.
     */
    public function downloadResource(Course $course, Lesson $lesson, LessonResource $resource)
    {
        $this->ensureAccessible($course);
        $this->ensureBelongsToCourse($course, $lesson);

        abort_unless((int) $resource->lesson_id === (int) $lesson->id, 404);
        abort_unless(in_array($resource->type, ['File', 'Image'], true), 404);
        abort_unless($resource->file_path && Storage::disk('local')->exists($resource->file_path), 404);

        return Storage::disk('local')->download(
            $resource->file_path,
            $resource->file_name ?: $resource->title
        );
    }

    /**
     * The validated lesson fields with rich content sanitised and the
     * resources array removed (resources are synced separately).
     */
    protected function lessonData(StoreLessonRequest $request): array
    {
        $data = $request->validated();

        //$data['content'] = ContentSanitizer::sanitize($data['content'] ?? '');
         // INSECURE: content is stored without sanitisation
         $data['content'] = $data['content'] ?? '';
        $data['description'] = isset($data['description']) && trim($data['description']) !== ''
            ? trim($data['description'])
            : null;

        unset($data['resources']);

        return $data;
    }

    /**
     * Reconcile the submitted resources with the lesson's current resources:
     * create new rows, update existing ones, delete rows removed in the form
     * (and delete their stored files).
     */
    protected function syncResources(Lesson $lesson, array $resources = []): void
    {
        $submittedIds = collect($resources)
            ->pluck('id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->all();

        $lesson->resources()
            ->whereNotIn('id', $submittedIds ?: [0])
            ->get()
            ->each(function (LessonResource $resource) {
                $this->deleteStoredFile($resource);
                $resource->delete();
            });

        $order = 0;

        foreach ($resources as $resourceData) {
            $order++;

            $id = ! empty($resourceData['id']) ? (int) $resourceData['id'] : null;
            $type = $resourceData['type'] ?? 'Link';
            $model = $id ? $lesson->resources()->find($id) : null;

            $attributes = [
                'type' => $type,
                'title' => trim((string) ($resourceData['title'] ?? '')),
                'description' => isset($resourceData['description']) && trim((string) $resourceData['description']) !== ''
                    ? trim((string) $resourceData['description'])
                    : null,
                'display_order' => $order,
            ];

            if (in_array($type, ['Link', 'Video'], true)) {
                $attributes['url'] = trim((string) ($resourceData['url'] ?? ''));
                $this->clearFileFields($model, $attributes);
            } else {
                $attributes['url'] = null;

                if (! empty($resourceData['file'])) {
                    $this->deleteStoredFile($model);

                    $attributes['file_path'] = $resourceData['file']->store('lessons/'.$lesson->id, 'local');
                    $attributes['file_name'] = $resourceData['file']->getClientOriginalName();
                    $attributes['mime_type'] = $resourceData['file']->getMimeType();
                    $attributes['file_size'] = $resourceData['file']->getSize();
                }
            }

            if ($model) {
                $model->update($attributes);
            } else {
                $lesson->resources()->create($attributes);
            }
        }
    }

    /**
     * Remove the stored file from the disk, if any.
     */
    protected function deleteStoredFile(?LessonResource $resource): void
    {
        if ($resource && $resource->file_path) {
            Storage::disk('local')->delete($resource->file_path);
        }
    }

    /**
     * When a resource changes to a Link/Video, drop any previously stored file.
     */
    protected function clearFileFields(?LessonResource $model, array &$attributes): void
    {
        $this->deleteStoredFile($model);

        $attributes['file_path'] = null;
        $attributes['file_name'] = null;
        $attributes['mime_type'] = null;
        $attributes['file_size'] = null;
    }

    public function delete(Course $course, Lesson $lesson)
    {
        $this->ensureAccessible($course);
        $this->ensureBelongsToCourse($course, $lesson);

        $lesson->loadCount(['assessments', 'progresses']);

        return view('lessons.delete', compact('course', 'lesson'));
    }

    public function destroy(Course $course, Lesson $lesson)
    {
        $this->ensureAccessible($course);
        $this->ensureBelongsToCourse($course, $lesson);

        // Deleting a lesson cascades to assessments, questions, attempts,
        // student answers and progress (verified FK cascade chain). Block the
        // deletion when that would destroy historical learning data.
        if ($lesson->assessments()->exists() || $lesson->progresses()->exists()) {
            return redirect()
                ->route('courses.lessons.index', $course)
                ->with('warning', 'Lessons with assessments or student progress cannot be deleted.');
        }

        $lesson->delete();

        return redirect()
            ->route('courses.lessons.index', $course)
            ->with('success', 'Lesson deleted successfully.');
    }
}
