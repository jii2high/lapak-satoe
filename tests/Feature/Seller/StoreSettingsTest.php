<?php

namespace Tests\Feature\Seller;

use App\Enums\Role;
use App\Enums\StoreStatus;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class StoreSettingsTest extends TestCase
{
    use RefreshDatabase;

    private function seller(): User
    {
        return User::factory()->withRole(Role::Seller, active: true)->create();
    }

    private function storeFor(User $seller): Store
    {
        return Store::factory()
            ->for($seller)
            ->create(['status' => StoreStatus::Open]);
    }

    public function test_guests_are_redirected_to_login()
    {
        $this->get(route('seller.store.edit'))->assertRedirect(route('login'));
        $this->patch(route('seller.store.update'), [])->assertRedirect(route('login'));
    }

    public function test_buyers_can_not_open_the_store_settings()
    {
        $buyer = User::factory()->create();

        $this->actingAs($buyer)
            ->get(route('seller.store.edit'))
            ->assertRedirect(route('home'));

        $this->actingAs($buyer)
            ->patch(route('seller.store.update'), ['name' => 'Lapak Palsu'])
            ->assertRedirect(route('home'));
    }

    public function test_seller_sees_the_store_settings_with_previous_name()
    {
        Storage::fake('public');

        $seller = $this->seller();
        $store = $this->storeFor($seller);

        $this->actingAs($seller)
            ->get(route('seller.store.edit'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('seller/store')
                ->where('store.name', $store->name)
                ->where('store.status', 'open')
                ->where('active_product_count', 0)
                ->where('active_order_count', 0));
    }

    public function test_seller_can_update_the_store_profile()
    {
        $seller = $this->seller();
        $store = $this->storeFor($seller);
        $slug = $store->slug;

        $this->actingAs($seller)
            ->patch(route('seller.store.update'), [
                'name' => 'Kopi Teras Baru',
                'description' => 'Kopi susu dan roti bakar.',
                'whatsapp' => '081234567890',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $store->refresh();

        $this->assertSame('Kopi Teras Baru', $store->name);
        $this->assertSame('Kopi susu dan roti bakar.', $store->description);
        $this->assertSame('081234567890', $store->whatsapp);
        $this->assertSame($slug, $store->slug);
        $this->assertSame(StoreStatus::Open, $store->status);
    }

    public function test_renaming_the_store_records_history_without_changing_the_slug()
    {
        $seller = $this->seller();
        $store = $this->storeFor($seller);
        $slug = $store->slug;
        $oldName = $store->name;

        $this->actingAs($seller)
            ->patch(route('seller.store.update'), [
                'name' => 'Nama Baru Lapak',
                'description' => $store->description,
                'whatsapp' => $store->whatsapp,
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('store_name_histories', [
            'store_id' => $store->id,
            'old_name' => $oldName,
        ]);

        $this->assertSame($slug, $store->refresh()->slug);
        $this->assertSame($slug, $store->slug);
    }

    public function test_public_store_page_shows_the_previous_name_for_seven_days()
    {
        $seller = $this->seller();
        $store = $this->storeFor($seller);
        $oldName = $store->name;

        $this->actingAs($seller)
            ->patch(route('seller.store.update'), [
                'name' => 'Lapak Ganti Nama',
                'description' => $store->description,
                'whatsapp' => $store->whatsapp,
            ])
            ->assertSessionHasNoErrors();

        $this->get(route('stores.show', $store->slug))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('store.name', 'Lapak Ganti Nama')
                ->where('store.previous_name', $oldName));
    }

    public function test_whatsapp_number_must_start_with_08_or_62()
    {
        $seller = $this->seller();
        $store = $this->storeFor($seller);

        $this->actingAs($seller)
            ->patch(route('seller.store.update'), [
                'name' => $store->name,
                'description' => '',
                'whatsapp' => '12345',
            ])
            ->assertSessionHasErrors('whatsapp');

        $this->assertSame($store->whatsapp, $store->refresh()->whatsapp);
    }

    public function test_store_name_is_required()
    {
        $seller = $this->seller();
        $this->storeFor($seller);

        $this->actingAs($seller)
            ->patch(route('seller.store.update'), [
                'name' => '',
                'description' => '',
                'whatsapp' => '081234567890',
            ])
            ->assertSessionHasErrors('name');
    }

    public function test_logo_is_stored_and_the_old_file_is_removed()
    {
        Storage::fake('public');

        $seller = $this->seller();
        $store = $this->storeFor($seller);

        $this->actingAs($seller)
            ->patch(route('seller.store.update'), [
                'name' => $store->name,
                'description' => '',
                'whatsapp' => $store->whatsapp,
                'logo' => UploadedFile::fake()->image('logo.jpg', 400, 400),
            ])
            ->assertSessionHasNoErrors();

        $firstLogo = $store->refresh()->logo_path;

        $this->assertNotNull($firstLogo);
        Storage::disk('public')->assertExists($firstLogo);

        $this->actingAs($seller)
            ->patch(route('seller.store.update'), [
                'name' => $store->name,
                'description' => '',
                'whatsapp' => $store->whatsapp,
                'logo' => UploadedFile::fake()->image('logo-baru.png', 400, 400),
            ])
            ->assertSessionHasNoErrors();

        $secondLogo = $store->refresh()->logo_path;

        $this->assertNotSame($firstLogo, $secondLogo);
        Storage::disk('public')->assertMissing($firstLogo);
        Storage::disk('public')->assertExists($secondLogo);
    }

    public function test_store_images_must_be_jpeg_or_png()
    {
        Storage::fake('public');

        $seller = $this->seller();
        $store = $this->storeFor($seller);

        $this->actingAs($seller)
            ->patch(route('seller.store.update'), [
                'name' => $store->name,
                'description' => '',
                'whatsapp' => $store->whatsapp,
                'banner' => UploadedFile::fake()->create('banner.pdf', 100, 'application/pdf'),
            ])
            ->assertSessionHasErrors('banner');

        $this->assertNull($store->refresh()->banner_path);
    }
}
