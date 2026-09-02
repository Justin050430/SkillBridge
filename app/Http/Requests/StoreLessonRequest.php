<?php

/**
 * @author ZHI FOOK CHUA
 */

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesLessonResources;
use App\Support\ValidationRules;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates the creation of a lesson within a course by an Admin or Mentor.
 *
 * The course is bound from the nested route ({course:slug}); the mentor's
 * course scope is enforced server-side in the controller (ensureAccessible),
 * so this request only concerns itself with lesson field validation.
 */
class StoreLessonRequest extends FormRequest
{
    use ValidatesLessonResources;

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
            'title' => ValidationRules::title(),
            'description' => 'nullable|string|max:2000',
            'content' => ValidationRules::longText(20000),
            'order_number' => 'required|integer|min:1|max:9999',
            'estimated_duration' => 'nullable|integer|min:1|max:600',
            'status' => 'required|in:Draft,Published',
        ] + $this->resourceRules();
    }

    public function withValidator(\Illuminate\Contracts\Validation\Validator $validator): void
    {
        $this->validateLessonResources($validator);
    }
    public function messages(): array
{
    return [
        'resources.*.title.required' => 'Title is required.',
    ];
}
}
