<?php

namespace App\Http\Controllers\Marketplace;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Support\CatalogPresenter;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    public function show(Request $request, Product $product): Response
    {
        $product = Product::query()
            ->publiclyVisible()
            ->with(['store', 'images', 'category'])
            ->findOrFail($product->id);

        $related = Product::query()
            ->publiclyVisible()
            ->with(['store', 'images'])
            ->where('store_id', $product->store_id)
            ->whereKeyNot($product->id)
            ->latest()
            ->limit(6)
            ->get()
            ->map(fn (Product $related) => CatalogPresenter::productCard($related));

        return Inertia::render('marketplace/product', [
            'product' => CatalogPresenter::productDetail($product),
            'related' => $related,
        ]);
    }
}
