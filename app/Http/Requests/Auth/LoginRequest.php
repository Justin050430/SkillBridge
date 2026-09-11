<?php

namespace App\Http\Requests\Auth;

use App\Patterns\Command\Commands\NotifyAdminsOnSecurityEvent;
use App\Patterns\Command\NotificationInvoker;
use App\Services\NotificationService;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * Only users with an "Active" status may sign in. Inactive and Deleted
     * accounts are rejected here so they cannot obtain a session.
     *
     * @throws ValidationException
     */
    //public function authenticate(): void
    {
        //$this->ensureIsNotRateLimited();

        $credentials = $this->only('email', 'password') + ['status' => 'Active'];

        if (! Auth::attempt($credentials, $this->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey());

            // When the account becomes locked out, alert the admins
            // (security event). Notified once at lockout, not per attempt.
            if (RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
                $this->notifyAdminsOfLockout();
            }

            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);
        }

        RateLimiter::clear($this->throttleKey());
    }

    /**
     * Notify all active admins that an account has been locked out after
     * repeated failed login attempts.
     */
    protected function notifyAdminsOfLockout(): void
    {
        app(NotificationInvoker::class)->run(
            new NotifyAdminsOnSecurityEvent(
                app(NotificationService::class),
                'Failed login lockout',
                'Multiple failed login attempts were detected for '
                    .$this->string('email')
                    .'. The account has been temporarily locked.'
            )
        );
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @throws ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('email')).'|'.$this->ip());
    }
}
