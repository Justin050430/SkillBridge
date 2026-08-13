<?php

/**
 * @author YIK HANG YIP
 *
 * GoF Command pattern - ConcreteCommand.
 * Broadcasts a security-related event to every active admin. Used for
 * significant administrative/security events (e.g. failed-login lockouts,
 * account deactivations). Admins are only notified for events that genuinely
 * require their awareness - never for routine student activity.
 */

namespace App\Patterns\Command\Commands;

use App\Models\User;
use App\Patterns\Command\NotificationCommand;
use App\Services\NotificationService;

class NotifyAdminsOnSecurityEvent implements NotificationCommand
{
    public function __construct(
        protected NotificationService $notifier,
        protected string $title,
        protected string $message,
        protected ?string $relatedType = null,
        protected ?int $relatedId = null
    ) {
    }

    public function execute(): void
    {
        foreach (User::admins()->active()->get() as $admin) {
            $this->notifier->notify(
                $admin,
                $this->title,
                $this->message,
                'Security',
                $this->relatedType,
                $this->relatedId
            );
        }
    }
}
