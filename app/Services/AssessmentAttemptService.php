<?php

namespace App\Services;

use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\Enrollment;
use Closure;
use Illuminate\Support\Facades\DB;

class AssessmentAttemptService
{
    public function start(Assessment $assessment, int $enrollmentId): ?AssessmentAttempt
    {
        return DB::transaction(function () use ($assessment, $enrollmentId) {
            $this->lockEnrollment($enrollmentId);
            $assessment->refresh();
            $attempts = AssessmentAttempt::where('enrollment_id', $enrollmentId)
                ->where('assessment_id', $assessment->id)->orderBy('attempt_number')->lockForUpdate()->get();

            foreach ($attempts as $attempt) {
                $this->expireIfDue($attempt);
            }

            if ($active = $attempts->first(fn ($attempt) => $attempt->isActive())) {
                return $active;
            }

            if ($assessment->max_attempts !== null && $attempts->count() >= $assessment->max_attempts) {
                return null;
            }

            // Use the same persisted second precision for both timestamps.
            $startedAt = now()->startOfSecond();

            return AssessmentAttempt::create([
                'enrollment_id' => $enrollmentId,
                'assessment_id' => $assessment->id,
                'attempt_number' => ($attempts->max('attempt_number') ?? 0) + 1,
                'score' => 0,
                'percentage' => 0,
                'is_passed' => false,
                'started_at' => $startedAt,
                'expires_at' => $assessment->time_limit === null
                    ? null : $startedAt->copy()->addSeconds($assessment->time_limit),
            ]);
        }, 3);
    }

    public function withAttempt(Assessment $assessment, int $enrollmentId, int $attemptId, Closure $action): mixed
    {
        return DB::transaction(function () use ($assessment, $enrollmentId, $attemptId, $action) {
            // All lifecycle mutations lock in this order, including concurrent starts.
            $this->lockEnrollment($enrollmentId);
            $attempt = AssessmentAttempt::whereKey($attemptId)
                ->where('assessment_id', $assessment->id)
                ->where('enrollment_id', $enrollmentId)->lockForUpdate()->first();
            abort_unless($attempt, 403, 'This attempt is not available.');
            $this->expireIfDue($attempt);

            // Return an expired response normally, so the expiration is committed.
            return $action($attempt);
        }, 3);
    }

    public function expireIfDue(AssessmentAttempt $attempt): void
    {
        if (! $attempt->hasExpired() || $attempt->expired_at !== null) {
            return;
        }

        AssessmentAttempt::whereKey($attempt->id)->whereNull('submitted_at')
            ->whereNull('expired_at')->where('expires_at', '<=', now())
            ->update(['expired_at' => $attempt->expires_at, 'is_passed' => false]);
        $attempt->refresh();
    }

    private function lockEnrollment(int $enrollmentId): void
    {
        // SQLite ignores FOR UPDATE. Acquire its write lock before any reads
        // so simultaneous starts cannot race while upgrading read transactions.
        // Query builder avoids changing the enrollment's updated_at timestamp.
        if (DB::connection()->getDriverName() === 'sqlite') {
            DB::table('enrollments')->where('id', $enrollmentId)->update(['id' => DB::raw('id')]);
        }

        $enrollment = Enrollment::whereKey($enrollmentId)->lockForUpdate()->first();
        abort_unless($enrollment && $enrollment->status === 'Active', 403, 'This enrollment is no longer active.');
    }
}
