<?php

namespace App\Http\Controllers\Seller;

use App\Enums\StoreStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Seller\UpdateStoreRequest;
use App\Models\Store;
use App\Services\StoreService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class StoreController extends Controller
{
    public function edit(Request $request): Response
    {
        $store = $this->store($request);

        Gate::authorize('update', $store);

        return Inertia::render('seller/store', [
            'store' => [
                'id' => $store->id,
                'name' => $store->name,
                'slug' => $store->slug,
                'description' => $store->description,
                'whatsapp' => $store->whatsapp,
                'logo_url' => $store->logo_path === null
                    ? null
                    : Storage::disk('public')->url($store->logo_path),
                'banner_url' => $store->banner_path === null
                    ? null
                    : Storage::disk('public')->url($store->banner_path),
                'status' => $store->status->value,
                'is_suspended' => $store->is_suspended,
            ],
            'previous_name' => $store->nameHistories()
                ->where('changed_at', '>=', now()->subDays(7))
                ->latest('changed_at')
                ->value('old_name'),
            'active_order_count' => $store->orders()
                ->whereIn('status', ['unconfirmed', 'pending'])
                ->count(),
            'active_product_count' => $store->products()
                ->where('is_active', true)
                ->where('is_suspended', false)
                ->count(),
        ]);
    }

    public function update(UpdateStoreRequest $request, StoreService $stores): RedirectResponse
    {
        $store = $this->store($request);

        Gate::authorize('update', $store);

        $stores->update(
            $store,
            $request->payload(),
            $request->file('logo'),
            $request->file('banner'),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Pengaturan lapak disimpan.']);

        return back();
    }

    public function toggle(Request $request, StoreService $stores): RedirectResponse
    {
        $store = $this->store($request);

        Gate::authorize('toggle', $store);

        try {
            $status = $stores->toggle($store);
        } catch (ValidationException $exception) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => $exception->validator->errors()->first('status'),
            ]);

            return back();
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => match ($status) {
                StoreStatus::Open => 'Lapak dibuka.',
                StoreStatus::Closing => 'Lapak akan ditutup setelah pesanan aktif selesai.',
                StoreStatus::Closed => 'Lapak ditutup.',
            },
        ]);

        return back();
    }

    private function store(Request $request): Store
    {
        return $request->user()->store()->firstOrFail();
    }
}
