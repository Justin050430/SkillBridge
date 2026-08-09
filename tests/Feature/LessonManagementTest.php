<?php

/**
 * @author OOI JIAN XIN
 *
 * Feature tests for the Lesson Management module.
 *  - Role access: Admin + Mentor allowed, Student blocked.
 *  - Mentor scoping: mentors may only manage lessons in their own courses.
 *  - CRUD: create, update, delete.
 *  - Relationship guard: a lesson with assessments or progress cannot be
 *    deleted (verified FK cascade chain would destroy historical data).
 */

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\Category;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\Progress;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LessonManagementTest extends TestCase
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
            'status' => 'Active',
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

    public function test_students_are_blocked_from_lesson_management(): void
    {
        $mentor = $this->makeUser('Mentor', 'mentor@test.com');
        $student = $this->makeUser('Student', 'student@test.com');
        $course = $this->makeCourseFor($mentor);
        $lesson = $this->makeLesson($course);

        $this->actingAs($student)
            ->get('/courses/'.$course->slug.'/lessons')
            ->assertForbidden();

        $this->actingAs($student)
            ->get('/courses/'.$course->slug.'/lessons/'.$lesson->id.'/edit')
            ->assertForbidden();
    }

    public function test_admin_can_view_course_lessons(): void
    {
        $admin = $this->makeUser('Admin', 'admin@test.com');
        $mentor = $this->makeUser('Mentor', 'mentor@test.com');
        $course = $this->makeCourseFor($mentor);
        $lesson = $this->makeLesson($course);

        $this->actingAs($admin)
            ->get('/courses/'.$course->slug.'/lessons')
            ->assertOk()
            ->assertSee($lesson->title);
    }

    public function test_mentor_can_view_lessons_of_own_course(): void
    {
        $mentor = $this->makeUser('Mentor', 'mentor@test.com');
        $course = $this->makeCourseFor($mentor);
        $lesson = $this->makeLesson($course);

        $this->actingAs($mentor)
            ->get('/courses/'.$course->slug.'/lessons')
            ->assertOk()
            ->assertSee($lesson->title);
    }

    public function test_back_to_courses_link_respects_the_viewers_role(): void
    {
        $mentor = $this->makeUser('Mentor', 'mentor@test.com');
        $admin = $this->makeUser('Admin', 'admin@test.com');
        $course = $this->makeCourseFor($mentor);
        $this->makeLesson($course);

        // A mentor must NOT be pointed at the Admin course-management route.
        $this->actingAs($mentor)
            ->get('/courses/'.$course->slug.'/lessons')
            ->assertOk()
            ->assertSee(route('mentor.courses'), false)
            ->assertDontSee(route('admin.courses'), false);

        // An admin navigates back to the Admin course-management area.
        $this->actingAs($admin)
            ->get('/courses/'.$course->slug.'/lessons')
            ->assertOk()
            ->assertSee(route('admin.courses'), false)
            ->assertDontSee(route('mentor.courses'), false);
    }

    public function test_mentor_cannot_access_another_mentors_course_lessons(): void
    {
        $mentorA = $this->makeUser('Mentor', 'mentorA@test.com');
        $mentorB = $this->makeUser('Mentor', 'mentorB@test.com');
        $courseB = $this->makeCourseFor($mentorB);
        $lessonB = $this->makeLesson($courseB);

        $this->actingAs($mentorA)
            ->get('/courses/'.$courseB->slug.'/lessons')
            ->assertForbidden();

        $this->actingAs($mentorA)
            ->get('/courses/'.$courseB->slug.'/lessons/'.$lessonB->id.'/edit')
            ->assertForbidden();
    }

    public function test_admin_can_create_a_lesson(): void
    {
        $admin = $this->makeUser('Admin', 'admin@test.com');
        $mentor = $this->makeUser('Mentor', 'mentor@test.com');
        $course = $this->makeCourseFor($mentor);

        $this->actingAs($admin)
            ->post('/courses/'.$course->slug.'/lessons', [
                'title' => 'New Lesson',
                'content' => 'New lesson content',
                'order_number' => 2,
                'estimated_duration' => 30,
                'status' => 'Draft',
            ])
            ->assertRedirect('/courses/'.$course->slug.'/lessons');

        $this->assertDatabaseHas('lessons', [
            'course_id' => $course->id,
            'title' => 'New Lesson',
            'order_number' => 2,
        ]);
    }

    public function test_admin_can_update_a_lesson(): void
    {
        $admin = $this->makeUser('Admin', 'admin@test.com');
        $mentor = $this->makeUser('Mentor', 'mentor@test.com');
        $course = $this->makeCourseFor($mentor);
        $lesson = $this->makeLesson($course);

        $this->actingAs($admin)
            ->put('/courses/'.$course->slug.'/lessons/'.$lesson->id, [
                'title' => 'Updated Lesson',
                'content' => 'Updated content',
                'order_number' => 3,
                'estimated_duration' => null,
                'status' => 'Published',
            ])
            ->assertRedirect('/courses/'.$course->slug.'/lessons');

        $this->assertDatabaseHas('lessons', [
            'id' => $lesson->id,
            'title' => 'Updated Lesson',
            'order_number' => 3,
        ]);
    }

    public function test_admin_can_delete_a_lesson_without_relationships(): void
    {
        $admin = $this->makeUser('Admin', 'admin@test.com');
        $mentor = $this->makeUser('Mentor', 'mentor@test.com');
        $course = $this->makeCourseFor($mentor);
        $lesson = $this->makeLesson($course);

        $this->actingAs($admin)
            ->delete('/courses/'.$course->slug.'/lessons/'.$lesson->id)
            ->assertRedirect('/courses/'.$course->slug.'/lessons');

        $this->assertDatabaseMissing('lessons', ['id' => $lesson->id]);
    }

    public function test_lesson_with_assessments_cannot_be_deleted(): void
    {
        $admin = $this->makeUser('Admin', 'admin@test.com');
        $mentor = $this->makeUser('Mentor', 'mentor@test.com');
        $course = $this->makeCourseFor($mentor);
        $lesson = $this->makeLesson($course);

        Assessment::create([
            'lesson_id' => $lesson->id,
            'title' => 'Assessment '.uniqid(),
            'passing_score' => 50,
            'status' => 'Published',
        ]);

        $this->actingAs($admin)
            ->delete('/courses/'.$course->slug.'/lessons/'.$lesson->id)
            ->assertRedirect('/courses/'.$course->slug.'/lessons');

        // The lesson and its assessment must still exist (history preserved).
        $this->assertDatabaseHas('lessons', ['id' => $lesson->id]);
        $this->assertDatabaseHas('assessments', ['lesson_id' => $lesson->id]);
    }

    public function test_lesson_with_progress_cannot_be_deleted(): void
    {
        $admin = $this->makeUser('Admin', 'admin@test.com');
        $mentor = $this->makeUser('Mentor', 'mentor@test.com');
        $student = $this->makeUser('Student', 'student@test.com');
        $course = $this->makeCourseFor($mentor);
        $lesson = $this->makeLesson($course);

        $enrollment = Enrollment::create([
            'student_id' => $student->id,
            'course_id' => $course->id,
            'status' => 'Active',
            'enrolled_at' => now(),
        ]);

        Progress::create([
            'enrollment_id' => $enrollment->id,
            'lesson_id' => $lesson->id,
            'status' => 'Completed',
            'completed_at' => now(),
        ]);

        $this->actingAs($admin)
            ->delete('/courses/'.$course->slug.'/lessons/'.$lesson->id)
            ->assertRedirect('/courses/'.$course->slug.'/lessons');

        $this->assertDatabaseHas('lessons', ['id' => $lesson->id]);
    }
}
