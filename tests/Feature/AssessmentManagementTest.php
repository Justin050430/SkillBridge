<?php

/**
 * @author OOI JIAN XIN
 *
 * Feature tests for the Assessment & Question Management module.
 * Verifies role-based access control and CRUD behaviour.
 */

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\Category;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Question;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AssessmentManagementTest extends TestCase
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

    protected function makeAssessmentFor(User $mentor): Assessment
    {
        $course = $this->makeCourseFor($mentor);

        $lesson = Lesson::create([
            'course_id' => $course->id,
            'title' => 'Lesson '.uniqid(),
            'content' => 'Content',
            'order_number' => 1,
            'status' => 'Published',
        ]);

        return Assessment::create([
            'lesson_id' => $lesson->id,
            'title' => 'Assessment '.uniqid(),
            'passing_score' => 50,
            'status' => 'Published',
        ]);
    }

    public function test_unauthenticated_users_are_redirected_to_login(): void
    {
        $this->get('/assessments')->assertRedirect('/login');
        $this->get('/assessments/create')->assertRedirect('/login');
    }

    public function test_students_cannot_access_assessment_management(): void
    {
        $student = $this->makeUser('Student', 'student@test.com');

        $this->actingAs($student)
            ->get('/assessments')
            ->assertForbidden();
    }

    public function test_admin_can_view_assessments_index(): void
    {
        $admin = $this->makeUser('Admin', 'admin@test.com');
        $this->makeAssessmentFor($admin);

        $this->actingAs($admin)
            ->get('/assessments')
            ->assertOk();
    }

    public function test_mentor_only_sees_assessments_of_own_courses(): void
    {
        $mentorA = $this->makeUser('Mentor', 'mentorA@test.com');
        $mentorB = $this->makeUser('Mentor', 'mentorB@test.com');

        $assessmentA = $this->makeAssessmentFor($mentorA);
        $assessmentB = $this->makeAssessmentFor($mentorB);

        $this->actingAs($mentorA)
            ->get('/assessments')
            ->assertOk()
            ->assertSee($assessmentA->title)
            ->assertDontSee($assessmentB->title);
    }

    public function test_mentor_cannot_edit_another_mentors_assessment(): void
    {
        $mentorA = $this->makeUser('Mentor', 'mentorA@test.com');
        $mentorB = $this->makeUser('Mentor', 'mentorB@test.com');

        $assessmentB = $this->makeAssessmentFor($mentorB);

        $this->actingAs($mentorA)
            ->get('/assessments/'.$assessmentB->id.'/edit')
            ->assertForbidden();
    }

    public function test_admin_can_create_an_assessment(): void
    {
        $admin = $this->makeUser('Admin', 'admin@test.com');
        $mentor = $this->makeUser('Mentor', 'mentor@test.com');
        $course = $this->makeCourseFor($mentor);

        $lesson = Lesson::create([
            'course_id' => $course->id,
            'title' => 'Lesson',
            'content' => 'Content',
            'order_number' => 1,
            'status' => 'Published',
        ]);

        $this->actingAs($admin)
            ->post('/assessments', [
                'lesson_id' => $lesson->id,
                'title' => 'New Assessment',
                'description' => 'Description',
                'passing_score' => 60,
                'time_limit' => 20,
                'max_attempts' => 3,
                'status' => 'Published',
            ])
            ->assertRedirect('/assessments');

        $this->assertDatabaseHas('assessments', ['title' => 'New Assessment']);
    }

    public function test_mentor_cannot_create_assessment_on_another_mentors_lesson(): void
    {
        $mentorA = $this->makeUser('Mentor', 'mentorA@test.com');
        $mentorB = $this->makeUser('Mentor', 'mentorB@test.com');
        $courseB = $this->makeCourseFor($mentorB);

        $lessonB = Lesson::create([
            'course_id' => $courseB->id,
            'title' => 'Lesson B',
            'content' => 'Content',
            'order_number' => 1,
            'status' => 'Published',
        ]);

        $this->actingAs($mentorA)
            ->post('/assessments', [
                'lesson_id' => $lessonB->id,
                'title' => 'Should Fail',
                'passing_score' => 50,
                'status' => 'Draft',
            ])
            ->assertSessionHasErrors('lesson_id');
    }

    public function test_question_can_be_created_with_mcq_correct_answer(): void
    {
        $admin = $this->makeUser('Admin', 'admin@test.com');
        $assessment = $this->makeAssessmentFor($this->makeUser('Mentor', 'mentor@test.com'));

        $this->actingAs($admin)
            ->post('/assessments/'.$assessment->id.'/questions', [
                'question_text' => 'Sample question?',
                'question_type' => 'MCQ',
                'option_a' => 'Option A',
                'option_b' => 'Option B',
                'option_c' => 'Option C',
                'option_d' => 'Option D',
                'correct_answer_mcq' => 'B',
                'marks' => 2,
                'order_number' => 1,
            ])
            ->assertRedirect('/assessments/'.$assessment->id.'/questions');

        $this->assertDatabaseHas('questions', [
            'question_text' => 'Sample question?',
            'correct_answer' => 'B',
            'marks' => 2,
        ]);
    }

    public function test_question_requires_type_specific_correct_answer(): void
    {
        $admin = $this->makeUser('Admin', 'admin@test.com');
        $assessment = $this->makeAssessmentFor($this->makeUser('Mentor', 'mentor@test.com'));

        $this->actingAs($admin)
            ->post('/assessments/'.$assessment->id.'/questions', [
                'question_text' => 'True or false?',
                'question_type' => 'TrueFalse',
                'correct_answer_tf' => 'True',
                'marks' => 1,
                'order_number' => 1,
            ])
            ->assertRedirect('/assessments/'.$assessment->id.'/questions');

        $this->assertDatabaseHas('questions', [
            'question_text' => 'True or false?',
            'correct_answer' => 'True',
        ]);
    }
}
