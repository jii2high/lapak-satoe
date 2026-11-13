import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import { CircleAlert, ImagePlus, Store, X } from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';
import type { PropsWithChildren } from 'react';
import { EmailVerificationDialog } from '@/components/account/email-verification-dialog';
import InputError from '@/components/input-error';
import { CategoryCombobox } from '@/components/seller/category-combobox';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
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
import { home, terms } from '@/routes';
import { dashboard as sellerDashboard } from '@/routes/seller';
import { store as storeSellerApplication } from '@/routes/seller-application';

type Category = {
    id: number;
    name: string;
};

type ApplicationSummary = {
    status: string;
    reject_note: string | null;
};

type Props = {
    application: ApplicationSummary | null;
    is_seller: boolean;
    categories: Category[];
};

type ApplicationForm = {
    full_name: string;
    nis: string;
    whatsapp: string;
    store_name: string;
    description: string;
    categories: number[];
    product_photos: File[];
    student_card: File | null;
    agreed_terms: boolean;
};

const uploadHint =
    'Tarik file ke sini atau pilih dari perangkat. Maks 3 MB, JPG atau PNG.';

function FileField({
    id,
    files,
    onChange,
    max,
}: {
    id: string;
    files: File[];
    onChange: (files: File[]) => void;
    max: number;
}) {
    const [error, setError] = useState<string | null>(null);

    const previews = useMemo(
        () => files.map((file) => ({ file, url: URL.createObjectURL(file) })),
        [files],
    );

    useEffect(
        () => () =>
            previews.forEach((preview) => URL.revokeObjectURL(preview.url)),
        [previews],
    );

    // shortcut: mirrors config/lapak.php files.*.max_kb, the server still validates
    const add = (list: FileList | null) => {
        if (list === null) {
            return;
        }

        const incoming = Array.from(list);

        if (incoming.some((file) => file.size > 3 * 1024 * 1024)) {
            setError('Ukuran tiap berkas maksimal 3 MB.');

            return;
        }

        setError(null);
        onChange([...files, ...incoming].slice(0, max));
    };

    return (
        <div className="grid gap-3">
            <label
                htmlFor={id}
                onDragOver={(event) => event.preventDefault()}
                onDrop={(event) => {
                    event.preventDefault();
                    add(event.dataTransfer.files);
                }}
                className="flex cursor-pointer flex-col items-center gap-2 rounded-md border border-dashed border-input bg-card px-4 py-6 text-center focus-within:outline-2 focus-within:outline-offset-2 focus-within:outline-ring"
            >
                <ImagePlus
                    aria-hidden
                    className="size-6 text-muted-foreground"
                />
                <span className="text-sm text-muted-foreground">
                    {uploadHint}
                </span>
                <input
                    id={id}
                    type="file"
                    accept="image/jpeg,image/png"
                    multiple={max > 1}
                    className="sr-only"
                    onChange={(event) => {
                        add(event.target.files);
                        event.target.value = '';
                    }}
                />
            </label>

            {previews.length > 0 && (
                <ul className="flex flex-wrap gap-3">
                    {previews.map((preview) => (
                        <li key={preview.url} className="relative">
                            <img
                                src={preview.url}
                                alt={preview.file.name}
                                className="size-24 rounded-md border border-border object-cover"
                            />
                            <button
                                type="button"
                                aria-label={`Hapus ${preview.file.name}`}
                                onClick={() =>
                                    onChange(
                                        files.filter(
                                            (file) => file !== preview.file,
                                        ),
                                    )
                                }
                                className="absolute -top-2 -right-2 flex size-6 items-center justify-center rounded-md border border-border bg-card text-foreground before:absolute before:-inset-2.5 before:content-[''] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring"
                            >
                                <X aria-hidden className="size-3.5" />
                            </button>
                        </li>
                    ))}
                </ul>
            )}

            {max > 1 && (
                <p className="text-xs text-muted-foreground">
                    {files.length} dari {max} berkas dipilih.
                </p>
            )}

            <InputError message={error ?? undefined} />
        </div>
    );
}

function SellerApplicationShell({ children }: PropsWithChildren) {
    return (
        <main className="min-h-svh bg-background px-4 py-8 md:py-12">
            <div className="mx-auto w-full max-w-2xl">
                <Link
                    href={home()}
                    className="inline-flex min-h-11 items-center font-display text-xl font-bold text-foreground focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-ring"
                >
                    Lapak Satoe
                </Link>

                <div className="mt-5 rounded-md border border-border bg-card p-5 md:p-6">
                    {children}
                </div>
            </div>
        </main>
    );
}

