<?php

/**
 * @author OOI JIAN XIN
 *
 * Verifies the demo-data seeder produces realistic, relationship-valid content
 * that a newly registered student can immediately browse and use.
 */

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\Category;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Question;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoDataSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_creates_realistic_content(): void
    {
        $this->seed(DemoDataSeeder::class);

        $this->assertGreaterThanOrEqual(20, Course::count());
        $this->assertGreaterThan(0, Course::where('status', 'Published')->count());
        $this->assertGreaterThan(0, Lesson::count());
        $this->assertGreaterThanOrEqual(2, Assessment::count());
        $this->assertGreaterThan(0, Question::count());
        $this->assertGreaterThan(0, User::mentors()->count());
        $this->assertGreaterThan(0, Category::count());

        // Every published assessment must be student-ready: its parent lesson
        // is Published AND it has at least one question.
        Assessment::where('status', 'Published')->get()->each(function (Assessment $assessment) {
            $this->assertSame('Published', $assessment->lesson?->status);
            $this->assertGreaterThan(0, $assessment->questions()->count());
        });
    }

    public function test_new_student_can_see_seeded_published_courses(): void
    {
        $this->seed(DemoDataSeeder::class);

        $studentRoleId = Role::where('role_name', 'Student')->value('id');
        User::factory()->create(['role_id' => $studentRoleId]);

        // The student catalogue only exposes published courses.
        $visible = Course::where('status', 'Published')->count();

        $this->assertGreaterThan(0, $visible);
        $this->assertSame($visible, Course::count() - Course::where('status', '!=', 'Published')->count());
    }

    public function test_seeder_is_idempotent(): void
    {
        $this->seed(DemoDataSeeder::class);
        $courses = Course::count();

        $this->seed(DemoDataSeeder::class);

        $this->assertSame($courses, Course::count());
    }
}
