import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import { LogOut, Pencil } from 'lucide-react';
import { useState } from 'react';
import { EmailVerificationDialog } from '@/components/account/email-verification-dialog';
import InputError from '@/components/input-error';
import PasswordInput from '@/components/password-input';
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
import { clearSessionCache } from '@/lib/session-cache';
import { logout } from '@/routes';
import { edit as account, switchRole } from '@/routes/account';
import { update as updateDisplayName } from '@/routes/account/display-name';
import { update as updateEmail } from '@/routes/account/email';
import { update as updatePassword } from '@/routes/account/password';
import { update as updateUsername } from '@/routes/account/username';
import { create as sellerApplication } from '@/routes/seller-application';
import type { Role } from '@/types';

type AccountRole = {
    value: Role;
    label: string;
};

type SellerApplicationSummary = {
    status: string;
};

type Props = {
    roles: AccountRole[];
    seller_application: SellerApplicationSummary | null;
};

type EditMode = 'display-name' | 'username' | 'email' | 'password' | null;

const dialogCopy: Record<
    Exclude<EditMode, null>,
    { title: string; description: string; submit: string }
> = {
    'display-name': {
        title: 'Ubah nama tampilan',
        description: 'Nama ini tampil di header dan halaman akunmu.',
        submit: 'Simpan nama',
    },
    username: {
        title: 'Ubah username',
        description:
            'Username dipakai untuk masuk. Gunakan huruf, angka, atau garis bawah.',
        submit: 'Simpan username',
    },
    email: {
        title: 'Ubah email',
        description:
            'Setelah diganti, email perlu diverifikasi ulang lewat OTP.',
        submit: 'Simpan email',
    },
    password: {
        title: 'Ubah kata sandi',
        description: 'Masukkan kata sandi saat ini untuk membuat yang baru.',
        submit: 'Simpan kata sandi',
    },
};

