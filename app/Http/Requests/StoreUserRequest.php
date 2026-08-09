<?php

namespace App\Http\Requests;

use App\Support\ValidationRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

/**
 * Validates the creation of a user by an Admin.
 *
 * Authorization (role:Admin) is enforced by route middleware, so this request
 * only concerns itself with input validation.
 */
class StoreUserRequest extends FormRequest
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
            'first_name' => ValidationRules::name(),
            'last_name' => ValidationRules::name(),
            'email' => ['required', 'string', 'email', 'lowercase', 'max:255', 'unique:users,email'],
            'role_id' => 'required|exists:roles,id',
            'password' => ['required', 'confirmed', Password::defaults()],
        ];
    }
}
