<?php

namespace Tests\Feature\Admin;

use App\Enums\ApplicationStatus;
use App\Enums\Role;
use App\Models\Report;
use App\Models\SellerApplication;
use App\Models\Store;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->withRole(Role::Admin, active: true)->create();
    }

    private function report(User $reporter, Store $store, CarbonInterface $createdAt): Report
    {
        $report = Report::create([
            'reporter_id' => $reporter->id,
            'reported_user_id' => $store->user_id,
            'store_id' => $store->id,
            'violation_type' => 'penipuan',
            'description' => 'Tidak sesuai deskripsi.',
        ]);

        Report::query()->whereKey($report->id)->update(['created_at' => $createdAt]);

        return $report;
    }

    public function test_guests_are_redirected_to_login()
    {
        $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
        $this->get(route('admin.data.dashboard'))->assertRedirect(route('login'));
    }

    public function test_non_admins_can_not_open_the_admin_dashboard()
    {
        $this->actingAs(User::factory()->create())
            ->get(route('admin.dashboard'))
            ->assertRedirect(route('home'));
    }

    public function test_kpi_counts_stores_active_users_and_reports_of_the_day()
    {
        $admin = $this->admin();
        $store = Store::factory()->open()->create();
        $buyer = User::factory()->create();

        $this->report($buyer, $store, now());
        $this->report($buyer, $store, now()->subDays(10));

        $removed = User::factory()->create();
        $removed->delete();

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/dashboard')
                ->where('stats.range', '7d')
                ->where('stats.kpi.stores', 1)
                ->where('stats.kpi.users', 3)
                ->where('stats.kpi.reports_today', 1)
                ->has('stats.series', 7));
    }

    public function test_the_report_series_follows_the_selected_range()
    {
        $admin = $this->admin();
        $store = Store::factory()->open()->create();
        $buyer = User::factory()->create();

        $this->report($buyer, $store, now());
        $this->report($buyer, $store, now()->subDays(10));

        $response = $this->actingAs($admin)
            ->getJson(route('admin.data.dashboard', ['range' => '1m']))
            ->assertOk()
            ->assertJsonPath('range', '1m')
            ->assertJsonCount(30, 'series');

        self::assertSame(2, collect($response->json('series'))->sum('reports'));

        $this->actingAs($admin)
            ->getJson(route('admin.data.dashboard', ['range' => '7d']))
            ->assertOk()
            ->assertJsonCount(7, 'series');

        $this->actingAs($admin)
            ->getJson(route('admin.data.dashboard', ['range' => '6m']))
            ->assertOk()
            ->assertJsonCount(6, 'series');
    }

    public function test_the_recent_applications_list_shows_the_newest_first()
    {
        $admin = $this->admin();
        $applicant = User::factory()->create();

        SellerApplication::create([
            'user_id' => $applicant->id,
            'full_name' => 'Budi Santoso',
            'nis' => '12345',
            'whatsapp' => '081234567890',
            'store_name' => 'Lapak Budi',
            'description' => 'Jual alat tulis.',
            'status' => ApplicationStatus::Approved,
            'agreed_terms_at' => now(),
        ]);

        $newest = SellerApplication::create([
            'user_id' => $applicant->id,
            'full_name' => 'Sari Dewi',
            'nis' => '67890',
            'whatsapp' => '081298765432',
            'store_name' => 'Lapak Sari',
            'description' => 'Jual kue.',
            'status' => ApplicationStatus::Pending,
            'agreed_terms_at' => now(),
        ]);

        $this->actingAs($admin)
            ->getJson(route('admin.data.dashboard'))
            ->assertOk()
            ->assertJsonCount(2, 'recent')
            ->assertJsonPath('recent.0.id', $newest->id)
            ->assertJsonPath('recent.0.store_name', 'Lapak Sari')
            ->assertJsonPath('recent.0.applicant', $applicant->username);
    }
}
