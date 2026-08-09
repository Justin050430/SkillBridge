<?php

namespace App\Http\Requests;

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
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'order_number' => 'required|integer|min:1',
            'estimated_duration' => 'nullable|integer|min:1|max:600',
            'status' => 'required|in:Draft,Published',
        ];
    }
}
