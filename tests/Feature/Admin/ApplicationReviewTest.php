<?php

namespace Tests\Feature\Admin;

use App\Enums\ApplicationStatus;
use App\Enums\Role;
use App\Enums\StoreStatus;
use App\Models\Category;
use App\Models\SellerApplication;
use App\Models\User;
use App\Notifications\ApplicationReviewed;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ApplicationReviewTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->withRole(Role::Admin, active: true)->create();
    }

    private function application(array $overrides = []): SellerApplication
    {
        $applicant = User::factory()->create();

        $application = $applicant->applications()->create(array_merge([
            'full_name' => 'Budi Santoso',
            'nis' => '12345',
            'whatsapp' => '081234567890',
            'store_name' => 'Kopi Budi',
            'description' => 'Kopi susu dan teh manis.',
            'status' => ApplicationStatus::Pending,
            'agreed_terms_at' => now(),
        ], $overrides));

        $application->categories()->attach(
            Category::factory()->create(['name' => 'Minuman'])->id,
        );

        $application->files()->create([
            'type' => 'student_card',
            'path' => 'applications/kartu.jpg',
        ]);

        return $application;
    }

    public function test_guests_are_redirected_to_the_login_page()
    {
        $this->get(route('admin.applications.index'))->assertRedirect(route('login'));
    }

    public function test_buyers_can_not_review_applications()
    {
        $buyer = User::factory()->create();

        $this->actingAs($buyer)
            ->get(route('admin.applications.index'))
            ->assertRedirect(route('home'));
    }

    public function test_the_admin_sees_the_queue_with_applicant_details()
    {
        $application = $this->application();

        $this->actingAs($this->admin())
            ->get(route('admin.applications.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('admin/applications')
                ->has('applications', 1)
                ->where('applications.0.full_name', 'Budi Santoso')
                ->where('applications.0.nis', '12345')
                ->where('applications.0.store_name', 'Kopi Budi')
                ->where('applications.0.status', 'pending')
                ->where('applications.0.categories', ['Minuman'])
                ->where('applications.0.student_card_url', route('admin.applications.card', $application)));
    }

    public function test_approving_grants_the_seller_role_and_creates_a_closed_store()
    {
        Notification::fake();

        $application = $this->application();

        $this->actingAs($this->admin())
            ->post(route('admin.applications.approve', $application))
            ->assertRedirect();

        $application->refresh();

        $this->assertSame(ApplicationStatus::Approved, $application->status);
        $this->assertNotNull($application->reviewed_at);
        $this->assertTrue($application->user->hasRole(Role::Seller));

        $store = $application->user->store;
        $this->assertNotNull($store);
        $this->assertSame('Kopi Budi', $store->name);
        $this->assertSame('kopi-budi', $store->slug);
        $this->assertSame(StoreStatus::Closed, $store->status);

        Notification::assertSentTo($application->user, ApplicationReviewed::class);
    }

    public function test_an_application_can_only_be_reviewed_once()
    {
        Notification::fake();

        $application = $this->application();
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.applications.approve', $application));
        $this->actingAs($admin)
            ->post(route('admin.applications.approve', $application))
            ->assertSessionHasErrors('status');

        $this->assertDatabaseCount('stores', 1);
    }

    public function test_rejecting_stores_the_note_and_notifies_the_applicant()
    {
        Notification::fake();

        $application = $this->application();

        $this->actingAs($this->admin())
            ->post(route('admin.applications.reject', $application), [
                'reject_note' => 'Foto kartu pelajar tidak terbaca.',
            ])
            ->assertRedirect();

        $application->refresh();

        $this->assertSame(ApplicationStatus::Rejected, $application->status);
        $this->assertSame('Foto kartu pelajar tidak terbaca.', $application->reject_note);
        $this->assertFalse($application->user->hasRole(Role::Seller));

        Notification::assertSentTo($application->user, ApplicationReviewed::class);
    }

    public function test_a_rejection_requires_a_note()
    {
        $application = $this->application();

        $this->actingAs($this->admin())
            ->post(route('admin.applications.reject', $application), ['reject_note' => ''])
            ->assertSessionHasErrors('reject_note');

        $this->assertSame(ApplicationStatus::Pending, $application->fresh()->status);
    }

    public function test_the_student_card_is_streamed_from_private_storage()
    {
        Storage::fake('local');

        $application = $this->application();
        Storage::disk('local')->put('applications/kartu.jpg', 'private');

        $response = $this->actingAs($this->admin())
            ->get(route('admin.applications.card', $application));

        $response->assertOk();
        $this->assertSame('private', $response->streamedContent());
    }

    public function test_buyers_can_not_open_the_student_card()
    {
        $application = $this->application();

        $this->actingAs(User::factory()->create())
            ->get(route('admin.applications.card', $application))
            ->assertRedirect(route('home'));
    }
}
