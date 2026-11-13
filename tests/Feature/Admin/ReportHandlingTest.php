<?php

namespace Tests\Feature\Admin;

use App\Enums\OrderStatus;
use App\Enums\ReportStatus;
use App\Enums\Role;
use App\Models\Product;
use App\Models\Report;
use App\Models\Store;
use App\Models\User;
use App\Notifications\ReportReviewed;
use App\Notifications\ReportTargetSuspended;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\MakesOrders;
use Tests\TestCase;

class ReportHandlingTest extends TestCase
{
    use MakesOrders, RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->withRole(Role::Admin, active: true)->create();
    }

    private function reportOnProduct(Store $store, Product $product, array $overrides = []): Report
    {
        $reporter = User::factory()->create();

        return Report::query()->create(array_merge([
            'reporter_id' => $reporter->id,
            'reported_user_id' => $store->user_id,
            'product_id' => $product->id,
            'store_id' => $store->id,
            'violation_type' => 'penipuan',
            'description' => 'Barang tidak sesuai deskripsi.',
            'status' => ReportStatus::New,
        ], $overrides));
    }

    public function test_buyers_can_not_open_the_report_queue()
    {
        $this->actingAs(User::factory()->create())
            ->get(route('admin.reports.index'))
            ->assertRedirect(route('home'));
    }

    public function test_the_admin_sees_reports_with_their_target()
    {
        $store = Store::factory()->open()->create();
        $product = Product::factory()->for($store)->create(['name' => 'Kue Nastar']);
        $this->reportOnProduct($store, $product);

        $this->actingAs($this->admin())
            ->get(route('admin.reports.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/reports')
                ->has('reports.data', 1)
                ->where('reports.data.0.target', 'Produk Kue Nastar')
                ->where('reports.data.0.violation_label', 'Penipuan/tidak sesuai deskripsi')
                ->where('reports.data.0.status', ReportStatus::New->value));
    }

    public function test_the_report_queue_can_be_searched_and_filtered()
    {
        $store = Store::factory()->open()->create();
        $this->reportOnProduct($store, Product::factory()->for($store)->create(['name' => 'Kue Nastar']));

        $other = Store::factory()->open()->create();
        $processed = $this->reportOnProduct($other, Product::factory()->for($other)->create(['name' => 'Kue Lumpur']), [
            'status' => ReportStatus::Processed,
        ]);

        $this->actingAs($this->admin())
            ->get(route('admin.reports.index', ['q' => 'Kue']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('reports.data', 2)
                ->where('filters.q', 'Kue'));

        $this->actingAs($this->admin())
            ->get(route('admin.reports.index', ['status' => 'processed']))
            ->assertInertia(fn (Assert $page) => $page
                ->has('reports.data', 1)
                ->where('reports.data.0.id', $processed->id)
                ->where('filters.status', 'processed'));

        $this->actingAs($this->admin())
            ->get(route('admin.reports.index', ['status' => 'bogus']))
            ->assertInertia(fn (Assert $page) => $page->has('reports.data', 2));
    }

    public function test_processing_a_product_report_suspends_only_that_product()
    {
        Notification::fake();

        $store = Store::factory()->open()->create();
        $product = Product::factory()->for($store)->create();
        $report = $this->reportOnProduct($store, $product);

        $this->actingAs($this->admin())
            ->post(route('admin.reports.process', $report), ['note' => 'Produk dilarang dijual.'])
            ->assertRedirect();

        $report->refresh();

        self::assertSame(ReportStatus::Processed, $report->status);
        self::assertSame('Produk dilarang dijual.', $report->reported_note);
        self::assertNull($report->reporter_note);
        self::assertNotNull($report->handled_at);
        self::assertTrue($product->refresh()->is_suspended);
        self::assertFalse($store->refresh()->is_suspended);

        Notification::assertSentTo($report->reporter, ReportReviewed::class);
        Notification::assertSentTo($store->user, ReportTargetSuspended::class);
    }

    public function test_processing_a_store_report_suspends_the_store_only()
    {
        $store = Store::factory()->open()->create();
        $product = Product::factory()->for($store)->create();
        $report = $this->reportOnProduct($store, $product, [
            'product_id' => null,
            'violation_type' => 'konten_tidak_pantas',
        ]);

        $this->actingAs($this->admin())
            ->post(route('admin.reports.process', $report), ['note' => 'Lapak menjual barang terlarang.'])
            ->assertRedirect();

        self::assertTrue($store->refresh()->is_suspended);
        self::assertFalse($product->refresh()->is_suspended);
    }

    public function test_rejecting_keeps_the_target_active_and_notes_the_reporter()
    {
        Notification::fake();

        $store = Store::factory()->open()->create();
        $product = Product::factory()->for($store)->create();
        $report = $this->reportOnProduct($store, $product);

        $this->actingAs($this->admin())
            ->post(route('admin.reports.reject', $report), ['note' => 'Bukti tidak cukup.'])
            ->assertRedirect();

        $report->refresh();

        self::assertSame(ReportStatus::Rejected, $report->status);
        self::assertSame('Bukti tidak cukup.', $report->reporter_note);
        self::assertNull($report->reported_note);
        self::assertFalse($product->refresh()->is_suspended);

        Notification::assertSentTo($report->reporter, ReportReviewed::class);
        Notification::assertNotSentTo($store->user, ReportTargetSuspended::class);
    }

    public function test_handling_a_disputed_report_completes_the_order()
    {
        $store = Store::factory()->open()->create();
        $product = Product::factory()->for($store)->create(['stock' => 10, 'reserved' => 2]);
        $order = $this->orderFor($store, $product, qty: 1, status: OrderStatus::AwaitingBuyer);
        $report = Report::query()->create([
            'reporter_id' => $order->buyer_id,
            'reported_user_id' => $store->user_id,
            'product_id' => $product->id,
            'store_id' => $store->id,
            'order_id' => $order->id,
            'violation_type' => 'penipuan',
            'description' => 'Barang tidak sesuai deskripsi.',
            'status' => ReportStatus::New,
        ]);

        $order->update(['status' => OrderStatus::Disputed, 'auto_confirm_at' => null]);

        $this->actingAs($this->admin())
            ->post(route('admin.reports.process', $report), ['note' => 'Pelapor benar.'])
            ->assertRedirect();

        $order->refresh();

        self::assertSame(OrderStatus::Completed, $order->status);
        self::assertNotNull($order->completed_at);
        self::assertSame(1, $product->refresh()->sold_count);
    }

    public function test_restore_reactivates_the_target_and_marks_the_report()
    {
        $store = Store::factory()->open()->create();
        $product = Product::factory()->for($store)->create(['is_suspended' => true]);
        $report = $this->reportOnProduct($store, $product, [
            'status' => ReportStatus::Processed,
            'reported_note' => 'Produk dilarang.',
            'handled_at' => now(),
        ]);

        $this->actingAs($this->admin())
            ->post(route('admin.reports.restore', $report))
            ->assertRedirect();

        $report->refresh();

        self::assertNotNull($report->restored_at);
        self::assertFalse($product->refresh()->is_suspended);
    }

    public function test_a_report_that_is_not_processed_can_not_be_restored()
    {
        $store = Store::factory()->open()->create();
        $product = Product::factory()->for($store)->create(['is_suspended' => true]);
        $report = $this->reportOnProduct($store, $product);

        $this->actingAs($this->admin())
            ->post(route('admin.reports.restore', $report))
            ->assertRedirect()
            ->assertSessionHas('inertia.flash_data.toast.type', 'error');

        self::assertTrue($product->refresh()->is_suspended);
    }

    public function test_a_handled_report_can_not_be_processed_twice()
    {
        $store = Store::factory()->open()->create();
        $product = Product::factory()->for($store)->create();
        $report = $this->reportOnProduct($store, $product, ['status' => ReportStatus::Rejected]);

        $this->actingAs($this->admin())
            ->post(route('admin.reports.process', $report), ['note' => 'Coba lagi.'])
            ->assertRedirect()
            ->assertSessionHas('inertia.flash_data.toast.type', 'error');

        self::assertSame(ReportStatus::Rejected, $report->refresh()->status);
        self::assertFalse($product->refresh()->is_suspended);
    }

    public function test_the_admin_can_download_the_report_as_pdf()
    {
        $store = Store::factory()->open()->create();
        $product = Product::factory()->for($store)->create();
        $report = $this->reportOnProduct($store, $product);

        $this->actingAs($this->admin())
            ->get(route('admin.reports.pdf', $report))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }
}
