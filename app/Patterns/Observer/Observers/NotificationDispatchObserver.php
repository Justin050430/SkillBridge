<?php

/**
 * @author OOI JIAN XIN
 *
 * GoF Observer pattern - ConcreteObserver.
 * Reacts to an AssessmentSubmission and composes the Command pattern: it
 * dispatches the relevant notification commands through the NotificationInvoker
 * (student result + mentor submission notification).
 */

namespace App\Patterns\Observer\Observers;

use App\Patterns\Command\Commands\NotifyMentorOnAssessmentSubmission;
use App\Patterns\Command\Commands\NotifyStudentOnAssessmentResult;
use App\Patterns\Command\NotificationInvoker;
use App\Patterns\Observer\AssessmentSubmission;
use App\Patterns\Observer\Observer;
use App\Patterns\Observer\Subject;
use App\Services\NotificationService;

class NotificationDispatchObserver implements Observer
{
    public function update(Subject $subject): void
    {
        if (!$subject instanceof AssessmentSubmission) {
            return;
        }

        $attempt = $subject->getAttempt();
        $assessment = $subject->getAssessment();

        $student = $attempt->enrollment?->student;
        $mentor = $assessment->lesson?->course?->mentor;

        $invoker = app(NotificationInvoker::class);
        $notifier = app(NotificationService::class);

        if ($student) {
            $invoker->run(new NotifyStudentOnAssessmentResult($notifier, $student, $assessment, $attempt));
        }

        if ($mentor && $student) {
            $invoker->run(new NotifyMentorOnAssessmentSubmission($notifier, $mentor, $student, $assessment));
        }
    }
}
