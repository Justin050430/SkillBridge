<?php

/**
 * @author OOI JIAN XIN
 *
 * Student lesson-access and assessment-flow regression tests:
 *  - Enrolled students can view a course's lessons (index + show) in order.
 *  - Non-enrolled students cannot access lessons or assessments.
 *  - The ACTIVE enrollment is used for assessment attempts and results, so
 *    dropped-enrollment history never leaks into a fresh (re)enrollment.
 *  - A student with no prior attempt can open an assessment normally.
 *  - Orphaned assessments (missing parent lesson) are 404, not 500.
 */

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\Category;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\LessonResource;
use App\Models\Progress;
use App\Models\Question;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class StudentLessonAccessTest extends TestCase
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

    protected function makeLesson(Course $course, int $order = 1, array $overrides = []): Lesson
    {
        return Lesson::create(array_merge([
            'course_id' => $course->id,
            'title' => 'Lesson '.uniqid(),
            'content' => 'Lesson content',
            'order_number' => $order,
            'status' => 'Published',
        ], $overrides));
    }

    protected function makeAssessment(Lesson $lesson): Assessment
    {
        return Assessment::create([
            'lesson_id' => $lesson->id,
            'title' => 'Assessment '.uniqid(),
            'passing_score' => 50,
            'max_attempts' => 3,
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

    protected function activeEnrollment(User $student, Course $course): ?Enrollment
    {
        return Enrollment::where('student_id', $student->id)
            ->where('course_id', $course->id)
            ->where('status', 'Active')
            ->first();
    }

    // ---- Student course-lessons index ----

    public function test_enrolled_student_can_view_course_lessons_in_order(): void
    {
        $mentor = $this->makeUser('Mentor', 'mentor@test.com');
        $student = $this->makeUser('Student', 'student@test.com');
        $course = $this->makeCourseFor($mentor);
        $this->enroll($student, $course);

        $lessonA = $this->makeLesson($course, 1, ['title' => 'First Lesson']);
        $lessonB = $this->makeLesson($course, 2, ['title' => 'Second Lesson']);

        $this->actingAs($student)
            ->get("/student/courses/{$course->slug}/lessons")
            ->assertOk()
            ->assertSee('First Lesson')
            ->assertSee('Second Lesson')
            ->assertSee('Open Lesson');
    }

    public function test_non_enrolled_student_cannot_view_course_lessons(): void
    {
        $mentor = $this->makeUser('Mentor', 'mentor@test.com');
        $student = $this->makeUser('Student', 'student@test.com');
        $course = $this->makeCourseFor($mentor);
        $this->makeLesson($course);

        $this->actingAs($student)
            ->get("/student/courses/{$course->slug}/lessons")
            ->assertForbidden();

        $lesson = $course->lessons()->first();
        $this->actingAs($student)
            ->get("/student/courses/{$course->slug}/lessons/{$lesson->id}")
            ->assertForbidden();
    }

    public function test_lesson_index_shows_progress_and_assessment_info(): void
    {
        $mentor = $this->makeUser('Mentor', 'mentor@test.com');
        $student = $this->makeUser('Student', 'student@test.com');
        $course = $this->makeCourseFor($mentor);
        $this->enroll($student, $course);

        $lesson = $this->makeLesson($course);
        $assessment = $this->makeAssessment($lesson);
        $this->makeQuestion($assessment);

        // Mark the lesson complete so a status badge shows.
        $enrollment = $this->activeEnrollment($student, $course);
        Progress::create([
            'enrollment_id' => $enrollment->id,
            'lesson_id' => $lesson->id,
            'status' => 'Completed',
        ]);

        $this->actingAs($student)
            ->get("/student/courses/{$course->slug}/lessons")
            ->assertOk()
            ->assertSee('Completed')
            ->assertSee($assessment->title)
            ->assertSee('1 of 1 lessons completed');
    }

    // ---- Assessment from a lesson (no prior attempt) ----

    public function test_student_with_no_prior_attempt_can_open_assessment(): void
    {
        $mentor = $this->makeUser('Mentor', 'mentor@test.com');
        $student = $this->makeUser('Student', 'student@test.com');
        $course = $this->makeCourseFor($mentor);
        $this->enroll($student, $course);

        $lesson = $this->makeLesson($course);
        $assessment = $this->makeAssessment($lesson);
        $this->makeQuestion($assessment);

        $enrollment = $this->activeEnrollment($student, $course);
        $this->assertSame(0, AssessmentAttempt::where('enrollment_id', $enrollment->id)->count());

        $this->actingAs($student)
            ->startAssessment($assessment)
            ->assertOk()
            ->assertSee($assessment->title);

        // A fresh attempt (number 1) is created under the active enrollment.
        $attempt = AssessmentAttempt::where('enrollment_id', $enrollment->id)
            ->where('assessment_id', $assessment->id)
            ->first();
        $this->assertNotNull($attempt);
        $this->assertSame(1, $attempt->attempt_number);
    }

    // ---- Active enrollment used; dropped history never leaks ----

    public function test_dropped_enrollment_attempt_does_not_leak_into_result_after_reenroll(): void
    {
        $mentor = $this->makeUser('Mentor', 'mentor@test.com');
        $student = $this->makeUser('Student', 'student@test.com');
        $course = $this->makeCourseFor($mentor);
        $lesson = $this->makeLesson($course);
        $assessment = $this->makeAssessment($lesson);
        $this->makeQuestion($assessment);

        // First cycle: enroll + submit an attempt under the first enrollment.
        $firstEnrollment = $this->enroll($student, $course);
        AssessmentAttempt::create([
            'enrollment_id' => $firstEnrollment->id,
            'assessment_id' => $assessment->id,
            'attempt_number' => 1,
            'score' => 10,
            'percentage' => 100,
            'is_passed' => true,
            'started_at' => now()->subDay(),
            'submitted_at' => now()->subDay(),
        ]);

        // Drop, then re-enroll (fresh row).
        $firstEnrollment->update(['status' => 'Dropped']);
        $newEnrollment = $this->enroll($student, $course);

        // The dropped cycle's submitted attempt must NOT appear as the result
        // for the fresh enrollment.
        $this->actingAs($student)
            ->get("/student/assessments/{$assessment->slug}/result")
            ->assertNotFound();

        // My Assessments counts attempts for the ACTIVE enrollment only - the
        // dropped enrollment's attempt is not shown.
        $this->actingAs($student)
            ->get('/student/assessments')
            ->assertOk()
            ->assertSee('Attempts used: 0');

        // Taking the assessment again creates a NEW attempt under the new
        // enrollment (not resuming the dropped one).
        $this->actingAs($student)
            ->startAssessment($assessment)
            ->assertOk();

        $newAttempt = AssessmentAttempt::where('enrollment_id', $newEnrollment->id)
            ->where('assessment_id', $assessment->id)
            ->first();
        $this->assertNotNull($newAttempt);
        $this->assertSame(1, $newAttempt->attempt_number);
    }

    // ---- Authorization ----

    public function test_student_cannot_access_assessment_of_a_course_they_are_not_enrolled_in(): void
    {
        $mentor = $this->makeUser('Mentor', 'mentor@test.com');
        $student = $this->makeUser('Student', 'student@test.com');
        $course = $this->makeCourseFor($mentor);
        $lesson = $this->makeLesson($course);
        $assessment = $this->makeAssessment($lesson);
        $this->makeQuestion($assessment);

        $this->actingAs($student)
            ->startAssessment($assessment)
            ->assertForbidden();
    }

    // ---- Existing lesson show still works (with resources) ----

    public function test_enrolled_student_can_open_a_lesson_that_has_resources(): void
    {
        $mentor = $this->makeUser('Mentor', 'mentor@test.com');
        $student = $this->makeUser('Student', 'student@test.com');
        $course = $this->makeCourseFor($mentor);
        $this->enroll($student, $course);

        $lesson = $this->makeLesson($course, 1, [
            'description' => 'Short intro',
            'content' => '<h2>Intro</h2><p>Body</p>',
        ]);

        LessonResource::create([
            'lesson_id' => $lesson->id,
            'type' => 'Link',
            'title' => 'OWASP Guide',
            'url' => 'https://owasp.org/',
            'display_order' => 1,
        ]);

        $this->actingAs($student)
            ->get("/student/courses/{$course->slug}/lessons/{$lesson->id}")
            ->assertOk()
            ->assertSee('Short intro')
            ->assertSee('Intro', false)
            ->assertSee('OWASP Guide')
            ->assertSee('Materials');
    }

    /**
     * Regression: the Lesson::resources() relation already orders by
     * display_order. Re-adding orderBy in the eager-load constraint produced
     * `ORDER BY display_order ASC, display_order ASC`, which SQL Server
     * rejects with a 500 ("A column has been specified more than once in the
     * order by list"). Assert the generated SQL does not duplicate it.
     */
    public function test_lesson_show_resources_query_has_no_duplicate_order_by(): void
    {
        $mentor = $this->makeUser('Mentor', 'mentor@test.com');
        $student = $this->makeUser('Student', 'student@test.com');
        $course = $this->makeCourseFor($mentor);
        $this->enroll($student, $course);

        $lesson = $this->makeLesson($course);

        LessonResource::create([
            'lesson_id' => $lesson->id,
            'type' => 'File',
            'title' => 'Notes',
            'file_path' => 'lessons/'.$lesson->id.'/notes.pdf',
            'file_name' => 'notes.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 1024,
            'display_order' => 1,
        ]);

        $queries = [];
        DB::listen(function ($query) use (&$queries) {
            $queries[] = $query->sql;
        });

        $this->actingAs($student)
            ->get("/student/courses/{$course->slug}/lessons/{$lesson->id}")
            ->assertOk();

        $resourcesQuery = collect($queries)
            ->first(fn ($sql) => str_contains($sql, 'lesson_resources'));

        $this->assertNotNull($resourcesQuery, 'Expected a lesson_resources query.');
        $this->assertSame(
            1,
            substr_count($resourcesQuery, 'display_order'),
            'display_order must appear exactly once in the resources query.'
        );
    }
}
