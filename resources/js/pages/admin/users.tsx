import { Head, router, useForm } from '@inertiajs/react';
import { Ellipsis } from 'lucide-react';
import { useEffect, useState } from 'react';
import InputError from '@/components/input-error';
import { Pagination } from '@/components/pagination';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
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
import { useDebounce } from '@/hooks/use-debounce';
import {
    index as users,
    destroy as destroyUser,
    reset as resetUserPassword,
    toggle as toggleUser,
} from '@/routes/admin/users';
import { update as updateUserEmail } from '@/routes/admin/users/email';
import type { Paginator } from '@/types';

type UserRow = {
    id: number;
    display_name: string;
    username: string;
    email: string;
    roles: string[];
    is_active: boolean;
    is_self: boolean;
    is_admin: boolean;
    created_at: string | null;
};

type Props = {
    users: Paginator<UserRow>;
    filters: {
        q: string;
    };
};

function StatusBadge({ active }: { active: boolean }) {
    return (
        <span
            className={`inline-flex h-6 items-center px-2 text-xs font-semibold ${
                active
                    ? 'bg-secondary text-secondary-foreground'
                    : 'bg-muted text-muted-foreground'
            }`}
        >
            {active ? 'Aktif' : 'Nonaktif'}
        </span>
    );
}

