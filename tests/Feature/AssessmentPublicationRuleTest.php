<?php

/**
 * @author OOI JIAN XIN
 *
 * Assessment publication invariant tests:
 *   An assessment must contain at least one question before it can be
 *   Published. Enforcement points:
 *    - create/update Form Request validation (server-side).
 *    - deleting the last question of a Published assessment auto-reverts to
 *      Draft.
 *    - student listing/take guards (Published + Published lesson + >=1
 *      question).
 *   Admin/Mentor management permissions are unchanged.
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

class AssessmentPublicationRuleTest extends TestCase
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

    protected function makeLesson(Course $course, string $status = 'Published'): Lesson
    {
        return Lesson::create([
            'course_id' => $course->id,
            'title' => 'Lesson '.uniqid(),
            'content' => 'Content',
            'order_number' => 1,
            'status' => $status,
        ]);
    }

    protected function makeAssessment(Lesson $lesson, string $status, array $overrides = []): Assessment
    {
        return Assessment::create(array_merge([
            'lesson_id' => $lesson->id,
            'title' => 'Assessment '.uniqid(),
            'passing_score' => 50,
            'status' => $status,
        ], $overrides));
    }

    protected function makeQuestion(Assessment $assessment, array $overrides = []): Question
    {
        return $assessment->questions()->create(array_merge([
            'question_text' => 'Question?',
            'question_type' => 'MCQ',
            'option_a' => 'A',
            'option_b' => 'B',
            'option_c' => 'C',
            'option_d' => 'D',
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

    // 1. Empty assessment cannot be published.

    public function test_empty_assessment_cannot_be_created_as_published(): void
    {
        $admin = $this->makeUser('Admin', 'admin@test.com');
        $mentor = $this->makeUser('Mentor', 'mentor@test.com');
        $lesson = $this->makeLesson($this->makeCourseFor($mentor));

        $this->actingAs($admin)
            ->post('/assessments', [
                'lesson_id' => $lesson->id,
                'title' => 'Empty Quiz',
                'passing_score' => 50,
                'status' => 'Published',
            ])
            ->assertSessionHasErrors('status');

        $this->assertDatabaseMissing('assessments', ['title' => 'Empty Quiz']);
    }

    public function test_empty_assessment_cannot_be_changed_to_published_on_update(): void
    {
        $admin = $this->makeUser('Admin', 'admin@test.com');
        $mentor = $this->makeUser('Mentor', 'mentor@test.com');
        $assessment = $this->makeAssessment($this->makeLesson($this->makeCourseFor($mentor)), 'Draft');

        $this->actingAs($admin)
            ->put('/assessments/'.$assessment->slug, [
                'lesson_id' => $assessment->lesson_id,
                'title' => $assessment->title,
                'passing_score' => 50,
                'status' => 'Published',
            ])
            ->assertSessionHasErrors('status');

        $this->assertSame('Draft', $assessment->fresh()->status);
    }

    // 2. Assessment with one question can be published.

    public function test_assessment_with_one_question_can_be_published(): void
    {
        $admin = $this->makeUser('Admin', 'admin@test.com');
        $mentor = $this->makeUser('Mentor', 'mentor@test.com');
        $assessment = $this->makeAssessment($this->makeLesson($this->makeCourseFor($mentor)), 'Draft');
        $this->makeQuestion($assessment);

        $this->actingAs($admin)
            ->put('/assessments/'.$assessment->slug, [
                'lesson_id' => $assessment->lesson_id,
                'title' => $assessment->title,
                'passing_score' => 50,
                'status' => 'Published',
            ])
            ->assertRedirect('/assessments');

        $this->assertSame('Published', $assessment->fresh()->status);
    }

    // 3. Published assessment with one question is visible to eligible students.

    public function test_published_assessment_with_one_question_is_visible_to_student(): void
    {
        $student = $this->makeUser('Student', 'student@test.com');
        $mentor = $this->makeUser('Mentor', 'mentor@test.com');
        $course = $this->makeCourseFor($mentor);
        $assessment = $this->makeAssessment($this->makeLesson($course), 'Published');
        $this->makeQuestion($assessment);
        $this->enroll($student, $course);

        $this->actingAs($student)->get('/student/assessments')
            ->assertOk()
            ->assertSee($assessment->title);

        $this->actingAs($student)->startAssessment($assessment)
            ->assertOk();
    }

    // 4. Published assessment with zero questions is not visible to students.

    public function test_published_assessment_with_zero_questions_is_not_visible_to_student(): void
    {
        $student = $this->makeUser('Student', 'student@test.com');
        $mentor = $this->makeUser('Mentor', 'mentor@test.com');
        $course = $this->makeCourseFor($mentor);
        $assessment = $this->makeAssessment($this->makeLesson($course), 'Published');
        $this->enroll($student, $course);

        $this->actingAs($student)->get('/student/assessments')
            ->assertOk()
            ->assertDontSee($assessment->title);
    }

    // 5. Student cannot directly access a published assessment with zero questions.

    public function test_student_cannot_directly_access_published_assessment_with_zero_questions(): void
    {
        $student = $this->makeUser('Student', 'student@test.com');
        $mentor = $this->makeUser('Mentor', 'mentor@test.com');
        $course = $this->makeCourseFor($mentor);
        $assessment = $this->makeAssessment($this->makeLesson($course), 'Published');
        $this->enroll($student, $course);

        $this->actingAs($student)->startAssessment($assessment)
            ->assertNotFound();

        $this->actingAs($student)->post('/student/assessments/'.$assessment->slug.'/submit', ['attempt_id' => $assessment->attempts()->latest('id')->value('id') ?? 1, 'answers' => []])
            ->assertNotFound();
    }

    // 6. Deleting the final question reverts a Published assessment to Draft.

    public function test_deleting_final_question_reverts_published_assessment_to_draft(): void
    {
        $admin = $this->makeUser('Admin', 'admin@test.com');
        $mentor = $this->makeUser('Mentor', 'mentor@test.com');
        $assessment = $this->makeAssessment($this->makeLesson($this->makeCourseFor($mentor)), 'Published');
        $question = $this->makeQuestion($assessment);

        $this->actingAs($admin)
            ->delete('/assessments/'.$assessment->slug.'/questions/'.$question->id)
            ->assertRedirect();

        $this->assertSame('Draft', $assessment->fresh()->status);
    }

    // 7. Draft parent lesson blocks access even with questions present.

    public function test_draft_lesson_blocks_student_even_when_assessment_has_questions(): void
    {
        $student = $this->makeUser('Student', 'student@test.com');
        $mentor = $this->makeUser('Mentor', 'mentor@test.com');
        $course = $this->makeCourseFor($mentor);
        $assessment = $this->makeAssessment($this->makeLesson($course, 'Draft'), 'Published');
        $this->makeQuestion($assessment);
        $this->enroll($student, $course);

        $this->actingAs($student)->get('/student/assessments')
            ->assertOk()
            ->assertDontSee($assessment->title);

        $this->actingAs($student)->startAssessment($assessment)
            ->assertNotFound();
    }

    // 8. Admin/Mentor management permissions remain unchanged.

    public function test_admin_can_still_create_a_draft_assessment_with_no_questions(): void
    {
        $admin = $this->makeUser('Admin', 'admin@test.com');
        $mentor = $this->makeUser('Mentor', 'mentor@test.com');
        $lesson = $this->makeLesson($this->makeCourseFor($mentor));

        $this->actingAs($admin)
            ->post('/assessments', [
                'lesson_id' => $lesson->id,
                'title' => 'Draft Quiz',
                'passing_score' => 50,
                'status' => 'Draft',
            ])
            ->assertRedirect('/assessments');

        $this->assertDatabaseHas('assessments', ['title' => 'Draft Quiz', 'status' => 'Draft']);
    }

    public function test_mentor_can_publish_own_assessment_that_has_a_question(): void
    {
        $mentor = $this->makeUser('Mentor', 'mentor@test.com');
        $assessment = $this->makeAssessment($this->makeLesson($this->makeCourseFor($mentor)), 'Draft');
        $this->makeQuestion($assessment);

        $this->actingAs($mentor)
            ->put('/assessments/'.$assessment->slug, [
                'lesson_id' => $assessment->lesson_id,
                'title' => $assessment->title,
                'passing_score' => 50,
                'status' => 'Published',
            ])
            ->assertRedirect('/assessments');

        $this->assertSame('Published', $assessment->fresh()->status);
    }

    public function test_mentor_cannot_publish_assessment_on_another_mentors_lesson(): void
    {
        $mentorA = $this->makeUser('Mentor', 'mentorA@test.com');
        $mentorB = $this->makeUser('Mentor', 'mentorB@test.com');
        $lessonB = $this->makeLesson($this->makeCourseFor($mentorB));

        $this->actingAs($mentorA)
            ->post('/assessments', [
                'lesson_id' => $lessonB->id,
                'title' => 'Should Fail',
                'passing_score' => 50,
                'status' => 'Draft',
            ])
            ->assertSessionHasErrors('lesson_id');
    }
}
