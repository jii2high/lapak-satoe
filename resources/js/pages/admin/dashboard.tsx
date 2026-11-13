import { Head, Link } from '@inertiajs/react';
import { KpiStrip } from '@/components/dashboard/kpi-strip';
import {
    RangeTabs,
    rangePeriod,
    type Range,
} from '@/components/dashboard/range-tabs';
import { StatsChart } from '@/components/dashboard/stats-chart';
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
import { useDashboardStats } from '@/hooks/use-dashboard-stats';
import { formatDate } from '@/lib/format';
import { dashboard } from '@/routes/admin';
import { index as applications } from '@/routes/admin/applications';
import { dashboard as adminStatsData } from '@/routes/admin/data';

type RecentApplication = {
    id: number;
    store_name: string;
    full_name: string;
    status: string;
    applicant: string | null;
    created_at: string | null;
};

type AdminStats = {
    range: string;
    kpi: { stores: number; users: number; reports_today: number };
    series: { key: string; reports: number }[];
    recent: RecentApplication[];
};

const applicationStatus: Record<string, { label: string; className: string }> =
    {
        pending: {
            label: 'Menunggu',
            className: 'bg-highlight-soft text-price',
        },
        approved: {
            label: 'Diterima',
            className: 'bg-secondary text-secondary-foreground',
        },
        rejected: {
            label: 'Ditolak',
            className: 'bg-muted text-muted-foreground',
        },
    };

const count = (value: number) => String(value);

function ApplicationStatusBadge({ status }: { status: string }) {
    const style = applicationStatus[status] ?? {
        label: status,
        className: 'bg-muted text-muted-foreground',
    };

    return (
        <span
            className={`inline-flex h-6 items-center px-2 text-xs font-semibold ${style.className}`}
        >
            {style.label}
        </span>
    );
}

export default function AdminDashboard({
    stats: initial,
}: {
    stats: AdminStats;
}) {
    const { stats, range, setRange, loading, failed, retry } =
        useDashboardStats(initial, adminStatsData.url());

    return (
        <>
            <Head title="Dashboard admin" />

            <div className="flex flex-col gap-6 px-4 py-6 md:px-6">
                <div>
                    <h1 className="font-display text-2xl font-bold">
                        Dashboard
                    </h1>
                    <p className="mt-1 text-sm text-muted-foreground">
                        Ringkasan katalog, pengguna, dan moderasi.
                    </p>
                </div>

                <section className="flex flex-col gap-3">
                    <h2 className="font-display text-lg font-semibold">
                        Ringkasan katalog
                    </h2>
                    <KpiStrip
                        items={[
                            {
                                label: 'Total lapak',
                                value: String(stats.kpi.stores),
                            },
                            {
                                label: 'Total pengguna',
                                value: String(stats.kpi.users),
                            },
                            {
                                label: 'Laporan hari ini',
                                value: String(stats.kpi.reports_today),
                            },
                        ]}
                    />
                </section>

                <section className="flex flex-col gap-4 border border-border bg-card p-4">
                    <RangeTabs value={range as Range} onChange={setRange} />

                    <div className="flex items-center justify-between gap-2">
                        <p className="text-sm text-muted-foreground">
                            Laporan masuk per {rangePeriod(range)}
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
                            dataKey="reports"
                            name="Laporan"
                            formatValue={count}
                            formatTick={count}
                        />
                    )}
                </section>

                <section className="flex flex-col gap-3">
                    <div className="flex flex-wrap items-center justify-between gap-3">
                        <h2 className="font-display text-lg font-semibold">
                            Pendaftaran terbaru
                        </h2>
                        <Button asChild variant="outline">
                            <Link href={applications()}>Lihat antrean</Link>
                        </Button>
                    </div>

                    {stats.recent.length === 0 ? (
                        <div className="border border-border bg-card px-4 py-10 text-center">
                            <h3 className="font-display text-base font-semibold">
                                Belum ada pendaftaran
                            </h3>
                            <p className="mx-auto mt-2 max-w-prose text-sm text-muted-foreground">
                                Pendaftaran seller dari pengguna akan muncul di
                                sini.
                            </p>
                        </div>
                    ) : (
                        <>
                            <div className="hidden border border-border bg-card md:block">
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>Nama lengkap</TableHead>
                                            <TableHead>Lapak</TableHead>
                                            <TableHead>Pengguna</TableHead>
                                            <TableHead>Status</TableHead>
                                            <TableHead>Diajukan</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {stats.recent.map((application) => (
                                            <TableRow key={application.id}>
                                                <TableCell className="font-medium">
                                                    {application.full_name}
                                                </TableCell>
                                                <TableCell>
                                                    {application.store_name}
                                                </TableCell>
                                                <TableCell className="text-muted-foreground">
                                                    {application.applicant ===
                                                    null
                                                        ? '-'
                                                        : `@${application.applicant}`}
                                                </TableCell>
                                                <TableCell>
                                                    <ApplicationStatusBadge
                                                        status={
                                                            application.status
                                                        }
                                                    />
                                                </TableCell>
                                                <TableCell className="text-muted-foreground">
                                                    {application.created_at ===
                                                    null
                                                        ? '-'
                                                        : formatDate(
                                                              application.created_at,
                                                          )}
                                                </TableCell>
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                </Table>
                            </div>

                            <ul className="grid gap-3 md:hidden">
                                {stats.recent.map((application) => (
                                    <li
                                        key={application.id}
                                        className="border border-border bg-card p-4"
                                    >
                                        <div className="flex items-start justify-between gap-3">
                                            <div className="min-w-0">
                                                <p className="truncate font-medium">
                                                    {application.full_name}
                                                </p>
                                                <p className="truncate text-sm text-muted-foreground">
                                                    {application.store_name}
                                                </p>
                                            </div>
                                            <ApplicationStatusBadge
                                                status={application.status}
                                            />
                                        </div>
                                        <p className="mt-2 text-xs text-muted-foreground">
                                            {application.created_at === null
                                                ? 'Tanggal tidak diketahui'
                                                : formatDate(
                                                      application.created_at,
                                                  )}
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

AdminDashboard.layout = {
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: dashboard(),
        },
    ],
};
