<?php

/**
 * @author ZHI FOOK CHUA
 */

namespace App\Http\Requests\Mentor;

use App\Support\ValidationRules;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates the creation of a course by a Mentor.
 *
 * Note: there is intentionally NO mentor_id field — the controller always
 * derives mentor_id from the authenticated mentor server-side, so a client
 * can never assign a course to another mentor.
 *
 * Authorization (role:Mentor) is enforced by route middleware and mentor
 * scoping is enforced server-side in the controller.
 */
class StoreCourseRequest extends FormRequest
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
            'category_id' => 'required|exists:categories,id',
            'title' => ValidationRules::title(),
            'description' => ValidationRules::longText(5000),
            'thumbnail' => 'nullable|string|max:255',
            'status' => 'required|in:Draft,Published',
        ];
    }
}
