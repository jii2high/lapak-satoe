<?php

namespace Tests\Feature\Marketplace;

use App\Models\CartItem;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CartTest extends TestCase
{
    use RefreshDatabase;

    private function cartItem(User $buyer, Product $product, int $qty): CartItem
    {
        return $buyer->cart()->firstOrCreate([])
            ->items()
            ->create(['product_id' => $product->id, 'qty' => $qty]);
    }

    public function test_guests_are_redirected_to_login()
    {
        $this->get(route('cart.index'))->assertRedirect(route('login'));
        $this->post(route('cart.merge'))->assertRedirect(route('login'));
        $this->patch(route('cart.items.update', 1))->assertRedirect(route('login'));
        $this->delete(route('cart.items.destroy', 1))->assertRedirect(route('login'));
    }

    public function test_items_are_grouped_per_store_with_subtotals_and_problems()
    {
        $buyer = User::factory()->create();
        $open = Store::factory()->open()->create();
        $closed = Store::factory()->create();
        $cheap = Product::factory()->for($open)->create(['price' => 4000, 'stock' => 10]);
        $scarce = Product::factory()->for($open)->create(['price' => 1000, 'stock' => 2]);
        $hidden = Product::factory()->for($closed)->create(['price' => 9000]);

        $this->cartItem($buyer, $cheap, 2);
        $this->cartItem($buyer, $scarce, 5);
        $this->cartItem($buyer, $hidden, 1);

        $this->actingAs($buyer)
            ->get(route('cart.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('marketplace/cart')
                ->has('groups', 2)
                ->has('groups.0.items', 2)
                ->where('groups.0.store.slug', $open->slug)
                ->where('groups.0.subtotal', 13000)
                ->where('groups.0.problem', 'Stok tersisa 2.')
                ->where('groups.0.items.1.available', 2)
                ->where('groups.0.items.1.problem', 'Stok tersisa 2.')
                ->where('groups.0.items.0.problem', null)
                ->where('groups.1.store.slug', $closed->slug)
                ->where('groups.1.problem', 'Lapak sedang tutup.')
                ->where('groups.1.items.0.problem', 'Lapak sedang tutup.'));
    }

    public function test_items_with_a_removed_product_are_dropped_from_the_cart()
    {
        $buyer = User::factory()->create();
        $product = Product::factory()->create();
        $item = $this->cartItem($buyer, $product, 1);

        $product->delete();

        $this->actingAs($buyer)
            ->get(route('cart.index'))
            ->assertInertia(fn (Assert $page) => $page->has('groups', 0));

        self::assertNull(CartItem::query()->find($item->id));
    }

    public function test_merge_sums_quantities_and_clamps_to_available_stock()
    {
        $buyer = User::factory()->create();
        $store = Store::factory()->open()->create();
        $product = Product::factory()->for($store)->create(['stock' => 3]);
        $blocked = Product::factory()->inactive()->create();
        $this->cartItem($buyer, $product, 1);

        $this->actingAs($buyer)
            ->post(route('cart.merge'), [
                'items' => [
                    ['product_id' => $product->id, 'qty' => 5],
                    ['product_id' => $blocked->id, 'qty' => 2],
                ],
            ])
            ->assertRedirect();

        self::assertSame(3, $buyer->cart()->first()->items()->sole()->qty);
    }

    public function test_quantity_update_is_rejected_above_the_available_stock()
    {
        $buyer = User::factory()->create();
        $store = Store::factory()->open()->create();
        $product = Product::factory()->for($store)->create(['stock' => 4, 'reserved' => 2]);
        $item = $this->cartItem($buyer, $product, 1);

        $this->actingAs($buyer)
            ->patch(route('cart.items.update', $item), ['qty' => 3])
            ->assertSessionHasErrors("items.{$item->id}");

        self::assertSame(1, $item->refresh()->qty);
    }

    public function test_quantity_can_be_changed_and_the_item_removed()
    {
        $buyer = User::factory()->create();
        $store = Store::factory()->open()->create();
        $product = Product::factory()->for($store)->create(['stock' => 10]);
        $item = $this->cartItem($buyer, $product, 1);

        $this->actingAs($buyer)
            ->patch(route('cart.items.update', $item), ['qty' => 4])
            ->assertRedirect();

        self::assertSame(4, $item->refresh()->qty);

        $this->actingAs($buyer)
            ->delete(route('cart.items.destroy', $item))
            ->assertRedirect();

        self::assertNull(CartItem::query()->find($item->id));
    }

    public function test_items_of_another_cart_are_not_reachable()
    {
        $buyer = User::factory()->create();
        $other = User::factory()->create();
        $product = Product::factory()->create();
        $item = $this->cartItem($other, $product, 1);

        $this->actingAs($buyer)
            ->patch(route('cart.items.update', $item), ['qty' => 2])
            ->assertNotFound();

        $this->actingAs($buyer)
            ->delete(route('cart.items.destroy', $item))
            ->assertNotFound();
    }
}
