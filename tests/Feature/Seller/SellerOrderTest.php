<?php

namespace Tests\Feature\Seller;

use App\Enums\OrderStatus;
use App\Enums\Role;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\MakesOrders;
use Tests\TestCase;

class SellerOrderTest extends TestCase
{
    use MakesOrders, RefreshDatabase;

    private function seller(): User
    {
        return User::factory()->withRole(Role::Seller, active: true)->create();
    }

    /**
     * @return array{0: User, 1: Store}
     */
    private function storeForSeller(): array
    {
        $seller = $this->seller();
        $store = Store::factory()->open()->for($seller)->create();

        return [$seller, $store];
    }

    public function test_guests_are_redirected_to_login()
    {
        $this->get(route('seller.orders.index'))->assertRedirect(route('login'));
    }

    public function test_buyers_can_not_open_the_seller_order_page()
    {
        $buyer = User::factory()->create();

        $this->actingAs($buyer)
            ->get(route('seller.orders.index'))
            ->assertRedirect(route('home'));
    }

    public function test_only_orders_of_the_sellers_own_store_are_listed()
    {
        [$seller, $store] = $this->storeForSeller();
        $order = $this->orderFor($store, Product::factory()->for($store)->create(), 1);
        $otherStore = Store::factory()->open()->create();
        $this->orderFor($otherStore, Product::factory()->for($otherStore)->create(), 1);

        $this->actingAs($seller)
            ->get(route('seller.orders.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('seller/orders')
                ->where('tab', 'aktif')
                ->has('orders.data', 1)
                ->where('orders.data.0.id', $order->id)
                ->where('orders.data.0.buyer.username', $order->buyer->username));
    }

    public function test_the_history_tab_lists_finished_orders()
    {
        [$seller, $store] = $this->storeForSeller();
        $product = Product::factory()->for($store)->create();
        $this->orderFor($store, $product, 1, OrderStatus::Unconfirmed);
        $this->orderFor($store, $product, 1, OrderStatus::Cancelled);

        $this->actingAs($seller)
            ->get(route('seller.orders.index', ['tab' => 'riwayat']))
            ->assertInertia(fn (Assert $page) => $page
                ->where('tab', 'riwayat')
                ->has('orders.data', 1)
                ->where('orders.data.0.status', OrderStatus::Cancelled->value));
    }

    public function test_the_order_list_can_be_searched_and_filtered()
    {
        [$seller, $store] = $this->storeForSeller();
        $product = Product::factory()->for($store)->create();
        $pending = $this->orderFor($store, $product, 1, OrderStatus::Pending);
        $unconfirmed = $this->orderFor($store, $product, 1, OrderStatus::Unconfirmed);

        $this->actingAs($seller)
            ->get(route('seller.orders.index', ['status' => 'pending']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('orders.data', 1)
                ->where('orders.data.0.id', $pending->id)
                ->where('filters.status', 'pending'));

        $this->actingAs($seller)
            ->get(route('seller.orders.index', ['q' => $unconfirmed->buyer->username]))
            ->assertInertia(fn (Assert $page) => $page
                ->has('orders.data', 1)
                ->where('orders.data.0.id', $unconfirmed->id)
                ->where('filters.q', $unconfirmed->buyer->username));

        $this->actingAs($seller)
            ->get(route('seller.orders.index', ['status' => 'bogus']))
            ->assertInertia(fn (Assert $page) => $page->has('orders.data', 2));
    }

    public function test_an_order_can_be_accepted()
    {
        [$seller, $store] = $this->storeForSeller();
        $product = Product::factory()->for($store)->create(['stock' => 10, 'reserved' => 2]);
        $order = $this->orderFor($store, $product, 2);

        $this->actingAs($seller)
            ->post(route('seller.orders.accept', $order))
            ->assertRedirect();

        self::assertSame(OrderStatus::Pending, $order->refresh()->status);
        self::assertSame(8, $product->refresh()->stock);
        self::assertSame(0, $product->reserved);
    }

    public function test_an_order_can_be_rejected()
    {
        [$seller, $store] = $this->storeForSeller();
        $product = Product::factory()->for($store)->create(['stock' => 10, 'reserved' => 2]);
        $order = $this->orderFor($store, $product, 2);

        $this->actingAs($seller)
            ->post(route('seller.orders.reject', $order))
            ->assertRedirect();

        self::assertSame(OrderStatus::Rejected, $order->refresh()->status);
        self::assertSame(0, $product->refresh()->reserved);
    }

    public function test_a_pending_order_can_be_completed()
    {
        [$seller, $store] = $this->storeForSeller();
        $product = Product::factory()->for($store)->create(['stock' => 8]);
        $order = $this->orderFor($store, $product, 2, OrderStatus::Pending);

        $this->actingAs($seller)
            ->post(route('seller.orders.complete', $order))
            ->assertRedirect();

        self::assertSame(OrderStatus::AwaitingBuyer, $order->refresh()->status);
    }

    public function test_cancelling_requires_a_note()
    {
        [$seller, $store] = $this->storeForSeller();
        $product = Product::factory()->for($store)->create(['stock' => 8]);
        $order = $this->orderFor($store, $product, 2, OrderStatus::Pending);

        $this->actingAs($seller)
            ->post(route('seller.orders.cancel', $order))
            ->assertSessionHasErrors('note');

        self::assertSame(OrderStatus::Pending, $order->refresh()->status);

        $this->actingAs($seller)
            ->post(route('seller.orders.cancel', $order), ['note' => 'Stok habis'])
            ->assertRedirect();

        $order->refresh();

        self::assertSame(OrderStatus::Cancelled, $order->status);
        self::assertSame('Stok habis', $order->cancel_note);
        self::assertSame(10, $product->refresh()->stock);
    }

    public function test_another_seller_can_not_touch_the_order()
    {
        [, $store] = $this->storeForSeller();
        $product = Product::factory()->for($store)->create(['stock' => 10, 'reserved' => 2]);
        $order = $this->orderFor($store, $product, 2);
        $intruder = $this->seller();

        $this->actingAs($intruder)
            ->post(route('seller.orders.accept', $order))
            ->assertForbidden();

        $this->actingAs($intruder)
            ->post(route('seller.orders.reject', $order))
            ->assertForbidden();

        $this->actingAs($intruder)
            ->post(route('seller.orders.complete', $order))
            ->assertForbidden();

        $this->actingAs($intruder)
            ->post(route('seller.orders.cancel', $order), ['note' => 'Salah'])
            ->assertForbidden();

        self::assertSame(OrderStatus::Unconfirmed, $order->refresh()->status);
        self::assertSame(2, $product->refresh()->reserved);
    }
}
