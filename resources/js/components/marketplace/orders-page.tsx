import { Link, router } from '@inertiajs/react';
import { PackageSearch, Search } from 'lucide-react';
import { useEffect, useState } from 'react';
import { OrderCard } from '@/components/marketplace/order-card';
import { Pagination } from '@/components/pagination';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { useDebounce } from '@/hooks/use-debounce';
import { cn } from '@/lib/utils';
import { index as buyerOrders } from '@/routes/orders';
import { index as sellerOrders } from '@/routes/seller/orders';
import type { OrderData, Paginator } from '@/types';

export type OrderTab = 'aktif' | 'riwayat';

export type OrderFilters = {
    q: string;
    status: string;
};

const tabs: { value: OrderTab; label: string }[] = [
    { value: 'aktif', label: 'Aktif' },
    { value: 'riwayat', label: 'Riwayat' },
];

const statusFilters: Record<OrderTab, { value: string; label: string }[]> = {
    aktif: [
        { value: 'all', label: 'Semua status' },
        { value: 'unconfirmed', label: 'Menunggu konfirmasi' },
        { value: 'pending', label: 'Diproses' },
        { value: 'awaiting_buyer', label: 'Menunggu buyer' },
        { value: 'disputed', label: 'Dalam laporan' },
    ],
    riwayat: [
        { value: 'all', label: 'Semua status' },
        { value: 'completed', label: 'Selesai' },
        { value: 'rejected', label: 'Ditolak' },
        { value: 'cancelled', label: 'Dibatalkan' },
    ],
};

function ordersQuery(tab: OrderTab, q: string, status: string) {
    return {
        tab,
        ...(q === '' ? {} : { q }),
        ...(status === 'all' ? {} : { status }),
    };
}

export function OrdersPage({
    orders,
    tab,
    viewer,
    filters,
}: {
    orders: Paginator<OrderData>;
    tab: OrderTab;
    viewer: 'buyer' | 'seller';
    filters?: OrderFilters;
}) {
    const index = viewer === 'buyer' ? buyerOrders : sellerOrders;
    const [search, setSearch] = useState(filters?.q ?? '');
    const debounced = useDebounce(search);
    const filtered =
        filters !== undefined && (filters.q !== '' || filters.status !== 'all');

    useEffect(() => {
        if (filters === undefined || debounced === filters.q) {
            return;
        }

        router.get(
            index.url({ query: ordersQuery(tab, debounced, filters.status) }),
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
            },
        );
    }, [debounced, filters, tab, index]);

    const changeStatus = (next: string) => {
        if (filters === undefined) {
            return;
        }

        router.get(index.url({ query: ordersQuery(tab, filters.q, next) }), {
            preserveState: true,
            preserveScroll: true,
        });
    };

    return (
        <div className="grid gap-4">
            <div>
                <h1 className="font-display text-2xl font-bold">Pesanan</h1>
                <p className="mt-1 text-sm text-muted-foreground">
                    {viewer === 'buyer'
                        ? `${orders.total} pesanan kamu.`
                        : `${orders.total} pesanan di lapakmu.`}
                </p>
            </div>

            <nav
                aria-label="Pilih tab pesanan"
                className="flex gap-1 border-b border-border"
            >
                {tabs.map((item) => {
                    const active = item.value === tab;

                    return (
                        <Link
                            key={item.value}
                            href={index.url({
                                query:
                                    filters === undefined
                                        ? { tab: item.value }
                                        : ordersQuery(
                                              item.value,
                                              filters.q,
                                              filters.status,
                                          ),
                            })}
                            preserveScroll
                            aria-current={active ? 'page' : undefined}
                            className={cn(
                                '-mb-px border-b-2 px-3 py-2 text-sm',
                                active
                                    ? 'border-primary font-semibold text-primary'
                                    : 'border-transparent text-muted-foreground hover:text-foreground',
                            )}
                        >
                            {item.label}
                        </Link>
                    );
                })}
            </nav>

            {filters !== undefined && (
                <div className="flex flex-col gap-2 sm:flex-row">
                    <div className="relative flex-1">
                        <Search
                            aria-hidden
                            className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                        />
                        <Input
                            value={search}
                            onChange={(event) => setSearch(event.target.value)}
                            aria-label="Cari pesanan"
                            placeholder="Cari kode pesanan atau pembeli"
                            className="pl-9"
                        />
                    </div>

                    <Select value={filters.status} onValueChange={changeStatus}>
                        <SelectTrigger
                            aria-label="Filter status pesanan"
                            className="w-full sm:w-56"
                        >
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            {statusFilters[tab].map((item) => (
                                <SelectItem key={item.value} value={item.value}>
                                    {item.label}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </div>
            )}

            {orders.data.length === 0 ? (
                <div className="border border-border bg-card px-4 py-10 text-center">
                    <PackageSearch
                        aria-hidden
                        className="mx-auto size-8 text-muted-foreground"
                    />
                    <p className="mt-3 font-display text-lg font-semibold">
                        {filtered
                            ? 'Tidak ada pesanan cocok'
                            : 'Belum ada pesanan'}
                    </p>
                    <p className="mx-auto mt-1 max-w-prose text-sm text-muted-foreground">
                        {filtered
                            ? 'Ubah kata kunci atau filter status.'
                            : viewer === 'buyer'
                              ? 'Pesanan yang kamu buat lewat WhatsApp muncul di sini.'
                              : 'Pesanan yang masuk ke lapakmu muncul di sini.'}
                    </p>
                </div>
            ) : (
                <div className="grid gap-4">
                    {orders.data.map((order) => (
                        <OrderCard
                            key={order.id}
                            order={order}
                            viewer={viewer}
                        />
                    ))}
                </div>
            )}

            <Pagination links={orders.links} lastPage={orders.last_page} />
        </div>
    );
}
