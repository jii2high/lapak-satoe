import { Head, router, useForm } from '@inertiajs/react';
import { Check, ClipboardList, Eye, FileText, X } from 'lucide-react';
import { useState } from 'react';
import InputError from '@/components/input-error';
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
import { Label } from '@/components/ui/label';
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
import {
    approve as approveApplication,
    index as applications,
    reject as rejectApplication,
} from '@/routes/admin/applications';

type Application = {
    id: number;
    full_name: string;
    nis: string;
    whatsapp: string;
    store_name: string;
    description: string;
    status: string;
    reject_note: string | null;
    submitted_at: string | null;
    applicant: {
        display_name: string;
        username: string;
    };
    categories: string[];
    product_photos: string[];
    student_card_url: string;
};

type Props = {
    applications: Application[];
};

const statusStyles: Record<
    string,
    { label: string; badge: string; dot: string }
> = {
    pending: {
        label: 'Menunggu tinjauan',
        badge: 'border-highlight bg-highlight-soft text-price',
        dot: 'bg-highlight',
    },
    approved: {
        label: 'Diterima',
        badge: 'border-primary bg-secondary text-secondary-foreground',
        dot: 'bg-primary',
    },
    rejected: {
        label: 'Ditolak',
        badge: 'border-border bg-muted text-muted-foreground',
        dot: 'bg-muted-foreground',
    },
};

