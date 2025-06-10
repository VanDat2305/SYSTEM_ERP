<?php

namespace Modules\Users\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail as BaseVerifyEmail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Notifications\Messages\MailMessage;

class VerifyEmailNotification extends BaseVerifyEmail
{
    public function toMail($notifiable)
    {
        // Tạo signed URL backend
        $signedURL = URL::temporarySignedRoute(
            'api.email.verify', // route name do ta định nghĩa
            now()->addMinutes(60),
            [
                'id' => $notifiable->getKey(),
                'hash' => sha1($notifiable->getEmailForVerification()),
            ]
        );

        // Parse URL để lấy các components một cách chính xác
        $parsedUrl = parse_url($signedURL);
        parse_str($parsedUrl['query'], $queryParams);
        
        // Lấy signature và expires từ query parameters (không phải từ string)
        $signature = $queryParams['signature'];
        $expires = $queryParams['expires'];

        // URL frontend xác minh (SPA)
        $frontendUrl = config('app.frontend_url');
        $verifyUrl = "{$frontendUrl}/verify/{$notifiable->getKey()}/" . sha1($notifiable->getEmailForVerification())
            . "?expires={$expires}&signature=" . urlencode($signature);

        return (new MailMessage)
            ->subject(trans('users::verify.subject'))
            ->line(trans('users::verify.line1'))
            ->action(trans('users::verify.action'), $verifyUrl)
            ->line(trans('users::verify.line2'));
    }
}