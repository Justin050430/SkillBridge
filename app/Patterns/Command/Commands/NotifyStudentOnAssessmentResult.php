<?php

/**
 * @author OOI JIAN XIN
 *
 * GoF Command pattern - ConcreteCommand.
 * Notifies a student that their assessment result is ready, including the
 * pass/fail outcome and percentage.
 */

namespace App\Patterns\Command\Commands;

use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\User;
use App\Patterns\Command\NotificationCommand;
use App\Services\NotificationService;

class NotifyStudentOnAssessmentResult implements NotificationCommand
{
    public function __construct(
        protected NotificationService $notifier,
        protected User $student,
        protected Assessment $assessment,
        protected AssessmentAttempt $attempt
    ) {
    }

    public function execute(): void
    {
        $outcome = $this->attempt->is_passed ? 'passed' : 'did not pass';

        $this->notifier->notify(
            $this->student,
            'Assessment result',
            "Your result for \"{$this->assessment->title}\" is ready. "
                ."You {$outcome} with {$this->attempt->percentage}%.",
            'Assessment'
        );
    }
}
