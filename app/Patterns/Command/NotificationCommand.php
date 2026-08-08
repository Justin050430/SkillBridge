<?php

/**
 * @author OOI JIAN XIN
 *
 * GoF Command pattern - Command interface.
 * Encapsulates a notification intent as an object that can be executed by an
 * invoker. The intent (who should be notified, and about what) is captured in
 * the command, keeping the trigger (controller/observer) decoupled from the
 * actual notification logic.
 */

namespace App\Patterns\Command;

interface NotificationCommand
{
    /**
     * Execute the encapsulated notification.
     */
    public function execute(): void;
}
