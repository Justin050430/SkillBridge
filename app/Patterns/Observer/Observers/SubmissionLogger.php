<?php

/**
 * @author OOI JIAN XIN
 *
 * GoF Observer pattern - ConcreteObserver.
 * Writes an audit record to the dedicated "assessment" log channel whenever an
 * assessment attempt is submitted. Sensitive details (session identifiers,
 * hashes, credentials) are intentionally NOT written, aligning with the
 * secure coding practices for error handling and logging.
 */

namespace App\Patterns\Observer\Observers;

use App\Patterns\Observer\AssessmentSubmission;
use App\Patterns\Observer\Observer;
use App\Patterns\Observer\Subject;
use Illuminate\Support\Facades\Log;

class SubmissionLogger implements Observer
{
    public function update(Subject $subject): void
    {
        if (!$subject instanceof AssessmentSubmission) {
            return;
        }

        $attempt = $subject->getAttempt();
        $assessment = $subject->getAssessment();

        Log::channel('assessment')->info('Assessment submitted', [
            'assessment_id' => $assessment->id,
            'assessment_title' => $assessment->title,
            'attempt_id' => $attempt->id,
            'student_id' => $attempt->enrollment?->student_id,
            'score' => $attempt->score,
            'percentage' => $attempt->percentage,
            'is_passed' => $attempt->is_passed,
            'timestamp' => now()->toIso8601String(),
        ]);
    }
}
