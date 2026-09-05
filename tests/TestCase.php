<?php

namespace Tests;

use App\Models\Assessment;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Testing\TestResponse;

abstract class TestCase extends BaseTestCase
{
    /** Follow the explicit start redirect, as the browser does after Start/Retake. */
    protected function startAssessment(Assessment $assessment): TestResponse
    {
        $response = $this->post(route('student.assessments.start', $assessment));
        $location = $response->headers->get('Location');
        if ($response->isRedirect() && $location && str_contains($location, '/take?attempt=')) {
            return $this->get($location);
        }

        return $response;
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }
}
