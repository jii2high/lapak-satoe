<?php

namespace Tests\Feature\Seller;

use App\Enums\Role;
use App\Models\Category;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ProductManagementTest extends TestCase
{
    use RefreshDatabase;

    private function seller(): User
    {
        return User::factory()->withRole(Role::Seller, active: true)->create();
    }

    private function storeFor(User $seller): Store
    {
        return Store::factory()->for($seller)->create();
    }

    /**
     * @param  array<int, UploadedFile>  $images
     * @return array<string, mixed>
     */
    private function payload(Category $category, array $images, array $overrides = []): array
    {
        return array_merge([
            'name' => 'Kopi Susu',
            'description' => 'Kopi susu gula aren.',
            'category_id' => $category->id,
            'price' => 12000,
            'stock' => 20,
            'is_active' => true,
            'images' => $images,
        ], $overrides);
    }

    public function test_guests_are_redirected_to_login()
    {
        $this->get(route('seller.products.create'))->assertRedirect(route('login'));
        $this->post(route('seller.products.store'), [])->assertRedirect(route('login'));
    }

    public function test_buyers_can_not_manage_products()
    {
        $buyer = User::factory()->create();

        $this->actingAs($buyer)
            ->get(route('seller.products.create'))
            ->assertRedirect(route('home'));

        $this->actingAs($buyer)
            ->post(route('seller.products.store'), ['name' => 'Produk Palsu'])
            ->assertRedirect(route('home'));
    }

    public function test_a_product_can_be_created_with_resized_images()
    {
        Storage::fake('public');

        $seller = $this->seller();
        $this->storeFor($seller);
        $category = Category::factory()->create();

        $this->actingAs($seller)
            ->post(route('seller.products.store'), $this->payload($category, [
                UploadedFile::fake()->image('depan.jpg', 2000, 1000),
                UploadedFile::fake()->image('belakang.png', 800, 800),
            ]))
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $product = Product::query()->firstOrFail();

        $this->assertSame('Kopi Susu', $product->name);
        $this->assertSame(12000, $product->price);
        $this->assertSame(20, $product->stock);
        $this->assertSame(2, $product->images()->count());

        $first = $product->images()->orderBy('sort')->firstOrFail();
        $size = getimagesizefromstring(Storage::disk('public')->get($first->path));

        $this->assertNotFalse($size);
        $this->assertSame(1200, $size[0]);
        $this->assertSame(600, $size[1]);
    }

    public function test_product_requires_at_least_one_image()
    {
        Storage::fake('public');

        $seller = $this->seller();
        $this->storeFor($seller);
        $category = Category::factory()->create();

        $this->actingAs($seller)
            ->post(route('seller.products.store'), $this->payload($category, []))
            ->assertSessionHasErrors('images');

        $this->assertDatabaseCount('products', 0);
    }

    public function test_product_rejects_more_than_five_images()
    {
        Storage::fake('public');

        $seller = $this->seller();
        $this->storeFor($seller);
        $category = Category::factory()->create();

        $images = [];
        for ($index = 0; $index < 6; $index++) {
            $images[] = UploadedFile::fake()->image("foto-{$index}.jpg", 600, 600);
        }

        $this->actingAs($seller)
            ->post(route('seller.products.store'), $this->payload($category, $images))
            ->assertSessionHasErrors('images');

        $this->assertDatabaseCount('products', 0);
    }

    public function test_product_rejects_files_that_are_not_images()
    {
        Storage::fake('public');

        $seller = $this->seller();
        $this->storeFor($seller);
        $category = Category::factory()->create();

        $this->actingAs($seller)
            ->post(route('seller.products.store'), $this->payload($category, [
                UploadedFile::fake()->create('foto.pdf', 100, 'application/pdf'),
            ]))
            ->assertSessionHasErrors('images.0');
    }

    public function test_product_rejects_images_larger_than_three_megabytes()
    {
        Storage::fake('public');

        $seller = $this->seller();
        $this->storeFor($seller);
        $category = Category::factory()->create();

        $this->actingAs($seller)
            ->post(route('seller.products.store'), $this->payload($category, [
                UploadedFile::fake()->image('besar.jpg', 600, 600)->size(4000),
            ]))
            ->assertSessionHasErrors('images.0');
    }

    public function test_edit_page_shows_the_product_of_the_seller()
    {
        Storage::fake('public');

        $seller = $this->seller();
        $store = $this->storeFor($seller);
        $product = Product::factory()->for($store)->create(['name' => 'Kopi Susu']);

        $this->actingAs($seller)
            ->get(route('seller.products.edit', $product))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('seller/products/edit')
                ->where('product.name', 'Kopi Susu')
                ->where('product.stock', 10));
    }

    public function test_a_product_can_be_updated_and_its_images_removed_or_added()
    {
        Storage::fake('public');

        $seller = $this->seller();
        $store = $this->storeFor($seller);
        $category = Category::factory()->create();
        $product = Product::factory()->for($store)->create(['category_id' => $category->id]);

        $oldImage = $product->images()->create(['path' => 'products/lama.jpg', 'sort' => 0]);
        $keptImage = $product->images()->create(['path' => 'products/simpan.jpg', 'sort' => 1]);
        Storage::disk('public')->put('products/lama.jpg', 'lama');

        $this->actingAs($seller)
            ->patch(route('seller.products.update', $product), [
                'name' => 'Kopi Susu Baru',
                'description' => 'Resep baru.',
                'category_id' => $category->id,
                'price' => 15000,
                'stock' => 8,
                'is_active' => false,
                'removed_image_ids' => [$oldImage->id],
                'images' => [UploadedFile::fake()->image('tambahan.jpg', 600, 600)],
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $product->refresh();

        $this->assertSame('Kopi Susu Baru', $product->name);
        $this->assertSame(15000, $product->price);
        $this->assertSame(8, $product->stock);
        $this->assertFalse($product->is_active);

        $this->assertDatabaseMissing('product_images', ['id' => $oldImage->id]);
        $this->assertDatabaseHas('product_images', ['id' => $keptImage->id]);
        $this->assertSame(2, $product->images()->count());
        Storage::disk('public')->assertMissing('products/lama.jpg');
    }

    public function test_update_rejects_removing_every_image()
    {
        Storage::fake('public');

        $seller = $this->seller();
        $store = $this->storeFor($seller);
        $category = Category::factory()->create();
        $product = Product::factory()->for($store)->create(['category_id' => $category->id]);
        $image = $product->images()->create(['path' => 'products/satu.jpg', 'sort' => 0]);

        $this->actingAs($seller)
            ->patch(route('seller.products.update', $product), [
                'name' => $product->name,
                'description' => '',
                'category_id' => $category->id,
                'price' => $product->price,
                'stock' => $product->stock,
                'is_active' => true,
                'removed_image_ids' => [$image->id],
            ])
            ->assertSessionHasErrors('images');

        $this->assertDatabaseHas('product_images', ['id' => $image->id]);
    }

    public function test_update_rejects_an_image_that_belongs_to_another_product()
    {
        Storage::fake('public');

        $seller = $this->seller();
        $store = $this->storeFor($seller);
        $category = Category::factory()->create();
        $product = Product::factory()->for($store)->create(['category_id' => $category->id]);
        $product->images()->create(['path' => 'products/satu.jpg', 'sort' => 0]);

        $otherProduct = Product::factory()->create(['category_id' => $category->id]);
        $foreignImage = $otherProduct->images()->create(['path' => 'products/lain.jpg', 'sort' => 0]);

        $this->actingAs($seller)
            ->patch(route('seller.products.update', $product), [
                'name' => $product->name,
                'description' => '',
                'category_id' => $category->id,
                'price' => $product->price,
                'stock' => $product->stock,
                'is_active' => true,
                'removed_image_ids' => [$foreignImage->id],
            ])
            ->assertSessionHasErrors('removed_image_ids.0');
    }

    public function test_update_rejects_stock_below_reserved_amount()
    {
        Storage::fake('public');

        $seller = $this->seller();
        $store = $this->storeFor($seller);
        $category = Category::factory()->create();
        $product = Product::factory()->for($store)->create([
            'category_id' => $category->id,
            'stock' => 10,
            'reserved' => 4,
        ]);
        $product->images()->create(['path' => 'products/satu.jpg', 'sort' => 0]);

        $this->actingAs($seller)
            ->patch(route('seller.products.update', $product), [
                'name' => $product->name,
                'description' => '',
                'category_id' => $category->id,
                'price' => $product->price,
                'stock' => 2,
                'is_active' => true,
            ])
            ->assertSessionHasErrors('stock');

        $this->assertSame(10, $product->refresh()->stock);
    }

    public function test_update_rejects_activating_a_suspended_product()
    {
        Storage::fake('public');

        $seller = $this->seller();
        $store = $this->storeFor($seller);
        $category = Category::factory()->create();
        $product = Product::factory()->for($store)->suspended()->create([
            'category_id' => $category->id,
            'is_active' => false,
        ]);
        $product->images()->create(['path' => 'products/satu.jpg', 'sort' => 0]);

        $this->actingAs($seller)
            ->patch(route('seller.products.update', $product), [
                'name' => $product->name,
                'description' => '',
                'category_id' => $category->id,
                'price' => $product->price,
                'stock' => $product->stock,
                'is_active' => true,
            ])
            ->assertSessionHasErrors('is_active');

        $this->assertFalse($product->refresh()->is_active);
    }

    public function test_another_seller_can_not_touch_the_product()
    {
        Storage::fake('public');

        $owner = $this->seller();
        $store = $this->storeFor($owner);
        $category = Category::factory()->create();
        $product = Product::factory()->for($store)->create(['category_id' => $category->id]);
        $product->images()->create(['path' => 'products/satu.jpg', 'sort' => 0]);
        $name = $product->name;

        $intruder = $this->seller();
        $this->storeFor($intruder);

        $this->actingAs($intruder)
            ->get(route('seller.products.edit', $product))
            ->assertForbidden();

        $this->actingAs($intruder)
            ->patch(route('seller.products.update', $product), [
                'name' => 'Diubah Orang Lain',
                'description' => '',
                'category_id' => $category->id,
                'price' => 1000,
                'stock' => 1,
                'is_active' => true,
            ])
            ->assertForbidden();

        $this->actingAs($intruder)
            ->delete(route('seller.products.destroy', $product))
            ->assertForbidden();

        $this->assertSame($name, Product::query()->findOrFail($product->id)->name);
    }

    public function test_a_product_can_be_soft_deleted()
    {
        Storage::fake('public');

        $seller = $this->seller();
        $store = $this->storeFor($seller);
        $product = Product::factory()->for($store)->create();

        $this->actingAs($seller)
            ->delete(route('seller.products.destroy', $product))
            ->assertRedirect();

        $this->assertSoftDeleted('products', ['id' => $product->id]);
    }
}
