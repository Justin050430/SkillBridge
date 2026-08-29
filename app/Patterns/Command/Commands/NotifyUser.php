<?php

/**
 * @author YIK HANG YIP
 *
 * GoF Command pattern - ConcreteCommand.
 *
 * A generic "notify a user" command. This is the command executed by the
 * Notification module's web service (POST /api/notifications): the API
 * controller packs the request into a NotifyUser command and hands it to the
 * NotificationInvoker, keeping the caller decoupled from how the notification
 * is actually created.
 */

namespace App\Patterns\Command\Commands;

use App\Models\Notification;
use App\Models\User;
use App\Patterns\Command\NotificationCommand;
use App\Services\NotificationService;

class NotifyUser implements NotificationCommand
{
    protected ?Notification $notification = null;

    public function __construct(
        protected NotificationService $service,
        protected User $user,
        protected string $title,
        protected string $message,
        protected string $type,
        protected ?string $relatedType = null,
        protected ?int $relatedId = null
    ) {
    }

    public function execute(): void
    {
        $this->notification = $this->service->notify(
            $this->user,
            $this->title,
            $this->message,
            $this->type,
            $this->relatedType,
            $this->relatedId
        );
    }

    /**
     * The notification created by the most recent execution (null before run).
     */
    public function getNotification(): ?Notification
    {
        return $this->notification;
    }
}
