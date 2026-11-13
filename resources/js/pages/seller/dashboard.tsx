import { Head } from '@inertiajs/react';
import { useState } from 'react';
import { KpiStrip } from '@/components/dashboard/kpi-strip';
import {
    RangeTabs,
    rangePeriod,
    type Range,
} from '@/components/dashboard/range-tabs';
import { StatsChart } from '@/components/dashboard/stats-chart';
import { OrderStatusBadge } from '@/components/marketplace/order-status-badge';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { ToggleGroup, ToggleGroupItem } from '@/components/ui/toggle-group';
import { useDashboardStats } from '@/hooks/use-dashboard-stats';
import { compactRupiah, formatDate, formatRupiah } from '@/lib/format';
import { dashboard } from '@/routes/seller';
import { dashboard as sellerStatsData } from '@/routes/seller/data';
import type { OrderStatus } from '@/types';

type SeriesPoint = {
    key: string;
    revenue: number;
    orders: number;
    new_orders: number;
};

type RecentOrder = {
    id: number;
    code: string;
    buyer: string | null;
    status: OrderStatus;
    total: number;
    created_at: string | null;
};

type SellerStats = {
    range: string;
    kpi: { revenue: number; new_orders: number; completed: number };
    series: SeriesPoint[];
    recent: RecentOrder[];
};

const metrics = [
    { value: 'revenue', label: 'Penjualan' },
    { value: 'orders', label: 'Pesanan selesai' },
] as const;

type Metric = (typeof metrics)[number]['value'];

const count = (value: number) => String(value);

export default function SellerDashboard({
    stats: initial,
}: {
    stats: SellerStats;
}) {
    const { stats, range, setRange, loading, failed, retry } =
        useDashboardStats(initial, sellerStatsData.url());
    const [metric, setMetric] = useState<Metric>('revenue');

    const metricLabel =
        metrics.find((item) => item.value === metric)?.label ?? 'Penjualan';
    const formatValue = metric === 'revenue' ? formatRupiah : count;
    const formatTick = metric === 'revenue' ? compactRupiah : count;

    return (
        <>
            <Head title="Dashboard" />

            <div className="flex flex-col gap-6 px-4 py-6 md:px-6">
                <div>
                    <h1 className="font-display text-2xl font-bold">
                        Dashboard
                    </h1>
                    <p className="mt-1 text-sm text-muted-foreground">
                        Ringkasan penjualan lapakmu.
                    </p>
                </div>

                <section className="flex flex-col gap-3">
                    <h2 className="font-display text-lg font-semibold">
                        Ringkasan {rangePeriod(range)}
                    </h2>
                    <KpiStrip
                        items={[
                            {
                                label: 'Pendapatan',
                                value: formatRupiah(stats.kpi.revenue),
                            },
                            {
                                label: 'Pesanan baru',
                                value: String(stats.kpi.new_orders),
                            },
                            {
                                label: 'Pesanan selesai',
                                value: String(stats.kpi.completed),
                            },
                        ]}
                    />
                </section>

                <section className="flex flex-col gap-4 border border-border bg-card p-4">
                    <div className="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                        <RangeTabs value={range as Range} onChange={setRange} />

                        <ToggleGroup
                            type="single"
                            variant="outline"
                            value={metric}
                            aria-label="Pilih data chart"
                            onValueChange={(next) =>
                                next !== '' && setMetric(next as Metric)
                            }
                        >
                            {metrics.map((item) => (
                                <ToggleGroupItem
                                    key={item.value}
                                    value={item.value}
                                    className="h-11 px-3"
                                >
                                    {item.label}
                                </ToggleGroupItem>
                            ))}
                        </ToggleGroup>
                    </div>

                    <div className="flex items-center justify-between gap-2">
                        <p className="text-sm text-muted-foreground">
                            {metricLabel} per {rangePeriod(range)}
                        </p>
                        {loading && (
                            <span
                                role="status"
                                className="flex items-center gap-2 text-xs text-muted-foreground"
                            >
                                <Spinner />
                                Memuat
                            </span>
                        )}
                    </div>

                    {failed ? (
                        <div className="flex flex-col items-start gap-3 border border-dashed border-border p-4">
                            <p className="text-sm text-muted-foreground">
                                Data chart gagal dimuat. Periksa koneksi lalu
                                coba lagi.
                            </p>
                            <Button variant="outline" onClick={retry}>
                                Coba lagi
                            </Button>
                        </div>
                    ) : (
                        <StatsChart
                            data={stats.series}
                            dataKey={metric}
                            name={metricLabel}
                            formatValue={formatValue}
                            formatTick={formatTick}
                        />
                    )}
                </section>

                <section className="flex flex-col gap-3">
                    <h2 className="font-display text-lg font-semibold">
                        Pesanan terbaru
                    </h2>

                    {stats.recent.length === 0 ? (
                        <div className="border border-border bg-card px-4 py-10 text-center">
                            <h3 className="font-display text-base font-semibold">
                                Belum ada pesanan
                            </h3>
                            <p className="mx-auto mt-2 max-w-prose text-sm text-muted-foreground">
                                Pesanan masuk dari pembeli akan muncul di sini.
                            </p>
                        </div>
                    ) : (
                        <>
                            <div className="hidden border border-border bg-card md:block">
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>Kode</TableHead>
                                            <TableHead>Pembeli</TableHead>
                                            <TableHead>Status</TableHead>
                                            <TableHead className="text-right">
                                                Total
                                            </TableHead>
                                            <TableHead>Dibuat</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {stats.recent.map((order) => (
                                            <TableRow key={order.id}>
                                                <TableCell className="font-medium tabular-nums">
                                                    {order.code}
                                                </TableCell>
                                                <TableCell className="text-muted-foreground">
                                                    {order.buyer === null
                                                        ? '-'
                                                        : `@${order.buyer}`}
                                                </TableCell>
                                                <TableCell>
                                                    <OrderStatusBadge
                                                        status={order.status}
                                                        viewer="seller"
                                                    />
                                                </TableCell>
                                                <TableCell className="text-right tabular-nums">
                                                    {formatRupiah(order.total)}
                                                </TableCell>
                                                <TableCell className="text-muted-foreground">
                                                    {order.created_at === null
                                                        ? '-'
                                                        : formatDate(
                                                              order.created_at,
                                                          )}
                                                </TableCell>
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                </Table>
                            </div>

                            <ul className="grid gap-3 md:hidden">
                                {stats.recent.map((order) => (
                                    <li
                                        key={order.id}
                                        className="border border-border bg-card p-4"
                                    >
                                        <div className="flex items-start justify-between gap-3">
                                            <div className="min-w-0">
                                                <p className="font-medium tabular-nums">
                                                    {order.code}
                                                </p>
                                                <p className="truncate text-sm text-muted-foreground">
                                                    {order.buyer === null
                                                        ? 'Pembeli tidak diketahui'
                                                        : `@${order.buyer}`}
                                                </p>
                                            </div>
                                            <OrderStatusBadge
                                                status={order.status}
                                                viewer="seller"
                                            />
                                        </div>
                                        <p className="mt-2 text-sm font-semibold tabular-nums">
                                            {formatRupiah(order.total)}
                                        </p>
                                    </li>
                                ))}
                            </ul>
                        </>
                    )}
                </section>
            </div>
        </>
    );
}

SellerDashboard.layout = {
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: dashboard(),
        },
    ],
};
