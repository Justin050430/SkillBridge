<?php

/**
 * @author OOI JIAN XIN
 *
 * Regression tests for the question Edit flow (previously returning 404 on
 * MySQL/sqlsrv because the FK comparison used a strict int/string equality).
 *
 *  - Admin can edit questions
 *  - Mentor can edit questions in their own course
 *  - Mentor cannot edit another mentor's question (403)
 *  - Student cannot access question management (403)
 *  - Saving an edited question persists the changes
 *  - Validation errors are surfaced and nothing is saved
 *  - A question cannot be edited through the wrong assessment (404)
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

class QuestionEditTest extends TestCase
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

    protected function makeQuestion(Assessment $assessment): Question
    {
        return Question::create([
            'assessment_id' => $assessment->id,
            'question_text' => 'Original question?',
            'question_type' => 'MCQ',
            'option_a' => 'Option A',
            'option_b' => 'Option B',
            'option_c' => 'Option C',
            'option_d' => 'Option D',
            'correct_answer' => 'A',
            'marks' => 1,
            'order_number' => 1,
        ]);
    }

    protected function questionPayload(Question $question, array $overrides = []): array
    {
        return array_merge([
            'question_text' => 'Updated question?',
            'question_type' => 'MCQ',
            'option_a' => 'Option A',
            'option_b' => 'Option B',
            'option_c' => 'Option C',
            'option_d' => 'Option D',
            'correct_answer_mcq' => 'B',
            'marks' => 3,
            'order_number' => 1,
        ], $overrides);
    }

    public function test_admin_can_open_and_save_question_edit(): void
    {
        $admin = $this->makeUser('Admin', 'admin@test.com');
        $assessment = $this->makeAssessmentFor($this->makeUser('Mentor', 'mentor@test.com'));
        $question = $this->makeQuestion($assessment);

        // Edit page renders.
        $this->actingAs($admin)
            ->get('/assessments/'.$assessment->slug.'/questions/'.$question->id.'/edit')
            ->assertOk()
            ->assertSee($question->question_text);

        // Saving the edit persists the changes.
        $this->actingAs($admin)
            ->put('/assessments/'.$assessment->slug.'/questions/'.$question->id, $this->questionPayload($question))
            ->assertRedirect('/assessments/'.$assessment->slug.'/questions')
            ->assertSessionHas('success');

        $this->assertDatabaseHas('questions', [
            'id' => $question->id,
            'question_text' => 'Updated question?',
            'correct_answer' => 'B',
            'marks' => 3,
        ]);
    }

    public function test_mentor_can_edit_question_in_own_course(): void
    {
        $mentor = $this->makeUser('Mentor', 'mentor@test.com');
        $assessment = $this->makeAssessmentFor($mentor);
        $question = $this->makeQuestion($assessment);

        $this->actingAs($mentor)
            ->get('/assessments/'.$assessment->slug.'/questions/'.$question->id.'/edit')
            ->assertOk();

        $this->actingAs($mentor)
            ->put('/assessments/'.$assessment->slug.'/questions/'.$question->id, $this->questionPayload($question))
            ->assertRedirect('/assessments/'.$assessment->slug.'/questions');

        $this->assertDatabaseHas('questions', [
            'id' => $question->id,
            'question_text' => 'Updated question?',
        ]);
    }

    public function test_mentor_cannot_edit_another_mentors_question(): void
    {
        $mentorA = $this->makeUser('Mentor', 'mentorA@test.com');
        $mentorB = $this->makeUser('Mentor', 'mentorB@test.com');
        $assessmentB = $this->makeAssessmentFor($mentorB);
        $question = $this->makeQuestion($assessmentB);

        $this->actingAs($mentorA)
            ->get('/assessments/'.$assessmentB->slug.'/questions/'.$question->id.'/edit')
            ->assertForbidden();

        $this->actingAs($mentorA)
            ->put('/assessments/'.$assessmentB->slug.'/questions/'.$question->id, $this->questionPayload($question))
            ->assertForbidden();

        $this->assertDatabaseHas('questions', [
            'id' => $question->id,
            'question_text' => 'Original question?',
        ]);
    }

    public function test_student_cannot_access_question_management(): void
    {
        $student = $this->makeUser('Student', 'student@test.com');
        $assessment = $this->makeAssessmentFor($this->makeUser('Mentor', 'mentor@test.com'));
        $question = $this->makeQuestion($assessment);

        $this->actingAs($student)
            ->get('/assessments/'.$assessment->slug.'/questions/'.$question->id.'/edit')
            ->assertForbidden();

        $this->actingAs($student)
            ->put('/assessments/'.$assessment->slug.'/questions/'.$question->id, $this->questionPayload($question))
            ->assertForbidden();
    }

    public function test_validation_errors_are_surfaced_and_nothing_is_saved(): void
    {
        $admin = $this->makeUser('Admin', 'admin@test.com');
        $assessment = $this->makeAssessmentFor($this->makeUser('Mentor', 'mentor@test.com'));
        $question = $this->makeQuestion($assessment);

        $this->actingAs($admin)
            ->from('/assessments/'.$assessment->slug.'/questions/'.$question->id.'/edit')
            ->put('/assessments/'.$assessment->slug.'/questions/'.$question->id, [
                'question_text' => '',
                'question_type' => 'MCQ',
                'option_a' => 'Option A',
                'option_b' => 'Option B',
                'option_c' => 'Option C',
                'option_d' => 'Option D',
                'correct_answer_mcq' => 'A',
                'marks' => 1,
                'order_number' => 1,
            ])
            ->assertSessionHasErrors('question_text');

        $this->assertDatabaseHas('questions', [
            'id' => $question->id,
            'question_text' => 'Original question?',
        ]);
    }

    public function test_question_cannot_be_edited_through_the_wrong_assessment(): void
    {
        $admin = $this->makeUser('Admin', 'admin@test.com');
        $mentor = $this->makeUser('Mentor', 'mentor@test.com');

        $assessmentA = $this->makeAssessmentFor($mentor);
        $assessmentB = $this->makeAssessmentFor($mentor);
        $questionA = $this->makeQuestion($assessmentA);

        // The question belongs to assessment A, not B.
        $this->actingAs($admin)
            ->get('/assessments/'.$assessmentB->slug.'/questions/'.$questionA->id.'/edit')
            ->assertNotFound();
    }
}
