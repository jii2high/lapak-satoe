import { Head, Link, router } from '@inertiajs/react';
import {
    ImageOff,
    Pencil,
    Plus,
    Search,
    Trash2,
    PackageSearch,
} from 'lucide-react';
import { useEffect, useState } from 'react';
import { Pagination } from '@/components/pagination';
import { Badge } from '@/components/ui/badge';
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
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
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
    useCacheInvalidator,
    useSessionCache,
    useSessionKey,
} from '@/hooks/use-session-cache';
import { fetchJson } from '@/lib/http';
import { formatRupiah } from '@/lib/format';
import {
    bulk as bulkProducts,
    create as createProduct,
    destroy as destroyProduct,
    edit as editProduct,
    index as productsIndex,
} from '@/routes/seller/products';
import { products as productsData } from '@/routes/seller/data';
import type { Paginator } from '@/types';

type ProductRow = {
    id: number;
    name: string;
    category: string;
    price: number;
    stock: number;
    reserved: number;
    is_active: boolean;
    is_suspended: boolean;
    image_url: string | null;
};

type Filters = {
    q: string;
    status: string;
};

type Props = {
    products: Paginator<ProductRow>;
    filters: Filters;
};

const statusFilters = [
    { value: 'all', label: 'Semua status' },
    { value: 'active', label: 'Aktif' },
    { value: 'inactive', label: 'Nonaktif' },
    { value: 'suspended', label: 'Dinonaktifkan admin' },
];

function statusOf(product: ProductRow): {
    label: string;
    className: string;
} {
    if (product.is_suspended) {
        return {
            label: 'Dinonaktifkan admin',
            className: 'border-destructive bg-destructive/10 text-destructive',
        };
    }

    if (product.is_active) {
        return {
            label: 'Aktif',
            className: 'border-primary bg-secondary text-secondary-foreground',
        };
    }

    return {
        label: 'Nonaktif',
        className: 'border-border bg-muted text-muted-foreground',
    };
}

function StatusBadge({ product }: { product: ProductRow }) {
    const status = statusOf(product);

    return (
        <Badge variant="outline" className={status.className}>
            {status.label}
        </Badge>
    );
}

function ProductThumb({
    product,
    className,
}: {
    product: ProductRow;
    className: string;
}) {
    if (product.image_url === null) {
        return (
            <span
                className={`flex items-center justify-center border border-border bg-muted text-muted-foreground ${className}`}
            >
                <ImageOff aria-hidden className="size-4" />
            </span>
        );
    }

    return (
        <img
            src={product.image_url}
            alt=""
            className={`border border-border object-cover ${className}`}
        />
    );
}

