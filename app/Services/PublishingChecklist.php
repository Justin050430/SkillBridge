<?php

namespace App\Services;

use Illuminate\Database\Query\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class PublishingChecklist
{
    public function items(string $type = 'all'): LengthAwarePaginator
    {
        return $this->query()
            ->when($type !== 'all', fn (Builder $query) => $query->where('type', $type))
            ->orderByDesc('blocked')
            ->orderByDesc('missing')
            ->orderBy('updated_at')
            ->orderBy('type')
            ->orderBy('id')
            ->paginate(5, ['*'], 'checklist_page')
            ->withQueryString()
            ->fragment('publishing-checklist')
            ->through(fn (object $item) => $this->describe($item));
    }

    public function counts(): array
    {
        $counts = $this->query()->select('type')->selectRaw('COUNT(*) as total')
            ->groupBy('type')->pluck('total', 'type');

        return [
            'all' => $counts->sum(),
            'course' => (int) $counts->get('course', 0),
            'lesson' => (int) $counts->get('lesson', 0),
            'assessment' => (int) $counts->get('assessment', 0),
        ];
    }

    private function query(): Builder
    {
        $courses = DB::table('courses')
            ->where('courses.status', 'Draft')
            ->select('courses.id', 'courses.title', 'courses.slug', 'courses.updated_at')
            ->selectRaw("'course' as type, NULL as parent_slug, NULL as context, 0 as blocked")
            ->selectRaw('CASE WHEN EXISTS (SELECT 1 FROM lessons WHERE lessons.course_id = courses.id) THEN 0 ELSE 1 END as missing');

        $lessons = DB::table('lessons')
            ->join('courses', 'courses.id', '=', 'lessons.course_id')
            ->where('lessons.status', 'Draft')
            ->where('courses.status', '!=', 'Archived')
            ->select('lessons.id', 'lessons.title')
            ->selectRaw('NULL as slug')
            ->addSelect('lessons.updated_at')
            ->selectRaw("'lesson' as type")
            ->addSelect('courses.slug as parent_slug', 'courses.title as context')
            ->selectRaw("CASE WHEN TRIM(COALESCE(lessons.content, '')) = '' THEN 1 ELSE 0 END as blocked")
            ->selectRaw("CASE WHEN TRIM(COALESCE(lessons.content, '')) = '' THEN 1 ELSE 0 END as missing");

        $assessments = DB::table('assessments')
            ->join('lessons', 'lessons.id', '=', 'assessments.lesson_id')
            ->join('courses', 'courses.id', '=', 'lessons.course_id')
            ->where('assessments.status', 'Draft')
            ->where('courses.status', '!=', 'Archived')
            ->select('assessments.id', 'assessments.title', 'assessments.slug', 'assessments.updated_at')
            ->selectRaw("'assessment' as type")
            ->addSelect('courses.slug as parent_slug', 'lessons.title as context')
            ->selectRaw('CASE WHEN EXISTS (SELECT 1 FROM questions WHERE questions.assessment_id = assessments.id) THEN 0 ELSE 1 END as blocked')
            ->selectRaw('CASE WHEN EXISTS (SELECT 1 FROM questions WHERE questions.assessment_id = assessments.id) THEN 0 ELSE 1 END as missing');

        // Paginate the combined queue in the database, so every draft is reachable.
        // Archived courses and their descendants are intentionally out of scope.
        return DB::query()->fromSub($courses->unionAll($lessons)->unionAll($assessments), 'drafts');
    }

    private function describe(object $item): object
    {
        $item->label = $item->blocked ? 'Blocked' : ($item->missing ? 'Content reminder' : 'Draft');
        $item->detail = 'Saved as draft. Review and publish when ready.';
        $item->action = 'Edit';

        if ($item->type === 'course') {
            $item->url = route('admin.courses.edit', $item->slug);
            if ($item->missing) {
                $item->detail = 'No lessons added yet. Add learning content before sharing this course.';
                $item->action = 'Add lesson';
                $item->url = route('courses.lessons.create', $item->slug);
            }
        } elseif ($item->type === 'lesson') {
            $item->url = route('courses.lessons.edit', [$item->parent_slug, $item->id]);
            if ($item->missing) {
                $item->detail = 'Missing lesson content. Add content before publishing.';
            }
        } else {
            $item->url = route('assessments.edit', $item->slug);
            if ($item->missing) {
                $item->detail = 'No questions added. At least one question is required to publish.';
                $item->action = 'Add question';
                $item->url = route('assessments.questions.create', $item->slug);
            }
        }

        return $item;
    }
}
