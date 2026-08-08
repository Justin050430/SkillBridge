<?php

/**
 * @author OOI JIAN XIN
 *
 * GoF Observer pattern - Observer interface.
 * Concrete observers implement update() to react to a subject's state change.
 */

namespace App\Patterns\Observer;

interface Observer
{
    /**
     * React to a state change published by the given subject.
     */
    public function update(Subject $subject): void;
}