export default function SellerProducts(props: Props) {
    const key = useSessionKey(
        `seller:products:${props.products.current_page}:${props.filters.q}:${props.filters.status}`,
    );

    const { data } = useSessionCache<Props>(
        key,
        () =>
            fetchJson<Props>(
                productsData.url({
                    query: {
                        ...(props.filters.q === ''
                            ? {}
                            : { q: props.filters.q }),
                        ...(props.filters.status === 'all'
                            ? {}
                            : { status: props.filters.status }),
                        page: props.products.current_page,
                    },
                }),
            ),
        props,
    );

    const invalidate = useCacheInvalidator();
    const { products, filters } = data ?? props;
    const [search, setSearch] = useState(filters.q);
    const debounced = useDebounce(search);
    const [selected, setSelected] = useState<number[]>([]);
    const [deleting, setDeleting] = useState<ProductRow | null>(null);
    const [bulkDeleteOpen, setBulkDeleteOpen] = useState(false);
    const [busy, setBusy] = useState(false);

    const rows = products.data;
    const pageIds = rows.map((product) => product.id);
    const allSelected =
        pageIds.length > 0 && pageIds.every((id) => selected.includes(id));
    const someSelected = selected.length > 0 && !allSelected;
    const available = (product: ProductRow) =>
        Math.max(product.stock - product.reserved, 0);

    useEffect(() => {
        if (debounced === filters.q) {
            return;
        }

        router.get(
            productsIndex.url({
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

    useEffect(() => {
        setSelected([]);
    }, [products.current_page, filters.q, filters.status]);

    const changeStatus = (status: string) => {
        router.get(
            productsIndex.url({
                query: {
                    ...(filters.q === '' ? {} : { q: filters.q }),
                    ...(status === 'all' ? {} : { status }),
                },
            }),
            { preserveState: true, preserveScroll: true },
        );
    };

    const toggleAll = (checked: boolean | 'indeterminate') => {
        setSelected(checked === true ? pageIds : []);
    };

    const toggleOne = (id: number, checked: boolean | 'indeterminate') => {
        setSelected((current) =>
            checked === true
                ? [...current, id]
                : current.filter((value) => value !== id),
        );
    };

    const runBulk = (action: 'activate' | 'deactivate' | 'delete') => {
        setBusy(true);
        router.post(
            bulkProducts.url(),
            { action, ids: selected },
            {
                preserveScroll: true,
                onSuccess: () => {
                    setSelected([]);
                    setBulkDeleteOpen(false);
                    invalidate('seller:products');
                },
                onFinish: () => setBusy(false),
            },
        );
    };

    const confirmDelete = () => {
        if (deleting === null) {
            return;
        }

        router.delete(destroyProduct.url(deleting.id), {
            preserveScroll: true,
            onSuccess: () => {
                setDeleting(null);
                setSelected((current) =>
                    current.filter((id) => id !== deleting.id),
                );
                invalidate('seller:products');
            },
        });
    };

    const emptyState = (
        <div className="px-4 py-10 text-center">
            <PackageSearch
                aria-hidden
                className="mx-auto size-8 text-muted-foreground"
            />
            <p className="mt-3 font-display text-lg font-semibold">
                {filters.q !== '' || filters.status !== 'all'
                    ? 'Tidak ada produk cocok'
                    : 'Belum ada produk'}
            </p>
            <p className="mx-auto mt-1 max-w-prose text-sm text-muted-foreground">
                {filters.q !== '' || filters.status !== 'all'
                    ? 'Ubah kata kunci atau filter status.'
                    : 'Tambahkan produk pertama supaya buyer bisa melihatnya di katalog.'}
            </p>
            {filters.q === '' && filters.status === 'all' && (
                <Button asChild className="mt-4">
                    <Link href={createProduct()}>
                        <Plus aria-hidden />
                        Tambah produk
                    </Link>
                </Button>
            )}
        </div>
    );

    return (
        <>
            <Head title="Produk" />

            <div className="flex flex-1 flex-col gap-4 px-4 py-6 md:px-6">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <h1 className="font-display text-2xl font-bold">
                            Produk
                        </h1>
                        <p className="mt-1 text-sm text-muted-foreground">
                            {products.total} produk di lapakmu.
                        </p>
                    </div>

                    <Button asChild>
                        <Link href={createProduct()}>
                            <Plus aria-hidden />
                            Tambah produk
                        </Link>
                    </Button>
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
                            aria-label="Cari produk"
                            placeholder="Cari nama produk"
                            className="pl-9"
                        />
                    </div>

                    <Select value={filters.status} onValueChange={changeStatus}>
                        <SelectTrigger
                            aria-label="Filter status produk"
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

                {selected.length > 0 && (
                    <div className="flex flex-wrap items-center gap-2 border border-border bg-secondary px-3 py-2">
                        <p className="text-sm font-medium">
                            {selected.length} produk dipilih
                        </p>
                        <div className="flex flex-wrap gap-1">
                            <Button
                                size="sm"
                                variant="outline"
                                disabled={busy}
                                onClick={() => runBulk('activate')}
                            >
                                Aktifkan
                            </Button>
                            <Button
                                size="sm"
                                variant="outline"
                                disabled={busy}
                                onClick={() => runBulk('deactivate')}
                            >
                                Nonaktifkan
                            </Button>
                            <Button
                                size="sm"
                                variant="destructive"
                                disabled={busy}
                                onClick={() => setBulkDeleteOpen(true)}
                            >
                                <Trash2 aria-hidden />
                                Hapus
                            </Button>
                            <Button
                                size="sm"
                                variant="ghost"
                                disabled={busy}
                                onClick={() => setSelected([])}
                            >
                                Batalkan
                            </Button>
                        </div>
                    </div>
                )}

                <div className="hidden border border-border bg-card md:block">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead className="w-12">
                                    <Checkbox
                                        aria-label="Pilih semua produk di halaman ini"
                                        checked={
                                            allSelected
                                                ? true
                                                : someSelected
                                                  ? 'indeterminate'
                                                  : false
                                        }
                                        onCheckedChange={toggleAll}
                                    />
                                </TableHead>
                                <TableHead>Produk</TableHead>
                                <TableHead>Kategori</TableHead>
                                <TableHead className="text-right">
                                    Harga
                                </TableHead>
                                <TableHead className="text-right">
                                    Stok
                                </TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead className="text-right">
                                    Aksi
                                </TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {rows.length === 0 ? (
                                <TableRow>
                                    <TableCell colSpan={7} className="p-0">
                                        {emptyState}
                                    </TableCell>
                                </TableRow>
                            ) : (
                                rows.map((product) => (
                                    <TableRow key={product.id}>
                                        <TableCell>
                                            <Checkbox
                                                aria-label={`Pilih ${product.name}`}
                                                checked={selected.includes(
                                                    product.id,
                                                )}
                                                onCheckedChange={(checked) =>
                                                    toggleOne(
                                                        product.id,
                                                        checked,
                                                    )
                                                }
                                            />
                                        </TableCell>
                                        <TableCell>
                                            <div className="flex items-center gap-3">
                                                <ProductThumb
                                                    product={product}
                                                    className="size-10 shrink-0"
                                                />
                                                <span className="max-w-56 truncate font-medium">
                                                    {product.name}
                                                </span>
                                            </div>
                                        </TableCell>
                                        <TableCell className="text-muted-foreground">
                                            {product.category}
                                        </TableCell>
                                        <TableCell className="text-right tabular-nums">
                                            {formatRupiah(product.price)}
                                        </TableCell>
                                        <TableCell className="text-right tabular-nums">
                                            {available(product)}
                                        </TableCell>
                                        <TableCell>
                                            <StatusBadge product={product} />
                                        </TableCell>
                                        <TableCell>
                                            <div className="flex justify-end gap-1">
                                                <Button
                                                    asChild
                                                    variant="ghost"
                                                    size="sm"
                                                >
                                                    <Link
                                                        href={editProduct(
                                                            product.id,
                                                        )}
                                                    >
                                                        <Pencil aria-hidden />
                                                        Ubah
                                                    </Link>
                                                </Button>
                                                <Button
                                                    variant="ghost"
                                                    size="sm"
                                                    onClick={() =>
                                                        setDeleting(product)
                                                    }
                                                >
                                                    <Trash2 aria-hidden />
                                                    Hapus
                                                </Button>
                                            </div>
                                        </TableCell>
                                    </TableRow>
                                ))
                            )}
                        </TableBody>
                    </Table>
                </div>

                <div className="border border-border bg-card md:hidden">
                    {rows.length === 0 ? (
                        emptyState
                    ) : (
                        <ul>
                            {rows.map((product) => (
                                <li
                                    key={product.id}
                                    className="flex gap-3 border-b border-border p-4 last:border-b-0"
                                >
                                    <Checkbox
                                        className="mt-1"
                                        aria-label={`Pilih ${product.name}`}
                                        checked={selected.includes(product.id)}
                                        onCheckedChange={(checked) =>
                                            toggleOne(product.id, checked)
                                        }
                                    />

                                    <ProductThumb
                                        product={product}
                                        className="size-12 shrink-0"
                                    />

                                    <div className="min-w-0 flex-1">
                                        <div className="flex items-start justify-between gap-2">
                                            <div className="min-w-0">
                                                <p className="truncate font-medium">
                                                    {product.name}
                                                </p>
                                                <p className="truncate text-xs text-muted-foreground">
                                                    {product.category}
                                                </p>
                                            </div>
                                            <StatusBadge product={product} />
                                        </div>

                                        <p className="mt-1 text-sm tabular-nums">
                                            {formatRupiah(product.price)}
                                            <span className="text-muted-foreground">
                                                {' '}
                                                · stok {available(product)}
                                            </span>
                                        </p>

                                        <div className="mt-2 flex gap-1">
                                            <Button
                                                asChild
                                                variant="ghost"
                                                size="sm"
                                            >
                                                <Link
                                                    href={editProduct(
                                                        product.id,
                                                    )}
                                                >
                                                    <Pencil aria-hidden />
                                                    Ubah
                                                </Link>
                                            </Button>
                                            <Button
                                                variant="ghost"
                                                size="sm"
                                                onClick={() =>
                                                    setDeleting(product)
                                                }
                                            >
                                                <Trash2 aria-hidden />
                                                Hapus
                                            </Button>
                                        </div>
                                    </div>
                                </li>
                            ))}
                        </ul>
                    )}
                </div>

                <Pagination
                    links={products.links}
                    lastPage={products.last_page}
                />
            </div>

            <Dialog
                open={deleting !== null}
                onOpenChange={(open) => !open && setDeleting(null)}
            >
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle className="font-display text-xl">
                            Hapus produk ini?
                        </DialogTitle>
                        <DialogDescription>
                            {deleting?.name} akan dihapus dari daftar produkmu.
                            Riwayat pesanan lama tetap tersimpan.
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
                            Hapus produk
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            <Dialog open={bulkDeleteOpen} onOpenChange={setBulkDeleteOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle className="font-display text-xl">
                            Hapus {selected.length} produk?
                        </DialogTitle>
                        <DialogDescription>
                            Semua produk yang dipilih akan dihapus dari daftar
                            produkmu. Riwayat pesanan lama tetap tersimpan.
                        </DialogDescription>
                    </DialogHeader>

                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => setBulkDeleteOpen(false)}
                        >
                            Batal
                        </Button>
                        <Button
                            type="button"
                            variant="destructive"
                            disabled={busy}
                            onClick={() => runBulk('delete')}
                        >
                            <Trash2 aria-hidden />
                            Hapus produk
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </>
    );
}

SellerProducts.layout = {
    breadcrumbs: [
        {
            title: 'Produk',
            href: productsIndex(),
        },
    ],
};
