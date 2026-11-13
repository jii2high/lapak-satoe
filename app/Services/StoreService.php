<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\StoreStatus;
use App\Models\Store;
use App\Support\ImageStorage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

class StoreService
{
    /**
     * @param  array{name: string, description: string|null, whatsapp: string}  $data
     */
    public function update(Store $store, array $data, ?UploadedFile $logo, ?UploadedFile $banner): void
    {
        $oldLogo = $store->logo_path;
        $oldBanner = $store->banner_path;
        $newLogo = $logo === null ? null : ImageStorage::store($logo, "stores/{$store->id}");
        $newBanner = $banner === null ? null : ImageStorage::store($banner, "stores/{$store->id}");

        try {
            DB::transaction(function () use ($store, $data, $newLogo, $newBanner): void {
                if ($store->name !== $data['name']) {
                    $store->nameHistories()->create([
                        'old_name' => $store->name,
                        'changed_at' => now(),
                    ]);
                }

                $store->update([
                    'name' => $data['name'],
                    'description' => $data['description'],
                    'whatsapp' => $data['whatsapp'],
                    'logo_path' => $newLogo ?? $store->logo_path,
                    'banner_path' => $newBanner ?? $store->banner_path,
                ]);
            });
        } catch (Throwable $exception) {
            ImageStorage::delete($newLogo);
            ImageStorage::delete($newBanner);

            throw $exception;
        }

        if ($newLogo !== null) {
            ImageStorage::delete($oldLogo);
        }

        if ($newBanner !== null) {
            ImageStorage::delete($oldBanner);
        }
    }

    /**
     * @throws ValidationException
     */
    public function open(Store $store): void
    {
        if ($store->is_suspended) {
            throw ValidationException::withMessages([
                'status' => 'Lapak dinonaktifkan admin. Hubungi admin untuk memulihkan.',
            ]);
        }

        if ($store->products()->where('is_active', true)->where('is_suspended', false)->doesntExist()) {
            throw ValidationException::withMessages([
                'status' => 'Tambahkan minimal satu produk aktif sebelum membuka lapak.',
            ]);
        }

        $store->update(['status' => StoreStatus::Open]);
    }

    public function close(Store $store): StoreStatus
    {
        if ($store->status !== StoreStatus::Open) {
            return $store->status;
        }

        $status = $this->activeOrderCount($store) > 0
            ? StoreStatus::Closing
            : StoreStatus::Closed;

        $store->update(['status' => $status]);

        return $status;
    }

    public function settle(Store $store): StoreStatus
    {
        if ($store->status === StoreStatus::Closing && $this->activeOrderCount($store) === 0) {
            $store->update(['status' => StoreStatus::Closed]);
        }

        return $store->status;
    }

    /**
     * @throws ValidationException
     */
    public function toggle(Store $store): StoreStatus
    {
        return match ($store->status) {
            StoreStatus::Open => $this->close($store),
            StoreStatus::Closed => tap(StoreStatus::Open, fn () => $this->open($store)),
            StoreStatus::Closing => $this->settle($store),
        };
    }

    public function activeOrderCount(Store $store): int
    {
        return $store->orders()
            ->whereIn('status', [OrderStatus::Unconfirmed, OrderStatus::Pending])
            ->count();
    }
}
