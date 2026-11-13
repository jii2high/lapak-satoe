import { Head, router, useForm } from '@inertiajs/react';
import { ImagePlus, ShieldAlert, Store as StoreIcon, X } from 'lucide-react';
import { useEffect, useMemo, useRef, useState } from 'react';
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
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import { cn } from '@/lib/utils';
import {
    edit as editStore,
    toggle as toggleStore,
    update as updateStore,
} from '@/routes/seller/store';
import type { StoreStatus } from '@/types';

type StoreData = {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    whatsapp: string;
    logo_url: string | null;
    banner_url: string | null;
    status: StoreStatus;
    is_suspended: boolean;
    suspension_note: string | null;
};

type Props = {
    store: StoreData;
    previous_name: string | null;
    active_order_count: number;
    active_product_count: number;
};

type StoreForm = {
    name: string;
    description: string;
    whatsapp: string;
    logo: File | null;
    banner: File | null;
};

const statusStyles: Record<StoreStatus, { label: string; badge: string }> = {
    open: {
        label: 'Buka',
        badge: 'border-primary bg-secondary text-secondary-foreground',
    },
    closing: {
        label: 'Menunggu pesanan selesai',
        badge: 'border-highlight bg-highlight-soft text-price',
    },
    closed: {
        label: 'Tutup',
        badge: 'border-border bg-muted text-muted-foreground',
    },
};

function ImageField({
    id,
    label,
    hint,
    currentUrl,
    file,
    className,
    onChange,
}: {
    id: string;
    label: string;
    hint: string;
    currentUrl: string | null;
    file: File | null;
    className: string;
    onChange: (file: File | null) => void;
}) {
    const inputRef = useRef<HTMLInputElement>(null);
    const [error, setError] = useState<string | null>(null);
    const preview = useMemo(
        () => (file === null ? null : URL.createObjectURL(file)),
        [file],
    );

    useEffect(
        () => () => {
            if (preview !== null) {
                URL.revokeObjectURL(preview);
            }
        },
        [preview],
    );

    const source = preview ?? currentUrl;

    return (
        <div className="grid gap-2">
            <Label htmlFor={id}>{label}</Label>

            <div className="flex flex-wrap items-start gap-3">
                {source === null ? (
                    <div
                        className={cn(
                            'flex items-center justify-center border border-border bg-muted text-muted-foreground',
                            className,
                        )}
                    >
                        <StoreIcon aria-hidden className="size-6" />
                    </div>
                ) : (
                    <img
                        src={source}
                        alt={label}
                        className={cn(
                            'border border-border object-cover',
                            className,
                        )}
                    />
                )}

                <div className="grid gap-1">
                    <Button
                        type="button"
                        variant="outline"
                        onClick={() => inputRef.current?.click()}
                    >
                        <ImagePlus aria-hidden />
                        {file === null ? 'Pilih gambar' : 'Ganti gambar'}
                    </Button>

                    {file !== null && (
                        <Button
                            type="button"
                            variant="ghost"
                            size="sm"
                            onClick={() => {
                                onChange(null);
                                setError(null);

                                if (inputRef.current !== null) {
                                    inputRef.current.value = '';
                                }
                            }}
                        >
                            <X aria-hidden />
                            Batalkan
                        </Button>
                    )}
                </div>
            </div>

            <input
                ref={inputRef}
                id={id}
                type="file"
                accept="image/jpeg,image/png"
                className="sr-only"
                onChange={(event) => {
                    const picked = event.target.files?.[0] ?? null;

                    // shortcut: mirrors config/lapak.php store_image.max_kb, the server still validates
                    if (picked !== null && picked.size > 3 * 1024 * 1024) {
                        setError('Ukuran berkas maksimal 3 MB.');
                        onChange(null);

                        return;
                    }

                    setError(null);
                    onChange(picked);
                }}
            />

            <p className="text-xs text-muted-foreground">{hint}</p>
            <InputError message={error ?? undefined} />
        </div>
    );
}