export default function AdminUsers({ users: rows, filters }: Props) {
    const [search, setSearch] = useState(filters.q);
    const [editing, setEditing] = useState<UserRow | null>(null);
    const [resetting, setResetting] = useState<UserRow | null>(null);
    const [toggling, setToggling] = useState<UserRow | null>(null);
    const [deleting, setDeleting] = useState<UserRow | null>(null);
    const [busy, setBusy] = useState(false);
    const debounced = useDebounce(search);
    const emailForm = useForm({ email: '' });

    useEffect(() => {
        if (debounced === filters.q) {
            return;
        }

        router.get(
            users.url({
                query: debounced === '' ? {} : { q: debounced },
            }),
            { preserveState: true, preserveScroll: true, replace: true },
        );
    }, [debounced, filters.q]);

    const openEmail = (user: UserRow) => {
        emailForm.reset();
        emailForm.clearErrors();
        setEditing(user);
    };

    const confirmEmail = () => {
        if (editing === null) {
            return;
        }

        emailForm.patch(updateUserEmail.url(editing.id), {
            preserveScroll: true,
            onSuccess: () => setEditing(null),
        });
    };

    const confirmReset = () => {
        if (resetting === null) {
            return;
        }

        setBusy(true);
        router.post(
            resetUserPassword.url(resetting.id),
            {},
            {
                preserveScroll: true,
                onSuccess: () => setResetting(null),
                onFinish: () => setBusy(false),
            },
        );
    };

    const confirmToggle = () => {
        if (toggling === null) {
            return;
        }

        setBusy(true);
        router.post(
            toggleUser.url(toggling.id),
            {},
            {
                preserveScroll: true,
                onSuccess: () => setToggling(null),
                onFinish: () => setBusy(false),
            },
        );
    };

    const confirmDelete = () => {
        if (deleting === null) {
            return;
        }

        setBusy(true);
        router.delete(destroyUser.url(deleting.id), {
            preserveScroll: true,
            onSuccess: () => setDeleting(null),
            onFinish: () => setBusy(false),
        });
    };

    const actions = (user: UserRow) => (
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                <Button
                    variant="ghost"
                    size="icon"
                    aria-label={`Aksi untuk @${user.username}`}
                >
                    <Ellipsis aria-hidden />
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end">
                {!user.is_self && (
                    <>
                        <DropdownMenuItem onSelect={() => openEmail(user)}>
                            Ubah email
                        </DropdownMenuItem>
                        <DropdownMenuItem onSelect={() => setResetting(user)}>
                            Reset kata sandi
                        </DropdownMenuItem>
                    </>
                )}
                {!user.is_self && !user.is_admin && (
                    <>
                        <DropdownMenuItem onSelect={() => setToggling(user)}>
                            {user.is_active ? 'Nonaktifkan' : 'Aktifkan'}
                        </DropdownMenuItem>
                        <DropdownMenuItem
                            variant="destructive"
                            onSelect={() => setDeleting(user)}
                        >
                            Hapus akun
                        </DropdownMenuItem>
                    </>
                )}
                {user.is_self && (
                    <DropdownMenuItem disabled>
                        Akunmu diatur dari halaman akun
                    </DropdownMenuItem>
                )}
            </DropdownMenuContent>
        </DropdownMenu>
    );

    return (
        <>
            <Head title="Kelola pengguna" />

            <div className="flex flex-col gap-4 px-4 py-6 md:px-6">
                <div>
                    <h1 className="font-display text-2xl font-bold">
                        Kelola pengguna
                    </h1>
                    <p className="mt-1 text-sm text-muted-foreground">
                        Cari pengguna, ubah email, kirim tautan reset kata
                        sandi, nonaktifkan, atau hapus akun.
                    </p>
                </div>

                <div className="relative">
                    <Input
                        value={search}
                        onChange={(event) => setSearch(event.target.value)}
                        aria-label="Cari pengguna"
                        placeholder="Cari nama, username, atau email"
                    />
                </div>

                {rows.data.length === 0 ? (
                    <div className="border border-border bg-card px-4 py-10 text-center">
                        <h2 className="font-display text-lg font-semibold">
                            {filters.q === ''
                                ? 'Belum ada pengguna'
                                : 'Tidak ada pengguna cocok'}
                        </h2>
                        <p className="mx-auto mt-2 max-w-prose text-sm text-muted-foreground">
                            {filters.q === ''
                                ? 'Akun yang mendaftar akan muncul di sini.'
                                : 'Ubah kata kunci pencarian.'}
                        </p>
                    </div>
                ) : (
                    <>
                        <div className="hidden border border-border bg-card md:block">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Pengguna</TableHead>
                                        <TableHead>Email</TableHead>
                                        <TableHead>Peran</TableHead>
                                        <TableHead>Status</TableHead>
                                        <TableHead className="text-right">
                                            Aksi
                                        </TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {rows.data.map((user) => (
                                        <TableRow key={user.id}>
                                            <TableCell>
                                                <span className="font-medium">
                                                    {user.display_name}
                                                </span>
                                                <span className="block text-xs text-muted-foreground">
                                                    @{user.username}
                                                </span>
                                            </TableCell>
                                            <TableCell className="text-muted-foreground">
                                                {user.email}
                                            </TableCell>
                                            <TableCell className="text-muted-foreground">
                                                {user.roles.join(', ')}
                                            </TableCell>
                                            <TableCell>
                                                <StatusBadge
                                                    active={user.is_active}
                                                />
                                            </TableCell>
                                            <TableCell className="text-right">
                                                {actions(user)}
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        </div>

                        <ul className="grid gap-3 md:hidden">
                            {rows.data.map((user) => (
                                <li
                                    key={user.id}
                                    className="border border-border bg-card p-4"
                                >
                                    <div className="flex items-start justify-between gap-3">
                                        <div className="min-w-0">
                                            <p className="truncate font-medium">
                                                {user.display_name}
                                            </p>
                                            <p className="truncate text-sm text-muted-foreground">
                                                @{user.username}
                                            </p>
                                            <p className="mt-1 truncate text-xs text-muted-foreground">
                                                {user.email}
                                            </p>
                                        </div>
                                        <div className="flex shrink-0 flex-col items-end gap-2">
                                            <StatusBadge
                                                active={user.is_active}
                                            />
                                            {actions(user)}
                                        </div>
                                    </div>
                                    <p className="mt-2 text-xs text-muted-foreground">
                                        Peran: {user.roles.join(', ')}
                                    </p>
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
                open={editing !== null}
                onOpenChange={(open) => !open && setEditing(null)}
            >
                <DialogContent className="sm:max-w-[480px]">
                    {editing && (
                        <>
                            <DialogHeader>
                                <DialogTitle className="font-display text-xl">
                                    Ubah email @{editing.username}
                                </DialogTitle>
                                <DialogDescription>
                                    Email sekarang {editing.email}. Email baru
                                    menggantikan yang lama dan verifikasinya
                                    direset.
                                </DialogDescription>
                            </DialogHeader>

                            <form
                                id="user-email-form"
                                onSubmit={(event) => {
                                    event.preventDefault();
                                    confirmEmail();
                                }}
                                className="grid gap-2"
                            >
                                <Label htmlFor="user-email">Email baru</Label>
                                <Input
                                    id="user-email"
                                    type="email"
                                    autoComplete="off"
                                    value={emailForm.data.email}
                                    onChange={(event) =>
                                        emailForm.setData(
                                            'email',
                                            event.target.value,
                                        )
                                    }
                                />
                                <InputError message={emailForm.errors.email} />
                            </form>

                            <DialogFooter>
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={() => setEditing(null)}
                                >
                                    Batal
                                </Button>
                                <Button
                                    type="submit"
                                    form="user-email-form"
                                    disabled={
                                        emailForm.processing ||
                                        emailForm.data.email.trim() === ''
                                    }
                                >
                                    {emailForm.processing && <Spinner />}
                                    Simpan email
                                </Button>
                            </DialogFooter>
                        </>
                    )}
                </DialogContent>
            </Dialog>

            <Dialog
                open={resetting !== null}
                onOpenChange={(open) => !open && setResetting(null)}
            >
                <DialogContent className="sm:max-w-[480px]">
                    {resetting && (
                        <>
                            <DialogHeader>
                                <DialogTitle className="font-display text-xl">
                                    Kirim tautan reset kata sandi ke @
                                    {resetting.username}?
                                </DialogTitle>
                                <DialogDescription>
                                    Pengguna menerima email berisi tautan untuk
                                    membuat kata sandi baru. Kata sandinya tidak
                                    berubah sampai tautan itu dipakai.
                                </DialogDescription>
                            </DialogHeader>

                            <DialogFooter>
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={() => setResetting(null)}
                                >
                                    Batal
                                </Button>
                                <Button
                                    type="button"
                                    disabled={busy}
                                    onClick={confirmReset}
                                >
                                    {busy && <Spinner />}
                                    Kirim tautan
                                </Button>
                            </DialogFooter>
                        </>
                    )}
                </DialogContent>
            </Dialog>

            <Dialog
                open={toggling !== null}
                onOpenChange={(open) => !open && setToggling(null)}
            >
                <DialogContent className="sm:max-w-[480px]">
                    {toggling && (
                        <>
                            <DialogHeader>
                                <DialogTitle className="font-display text-xl">
                                    {toggling.is_active
                                        ? `Nonaktifkan @${toggling.username}?`
                                        : `Aktifkan @${toggling.username}?`}
                                </DialogTitle>
                                <DialogDescription>
                                    {toggling.is_active
                                        ? 'Akun tidak bisa login lagi dan sesi yang sedang berjalan diputus. Data dan lapaknya tidak dihapus.'
                                        : 'Akun bisa login lagi dan memakai situs seperti biasa.'}
                                </DialogDescription>
                            </DialogHeader>

                            <DialogFooter>
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={() => setToggling(null)}
                                >
                                    Batal
                                </Button>
                                <Button
                                    type="button"
                                    variant={
                                        toggling.is_active
                                            ? 'destructive'
                                            : 'default'
                                    }
                                    disabled={busy}
                                    onClick={confirmToggle}
                                >
                                    {busy && <Spinner />}
                                    {toggling.is_active
                                        ? 'Nonaktifkan'
                                        : 'Aktifkan'}
                                </Button>
                            </DialogFooter>
                        </>
                    )}
                </DialogContent>
            </Dialog>

            <Dialog
                open={deleting !== null}
                onOpenChange={(open) => !open && setDeleting(null)}
            >
                <DialogContent className="sm:max-w-[480px]">
                    {deleting && (
                        <>
                            <DialogHeader>
                                <DialogTitle className="font-display text-xl">
                                    Hapus akun @{deleting.username}?
                                </DialogTitle>
                                <DialogDescription>
                                    Akun tidak bisa login lagi, lapak ditutup,
                                    dan produknya disembunyikan dari katalog.
                                    Email dan username-nya dibebaskan. Data lama
                                    tetap tersimpan, dan penghapusan ditolak
                                    bila pengguna masih punya pesanan aktif.
                                </DialogDescription>
                            </DialogHeader>

                            <DialogFooter>
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={() => setDeleting(null)}
                                >
                                    Batal
                                </Button>
                                <Button
                                    type="button"
                                    variant="destructive"
                                    disabled={busy}
                                    onClick={confirmDelete}
                                >
                                    {busy && <Spinner />}
                                    Hapus akun
                                </Button>
                            </DialogFooter>
                        </>
                    )}
                </DialogContent>
            </Dialog>
        </>
    );
}

AdminUsers.layout = {
    breadcrumbs: [
        {
            title: 'Kelola pengguna',
            href: users(),
        },
    ],
};
