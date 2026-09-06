<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\Category;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class AssessmentAttemptConcurrencyTest extends TestCase
{
    public function test_simultaneous_starts_share_one_attempt_and_simultaneous_late_requests_cannot_reopen_it(): void
    {
        $database = storage_path('framework/testing/assessment-race-'.bin2hex(random_bytes(8)).'.sqlite');
        $barrier = $database.'.ready';
        touch($database);
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => $database]);
        DB::purge('sqlite');

        try {
            Artisan::call('migrate', ['--database' => 'sqlite', '--force' => true]);
            $role = Role::create(['role_name' => 'Student']);
            $student = User::create(['role_id' => $role->id, 'first_name' => 'Race', 'last_name' => 'Test', 'email' => 'race@example.test', 'password' => 'unused-test-password']);
            $category = Category::create(['category_name' => 'Race test']);
            $course = Course::create(['mentor_id' => $student->id, 'category_id' => $category->id, 'title' => 'Race course', 'description' => 'Test', 'status' => 'Published']);
            $lesson = Lesson::create(['course_id' => $course->id, 'title' => 'Race lesson', 'content' => 'Test', 'order_number' => 1, 'status' => 'Published']);
            $assessment = Assessment::create(['lesson_id' => $lesson->id, 'title' => 'Race assessment', 'passing_score' => 50, 'time_limit' => 600, 'max_attempts' => 1, 'status' => 'Published']);
            $enrollment = Enrollment::create(['student_id' => $student->id, 'course_id' => $course->id, 'status' => 'Active', 'enrolled_at' => now()]);

            $results = $this->race($database, $barrier, $assessment, $enrollment, 'start');
            $this->assertSame(1, AssessmentAttempt::count());
            $attempt = AssessmentAttempt::firstOrFail();
            $this->assertSame([$attempt->id], array_values(array_unique(array_column($results, 'attempt'))));

            $attempt->update(['expires_at' => now()->subSecond()]);
            $results = $this->race($database, $barrier, $assessment, $enrollment, 'submit', $attempt->id);
            $this->assertSame(['expired'], array_values(array_unique(array_column($results, 'state'))));
            $this->assertNotNull($attempt->fresh()->expired_at);
            $this->assertNull($attempt->fresh()->submitted_at);

            $results = $this->race($database, $barrier, $assessment, $enrollment, 'start');
            $this->assertSame([null, null, null], array_column($results, 'attempt'));
            $this->assertSame(1, AssessmentAttempt::count());
        } finally {
            DB::disconnect('sqlite');
            foreach ([$database, $barrier] as $temporaryFile) {
                if (file_exists($temporaryFile)) {
                    unlink($temporaryFile);
                }
            }
        }
    }

    private function race(string $database, string $barrier, Assessment $assessment, Enrollment $enrollment, string $action, int $attemptId = 0): array
    {
        if (file_exists($barrier)) {
            unlink($barrier);
        }
        $workers = [];
        try {
            foreach (range(1, 3) as $number) {
                $worker = new Process([PHP_BINARY, base_path('tests/Support/assessment-race-worker.php'), $database, (string) $assessment->id, (string) $enrollment->id, $barrier, $action, (string) $attemptId], base_path());
                $worker->setTimeout(20);
                $worker->start();
                $workers[] = $worker;
            }
            $until = microtime(true) + 10;
            do {
                $ready = array_filter($workers, fn ($worker) => str_contains($worker->getOutput(), 'READY'));
                if (count($ready) === count($workers)) {
                    break;
                }
                usleep(10000);
            } while (microtime(true) < $until);
            foreach ($workers as $worker) {
                $this->assertStringContainsString('READY', $worker->getOutput(), $worker->getErrorOutput());
            }
            touch($barrier);

            return array_map(function ($worker) {
                $worker->wait();
                $this->assertTrue($worker->isSuccessful(), $worker->getErrorOutput().$worker->getOutput());
                $lines = explode("\n", trim($worker->getOutput()));
                $this->assertJson(end($lines), $worker->getOutput());

                return json_decode(end($lines), true, flags: JSON_THROW_ON_ERROR);
            }, $workers);
        } finally {
            foreach ($workers as $worker) {
                if ($worker->isRunning()) {
                    $worker->stop();
                }
            }
        }
    }
}
