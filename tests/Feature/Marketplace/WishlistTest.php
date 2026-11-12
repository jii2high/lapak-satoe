<?php

namespace Tests\Feature\Marketplace;

use App\Enums\Role;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class WishlistTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login()
    {
        $this->get(route('wishlist.index'))->assertRedirect(route('login'));

        $product = Product::factory()->create();
        $this->post(route('wishlist.products.toggle', $product))->assertRedirect(route('login'));
    }

    public function test_products_can_be_toggled()
    {
        $user = User::factory()->create();
        $store = Store::factory()->open()->create();
        $product = Product::factory()->create(['store_id' => $store->id]);

        $this->actingAs($user)
            ->post(route('wishlist.products.toggle', $product))
            ->assertRedirect();

        $this->assertDatabaseHas('wishlists', [
            'user_id' => $user->id,
            'product_id' => $product->id,
        ]);

        $this->actingAs($user)
            ->post(route('wishlist.products.toggle', $product))
            ->assertRedirect();

        $this->assertDatabaseMissing('wishlists', [
            'user_id' => $user->id,
            'product_id' => $product->id,
        ]);
    }

    public function test_stores_can_be_toggled()
    {
        $user = User::factory()->create();
        $store = Store::factory()->open()->create();

        $this->actingAs($user)
            ->post(route('wishlist.stores.toggle', $store))
            ->assertRedirect();

        $this->assertDatabaseHas('wishlists', [
            'user_id' => $user->id,
            'store_id' => $store->id,
        ]);
    }

    public function test_hidden_targets_can_not_be_wishlisted()
    {
        $user = User::factory()->create();
        $inactive = Product::factory()->inactive()->create();
        $closed = Store::factory()->create();

        $this->actingAs($user)
            ->post(route('wishlist.products.toggle', $inactive))
            ->assertNotFound();

        $this->actingAs($user)
            ->post(route('wishlist.stores.toggle', $closed))
            ->assertNotFound();
    }

    public function test_wishlist_page_lists_saved_products_and_stores()
    {
        $user = User::factory()->withRole(Role::Buyer, active: true)->create();
        $store = Store::factory()->open()->create();
        $product = Product::factory()->create(['store_id' => $store->id]);

        $user->wishlists()->create(['product_id' => $product->id]);
        $user->wishlists()->create(['store_id' => $store->id]);

        $this->actingAs($user)
            ->get(route('wishlist.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('marketplace/wishlist')
                ->has('products', 1)
                ->where('products.0.id', $product->id)
                ->has('stores', 1)
                ->where('stores.0.id', $store->id));
    }
}
