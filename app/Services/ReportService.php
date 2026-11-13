<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\ReportStatus;
use App\Models\Order;
use App\Models\Product;
use App\Models\Report;
use App\Models\Store;
use App\Models\User;
use App\Notifications\ReportReviewed;
use App\Notifications\ReportTargetSuspended;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Laporan pelanggaran (TRD 6.7).
 */
class ReportService
{
    public function __construct(private readonly OrderService $orders) {}

    /**
     * BR-18, BR-23. Laporan order ditulis bersama perpindahan order ke `disputed`.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws ValidationException
     */
    public function create(User $reporter, array $data): Report
    {
        $target = $this->resolveTarget($reporter, $data);

        return DB::transaction(function () use ($reporter, $data, $target): Report {
            $report = Report::query()->create([
                'reporter_id' => $reporter->id,
                'reported_user_id' => $target['reported_user_id'],
                'product_id' => $target['product']?->id,
                'store_id' => $target['store']->id,
                'order_id' => $target['order']?->id,
                'violation_type' => $data['violation_type'],
                'description' => $data['description'],
                'status' => ReportStatus::New,
            ]);

            if ($target['order'] !== null) {
                $this->orders->dispute($target['order']);
            }

            return $report;
        });
    }

    /**
     * BR-18, BR-24. Catatan admin hanya untuk pemilik objek terlapor.
     *
     * @throws ValidationException
     */
    public function process(Report $report, User $admin, string $note): void
    {
        $this->handle($report, $admin, ReportStatus::Processed, $note);
    }

    /**
     * BR-18. Penolakan hanya mengisi catatan pelapor, tidak ada objek yang dinonaktifkan.
     *
     * @throws ValidationException
     */
    public function reject(Report $report, User $admin, string $note): void
    {
        $this->handle($report, $admin, ReportStatus::Rejected, $note);
    }

    /**
     * BR-24.
     *
     * @throws ValidationException
     */
    public function restore(Report $report): void
    {
        if ($report->status !== ReportStatus::Processed) {
            throw ValidationException::withMessages([
                'status' => 'Hanya laporan yang sudah diproses bisa dipulihkan.',
            ]);
        }

        if ($report->restored_at !== null) {
            throw ValidationException::withMessages([
                'status' => 'Objek laporan ini sudah dipulihkan.',
            ]);
        }

        DB::transaction(function () use ($report): void {
            $this->setSuspended($report, false);

            $report->update(['restored_at' => now()]);
        });
    }

    /**
     * @throws ValidationException
     */
    private function handle(Report $report, User $admin, ReportStatus $status, string $note): void
    {
        $processed = $status === ReportStatus::Processed;

        DB::transaction(function () use ($report, $admin, $status, $note, $processed): void {
            $locked = Report::query()
                ->whereKey($report->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($locked->status !== ReportStatus::New) {
                throw ValidationException::withMessages([
                    'status' => 'Laporan ini sudah ditangani admin lain. Muat ulang halaman.',
                ]);
            }

            $locked->update([
                'status' => $status,
                'handled_by' => $admin->id,
                'handled_at' => now(),
                'reporter_note' => $processed ? null : $note,
                'reported_note' => $processed ? $note : null,
            ]);

            if ($processed) {
                $this->setSuspended($locked, true);
            }

            $order = $locked->order;

            if ($order !== null && $order->status === OrderStatus::Disputed) {
                $this->orders->resolveDispute($order);
            }
        });

        $report->refresh();

        $report->reporter?->notify(new ReportReviewed($report));

        if ($processed) {
            $report->reportedUser?->notify(new ReportTargetSuspended($report));
        }
    }

    /**
     * BR-24: hanya objek yang dilaporkan yang dinonaktifkan.
     *
     * shortcut: memulihkan satu laporan selalu melepas suspend objek itu, walau ada
     * laporan lain yang masih diproses untuk objek yang sama. Ganti dengan hitung
     * laporan aktif per objek kalau kasus itu muncul.
     */
    private function setSuspended(Report $report, bool $suspended): void
    {
        if ($report->product_id !== null) {
            Product::query()->whereKey($report->product_id)->update(['is_suspended' => $suspended]);

            return;
        }

        Store::query()->whereKey($report->store_id)->update(['is_suspended' => $suspended]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{store: Store, product: Product|null, order: Order|null, reported_user_id: int}
     *
     * @throws ValidationException
     */
    private function resolveTarget(User $reporter, array $data): array
    {
        $order = isset($data['order_id']) ? Order::query()->find((int) $data['order_id']) : null;

        if (isset($data['order_id']) && $order === null) {
            throw ValidationException::withMessages(['order_id' => 'Pesanan tidak ditemukan.']);
        }

        if ($order !== null) {
            if ($order->buyer_id !== $reporter->id) {
                throw ValidationException::withMessages(['order_id' => 'Pesanan ini bukan milikmu.']);
            }

            $product = isset($data['product_id']) ? Product::query()->find((int) $data['product_id']) : null;

            if ($product === null) {
                return [
                    'store' => $order->store,
                    'product' => null,
                    'order' => $order,
                    'reported_user_id' => $order->store->user_id,
                ];
            }

            if (! $order->items()->where('product_id', $product->id)->exists()) {
                throw ValidationException::withMessages([
                    'product_id' => 'Produk itu tidak ada di pesanan ini.',
                ]);
            }

            return [
                'store' => $order->store,
                'product' => $product,
                'order' => $order,
                'reported_user_id' => $order->store->user_id,
            ];
        }

        $product = isset($data['product_id']) ? Product::query()->find((int) $data['product_id']) : null;
        $store = isset($data['store_id']) ? Store::query()->find((int) $data['store_id']) : null;

        if (($product === null) === ($store === null)) {
            throw ValidationException::withMessages([
                'store_id' => 'Pilih satu objek yang dilaporkan: produk atau lapak.',
            ]);
        }

        if ($product !== null) {
            if ($product->store->user_id === $reporter->id) {
                throw ValidationException::withMessages([
                    'store_id' => 'Produk atau lapak sendiri tidak bisa dilaporkan.',
                ]);
            }

            return [
                'store' => $product->store,
                'product' => $product,
                'order' => null,
                'reported_user_id' => $product->store->user_id,
            ];
        }

        $reportedUserId = $store->user_id;

        if ($reportedUserId === $reporter->id) {
            throw ValidationException::withMessages([
                'store_id' => 'Produk atau lapak sendiri tidak bisa dilaporkan.',
            ]);
        }

        return [
            'store' => $store,
            'product' => $product,
            'order' => null,
            'reported_user_id' => $reportedUserId,
        ];
    }
}
