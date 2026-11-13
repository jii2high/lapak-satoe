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

class SellerDashboardTest extends TestCase
{
    use MakesOrders, RefreshDatabase;

    /**
     * @return array{0: User, 1: Store}
     */
    private function storeForSeller(): array
    {
        $seller = User::factory()->withRole(Role::Seller, active: true)->create();

        return [$seller, Store::factory()->open()->for($seller)->create()];
    }

    public function test_guests_are_redirected_to_login()
    {
        $this->get(route('seller.dashboard'))->assertRedirect(route('login'));
        $this->get(route('seller.data.dashboard'))->assertRedirect(route('login'));
    }

    public function test_buyers_can_not_open_the_seller_dashboard()
    {
        $this->actingAs(User::factory()->create())
            ->get(route('seller.dashboard'))
            ->assertRedirect(route('home'));
    }

    public function test_kpi_counts_only_completed_orders_of_the_store()
    {
        [$seller, $store] = $this->storeForSeller();
        $product = Product::factory()->for($store)->create();

        $this->orderFor($store, $product, 1, OrderStatus::Completed, [
            'total' => 30000,
            'completed_at' => now(),
        ]);
        $this->orderFor($store, $product, 1, OrderStatus::Unconfirmed);

        $otherStore = Store::factory()->open()->create();
        $this->orderFor(
            $otherStore,
            Product::factory()->for($otherStore)->create(),
            1,
            OrderStatus::Completed,
            ['total' => 999000, 'completed_at' => now()],
        );

        $this->actingAs($seller)
            ->get(route('seller.dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('seller/dashboard')
                ->where('stats.range', '7d')
                ->where('stats.kpi.revenue', 30000)
                ->where('stats.kpi.completed', 1)
                ->where('stats.kpi.new_orders', 2)
                ->has('stats.series', 7)
                ->has('stats.recent', 2));
    }

    public function test_completed_orders_outside_the_range_are_ignored()
    {
        [$seller, $store] = $this->storeForSeller();
        $product = Product::factory()->for($store)->create();

        $order = $this->orderFor($store, $product, 1, OrderStatus::Completed, [
            'total' => 45000,
            'completed_at' => now(),
        ]);

        $order->forceFill([
            'completed_at' => now()->subDays(40),
            'created_at' => now()->subDays(40),
        ])->save();

        $this->actingAs($seller)
            ->getJson(route('seller.data.dashboard', ['range' => '7d']))
            ->assertOk()
            ->assertJsonPath('kpi.revenue', 0)
            ->assertJsonPath('kpi.new_orders', 0);

        $this->actingAs($seller)
            ->getJson(route('seller.data.dashboard', ['range' => '6m']))
            ->assertOk()
            ->assertJsonPath('kpi.revenue', 45000)
            ->assertJsonCount(6, 'series');
    }

    public function test_the_range_filter_controls_the_bucket_count()
    {
        [$seller] = $this->storeForSeller();

        $this->actingAs($seller)
            ->getJson(route('seller.data.dashboard', ['range' => '1m']))
            ->assertOk()
            ->assertJsonPath('range', '1m')
            ->assertJsonCount(30, 'series');

        $this->actingAs($seller)
            ->getJson(route('seller.data.dashboard', ['range' => 'bogus']))
            ->assertOk()
            ->assertJsonPath('range', '7d')
            ->assertJsonCount(7, 'series');

        $this->actingAs($seller)
            ->getJson(route('seller.data.dashboard', ['range' => 'all']))
            ->assertOk()
            ->assertJsonCount(1, 'series');
    }

    public function test_the_recent_orders_list_shows_five_newest_orders()
    {
        [$seller, $store] = $this->storeForSeller();
        $product = Product::factory()->for($store)->create();

        for ($index = 0; $index < 7; $index++) {
            $this->orderFor($store, $product, 1);
        }

        $newest = $store->orders()->latest('id')->firstOrFail();

        $this->actingAs($seller)
            ->getJson(route('seller.data.dashboard'))
            ->assertOk()
            ->assertJsonCount(5, 'recent')
            ->assertJsonPath('recent.0.buyer', $newest->buyer->username);
    }
}