export default function SellerApplication({
    application,
    is_seller,
    categories,
}: Props) {
    const { auth } = usePage().props;
    const verified = auth.user.email_verified;

    const [otpOpen, setOtpOpen] = useState(false);
    const [continueOpen, setContinueOpen] = useState(false);

    const form = useForm<ApplicationForm>({
        full_name: '',
        nis: '',
        whatsapp: '',
        store_name: '',
        description: '',
        categories: [],
        product_photos: [],
        student_card: null,
        agreed_terms: false,
    });

    const submit = () => {
        form.post(storeSellerApplication.url(), {
            forceFormData: true,
            preserveScroll: true,
        });
    };

    const openOtp = () => {
        setOtpOpen(true);
    };

    if (is_seller) {
        return (
            <>
                <SellerApplicationShell>
                    <Head title="Daftar penjual" />

                    <div className="flex flex-col gap-4">
                        <div>
                            <h1 className="font-display text-2xl font-bold">
                                Kamu sudah jadi penjual
                            </h1>
                            <p className="mt-1 text-sm text-muted-foreground">
                                Lapakmu sudah dibuat dan masih tertutup. Buka
                                lapak saat produkmu siap.
                            </p>
                        </div>

                        <div className="flex flex-wrap gap-2">
                            <Button asChild>
                                <Link href={sellerDashboard()}>
                                    Buka dashboard seller
                                </Link>
                            </Button>
                            <Button asChild variant="outline">
                                <Link href={home()}>Kembali ke beranda</Link>
                            </Button>
                        </div>
                    </div>
                </SellerApplicationShell>
            </>
        );
    }

    if (application?.status === 'pending') {
        return (
            <>
                <SellerApplicationShell>
                    <Head title="Daftar penjual" />

                    <div className="flex flex-col gap-4">
                        <div>
                            <h1 className="font-display text-2xl font-bold">
                                Pendaftaranmu sedang ditinjau
                            </h1>
                            <p className="mt-1 text-sm text-muted-foreground">
                                Admin akan memeriksa datamu. Hasilnya muncul di
                                notifikasi dan halaman ini.
                            </p>
                        </div>

                        <Button
                            asChild
                            variant="outline"
                            className="self-start"
                        >
                            <Link href={home()}>Kembali ke beranda</Link>
                        </Button>
                    </div>
                </SellerApplicationShell>
            </>
        );
    }

    return (
        <>
            <SellerApplicationShell>
                <Head title="Daftar penjual" />

                <div className="flex flex-col gap-6">
                    <div>
                        <h1 className="font-display text-2xl font-bold">
                            Daftar jadi penjual
                        </h1>
                        <p className="mt-1 text-sm text-muted-foreground">
                            Isi data diri dan lapakmu. Admin meninjau
                            pendaftaran sebelum lapakmu aktif.
                        </p>
                    </div>

                    {application?.status === 'rejected' && (
                        <div className="flex items-start gap-3 rounded-md border border-border bg-highlight-soft px-4 py-3">
                            <CircleAlert
                                aria-hidden
                                className="mt-0.5 size-5 shrink-0 text-price"
                            />
                            <div>
                                <p className="font-medium text-price">
                                    Pendaftaran sebelumnya ditolak
                                </p>
                                <p className="mt-1 text-sm text-price">
                                    {application.reject_note ??
                                        'Perbaiki datamu lalu kirim ulang.'}
                                </p>
                            </div>
                        </div>
                    )}

                    {!verified ? (
                        <section className="rounded-md border border-border bg-background px-4 py-5">
                            <Store
                                aria-hidden
                                className="size-8 text-muted-foreground"
                            />
                            <h2 className="mt-3 font-display text-lg font-semibold">
                                Verifikasi email dulu
                            </h2>
                            <p className="mt-1 max-w-prose text-sm text-muted-foreground">
                                Pendaftaran penjual butuh email terverifikasi.
                                Kami kirim kode 6 angka ke{' '}
                                {auth.user.email_masked}.
                            </p>
                            <div className="mt-4 flex flex-wrap gap-2">
                                <Button type="button" onClick={openOtp}>
                                    Verifikasi email
                                </Button>
                                <Button asChild variant="outline">
                                    <Link href={home()}>Nanti saja</Link>
                                </Button>
                            </div>
                        </section>
                    ) : (
                        <form
                            className="grid max-w-2xl gap-5"
                            onSubmit={(event) => {
                                event.preventDefault();
                                submit();
                            }}
                        >
                            <div className="grid gap-2">
                                <Label htmlFor="full_name">Nama lengkap</Label>
                                <Input
                                    id="full_name"
                                    value={form.data.full_name}
                                    maxLength={100}
                                    autoComplete="name"
                                    onChange={(event) =>
                                        form.setData(
                                            'full_name',
                                            event.target.value,
                                        )
                                    }
                                />
                                <InputError message={form.errors.full_name} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="nis">NIS</Label>
                                <Input
                                    id="nis"
                                    value={form.data.nis}
                                    maxLength={30}
                                    inputMode="numeric"
                                    onChange={(event) =>
                                        form.setData('nis', event.target.value)
                                    }
                                />
                                <InputError message={form.errors.nis} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="whatsapp">Nomor WhatsApp</Label>
                                <Input
                                    id="whatsapp"
                                    value={form.data.whatsapp}
                                    maxLength={20}
                                    inputMode="tel"
                                    placeholder="08xxxxxxxxxx"
                                    onChange={(event) =>
                                        form.setData(
                                            'whatsapp',
                                            event.target.value,
                                        )
                                    }
                                />
                                <InputError message={form.errors.whatsapp} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="store_name">Nama lapak</Label>
                                <Input
                                    id="store_name"
                                    value={form.data.store_name}
                                    maxLength={60}
                                    onChange={(event) =>
                                        form.setData(
                                            'store_name',
                                            event.target.value,
                                        )
                                    }
                                />
                                <InputError message={form.errors.store_name} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="description">
                                    Deskripsi lapak
                                </Label>
                                <Textarea
                                    id="description"
                                    value={form.data.description}
                                    maxLength={1000}
                                    onChange={(event) =>
                                        form.setData(
                                            'description',
                                            event.target.value,
                                        )
                                    }
                                />
                                <InputError message={form.errors.description} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="categories">
                                    Kategori produk
                                </Label>
                                <CategoryCombobox
                                    categories={categories}
                                    value={form.data.categories}
                                    invalid={
                                        form.errors.categories !== undefined
                                    }
                                    onChange={(ids) =>
                                        form.setData('categories', ids)
                                    }
                                />
                                <InputError message={form.errors.categories} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="product_photos">
                                    Foto produk
                                </Label>
                                <FileField
                                    id="product_photos"
                                    files={form.data.product_photos}
                                    max={3}
                                    onChange={(files) =>
                                        form.setData('product_photos', files)
                                    }
                                />
                                <InputError
                                    message={form.errors.product_photos}
                                />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="student_card">
                                    Kartu pelajar atau kartu perpustakaan
                                </Label>
                                <FileField
                                    id="student_card"
                                    files={
                                        form.data.student_card === null
                                            ? []
                                            : [form.data.student_card]
                                    }
                                    max={1}
                                    onChange={(files) =>
                                        form.setData(
                                            'student_card',
                                            files[0] ?? null,
                                        )
                                    }
                                />
                                <p className="text-xs text-muted-foreground">
                                    Hanya admin yang bisa melihat berkas ini.
                                </p>
                                <InputError
                                    message={form.errors.student_card}
                                />
                            </div>

                            <div className="flex items-start gap-3">
                                <Checkbox
                                    id="agreed_terms"
                                    checked={form.data.agreed_terms}
                                    onCheckedChange={(checked) =>
                                        form.setData(
                                            'agreed_terms',
                                            checked === true,
                                        )
                                    }
                                />
                                <div className="grid gap-1">
                                    <Label
                                        htmlFor="agreed_terms"
                                        className="leading-snug font-normal"
                                    >
                                        Saya sudah membaca dan setuju dengan{' '}
                                        <a
                                            href={terms.url()}
                                            target="_blank"
                                            rel="noreferrer"
                                            className="font-medium text-primary underline underline-offset-4"
                                        >
                                            syarat dan ketentuan
                                        </a>
                                        .
                                    </Label>
                                    <InputError
                                        message={form.errors.agreed_terms}
                                    />
                                </div>
                            </div>

                            <Button
                                type="submit"
                                disabled={form.processing}
                                className="self-start"
                            >
                                {form.processing && <Spinner />}
                                Kirim pendaftaran
                            </Button>
                        </form>
                    )}
                </div>
            </SellerApplicationShell>

            <EmailVerificationDialog
                open={otpOpen}
                onOpenChange={setOtpOpen}
                onVerified={() => setContinueOpen(true)}
            />

            <Dialog open={continueOpen} onOpenChange={setContinueOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle className="font-display text-xl">
                            Emailmu sudah terverifikasi
                        </DialogTitle>
                        <DialogDescription>
                            Isi formulir pendaftaran sekarang? Datamu bisa
                            dilengkapi nanti.
                        </DialogDescription>
                    </DialogHeader>

                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => router.visit(home())}
                        >
                            Nanti saja
                        </Button>
                        <Button
                            type="button"
                            onClick={() => setContinueOpen(false)}
                        >
                            Daftar
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </>
    );
}
