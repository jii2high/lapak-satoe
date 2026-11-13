<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Report;
use App\Models\SellerApplication;
use App\Models\Store;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Data dashboard seller dan admin (PRD FR-SEL-1, FR-ADM-1, TRD 8).
 */
class DashboardService
{
    /**
     * Rentang chart yang dikenal klien.
     *
     * @var array<int, string>
     */
    public const RANGES = ['7d', '1m', '6m', 'all'];

    /**
     * Batas bucket rentang "all" supaya satu halaman tidak menggambar ratusan titik.
     */
    private const MAX_BUCKETS = 24;

    /**
     * KPI, seri chart, dan pesanan terbaru satu lapak. BR-09: hanya order `completed`
     * yang dihitung sebagai pendapatan dan penjualan.
     *
     * @return array<string, mixed>
     */
    public function seller(Store $store, string $range): array
    {
        $span = $this->span($range, $store->orders()->min('created_at'));

        $completed = Order::query()
            ->where('store_id', $store->id)
            ->where('status', OrderStatus::Completed)
            ->where('completed_at', '>=', $span['from'])
            ->selectRaw($this->bucketExpression('completed_at', $span['unit']).' as bucket')
            ->selectRaw('COUNT(*) as orders')
            ->selectRaw('COALESCE(SUM(total), 0) as revenue')
            ->groupBy('bucket')
            ->get()
            ->keyBy('bucket');

        $created = Order::query()
            ->where('store_id', $store->id)
            ->where('created_at', '>=', $span['from'])
            ->selectRaw($this->bucketExpression('created_at', $span['unit']).' as bucket')
            ->selectRaw('COUNT(*) as orders')
            ->groupBy('bucket')
            ->get()
            ->keyBy('bucket');

        $series = array_map(fn (string $key): array => [
            'key' => $key,
            'revenue' => (int) ($completed[$key]->revenue ?? 0),
            'orders' => (int) ($completed[$key]->orders ?? 0),
            'new_orders' => (int) ($created[$key]->orders ?? 0),
        ], $span['keys']);

        return [
            'range' => $range,
            'kpi' => [
                'revenue' => array_sum(array_column($series, 'revenue')),
                'new_orders' => array_sum(array_column($series, 'new_orders')),
                'completed' => array_sum(array_column($series, 'orders')),
            ],
            'series' => $series,
            'recent' => $store->orders()
                ->with('buyer:id,username')
                ->latest('id')
                ->limit(5)
                ->get()
                ->map(fn (Order $order): array => [
                    'id' => $order->id,
                    'code' => $order->code,
                    'buyer' => $order->buyer?->username,
                    'status' => $order->status->value,
                    'total' => $order->total,
                    'created_at' => $order->created_at?->toIso8601String(),
                ])
                ->all(),
        ];
    }

    /**
     * KPI katalog, seri laporan, dan pendaftaran terbaru (PRD FR-ADM-1).
     *
     * @return array<string, mixed>
     */
    public function admin(string $range): array
    {
        $span = $this->span($range, Report::query()->min('created_at'));

        $reports = Report::query()
            ->where('created_at', '>=', $span['from'])
            ->selectRaw($this->bucketExpression('created_at', $span['unit']).' as bucket')
            ->selectRaw('COUNT(*) as reports')
            ->groupBy('bucket')
            ->get()
            ->keyBy('bucket');

        return [
            'range' => $range,
            'kpi' => [
                'stores' => Store::query()->count(),
                'users' => User::query()->count(),
                'reports_today' => Report::query()
                    ->where('created_at', '>=', now()->startOfDay())
                    ->count(),
            ],
            'series' => array_map(fn (string $key): array => [
                'key' => $key,
                'reports' => (int) ($reports[$key]->reports ?? 0),
            ], $span['keys']),
            'recent' => SellerApplication::query()
                ->with('user:id,username')
                ->latest('id')
                ->limit(5)
                ->get()
                ->map(fn (SellerApplication $application): array => [
                    'id' => $application->id,
                    'store_name' => $application->store_name,
                    'full_name' => $application->full_name,
                    'status' => $application->status->value,
                    'applicant' => $application->user?->username,
                    'created_at' => $application->created_at?->toIso8601String(),
                ])
                ->all(),
        ];
    }

    public function normalizeRange(mixed $range): string
    {
        return is_string($range) && in_array($range, self::RANGES, true) ? $range : '7d';
    }

    /**
     * Ekspresi bucket per driver: MySQL (produksi) memakai DATE_FORMAT, SQLite
     * (test) memakai strftime.
     */
    private function bucketExpression(string $column, string $unit): string
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            return $unit === 'month'
                ? "strftime('%Y-%m', {$column})"
                : "date({$column})";
        }

        return $unit === 'month'
            ? "DATE_FORMAT({$column}, '%Y-%m')"
            : "DATE({$column})";
    }

    /**
     * Jendela waktu seri: satuan bucket, daftar kunci, dan batas bawah query.
     *
     * @return array{unit: string, keys: array<int, string>, from: CarbonInterface}
     */
    private function span(string $range, ?string $earliest): array
    {
        $now = now();
        $unit = in_array($range, ['6m', 'all'], true) ? 'month' : 'day';

        $start = match ($range) {
            '1m' => $now->copy()->subDays(29)->startOfDay(),
            '6m' => $now->copy()->subMonthsNoOverflow(5)->startOfMonth(),
            'all' => $earliest === null
                ? $now->copy()->startOfMonth()
                : Carbon::parse($earliest)->startOfMonth(),
            default => $now->copy()->subDays(6)->startOfDay(),
        };

        $keys = $this->keys($start, $now, $unit);
        $first = $keys[0];

        return [
            'unit' => $unit,
            'keys' => $keys,
            'from' => $unit === 'month'
                ? Carbon::parse($first.'-01')->startOfMonth()
                : Carbon::parse($first)->startOfDay(),
        ];
    }

    /**
     * @return array<int, string>
     */
    private function keys(CarbonInterface $start, CarbonInterface $now, string $unit): array
    {
        $cursor = $unit === 'month' ? $start->copy()->startOfMonth() : $start->copy()->startOfDay();
        $last = $unit === 'month' ? $now->copy()->startOfMonth() : $now->copy()->startOfDay();
        $keys = [];

        while ($cursor->lessThanOrEqualTo($last)) {
            $keys[] = $cursor->format($unit === 'month' ? 'Y-m' : 'Y-m-d');
            $cursor = $unit === 'month' ? $cursor->addMonth() : $cursor->addDay();
        }

        return $unit === 'month' && count($keys) > self::MAX_BUCKETS
            ? array_slice($keys, -self::MAX_BUCKETS)
            : $keys;
    }
}
