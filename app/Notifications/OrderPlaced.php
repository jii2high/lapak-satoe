<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Notifications\Notification;

class OrderPlaced extends Notification
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
        $buyer = $this->order->buyer->display_name;

        return [
            'type' => 'order_new',
            'message' => "Pesanan baru {$this->order->code} dari {$buyer}.",
            'url' => '/seller/orders',
        ];
    }
}
