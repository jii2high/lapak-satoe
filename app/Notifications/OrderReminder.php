<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Notifications\Notification;

class OrderReminder extends Notification
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
            'type' => 'order_reminder',
            'message' => "Pesanan {$this->order->code} belum ditindak. Terima atau tolak sebelum dibatalkan otomatis.",
            'url' => '/seller/orders',
        ];
    }
}
