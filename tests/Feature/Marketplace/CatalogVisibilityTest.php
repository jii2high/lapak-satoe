<?php

namespace Tests\Feature\Marketplace;

use App\Models\Category;
use App\Models\Product;
use App\Models\Store;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CatalogVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_lists_only_publicly_visible_products_and_stores()
    {
        $store = Store::factory()->open()->create();
        $visible = Product::factory()->create([
            'store_id' => $store->id,
            'name' => 'Produk Tampil',
        ]);

        Product::factory()->inactive()->create(['store_id' => $store->id]);
        Product::factory()->suspended()->create(['store_id' => $store->id]);

        $closedStore = Store::factory()->create();
        Product::factory()->create(['store_id' => $closedStore->id]);

        $suspendedStore = Store::factory()->open()->suspended()->create();
        Product::factory()->create(['store_id' => $suspendedStore->id]);

        $this->get(route('home'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('marketplace/home')
                ->has('newProducts', 1)
                ->where('newProducts.0.id', $visible->id)
                ->has('bestSellers', 1)
                ->has('stores', 1)
                ->where('stores.0.id', $store->id));
    }

    public function test_home_filters_products_by_category()
    {
        $store = Store::factory()->open()->create();
        $category = Category::factory()->create(['name' => 'Minuman']);
        $otherCategory = Category::factory()->create(['name' => 'Makanan']);

        $matching = Product::factory()->create([
            'store_id' => $store->id,
            'category_id' => $category->id,
            'name' => 'Kopi Susu',
        ]);
        Product::factory()->create([
            'store_id' => $store->id,
            'category_id' => $otherCategory->id,
            'name' => 'Nasi Goreng',
        ]);
        Product::factory()->inactive()->create([
            'store_id' => $store->id,
            'category_id' => $category->id,
        ]);

        $this->get(route('home', ['category' => $category->slug]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('marketplace/home')
                ->where('selectedCategory.slug', $category->slug)
                ->has('categoryProducts', 1)
                ->where('categoryProducts.0.id', $matching->id)
                ->has('newProducts', 0)
                ->has('bestSellers', 0)
                ->has('stores', 0));
    }

    public function test_home_ignores_an_unknown_category_filter()
    {
        $store = Store::factory()->open()->create();
        Product::factory()->create(['store_id' => $store->id]);

        $this->get(route('home', ['category' => 'kategori-tidak-ada']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('selectedCategory', null)
                ->has('categoryProducts', 0)
                ->has('newProducts', 1)
                ->has('bestSellers', 1)
                ->has('stores', 1));
    }

    public function test_home_category_filter_hides_products_from_closed_stores()
    {
        $category = Category::factory()->create();
        $openStore = Store::factory()->open()->create();
        Product::factory()->create([
            'store_id' => $openStore->id,
            'category_id' => $category->id,
        ]);

        $closedStore = Store::factory()->create();
        Product::factory()->create([
            'store_id' => $closedStore->id,
            'category_id' => $category->id,
        ]);

        $this->get(route('home', ['category' => $category->slug]))
            ->assertInertia(fn (Assert $page) => $page
                ->has('categoryProducts', 1));
    }

    public function test_hidden_products_return_not_found_on_the_detail_page()
    {
        $closedStore = Store::factory()->create();
        $productFromClosedStore = Product::factory()->create(['store_id' => $closedStore->id]);

        $inactive = Product::factory()->inactive()->create();
        $suspended = Product::factory()->suspended()->create();

        $this->get(route('products.show', $inactive))->assertNotFound();
        $this->get(route('products.show', $suspended))->assertNotFound();
        $this->get(route('products.show', $productFromClosedStore))->assertNotFound();
    }

    public function test_product_detail_shows_visible_product_with_related_items()
    {
        $store = Store::factory()->open()->create();
        $product = Product::factory()->create([
            'store_id' => $store->id,
            'name' => 'Kopi Susu',
        ]);
        Product::factory()->create(['store_id' => $store->id, 'name' => 'Teh Manis']);
        Product::factory()->inactive()->create(['store_id' => $store->id]);

        $this->get(route('products.show', $product))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('marketplace/product')
                ->where('product.id', $product->id)
                ->where('product.name', 'Kopi Susu')
                ->has('related', 1)
                ->where('related.0.name', 'Teh Manis'));
    }

    public function test_store_page_returns_not_found_for_closed_or_suspended_stores()
    {
        $closed = Store::factory()->create();
        $suspended = Store::factory()->open()->suspended()->create();

        $this->get(route('stores.show', $closed->slug))->assertNotFound();
        $this->get(route('stores.show', $suspended->slug))->assertNotFound();
    }

    public function test_store_page_lists_only_visible_products()
    {
        $store = Store::factory()->open()->create();
        $visible = Product::factory()->create(['store_id' => $store->id]);
        Product::factory()->inactive()->create(['store_id' => $store->id]);

        $this->get(route('stores.show', $store->slug))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('marketplace/store')
                ->where('store.id', $store->id)
                ->where('store.product_count', 1)
                ->has('products.data', 1)
                ->where('products.data.0.id', $visible->id));
    }
}
