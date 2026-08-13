<?php

/**
 * @author GEN TECK NG
 *
 * GoF Strategy pattern - Strategy interface for enrollment processing.
 * Concrete strategies encapsulate the state-changing business logic for each
 * enrollment lifecycle action so the controller does not re-implement it.
 */

namespace App\Patterns\Strategy;

use App\Models\Enrollment;

interface EnrollmentStrategy
{
    /**
     * Execute the enrollment action described by the given payload and
     * return the resulting enrollment.
     *
     * @param  array<string, mixed>  $data
     */
    public function execute(array $data): Enrollment;
}
