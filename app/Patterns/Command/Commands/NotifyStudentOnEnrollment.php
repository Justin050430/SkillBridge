<?php

/**
 * @author OOI JIAN XIN
 *
 * GoF Command pattern - ConcreteCommand.
 * Confirms to a student that they have successfully enrolled in a course.
 * Complements NotifyMentorOnEnrollment so both parties are informed.
 */

namespace App\Patterns\Command\Commands;

use App\Models\Course;
use App\Models\User;
use App\Patterns\Command\NotificationCommand;
use App\Services\NotificationService;

class NotifyStudentOnEnrollment implements NotificationCommand
{
    public function __construct(
        protected NotificationService $notifier,
        protected User $student,
        protected Course $course
    ) {
    }

    public function execute(): void
    {
        $this->notifier->notify(
            $this->student,
            'Enrollment confirmed',
            "You have successfully enrolled in \"{$this->course->title}\".",
            'Enrollment',
            Course::class,
            $this->course->id
        );
    }
}
