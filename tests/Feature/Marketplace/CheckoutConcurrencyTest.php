<?php

namespace Tests\Feature\Marketplace;

use App\Models\Order;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Checkout di bawah rebutan stok (TRD 11). Satu proses PHP tidak bisa benar-benar
 * paralel, jadi tes meniru balapan: buyer berikutnya memesan setelah reservasi buyer
 * sebelumnya sudah tersimpan, persis seperti transaksi yang datang beruntun.
 */
class CheckoutConcurrencyTest extends TestCase
{
    use RefreshDatabase;

    private function store(): Store
    {
        return Store::factory()->open()->create();
    }

    private function buyerWithCart(Product $product, int $qty): User
    {
        $buyer = User::factory()->create();

        $buyer->cart()->create()->items()->create([
            'product_id' => $product->id,
            'qty' => $qty,
        ]);

        return $buyer;
    }

    public function test_only_one_buyer_gets_the_last_unit()
    {
        $store = $this->store();
        $product = Product::factory()->for($store)->create(['stock' => 1]);

        $winner = $this->buyerWithCart($product, 1);
        $runnersUp = collect([$this->buyerWithCart($product, 1), $this->buyerWithCart($product, 1)]);

        $this->actingAs($winner)
            ->post(route('checkout.store', $store))
            ->assertRedirect();

        self::assertSame(1, Order::query()->count());

        foreach ($runnersUp as $buyer) {
            $item = $buyer->cart()->first()->items()->sole();

            $this->actingAs($buyer)
                ->post(route('checkout.store', $store))
                ->assertSessionHasErrors(["items.{$item->id}" => 'Stok tersisa 0.']);
        }

        $product->refresh();

        self::assertSame(1, Order::query()->count());
        self::assertSame(1, $product->reserved);
        self::assertLessThanOrEqual($product->stock, $product->reserved);
    }

    public function test_two_buyers_can_still_take_the_last_two_units()
    {
        $store = $this->store();
        $product = Product::factory()->for($store)->create(['stock' => 2]);

        foreach ([$this->buyerWithCart($product, 1), $this->buyerWithCart($product, 1)] as $buyer) {
            $this->actingAs($buyer)
                ->post(route('checkout.store', $store))
                ->assertRedirect();
        }

        $product->refresh();

        self::assertSame(2, Order::query()->count());
        self::assertSame(2, $product->reserved);
        self::assertSame(2, $product->stock);
    }
}
