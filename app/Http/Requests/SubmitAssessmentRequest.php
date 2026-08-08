<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates the student's submitted answers for an assessment.
 *
 * Answers are keyed by question id; empty submissions are allowed (the
 * controller grades whatever answers were provided).
 *
 * Authorization (enrolled student) is enforced server-side in the controller.
 */
class SubmitAssessmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'answers' => 'nullable|array',
            'answers.*' => 'nullable|string',
        ];
    }
}