function StatusBadge({ status }: { status: string }) {
    const style = statusStyles[status] ?? {
        label: status,
        badge: 'border-border bg-muted text-muted-foreground',
        dot: 'bg-muted-foreground',
    };

    return (
        <Badge variant="outline" className={`gap-1.5 ${style.badge}`}>
            <span aria-hidden className={`size-2 ${style.dot}`} />
            {style.label}
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

export default function AdminApplications({ applications }: Props) {
    const [detail, setDetail] = useState<Application | null>(null);
    const [approving, setApproving] = useState<Application | null>(null);
    const [approveError, setApproveError] = useState<string | null>(null);
    const [processing, setProcessing] = useState(false);
    const [rejecting, setRejecting] = useState<Application | null>(null);

    const rejectForm = useForm({ reject_note: '' });

    const openDetail = (application: Application) => {
        setDetail(application);
    };

    const openApprove = (application: Application) => {
        setApproveError(null);
        setApproving(application);
    };

    const openReject = (application: Application) => {
        rejectForm.reset();
        rejectForm.clearErrors();
        setDetail(null);
        setRejecting(application);
    };

    const confirmApprove = () => {
        if (approving === null) {
            return;
        }

        setProcessing(true);
        router.post(
            approveApplication.url(approving.id),
            {},
            {
                preserveScroll: true,
                onSuccess: () => {
                    setApproving(null);
                    setDetail(null);
                },
                onError: () =>
                    setApproveError(
                        'Pendaftaran gagal diproses. Muat ulang halaman lalu coba lagi.',
                    ),
                onFinish: () => setProcessing(false),
            },
        );
    };

    const confirmReject = () => {
        if (rejecting === null) {
            return;
        }

        rejectForm.post(rejectApplication.url(rejecting.id), {
            preserveScroll: true,
            onSuccess: () => setRejecting(null),
        });
    };

    return (
        <>
            <Head title="Antrean pendaftaran" />

            <div className="flex flex-col gap-4 px-4 py-6 md:px-6">
                <div>
                    <h1 className="font-display text-2xl font-bold">
                        Antrean pendaftaran
                    </h1>
                    <p className="mt-1 text-sm text-muted-foreground">
                        Tinjau pendaftaran penjual baru sebelum lapaknya dibuat.
                    </p>
                </div>

                {applications.length === 0 ? (
                    <div className="border border-border bg-card px-4 py-10 text-center">
                        <ClipboardList
                            aria-hidden
                            className="mx-auto size-8 text-muted-foreground"
                        />
                        <h2 className="mt-3 font-display text-lg font-semibold">
                            Belum ada pendaftaran
                        </h2>
                        <p className="mx-auto mt-2 max-w-prose text-sm text-muted-foreground">
                            Pendaftaran penjual baru muncul di halaman ini.
                        </p>
                    </div>
                ) : (
                    <>
                        <div className="hidden border border-border bg-card md:block">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Nama lengkap</TableHead>
                                        <TableHead>NIS</TableHead>
                                        <TableHead>Nama lapak</TableHead>
                                        <TableHead>Status</TableHead>
                                        <TableHead className="text-right">
                                            Aksi
                                        </TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {applications.map((application) => (
                                        <TableRow key={application.id}>
                                            <TableCell className="font-medium">
                                                {application.full_name}
                                            </TableCell>
                                            <TableCell className="text-muted-foreground">
                                                {application.nis}
                                            </TableCell>
                                            <TableCell>
                                                {application.store_name}
                                            </TableCell>
                                            <TableCell>
                                                <StatusBadge
                                                    status={application.status}
                                                />
                                            </TableCell>
                                            <TableCell className="text-right">
                                                <Button
                                                    variant="ghost"
                                                    size="sm"
                                                    onClick={() =>
                                                        openDetail(application)
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
                            {applications.map((application) => (
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
                                            <p className="mt-1 text-xs text-muted-foreground">
                                                NIS {application.nis}
                                            </p>
                                        </div>

                                        <StatusBadge
                                            status={application.status}
                                        />
                                    </div>

                                    <Button
                                        variant="secondary"
                                        size="sm"
                                        className="mt-3"
                                        onClick={() => openDetail(application)}
                                    >
                                        <Eye aria-hidden />
                                        Detail
                                    </Button>
                                </li>
                            ))}
                        </ul>
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
                                    {detail.store_name}
                                </DialogTitle>
                                <DialogDescription>
                                    Dikirim {formatDate(detail.submitted_at)}{' '}
                                    oleh {detail.applicant.display_name} (
                                    {detail.applicant.username}).
                                </DialogDescription>
                            </DialogHeader>

                            <dl className="grid gap-3 text-sm sm:grid-cols-2">
                                <div>
                                    <dt className="text-muted-foreground">
                                        Nama lengkap
                                    </dt>
                                    <dd className="font-medium">
                                        {detail.full_name}
                                    </dd>
                                </div>
                                <div>
                                    <dt className="text-muted-foreground">
                                        NIS
                                    </dt>
                                    <dd className="font-medium">
                                        {detail.nis}
                                    </dd>
                                </div>
                                <div>
                                    <dt className="text-muted-foreground">
                                        Nomor WhatsApp
                                    </dt>
                                    <dd className="font-medium">
                                        {detail.whatsapp}
                                    </dd>
                                </div>
                                <div>
                                    <dt className="text-muted-foreground">
                                        Status
                                    </dt>
                                    <dd className="mt-1">
                                        <StatusBadge status={detail.status} />
                                    </dd>
                                </div>
                            </dl>

                            <div className="grid gap-1 text-sm">
                                <p className="text-muted-foreground">
                                    Deskripsi lapak
                                </p>
                                <p className="whitespace-pre-line">
                                    {detail.description}
                                </p>
                            </div>

                            <div className="grid gap-1 text-sm">
                                <p className="text-muted-foreground">
                                    Kategori
                                </p>
                                <p>{detail.categories.join(', ')}</p>
                            </div>

                            <div className="grid gap-2 text-sm">
                                <p className="text-muted-foreground">
                                    Foto produk
                                </p>
                                <ul className="flex flex-wrap gap-3">
                                    {detail.product_photos.map(
                                        (photo, index) => (
                                            <li key={photo}>
                                                <a
                                                    href={photo}
                                                    target="_blank"
                                                    rel="noreferrer"
                                                    className="block focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring"
                                                >
                                                    <img
                                                        src={photo}
                                                        alt={`Foto produk ${index + 1}`}
                                                        className="size-24 rounded-md border border-border object-cover"
                                                    />
                                                </a>
                                            </li>
                                        ),
                                    )}
                                </ul>
                            </div>

                            <div className="grid gap-2 text-sm">
                                <p className="text-muted-foreground">
                                    Kartu pelajar
                                </p>
                                <Button
                                    asChild
                                    variant="outline"
                                    size="sm"
                                    className="self-start"
                                >
                                    <a
                                        href={detail.student_card_url}
                                        target="_blank"
                                        rel="noreferrer"
                                    >
                                        <FileText aria-hidden />
                                        Buka kartu pelajar
                                    </a>
                                </Button>
                            </div>

                            {detail.reject_note && (
                                <div className="grid gap-1 text-sm">
                                    <p className="text-muted-foreground">
                                        Catatan penolakan
                                    </p>
                                    <p className="text-price">
                                        {detail.reject_note}
                                    </p>
                                </div>
                            )}

                            {detail.status === 'pending' ? (
                                <DialogFooter>
                                    <Button
                                        type="button"
                                        variant="destructive"
                                        onClick={() => openReject(detail)}
                                    >
                                        <X aria-hidden />
                                        Tolak
                                    </Button>
                                    <Button
                                        type="button"
                                        onClick={() => openApprove(detail)}
                                    >
                                        <Check aria-hidden />
                                        Terima
                                    </Button>
                                </DialogFooter>
                            ) : (
                                <p className="text-sm text-muted-foreground">
                                    Pendaftaran ini sudah ditinjau.
                                </p>
                            )}
                        </>
                    )}
                </DialogContent>
            </Dialog>

            <Dialog
                open={approving !== null}
                onOpenChange={(open) => !open && setApproving(null)}
            >
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle className="font-display text-xl">
                            Terima pendaftar ini?
                        </DialogTitle>
                        <DialogDescription>
                            Lapak {approving?.store_name} akan dibuat dengan
                            status tutup, dan {approving?.full_name} mendapat
                            akses seller.
                        </DialogDescription>
                    </DialogHeader>

                    <InputError message={approveError ?? undefined} />

                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => setApproving(null)}
                        >
                            Batal
                        </Button>
                        <Button
                            type="button"
                            disabled={processing}
                            onClick={confirmApprove}
                        >
                            {processing && <Spinner />}
                            <Check aria-hidden />
                            Terima pendaftar
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            <Dialog
                open={rejecting !== null}
                onOpenChange={(open) => !open && setRejecting(null)}
            >
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle className="font-display text-xl">
                            Tolak pendaftaran {rejecting?.store_name}?
                        </DialogTitle>
                        <DialogDescription>
                            Pendaftar menerima alasanmu dan bisa mengajukan
                            ulang setelah diperbaiki.
                        </DialogDescription>
                    </DialogHeader>

                    <form
                        className="grid gap-4"
                        onSubmit={(event) => {
                            event.preventDefault();
                            confirmReject();
                        }}
                    >
                        <div className="grid gap-2">
                            <Label htmlFor="reject_note">
                                Alasan penolakan
                            </Label>
                            <Textarea
                                id="reject_note"
                                value={rejectForm.data.reject_note}
                                maxLength={1000}
                                autoFocus
                                onChange={(event) =>
                                    rejectForm.setData(
                                        'reject_note',
                                        event.target.value,
                                    )
                                }
                            />
                            <InputError
                                message={rejectForm.errors.reject_note}
                            />
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
                                type="submit"
                                variant="destructive"
                                disabled={rejectForm.processing}
                            >
                                {rejectForm.processing && <Spinner />}
                                Tolak pendaftaran
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </>
    );
}

AdminApplications.layout = {
    breadcrumbs: [
        {
            title: 'Antrean pendaftaran',
            href: applications(),
        },
    ],
};
