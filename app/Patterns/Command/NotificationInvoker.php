<?php

/**
 * @author YIK HANG YIP
 *
 * GoF Command pattern - Invoker.
 * Triggers the execution of a notification command. The invoker knows how to
 * run a command but not what the command does, keeping the caller decoupled
 * from the concrete notification behaviour (and open to queuing/logging later).
 */

namespace App\Patterns\Command;

class NotificationInvoker
{
    /**
     * Execute the given notification command.
     */
    public function run(NotificationCommand $command): void
    {
        $command->execute();
    }
}
