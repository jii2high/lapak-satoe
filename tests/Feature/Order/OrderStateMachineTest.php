<?php

namespace Tests\Feature\Order;

use App\Enums\OrderStatus;
use App\Enums\StoreStatus;
use App\Models\Order;
use App\Models\Product;
use App\Models\Store;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\Concerns\MakesOrders;
use Tests\TestCase;

class OrderStateMachineTest extends TestCase
{
    use MakesOrders, RefreshDatabase;

    private OrderService $orders;

    protected function setUp(): void
    {
        parent::setUp();

        $this->orders = app(OrderService::class);
    }

    private function store(): Store
    {
        return Store::factory()->open()->create();
    }

    public function test_accept_moves_stock_out_of_reserved()
    {
        $store = $this->store();
        $product = Product::factory()->for($store)->create(['stock' => 10, 'reserved' => 2]);
        $order = $this->orderFor($store, $product, 2);

        $this->orders->accept($order);

        $order->refresh();

        self::assertSame(OrderStatus::Pending, $order->status);
        self::assertSame(8, $product->refresh()->stock);
        self::assertSame(0, $product->reserved);
        self::assertNotNull($order->confirmed_at);
        self::assertNull($order->reminded_at);
        self::assertTrue($order->expires_at->between(now()->addHours(23), now()->addHours(25)));
        self::assertTrue($order->remind_at->between(now()->addHours(11), now()->addHours(13)));
    }

    public function test_accept_is_rejected_once_the_order_moved_on()
    {
        $store = $this->store();
        $product = Product::factory()->for($store)->create(['stock' => 10]);
        $order = $this->orderFor($store, $product, 2, OrderStatus::Pending);

        $this->expectException(ValidationException::class);

        $this->orders->accept($order);
    }

    public function test_reject_releases_the_reservation()
    {
        $store = $this->store();
        $product = Product::factory()->for($store)->create(['stock' => 10, 'reserved' => 2]);
        $order = $this->orderFor($store, $product, 2);

        $this->orders->reject($order);

        self::assertSame(OrderStatus::Rejected, $order->refresh()->status);
        self::assertSame(10, $product->refresh()->stock);
        self::assertSame(0, $product->reserved);
    }

    public function test_buyer_cancel_releases_the_reservation()
    {
        $store = $this->store();
        $product = Product::factory()->for($store)->create(['stock' => 10, 'reserved' => 2]);
        $order = $this->orderFor($store, $product, 2);

        $this->orders->cancelByBuyer($order);

        $order->refresh();

        self::assertSame(OrderStatus::Cancelled, $order->status);
        self::assertSame('buyer', $order->cancelled_by);
        self::assertSame(0, $product->refresh()->reserved);
    }

    public function test_seller_cancel_returns_the_stock_and_stores_the_note()
    {
        $store = $this->store();
        $product = Product::factory()->for($store)->create(['stock' => 8, 'reserved' => 0]);
        $order = $this->orderFor($store, $product, 2, OrderStatus::Pending);

        $this->orders->cancelBySeller($order, 'Stok habis');

        $order->refresh();

        self::assertSame(OrderStatus::Cancelled, $order->status);
        self::assertSame('seller', $order->cancelled_by);
        self::assertSame('Stok habis', $order->cancel_note);
        self::assertSame(10, $product->refresh()->stock);
    }

    public function test_seller_cancel_is_rejected_before_the_order_is_accepted()
    {
        $store = $this->store();
        $product = Product::factory()->for($store)->create(['stock' => 10, 'reserved' => 2]);
        $order = $this->orderFor($store, $product, 2);

        $this->expectException(ValidationException::class);

        $this->orders->cancelBySeller($order, 'Salah pesan');
    }

    public function test_complete_waits_for_the_buyer()
    {
        $store = $this->store();
        $product = Product::factory()->for($store)->create(['stock' => 8]);
        $order = $this->orderFor($store, $product, 2, OrderStatus::Pending);

        $this->orders->complete($order);

        $order->refresh();

        self::assertSame(OrderStatus::AwaitingBuyer, $order->status);
        self::assertNotNull($order->seller_done_at);
        self::assertTrue($order->auto_confirm_at->between(now()->addHours(23), now()->addHours(25)));
    }

    public function test_buyer_confirmation_counts_the_sold_products()
    {
        $store = $this->store();
        $product = Product::factory()->for($store)->create(['stock' => 8, 'sold_count' => 1]);
        $order = $this->orderFor($store, $product, 2, OrderStatus::AwaitingBuyer, [
            'auto_confirm_at' => now()->addHours(24),
        ]);

        $this->orders->confirm($order);

        $order->refresh();

        self::assertSame(OrderStatus::Completed, $order->status);
        self::assertNotNull($order->completed_at);
        self::assertNull($order->auto_confirm_at);
        self::assertSame(3, $product->refresh()->sold_count);
    }

    public function test_confirmation_is_rejected_while_the_seller_still_works()
    {
        $store = $this->store();
        $product = Product::factory()->for($store)->create(['stock' => 8]);
        $order = $this->orderFor($store, $product, 2, OrderStatus::Pending);

        $this->expectException(ValidationException::class);

        $this->orders->confirm($order);
    }

    public function test_every_item_of_the_order_moves_stock()
    {
        $store = $this->store();
        $first = Product::factory()->for($store)->create(['stock' => 10, 'reserved' => 2]);
        $second = Product::factory()->for($store)->create(['stock' => 5, 'reserved' => 3]);
        $order = $this->orderFor($store, $first, 2);
        $order->items()->create([
            'product_id' => $second->id,
            'product_name' => $second->name,
            'price' => $second->price,
            'qty' => 3,
            'subtotal' => $second->price * 3,
        ]);

        $this->orders->accept($order);

        self::assertSame(8, $first->refresh()->stock);
        self::assertSame(0, $first->reserved);
        self::assertSame(2, $second->refresh()->stock);
        self::assertSame(0, $second->reserved);
    }

    public function test_a_closing_store_closes_when_its_last_order_ends()
    {
        $store = $this->store();
        $product = Product::factory()->for($store)->create(['stock' => 8]);
        $order = $this->orderFor($store, $product, 2, OrderStatus::Pending);
        $store->update(['status' => StoreStatus::Closing]);

        $this->orders->cancelBySeller($order, 'Toko tutup');

        self::assertSame(StoreStatus::Closed, $store->refresh()->status);
    }

    public function test_a_closing_store_stays_closing_while_an_order_is_still_running()
    {
        $store = $this->store();
        $product = Product::factory()->for($store)->create(['stock' => 10, 'reserved' => 2]);
        $order = $this->orderFor($store, $product, 2);
        $store->update(['status' => StoreStatus::Closing]);

        $this->orders->accept($order);

        self::assertSame(StoreStatus::Closing, $store->refresh()->status);
        self::assertSame(OrderStatus::Pending, Order::query()->sole()->status);
    }
}
