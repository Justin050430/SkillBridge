<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates the creation of a question by an Admin or Mentor.
 *
 * The correct answer is captured from a type-specific field
 * (correct_answer_mcq / correct_answer_tf / correct_answer_short) and is
 * normalised into the question's correct_answer column by the controller.
 */
class StoreQuestionRequest extends FormRequest
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
            'question_text' => ['required', 'string', 'max:2000', 'regex:/^[^<>]+$/'],
            'question_type' => 'required|in:MCQ,TrueFalse,ShortAnswer',
            'option_a' => 'nullable|string|max:255',
            'option_b' => 'nullable|string|max:255',
            'option_c' => 'nullable|string|max:255',
            'option_d' => 'nullable|string|max:255',
            'correct_answer_mcq' => 'nullable|required_if:question_type,MCQ|string|max:255',
            'correct_answer_tf' => 'nullable|required_if:question_type,TrueFalse|string|max:255',
            'correct_answer_short' => 'nullable|required_if:question_type,ShortAnswer|string|max:255',
            'explanation' => 'nullable|string|max:5000',
            'marks' => 'required|integer|min:1|max:100',
            'order_number' => 'required|integer|min:1|max:9999',
        ];
    }
}
