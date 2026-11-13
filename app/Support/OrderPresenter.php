<?php

namespace App\Support;

use App\Models\Order;

class OrderPresenter
{
    /**
     * Baris daftar pesanan plus isi nota untuk dialog detail (DESIGN 7.8).
     *
     * @return array<string, mixed>
     */
    public static function row(Order $order): array
    {
        return [
            'id' => $order->id,
            'code' => $order->code,
            'status' => $order->status->value,
            'total' => $order->total,
            'created_at' => $order->created_at?->toIso8601String(),
            'expires_at' => $order->expires_at->toIso8601String(),
            'cancel_note' => $order->cancel_note,
            'store' => [
                'name' => $order->store->name,
                'slug' => $order->store->slug,
            ],
            'buyer' => [
                'username' => $order->buyer->username,
            ],
            'items' => $order->items
                ->map(fn ($item) => [
                    'product_id' => $item->product_id,
                    'name' => $item->product_name,
                    'price' => $item->price,
                    'qty' => $item->qty,
                    'subtotal' => $item->subtotal,
                ])
                ->values()
                ->all(),
        ];
    }
}
