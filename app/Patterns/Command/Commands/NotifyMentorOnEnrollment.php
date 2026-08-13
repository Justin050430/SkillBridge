<?php

/**
 * @author YIK HANG YIP
 *
 * GoF Command pattern - ConcreteCommand.
 * Notifies a course mentor that a student has enrolled in one of their courses.
 */

namespace App\Patterns\Command\Commands;

use App\Models\Course;
use App\Models\User;
use App\Patterns\Command\NotificationCommand;
use App\Services\NotificationService;

class NotifyMentorOnEnrollment implements NotificationCommand
{
    public function __construct(
        protected NotificationService $notifier,
        protected User $mentor,
        protected User $student,
        protected Course $course
    ) {
    }

    public function execute(): void
    {
        $studentName = $this->student->first_name.' '.$this->student->last_name;

        $this->notifier->notify(
            $this->mentor,
            'New enrollment',
            "{$studentName} enrolled in your course \"{$this->course->title}\".",
            'Enrollment'
        );
    }
}
