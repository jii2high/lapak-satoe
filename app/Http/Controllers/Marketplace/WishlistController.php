<?php

namespace App\Http\Controllers\Marketplace;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Store;
use App\Support\CatalogPresenter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WishlistController extends Controller
{
    public function index(Request $request): Response
    {
        $wishlists = $request->user()->wishlists();

        $products = Product::query()
            ->publiclyVisible()
            ->with(['store', 'images'])
            ->whereIn('id', (clone $wishlists)->whereNotNull('product_id')->select('product_id'))
            ->latest()
            ->get()
            ->map(fn (Product $product) => CatalogPresenter::productCard($product));

        $stores = Store::query()
            ->publiclyVisible()
            ->withCount(['products' => fn ($query) => $query->publiclyVisible()])
            ->whereIn('id', (clone $wishlists)->whereNotNull('store_id')->select('store_id'))
            ->orderBy('name')
            ->get()
            ->map(fn (Store $store) => CatalogPresenter::storeCard($store));

        return Inertia::render('marketplace/wishlist', [
            'products' => $products,
            'stores' => $stores,
        ]);
    }

    public function toggleProduct(Request $request, Product $product): RedirectResponse
    {
        $product = Product::query()->publiclyVisible()->findOrFail($product->id);

        $existing = $request->user()->wishlists()
            ->where('product_id', $product->id)
            ->first();

        if ($existing === null) {
            $request->user()->wishlists()->create(['product_id' => $product->id]);
            $message = 'Produk ditambahkan ke wishlist.';
        } else {
            $existing->delete();
            $message = 'Produk dihapus dari wishlist.';
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => $message]);

        return back();
    }

    public function toggleStore(Request $request, Store $store): RedirectResponse
    {
        $store = Store::query()->publiclyVisible()->findOrFail($store->id);

        $existing = $request->user()->wishlists()
            ->where('store_id', $store->id)
            ->first();

        if ($existing === null) {
            $request->user()->wishlists()->create(['store_id' => $store->id]);
            $message = 'Lapak ditambahkan ke wishlist.';
        } else {
            $existing->delete();
            $message = 'Lapak dihapus dari wishlist.';
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => $message]);

        return back();
    }
}
