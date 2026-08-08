<?php

/**
 * @author OOI JIAN XIN
 *
 * GoF Command pattern - ConcreteCommand.
 * Notifies a course mentor that a student submitted one of their assessments.
 */

namespace App\Patterns\Command\Commands;

use App\Models\Assessment;
use App\Models\User;
use App\Patterns\Command\NotificationCommand;
use App\Services\NotificationService;

class NotifyMentorOnAssessmentSubmission implements NotificationCommand
{
    public function __construct(
        protected NotificationService $notifier,
        protected User $mentor,
        protected User $student,
        protected Assessment $assessment
    ) {
    }

    public function execute(): void
    {
        $studentName = $this->student->first_name.' '.$this->student->last_name;

        $this->notifier->notify(
            $this->mentor,
            'Assessment submitted',
            "{$studentName} submitted \"{$this->assessment->title}\".",
            'Assessment'
        );
    }
}
