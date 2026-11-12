<?php

namespace App\Http\Controllers\Marketplace;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\Store;
use App\Support\CatalogPresenter;
use App\Support\HomePath;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function __invoke(Request $request): Response|RedirectResponse
    {
        $user = $request->user();

        if ($user !== null && $user->active_role !== Role::Buyer) {
            return redirect(HomePath::for($user));
        }

        $categories = Category::query()
            ->withCount(['products' => fn ($query) => $query->publiclyVisible()])
            ->orderBy('name')
            ->get(['id', 'name', 'slug']);

        $selectedCategory = Category::query()
            ->where('slug', trim((string) $request->query('category', '')))
            ->first(['name', 'slug']);

        if ($selectedCategory !== null) {
            $categoryProducts = Product::query()
                ->publiclyVisible()
                ->with(['store', 'images'])
                ->whereHas('category', fn ($query) => $query->where('slug', $selectedCategory->slug))
                ->latest()
                ->limit(12)
                ->get()
                ->map(fn (Product $product) => CatalogPresenter::productCard($product));

            return Inertia::render('marketplace/home', [
                'newProducts' => [],
                'bestSellers' => [],
                'stores' => [],
                'categories' => $categories,
                'selectedCategory' => $selectedCategory,
                'categoryProducts' => $categoryProducts,
            ]);
        }

        $newProducts = Product::query()
            ->publiclyVisible()
            ->with(['store', 'images'])
            ->latest()
            ->limit(12)
            ->get()
            ->map(fn (Product $product) => CatalogPresenter::productCard($product));

        $bestSellers = Product::query()
            ->publiclyVisible()
            ->with(['store', 'images'])
            ->orderByDesc('sold_count')
            ->orderByDesc('id')
            ->limit(12)
            ->get()
            ->map(fn (Product $product) => CatalogPresenter::productCard($product));

        $stores = Store::query()
            ->publiclyVisible()
            ->withCount(['products' => fn ($query) => $query->publiclyVisible()])
            ->latest()
            ->limit(6)
            ->get()
            ->map(fn (Store $store) => CatalogPresenter::storeCard($store));

        return Inertia::render('marketplace/home', [
            'newProducts' => $newProducts,
            'bestSellers' => $bestSellers,
            'categories' => $categories,
            'stores' => $stores,
            'selectedCategory' => null,
            'categoryProducts' => [],
        ]);
    }
}
