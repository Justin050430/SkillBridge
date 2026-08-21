<?php

/**
 * @author OOI JIAN XIN
 *
 * SkillBridge-branded password reset email.
 *
 * This extends Laravel's built-in ResetPassword notification so the secure
 * token mechanism, broker validation and one-time-use behaviour are all
 * inherited unchanged. Only the email presentation is customised.
 */

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword as BaseResetPassword;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Lang;

class ResetPassword extends BaseResetPassword
{
    /**
     * Build the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        if (static::$toMailCallback) {
            return call_user_func(static::$toMailCallback, $notifiable, $this->token);
        }

        // Built exactly like the framework default: url(route('password.reset'))
        // -> APP_URL-based, so on Azure it points to the SkillBridge site.
        $url = $this->resetUrl($notifiable);

        $expire = config('auth.passwords.'.config('auth.defaults.passwords').'.expire');

        return (new MailMessage)
            ->subject(Lang::get('Reset Your SkillBridge Password'))
            ->greeting(Lang::get('Hello!'))
            ->line(Lang::get('You are receiving this email because we received a password reset request for your SkillBridge account.'))
            ->line(Lang::get('Click the button below to choose a new password.'))
            ->action(Lang::get('Reset Password'), $url)
            ->line(Lang::get('This password reset link will expire in :count minutes.', ['count' => $expire]))
            ->line(Lang::get('If you did not request a password reset, no further action is required.'))
            ->line(Lang::get('SkillBridge — Quality education for everyone.'));
    }
}
