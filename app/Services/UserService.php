<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\StoreStatus;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Aksi admin atas akun pengguna (PRD FR-ADM-4, BR-21, TRD 3).
 */
class UserService
{
    /**
     * Soft delete akun: katalog lapaknya ikut disembunyikan dan email/username
     * dikosongkan supaya bisa dipakai ulang. Ditolak selama masih ada order aktif.
     *
     * @throws ValidationException
     */
    public function delete(User $user): void
    {
        if ($this->hasActiveOrders($user)) {
            throw ValidationException::withMessages([
                'user' => 'Pengguna masih punya pesanan aktif. Selesaikan atau batalkan dulu.',
            ]);
        }

        DB::transaction(function () use ($user): void {
            $store = $user->store;

            if ($store !== null) {
                $store->products()->delete();
                $store->update(['status' => StoreStatus::Closed]);
            }

            $this->forgetSessions($user);

            $user->forceFill([
                'username' => Str::limit('deleted_'.$user->id.'_'.$user->username, 30, ''),
                'email' => 'deleted_'.$user->id.'_'.$user->email,
            ])->save();

            $user->delete();
        });
    }

    /**
     * Nonaktifkan atau aktifkan akun. Menonaktifkan sekaligus memutus sesi yang
     * sedang berjalan, karena driver sesi memakai tabel `sessions`.
     */
    public function setActive(User $user, bool $active): void
    {
        $user->update(['is_active' => $active]);

        if (! $active) {
            $this->forgetSessions($user);
        }
    }

    private function hasActiveOrders(User $user): bool
    {
        $statuses = OrderStatus::active();

        $asBuyer = Order::query()
            ->where('buyer_id', $user->id)
            ->whereIn('status', $statuses)
            ->exists();

        if ($asBuyer) {
            return true;
        }

        return Order::query()
            ->whereIn('status', $statuses)
            ->whereHas('store', fn (Builder $query) => $query->where('user_id', $user->id))
            ->exists();
    }

    private function forgetSessions(User $user): void
    {
        DB::table(config('session.table', 'sessions'))
            ->where('user_id', $user->id)
            ->delete();
    }
}