export default function Account({ roles, seller_application }: Props) {
    const { auth } = usePage().props;
    const user = auth.user;
    const [editing, setEditing] = useState<EditMode>(null);
    const [otpOpen, setOtpOpen] = useState(false);
    const [continueOpen, setContinueOpen] = useState(false);
    const [verifyConfirmOpen, setVerifyConfirmOpen] = useState(false);
    const [verifyForSeller, setVerifyForSeller] = useState(false);

    const isSeller = roles.some((role) => role.value === 'seller');
    const applicationPending = seller_application?.status === 'pending';

    const displayNameForm = useForm({ display_name: user.display_name });
    const usernameForm = useForm({ username: user.username });
    const emailForm = useForm({ email: '' });
    const passwordForm = useForm({
        current_password: '',
        password: '',
        password_confirmation: '',
    });

    const close = () => setEditing(null);

    const open = (mode: Exclude<EditMode, null>) => {
        displayNameForm.setData('display_name', user.display_name);
        displayNameForm.clearErrors();
        usernameForm.setData('username', user.username);
        usernameForm.clearErrors();
        emailForm.setData('email', '');
        emailForm.clearErrors();
        passwordForm.reset();
        passwordForm.clearErrors();
        setEditing(mode);
    };

    const copy = editing ? dialogCopy[editing] : null;

    return (
        <>
            <Head title="Akun" />

            <div className="mb-8 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h1 className="font-display text-2xl font-bold">Akun</h1>
                    <p className="mt-1 text-sm text-muted-foreground">
                        Kelola profil, keamanan, dan tipe akunmu.
                    </p>
                </div>
                <div className="flex flex-wrap gap-2">
                    <Button asChild variant="outline">
                        <Link
                            href={logout()}
                            as="button"
                            onClick={() => {
                                clearSessionCache(user.id);
                                router.flushAll();
                            }}
                        >
                            <LogOut aria-hidden />
                            Keluar
                        </Link>
                    </Button>
                </div>
            </div>

            <div className="grid max-w-2xl gap-8">
                <section>
                    <h2 className="mb-3 font-display text-lg font-semibold">
                        Profil
                    </h2>
                    <div className="rounded-md border border-border bg-card">
                        <div className="flex items-center justify-between gap-3 border-b border-border px-4 py-4">
                            <div className="min-w-0">
                                <p className="text-sm text-muted-foreground">
                                    Nama tampilan
                                </p>
                                <p className="truncate font-medium">
                                    {user.display_name}
                                </p>
                            </div>
                            <Button
                                variant="ghost"
                                size="icon"
                                onClick={() => open('display-name')}
                            >
                                <Pencil aria-hidden />
                                <span className="sr-only">
                                    Ubah nama tampilan
                                </span>
                            </Button>
                        </div>

                        <div className="flex items-center justify-between gap-3 border-b border-border px-4 py-4">
                            <div className="min-w-0">
                                <p className="text-sm text-muted-foreground">
                                    Username
                                </p>
                                <p className="truncate font-medium">
                                    {user.username}
                                </p>
                            </div>
                            <Button
                                variant="ghost"
                                size="icon"
                                onClick={() => open('username')}
                            >
                                <Pencil aria-hidden />
                                <span className="sr-only">Ubah username</span>
                            </Button>
                        </div>

                        <div className="flex items-center justify-between gap-3 px-4 py-4">
                            <div className="min-w-0">
                                <p className="text-sm text-muted-foreground">
                                    Email
                                </p>
                                <div className="flex flex-wrap items-center gap-2">
                                    <p className="truncate font-medium">
                                        {user.email_masked}
                                    </p>
                                    <Badge
                                        variant="outline"
                                        className={
                                            user.email_verified
                                                ? 'border-primary bg-secondary text-secondary-foreground'
                                                : 'border-highlight bg-highlight-soft text-price'
                                        }
                                    >
                                        {user.email_verified
                                            ? 'Terverifikasi'
                                            : 'Belum terverifikasi'}
                                    </Badge>
                                </div>
                            </div>
                            <div className="flex shrink-0 items-center gap-1">
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    onClick={() => open('email')}
                                >
                                    <Pencil aria-hidden />
                                    <span className="sr-only">Ubah email</span>
                                </Button>
                            </div>
                        </div>

                        {!user.email_verified && (
                            <div className="flex flex-col gap-3 border-t border-border px-4 py-4 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <p className="font-medium">
                                        Emailmu belum terverifikasi
                                    </p>
                                    <p className="text-sm text-muted-foreground">
                                        Verifikasi email untuk bisa mendaftar
                                        jadi penjual.
                                    </p>
                                </div>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    className="shrink-0 self-start sm:self-auto"
                                    onClick={() => {
                                        setVerifyForSeller(false);
                                        setOtpOpen(true);
                                    }}
                                >
                                    Verifikasi email
                                </Button>
                            </div>
                        )}
                    </div>
                </section>

                <section>
                    <h2 className="mb-3 font-display text-lg font-semibold">
                        Keamanan
                    </h2>
                    <div className="rounded-md border border-border bg-card">
                        <div className="flex items-center justify-between gap-3 px-4 py-4">
                            <div>
                                <p className="font-medium">Kata sandi</p>
                                <p className="text-sm text-muted-foreground">
                                    Ganti kata sandi akunmu.
                                </p>
                            </div>
                            <Button
                                variant="ghost"
                                size="icon"
                                onClick={() => open('password')}
                            >
                                <Pencil aria-hidden />
                                <span className="sr-only">Ubah kata sandi</span>
                            </Button>
                        </div>
                    </div>
                </section>

                <section>
                    <h2 className="mb-3 font-display text-lg font-semibold">
                        Tipe akun
                    </h2>
                    <div className="rounded-md border border-border bg-card">
                        {roles.map((role) => {
                            const active = role.value === user.active_role;

                            return (
                                <div
                                    key={role.value}
                                    className="flex flex-col gap-3 border-b border-border px-4 py-4 last:border-b-0 sm:flex-row sm:items-center sm:justify-between"
                                >
                                    <div>
                                        <p className="font-medium">
                                            {role.label}
                                        </p>
                                        <p className="text-sm text-muted-foreground">
                                            {active
                                                ? 'Mode yang sedang dipakai.'
                                                : 'Kamu punya akses ke mode ini.'}
                                        </p>
                                    </div>
                                    <Button
                                        variant="secondary"
                                        size="sm"
                                        disabled={active}
                                        onClick={() => {
                                            clearSessionCache(user.id);
                                            router.post(switchRole(), {
                                                role: role.value,
                                            });
                                        }}
                                    >
                                        {active
                                            ? 'Mode aktif'
                                            : `Ganti ke ${role.label}`}
                                    </Button>
                                </div>
                            );
                        })}
                    </div>
                    <p className="mt-2 text-xs text-muted-foreground">
                        Mode menentukan halaman yang kamu buka setelah masuk.
                    </p>

                    {!isSeller && applicationPending && (
                        <div className="mt-4 border border-border bg-card px-4 py-4">
                            <p className="font-medium">
                                Pendaftaranmu sedang diproses
                            </p>
                            <p className="mt-1 text-sm text-muted-foreground">
                                Admin akan meninjau datamu. Hasilnya muncul di
                                notifikasi.
                            </p>
                        </div>
                    )}

                    {!isSeller && !applicationPending && (
                        <div className="mt-4 flex flex-col gap-3 border border-border bg-card px-4 py-4 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <p className="font-medium">
                                    Belum punya lapak?
                                </p>
                                <p className="text-sm text-muted-foreground">
                                    Daftar jadi penjual supaya produkmu bisa
                                    tampil di katalog.
                                </p>
                            </div>
                            {user.email_verified ? (
                                <Button asChild className="shrink-0">
                                    <Link href={sellerApplication()}>
                                        Jadi penjual
                                    </Link>
                                </Button>
                            ) : (
                                <Button
                                    type="button"
                                    className="shrink-0"
                                    onClick={() => setVerifyConfirmOpen(true)}
                                >
                                    Jadi penjual
                                </Button>
                            )}
                        </div>
                    )}
                </section>
            </div>

            <Dialog
                open={verifyConfirmOpen}
                onOpenChange={setVerifyConfirmOpen}
            >
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle className="font-display text-xl">
                            Email belum terverifikasi
                        </DialogTitle>
                        <DialogDescription>
                            Verifikasi dulu untuk mendaftar jadi penjual.
                        </DialogDescription>
                    </DialogHeader>

                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => setVerifyConfirmOpen(false)}
                        >
                            Nanti saja
                        </Button>
                        <Button
                            type="button"
                            onClick={() => {
                                setVerifyConfirmOpen(false);
                                setVerifyForSeller(true);
                                setOtpOpen(true);
                            }}
                        >
                            Verifikasi
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            <EmailVerificationDialog
                open={otpOpen}
                onOpenChange={setOtpOpen}
                onVerified={() => {
                    if (verifyForSeller) {
                        setContinueOpen(true);
                    }
                }}
            />

            <Dialog open={continueOpen} onOpenChange={setContinueOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle className="font-display text-xl">
                            Emailmu sudah terverifikasi
                        </DialogTitle>
                        <DialogDescription>
                            Lanjut isi formulir pendaftaran penjual sekarang?
                        </DialogDescription>
                    </DialogHeader>

                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => setContinueOpen(false)}
                        >
                            Nanti saja
                        </Button>
                        <Button
                            type="button"
                            onClick={() => router.visit(sellerApplication())}
                        >
                            Isi formulir
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            <Dialog
                open={editing !== null}
                onOpenChange={(isOpen) => !isOpen && close()}
            >
                <DialogContent>
                    {copy && (
                        <>
                            <DialogHeader>
                                <DialogTitle className="font-display text-xl">
                                    {copy.title}
                                </DialogTitle>
                                <DialogDescription>
                                    {copy.description}
                                </DialogDescription>
                            </DialogHeader>

                            {editing === 'display-name' && (
                                <form
                                    className="grid gap-4"
                                    onSubmit={(event) => {
                                        event.preventDefault();
                                        displayNameForm.patch(
                                            updateDisplayName.url(),
                                            {
                                                preserveScroll: true,
                                                onSuccess: close,
                                            },
                                        );
                                    }}
                                >
                                    <div className="grid gap-2">
                                        <Label htmlFor="display_name">
                                            Nama tampilan
                                        </Label>
                                        <Input
                                            id="display_name"
                                            value={
                                                displayNameForm.data
                                                    .display_name
                                            }
                                            onChange={(event) =>
                                                displayNameForm.setData(
                                                    'display_name',
                                                    event.target.value,
                                                )
                                            }
                                            autoFocus
                                        />
                                        <InputError
                                            message={
                                                displayNameForm.errors
                                                    .display_name
                                            }
                                        />
                                    </div>
                                    <DialogFooter>
                                        <Button
                                            type="button"
                                            variant="outline"
                                            onClick={close}
                                        >
                                            Batal
                                        </Button>
                                        <Button
                                            type="submit"
                                            disabled={
                                                displayNameForm.processing
                                            }
                                        >
                                            {displayNameForm.processing && (
                                                <Spinner />
                                            )}
                                            {copy.submit}
                                        </Button>
                                    </DialogFooter>
                                </form>
                            )}

                            {editing === 'username' && (
                                <form
                                    className="grid gap-4"
                                    onSubmit={(event) => {
                                        event.preventDefault();
                                        usernameForm.patch(
                                            updateUsername.url(),
                                            {
                                                preserveScroll: true,
                                                onSuccess: close,
                                            },
                                        );
                                    }}
                                >
                                    <div className="grid gap-2">
                                        <Label htmlFor="username">
                                            Username
                                        </Label>
                                        <Input
                                            id="username"
                                            value={usernameForm.data.username}
                                            onChange={(event) =>
                                                usernameForm.setData(
                                                    'username',
                                                    event.target.value,
                                                )
                                            }
                                            autoComplete="username"
                                            autoFocus
                                        />
                                        <InputError
                                            message={
                                                usernameForm.errors.username
                                            }
                                        />
                                    </div>
                                    <DialogFooter>
                                        <Button
                                            type="button"
                                            variant="outline"
                                            onClick={close}
                                        >
                                            Batal
                                        </Button>
                                        <Button
                                            type="submit"
                                            disabled={usernameForm.processing}
                                        >
                                            {usernameForm.processing && (
                                                <Spinner />
                                            )}
                                            {copy.submit}
                                        </Button>
                                    </DialogFooter>
                                </form>
                            )}

                            {editing === 'email' && (
                                <form
                                    className="grid gap-4"
                                    onSubmit={(event) => {
                                        event.preventDefault();
                                        emailForm.patch(updateEmail.url(), {
                                            preserveScroll: true,
                                            onSuccess: close,
                                        });
                                    }}
                                >
                                    <div className="grid gap-2">
                                        <Label htmlFor="email">
                                            Email baru
                                        </Label>
                                        <Input
                                            id="email"
                                            type="email"
                                            value={emailForm.data.email}
                                            onChange={(event) =>
                                                emailForm.setData(
                                                    'email',
                                                    event.target.value,
                                                )
                                            }
                                            autoComplete="email"
                                            placeholder="budi@email.com"
                                            autoFocus
                                        />
                                        <InputError
                                            message={emailForm.errors.email}
                                        />
                                    </div>
                                    <DialogFooter>
                                        <Button
                                            type="button"
                                            variant="outline"
                                            onClick={close}
                                        >
                                            Batal
                                        </Button>
                                        <Button
                                            type="submit"
                                            disabled={emailForm.processing}
                                        >
                                            {emailForm.processing && (
                                                <Spinner />
                                            )}
                                            {copy.submit}
                                        </Button>
                                    </DialogFooter>
                                </form>
                            )}

                            {editing === 'password' && (
                                <form
                                    className="grid gap-4"
                                    onSubmit={(event) => {
                                        event.preventDefault();
                                        passwordForm.patch(
                                            updatePassword.url(),
                                            {
                                                preserveScroll: true,
                                                onSuccess: close,
                                            },
                                        );
                                    }}
                                >
                                    <div className="grid gap-2">
                                        <Label htmlFor="current_password">
                                            Kata sandi saat ini
                                        </Label>
                                        <PasswordInput
                                            id="current_password"
                                            value={
                                                passwordForm.data
                                                    .current_password
                                            }
                                            onChange={(event) =>
                                                passwordForm.setData(
                                                    'current_password',
                                                    event.target.value,
                                                )
                                            }
                                            autoComplete="current-password"
                                            autoFocus
                                        />
                                        <InputError
                                            message={
                                                passwordForm.errors
                                                    .current_password
                                            }
                                        />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="new_password">
                                            Kata sandi baru
                                        </Label>
                                        <PasswordInput
                                            id="new_password"
                                            value={passwordForm.data.password}
                                            onChange={(event) =>
                                                passwordForm.setData(
                                                    'password',
                                                    event.target.value,
                                                )
                                            }
                                            autoComplete="new-password"
                                        />
                                        <InputError
                                            message={
                                                passwordForm.errors.password
                                            }
                                        />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="password_confirmation">
                                            Ulangi kata sandi baru
                                        </Label>
                                        <PasswordInput
                                            id="password_confirmation"
                                            value={
                                                passwordForm.data
                                                    .password_confirmation
                                            }
                                            onChange={(event) =>
                                                passwordForm.setData(
                                                    'password_confirmation',
                                                    event.target.value,
                                                )
                                            }
                                            autoComplete="new-password"
                                        />
                                        <InputError
                                            message={
                                                passwordForm.errors
                                                    .password_confirmation
                                            }
                                        />
                                    </div>
                                    <DialogFooter>
                                        <Button
                                            type="button"
                                            variant="outline"
                                            onClick={close}
                                        >
                                            Batal
                                        </Button>
                                        <Button
                                            type="submit"
                                            disabled={passwordForm.processing}
                                        >
                                            {passwordForm.processing && (
                                                <Spinner />
                                            )}
                                            {copy.submit}
                                        </Button>
                                    </DialogFooter>
                                </form>
                            )}
                        </>
                    )}
                </DialogContent>
            </Dialog>
        </>
    );
}

Account.layout = {
    breadcrumbs: [
        {
            title: 'Pengaturan',
            href: account(),
        },
    ],
};
