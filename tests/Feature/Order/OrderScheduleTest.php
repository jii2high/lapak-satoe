<?php

namespace Tests\Feature\Order;

use App\Enums\OrderStatus;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use App\Notifications\OrderCancelled;
use App\Notifications\OrderPlaced;
use App\Notifications\OrderReminder;
use App\Services\CheckoutService;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\Concerns\MakesOrders;
use Tests\TestCase;

class OrderScheduleTest extends TestCase
{
    use MakesOrders, RefreshDatabase;

    private function store(): Store
    {
        return Store::factory()->open()->create();
    }

    public function test_remind_notifies_the_seller_once_and_stamps_reminded_at()
    {
        Notification::fake();

        $store = $this->store();
        $product = Product::factory()->for($store)->create(['stock' => 10, 'reserved' => 2]);
        $order = $this->orderFor($store, $product, 2, attributes: ['remind_at' => now()->subMinute()]);

        $this->artisan('orders:remind')->assertSuccessful();

        Notification::assertSentToTimes($store->user, OrderReminder::class, 1);
        self::assertNotNull($order->refresh()->reminded_at);

        Notification::fake();
        $this->artisan('orders:remind')->assertSuccessful();

        Notification::assertNothingSent();
    }

    public function test_remind_skips_orders_that_are_not_due_yet()
    {
        Notification::fake();

        $store = $this->store();
        $product = Product::factory()->for($store)->create(['stock' => 10, 'reserved' => 2]);
        $order = $this->orderFor($store, $product, 2, attributes: ['remind_at' => now()->addHours(2)]);

        $this->artisan('orders:remind')->assertSuccessful();

        Notification::assertNothingSent();
        self::assertNull($order->refresh()->reminded_at);
    }

    public function test_expire_releases_the_reservation_of_an_unconfirmed_order()
    {
        Notification::fake();

        $store = $this->store();
        $product = Product::factory()->for($store)->create(['stock' => 10, 'reserved' => 2]);
        $order = $this->orderFor($store, $product, 2, attributes: ['expires_at' => now()->subMinute()]);

        $this->artisan('orders:expire')->assertSuccessful();

        $order->refresh();

        self::assertSame(OrderStatus::Cancelled, $order->status);
        self::assertSame('system', $order->cancelled_by);
        self::assertSame(10, $product->refresh()->stock);
        self::assertSame(0, $product->reserved);

        Notification::assertSentTo($order->buyer, OrderCancelled::class);
        Notification::assertSentTo($store->user, OrderCancelled::class);
    }

    public function test_expire_returns_the_stock_of_a_pending_order()
    {
        Notification::fake();

        $store = $this->store();
        $product = Product::factory()->for($store)->create(['stock' => 8, 'reserved' => 0]);
        $order = $this->orderFor(
            $store,
            $product,
            2,
            OrderStatus::Pending,
            ['expires_at' => now()->subMinute()],
        );

        $this->artisan('orders:expire')->assertSuccessful();

        self::assertSame(OrderStatus::Cancelled, $order->refresh()->status);
        self::assertSame(10, $product->refresh()->stock);
    }

    public function test_expire_leaves_orders_inside_the_deadline_alone()
    {
        Notification::fake();

        $store = $this->store();
        $product = Product::factory()->for($store)->create(['stock' => 10, 'reserved' => 2]);
        $order = $this->orderFor($store, $product, 2, attributes: ['expires_at' => now()->addHours(3)]);

        $this->artisan('orders:expire')->assertSuccessful();

        self::assertSame(OrderStatus::Unconfirmed, $order->refresh()->status);
        self::assertSame(2, $product->refresh()->reserved);
    }

    public function test_auto_confirm_completes_the_order_and_counts_the_sale()
    {
        $store = $this->store();
        $product = Product::factory()->for($store)->create(['stock' => 8, 'sold_count' => 1]);
        $order = $this->orderFor(
            $store,
            $product,
            2,
            OrderStatus::AwaitingBuyer,
            ['auto_confirm_at' => now()->subMinute()],
        );

        $this->artisan('orders:auto-confirm')->assertSuccessful();

        $order->refresh();

        self::assertSame(OrderStatus::Completed, $order->status);
        self::assertSame(3, $product->refresh()->sold_count);
        self::assertNotNull($order->completed_at);
    }

    public function test_auto_confirm_ignores_an_order_the_buyer_still_holds()
    {
        $store = $this->store();
        $product = Product::factory()->for($store)->create(['stock' => 8, 'sold_count' => 1]);
        $order = $this->orderFor(
            $store,
            $product,
            2,
            OrderStatus::AwaitingBuyer,
            ['auto_confirm_at' => now()->addHours(4)],
        );

        $this->artisan('orders:auto-confirm')->assertSuccessful();

        self::assertSame(OrderStatus::AwaitingBuyer, $order->refresh()->status);
        self::assertSame(1, $product->refresh()->sold_count);
    }

    public function test_auto_confirm_ignores_a_disputed_order()
    {
        $store = $this->store();
        $product = Product::factory()->for($store)->create(['stock' => 8]);
        $order = $this->orderFor(
            $store,
            $product,
            2,
            OrderStatus::Disputed,
            ['auto_confirm_at' => now()->subHour()],
        );

        $this->artisan('orders:auto-confirm')->assertSuccessful();

        self::assertSame(OrderStatus::Disputed, $order->refresh()->status);
    }

    public function test_the_scheduler_carries_all_three_commands()
    {
        $schedule = collect(app(Schedule::class)->events())
            ->map(fn ($event) => $event->command);

        self::assertTrue($schedule->contains(fn (string $command) => str_contains($command, 'orders:remind')));
        self::assertTrue($schedule->contains(fn (string $command) => str_contains($command, 'orders:expire')));
        self::assertTrue($schedule->contains(fn (string $command) => str_contains($command, 'orders:auto-confirm')));
    }

    public function test_checkout_notifies_the_seller()
    {
        Notification::fake();

        $store = $this->store();
        $product = Product::factory()->for($store)->create(['stock' => 5, 'is_active' => true]);
        $buyer = User::factory()->create();
        $buyer->cart()->create()->items()->create(['product_id' => $product->id, 'qty' => 1]);

        app(CheckoutService::class)->checkout($buyer, $store);

        Notification::assertSentTo($store->user, OrderPlaced::class);
    }
}
