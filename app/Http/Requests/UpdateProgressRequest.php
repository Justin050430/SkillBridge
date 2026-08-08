<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates marking a lesson as In Progress / Completed on the student's
 * My Progress page.
 *
 * Authorization (enrolled student) is enforced server-side in the controller.
 */
class UpdateProgressRequest extends FormRequest
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
            'status' => 'required|in:In Progress,Completed',
        ];
    }
}
