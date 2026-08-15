<?php

/**
 * @author OOI JIAN XIN
 *
 * Lesson resources & rich-content feature tests:
 *  - Mentor/admin can attach documents, links and videos to a lesson.
 *  - Rich content is sanitised (stored XSS protection).
 *  - Uploads are validated by MIME type and size (no trusting extensions).
 *  - Students can read published lessons and download authorised files.
 *  - Publication, enrolment and ownership rules are enforced server-side.
 */

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\Category;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\LessonResource;
use App\Models\Question;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LessonResourceTest extends TestCase
{
    use RefreshDatabase;

    protected function makeUser(string $roleName, string $email): User
    {
        $role = Role::firstOrCreate(['role_name' => $roleName]);

        return User::create([
            'role_id' => $role->id,
            'first_name' => 'Test',
            'last_name' => $roleName,
            'email' => $email,
            'password' => Hash::make('password'),
        ]);
    }

    protected function makeCourseFor(User $mentor): Course
    {
        $category = Category::create(['category_name' => 'Category '.uniqid()]);

        return Course::create([
            'mentor_id' => $mentor->id,
            'category_id' => $category->id,
            'title' => 'Course '.uniqid(),
            'slug' => 'course-'.uniqid(),
            'description' => 'Test course',
            'status' => 'Published',
        ]);
    }

    protected function makeLesson(Course $course, array $overrides = []): Lesson
    {
        return Lesson::create(array_merge([
            'course_id' => $course->id,
            'title' => 'Lesson '.uniqid(),
            'content' => 'Lesson content',
            'order_number' => 1,
            'status' => 'Published',
        ], $overrides));
    }

    protected function makeAssessment(Lesson $lesson): Assessment
    {
        return Assessment::create([
            'lesson_id' => $lesson->id,
            'title' => 'Assessment '.uniqid(),
            'passing_score' => 50,
            'status' => 'Published',
        ]);
    }

    protected function makeQuestion(Assessment $assessment): Question
    {
        return Question::create([
            'assessment_id' => $assessment->id,
            'question_text' => 'Q?',
            'question_type' => 'MCQ',
            'option_a' => 'A', 'option_b' => 'B', 'option_c' => 'C', 'option_d' => 'D',
            'correct_answer' => 'A',
            'marks' => 1,
            'order_number' => 1,
        ]);
    }

    protected function enroll(User $student, Course $course): Enrollment
    {
        return Enrollment::create([
            'student_id' => $student->id,
            'course_id' => $course->id,
            'status' => 'Active',
            'enrolled_at' => now(),
        ]);
    }

    protected function storePayload(Course $course, array $resources, array $overrides = []): array
    {
        return array_merge([
            'title' => 'PHP Security Lesson',
            'description' => 'Learn secure PHP development.',
            'content' => '<h2>Introduction</h2><p>Lesson body.</p>',
            'order_number' => 1,
            'status' => 'Published',
            'resources' => $resources,
        ], $overrides);
    }

    // ---- Preview ----

    public function test_preview_returns_sanitised_content(): void
    {
        $mentor = $this->makeUser('Mentor', 'mentor@test.com');
        $course = $this->makeCourseFor($mentor);

        $this->actingAs($mentor)
            ->postJson("/courses/{$course->slug}/lessons/preview", [
                'content' => '<script>alert(1)</script><h2>Safe heading</h2>',
            ])
            ->assertOk()
            ->assertJson(['status' => 'S'])
            ->assertJsonPath('html', fn ($html) => str_contains($html, '<h2>Safe heading</h2>') && ! str_contains($html, '<script'));
    }

    // ---- Mentor/admin can attach resources ----

    public function test_mentor_can_create_lesson_with_document_link_and_video_resources(): void
    {
        Storage::fake('local');

        $mentor = $this->makeUser('Mentor', 'mentor@test.com');
        $course = $this->makeCourseFor($mentor);

        $response = $this->actingAs($mentor)->post("/courses/{$course->slug}/lessons", $this->storePayload($course, [
            ['type' => 'File', 'title' => 'PHP Security Notes', 'display_order' => 1,
                'file' => UploadedFile::fake()->create('notes.pdf', 100, 'application/pdf')],
            ['type' => 'Link', 'title' => 'OWASP Cheat Sheet', 'display_order' => 2,
                'url' => 'https://cheatsheetseries.owasp.org/cheatsheets/PHP_Security_Cheat_Sheet.html'],
            ['type' => 'Video', 'title' => 'Intro to PHP Security', 'display_order' => 3,
                'url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ'],
        ]));

        $response->assertRedirect(route('courses.lessons.index', $course));
        $response->assertSessionHas('success');

        $lesson = Lesson::where('course_id', $course->id)->firstOrFail();
        $this->assertSame('Learn secure PHP development.', $lesson->description);
        $this->assertStringContainsString('<h2>Introduction</h2>', $lesson->content);

        $resources = $lesson->resources;
        $this->assertCount(3, $resources);

        $file = $resources->firstWhere('type', 'File');
        $this->assertSame('PHP Security Notes', $file->title);
        $this->assertSame('application/pdf', $file->mime_type);
        $this->assertSame('notes.pdf', $file->file_name);
        $this->assertNotNull($file->file_path);
        Storage::disk('local')->assertExists($file->file_path);

        $link = $resources->firstWhere('type', 'Link');
        $this->assertSame('https://cheatsheetseries.owasp.org/cheatsheets/PHP_Security_Cheat_Sheet.html', $link->url);

        $video = $resources->firstWhere('type', 'Video');
        $this->assertSame('dQw4w9WgXcQ', $video->youtube_id);
    }

    // ---- Sanitization / stored XSS ----

    public function test_lesson_content_is_sanitised_against_stored_xss(): void
    {
        $mentor = $this->makeUser('Mentor', 'mentor@test.com');
        $course = $this->makeCourseFor($mentor);

        $this->actingAs($mentor)->post("/courses/{$course->slug}/lessons", $this->storePayload($course, [], [
            'content' => '<script>alert(1)</script><p onclick="steal()">Safe</p><a href="javascript:alert(1)">bad link</a><p>Keep me</p>',
        ]));

        $lesson = Lesson::where('course_id', $course->id)->firstOrFail();

        $this->assertStringNotContainsString('<script', $lesson->content);
        $this->assertStringNotContainsString('onclick', $lesson->content);
        $this->assertStringNotContainsString('javascript:', $lesson->content);
        $this->assertStringContainsString('Safe', $lesson->content);
        $this->assertStringContainsString('Keep me', $lesson->content);
    }

    // ---- Upload security ----

    public function test_file_upload_with_disallowed_mime_type_is_rejected(): void
    {
        $mentor = $this->makeUser('Mentor', 'mentor@test.com');
        $course = $this->makeCourseFor($mentor);

        $response = $this->actingAs($mentor)->post("/courses/{$course->slug}/lessons", $this->storePayload($course, [
            ['type' => 'File', 'title' => 'Evil script', 'display_order' => 1,
                'file' => UploadedFile::fake()->create('shell.php', 1, 'application/x-httpd-php')],
        ]));

        $response->assertSessionHasErrors('resources.0.file');
        $this->assertDatabaseCount('lessons', 0);
        $this->assertDatabaseCount('lesson_resources', 0);
    }

    public function test_file_upload_over_size_limit_is_rejected(): void
    {
        $mentor = $this->makeUser('Mentor', 'mentor@test.com');
        $course = $this->makeCourseFor($mentor);

        $response = $this->actingAs($mentor)->post("/courses/{$course->slug}/lessons", $this->storePayload($course, [
            ['type' => 'File', 'title' => 'Big file', 'display_order' => 1,
                'file' => UploadedFile::fake()->create('big.zip', 26000, 'application/zip')],
        ]));

        $response->assertSessionHasErrors('resources.0.file');
        $this->assertDatabaseCount('lessons', 0);
    }

    public function test_file_resource_without_a_file_is_rejected(): void
    {
        $mentor = $this->makeUser('Mentor', 'mentor@test.com');
        $course = $this->makeCourseFor($mentor);

        $response = $this->actingAs($mentor)->post("/courses/{$course->slug}/lessons", $this->storePayload($course, [
            ['type' => 'File', 'title' => 'No file', 'display_order' => 1],
        ]));

        $response->assertSessionHasErrors('resources.0.file');
        $this->assertDatabaseCount('lessons', 0);
    }

    // ---- URL / video validation ----

    public function test_video_resource_requires_a_youtube_url(): void
    {
        $mentor = $this->makeUser('Mentor', 'mentor@test.com');
        $course = $this->makeCourseFor($mentor);

        $response = $this->actingAs($mentor)->post("/courses/{$course->slug}/lessons", $this->storePayload($course, [
            ['type' => 'Video', 'title' => 'Not YouTube', 'display_order' => 1,
                'url' => 'https://example.com/video.mp4'],
        ]));

        $response->assertSessionHasErrors('resources.0.url');
        $this->assertDatabaseCount('lessons', 0);
    }

    public function test_link_resource_requires_a_valid_url(): void
    {
        $mentor = $this->makeUser('Mentor', 'mentor@test.com');
        $course = $this->makeCourseFor($mentor);

        $response = $this->actingAs($mentor)->post("/courses/{$course->slug}/lessons", $this->storePayload($course, [
            ['type' => 'Link', 'title' => 'Bad URL', 'display_order' => 1, 'url' => 'not-a-url'],
        ]));

        $response->assertSessionHasErrors('resources.0.url');
        $this->assertDatabaseCount('lessons', 0);
    }

    // ---- Ownership / authorisation ----

    public function test_mentor_cannot_add_resources_to_another_mentors_course(): void
    {
        $mentorA = $this->makeUser('Mentor', 'a@test.com');
        $mentorB = $this->makeUser('Mentor', 'b@test.com');
        $courseB = $this->makeCourseFor($mentorB);

        $this->actingAs($mentorA)
            ->post("/courses/{$courseB->slug}/lessons", $this->storePayload($courseB, []))
            ->assertForbidden();

        $this->assertDatabaseCount('lessons', 0);
    }

    // ---- Student reading page ----

    public function test_enrolled_student_can_view_published_lesson_with_resources(): void
    {
        Storage::fake('local');

        $mentor = $this->makeUser('Mentor', 'mentor@test.com');
        $student = $this->makeUser('Student', 'student@test.com');
        $course = $this->makeCourseFor($mentor);
        $this->enroll($student, $course);

        $lesson = $this->makeLesson($course, [
            'description' => 'Short intro',
            'content' => '<h2>Intro</h2><p>Body</p>',
        ]);

        LessonResource::create([
            'lesson_id' => $lesson->id,
            'type' => 'File',
            'title' => 'Notes',
            'file_path' => UploadedFile::fake()->create('notes.pdf', 5, 'application/pdf')->store('lessons/'.$lesson->id, 'local'),
            'file_name' => 'notes.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 5 * 1024,
            'display_order' => 1,
        ]);

        LessonResource::create([
            'lesson_id' => $lesson->id,
            'type' => 'Link',
            'title' => 'OWASP Guide',
            'url' => 'https://owasp.org/',
            'display_order' => 2,
        ]);

        $response = $this->actingAs($student)
            ->get("/student/courses/{$course->slug}/lessons/{$lesson->id}");

        $response->assertOk();
        $response->assertSee($lesson->title);
        $response->assertSee('Short intro');
        $response->assertSee('Intro', false); // inside sanitized HTML content
        $response->assertSee('Notes');
        $response->assertSee('OWASP Guide');
        $response->assertSee('Materials');
    }

    public function test_lesson_related_assessment_is_shown_to_student(): void
    {
        $mentor = $this->makeUser('Mentor', 'mentor@test.com');
        $student = $this->makeUser('Student', 'student@test.com');
        $course = $this->makeCourseFor($mentor);
        $this->enroll($student, $course);

        $lesson = $this->makeLesson($course);
        $assessment = $this->makeAssessment($lesson);
        $this->makeQuestion($assessment);

        $this->actingAs($student)
            ->get("/student/courses/{$course->slug}/lessons/{$lesson->id}")
            ->assertOk()
            ->assertSee($assessment->title)
            ->assertSee('Take Assessment');
    }

    public function test_non_enrolled_student_cannot_view_lesson(): void
    {
        $mentor = $this->makeUser('Mentor', 'mentor@test.com');
        $student = $this->makeUser('Student', 'student@test.com');
        $course = $this->makeCourseFor($mentor);
        $lesson = $this->makeLesson($course);

        $this->actingAs($student)
            ->get("/student/courses/{$course->slug}/lessons/{$lesson->id}")
            ->assertForbidden();
    }

    public function test_student_cannot_view_draft_lesson(): void
    {
        $mentor = $this->makeUser('Mentor', 'mentor@test.com');
        $student = $this->makeUser('Student', 'student@test.com');
        $course = $this->makeCourseFor($mentor);
        $this->enroll($student, $course);

        $lesson = $this->makeLesson($course, ['status' => 'Draft']);

        $this->actingAs($student)
            ->get("/student/courses/{$course->slug}/lessons/{$lesson->id}")
            ->assertNotFound();
    }

    // ---- Student downloads ----

    public function test_enrolled_student_can_download_a_file_resource(): void
    {
        Storage::fake('local');

        $mentor = $this->makeUser('Mentor', 'mentor@test.com');
        $student = $this->makeUser('Student', 'student@test.com');
        $course = $this->makeCourseFor($mentor);
        $this->enroll($student, $course);

        $lesson = $this->makeLesson($course);

        $path = UploadedFile::fake()->create('notes.pdf', 5, 'application/pdf')->store('lessons/'.$lesson->id, 'local');
        $resource = LessonResource::create([
            'lesson_id' => $lesson->id,
            'type' => 'File',
            'title' => 'Notes',
            'file_path' => $path,
            'file_name' => 'notes.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 5 * 1024,
            'display_order' => 1,
        ]);

        $this->actingAs($student)
            ->get("/student/courses/{$course->slug}/lessons/{$lesson->id}/resources/{$resource->id}/download")
            ->assertOk()
            ->assertDownload('notes.pdf');
    }

    public function test_non_enrolled_student_cannot_download_a_file_resource(): void
    {
        Storage::fake('local');

        $mentor = $this->makeUser('Mentor', 'mentor@test.com');
        $student = $this->makeUser('Student', 'student@test.com');
        $course = $this->makeCourseFor($mentor);

        $lesson = $this->makeLesson($course);
        $path = UploadedFile::fake()->create('notes.pdf', 5, 'application/pdf')->store('lessons/'.$lesson->id, 'local');
        $resource = LessonResource::create([
            'lesson_id' => $lesson->id,
            'type' => 'File',
            'title' => 'Notes',
            'file_path' => $path,
            'file_name' => 'notes.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 5 * 1024,
            'display_order' => 1,
        ]);

        $this->actingAs($student)
            ->get("/student/courses/{$course->slug}/lessons/{$lesson->id}/resources/{$resource->id}/download")
            ->assertForbidden();
    }

    // ---- Resource sync on edit ----

    public function test_editing_a_lesson_syncs_resources_and_removes_deleted_ones(): void
    {
        Storage::fake('local');

        $mentor = $this->makeUser('Mentor', 'mentor@test.com');
        $course = $this->makeCourseFor($mentor);
        $lesson = $this->makeLesson($course);

        $keep = LessonResource::create([
            'lesson_id' => $lesson->id,
            'type' => 'Link',
            'title' => 'Keep Me',
            'url' => 'https://example.com/keep',
            'display_order' => 1,
        ]);

        $remove = LessonResource::create([
            'lesson_id' => $lesson->id,
            'type' => 'File',
            'title' => 'Remove Me',
            'file_path' => UploadedFile::fake()->create('old.pdf', 5, 'application/pdf')->store('lessons/'.$lesson->id, 'local'),
            'file_name' => 'old.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 5 * 1024,
            'display_order' => 2,
        ]);

        $oldPath = $remove->file_path;

        $this->actingAs($mentor)->put("/courses/{$course->slug}/lessons/{$lesson->id}", [
            'title' => 'Updated',
            'description' => '',
            'content' => 'Updated content',
            'order_number' => 1,
            'estimated_duration' => null,
            'status' => 'Published',
            'resources' => [
                ['id' => $keep->id, 'type' => 'Link', 'title' => 'Keep Me Updated', 'description' => '', 'url' => 'https://example.com/keep', 'display_order' => 1],
            ],
        ])->assertRedirect(route('courses.lessons.index', $course));

        $this->assertDatabaseMissing('lesson_resources', ['id' => $remove->id]);
        Storage::disk('local')->assertMissing($oldPath);

        $this->assertSame('Keep Me Updated', $keep->fresh()->title);
        $this->assertSame('Updated content', $lesson->fresh()->content);
    }
}
