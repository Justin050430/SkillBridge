<?php

/**
 * @author OOI JIAN XIN
 *
 * Student publication-control tests.
 *
 * A student may only see a Lesson when lessons.status = Published, and may
 * only see/access an Assessment when BOTH the assessment AND its parent lesson
 * are Published. Direct URL access (take/submit/result) to an assessment whose
 * lesson is Draft must be rejected server-side (404). Admin/Mentor management
 * pages are not affected.
 */

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\Category;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\Question;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class StudentPublicationAccessTest extends TestCase
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

    protected function makeLesson(Course $course, string $status): Lesson
    {
        return Lesson::create([
            'course_id' => $course->id,
            'title' => 'Lesson '.uniqid(),
            'content' => 'Content',
            'order_number' => 1,
            'status' => $status,
        ]);
    }

    protected function makeAssessment(Lesson $lesson, string $status): Assessment
    {
        return Assessment::create([
            'lesson_id' => $lesson->id,
            'title' => 'Assessment '.uniqid(),
            'passing_score' => 50,
            'status' => $status,
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

    protected function makeQuestion(Assessment $assessment): void
    {
        $assessment->questions()->create([
            'question_text' => 'Question?',
            'question_type' => 'MCQ',
            'option_a' => 'A',
            'option_b' => 'B',
            'option_c' => 'C',
            'option_d' => 'D',
            'correct_answer' => 'A',
            'marks' => 1,
            'order_number' => 1,
        ]);
    }

    // 1. Published Lesson + Published Assessment -> both accessible.

    public function test_published_lesson_with_published_assessment_is_fully_accessible(): void
    {
        $student = $this->makeUser('Student', 'student@test.com');
        $mentor = $this->makeUser('Mentor', 'mentor@test.com');
        $course = $this->makeCourseFor($mentor);
        $lesson = $this->makeLesson($course, 'Published');
        $assessment = $this->makeAssessment($lesson, 'Published');
        $this->makeQuestion($assessment);
        $this->enroll($student, $course);

        // Lesson visible on the progress page.
        $this->actingAs($student)->get('/student/progress')
            ->assertOk()
            ->assertSee($lesson->title);

        // Assessment listed and the take page is accessible.
        $this->actingAs($student)->get('/student/assessments')
            ->assertOk()
            ->assertSee($assessment->title);

        $this->actingAs($student)->get('/student/assessments/'.$assessment->slug.'/take')
            ->assertOk();
    }

    // 2. Published Lesson + Draft Assessment -> lesson visible, assessment hidden.

    public function test_published_lesson_with_draft_assessment_hides_only_the_assessment(): void
    {
        $student = $this->makeUser('Student', 'student@test.com');
        $mentor = $this->makeUser('Mentor', 'mentor@test.com');
        $course = $this->makeCourseFor($mentor);
        $lesson = $this->makeLesson($course, 'Published');
        $assessment = $this->makeAssessment($lesson, 'Draft');
        $this->enroll($student, $course);

        // Lesson remains visible.
        $this->actingAs($student)->get('/student/progress')
            ->assertOk()
            ->assertSee($lesson->title);

        // Assessment hidden from the list and the take page is rejected.
        $this->actingAs($student)->get('/student/assessments')
            ->assertOk()
            ->assertDontSee($assessment->title);

        $this->actingAs($student)->get('/student/assessments/'.$assessment->slug.'/take')
            ->assertNotFound();
    }

    // 3. Draft Lesson + Published Assessment -> lesson and assessment both hidden.

    public function test_draft_lesson_with_published_assessment_is_hidden_everywhere(): void
    {
        $student = $this->makeUser('Student', 'student@test.com');
        $mentor = $this->makeUser('Mentor', 'mentor@test.com');
        $course = $this->makeCourseFor($mentor);
        $lesson = $this->makeLesson($course, 'Draft');
        $assessment = $this->makeAssessment($lesson, 'Published');
        $this->enroll($student, $course);

        // Lesson hidden from the progress page.
        $this->actingAs($student)->get('/student/progress')
            ->assertOk()
            ->assertDontSee($lesson->title);

        // Assessment hidden from the list and the take page is rejected.
        $this->actingAs($student)->get('/student/assessments')
            ->assertOk()
            ->assertDontSee($assessment->title);

        $this->actingAs($student)->get('/student/assessments/'.$assessment->slug.'/take')
            ->assertNotFound();
    }

    // 4. Draft Lesson + Draft Assessment -> both hidden.

    public function test_draft_lesson_with_draft_assessment_is_hidden_everywhere(): void
    {
        $student = $this->makeUser('Student', 'student@test.com');
        $mentor = $this->makeUser('Mentor', 'mentor@test.com');
        $course = $this->makeCourseFor($mentor);
        $lesson = $this->makeLesson($course, 'Draft');
        $assessment = $this->makeAssessment($lesson, 'Draft');
        $this->enroll($student, $course);

        $this->actingAs($student)->get('/student/progress')
            ->assertOk()
            ->assertDontSee($lesson->title);

        $this->actingAs($student)->get('/student/assessments')
            ->assertOk()
            ->assertDontSee($assessment->title);

        $this->actingAs($student)->get('/student/assessments/'.$assessment->slug.'/take')
            ->assertNotFound();
    }

    // 5. Direct URL access to an assessment under a Draft lesson is rejected.

    public function test_direct_url_to_assessment_under_draft_lesson_is_rejected(): void
    {
        $student = $this->makeUser('Student', 'student@test.com');
        $mentor = $this->makeUser('Mentor', 'mentor@test.com');
        $course = $this->makeCourseFor($mentor);
        $lesson = $this->makeLesson($course, 'Draft');
        $assessment = $this->makeAssessment($lesson, 'Published');
        $this->enroll($student, $course);

        // take, submit and result are all rejected for an assessment whose
        // parent lesson is Draft - the student cannot bypass the UI.
        $this->actingAs($student)->get('/student/assessments/'.$assessment->slug.'/take')
            ->assertNotFound();

        $this->actingAs($student)->post('/student/assessments/'.$assessment->slug.'/submit', ['answers' => []])
            ->assertNotFound();

        $this->actingAs($student)->get('/student/assessments/'.$assessment->slug.'/result')
            ->assertNotFound();
    }

    // Admin/Mentor management pages are NOT affected.

    public function test_admin_can_still_manage_an_assessment_under_a_draft_lesson(): void
    {
        $admin = $this->makeUser('Admin', 'admin@test.com');
        $mentor = $this->makeUser('Mentor', 'mentor@test.com');
        $course = $this->makeCourseFor($mentor);
        $lesson = $this->makeLesson($course, 'Draft');
        $assessment = $this->makeAssessment($lesson, 'Draft');

        $this->actingAs($admin)->get('/assessments/'.$assessment->slug.'/edit')
            ->assertOk();
    }
}
