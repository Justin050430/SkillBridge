<?php

/**
 * @author OOI JIAN XIN
 *
 * Verifies the GoF Observer pattern integration in the assessment flow:
 *  - ProgressUpdater  : marks the linked lesson as Completed on a passing attempt
 *  - SubmissionLogger : writes an audit entry to the "assessment" log channel
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

class ObserverPatternTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Remove assessment log files left behind by previous runs/tests.
        array_map('unlink', glob(storage_path('logs/assessment-*.log')) ?: []);
    }

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

    protected function makeLesson(Course $course): Lesson
    {
        return Lesson::create([
            'course_id' => $course->id,
            'title' => 'Lesson '.uniqid(),
            'content' => 'Content',
            'order_number' => 1,
            'status' => 'Published',
        ]);
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

    protected function logContents(): string
    {
        $content = '';

        foreach (glob(storage_path('logs/assessment-*.log')) ?: [] as $file) {
            $content .= file_get_contents($file);
        }

        return $content;
    }

    public function test_passing_submission_fires_progress_updater_and_submission_logger(): void
    {
        $student = $this->makeUser('Student', 'student@test.com');
        $mentor = $this->makeUser('Mentor', 'mentor@test.com');
        $course = $this->makeCourseFor($mentor);
        $enrollment = $this->enroll($student, $course);
        $lesson = $this->makeLesson($course);
        $assessment = $this->makeAssessment($lesson);

        $question = $this->makeQuestion($assessment, ['correct_answer' => 'A', 'marks' => 1]);

        $this->actingAs($student)->startAssessment($assessment);
        $this->actingAs($student)
            ->post('/student/assessments/'.$assessment->slug.'/submit', ['attempt_id' => $assessment->attempts()->latest('id')->value('id') ?? 1,
                'answers' => [$question->id => 'A'],
            ])
            ->assertRedirect('/student/assessments/'.$assessment->slug.'/result');

        // ProgressUpdater: passing completes the linked lesson.
        $this->assertDatabaseHas('progress', [
            'enrollment_id' => $enrollment->id,
            'lesson_id' => $lesson->id,
            'status' => 'Completed',
        ]);

        // SubmissionLogger: an audit entry was written to the assessment channel.
        $content = $this->logContents();

        $this->assertStringContainsString('Assessment submitted', $content);
        $this->assertStringContainsString('assessment_id', $content);
        $this->assertStringContainsString((string) $assessment->id, $content);
    }

    public function test_failed_submission_logs_but_does_not_complete_progress(): void
    {
        $student = $this->makeUser('Student', 'student@test.com');
        $mentor = $this->makeUser('Mentor', 'mentor@test.com');
        $course = $this->makeCourseFor($mentor);
        $enrollment = $this->enroll($student, $course);
        $lesson = $this->makeLesson($course);
        $assessment = $this->makeAssessment($lesson);

        $question = $this->makeQuestion($assessment, ['correct_answer' => 'A', 'marks' => 1]);

        $this->actingAs($student)->startAssessment($assessment);
        $this->actingAs($student)
            ->post('/student/assessments/'.$assessment->slug.'/submit', ['attempt_id' => $assessment->attempts()->latest('id')->value('id') ?? 1,
                'answers' => [$question->id => 'B'], // wrong -> 0% -> failed
            ])
            ->assertRedirect('/student/assessments/'.$assessment->slug.'/result');

        // ProgressUpdater: a failed attempt must NOT complete the lesson.
        $this->assertDatabaseMissing('progress', [
            'enrollment_id' => $enrollment->id,
            'lesson_id' => $lesson->id,
            'status' => 'Completed',
        ]);

        // SubmissionLogger: the submission is still logged.
        $content = $this->logContents();

        $this->assertStringContainsString('Assessment submitted', $content);
        $this->assertStringContainsString((string) $assessment->id, $content);
    }
}
