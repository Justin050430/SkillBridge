<?php

/**
 * @author OOI JIAN XIN
 *
 * Verifies every web-service CONSUMER in SkillBridge:
 *   - Course module      -> CourseAnalyticsClient (Assessment course stats)
 *   - User module        -> EnrollmentStatsClient (Enrollment user stats)
 *   - Notification module-> UserProfileClient (User profile)
 *   - Enrollment module  -> NotificationApiClient (Notification create)
 *   - Assessment module  -> CourseApiClient (Course catalogue) - IFA request fields
 * Plus the UI integrations that surface the consumed data.
 */

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\Category;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\Role;
use App\Models\User;
use App\Services\CourseAnalyticsClient;
use App\Services\CourseApiClient;
use App\Services\EnrollmentStatsClient;
use App\Services\NotificationApiClient;
use App\Services\UserProfileClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WebServiceConsumptionTest extends TestCase
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

    protected function makeAssessment(Course $course): Assessment
    {
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

    protected function enroll(User $student, Course $course, string $status = 'Active'): Enrollment
    {
        return Enrollment::create([
            'student_id' => $student->id,
            'course_id' => $course->id,
            'status' => $status,
            'enrolled_at' => now(),
        ]);
    }

    // ------------------------------------------------------------------
    // CourseAnalyticsClient (Course module consumes Assessment course stats)
    // ------------------------------------------------------------------

    public function test_course_analytics_client_parses_ifa_response(): void
    {
        Http::fake([
            '*/api/assessments/course/*/stats*' => Http::response([
                'status' => 'S',
                'requestId' => 'req-1',
                'timestamp' => now()->toIso8601String(),
                'data' => [
                    'course_id' => 5,
                    'course_title' => 'Cyber',
                    'assessment_count' => 3,
                    'question_count' => 15,
                    'attempt_count' => 10,
                    'average_score' => 72.5,
                    'pass_rate' => 80.0,
                ],
            ], 200),
        ]);

        $data = app(CourseAnalyticsClient::class)->courseStats(5);

        $this->assertNotNull($data);
        $this->assertSame(5, $data['course_id']);
        $this->assertSame(3, $data['assessment_count']);
        $this->assertSame(80, $data['pass_rate']);
    }

    public function test_course_analytics_client_degrades_gracefully(): void
    {
        Http::fake([
            '*/api/assessments/course/*/stats*' => Http::response(['status' => 'E', 'timestamp' => now()->toIso8601String()], 500),
        ]);

        $this->assertNull(app(CourseAnalyticsClient::class)->courseStats(5));
    }

    // ------------------------------------------------------------------
    // EnrollmentStatsClient (User module consumes Enrollment user stats)
    // ------------------------------------------------------------------

    public function test_enrollment_stats_client_parses_ifa_response(): void
    {
        Http::fake([
            '*/api/enrollments/user/*/stats*' => Http::response([
                'status' => 'S',
                'requestId' => 'req-2',
                'timestamp' => now()->toIso8601String(),
                'data' => ['user_id' => 4, 'total' => 2, 'active' => 1, 'completed' => 1, 'dropped' => 0],
            ], 200),
        ]);

        $data = app(EnrollmentStatsClient::class)->userStats(4);

        $this->assertNotNull($data);
        $this->assertSame(2, $data['total']);
        $this->assertSame(1, $data['completed']);
    }

    public function test_enrollment_stats_client_degrades_gracefully(): void
    {
        Http::fake([
            '*/api/enrollments/user/*/stats*' => Http::response(['status' => 'F', 'message' => 'Nope', 'timestamp' => now()->toIso8601String()], 403),
        ]);

        $this->assertNull(app(EnrollmentStatsClient::class)->userStats(4));
    }

    // ------------------------------------------------------------------
    // UserProfileClient (Notification module consumes User profile)
    // ------------------------------------------------------------------

    public function test_user_profile_client_parses_ifa_response(): void
    {
        Http::fake([
            '*/api/users/*/profile*' => Http::response([
                'status' => 'S',
                'requestId' => 'req-3',
                'timestamp' => now()->toIso8601String(),
                'data' => ['user_id' => 9, 'first_name' => 'Aisha', 'last_name' => 'Rahman', 'role' => 'Mentor', 'status' => 'Active'],
            ], 200),
        ]);

        $data = app(UserProfileClient::class)->profile(9);

        $this->assertNotNull($data);
        $this->assertSame('Aisha', $data['first_name']);
        $this->assertSame('Mentor', $data['role']);
    }

    public function test_user_profile_client_degrades_gracefully(): void
    {
        Http::fake([
            '*/api/users/*/profile*' => Http::response(['status' => 'E', 'timestamp' => now()->toIso8601String()], 500),
        ]);

        $this->assertNull(app(UserProfileClient::class)->profile(9));
    }

    // ------------------------------------------------------------------
    // NotificationApiClient (Enrollment module consumes Notification create)
    // ------------------------------------------------------------------

    public function test_notification_api_client_returns_true_on_success(): void
    {
        Http::fake([
            '*/api/notifications' => Http::response([
                'status' => 'S',
                'requestId' => 'req-4',
                'timestamp' => now()->toIso8601String(),
                'data' => ['notification_id' => 1, 'user_id' => 3, 'recipient_name' => 'Test Student'],
            ], 200),
        ]);

        $ok = app(NotificationApiClient::class)->notify(3, 'Title', 'Message', 'Enrollment');

        $this->assertTrue($ok);
    }

    public function test_notification_api_client_returns_false_on_failure(): void
    {
        Http::fake([
            '*/api/notifications' => Http::response(['status' => 'E', 'timestamp' => now()->toIso8601String()], 500),
        ]);

        $this->assertFalse(app(NotificationApiClient::class)->notify(3, 'Title', 'Message', 'Enrollment'));
    }

    // ------------------------------------------------------------------
    // CourseApiClient (Assessment module consumes Course catalogue) - IFA
    // ------------------------------------------------------------------

    public function test_course_api_client_sends_ifa_request_fields(): void
    {
        Http::fake([
            '*/api/courses/published' => Http::response([
                'status' => 'S',
                'requestId' => 'req-5',
                'timestamp' => now()->toIso8601String(),
                'data' => [],
            ], 200),
        ]);

        app(CourseApiClient::class)->publishedCourses();

        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/api/courses/published')
                && str_contains($request->url(), 'timestamp=')
                && str_contains($request->url(), 'requestId=');
        });
    }

    // ------------------------------------------------------------------
    // UI integration - consumed data surfaced on existing pages
    // ------------------------------------------------------------------

    public function test_admin_course_edit_page_consumes_course_analytics(): void
    {
        Http::fake([
            '*/api/assessments/course/*/stats*' => Http::response([
                'status' => 'S',
                'requestId' => 'req-6',
                'timestamp' => now()->toIso8601String(),
                'data' => [
                    'course_id' => 1, 'course_title' => 'Cyber', 'assessment_count' => 2,
                    'question_count' => 10, 'attempt_count' => 5, 'average_score' => 70.0, 'pass_rate' => 60.0,
                ],
            ], 200),
        ]);

        $admin = $this->makeUser('Admin', 'admin@test.com');
        $mentor = $this->makeUser('Mentor', 'mentor@test.com');
        $course = $this->makeCourse($mentor);

        $this->actingAs($admin)
            ->get(route('admin.courses.edit', $course))
            ->assertOk()
            ->assertSee('Assessment Performance')
            ->assertSee('GET /api/assessments/course/'.$course->id.'/stats')
            ->assertSee('Pass Rate');
    }

    public function test_admin_course_edit_page_shows_real_data_when_service_unavailable(): void
    {
        Http::fake([
            '*/api/assessments/course/*/stats*' => Http::response(['status' => 'E', 'timestamp' => now()->toIso8601String()], 500),
        ]);

        $admin = $this->makeUser('Admin', 'admin@test.com');
        $mentor = $this->makeUser('Mentor', 'mentor@test.com');
        $course = $this->makeCourse($mentor);

        // Even when the Assessment web service is unreachable, the local
        // fallback supplies the real analytics so the page is never half-broken.
        $this->actingAs($admin)
            ->get(route('admin.courses.edit', $course))
            ->assertOk()
            ->assertSee('Assessment Performance')
            ->assertDontSee('temporarily unavailable');
    }

    // ------------------------------------------------------------------
    // Local fallback when the web service is unreachable
    // ------------------------------------------------------------------

    public function test_course_analytics_client_falls_back_to_local_computation(): void
    {
        Http::fake([
            '*/api/assessments/course/*/stats*' => Http::response(['status' => 'E', 'timestamp' => now()->toIso8601String()], 500),
        ]);

        $mentor = $this->makeUser('Mentor', 'mentor@test.com');
        $student = $this->makeUser('Student', 'student@test.com');
        $course = $this->makeCourse($mentor);
        $assessment = $this->makeAssessment($course);
        $enrollment = $this->enroll($student, $course, 'Active');

        AssessmentAttempt::create([
            'enrollment_id' => $enrollment->id,
            'assessment_id' => $assessment->id,
            'attempt_number' => 1,
            'score' => 1,
            'percentage' => 80.0,
            'is_passed' => true,
            'started_at' => now(),
            'submitted_at' => now(),
        ]);

        $data = app(CourseAnalyticsClient::class)->courseStats($course->id);

        $this->assertNotNull($data);
        $this->assertSame($course->id, $data['course_id']);
        $this->assertSame(1, $data['assessment_count']);
        $this->assertSame(1, $data['attempt_count']);
        $this->assertSame(80.0, $data['average_score']);
        $this->assertSame(100.0, $data['pass_rate']);
    }

    public function test_enrollment_stats_client_falls_back_to_local_computation(): void
    {
        Http::fake([
            '*/api/enrollments/user/*/stats*' => Http::response(['status' => 'E', 'timestamp' => now()->toIso8601String()], 500),
        ]);

        $mentor = $this->makeUser('Mentor', 'mentor@test.com');
        $student = $this->makeUser('Student', 'student@test.com');
        $course = $this->makeCourse($mentor);
        $this->enroll($student, $course, 'Active');
        $this->enroll($student, $course, 'Completed');

        $data = app(EnrollmentStatsClient::class)->userStats($student->id);

        $this->assertNotNull($data);
        $this->assertSame($student->id, $data['user_id']);
        $this->assertSame(2, $data['total']);
        $this->assertSame(1, $data['active']);
        $this->assertSame(1, $data['completed']);
        $this->assertSame(0, $data['dropped']);
    }

    public function test_user_profile_client_falls_back_to_local_lookup(): void
    {
        Http::fake([
            '*/api/users/*/profile*' => Http::response(['status' => 'E', 'timestamp' => now()->toIso8601String()], 500),
        ]);

        $user = $this->makeUser('Mentor', 'mentor@test.com');

        $data = app(UserProfileClient::class)->profile($user->id);

        $this->assertNotNull($data);
        $this->assertSame($user->id, $data['user_id']);
        $this->assertSame('Test', $data['first_name']);
        $this->assertSame('Mentor', $data['role']);
        $this->assertSame('Active', $data['status']);
    }

    public function test_notification_api_client_falls_back_to_local_notification(): void
    {
        Http::fake([
            '*/api/notifications' => Http::response(['status' => 'E', 'timestamp' => now()->toIso8601String()], 500),
        ]);

        $student = $this->makeUser('Student', 'student@test.com');

        $ok = app(NotificationApiClient::class)->notify(
            $student->id,
            'Enrollment completed',
            'Your enrollment was completed.',
            'Enrollment'
        );

        $this->assertTrue($ok);
        $this->assertDatabaseHas('notifications', [
            'user_id' => $student->id,
            'title' => 'Enrollment completed',
            'notification_type' => 'Enrollment',
        ]);
    }

    public function test_course_api_client_falls_back_to_local_catalogue(): void
    {
        Http::fake([
            '*/api/courses/published*' => Http::response(['status' => 'E', 'timestamp' => now()->toIso8601String()], 500),
        ]);

        $mentor = $this->makeUser('Mentor', 'mentor@test.com');
        $course = $this->makeCourse($mentor);

        $courses = app(CourseApiClient::class)->publishedCourses();

        $this->assertNotEmpty($courses);
        $this->assertSame($course->title, $courses[0]['title']);
        $this->assertArrayHasKey('category', $courses[0]);
        $this->assertArrayHasKey('mentor', $courses[0]);
    }

    public function test_admin_user_edit_page_shows_real_data_when_service_unavailable(): void
    {
        Http::fake([
            '*/api/enrollments/user/*/stats*' => Http::response(['status' => 'E', 'timestamp' => now()->toIso8601String()], 500),
        ]);

        $admin = $this->makeUser('Admin', 'admin@test.com');
        $mentor = $this->makeUser('Mentor', 'mentor@test.com');
        $student = $this->makeUser('Student', 'student@test.com');
        $course = $this->makeCourse($mentor);
        $this->enroll($student, $course, 'Active');

        // Even when the Enrollment web service is unreachable, the local
        // fallback supplies the real statistics so the page is never half-broken.
        $this->actingAs($admin)
            ->get(route('admin.users.edit', $student))
            ->assertOk()
            ->assertSee('Enrollment Statistics')
            ->assertDontSee('temporarily unavailable');
    }

    public function test_admin_user_edit_page_consumes_enrollment_stats(): void
    {
        Http::fake([
            '*/api/enrollments/user/*/stats' => Http::response([
                'status' => 'S',
                'requestId' => 'req-7',
                'timestamp' => now()->toIso8601String(),
                'data' => ['user_id' => 2, 'total' => 3, 'active' => 2, 'completed' => 1, 'dropped' => 0],
            ], 200),
        ]);

        $admin = $this->makeUser('Admin', 'admin@test.com');
        $student = $this->makeUser('Student', 'student@test.com');

        $this->actingAs($admin)
            ->get(route('admin.users.edit', $student))
            ->assertOk()
            ->assertSee('Enrollment Statistics')
            ->assertSee('GET /api/enrollments/user/'.$student->id.'/stats');
    }

    // ------------------------------------------------------------------
    // Enrollment module notifies via the Notification web service
    // ------------------------------------------------------------------

    public function test_enrollment_complete_notifies_student_via_notification_api(): void
    {
        Http::fake([
            '*/api/notifications' => Http::response([
                'status' => 'S',
                'requestId' => 'req-8',
                'timestamp' => now()->toIso8601String(),
                'data' => ['notification_id' => 1, 'user_id' => 1, 'recipient_name' => 'Test Student'],
            ], 200),
        ]);

        $admin = $this->makeUser('Admin', 'admin@test.com');
        $mentor = $this->makeUser('Mentor', 'mentor@test.com');
        $student = $this->makeUser('Student', 'student@test.com');
        $course = $this->makeCourse($mentor);
        $enrollment = $this->enroll($student, $course, 'Active');

        $this->actingAs($admin)
            ->post(route('enrollments.complete', $enrollment))
            ->assertRedirect(route('enrollments.index'));

        $this->assertDatabaseHas('enrollments', ['id' => $enrollment->id, 'status' => 'Completed']);

        Http::assertSent(function ($request) use ($student) {
            return str_contains($request->url(), '/api/notifications')
                && ($request->data()['user_id'] ?? null) === $student->id
                && str_contains((string) ($request->data()['title'] ?? ''), 'completed');
        });
    }

    public function test_enrollment_drop_notifies_student_via_notification_api(): void
    {
        Http::fake([
            '*/api/notifications' => Http::response([
                'status' => 'S',
                'requestId' => 'req-9',
                'timestamp' => now()->toIso8601String(),
                'data' => ['notification_id' => 2, 'user_id' => 1, 'recipient_name' => 'Test Student'],
            ], 200),
        ]);

        $admin = $this->makeUser('Admin', 'admin@test.com');
        $mentor = $this->makeUser('Mentor', 'mentor@test.com');
        $student = $this->makeUser('Student', 'student@test.com');
        $course = $this->makeCourse($mentor);
        $enrollment = $this->enroll($student, $course, 'Active');

        $this->actingAs($admin)
            ->post(route('enrollments.drop', $enrollment))
            ->assertRedirect(route('enrollments.index'));

        $this->assertDatabaseHas('enrollments', ['id' => $enrollment->id, 'status' => 'Dropped']);

        Http::assertSent(function ($request) use ($student) {
            return str_contains($request->url(), '/api/notifications')
                && ($request->data()['user_id'] ?? null) === $student->id
                && str_contains((string) ($request->data()['title'] ?? ''), 'dropped');
        });
    }
}
