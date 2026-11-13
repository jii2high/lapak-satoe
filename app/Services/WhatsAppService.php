<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Store;

class WhatsAppService
{
    public function normalize(string $number): string
    {
        $digits = preg_replace('/\D/', '', $number) ?? '';

        if (str_starts_with($digits, '62')) {
            return $digits;
        }

        return '62'.ltrim($digits, '0');
    }

    /**
     * Pesan memakai snapshot order_items, bukan harga produk saat ini.
     */
    public function orderUrl(Store $store, Order $order): string
    {
        $lines = [
            "Halo {$store->name}, saya {$order->buyer->username} ingin memesan.",
            "Order: {$order->code}",
        ];

        foreach ($order->items->values() as $index => $item) {
            $lines[] = ($index + 1).". {$item->product_name} x{$item->qty} - Rp".number_format($item->subtotal, 0, ',', '.');
        }

        $lines[] = 'Total: Rp'.number_format($order->total, 0, ',', '.');

        return 'https://wa.me/'.$this->normalize($store->whatsapp).'?text='.urlencode(implode("\n", $lines));
    }
}
