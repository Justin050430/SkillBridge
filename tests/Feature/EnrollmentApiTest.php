<?php

/**
 * @author GEN TECK NG
 *
 * Verifies the Enrollment Management module's exposed web service:
 *   GET /api/enrollments/user/{user}/stats (IFA-compliant)
 */

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class EnrollmentApiTest extends TestCase
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

    protected function makeCourse(User $mentor): Course
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

    protected function enroll(User $student, Course $course, string $status = 'Active'): Enrollment
    {
        return Enrollment::create([
            'student_id' => $student->id,
            'course_id' => $course->id,
            'status' => $status,
            'enrolled_at' => now(),
        ]);
    }

    public function test_user_stats_returns_counts_with_ifa_fields(): void
    {
        $admin = $this->makeUser('Admin', 'admin@test.com');
        $mentor = $this->makeUser('Mentor', 'mentor@test.com');
        $student = $this->makeUser('Student', 'student@test.com');

        $course = $this->makeCourse($mentor);
        $this->enroll($student, $course, 'Active');
        $this->enroll($student, $course, 'Completed');
        $this->enroll($student, $course, 'Dropped');

        $response = $this->actingAs($admin)
            ->getJson('/api/enrollments/user/'.$student->id.'/stats?timestamp=2026-01-01T00:00:00Z');

        $response->assertOk();
        $response->assertJson([
            'status' => 'S',
            'requestId' => true,
            'timestamp' => true,
        ]);
        $response->assertJsonPath('data.user_id', $student->id);
        $response->assertJsonPath('data.total', 3);
        $response->assertJsonPath('data.active', 1);
        $response->assertJsonPath('data.completed', 1);
        $response->assertJsonPath('data.dropped', 1);
    }

    public function test_user_stats_accepts_request_id_instead_of_timestamp(): void
    {
        $admin = $this->makeUser('Admin', 'admin@test.com');
        $student = $this->makeUser('Student', 'student@test.com');

        $this->actingAs($admin)
            ->getJson('/api/enrollments/user/'.$student->id.'/stats?requestId=req-xyz-999')
            ->assertOk()
            ->assertJsonPath('status', 'S')
            ->assertJsonPath('requestId', 'req-xyz-999');
    }

    public function test_user_stats_requires_timestamp_or_request_id(): void
    {
        $admin = $this->makeUser('Admin', 'admin@test.com');
        $student = $this->makeUser('Student', 'student@test.com');

        $response = $this->actingAs($admin)
            ->getJson('/api/enrollments/user/'.$student->id.'/stats');

        $response->assertStatus(422);
        $response->assertJsonPath('status', 'F');
        $response->assertJsonPath('message', 'A timestamp or requestId field is required.');
    }

    public function test_user_stats_requires_authentication(): void
    {
        $student = $this->makeUser('Student', 'student@test.com');

        $this->getJson('/api/enrollments/user/'.$student->id.'/stats?timestamp=2026-01-01T00:00:00Z')
            ->assertUnauthorized();
    }

    public function test_user_stats_blocks_non_admin_viewing_another_user(): void
    {
        $mentor = $this->makeUser('Mentor', 'mentor@test.com');
        $student = $this->makeUser('Student', 'student@test.com');

        $response = $this->actingAs($mentor)
            ->getJson('/api/enrollments/user/'.$student->id.'/stats?timestamp=2026-01-01T00:00:00Z');

        $response->assertForbidden();
        $response->assertJsonPath('status', 'F');
        $response->assertJsonPath('message', 'You can only view your own enrollment statistics.');
    }

    public function test_user_can_view_own_stats(): void
    {
        $student = $this->makeUser('Student', 'student@test.com');

        $this->actingAs($student)
            ->getJson('/api/enrollments/user/'.$student->id.'/stats?timestamp=2026-01-01T00:00:00Z')
            ->assertOk()
            ->assertJsonPath('status', 'S');
    }

    public function test_user_stats_returns_404_for_non_existent_user(): void
    {
        $admin = $this->makeUser('Admin', 'admin@test.com');

        $this->actingAs($admin)
            ->getJson('/api/enrollments/user/999999/stats?timestamp=2026-01-01T00:00:00Z')
            ->assertNotFound();
    }
}
