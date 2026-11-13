<?php

namespace Tests\Feature\Marketplace;

use App\Enums\OrderStatus;
use App\Enums\Role;
use App\Models\Order;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    use RefreshDatabase;

    private function store(array $attributes = []): Store
    {
        return Store::factory()->open()->create($attributes);
    }

    private function product(Store $store, array $attributes = []): Product
    {
        return Product::factory()->for($store)->create($attributes);
    }

    private function addToCart(User $buyer, Product $product, int $qty): void
    {
        $buyer->cart()->firstOrCreate([])
            ->items()
            ->create(['product_id' => $product->id, 'qty' => $qty]);
    }

    public function test_guests_are_redirected_to_login()
    {
        $store = $this->store();

        $this->post(route('checkout.store', $store))->assertRedirect(route('login'));
    }

    public function test_checkout_creates_an_order_and_reserves_stock()
    {
        $buyer = User::factory()->create();
        $store = $this->store();
        $product = $this->product($store, ['price' => 5000, 'stock' => 10]);
        $this->addToCart($buyer, $product, 3);

        $response = $this->actingAs($buyer)->post(route('checkout.store', $store));

        $response->assertRedirect();

        $order = Order::query()->sole();

        self::assertSame(OrderStatus::Unconfirmed, $order->status);
        self::assertSame(15000, $order->total);
        self::assertSame($buyer->id, $order->buyer_id);
        self::assertSame(3, $product->refresh()->reserved);
        self::assertSame(10, $product->stock);
        self::assertCount(1, $order->items);
        self::assertSame(3, $order->items->first()->qty);
        self::assertSame(0, $buyer->cart()->first()->items()->count());
        self::assertTrue($order->expires_at->between(now()->addHours(23), now()->addHours(25)));
        self::assertTrue($order->remind_at->between(now()->addHours(11), now()->addHours(13)));
        self::assertStringContainsString(
            $order->code,
            urldecode((string) $response->headers->get('Location')),
        );
    }

    public function test_whatsapp_link_uses_the_normalized_store_number()
    {
        $buyer = User::factory()->create();
        $store = $this->store(['whatsapp' => '081234567890']);
        $product = $this->product($store);
        $this->addToCart($buyer, $product, 1);

        $response = $this->actingAs($buyer)->post(route('checkout.store', $store));

        self::assertStringStartsWith(
            'https://wa.me/6281234567890?text=',
            (string) $response->headers->get('Location'),
        );
    }

    public function test_a_closed_store_can_not_be_checked_out()
    {
        $buyer = User::factory()->create();
        $store = Store::factory()->create();
        $product = $this->product($store);
        $this->addToCart($buyer, $product, 1);

        $this->actingAs($buyer)
            ->post(route('checkout.store', $store))
            ->assertSessionHasErrors('cart');

        self::assertSame(0, Order::query()->count());
        self::assertSame(0, $product->refresh()->reserved);
    }

    public function test_a_suspended_store_can_not_be_checked_out()
    {
        $buyer = User::factory()->create();
        $store = Store::factory()->open()->suspended()->create();
        $product = $this->product($store);
        $this->addToCart($buyer, $product, 1);

        $this->actingAs($buyer)
            ->post(route('checkout.store', $store))
            ->assertSessionHasErrors('cart');

        self::assertSame(0, Order::query()->count());
    }

    public function test_a_seller_can_not_checkout_their_own_store()
    {
        $seller = User::factory()->withRole(Role::Seller, active: true)->create();
        $store = Store::factory()->open()->for($seller)->create();
        $product = $this->product($store);
        $this->addToCart($seller, $product, 1);

        $this->actingAs($seller)
            ->post(route('checkout.store', $store))
            ->assertSessionHasErrors('cart');

        self::assertSame(0, Order::query()->count());
    }

    public function test_an_empty_cart_can_not_be_checked_out()
    {
        $buyer = User::factory()->create();
        $store = $this->store();
        $this->product($store);

        $this->actingAs($buyer)
            ->post(route('checkout.store', $store))
            ->assertSessionHasErrors('cart');

        self::assertSame(0, Order::query()->count());
    }

    public function test_a_quantity_above_the_available_stock_is_rejected()
    {
        $buyer = User::factory()->create();
        $store = $this->store();
        $product = $this->product($store, ['stock' => 5, 'reserved' => 3]);
        $this->addToCart($buyer, $product, 3);

        $item = $buyer->cart()->first()->items()->sole();

        $this->actingAs($buyer)
            ->post(route('checkout.store', $store))
            ->assertSessionHasErrors("items.{$item->id}");

        self::assertSame(0, Order::query()->count());
        self::assertSame(1, $buyer->cart()->first()->items()->count());
    }

    public function test_an_inactive_product_is_rejected()
    {
        $buyer = User::factory()->create();
        $store = $this->store();
        $product = $this->product($store, ['is_active' => false]);
        $this->addToCart($buyer, $product, 1);

        $item = $buyer->cart()->first()->items()->sole();

        $this->actingAs($buyer)
            ->post(route('checkout.store', $store))
            ->assertSessionHasErrors("items.{$item->id}");

        self::assertSame(0, Order::query()->count());
    }

    public function test_only_the_cart_of_that_store_is_checked_out()
    {
        $buyer = User::factory()->create();
        $store = $this->store();
        $other = $this->store();
        $product = $this->product($store);
        $otherProduct = $this->product($other);
        $this->addToCart($buyer, $product, 1);
        $this->addToCart($buyer, $otherProduct, 1);

        $this->actingAs($buyer)->post(route('checkout.store', $store))->assertRedirect();

        self::assertSame(1, $buyer->cart()->first()->items()->count());
        self::assertSame($otherProduct->id, $buyer->cart()->first()->items()->sole()->product_id);
    }

    public function test_checkout_is_rate_limited_per_user()
    {
        $buyer = User::factory()->create();
        $store = $this->store();

        for ($attempt = 0; $attempt < 10; $attempt++) {
            $this->actingAs($buyer)->post(route('checkout.store', $store));
        }

        $this->actingAs($buyer)
            ->post(route('checkout.store', $store))
            ->assertStatus(429);
    }
}
