<?php

/**
 * @author GEN TECK NG
 *
 * GoF Strategy pattern - ConcreteStrategy.
 * Transitions an Active enrollment to Completed (recording completed_at).
 * Payload: ['enrollment' => Enrollment]
 */

namespace App\Patterns\Strategy;

use App\Models\Enrollment;

class CompleteEnrollmentStrategy implements EnrollmentStrategy
{
    public function execute(array $data): Enrollment
    {
        $enrollment = $data['enrollment'];

        abort_unless(
            $enrollment->status === 'Active',
            422,
            'Only active enrollments can be completed.'
        );

        $enrollment->update([
            'status' => 'Completed',
            'completed_at' => now(),
        ]);

        return $enrollment->refresh();
    }
}
