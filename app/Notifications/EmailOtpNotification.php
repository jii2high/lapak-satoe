<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EmailOtpNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly string $code) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $minutes = (int) config('lapak.otp.expires_minutes');

        return (new MailMessage)
            ->subject('Kode verifikasi email Lapak Satoe')
            ->greeting('Halo '.$notifiable->display_name.',')
            ->line('Kode verifikasi emailmu: '.$this->code)
            ->line("Kode ini berlaku {$minutes} menit dan hanya untukmu. Jangan bagikan ke siapa pun.");
    }
}
