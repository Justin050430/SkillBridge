<?php

/**
 * @author OOI JIAN XIN
 *
 * GoF Strategy pattern - Context.
 * Executes whichever enrollment strategy has been selected, keeping the
 * controllers free of state-changing business logic.
 */

namespace App\Patterns\Strategy;

use App\Models\Enrollment;

class EnrollmentProcessor
{
    /**
     * Run the given strategy with the supplied payload.
     *
     * @param  array<string, mixed>  $data
     */
    public function process(EnrollmentStrategy $strategy, array $data = []): Enrollment
    {
        return $strategy->execute($data);
    }
}
