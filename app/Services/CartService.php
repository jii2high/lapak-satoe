<?php

namespace App\Services;

use App\Enums\StoreStatus;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class CartService
{
    public function forUser(User $user): Cart
    {
        return $user->cart()->firstOrCreate([]);
    }

    public function count(User $user): int
    {
        $cart = $user->cart()->first();

        return $cart === null ? 0 : (int) $cart->items()->sum('qty');
    }

    /**
     * Item dikelompokkan per lapak. Tiap item membawa masalahnya sendiri supaya
     * halaman keranjang bisa menampilkan teks error di bawah item (DESIGN 8.5).
     *
     * @return array<int, array<string, mixed>>
     */
    public function groups(Cart $cart): array
    {
        $items = $cart->items()
            ->with(['product.store', 'product.images'])
            ->get()
            ->filter(function (CartItem $item): bool {
                if ($item->product !== null) {
                    return true;
                }

                $item->delete();

                return false;
            });

        return $items
            ->groupBy(fn (CartItem $item) => $item->product->store_id)
            ->map(fn (Collection $group) => $this->group($group))
            ->values()
            ->all();
    }

    /**
     * @param  array<int, array{product_id: int|string, qty: int|string}>  $rows
     */
    public function merge(User $user, array $rows): void
    {
        $cart = $this->forUser($user);
        $ids = array_map(fn (array $row) => (int) $row['product_id'], $rows);
        $products = Product::query()
            ->publiclyVisible()
            ->whereIn('id', $ids)
            ->get()
            ->keyBy('id');

        DB::transaction(function () use ($cart, $rows, $products): void {
            foreach ($rows as $row) {
                $product = $products->get((int) $row['product_id']);

                if ($product === null) {
                    continue;
                }

                $available = max(0, $product->stock - $product->reserved);
                $existing = $cart->items()->where('product_id', $product->id)->first();
                $qty = min((int) $row['qty'], $available - ($existing->qty ?? 0));

                if ($qty < 1) {
                    continue;
                }

                if ($existing === null) {
                    $cart->items()->create(['product_id' => $product->id, 'qty' => $qty]);

                    continue;
                }

                $existing->update(['qty' => $existing->qty + $qty]);
            }
        });
    }

    /**
     * @throws ValidationException
     */
    public function setQty(CartItem $item, int $qty): void
    {
        $available = $this->available($item->product);

        if ($qty > $available) {
            throw ValidationException::withMessages([
                "items.{$item->id}" => "Stok tersisa {$available}.",
            ]);
        }

        $item->update(['qty' => $qty]);
    }

    public function remove(CartItem $item): void
    {
        $item->delete();
    }

    /**
     * @param  Collection<int, CartItem>  $group
     * @return array<string, mixed>
     */
    private function group(Collection $group): array
    {
        $store = $group->first()->product->store;
        $items = $group
            ->map(fn (CartItem $item) => $this->item($item, $store->status, $store->is_suspended))
            ->values();

        return [
            'store' => [
                'id' => $store->id,
                'name' => $store->name,
                'slug' => $store->slug,
                'status' => $store->status->value,
                'is_suspended' => $store->is_suspended,
            ],
            'subtotal' => $items->sum('subtotal'),
            'problem' => $items->pluck('problem')->filter()->first(),
            'items' => $items->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function item(CartItem $item, StoreStatus $storeStatus, bool $storeSuspended): array
    {
        $product = $item->product;
        $available = $this->available($product);
        $image = $product->images->first();

        return [
            'id' => $item->id,
            'product_id' => $product->id,
            'name' => $product->name,
            'price' => $product->price,
            'qty' => $item->qty,
            'subtotal' => $product->price * $item->qty,
            'available' => $available,
            'image_url' => $image === null ? null : Storage::disk('public')->url($image->path),
            'problem' => match (true) {
                $storeSuspended, $product->is_suspended => 'Produk dinonaktifkan admin.',
                ! $product->is_active => 'Produk sedang tidak dijual.',
                $storeStatus !== StoreStatus::Open => 'Lapak sedang tutup.',
                $item->qty > $available => "Stok tersisa {$available}.",
                default => null,
            },
        ];
    }

    private function available(?Product $product): int
    {
        return $product === null ? 0 : max(0, $product->stock - $product->reserved);
    }
}
