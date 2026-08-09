<?php

/**
 * @author OOI JIAN XIN
 *
 * Feature tests for the project-wide input-validation rules.
 *
 * Verifies the authoritative server-side validation behaves correctly for
 * each field type (names, email, password, titles, content, numerics,
 * enums/status/role), including rejection of malicious/abusive values sent
 * directly via HTTP requests (validation cannot be bypassed client-side).
 */

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class InputValidationTest extends TestCase
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

    // ---- Names ----

    public function test_name_accepts_letters_spaces_and_name_punctuation(): void
    {
        $admin = $this->makeUser('Admin', 'admin@test.com');

        $roleId = Role::firstOrCreate(['role_name' => 'Student'])->id;

        $this->actingAs($admin)
            ->post('/admin/users', [
                'first_name' => 'Jean-Luc',
                'last_name' => "O'Brien",
                'email' => 'new@test.com',
                'role_id' => $roleId,
                'password' => 'password',
                'password_confirmation' => 'password',
            ])
            ->assertRedirect('/admin/users');

        $this->assertDatabaseHas('users', ['email' => 'new@test.com']);
    }

    public function test_name_rejects_numbers(): void
    {
        $admin = $this->makeUser('Admin', 'admin@test.com');

        $this->actingAs($admin)
            ->post('/admin/users', [
                'first_name' => 'John123',
                'last_name' => 'Doe',
                'email' => 'num@test.com',
                'role_id' => Role::where('role_name', 'Student')->value('id'),
                'password' => 'password',
                'password_confirmation' => 'password',
            ])
            ->assertSessionHasErrors('first_name');

        $this->assertDatabaseMissing('users', ['email' => 'num@test.com']);
    }

    public function test_name_rejects_html_and_arbitrary_symbols(): void
    {
        $admin = $this->makeUser('Admin', 'admin@test.com');

        $this->actingAs($admin)
            ->post('/admin/users', [
                'first_name' => '<script>alert(1)</script>',
                'last_name' => 'Doe',
                'email' => 'xss@test.com',
                'role_id' => Role::where('role_name', 'Student')->value('id'),
                'password' => 'password',
                'password_confirmation' => 'password',
            ])
            ->assertSessionHasErrors('first_name');

        $this->assertDatabaseMissing('users', ['email' => 'xss@test.com']);
    }

    public function test_name_rejects_excessively_long_values(): void
    {
        $admin = $this->makeUser('Admin', 'admin@test.com');

        $this->actingAs($admin)
            ->post('/admin/users', [
                'first_name' => str_repeat('A', 150),
                'last_name' => 'Doe',
                'email' => 'long@test.com',
                'role_id' => Role::where('role_name', 'Student')->value('id'),
                'password' => 'password',
                'password_confirmation' => 'password',
            ])
            ->assertSessionHasErrors('first_name');
    }

    // ---- Email ----

    public function test_invalid_email_is_rejected(): void
    {
        $admin = $this->makeUser('Admin', 'admin@test.com');

        $this->actingAs($admin)
            ->post('/admin/users', [
                'first_name' => 'Jane',
                'last_name' => 'Doe',
                'email' => 'not-an-email',
                'role_id' => Role::where('role_name', 'Student')->value('id'),
                'password' => 'password',
                'password_confirmation' => 'password',
            ])
            ->assertSessionHasErrors('email');
    }

    public function test_duplicate_email_is_rejected(): void
    {
        $admin = $this->makeUser('Admin', 'admin@test.com');
        $this->makeUser('Student', 'existing@test.com');

        $this->actingAs($admin)
            ->post('/admin/users', [
                'first_name' => 'Jane',
                'last_name' => 'Doe',
                'email' => 'existing@test.com',
                'role_id' => Role::where('role_name', 'Student')->value('id'),
                'password' => 'password',
                'password_confirmation' => 'password',
            ])
            ->assertSessionHasErrors('email');
    }

    // ---- Password ----

    public function test_password_accepts_special_characters(): void
    {
        $admin = $this->makeUser('Admin', 'admin@test.com');

        $roleId = Role::firstOrCreate(['role_name' => 'Student'])->id;

        $this->actingAs($admin)
            ->post('/admin/users', [
                'first_name' => 'Jane',
                'last_name' => 'Doe',
                'email' => 'special@test.com',
                'role_id' => $roleId,
                'password' => 'Str0ng!Pass@word#',
                'password_confirmation' => 'Str0ng!Pass@word#',
            ])
            ->assertRedirect('/admin/users');

        $this->assertDatabaseHas('users', ['email' => 'special@test.com']);
    }

    public function test_password_confirmation_must_match(): void
    {
        $admin = $this->makeUser('Admin', 'admin@test.com');

        $this->actingAs($admin)
            ->post('/admin/users', [
                'first_name' => 'Jane',
                'last_name' => 'Doe',
                'email' => 'mismatch@test.com',
                'role_id' => Role::where('role_name', 'Student')->value('id'),
                'password' => 'password',
                'password_confirmation' => 'different',
            ])
            ->assertSessionHasErrors('password');
    }

    // ---- Role / status (enum tampering) ----

    public function test_invalid_role_id_is_rejected(): void
    {
        $admin = $this->makeUser('Admin', 'admin@test.com');

        $this->actingAs($admin)
            ->post('/admin/users', [
                'first_name' => 'Jane',
                'last_name' => 'Doe',
                'email' => 'badrole@test.com',
                'role_id' => 99999,
                'password' => 'password',
                'password_confirmation' => 'password',
            ])
            ->assertSessionHasErrors('role_id');
    }

    public function test_invalid_status_is_rejected_on_update(): void
    {
        $admin = $this->makeUser('Admin', 'admin@test.com');
        $student = $this->makeUser('Student', 'student@test.com');

        $this->actingAs($admin)
            ->put('/admin/users/'.$student->id, [
                'first_name' => 'Test',
                'last_name' => 'Student',
                'email' => 'student@test.com',
                'role_id' => $student->role_id,
                'status' => 'SuperAdmin',
            ])
            ->assertSessionHasErrors('status');
    }

    // ---- Numeric fields (course/assessment/lesson) ----

    public function test_assessment_rejects_negative_and_out_of_range_numerics(): void
    {
        $admin = $this->makeUser('Admin', 'admin@test.com');
        $mentor = $this->makeUser('Mentor', 'mentor@test.com');
        $category = Category::create(['category_name' => 'Cat']);
        $course = \App\Models\Course::create([
            'mentor_id' => $mentor->id,
            'category_id' => $category->id,
            'title' => 'Course',
            'slug' => 'course-'.uniqid(),
            'description' => 'Desc',
            'status' => 'Published',
        ]);
        $lesson = \App\Models\Lesson::create([
            'course_id' => $course->id,
            'title' => 'Lesson',
            'content' => 'Content',
            'order_number' => 1,
            'status' => 'Published',
        ]);

        $this->actingAs($admin)
            ->post('/assessments', [
                'lesson_id' => $lesson->id,
                'title' => 'Quiz',
                'passing_score' => -5,
                'status' => 'Published',
            ])
            ->assertSessionHasErrors('passing_score');
    }

    public function test_lesson_rejects_invalid_order_number(): void
    {
        $admin = $this->makeUser('Admin', 'admin@test.com');
        $mentor = $this->makeUser('Mentor', 'mentor@test.com');
        $category = Category::create(['category_name' => 'Cat']);
        $course = \App\Models\Course::create([
            'mentor_id' => $mentor->id,
            'category_id' => $category->id,
            'title' => 'Course',
            'slug' => 'course-'.uniqid(),
            'description' => 'Desc',
            'status' => 'Published',
        ]);

        $this->actingAs($admin)
            ->post('/courses/'.$course->slug.'/lessons', [
                'title' => 'Lesson',
                'content' => 'Content',
                'order_number' => 'abc',
                'status' => 'Published',
            ])
            ->assertSessionHasErrors('order_number');
    }

    // ---- Titles reject HTML (defence-in-depth) ----

    public function test_course_title_rejects_angle_brackets(): void
    {
        $admin = $this->makeUser('Admin', 'admin@test.com');
        $mentor = $this->makeUser('Mentor', 'mentor@test.com');
        $category = Category::create(['category_name' => 'Cat']);

        $this->actingAs($admin)
            ->post('/admin/courses', [
                'mentor_id' => $mentor->id,
                'category_id' => $category->id,
                'title' => '<script>alert(1)</script>',
                'description' => 'Desc',
                'status' => 'Draft',
            ])
            ->assertSessionHasErrors('title');
    }

    public function test_question_text_rejects_angle_brackets(): void
    {
        $admin = $this->makeUser('Admin', 'admin@test.com');
        $mentor = $this->makeUser('Mentor', 'mentor@test.com');
        $category = Category::create(['category_name' => 'Cat']);
        $course = \App\Models\Course::create([
            'mentor_id' => $mentor->id,
            'category_id' => $category->id,
            'title' => 'Course',
            'slug' => 'course-'.uniqid(),
            'description' => 'Desc',
            'status' => 'Published',
        ]);
        $lesson = \App\Models\Lesson::create([
            'course_id' => $course->id,
            'title' => 'Lesson',
            'content' => 'Content',
            'order_number' => 1,
            'status' => 'Published',
        ]);
        $assessment = \App\Models\Assessment::create([
            'lesson_id' => $lesson->id,
            'title' => 'Quiz',
            'passing_score' => 50,
            'status' => 'Published',
        ]);

        $this->actingAs($admin)
            ->post('/assessments/'.$assessment->slug.'/questions', [
                'question_text' => '<img src=x onerror=alert(1)>',
                'question_type' => 'MCQ',
                'correct_answer_mcq' => 'A',
                'marks' => 1,
                'order_number' => 1,
            ])
            ->assertSessionHasErrors('question_text');
    }

    // ---- Validation cannot be bypassed via direct HTTP tampering ----

    public function test_course_content_rejects_excessively_long_value(): void
    {
        $admin = $this->makeUser('Admin', 'admin@test.com');
        $mentor = $this->makeUser('Mentor', 'mentor@test.com');
        $category = Category::create(['category_name' => 'Cat']);

        $this->actingAs($admin)
            ->post('/admin/courses', [
                'mentor_id' => $mentor->id,
                'category_id' => $category->id,
                'title' => 'Valid Title',
                'description' => str_repeat('A', 6000),
                'status' => 'Draft',
            ])
            ->assertSessionHasErrors('description');
    }
}
