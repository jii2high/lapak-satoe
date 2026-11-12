<?php

namespace App\Http\Controllers\Marketplace;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Store;
use App\Support\CatalogPresenter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function suggest(Request $request): JsonResponse
    {
        $query = trim((string) $request->query('q', ''));

        if ($query === '') {
            $bestSellers = Product::query()
                ->publiclyVisible()
                ->with(['store', 'images'])
                ->orderByDesc('sold_count')
                ->orderByDesc('id')
                ->limit(config('lapak.search.product_limit'))
                ->get()
                ->map(fn (Product $product) => CatalogPresenter::productCard($product));

            return response()->json([
                'products' => [],
                'stores' => [],
                'best_sellers' => $bestSellers,
            ]);
        }

        $pattern = '%'.addcslashes($query, '%_\\').'%';

        $products = Product::query()
            ->publiclyVisible()
            ->with(['store', 'images'])
            ->where('name', 'like', $pattern)
            ->orderByDesc('sold_count')
            ->limit(config('lapak.search.product_limit'))
            ->get()
            ->map(fn (Product $product) => CatalogPresenter::productCard($product));

        $stores = Store::query()
            ->publiclyVisible()
            ->where('name', 'like', $pattern)
            ->orderBy('name')
            ->limit(config('lapak.search.store_limit'))
            ->get()
            ->map(fn (Store $store) => CatalogPresenter::storeCard($store));

        return response()->json([
            'products' => $products,
            'stores' => $stores,
            'best_sellers' => [],
        ]);
    }
}
