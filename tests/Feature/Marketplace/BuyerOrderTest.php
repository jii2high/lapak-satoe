<?php

namespace Tests\Feature\Marketplace;

use App\Enums\OrderStatus;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\MakesOrders;
use Tests\TestCase;

class BuyerOrderTest extends TestCase
{
    use MakesOrders, RefreshDatabase;

    private function store(): Store
    {
        return Store::factory()->open()->create();
    }

    public function test_guests_are_redirected_to_login()
    {
        $store = $this->store();
        $product = Product::factory()->for($store)->create();
        $order = $this->orderFor($store, $product);

        $this->get(route('orders.index'))->assertRedirect(route('login'));
        $this->post(route('orders.cancel', $order))->assertRedirect(route('login'));
        $this->post(route('orders.confirm', $order))->assertRedirect(route('login'));
    }

    public function test_the_page_splits_active_and_finished_orders()
    {
        $buyer = User::factory()->create();
        $store = $this->store();
        $product = Product::factory()->for($store)->create();
        $this->orderFor($store, $product, 1, OrderStatus::Unconfirmed, ['buyer_id' => $buyer->id]);
        $this->orderFor($store, $product, 1, OrderStatus::Completed, ['buyer_id' => $buyer->id]);

        $this->orderFor($store, $product, 1, OrderStatus::Unconfirmed, [
            'buyer_id' => User::factory()->create()->id,
        ]);

        $this->actingAs($buyer)
            ->get(route('orders.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('marketplace/orders')
                ->where('tab', 'aktif')
                ->has('orders.data', 1)
                ->where('orders.data.0.status', OrderStatus::Unconfirmed->value)
                ->where('orders.data.0.store.slug', $store->slug));

        $this->actingAs($buyer)
            ->get(route('orders.index', ['tab' => 'riwayat']))
            ->assertInertia(fn (Assert $page) => $page
                ->where('tab', 'riwayat')
                ->has('orders.data', 1)
                ->where('orders.data.0.status', OrderStatus::Completed->value));
    }

    public function test_a_buyer_can_cancel_an_unconfirmed_order()
    {
        $buyer = User::factory()->create();
        $store = $this->store();
        $product = Product::factory()->for($store)->create(['stock' => 10, 'reserved' => 2]);
        $order = $this->orderFor($store, $product, 2, OrderStatus::Unconfirmed, ['buyer_id' => $buyer->id]);

        $this->actingAs($buyer)
            ->post(route('orders.cancel', $order))
            ->assertRedirect();

        self::assertSame(OrderStatus::Cancelled, $order->refresh()->status);
        self::assertSame('buyer', $order->cancelled_by);
        self::assertSame(0, $product->refresh()->reserved);
    }

    public function test_a_pending_order_can_not_be_cancelled_by_the_buyer()
    {
        $buyer = User::factory()->create();
        $store = $this->store();
        $product = Product::factory()->for($store)->create(['stock' => 8]);
        $order = $this->orderFor($store, $product, 2, OrderStatus::Pending, ['buyer_id' => $buyer->id]);

        $this->actingAs($buyer)
            ->post(route('orders.cancel', $order))
            ->assertForbidden();

        self::assertSame(OrderStatus::Pending, $order->refresh()->status);
    }

    public function test_a_buyer_confirms_the_finished_order()
    {
        $buyer = User::factory()->create();
        $store = $this->store();
        $product = Product::factory()->for($store)->create(['stock' => 8, 'sold_count' => 0]);
        $order = $this->orderFor($store, $product, 2, OrderStatus::AwaitingBuyer, [
            'buyer_id' => $buyer->id,
            'auto_confirm_at' => now()->addHours(24),
        ]);

        $this->actingAs($buyer)
            ->post(route('orders.confirm', $order))
            ->assertRedirect();

        self::assertSame(OrderStatus::Completed, $order->refresh()->status);
        self::assertSame(2, $product->refresh()->sold_count);
    }

    public function test_an_order_of_another_buyer_is_not_reachable()
    {
        $buyer = User::factory()->create();
        $store = $this->store();
        $product = Product::factory()->for($store)->create(['stock' => 10, 'reserved' => 2]);
        $order = $this->orderFor($store, $product, 2, OrderStatus::Unconfirmed);

        $this->actingAs($buyer)
            ->post(route('orders.cancel', $order))
            ->assertForbidden();

        $this->actingAs($buyer)
            ->post(route('orders.confirm', $order))
            ->assertForbidden();

        self::assertSame(OrderStatus::Unconfirmed, $order->refresh()->status);
    }
}
