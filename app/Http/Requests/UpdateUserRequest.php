<?php

namespace App\Http\Requests;

use App\Support\ValidationRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * Validates the update of a user by an Admin.
 *
 * Unlike creation, the email is unique but must ignore the current user, the
 * password is optional, and the account status may be toggled (Active/Inactive).
 *
 * Authorization (role:Admin) is enforced by route middleware.
 */
class UpdateUserRequest extends FormRequest
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
        $user = $this->route('user');

        return [
            'first_name' => ValidationRules::name(),
            'last_name' => ValidationRules::name(),
            'email' => [
                'required',
                'string',
                'email',
                'lowercase',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'role_id' => 'required|exists:roles,id',
            'status' => 'nullable|in:Active,Inactive',
            'password' => ['nullable', 'confirmed', Password::defaults()],
        ];
    }
}
