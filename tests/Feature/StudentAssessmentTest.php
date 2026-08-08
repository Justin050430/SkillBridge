<?php

/**
 * @author OOI JIAN XIN
 *
 * Feature tests for the student enrolment, take-assessment and progress flows.
 */

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\Category;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\Question;
use App\Models\Role;
use App\Models\StudentAnswer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class StudentAssessmentTest extends TestCase
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

    protected function makeLesson(Course $course, int $order = 1): Lesson
    {
        return Lesson::create([
            'course_id' => $course->id,
            'title' => 'Lesson '.uniqid(),
            'content' => 'Content',
            'order_number' => $order,
            'status' => 'Published',
        ]);
    }

    protected function makeAssessment(Lesson $lesson, array $overrides = []): Assessment
    {
        return Assessment::create(array_merge([
            'lesson_id' => $lesson->id,
            'title' => 'Assessment '.uniqid(),
            'passing_score' => 50,
            'status' => 'Published',
        ], $overrides));
    }

    protected function makeQuestion(Assessment $assessment, array $overrides = []): Question
    {
        return Question::create(array_merge([
            'assessment_id' => $assessment->id,
            'question_text' => 'Question?',
            'question_type' => 'MCQ',
            'option_a' => 'Option A',
            'option_b' => 'Option B',
            'option_c' => 'Option C',
            'option_d' => 'Option D',
            'correct_answer' => 'A',
            'marks' => 1,
            'order_number' => 1,
        ], $overrides));
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

    public function test_student_can_enroll_in_published_course(): void
    {
        $student = $this->makeUser('Student', 'student@test.com');
        $mentor = $this->makeUser('Mentor', 'mentor@test.com');
        $course = $this->makeCourseFor($mentor);

        $this->actingAs($student)
            ->post('/student/courses/'.$course->slug.'/enroll')
            ->assertRedirect();

        $this->assertDatabaseHas('enrollments', [
            'student_id' => $student->id,
            'course_id' => $course->id,
            'status' => 'Active',
        ]);
    }

    public function test_student_cannot_enroll_twice(): void
    {
        $student = $this->makeUser('Student', 'student@test.com');
        $mentor = $this->makeUser('Mentor', 'mentor@test.com');
        $course = $this->makeCourseFor($mentor);
        $this->enroll($student, $course);

        $this->actingAs($student)
            ->post('/student/courses/'.$course->slug.'/enroll')
            ->assertRedirect();

        $this->assertEquals(
            1,
            Enrollment::where('student_id', $student->id)->where('course_id', $course->id)->count()
        );
    }

    public function test_student_only_sees_assessments_for_enrolled_courses(): void
    {
        $student = $this->makeUser('Student', 'student@test.com');
        $mentor = $this->makeUser('Mentor', 'mentor@test.com');

        $enrolledCourse = $this->makeCourseFor($mentor);
        $otherCourse = $this->makeCourseFor($mentor);

        $this->enroll($student, $enrolledCourse);

        $assessmentA = $this->makeAssessment($this->makeLesson($enrolledCourse));
        $assessmentB = $this->makeAssessment($this->makeLesson($otherCourse));

        $this->actingAs($student)
            ->get('/student/assessments')
            ->assertOk()
            ->assertSee($assessmentA->title)
            ->assertDontSee($assessmentB->title);
    }

    public function test_non_enrolled_student_is_blocked_from_taking_assessment(): void
    {
        $student = $this->makeUser('Student', 'student@test.com');
        $mentor = $this->makeUser('Mentor', 'mentor@test.com');
        $course = $this->makeCourseFor($mentor);
        $assessment = $this->makeAssessment($this->makeLesson($course));

        $this->actingAs($student)
            ->get('/student/assessments/'.$assessment->slug.'/take')
            ->assertForbidden();
    }

    public function test_submitting_an_assessment_grades_and_saves_answers(): void
    {
        $student = $this->makeUser('Student', 'student@test.com');
        $mentor = $this->makeUser('Mentor', 'mentor@test.com');
        $course = $this->makeCourseFor($mentor);
        $enrollment = $this->enroll($student, $course);

        $assessment = $this->makeAssessment($this->makeLesson($course));

        $q1 = $this->makeQuestion($assessment, [
            'question_type' => 'MCQ',
            'correct_answer' => 'B',
            'marks' => 2,
            'order_number' => 1,
        ]);

        $q2 = $this->makeQuestion($assessment, [
            'question_text' => 'True or false?',
            'question_type' => 'TrueFalse',
            'correct_answer' => 'True',
            'marks' => 1,
            'order_number' => 2,
        ]);

        // Start the attempt (as the student would by opening the assessment).
        $this->actingAs($student)
            ->get('/student/assessments/'.$assessment->slug.'/take')
            ->assertOk();

        $this->actingAs($student)
            ->post('/student/assessments/'.$assessment->slug.'/submit', [
                'answers' => [
                    $q1->id => 'B',
                    $q2->id => 'False',
                ],
            ])
            ->assertRedirect('/student/assessments/'.$assessment->slug.'/result');

        $attempt = AssessmentAttempt::where('enrollment_id', $enrollment->id)
            ->where('assessment_id', $assessment->id)
            ->first();

        $this->assertNotNull($attempt);
        $this->assertEquals(2, $attempt->score);
        $this->assertEqualsWithDelta(66.67, (float) $attempt->percentage, 0.01);
        $this->assertTrue((bool) $attempt->is_passed);
        $this->assertNotNull($attempt->submitted_at);

        $this->assertEquals(2, StudentAnswer::where('attempt_id', $attempt->id)->count());

        $this->assertDatabaseHas('student_answers', [
            'attempt_id' => $attempt->id,
            'question_id' => $q1->id,
            'submitted_answer' => 'B',
            'is_correct' => 1,
            'marks_awarded' => 2,
        ]);

        $this->assertDatabaseHas('student_answers', [
            'attempt_id' => $attempt->id,
            'question_id' => $q2->id,
            'submitted_answer' => 'False',
            'is_correct' => 0,
            'marks_awarded' => 0,
        ]);
    }

    public function test_max_attempts_are_enforced(): void
    {
        $student = $this->makeUser('Student', 'student@test.com');
        $mentor = $this->makeUser('Mentor', 'mentor@test.com');
        $course = $this->makeCourseFor($mentor);
        $this->enroll($student, $course);

        $assessment = $this->makeAssessment($this->makeLesson($course), ['max_attempts' => 1]);
        $this->makeQuestion($assessment);

        // First attempt.
        $this->actingAs($student)->get('/student/assessments/'.$assessment->slug.'/take');
        $this->actingAs($student)->post('/student/assessments/'.$assessment->slug.'/submit', ['answers' => []]);

        // Second take should be blocked (redirect back to the list).
        $this->actingAs($student)
            ->get('/student/assessments/'.$assessment->slug.'/take')
            ->assertRedirect('/student/assessments');
    }

    public function test_student_can_mark_lesson_complete(): void
    {
        $student = $this->makeUser('Student', 'student@test.com');
        $mentor = $this->makeUser('Mentor', 'mentor@test.com');
        $course = $this->makeCourseFor($mentor);
        $enrollment = $this->enroll($student, $course);
        $lesson = $this->makeLesson($course);

        $this->actingAs($student)
            ->post('/student/progress/'.$lesson->id, ['status' => 'Completed'])
            ->assertRedirect();

        $this->assertDatabaseHas('progress', [
            'enrollment_id' => $enrollment->id,
            'lesson_id' => $lesson->id,
            'status' => 'Completed',
        ]);
    }
}
