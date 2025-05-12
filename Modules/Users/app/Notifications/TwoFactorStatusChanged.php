<?php

namespace Modules\Users\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class TwoFactorStatusChanged extends Notification implements ShouldQueue
{
    use Queueable;

    protected $enabled;
    protected $ip;
    protected $time;

    public function __construct($enabled, $ip, $time)
    {
        $this->enabled = $enabled;
        $this->ip = $ip;
        $this->time = $time;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        $status = $this->enabled ? __("2fa.status_changed.ENABLED") :  __("2fa.status_changed.DISABLED");
        
        return (new MailMessage)
            ->subject(__("2fa.status_changed.subject")) //'Two-Factor Authentication Status Changed'
            ->line(__("2fa.status_changed.line1", ['status' => $status])) //'Your two-factor authentication has been '.$status
            ->line(__("2fa.status_changed.line2", ['time' => $this->time])) //'at '.$this->time
            ->line(__("2fa.status_changed.line3", ['ip' => $this->ip])) //'from IP address '.$this->ip
            ->line(__("2fa.status_changed.line4")); //'If you did not make this change, please contact support immediately.';
    }
}