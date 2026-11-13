import { Head, router, useForm } from '@inertiajs/react';
import { Eye, Flag, Printer, RotateCcw, Search } from 'lucide-react';
import { useEffect, useState } from 'react';
import InputError from '@/components/input-error';
import { Pagination } from '@/components/pagination';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Spinner } from '@/components/ui/spinner';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { Textarea } from '@/components/ui/textarea';
import { useDebounce } from '@/hooks/use-debounce';
import {
    index as reports,
    pdf as reportPdf,
    process as processReport,
    reject as rejectReport,
    restore as restoreReport,
} from '@/routes/admin/reports';
import type { Paginator } from '@/types';

type Report = {
    id: number;
    violation_label: string;
    description: string;
    status: string;
    target: string;
    target_kind: 'product' | 'store';
    reporter: string | null;
    reported_user: string | null;
    order_code: string | null;
    reporter_note: string | null;
    reported_note: string | null;
    handled_by: string | null;
    handled_at: string | null;
    restored_at: string | null;
    created_at: string | null;
};

type Props = {
    reports: Paginator<Report>;
    filters: {
        q: string;
        status: string;
    };
};

const statusFilters = [
    { value: 'all', label: 'Semua status' },
    { value: 'new', label: 'Baru' },
    { value: 'processed', label: 'Diproses' },
    { value: 'rejected', label: 'Ditolak' },
];

const statusStyles: Record<
    string,
    { label: string; badge: string; dot: string }
> = {
    new: {
        label: 'Baru',
        badge: 'border-highlight bg-highlight-soft text-price',
        dot: 'bg-highlight',
    },
    processed: {
        label: 'Diproses',
        badge: 'border-primary bg-secondary text-secondary-foreground',
        dot: 'bg-primary',
    },
    rejected: {
        label: 'Ditolak',
        badge: 'border-border bg-muted text-muted-foreground',
        dot: 'bg-muted-foreground',
    },
};

function StatusBadge({
    status,
    restored,
}: {
    status: string;
    restored: boolean;
}) {
    const style = statusStyles[status] ?? {
        label: status,
        badge: 'border-border bg-muted text-muted-foreground',
        dot: 'bg-muted-foreground',
    };

    return (
        <Badge variant="outline" className={`gap-1.5 ${style.badge}`}>
            <span aria-hidden className={`size-2 ${style.dot}`} />
            {restored ? 'Dipulihkan' : style.label}
        </Badge>
    );
}

function formatDate(value: string | null): string {
    if (value === null) {
        return '-';
    }

    return new Date(value).toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    });
}

