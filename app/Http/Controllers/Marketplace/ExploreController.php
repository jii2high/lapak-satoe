<?php

namespace App\Http\Controllers\Marketplace;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\Store;
use App\Support\CatalogPresenter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ExploreController extends Controller
{
    /** @var array<int, string> */
    private const TYPES = ['produk', 'lapak', 'keduanya'];

    public function index(Request $request): Response
    {
        $type = $request->query('type');
        $type = is_string($type) && in_array($type, self::TYPES, true) ? $type : 'keduanya';

        $query = trim((string) $request->query('q', ''));
        $pattern = '%'.addcslashes($query, '%_\\').'%';

        $filters = [
            'q' => $query,
            'type' => $type,
            'category' => $this->categorySlugs($request),
            'min' => $request->integer('min') ?: null,
            'max' => $request->integer('max') ?: null,
        ];

        $perPage = in_array($request->integer('per_page'), config('lapak.pagination.per_page_options'), true)
            ? $request->integer('per_page')
            : 20;

        $products = $type === 'lapak'
            ? null
            : Product::query()
                ->publiclyVisible()
                ->with(['store', 'images'])
                ->when($query !== '', fn (Builder $sub) => $sub->where('name', 'like', $pattern))
                ->when($filters['category'] !== [], fn (Builder $sub) => $sub->whereHas('category', fn ($category) => $category->whereIn('slug', $filters['category'])))
                ->when($filters['min'] !== null, fn (Builder $sub) => $sub->where('price', '>=', $filters['min']))
                ->when($filters['max'] !== null, fn (Builder $sub) => $sub->where('price', '<=', $filters['max']))
                ->latest()
                ->paginate($perPage)
                ->withQueryString()
                ->through(fn (Product $product) => CatalogPresenter::productCard($product));

        $stores = $type === 'produk'
            ? collect()
            : Store::query()
                ->publiclyVisible()
                ->withCount(['products' => fn ($sub) => $sub->publiclyVisible()])
                ->when($query !== '', fn (Builder $sub) => $sub->where('name', 'like', $pattern))
                ->when($filters['category'] !== [], fn (Builder $sub) => $sub->whereHas('products', fn ($product) => $product->publiclyVisible()->whereHas('category', fn ($category) => $category->whereIn('slug', $filters['category']))))
                ->when($filters['min'] !== null, fn (Builder $sub) => $sub->whereHas('products', fn ($product) => $product->publiclyVisible()->where('price', '>=', $filters['min'])))
                ->when($filters['max'] !== null, fn (Builder $sub) => $sub->whereHas('products', fn ($product) => $product->publiclyVisible()->where('price', '<=', $filters['max'])))
                ->orderBy('name')
                ->get()
                ->map(fn (Store $store) => CatalogPresenter::storeCard($store));

        $categories = Category::query()
            ->withCount(['products' => fn ($sub) => $sub->publiclyVisible()])
            ->orderBy('name')
            ->get(['id', 'name', 'slug']);

        return Inertia::render('marketplace/explore', [
            'products' => $products,
            'stores' => $stores,
            'categories' => $categories,
            'filters' => $filters,
            'perPage' => $perPage,
        ]);
    }

    /**
     * @return array<int, string>
     */
    private function categorySlugs(Request $request): array
    {
        $category = $request->input('category');

        if (is_string($category)) {
            $category = explode(',', $category);
        }

        if (! is_array($category)) {
            return [];
        }

        return array_values(array_filter(
            array_map(fn ($slug) => trim((string) $slug), $category),
            fn (string $slug) => $slug !== '',
        ));
    }
}
