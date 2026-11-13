<?php

namespace Tests\Concerns;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Product;
use App\Models\Store;

trait MakesOrders
{
    /**
     * Order dengan satu item dari produk yang diberikan.
     *
     * @param  array<string, mixed>  $attributes
     */
    protected function orderFor(
        Store $store,
        Product $product,
        int $qty = 2,
        OrderStatus $status = OrderStatus::Unconfirmed,
        array $attributes = [],
    ): Order {
        $order = Order::factory()->for($store)->status($status)->create($attributes);

        $order->items()->create([
            'product_id' => $product->id,
            'product_name' => $product->name,
            'price' => $product->price,
            'qty' => $qty,
            'subtotal' => $product->price * $qty,
        ]);

        return $order;
    }
}
