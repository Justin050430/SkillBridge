<?php

/**
 * @author GEN TECK NG
 *
 * GoF Strategy pattern - ConcreteStrategy.
 * Transitions an Active/Completed enrollment to Dropped.
 * Payload: ['enrollment' => Enrollment]
 */

namespace App\Patterns\Strategy;

use App\Models\Enrollment;

class DropEnrollmentStrategy implements EnrollmentStrategy
{
    public function execute(array $data): Enrollment
    {
        $enrollment = $data['enrollment'];

        abort_unless(
            in_array($enrollment->status, ['Active', 'Completed'], true),
            422,
            'This enrollment cannot be dropped.'
        );

        $enrollment->update([
            'status' => 'Dropped',
            'completed_at' => null,
        ]);

        return $enrollment->refresh();
    }
}
