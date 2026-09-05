<?php

/**
 * @author OOI JIAN XIN
 */

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Validator;

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
            'attempt_id' => 'required|integer|min:1',
        ];
    }

    public function validatedAnswers(): array
    {
        // Called under the attempt lock, after resolving expiration. A malformed
        // late submission must end the attempt instead of returning to the form.
        return Validator::make($this->only('answers'), [
            'answers' => 'nullable|array',
            'answers.*' => 'nullable|string',
        ])->validate()['answers'] ?? [];
    }
}
