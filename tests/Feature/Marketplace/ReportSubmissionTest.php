<?php

namespace Tests\Feature\Marketplace;

use App\Enums\OrderStatus;
use App\Enums\ReportStatus;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\MakesOrders;
use Tests\TestCase;

class ReportSubmissionTest extends TestCase
{
    use MakesOrders, RefreshDatabase;

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'violation_type' => 'penipuan',
            'description' => 'Barang yang dikirim tidak sesuai deskripsi.',
        ], $overrides);
    }

    public function test_guests_are_redirected_to_the_login_page()
    {
        $this->post(route('reports.store'), $this->payload())->assertRedirect(route('login'));
    }

    public function test_a_product_report_names_the_store_owner()
    {
        $store = Store::factory()->open()->create();
        $product = Product::factory()->for($store)->create();
        $buyer = User::factory()->create();

        $this->actingAs($buyer)
            ->post(route('reports.store'), $this->payload(['product_id' => $product->id]))
            ->assertRedirect();

        $this->assertDatabaseHas('reports', [
            'reporter_id' => $buyer->id,
            'reported_user_id' => $store->user_id,
            'product_id' => $product->id,
            'store_id' => $store->id,
            'order_id' => null,
            'status' => ReportStatus::New->value,
        ]);
    }

    public function test_a_report_needs_exactly_one_target()
    {
        $store = Store::factory()->open()->create();
        $product = Product::factory()->for($store)->create();
        $buyer = User::factory()->create();

        $this->actingAs($buyer)
            ->post(route('reports.store'), $this->payload())
            ->assertSessionHasErrors('store_id');

        $this->actingAs($buyer)
            ->post(route('reports.store'), $this->payload([
                'product_id' => $product->id,
                'store_id' => $store->id,
            ]))
            ->assertSessionHasErrors('store_id');
    }

    public function test_a_shop_owner_can_not_report_their_own_product()
    {
        $store = Store::factory()->open()->create();
        $product = Product::factory()->for($store)->create();

        $this->actingAs($store->user)
            ->post(route('reports.store'), $this->payload(['product_id' => $product->id]))
            ->assertSessionHasErrors('store_id');
    }

    public function test_a_dispute_marks_the_order_and_rejects_a_foreign_product()
    {
        $store = Store::factory()->open()->create();
        $product = Product::factory()->for($store)->create();
        $other = Product::factory()->for(Store::factory()->open())->create();
        $order = $this->orderFor($store, $product, status: OrderStatus::AwaitingBuyer);

        $this->actingAs($order->buyer)
            ->post(route('reports.store'), $this->payload([
                'order_id' => $order->id,
                'product_id' => $other->id,
            ]))
            ->assertSessionHasErrors('product_id');

        $this->assertSame(OrderStatus::AwaitingBuyer, $order->refresh()->status);

        $this->actingAs($order->buyer)
            ->post(route('reports.store'), $this->payload([
                'order_id' => $order->id,
                'product_id' => $product->id,
            ]))
            ->assertRedirect();

        $order->refresh();

        self::assertSame(OrderStatus::Disputed, $order->status);
        self::assertNull($order->auto_confirm_at);

        $this->assertDatabaseHas('reports', [
            'order_id' => $order->id,
            'product_id' => $product->id,
            'store_id' => $store->id,
            'reported_user_id' => $store->user_id,
            'status' => ReportStatus::New->value,
        ]);
    }

    public function test_a_dispute_can_point_at_the_store_instead_of_a_product()
    {
        $store = Store::factory()->open()->create();
        $product = Product::factory()->for($store)->create();
        $order = $this->orderFor($store, $product, status: OrderStatus::AwaitingBuyer);

        $this->actingAs($order->buyer)
            ->post(route('reports.store'), $this->payload(['order_id' => $order->id]))
            ->assertRedirect();

        self::assertSame(OrderStatus::Disputed, $order->refresh()->status);

        $this->assertDatabaseHas('reports', [
            'order_id' => $order->id,
            'product_id' => null,
            'store_id' => $store->id,
        ]);
    }

    public function test_reports_are_limited_to_five_per_hour()
    {
        $store = Store::factory()->open()->create();
        $product = Product::factory()->for($store)->create();
        $buyer = User::factory()->create();

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->actingAs($buyer)
                ->post(route('reports.store'), $this->payload(['product_id' => $product->id]))
                ->assertRedirect();
        }

        $this->actingAs($buyer)
            ->post(route('reports.store'), $this->payload(['product_id' => $product->id]))
            ->assertStatus(429);
    }
}
