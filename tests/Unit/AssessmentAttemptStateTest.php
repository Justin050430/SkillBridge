<?php

namespace Tests\Unit;

use App\Models\AssessmentAttempt;
use Tests\TestCase;

class AssessmentAttemptStateTest extends TestCase
{
    public function test_deadline_is_authoritative_before_expiration_is_persisted(): void
    {
        $this->freezeTime();
        $attempt = new AssessmentAttempt(['started_at' => now(), 'expires_at' => now()->addSeconds(10)]);
        $this->assertTrue($attempt->isActive());
        $this->travel(10)->seconds();
        $this->assertNull($attempt->expired_at);
        $this->assertTrue($attempt->hasExpired());
        $this->assertFalse($attempt->isActive());
    }

    public function test_an_accepted_submission_never_expires_later(): void
    {
        $this->freezeTime();
        $attempt = new AssessmentAttempt([
            'started_at' => now()->subMinute(), 'submitted_at' => now()->subSecond(), 'expires_at' => now(),
        ]);
        $this->assertFalse($attempt->hasExpired());
        $this->assertFalse($attempt->isActive());
    }
}