export default function AdminReports({ reports: rows, filters }: Props) {
    const [detail, setDetail] = useState<Report | null>(null);
    const [processing, setProcessing] = useState<Report | null>(null);
    const [rejecting, setRejecting] = useState<Report | null>(null);
    const [restoring, setRestoring] = useState<Report | null>(null);
    const [busy, setBusy] = useState(false);
    const [search, setSearch] = useState(filters.q);
    const debounced = useDebounce(search);

    const processForm = useForm({ note: '' });
    const rejectForm = useForm({ note: '' });
    const filtered = filters.q !== '' || filters.status !== 'all';

    useEffect(() => {
        if (debounced === filters.q) {
            return;
        }

        router.get(
            reports.url({
                query: {
                    ...(debounced === '' ? {} : { q: debounced }),
                    ...(filters.status === 'all'
                        ? {}
                        : { status: filters.status }),
                },
            }),
            { preserveState: true, preserveScroll: true, replace: true },
        );
    }, [debounced, filters.q, filters.status]);

    const changeStatus = (status: string) => {
        router.get(
            reports.url({
                query: {
                    ...(filters.q === '' ? {} : { q: filters.q }),
                    ...(status === 'all' ? {} : { status }),
                },
            }),
            { preserveState: true, preserveScroll: true },
        );
    };

    const openProcess = (report: Report) => {
        processForm.reset();
        processForm.clearErrors();
        setDetail(null);
        setProcessing(report);
    };

    const openReject = (report: Report) => {
        rejectForm.reset();
        rejectForm.clearErrors();
        setDetail(null);
        setRejecting(report);
    };

    const openRestore = (report: Report) => {
        setDetail(null);
        setRestoring(report);
    };

    const confirmProcess = () => {
        if (processing === null) {
            return;
        }

        processForm.post(processReport.url(processing.id), {
            preserveScroll: true,
            onSuccess: () => setProcessing(null),
        });
    };

    const confirmReject = () => {
        if (rejecting === null) {
            return;
        }

        rejectForm.post(rejectReport.url(rejecting.id), {
            preserveScroll: true,
            onSuccess: () => setRejecting(null),
        });
    };

    const confirmRestore = () => {
        if (restoring === null) {
            return;
        }

        setBusy(true);
        router.post(
            restoreReport.url(restoring.id),
            {},
            {
                preserveScroll: true,
                onSuccess: () => setRestoring(null),
                onFinish: () => setBusy(false),
            },
        );
    };

    return (
        <>
            <Head title="Pelaporan" />

            <div className="flex flex-col gap-4 px-4 py-6 md:px-6">
                <div>
                    <h1 className="font-display text-2xl font-bold">
                        Pelaporan
                    </h1>
                    <p className="mt-1 text-sm text-muted-foreground">
                        Tinjau laporan produk dan lapak. Proses menonaktifkan
                        objek terlapor, tolak menutup laporan tanpa tindakan.
                    </p>
                </div>

                <div className="flex flex-col gap-2 sm:flex-row">
                    <div className="relative flex-1">
                        <Search
                            aria-hidden
                            className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                        />
                        <Input
                            value={search}
                            onChange={(event) => setSearch(event.target.value)}
                            aria-label="Cari laporan"
                            placeholder="Cari objek, pengguna, atau kode pesanan"
                            className="pl-9"
                        />
                    </div>

                    <Select value={filters.status} onValueChange={changeStatus}>
                        <SelectTrigger
                            aria-label="Filter status laporan"
                            className="w-full sm:w-56"
                        >
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            {statusFilters.map((filter) => (
                                <SelectItem
                                    key={filter.value}
                                    value={filter.value}
                                >
                                    {filter.label}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </div>

                {rows.data.length === 0 ? (
                    <div className="border border-border bg-card px-4 py-10 text-center">
                        <Flag
                            aria-hidden
                            className="mx-auto size-8 text-muted-foreground"
                        />
                        <h2 className="mt-3 font-display text-lg font-semibold">
                            {filtered
                                ? 'Tidak ada laporan cocok'
                                : 'Belum ada laporan'}
                        </h2>
                        <p className="mx-auto mt-2 max-w-prose text-sm text-muted-foreground">
                            {filtered
                                ? 'Ubah kata kunci atau filter status.'
                                : 'Laporan dari pembeli muncul di halaman ini.'}
                        </p>
                    </div>
                ) : (
                    <>
                        <div className="hidden border border-border bg-card md:block">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Objek</TableHead>
                                        <TableHead>Pemilik</TableHead>
                                        <TableHead>Jenis</TableHead>
                                        <TableHead>Deskripsi</TableHead>
                                        <TableHead>Status</TableHead>
                                        <TableHead className="text-right">
                                            Aksi
                                        </TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {rows.data.map((report) => (
                                        <TableRow key={report.id}>
                                            <TableCell className="font-medium">
                                                {report.target}
                                            </TableCell>
                                            <TableCell className="text-muted-foreground">
                                                {report.reported_user === null
                                                    ? '-'
                                                    : `@${report.reported_user}`}
                                            </TableCell>
                                            <TableCell>
                                                {report.violation_label}
                                            </TableCell>
                                            <TableCell className="max-w-[24rem] text-muted-foreground">
                                                <span className="line-clamp-2">
                                                    {report.description}
                                                </span>
                                            </TableCell>
                                            <TableCell>
                                                <StatusBadge
                                                    status={report.status}
                                                    restored={
                                                        report.restored_at !==
                                                        null
                                                    }
                                                />
                                            </TableCell>
                                            <TableCell className="text-right">
                                                <Button
                                                    variant="ghost"
                                                    size="sm"
                                                    onClick={() =>
                                                        setDetail(report)
                                                    }
                                                >
                                                    <Eye aria-hidden />
                                                    Detail
                                                </Button>
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        </div>

                        <ul className="grid gap-3 md:hidden">
                            {rows.data.map((report) => (
                                <li
                                    key={report.id}
                                    className="border border-border bg-card p-4"
                                >
                                    <div className="flex items-start justify-between gap-3">
                                        <div className="min-w-0">
                                            <p className="truncate font-medium">
                                                {report.target}
                                            </p>
                                            <p className="truncate text-sm text-muted-foreground">
                                                {report.violation_label}
                                            </p>
                                            <p className="mt-1 text-xs text-muted-foreground">
                                                {report.reported_user === null
                                                    ? 'Pemilik tidak diketahui'
                                                    : `@${report.reported_user}`}
                                            </p>
                                        </div>

                                        <StatusBadge
                                            status={report.status}
                                            restored={
                                                report.restored_at !== null
                                            }
                                        />
                                    </div>

                                    <p className="mt-2 line-clamp-2 text-sm text-muted-foreground">
                                        {report.description}
                                    </p>

                                    <Button
                                        variant="secondary"
                                        size="sm"
                                        className="mt-3"
                                        onClick={() => setDetail(report)}
                                    >
                                        <Eye aria-hidden />
                                        Detail
                                    </Button>
                                </li>
                            ))}
                        </ul>

                        <Pagination
                            links={rows.links}
                            lastPage={rows.last_page}
                        />
                    </>
                )}
            </div>

            <Dialog
                open={detail !== null}
                onOpenChange={(open) => !open && setDetail(null)}
            >
                <DialogContent className="sm:max-w-[640px]">
                    {detail && (
                        <>
                            <DialogHeader>
                                <DialogTitle className="font-display text-xl">
                                    {detail.target}
                                </DialogTitle>
                                <DialogDescription>
                                    Dilaporkan {formatDate(detail.created_at)}
                                    {detail.order_code === null
                                        ? ` oleh @${detail.reporter ?? 'pembeli'}`
                                        : ` oleh @${detail.reporter ?? 'pembeli'} pada pesanan ${detail.order_code}`}
                                    .
                                </DialogDescription>
                            </DialogHeader>

                            <dl className="grid gap-3 text-sm sm:grid-cols-2">
                                <div>
                                    <dt className="text-muted-foreground">
                                        Objek terlapor
                                    </dt>
                                    <dd className="font-medium">
                                        {detail.target}
                                    </dd>
                                </div>
                                <div>
                                    <dt className="text-muted-foreground">
                                        Pemilik
                                    </dt>
                                    <dd className="font-medium">
                                        {detail.reported_user === null
                                            ? '-'
                                            : `@${detail.reported_user}`}
                                    </dd>
                                </div>
                                <div>
                                    <dt className="text-muted-foreground">
                                        Jenis pelanggaran
                                    </dt>
                                    <dd className="font-medium">
                                        {detail.violation_label}
                                    </dd>
                                </div>
                                <div>
                                    <dt className="text-muted-foreground">
                                        Status
                                    </dt>
                                    <dd className="mt-1">
                                        <StatusBadge
                                            status={detail.status}
                                            restored={
                                                detail.restored_at !== null
                                            }
                                        />
                                    </dd>
                                </div>
                            </dl>

                            <div className="grid gap-1 text-sm">
                                <p className="text-muted-foreground">
                                    Deskripsi pelapor
                                </p>
                                <p className="whitespace-pre-line">
                                    {detail.description}
                                </p>
                            </div>

                            {detail.reported_note !== null && (
                                <div className="grid gap-1 text-sm">
                                    <p className="text-muted-foreground">
                                        Catatan untuk pemilik
                                    </p>
                                    <p className="whitespace-pre-line">
                                        {detail.reported_note}
                                    </p>
                                </div>
                            )}

                            {detail.reporter_note !== null && (
                                <div className="grid gap-1 text-sm">
                                    <p className="text-muted-foreground">
                                        Catatan untuk pelapor
                                    </p>
                                    <p className="whitespace-pre-line">
                                        {detail.reporter_note}
                                    </p>
                                </div>
                            )}

                            {detail.handled_at !== null && (
                                <p className="text-xs text-muted-foreground">
                                    Ditangani @{detail.handled_by ?? 'admin'}{' '}
                                    pada {formatDate(detail.handled_at)}.
                                </p>
                            )}

                            <DialogFooter className="flex-wrap gap-2 sm:justify-between">
                                <div className="flex flex-wrap gap-2">
                                    <Button asChild variant="outline" size="sm">
                                        <a
                                            href={`${reportPdf.url(detail.id)}?print=1`}
                                            target="_blank"
                                            rel="noreferrer"
                                        >
                                            <Printer aria-hidden />
                                            Cetak
                                        </a>
                                    </Button>
                                    <Button asChild variant="outline" size="sm">
                                        <a href={reportPdf.url(detail.id)}>
                                            Simpan PDF
                                        </a>
                                    </Button>

                                    {detail.status === 'processed' &&
                                        detail.restored_at === null && (
                                            <Button
                                                type="button"
                                                variant="outline"
                                                size="sm"
                                                onClick={() =>
                                                    openRestore(detail)
                                                }
                                            >
                                                <RotateCcw aria-hidden />
                                                Pulihkan
                                            </Button>
                                        )}
                                </div>

                                {detail.status === 'new' && (
                                    <div className="flex flex-wrap gap-2">
                                        <Button
                                            type="button"
                                            variant="outline"
                                            size="sm"
                                            onClick={() => openReject(detail)}
                                        >
                                            Tolak
                                        </Button>
                                        <Button
                                            type="button"
                                            variant="destructive"
                                            size="sm"
                                            onClick={() => openProcess(detail)}
                                        >
                                            Proses
                                        </Button>
                                    </div>
                                )}
                            </DialogFooter>
                        </>
                    )}
                </DialogContent>
            </Dialog>

            <Dialog
                open={processing !== null}
                onOpenChange={(open) => !open && setProcessing(null)}
            >
                <DialogContent className="sm:max-w-[480px]">
                    {processing && (
                        <>
                            <DialogHeader>
                                <DialogTitle className="font-display text-xl">
                                    Proses laporan ini?
                                </DialogTitle>
                                <DialogDescription>
                                    {processing.target} akan dinonaktifkan dan
                                    tidak tampil di katalog. Pelapor dan pemilik
                                    akan diberi tahu.
                                </DialogDescription>
                            </DialogHeader>

                            <div className="grid gap-2">
                                <Label htmlFor="process-note">
                                    Catatan untuk pemilik
                                </Label>
                                <Textarea
                                    id="process-note"
                                    value={processForm.data.note}
                                    maxLength={1000}
                                    onChange={(event) =>
                                        processForm.setData(
                                            'note',
                                            event.target.value,
                                        )
                                    }
                                />
                                <InputError message={processForm.errors.note} />
                            </div>

                            <DialogFooter>
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={() => setProcessing(null)}
                                >
                                    Batal
                                </Button>
                                <Button
                                    type="button"
                                    variant="destructive"
                                    disabled={
                                        processForm.processing ||
                                        processForm.data.note.trim() === ''
                                    }
                                    onClick={confirmProcess}
                                >
                                    {processForm.processing && <Spinner />}
                                    Proses dan nonaktifkan
                                </Button>
                            </DialogFooter>
                        </>
                    )}
                </DialogContent>
            </Dialog>

            <Dialog
                open={rejecting !== null}
                onOpenChange={(open) => !open && setRejecting(null)}
            >
                <DialogContent className="sm:max-w-[480px]">
                    {rejecting && (
                        <>
                            <DialogHeader>
                                <DialogTitle className="font-display text-xl">
                                    Tolak laporan ini?
                                </DialogTitle>
                                <DialogDescription>
                                    {rejecting.target} tetap aktif. Pelapor
                                    menerima alasanmu.
                                </DialogDescription>
                            </DialogHeader>

                            <div className="grid gap-2">
                                <Label htmlFor="reject-note">
                                    Catatan untuk pelapor
                                </Label>
                                <Textarea
                                    id="reject-note"
                                    value={rejectForm.data.note}
                                    maxLength={1000}
                                    onChange={(event) =>
                                        rejectForm.setData(
                                            'note',
                                            event.target.value,
                                        )
                                    }
                                />
                                <InputError message={rejectForm.errors.note} />
                            </div>

                            <DialogFooter>
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={() => setRejecting(null)}
                                >
                                    Batal
                                </Button>
                                <Button
                                    type="button"
                                    variant="destructive"
                                    disabled={
                                        rejectForm.processing ||
                                        rejectForm.data.note.trim() === ''
                                    }
                                    onClick={confirmReject}
                                >
                                    {rejectForm.processing && <Spinner />}
                                    Tolak laporan
                                </Button>
                            </DialogFooter>
                        </>
                    )}
                </DialogContent>
            </Dialog>

            <Dialog
                open={restoring !== null}
                onOpenChange={(open) => !open && setRestoring(null)}
            >
                <DialogContent className="sm:max-w-[480px]">
                    {restoring && (
                        <>
                            <DialogHeader>
                                <DialogTitle className="font-display text-xl">
                                    Pulihkan {restoring.target}?
                                </DialogTitle>
                                <DialogDescription>
                                    Objek aktif kembali dan tampil di katalog
                                    bila lapaknya buka. Pemilik tidak menerima
                                    pemberitahuan tambahan.
                                </DialogDescription>
                            </DialogHeader>

                            <DialogFooter>
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={() => setRestoring(null)}
                                >
                                    Batal
                                </Button>
                                <Button
                                    type="button"
                                    disabled={busy}
                                    onClick={confirmRestore}
                                >
                                    {busy && <Spinner />}
                                    Pulihkan
                                </Button>
                            </DialogFooter>
                        </>
                    )}
                </DialogContent>
            </Dialog>
        </>
    );
}

AdminReports.layout = {
    breadcrumbs: [
        {
            title: 'Pelaporan',
            href: reports(),
        },
    ],
};
