<?php

/**
 * @author OOI JIAN XIN
 *
 * GoF Observer pattern - Subject interface.
 * A Subject maintains a registry of observers and notifies them whenever
 * its state changes, without needing to know their concrete types.
 */

namespace App\Patterns\Observer;

interface Subject
{
    /**
     * Register an observer to be notified of state changes.
     */
    public function attach(Observer $observer): void;

    /**
     * Remove an observer from the notification list.
     */
    public function detach(Observer $observer): void;

    /**
     * Notify all registered observers of a state change.
     */
    public function notify(): void;
}
