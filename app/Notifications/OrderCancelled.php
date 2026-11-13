<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Notifications\Notification;

class OrderCancelled extends Notification
{
    /**
     * @param  'rejected'|'expired'|'buyer'|'seller'  $reason
     */
    public function __construct(
        private readonly Order $order,
        private readonly string $reason,
        private readonly bool $forSeller,
    ) {}

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
        $code = $this->order->code;

        return [
            'type' => 'order_cancelled',
            'reason' => $this->reason,
            'message' => match ($this->reason) {
                'rejected' => "Pesanan {$code} ditolak penjual.",
                'expired' => "Pesanan {$code} dibatalkan otomatis karena lewat batas waktu.",
                'buyer' => "Pesanan {$code} dibatalkan pembeli.",
                default => "Pesanan {$code} dibatalkan penjual.",
            },
            'url' => $this->forSeller ? '/seller/orders' : '/pesanan-saya',
        ];
    }
}
