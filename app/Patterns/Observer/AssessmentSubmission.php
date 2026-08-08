<?php

/**
 * @author OOI JIAN XIN
 *
 * GoF Observer pattern - ConcreteSubject.
 * Represents the submission of an assessment attempt. After a student's
 * attempt has been graded, this subject publishes the state change to every
 * registered observer (e.g. ProgressUpdater, SubmissionLogger).
 */

namespace App\Patterns\Observer;

use App\Models\Assessment;
use App\Models\AssessmentAttempt;

class AssessmentSubmission implements Subject
{
    /**
     * @var array<int, Observer>
     */
    protected array $observers = [];

    public function __construct(
        protected AssessmentAttempt $attempt,
        protected Assessment $assessment
    ) {
    }

    public function attach(Observer $observer): void
    {
        if (!in_array($observer, $this->observers, true)) {
            $this->observers[] = $observer;
        }
    }

    public function detach(Observer $observer): void
    {
        $this->observers = array_values(array_filter(
            $this->observers,
            fn (Observer $registered) => $registered !== $observer
        ));
    }

    public function notify(): void
    {
        foreach ($this->observers as $observer) {
            $observer->update($this);
        }
    }

    /**
     * The graded attempt that triggered this notification.
     */
    public function getAttempt(): AssessmentAttempt
    {
        return $this->attempt;
    }

    /**
     * The assessment that the attempt belongs to.
     */
    public function getAssessment(): Assessment
    {
        return $this->assessment;
    }
}
