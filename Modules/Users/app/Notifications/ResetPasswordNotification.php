<?php

namespace Modules\Users\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class ResetPasswordNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $token;
    
    public function __construct($token)
    {
        $this->token = $token;
    }
    
    public function via($notifiable)
    {
        return ['mail'];
    }
    
    public function toMail($notifiable)
    {
        $url = config('app.frontend_url') . '/reset-password?token=' . $this->token . '&email=' . urlencode($notifiable->getEmailForPasswordReset());
        return (new MailMessage)
            ->subject(__('passwords.reset_subject'))
            ->line(__('passwords.reset_line_1'))
            ->action(__('passwords.reset_action'), $url)
            ->line(__('passwords.reset_line_2'))
            ->line(__('passwords.reset_line_3'));
    }
}