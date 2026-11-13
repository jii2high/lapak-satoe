<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Product;
use App\Notifications\OrderAwaitingBuyer;
use App\Notifications\OrderCancelled;
use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * State machine order (TRD 6.2). Semua perpindahan status lewat sini.
 */
class OrderService
{
    public function __construct(private readonly StoreService $stores) {}

    /**
     * BR-04
     *
     * @throws ValidationException
     */
    public function accept(Order $order): void
    {
        $this->transition($order, [OrderStatus::Unconfirmed], function (Order $locked): void {
            $this->applyStock($locked, stock: -1, reserved: -1);

            $locked->update([
                'status' => OrderStatus::Pending,
                'confirmed_at' => now(),
                'expires_at' => now()->addHours((int) config('lapak.order.expire_after_hours')),
                'remind_at' => now()->addHours((int) config('lapak.order.remind_after_hours')),
                'reminded_at' => null,
            ]);
        });
    }

    /**
     * BR-05
     *
     * @throws ValidationException
     */
    public function reject(Order $order): void
    {
        $this->transition($order, [OrderStatus::Unconfirmed], function (Order $locked): void {
            $this->applyStock($locked, reserved: -1);

            $locked->update(['status' => OrderStatus::Rejected]);
        });

        $order->buyer?->notify(new OrderCancelled($order, 'rejected', forSeller: false));
    }

    /**
     * BR-10
     *
     * @throws ValidationException
     */
    public function cancelByBuyer(Order $order): void
    {
        $this->transition($order, [OrderStatus::Unconfirmed], function (Order $locked): void {
            $this->applyStock($locked, reserved: -1);

            $locked->update([
                'status' => OrderStatus::Cancelled,
                'cancelled_by' => 'buyer',
            ]);
        });

        $order->store->user?->notify(new OrderCancelled($order, 'buyer', forSeller: true));
    }

    /**
     * BR-06
     *
     * @throws ValidationException
     */
    public function cancelBySeller(Order $order, string $note): void
    {
        $this->transition($order, [OrderStatus::Pending], function (Order $locked) use ($note): void {
            $this->applyStock($locked, stock: 1);

            $locked->update([
                'status' => OrderStatus::Cancelled,
                'cancelled_by' => 'seller',
                'cancel_note' => $note,
            ]);
        });

        $order->buyer?->notify(new OrderCancelled($order, 'seller', forSeller: false));
    }

    /**
     * BR-08, sisi seller.
     *
     * @throws ValidationException
     */
    public function complete(Order $order): void
    {
        $this->transition($order, [OrderStatus::Pending], function (Order $locked): void {
            $locked->update([
                'status' => OrderStatus::AwaitingBuyer,
                'seller_done_at' => now(),
                'auto_confirm_at' => now()->addHours((int) config('lapak.order.auto_confirm_after_hours')),
            ]);
        });

        $order->buyer?->notify(new OrderAwaitingBuyer($order));
    }

    /**
     * BR-07, BR-22. Aktor sistem, dipakai `orders:expire`.
     *
     * @throws ValidationException
     */
    public function expire(Order $order): void
    {
        $this->transition($order, [OrderStatus::Unconfirmed, OrderStatus::Pending], function (Order $locked): void {
            $unconfirmed = $locked->status === OrderStatus::Unconfirmed;

            $this->applyStock(
                $locked,
                stock: $unconfirmed ? 0 : 1,
                reserved: $unconfirmed ? -1 : 0,
            );

            $locked->update([
                'status' => OrderStatus::Cancelled,
                'cancelled_by' => 'system',
            ]);
        });

        $order->buyer?->notify(new OrderCancelled($order, 'expired', forSeller: false));
        $order->store->user?->notify(new OrderCancelled($order, 'expired', forSeller: true));
    }

    /**
     * BR-08, sisi buyer. M6 memakai jalur ini juga untuk auto-confirm.
     *
     * @throws ValidationException
     */
    public function confirm(Order $order): void
    {
        $this->transition($order, [OrderStatus::AwaitingBuyer], function (Order $locked): void {
            $this->applyStock($locked, sold: 1);

            $locked->update([
                'status' => OrderStatus::Completed,
                'completed_at' => now(),
                'auto_confirm_at' => null,
            ]);
        });
    }

    /**
     * @param  array<int, OrderStatus>  $allowed
     */
    private function transition(Order $order, array $allowed, Closure $mutate): void
    {
        DB::transaction(function () use ($order, $allowed, $mutate): void {
            $locked = Order::query()
                ->whereKey($order->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (! in_array($locked->status, $allowed, true)) {
                throw ValidationException::withMessages([
                    'status' => 'Pesanan ini sudah berpindah status. Muat ulang halaman.',
                ]);
            }

            $mutate($locked);

            $this->stores->settle($locked->store);
        });
    }

    /**
     * Delta per unit item. Baris produk dikunci urut id supaya tidak deadlock.
     */
    private function applyStock(Order $order, int $stock = 0, int $reserved = 0, int $sold = 0): void
    {
        $items = $order->items()->get();
        $products = Product::query()
            ->whereIn('id', $items->pluck('product_id')->all())
            ->orderBy('id')
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        foreach ($items as $item) {
            $product = $products->get($item->product_id);

            if ($product === null) {
                continue;
            }

            if ($stock !== 0) {
                $product->increment('stock', $stock * $item->qty);
            }

            if ($reserved !== 0) {
                $product->increment('reserved', $reserved * $item->qty);
            }

            if ($sold !== 0) {
                $product->increment('sold_count', $sold * $item->qty);
            }
        }
    }
}
