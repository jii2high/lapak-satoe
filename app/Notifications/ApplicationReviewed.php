<?php

namespace App\Notifications;

use App\Enums\ApplicationStatus;
use App\Models\SellerApplication;
use Illuminate\Notifications\Notification;

class ApplicationReviewed extends Notification
{
    public function __construct(private readonly SellerApplication $application) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $approved = $this->application->status === ApplicationStatus::Approved;

        return [
            'type' => 'application_result',
            'status' => $this->application->status->value,
            'message' => $approved
                ? "Pendaftaran lapak {$this->application->store_name} diterima. Lapakmu masih tertutup, buka kapan saja."
                : "Pendaftaran lapak {$this->application->store_name} ditolak.",
            'reject_note' => $this->application->reject_note,
            'url' => $approved ? '/seller/dashboard' : '/daftar-seller',
        ];
    }
}
