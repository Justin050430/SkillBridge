<?php

/**
 * @author YIK HANG YIP
 *
 * GoF Command pattern - ConcreteCommand.
 * Sends a security notification to a user confirming that their password was
 * changed, flagging the change so the user can detect unauthorised activity.
 */

namespace App\Patterns\Command\Commands;

use App\Models\User;
use App\Patterns\Command\NotificationCommand;
use App\Services\NotificationService;

class NotifyUserOnPasswordChange implements NotificationCommand
{
    public function __construct(
        protected NotificationService $notifier,
        protected User $user
    ) {
    }

    public function execute(): void
    {
        $this->notifier->notify(
            $this->user,
            'Password changed',
            'Your password was changed successfully. If you did not make this change, please contact support immediately.',
            'Security'
        );
    }
}
