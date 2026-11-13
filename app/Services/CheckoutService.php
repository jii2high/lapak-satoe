<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\StoreStatus;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CheckoutService
{
    public function __construct(private readonly WhatsAppService $whatsApp) {}

    /**
     * Satu order per lapak (BR-02).
     *
     * @throws ValidationException
     */
    public function checkout(User $buyer, Store $store): string
    {
        $this->assertStoreAcceptsOrders($buyer, $store);

        $cart = $buyer->cart()->first();
        $items = $cart === null
            ? collect()
            : $cart->items()
                ->whereHas('product', fn ($query) => $query->where('store_id', $store->id))
                ->get();

        if ($items->isEmpty()) {
            throw ValidationException::withMessages(['cart' => 'Keranjang lapak ini kosong.']);
        }

        try {
            $order = DB::transaction(fn () => $this->createOrder($buyer, $store, $cart, $items));
        } catch (QueryException $exception) {
            if (! str_contains($exception->getMessage(), 'chk_products_reserved')) {
                throw $exception;
            }

            throw ValidationException::withMessages(['cart' => 'Stok berubah saat memesan. Coba lagi.']);
        }

        $order->load('items', 'buyer');

        return $this->whatsApp->orderUrl($store, $order);
    }

    /**
     * @param  Collection<int, CartItem>  $items
     */
    private function createOrder(User $buyer, Store $store, Cart $cart, Collection $items): Order
    {
        $products = Product::query()
            ->whereIn('id', $items->pluck('product_id')->all())
            ->orderBy('id')
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        $errors = [];
        $lines = [];

        foreach ($items as $item) {
            $product = $products->get($item->product_id);
            $available = $product === null ? 0 : max(0, $product->stock - $product->reserved);

            if ($product === null || ! $product->is_active || $product->is_suspended) {
                $errors["items.{$item->id}"] = 'Produk tidak tersedia lagi.';

                continue;
            }

            if ($item->qty > $available) {
                $errors["items.{$item->id}"] = "Stok tersisa {$available}.";

                continue;
            }

            $lines[] = [
                'product' => $product,
                'name' => $product->name,
                'price' => $product->price,
                'qty' => $item->qty,
            ];
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        $order = $store->orders()->create([
            'code' => $this->code(),
            'buyer_id' => $buyer->id,
            'status' => OrderStatus::Unconfirmed,
            'total' => collect($lines)->sum(fn (array $line) => $line['price'] * $line['qty']),
            'expires_at' => now()->addHours((int) config('lapak.order.expire_after_hours')),
            'remind_at' => now()->addHours((int) config('lapak.order.remind_after_hours')),
        ]);

        foreach ($lines as $line) {
            $subtotal = $line['price'] * $line['qty'];

            $order->items()->create([
                'product_id' => $line['product']->id,
                'product_name' => $line['name'],
                'price' => $line['price'],
                'qty' => $line['qty'],
                'subtotal' => $subtotal,
            ]);

            $line['product']->increment('reserved', $line['qty']);
        }

        $cart->items()->whereIn('id', $items->pluck('id')->all())->delete();

        return $order;
    }

    /**
     * @throws ValidationException
     */
    private function assertStoreAcceptsOrders(User $buyer, Store $store): void
    {
        if ($store->status !== StoreStatus::Open || $store->is_suspended) {
            throw ValidationException::withMessages(['cart' => 'Lapak belum menerima pesanan.']);
        }

        if ($store->user_id === $buyer->id) {
            throw ValidationException::withMessages(['cart' => 'Kamu tidak bisa memesan dari lapakmu sendiri.']);
        }
    }

    private function code(): string
    {
        do {
            $code = 'LS-'.Str::upper(Str::random(8));
        } while (Order::query()->where('code', $code)->exists());

        return $code;
    }
}
