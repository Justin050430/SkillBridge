<?php

/**
 * @author OOI JIAN XIN
 *
 * GoF Observer pattern - ConcreteObserver.
 * When an assessment attempt is submitted and passed, this observer marks the
 * lesson (to which the assessment belongs) as Completed in the student's
 * progress record, linking the assessment flow with progress tracking.
 */

namespace App\Patterns\Observer\Observers;

use App\Models\Progress;
use App\Patterns\Observer\AssessmentSubmission;
use App\Patterns\Observer\Observer;
use App\Patterns\Observer\Subject;

class ProgressUpdater implements Observer
{
    public function update(Subject $subject): void
    {
        if (!$subject instanceof AssessmentSubmission) {
            return;
        }

        $attempt = $subject->getAttempt();

        // Only a passing submission completes the linked lesson.
        if (!$attempt->is_passed) {
            return;
        }

        Progress::updateOrCreate(
            [
                'enrollment_id' => $attempt->enrollment_id,
                'lesson_id' => $subject->getAssessment()->lesson_id,
            ],
            [
                'status' => 'Completed',
                'completed_at' => now(),
            ]
        );
    }
}
