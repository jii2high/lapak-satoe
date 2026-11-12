<?php

namespace Tests\Feature\Marketplace;

use App\Models\Category;
use App\Models\Product;
use App\Models\Store;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ExploreTest extends TestCase
{
    use RefreshDatabase;

    public function test_explore_lists_visible_products_only()
    {
        $store = Store::factory()->open()->create();
        Product::factory()->create(['store_id' => $store->id]);
        Product::factory()->inactive()->create(['store_id' => $store->id]);
        Product::factory()->suspended()->create(['store_id' => $store->id]);

        $closedStore = Store::factory()->create();
        Product::factory()->create(['store_id' => $closedStore->id]);

        $this->get(route('explore'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('marketplace/explore')
                ->has('products.data', 1)
                ->where('products.total', 1));
    }

    public function test_explore_searches_by_name()
    {
        $store = Store::factory()->open()->create();
        Product::factory()->create(['store_id' => $store->id, 'name' => 'Kopi Susu']);
        Product::factory()->create(['store_id' => $store->id, 'name' => 'Teh Manis']);

        $this->get(route('explore', ['q' => 'Kopi']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('products.data', 1)
                ->where('products.data.0.name', 'Kopi Susu')
                ->where('filters.q', 'Kopi'));
    }

    public function test_explore_filters_by_category_and_price()
    {
        $store = Store::factory()->open()->create();
        $otherStore = Store::factory()->open()->create();
        $category = Category::factory()->create();

        Product::factory()->create([
            'store_id' => $store->id,
            'category_id' => $category->id,
            'name' => 'Kopi Murah',
            'price' => 10000,
        ]);
        Product::factory()->create([
            'store_id' => $store->id,
            'category_id' => Category::factory()->create()->id,
            'name' => 'Kopi Mahal',
            'price' => 90000,
        ]);
        Product::factory()->create([
            'store_id' => $otherStore->id,
            'category_id' => $category->id,
            'name' => 'Kopi Toko Lain',
            'price' => 10000,
        ]);

        $this->get(route('explore', ['category' => $category->slug]))
            ->assertInertia(fn (Assert $page) => $page->has('products.data', 2));

        $secondCategory = Category::factory()->create();
        Product::factory()->create([
            'store_id' => $otherStore->id,
            'category_id' => $secondCategory->id,
            'name' => 'Teh Manis',
            'price' => 12000,
        ]);

        $this->get(route('explore', ['category' => $category->slug.','.$secondCategory->slug]))
            ->assertInertia(fn (Assert $page) => $page
                ->has('products.data', 3)
                ->where('filters.category', [$category->slug, $secondCategory->slug]));

        $this->get(route('explore', ['min' => 50000]))
            ->assertInertia(fn (Assert $page) => $page
                ->has('products.data', 1)
                ->where('products.data.0.name', 'Kopi Mahal'));

        $this->get(route('explore', ['max' => 50000]))
            ->assertInertia(fn (Assert $page) => $page->has('products.data', 3));
    }

    public function test_explore_limits_results_to_the_requested_type()
    {
        $store = Store::factory()->open()->create(['name' => 'Lapak Kopi']);
        Product::factory()->create(['store_id' => $store->id, 'name' => 'Kopi Susu']);
        Store::factory()->open()->create(['name' => 'Lapak Teh']);

        $this->get(route('explore', ['type' => 'produk']))
            ->assertInertia(fn (Assert $page) => $page
                ->has('products.data', 1)
                ->has('stores', 0));

        $this->get(route('explore', ['type' => 'lapak']))
            ->assertInertia(fn (Assert $page) => $page
                ->where('products', null)
                ->has('stores', 2));

        $this->get(route('explore', ['type' => 'tidak-valid']))
            ->assertInertia(fn (Assert $page) => $page
                ->where('filters.type', 'keduanya')
                ->has('products.data', 1)
                ->has('stores', 2));
    }

    public function test_explore_filters_stores_by_query_and_category()
    {
        $category = Category::factory()->create();
        $matching = Store::factory()->open()->create(['name' => 'Lapak Kopi']);
        Product::factory()->create([
            'store_id' => $matching->id,
            'category_id' => $category->id,
        ]);
        $empty = Store::factory()->open()->create(['name' => 'Lapak Teh']);
        Product::factory()->create([
            'store_id' => $empty->id,
            'category_id' => Category::factory()->create()->id,
        ]);

        $this->get(route('explore', ['type' => 'lapak', 'q' => 'Kopi']))
            ->assertInertia(fn (Assert $page) => $page
                ->has('stores', 1)
                ->where('stores.0.name', 'Lapak Kopi'));

        $this->get(route('explore', ['type' => 'lapak', 'category' => $category->slug]))
            ->assertInertia(fn (Assert $page) => $page
                ->has('stores', 1)
                ->where('stores.0.name', 'Lapak Kopi'));
    }

    public function test_explore_only_accepts_supported_per_page_values()
    {
        $store = Store::factory()->open()->create();
        Product::factory()->count(3)->create(['store_id' => $store->id]);

        $this->get(route('explore', ['per_page' => 60]))
            ->assertInertia(fn (Assert $page) => $page->where('perPage', 60));

        $this->get(route('explore', ['per_page' => 5]))
            ->assertInertia(fn (Assert $page) => $page->where('perPage', 20));
    }

    public function test_explore_paginates_twenty_products_by_default()
    {
        $store = Store::factory()->open()->create();
        Product::factory()->count(21)->create(['store_id' => $store->id]);

        $this->get(route('explore'))
            ->assertInertia(fn (Assert $page) => $page
                ->has('products.data', 20)
                ->where('products.total', 21)
                ->where('products.last_page', 2));
    }

    public function test_explore_returns_an_empty_state_when_nothing_matches()
    {
        Product::factory()->count(2)->create();

        $this->get(route('explore', ['q' => 'tidak-ada-produk-ini']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('products.data', 0)
                ->where('products.total', 0));
    }
}
