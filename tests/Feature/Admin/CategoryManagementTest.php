<?php

namespace Tests\Feature\Admin;

use App\Enums\Role;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CategoryManagementTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->withRole(Role::Admin, active: true)->create();
    }

    public function test_guests_are_redirected_to_login()
    {
        $this->get(route('admin.categories.index'))->assertRedirect(route('login'));
        $this->post(route('admin.categories.store'), ['name' => 'Makanan'])->assertRedirect(route('login'));
    }

    public function test_non_admins_can_not_manage_categories()
    {
        $buyer = User::factory()->create();

        $this->actingAs($buyer)
            ->get(route('admin.categories.index'))
            ->assertRedirect(route('home'));

        $this->actingAs($buyer)
            ->post(route('admin.categories.store'), ['name' => 'Makanan'])
            ->assertRedirect(route('home'));

        $this->assertDatabaseMissing('categories', ['name' => 'Makanan']);
    }

    public function test_admin_sees_the_category_list_with_product_counts()
    {
        $category = Category::factory()->create(['name' => 'Minuman', 'slug' => 'minuman']);
        Product::factory()->create(['category_id' => $category->id]);

        $this->actingAs($this->admin())
            ->get(route('admin.categories.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/categories')
                ->has('categories', 1)
                ->where('categories.0.name', 'Minuman')
                ->where('categories.0.slug', 'minuman')
                ->where('categories.0.product_count', 1));
    }

    public function test_a_category_can_be_created_with_a_generated_slug()
    {
        $this->actingAs($this->admin())
            ->post(route('admin.categories.store'), ['name' => 'Kopi Susu'])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->assertDatabaseHas('categories', [
            'name' => 'Kopi Susu',
            'slug' => 'kopi-susu',
        ]);
    }

    public function test_duplicate_category_names_are_rejected()
    {
        Category::factory()->create(['name' => 'Makanan', 'slug' => 'makanan']);

        $this->actingAs($this->admin())
            ->post(route('admin.categories.store'), ['name' => 'Makanan'])
            ->assertSessionHasErrors('name');
    }

    public function test_slugs_stay_unique_when_names_collide_after_slugging()
    {
        $this->actingAs($this->admin())
            ->post(route('admin.categories.store'), ['name' => 'Es Teh'])
            ->assertSessionHasNoErrors();

        $this->actingAs($this->admin())
            ->post(route('admin.categories.store'), ['name' => 'Es-Teh'])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('categories', ['name' => 'Es Teh', 'slug' => 'es-teh']);
        $this->assertDatabaseHas('categories', ['name' => 'Es-Teh', 'slug' => 'es-teh-2']);
    }

    public function test_a_category_can_be_renamed()
    {
        $category = Category::factory()->create(['name' => 'Makanan', 'slug' => 'makanan']);

        $this->actingAs($this->admin())
            ->patch(route('admin.categories.update', $category), ['name' => 'Makanan Ringan'])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'name' => 'Makanan Ringan',
            'slug' => 'makanan-ringan',
        ]);
    }

    public function test_rename_rejects_a_name_used_by_another_category()
    {
        Category::factory()->create(['name' => 'Makanan', 'slug' => 'makanan']);
        $category = Category::factory()->create(['name' => 'Minuman', 'slug' => 'minuman']);

        $this->actingAs($this->admin())
            ->patch(route('admin.categories.update', $category), ['name' => 'Makanan'])
            ->assertSessionHasErrors('name');
    }

    public function test_renaming_a_category_to_its_own_name_is_allowed()
    {
        $category = Category::factory()->create(['name' => 'Makanan', 'slug' => 'makanan']);

        $this->actingAs($this->admin())
            ->patch(route('admin.categories.update', $category), ['name' => 'Makanan'])
            ->assertSessionHasNoErrors();
    }

    public function test_an_unused_category_can_be_deleted()
    {
        $category = Category::factory()->create();

        $this->actingAs($this->admin())
            ->delete(route('admin.categories.destroy', $category))
            ->assertRedirect();

        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }

    public function test_a_category_used_by_a_product_can_not_be_deleted()
    {
        $category = Category::factory()->create();
        Product::factory()->create(['category_id' => $category->id]);

        $this->actingAs($this->admin())
            ->delete(route('admin.categories.destroy', $category))
            ->assertRedirect();

        $this->assertDatabaseHas('categories', ['id' => $category->id]);
    }

    public function test_a_category_used_by_a_trashed_product_can_not_be_deleted()
    {
        $category = Category::factory()->create();
        $product = Product::factory()->create(['category_id' => $category->id]);
        $product->delete();

        $this->actingAs($this->admin())
            ->delete(route('admin.categories.destroy', $category))
            ->assertRedirect();

        $this->assertDatabaseHas('categories', ['id' => $category->id]);
    }
}
