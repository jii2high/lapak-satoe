<?php

namespace App\Http\Controllers\Marketplace;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Store;
use App\Support\CatalogPresenter;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StoreController extends Controller
{
    public function show(Request $request, Store $store): Response
    {
        $store = Store::query()
            ->publiclyVisible()
            ->withCount(['products' => fn ($query) => $query->publiclyVisible()])
            ->where('slug', $store->slug)
            ->firstOrFail();

        $previousName = $store->nameHistories()
            ->where('changed_at', '>=', now()->subDays(7))
            ->latest('changed_at')
            ->value('old_name');

        $products = Product::query()
            ->publiclyVisible()
            ->with(['store', 'images'])
            ->where('store_id', $store->id)
            ->latest()
            ->paginate(20)
            ->withQueryString()
            ->through(fn (Product $product) => CatalogPresenter::productCard($product));

        return Inertia::render('marketplace/store', [
            'store' => [
                ...CatalogPresenter::storeCard($store),
                'previous_name' => $previousName,
            ],
            'products' => $products,
        ]);
    }
}