export default function SellerStore({
    store,
    previous_name,
    active_order_count,
    active_product_count,
}: Props) {
    const form = useForm<StoreForm>({
        name: store.name,
        description: store.description ?? '',
        whatsapp: store.whatsapp,
        logo: null,
        banner: null,
    });

    const [confirmClose, setConfirmClose] = useState(false);
    const [toggling, setToggling] = useState(false);

    const status = statusStyles[store.status];
    const cannotOpen = store.is_suspended || active_product_count === 0;

    const submit = () => {
        form.patch(updateStore.url(), {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                form.reset('logo', 'banner');
            },
        });
    };

    const toggle = () => {
        setToggling(true);
        router.post(
            toggleStore.url(),
            {},
            {
                preserveScroll: true,
                onFinish: () => setToggling(false),
            },
        );
    };

    const requestToggle = () => {
        if (store.status === 'open' && active_order_count > 0) {
            setConfirmClose(true);

            return;
        }

        toggle();
    };

    const toggleLabel = store.status === 'open' ? 'Tutup lapak' : 'Buka lapak';
    const toggleDisabled =
        toggling ||
        (store.status === 'closed' && cannotOpen) ||
        (store.status === 'closing' && active_order_count > 0);

    return (
        <>
            <Head title="Pengaturan lapak" />

            <div className="flex flex-1 flex-col gap-6 px-4 py-6 md:px-6">
                <div>
                    <h1 className="font-display text-2xl font-bold">
                        Pengaturan lapak
                    </h1>
                    <p className="mt-1 text-sm text-muted-foreground">
                        Nama, kontak, dan tampilan lapakmu di katalog.
                    </p>
                </div>

                {store.is_suspended && (
                    <div className="flex items-start gap-3 border border-l-4 border-border border-l-destructive bg-destructive/10 px-4 py-3">
                        <ShieldAlert
                            aria-hidden
                            className="mt-0.5 size-5 shrink-0 text-destructive"
                        />
                        <div>
                            <p className="font-medium text-foreground">
                                Lapak dinonaktifkan admin
                            </p>
                            <p className="mt-1 text-sm text-muted-foreground">
                                Lapak tidak bisa dibuka dan produknya tidak
                                tampil di katalog. Hubungi admin untuk
                                memulihkan.
                            </p>
                            {store.suspension_note !== null && (
                                <p className="mt-2 text-sm text-foreground">
                                    Catatan admin: {store.suspension_note}
                                </p>
                            )}
                        </div>
                    </div>
                )}

                <section className="border border-border bg-card">
                    <div className="flex flex-col gap-4 px-4 py-5 sm:flex-row sm:items-start sm:justify-between md:px-6">
                        <div>
                            <div className="flex flex-wrap items-center gap-2">
                                <h2 className="font-display text-lg font-semibold">
                                    Status lapak
                                </h2>
                                <Badge
                                    variant="outline"
                                    className={status.badge}
                                >
                                    {status.label}
                                </Badge>
                            </div>

                            <p className="mt-1 text-sm text-muted-foreground">
                                {active_product_count} produk aktif,{' '}
                                {active_order_count} pesanan aktif.
                            </p>

                            {store.status === 'closing' && (
                                <p className="mt-1 max-w-prose text-sm text-muted-foreground">
                                    {active_order_count > 0
                                        ? 'Lapak tertutup untuk pembeli baru dan akan menutup otomatis setelah pesanan aktif selesai.'
                                        : 'Semua pesanan aktif sudah selesai. Lapak siap ditutup.'}
                                </p>
                            )}

                            {store.status === 'closed' && cannotOpen && (
                                <p className="mt-1 max-w-prose text-sm text-muted-foreground">
                                    {store.is_suspended
                                        ? 'Lapak dinonaktifkan admin.'
                                        : 'Tambahkan minimal satu produk aktif sebelum membuka lapak.'}
                                </p>
                            )}
                        </div>

                        <Button
                            type="button"
                            variant={
                                store.status === 'open' ? 'outline' : 'default'
                            }
                            disabled={toggleDisabled}
                            onClick={requestToggle}
                            className="shrink-0"
                        >
                            {toggling && <Spinner />}
                            {store.status === 'closing'
                                ? 'Tutup sekarang'
                                : toggleLabel}
                        </Button>
                    </div>
                </section>

                <form
                    className="grid max-w-2xl gap-5"
                    onSubmit={(event) => {
                        event.preventDefault();
                        submit();
                    }}
                >
                    <div className="grid gap-2">
                        <Label htmlFor="store-name">Nama lapak</Label>
                        <Input
                            id="store-name"
                            value={form.data.name}
                            maxLength={60}
                            onChange={(event) =>
                                form.setData('name', event.target.value)
                            }
                        />
                        {previous_name !== null && (
                            <p className="text-xs text-muted-foreground">
                                Sebelumnya {previous_name}
                            </p>
                        )}
                        <InputError message={form.errors.name} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="store-description">
                            Deskripsi lapak
                        </Label>
                        <Textarea
                            id="store-description"
                            value={form.data.description}
                            maxLength={1000}
                            onChange={(event) =>
                                form.setData('description', event.target.value)
                            }
                        />
                        <InputError message={form.errors.description} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="store-whatsapp">Nomor WhatsApp</Label>
                        <Input
                            id="store-whatsapp"
                            value={form.data.whatsapp}
                            maxLength={20}
                            inputMode="tel"
                            placeholder="08xxxxxxxxxx"
                            onChange={(event) =>
                                form.setData('whatsapp', event.target.value)
                            }
                        />
                        <p className="text-xs text-muted-foreground">
                            Nomor ini dipakai buyer saat memesan lewat WhatsApp.
                        </p>
                        <InputError message={form.errors.whatsapp} />
                    </div>

                    <ImageField
                        id="store-logo"
                        label="Logo lapak"
                        hint="JPG atau PNG, maks 3 MB. Tampil persegi di kartu lapak."
                        currentUrl={store.logo_url}
                        file={form.data.logo}
                        className="size-20"
                        onChange={(file) => form.setData('logo', file)}
                    />
                    <InputError message={form.errors.logo} />

                    <ImageField
                        id="store-banner"
                        label="Banner lapak"
                        hint="JPG atau PNG, maks 3 MB. Rasio 3:1, di HP 2:1."
                        currentUrl={store.banner_url}
                        file={form.data.banner}
                        className="aspect-[2/1] w-full max-w-xs md:aspect-[3/1]"
                        onChange={(file) => form.setData('banner', file)}
                    />
                    <InputError message={form.errors.banner} />

                    <Button
                        type="submit"
                        disabled={form.processing}
                        className="self-start"
                    >
                        {form.processing && <Spinner />}
                        Simpan pengaturan
                    </Button>
                </form>
            </div>

            <Dialog open={confirmClose} onOpenChange={setConfirmClose}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle className="font-display text-xl">
                            Tutup lapak sekarang?
                        </DialogTitle>
                        <DialogDescription>
                            Lapak tidak bisa menerima pesanan baru, tetapi{' '}
                            {active_order_count} pesanan aktif tetap berjalan.
                            Lapak menutup otomatis setelah semua pesanan aktif
                            selesai.
                        </DialogDescription>
                    </DialogHeader>

                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => setConfirmClose(false)}
                        >
                            Batal
                        </Button>
                        <Button
                            type="button"
                            onClick={() => {
                                setConfirmClose(false);
                                toggle();
                            }}
                        >
                            Tutup lapak
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </>
    );
}

SellerStore.layout = {
    breadcrumbs: [
        {
            title: 'Pengaturan lapak',
            href: editStore(),
        },
    ],
};
