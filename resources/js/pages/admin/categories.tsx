import { Head, router, useForm } from '@inertiajs/react';
import { Pencil, Plus, Tag, Trash2 } from 'lucide-react';
import { useState } from 'react';
import InputError from '@/components/input-error';
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
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import {
    destroy as destroyCategory,
    index as categories,
    store as storeCategory,
    update as updateCategory,
} from '@/routes/admin/categories';

type CategoryRow = {
    id: number;
    name: string;
    slug: string;
    product_count: number;
};

type Props = {
    categories: CategoryRow[];
};

export default function AdminCategories({ categories }: Props) {
    const [editing, setEditing] = useState<CategoryRow | null>(null);
    const [dialogOpen, setDialogOpen] = useState(false);
    const [deleting, setDeleting] = useState<CategoryRow | null>(null);

    const form = useForm({ name: '' });

    const openCreate = () => {
        form.reset();
        form.clearErrors();
        setEditing(null);
        setDialogOpen(true);
    };

    const openEdit = (category: CategoryRow) => {
        form.clearErrors();
        form.setData('name', category.name);
        setEditing(category);
        setDialogOpen(true);
    };

    const closeDialog = () => {
        setDialogOpen(false);
        setEditing(null);
    };

    const submit = () => {
        const options = {
            preserveScroll: true,
            onSuccess: closeDialog,
        };

        if (editing === null) {
            form.post(storeCategory.url(), options);
        } else {
            form.patch(updateCategory.url(editing.id), options);
        }
    };

    const confirmDelete = () => {
        if (deleting === null) {
            return;
        }

        router.delete(destroyCategory.url(deleting.id), {
            preserveScroll: true,
            onSuccess: () => setDeleting(null),
        });
    };

    return (
        <>
            <Head title="Kelola kategori" />

            <div className="flex flex-col gap-4 px-4 py-6 md:px-6">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <h1 className="font-display text-2xl font-bold">
                            Kelola kategori
                        </h1>
                        <p className="mt-1 text-sm text-muted-foreground">
                            Kategori dipakai produk dan formulir pendaftaran
                            seller.
                        </p>
                    </div>

                    <Button onClick={openCreate}>
                        <Plus aria-hidden />
                        Tambah kategori
                    </Button>
                </div>

                {categories.length === 0 ? (
                    <div className="border border-border bg-card px-4 py-10 text-center">
                        <Tag
                            aria-hidden
                            className="mx-auto size-8 text-muted-foreground"
                        />
                        <h2 className="mt-3 font-display text-lg font-semibold">
                            Belum ada kategori
                        </h2>
                        <p className="mx-auto mt-2 max-w-prose text-sm text-muted-foreground">
                            Tambah kategori pertama supaya seller bisa memilih
                            saat mengunggah produk.
                        </p>
                        <Button className="mt-4" onClick={openCreate}>
                            Tambah kategori
                        </Button>
                    </div>
                ) : (
                    <>
                        <div className="hidden border border-border bg-card md:block">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Nama</TableHead>
                                        <TableHead>Slug</TableHead>
                                        <TableHead className="text-right">
                                            Produk
                                        </TableHead>
                                        <TableHead className="text-right">
                                            Aksi
                                        </TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {categories.map((category) => (
                                        <TableRow key={category.id}>
                                            <TableCell className="font-medium">
                                                {category.name}
                                            </TableCell>
                                            <TableCell className="text-muted-foreground">
                                                {category.slug}
                                            </TableCell>
                                            <TableCell className="text-right">
                                                {category.product_count}
                                            </TableCell>
                                            <TableCell>
                                                <div className="flex justify-end gap-1">
                                                    <Button
                                                        variant="ghost"
                                                        size="sm"
                                                        onClick={() =>
                                                            openEdit(category)
                                                        }
                                                    >
                                                        <Pencil aria-hidden />
                                                        Ubah
                                                    </Button>
                                                    <Button
                                                        variant="ghost"
                                                        size="sm"
                                                        disabled={
                                                            category.product_count >
                                                            0
                                                        }
                                                        onClick={() =>
                                                            setDeleting(
                                                                category,
                                                            )
                                                        }
                                                    >
                                                        <Trash2 aria-hidden />
                                                        Hapus
                                                    </Button>
                                                </div>
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        </div>

                        <ul className="grid gap-3 md:hidden">
                            {categories.map((category) => (
                                <li
                                    key={category.id}
                                    className="border border-border bg-card p-4"
                                >
                                    <div className="flex items-start justify-between gap-3">
                                        <div className="min-w-0">
                                            <p className="truncate font-medium">
                                                {category.name}
                                            </p>
                                            <p className="truncate text-xs text-muted-foreground">
                                                {category.slug}
                                            </p>
                                            <p className="mt-1 text-xs text-muted-foreground">
                                                {category.product_count} produk
                                            </p>
                                        </div>

                                        <div className="flex shrink-0 gap-1">
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                aria-label={`Ubah ${category.name}`}
                                                onClick={() =>
                                                    openEdit(category)
                                                }
                                            >
                                                <Pencil aria-hidden />
                                            </Button>
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                aria-label={`Hapus ${category.name}`}
                                                disabled={
                                                    category.product_count > 0
                                                }
                                                onClick={() =>
                                                    setDeleting(category)
                                                }
                                            >
                                                <Trash2 aria-hidden />
                                            </Button>
                                        </div>
                                    </div>
                                </li>
                            ))}
                        </ul>
                    </>
                )}
            </div>

            <Dialog
                open={dialogOpen}
                onOpenChange={(open) => !open && closeDialog()}
            >
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle className="font-display text-xl">
                            {editing === null
                                ? 'Tambah kategori'
                                : 'Ubah kategori'}
                        </DialogTitle>
                        <DialogDescription>
                            Nama kategori tampil di filter Jelajah dan formulir
                            pendaftaran seller.
                        </DialogDescription>
                    </DialogHeader>

                    <form
                        className="grid gap-4"
                        onSubmit={(event) => {
                            event.preventDefault();
                            submit();
                        }}
                    >
                        <div className="grid gap-2">
                            <Label htmlFor="category-name">Nama kategori</Label>
                            <Input
                                id="category-name"
                                value={form.data.name}
                                maxLength={60}
                                autoFocus
                                onChange={(event) =>
                                    form.setData('name', event.target.value)
                                }
                            />
                            <InputError message={form.errors.name} />
                        </div>

                        <DialogFooter>
                            <Button
                                type="button"
                                variant="outline"
                                onClick={closeDialog}
                            >
                                Batal
                            </Button>
                            <Button type="submit" disabled={form.processing}>
                                {form.processing && <Spinner />}
                                {editing === null
                                    ? 'Tambah kategori'
                                    : 'Simpan perubahan'}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            <Dialog
                open={deleting !== null}
                onOpenChange={(open) => !open && setDeleting(null)}
            >
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle className="font-display text-xl">
                            Hapus kategori ini?
                        </DialogTitle>
                        <DialogDescription>
                            Kategori {deleting?.name} akan dihapus dari daftar.
                            Tindakan ini tidak bisa dibatalkan.
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
                            onClick={confirmDelete}
                        >
                            <Trash2 aria-hidden />
                            Hapus kategori
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </>
    );
}

AdminCategories.layout = {
    breadcrumbs: [
        {
            title: 'Kategori',
            href: categories(),
        },
    ],
};
