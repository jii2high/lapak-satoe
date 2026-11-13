<?php

namespace Tests\Feature\Seller;

use App\Enums\OrderStatus;
use App\Enums\Role;
use App\Enums\StoreStatus;
use App\Models\Order;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use App\Services\StoreService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StoreStatusTest extends TestCase
{
    use RefreshDatabase;

    private function seller(): User
    {
        return User::factory()->withRole(Role::Seller, active: true)->create();
    }

    private function storeFor(User $seller): Store
    {
        return Store::factory()->for($seller)->create();
    }

    private function order(Store $store, OrderStatus $status): Order
    {
        return Order::create([
            'code' => 'PESAN-'.fake()->unique()->numerify('######'),
            'buyer_id' => User::factory()->create()->id,
            'store_id' => $store->id,
            'status' => $status,
            'total' => 10000,
            'expires_at' => now()->addDay(),
        ]);
    }

    public function test_guests_are_redirected_to_login()
    {
        $this->post(route('seller.store.toggle'))->assertRedirect(route('login'));
    }

    public function test_store_can_not_open_without_an_active_product()
    {
        $seller = $this->seller();
        $store = $this->storeFor($seller);
        Product::factory()->for($store)->inactive()->create();

        $this->actingAs($seller)
            ->post(route('seller.store.toggle'))
            ->assertRedirect();

        $this->assertSame(StoreStatus::Closed, $store->refresh()->status);
    }

    public function test_store_can_open_when_it_has_an_active_product()
    {
        $seller = $this->seller();
        $store = $this->storeFor($seller);
        Product::factory()->for($store)->create(['is_active' => true]);

        $this->actingAs($seller)
            ->post(route('seller.store.toggle'))
            ->assertRedirect();

        $this->assertSame(StoreStatus::Open, $store->refresh()->status);
    }

    public function test_suspended_store_can_not_open()
    {
        $seller = $this->seller();
        $store = $this->storeFor($seller);
        $store->update(['is_suspended' => true]);
        Product::factory()->for($store)->create(['is_active' => true]);

        $this->actingAs($seller)
            ->post(route('seller.store.toggle'))
            ->assertRedirect();

        $this->assertSame(StoreStatus::Closed, $store->refresh()->status);
    }

    public function test_suspended_product_does_not_count_as_an_active_product()
    {
        $seller = $this->seller();
        $store = $this->storeFor($seller);
        Product::factory()->for($store)->create(['is_active' => true, 'is_suspended' => true]);

        $this->actingAs($seller)
            ->post(route('seller.store.toggle'))
            ->assertRedirect();

        $this->assertSame(StoreStatus::Closed, $store->refresh()->status);
    }

    public function test_store_closes_immediately_without_active_orders()
    {
        $seller = $this->seller();
        $store = $this->storeFor($seller);
        $store->update(['status' => StoreStatus::Open]);
        $this->order($store, OrderStatus::Completed);

        $this->actingAs($seller)
            ->post(route('seller.store.toggle'))
            ->assertRedirect();

        $this->assertSame(StoreStatus::Closed, $store->refresh()->status);
    }

    public function test_store_enters_closing_while_orders_are_unconfirmed_or_pending()
    {
        $seller = $this->seller();
        $store = $this->storeFor($seller);
        $store->update(['status' => StoreStatus::Open]);
        $this->order($store, OrderStatus::Unconfirmed);
        $this->order($store, OrderStatus::Pending);

        $this->actingAs($seller)
            ->post(route('seller.store.toggle'))
            ->assertRedirect();

        $this->assertSame(StoreStatus::Closing, $store->refresh()->status);
    }

    public function test_awaiting_buyer_order_does_not_keep_the_store_open()
    {
        $seller = $this->seller();
        $store = $this->storeFor($seller);
        $store->update(['status' => StoreStatus::Open]);
        $this->order($store, OrderStatus::AwaitingBuyer);

        $this->actingAs($seller)
            ->post(route('seller.store.toggle'))
            ->assertRedirect();

        $this->assertSame(StoreStatus::Closed, $store->refresh()->status);
    }

    public function test_settle_closes_a_closing_store_once_orders_finish()
    {
        $seller = $this->seller();
        $store = $this->storeFor($seller);
        $order = $this->order($store, OrderStatus::Pending);
        $store->update(['status' => StoreStatus::Open]);

        $this->actingAs($seller)
            ->post(route('seller.store.toggle'))
            ->assertRedirect();

        $this->assertSame(StoreStatus::Closing, $store->refresh()->status);

        $service = app(StoreService::class);
        $this->assertSame(StoreStatus::Closing, $service->settle($store));

        $order->update(['status' => OrderStatus::Completed]);

        $this->assertSame(StoreStatus::Closed, $service->settle($store->refresh()));
        $this->assertSame(StoreStatus::Closed, $store->refresh()->status);
    }

    public function test_toggle_on_a_closing_store_without_active_orders_closes_it()
    {
        $seller = $this->seller();
        $store = $this->storeFor($seller);
        $store->update(['status' => StoreStatus::Closing]);

        $this->actingAs($seller)
            ->post(route('seller.store.toggle'))
            ->assertRedirect();

        $this->assertSame(StoreStatus::Closed, $store->refresh()->status);
    }

    public function test_closing_store_stays_closing_while_orders_are_active()
    {
        $seller = $this->seller();
        $store = $this->storeFor($seller);
        $store->update(['status' => StoreStatus::Closing]);
        $this->order($store, OrderStatus::Pending);

        $this->actingAs($seller)
            ->post(route('seller.store.toggle'))
            ->assertRedirect();

        $this->assertSame(StoreStatus::Closing, $store->refresh()->status);
    }
}
