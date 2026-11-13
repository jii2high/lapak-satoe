<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Http\Requests\Seller\BulkProductRequest;
use App\Http\Requests\Seller\StoreProductRequest;
use App\Http\Requests\Seller\UpdateProductRequest;
use App\Models\Category;
use App\Models\Product;
use App\Models\Report;
use App\Models\Store;
use App\Support\ImageStorage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class ProductController extends Controller
{
    public function index(Request $request): Response
    {
        $store = $this->currentStore($request);
        $query = trim((string) $request->query('q', ''));
        $status = $request->query('status');
        $status = is_string($status) && in_array($status, ['active', 'inactive', 'suspended'], true)
            ? $status
            : 'all';
        $pattern = '%'.addcslashes($query, '%_\\').'%';

        $products = $store->products()
            ->with(['images', 'category'])
            ->when($query !== '', fn (Builder $sub) => $sub->where('name', 'like', $pattern))
            ->when($status === 'active', fn (Builder $sub) => $sub
                ->where('is_active', true)
                ->where('is_suspended', false))
            ->when($status === 'inactive', fn (Builder $sub) => $sub->where('is_active', false))
            ->when($status === 'suspended', fn (Builder $sub) => $sub->where('is_suspended', true))
            ->latest()
            ->paginate(20)
            ->withQueryString()
            ->through(fn (Product $product) => $this->presentRow($product));

        return Inertia::render('seller/products/index', [
            'products' => $products,
            'filters' => [
                'q' => $query,
                'status' => $status,
            ],
        ]);
    }

    public function create(Request $request): Response
    {
        Gate::authorize('create', Product::class);

        return Inertia::render('seller/products/create', [
            'categories' => $this->categories(),
        ]);
    }

    public function store(StoreProductRequest $request): RedirectResponse
    {
        Gate::authorize('create', Product::class);

        $store = $this->currentStore($request);
        $images = $request->file('images');
        $images = is_array($images) ? $images : [];
        $storedPaths = [];

        try {
            DB::transaction(function () use ($request, $store, $images, &$storedPaths): void {
                $product = $store->products()->create([
                    'category_id' => $request->integer('category_id'),
                    'name' => (string) $request->input('name'),
                    'description' => $request->input('description'),
                    'price' => $request->integer('price'),
                    'stock' => $request->integer('stock'),
                    'is_active' => $request->boolean('is_active'),
                ]);

                $this->storeImages($product, $images, 0, $storedPaths);
            });
        } catch (Throwable $exception) {
            $this->deleteFiles($storedPaths);

            throw $exception;
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Produk ditambahkan.']);

        return redirect()->route('seller.products.index');
    }

    public function edit(Request $request, Product $product): Response
    {
        Gate::authorize('update', $product);

        $product->load(['images', 'category']);

        return Inertia::render('seller/products/edit', [
            'product' => $this->presentDetail($product),
            'categories' => $this->categories(),
        ]);
    }

    public function update(UpdateProductRequest $request, Product $product): RedirectResponse
    {
        $images = $request->file('images');
        $images = is_array($images) ? $images : [];
        $removedIds = $request->input('removed_image_ids');
        $removedIds = is_array($removedIds)
            ? array_map(fn ($id) => (int) $id, $removedIds)
            : [];
        $newPaths = [];
        $removedPaths = [];

        try {
            DB::transaction(function () use ($request, $product, $images, $removedIds, &$newPaths, &$removedPaths): void {
                $locked = Product::query()
                    ->whereKey($product->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $stock = $request->integer('stock');

                if ($stock < $locked->reserved) {
                    throw ValidationException::withMessages([
                        'stock' => 'Stok tidak boleh lebih kecil dari jumlah yang sedang dipesan ('.$locked->reserved.').',
                    ]);
                }

                $isActive = $request->boolean('is_active');

                if ($locked->is_suspended && $isActive && ! $locked->is_active) {
                    throw ValidationException::withMessages([
                        'is_active' => 'Produk dinonaktifkan admin dan tidak bisa diaktifkan.',
                    ]);
                }

                $locked->update([
                    'category_id' => $request->integer('category_id'),
                    'name' => (string) $request->input('name'),
                    'description' => $request->input('description'),
                    'price' => $request->integer('price'),
                    'stock' => $stock,
                    'is_active' => $isActive,
                ]);

                $removed = $locked->images()->whereIn('id', $removedIds)->get();

                foreach ($removed as $image) {
                    $removedPaths[] = $image->path;
                    $image->delete();
                }

                $sort = ((int) $locked->images()->max('sort')) + 1;
                $this->storeImages($locked, $images, $sort, $newPaths);
            });
        } catch (Throwable $exception) {
            $this->deleteFiles($newPaths);

            throw $exception;
        }

        $this->deleteFiles($removedPaths);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Produk diperbarui.']);

        return redirect()->route('seller.products.index');
    }

    public function destroy(Request $request, Product $product): RedirectResponse
    {
        Gate::authorize('delete', $product);

        $product->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Produk dihapus.']);

        return back();
    }

    public function bulk(BulkProductRequest $request): RedirectResponse
    {
        Gate::authorize('create', Product::class);

        $store = $this->currentStore($request);
        $action = (string) $request->input('action');
        $ids = $request->input('ids');
        $ids = is_array($ids) ? array_map(fn ($id) => (int) $id, $ids) : [];
        $products = $store->products()->whereIn('id', $ids)->get();
        $count = 0;

        if ($action === 'delete') {
            foreach ($products as $product) {
                $product->delete();
                $count++;
            }
        } else {
            foreach ($products as $product) {
                if ($action === 'activate' && $product->is_suspended) {
                    continue;
                }

                $product->update(['is_active' => $action === 'activate']);
                $count++;
            }
        }

        $message = match ($action) {
            'activate' => $count.' produk diaktifkan.',
            'deactivate' => $count.' produk dinonaktifkan.',
            'delete' => $count.' produk dihapus.',
            default => 'Tidak ada produk yang diubah.',
        };

        Inertia::flash('toast', [
            'type' => $count > 0 ? 'success' : 'info',
            'message' => $count > 0 ? $message : 'Tidak ada produk yang diubah.',
        ]);

        return back();
    }

    private function currentStore(Request $request): Store
    {
        return $request->user()->store()->firstOrFail();
    }

    /**
     * @return array<int, array{id: int, name: string}>
     */
    private function categories(): array
    {
        return Category::query()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Category $category) => [
                'id' => $category->id,
                'name' => $category->name,
            ])
            ->all();
    }

    /**
     * @param  array<int, UploadedFile>  $images
     * @param  array<int, string>  $paths
     */
    private function storeImages(Product $product, array $images, int $startSort, array &$paths): void
    {
        $maxDimension = (int) config('lapak.files.product_image.max_dimension');

        foreach ($images as $image) {
            $path = ImageStorage::store($image, "products/{$product->store_id}", $maxDimension);
            $paths[] = $path;

            $product->images()->create([
                'path' => $path,
                'sort' => $startSort++,
            ]);
        }
    }

    /**
     * @param  array<int, string>  $paths
     */
    private function deleteFiles(array $paths): void
    {
        foreach ($paths as $path) {
            ImageStorage::delete($path);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function presentRow(Product $product): array
    {
        $image = $product->images->first();

        return [
            'id' => $product->id,
            'name' => $product->name,
            'category' => $product->category->name,
            'price' => $product->price,
            'stock' => $product->stock,
            'reserved' => $product->reserved,
            'is_active' => $product->is_active,
            'is_suspended' => $product->is_suspended,
            'image_url' => $image === null
                ? null
                : Storage::disk('public')->url($image->path),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function presentDetail(Product $product): array
    {
        return [
            'id' => $product->id,
            'name' => $product->name,
            'description' => $product->description,
            'category_id' => $product->category_id,
            'price' => $product->price,
            'stock' => $product->stock,
            'reserved' => $product->reserved,
            'is_active' => $product->is_active,
            'is_suspended' => $product->is_suspended,
            'suspension_note' => $product->is_suspended
                ? Report::query()
                    ->where('product_id', $product->id)
                    ->whereNotNull('reported_note')
                    ->latest('handled_at')
                    ->value('reported_note')
                : null,
            'images' => $product->images
                ->map(fn ($image) => [
                    'id' => $image->id,
                    'url' => Storage::disk('public')->url($image->path),
                ])
                ->values()
                ->all(),
        ];
    }
}
