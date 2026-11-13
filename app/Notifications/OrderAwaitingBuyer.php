<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Notifications\Notification;

class OrderAwaitingBuyer extends Notification
{
    public function __construct(private readonly Order $order) {}

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
        return [
            'type' => 'order_awaiting_buyer',
            'order_id' => $this->order->id,
            'message' => "Pesanan {$this->order->code} ditandai selesai penjual. Konfirmasi kalau barangnya sudah diterima.",
            'url' => '/pesanan-saya',
        ];
    }
}
