<?php

namespace Tests\Feature\Seller;

use App\Enums\ApplicationStatus;
use App\Models\Category;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class SellerApplicationTest extends TestCase
{
    use RefreshDatabase;

    private function payload(Category $category, array $overrides = []): array
    {
        return array_merge([
            'full_name' => 'Budi Santoso',
            'nis' => '12345',
            'whatsapp' => '081234567890',
            'store_name' => 'Kopi Budi',
            'description' => 'Kopi susu dan teh manis.',
            'categories' => [$category->id],
            'product_photos' => [
                UploadedFile::fake()->create('produk.jpg', 100, 'image/jpeg'),
            ],
            'student_card' => UploadedFile::fake()->create('kartu.jpg', 100, 'image/jpeg'),
            'agreed_terms' => true,
        ], $overrides);
    }

    private function submit(User $user, array $payload): TestResponse
    {
        return $this->actingAs($user)->post(route('seller-application.store'), $payload);
    }

    public function test_guests_are_redirected_to_the_login_page()
    {
        $this->get(route('seller-application.create'))->assertRedirect(route('login'));
        $this->post(route('seller-application.store'))->assertRedirect(route('login'));
    }

    public function test_the_form_shows_the_categories_and_the_current_application()
    {
        $category = Category::factory()->create(['name' => 'Minuman']);
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('seller-application.create'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('seller/application')
                ->where('application', null)
                ->where('is_seller', false)
                ->has('categories', 1)
                ->where('categories.0.name', 'Minuman'));
    }

    public function test_an_unverified_user_can_not_submit_an_application()
    {
        Storage::fake('public');
        Storage::fake('local');

        $user = User::factory()->unverified()->create();

        $this->submit($user, $this->payload(Category::factory()->create()))
            ->assertRedirect();

        $this->assertDatabaseCount('seller_applications', 0);
    }

    public function test_a_verified_user_can_submit_an_application_with_files()
    {
        Storage::fake('public');
        Storage::fake('local');

        $category = Category::factory()->create();
        $user = User::factory()->create();

        $this->submit($user, $this->payload($category))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('seller-application.create'));

        $application = $user->applications()->firstOrFail();

        $this->assertSame(ApplicationStatus::Pending, $application->status);
        $this->assertNotNull($application->agreed_terms_at);
        $this->assertDatabaseHas('application_categories', [
            'application_id' => $application->id,
            'category_id' => $category->id,
        ]);

        $photo = $application->files()->where('type', 'product_photo')->firstOrFail();
        $card = $application->files()->where('type', 'student_card')->firstOrFail();

        Storage::disk('public')->assertExists($photo->path);
        Storage::disk('local')->assertExists($card->path);
    }

    public function test_the_required_documents_and_the_checkbox_are_mandatory()
    {
        Storage::fake('public');
        Storage::fake('local');

        $user = User::factory()->create();

        $this->submit($user, [
            'full_name' => 'Budi Santoso',
            'nis' => '12345',
            'whatsapp' => '081234567890',
            'store_name' => 'Kopi Budi',
            'description' => 'Kopi susu dan teh manis.',
        ])
            ->assertSessionHasErrors([
                'categories',
                'product_photos',
                'student_card',
                'agreed_terms',
            ]);

        $this->assertDatabaseCount('seller_applications', 0);
    }

    public function test_a_whatsapp_number_must_start_with_08_or_62()
    {
        Storage::fake('public');
        Storage::fake('local');

        $user = User::factory()->create();

        $this->submit($user, $this->payload(
            Category::factory()->create(),
            ['whatsapp' => '12345'],
        ))->assertSessionHasErrors('whatsapp');
    }

    public function test_a_second_pending_application_is_rejected()
    {
        Storage::fake('public');
        Storage::fake('local');

        $category = Category::factory()->create();
        $user = User::factory()->create();

        $this->submit($user, $this->payload($category))->assertSessionHasNoErrors();
        $this->submit($user, $this->payload($category))->assertSessionHasErrors('store_name');

        $this->assertDatabaseCount('seller_applications', 1);
    }

    public function test_a_rejected_user_can_apply_again()
    {
        Storage::fake('public');
        Storage::fake('local');

        $category = Category::factory()->create();
        $user = User::factory()->create();

        $user->applications()->create([
            'full_name' => 'Budi Santoso',
            'nis' => '12345',
            'whatsapp' => '081234567890',
            'store_name' => 'Kopi Budi',
            'description' => 'Percobaan pertama.',
            'status' => ApplicationStatus::Rejected,
            'reject_note' => 'Foto tidak jelas.',
            'agreed_terms_at' => now(),
        ]);

        $this->submit($user, $this->payload($category))->assertSessionHasNoErrors();

        $this->assertDatabaseCount('seller_applications', 2);
    }

    public function test_a_user_that_already_has_a_store_can_not_apply()
    {
        Storage::fake('public');
        Storage::fake('local');

        $user = User::factory()->create();
        Store::factory()->create(['user_id' => $user->id]);

        $this->submit($user, $this->payload(Category::factory()->create()))
            ->assertSessionHasErrors('store_name');

        $this->assertDatabaseCount('seller_applications', 0);
    }
}
