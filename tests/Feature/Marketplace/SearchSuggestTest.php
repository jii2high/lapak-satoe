<?php

namespace Tests\Feature\Marketplace;

use App\Models\Product;
use App\Models\Store;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SearchSuggestTest extends TestCase
{
    use RefreshDatabase;

    public function test_empty_query_returns_best_sellers_only()
    {
        $store = Store::factory()->open()->create();
        Product::factory()->create(['store_id' => $store->id, 'sold_count' => 5]);
        Product::factory()->create(['store_id' => $store->id, 'sold_count' => 9]);

        $response = $this->getJson(route('search.suggest'))->assertOk();

        $response->assertJsonCount(0, 'products')
            ->assertJsonCount(0, 'stores')
            ->assertJsonCount(2, 'best_sellers')
            ->assertJsonStructure([
                'products',
                'stores',
                'best_sellers' => [['id', 'name', 'price', 'store']],
            ]);
    }

    public function test_query_matches_visible_products_and_stores()
    {
        $store = Store::factory()->open()->create(['name' => 'Lapak Kopi']);
        Product::factory()->create(['store_id' => $store->id, 'name' => 'Kopi Hitam']);
        Product::factory()->create(['store_id' => $store->id, 'name' => 'Teh Hijau']);
        Product::factory()->inactive()->create(['store_id' => $store->id, 'name' => 'Kopi Mahal']);

        $this->getJson(route('search.suggest', ['q' => 'Kopi']))
            ->assertOk()
            ->assertJsonCount(1, 'products')
            ->assertJsonPath('products.0.name', 'Kopi Hitam')
            ->assertJsonCount(1, 'stores')
            ->assertJsonPath('stores.0.name', 'Lapak Kopi')
            ->assertJsonCount(0, 'best_sellers');
    }

    public function test_query_ignores_hidden_stores_and_their_products()
    {
        $closedStore = Store::factory()->create(['name' => 'Lapak Tutup']);
        Product::factory()->create(['store_id' => $closedStore->id, 'name' => 'Kopi Tutup']);

        $this->getJson(route('search.suggest', ['q' => 'Kopi']))
            ->assertOk()
            ->assertJsonCount(0, 'products')
            ->assertJsonCount(0, 'stores');
    }
}
