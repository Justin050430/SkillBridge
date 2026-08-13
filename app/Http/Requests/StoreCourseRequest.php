<?php

/**
 * @author ZHI FOOK CHUA
 */

namespace App\Http\Requests;

use App\Models\User;
use App\Support\ValidationRules;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates the creation of a course by an Admin.
 *
 * Authorization (role:Admin) is enforced by route middleware.
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
            'mentor_id' => [
                'required',
                'exists:users,id',
                function (string $attribute, mixed $value, \Closure $fail) {
                    if (!User::mentors()->whereKey($value)->exists()) {
                        $fail('The selected mentor is invalid.');
                    }
                },
            ],
            'category_id' => 'required|exists:categories,id',
            'title' => ValidationRules::title(),
            'description' => ValidationRules::longText(5000),
            'thumbnail' => 'nullable|string|max:255',
            'status' => 'required|in:Draft,Published,Archived',
        ];
    }
}
