import { Link } from '@inertiajs/react';
import { PackageSearch } from 'lucide-react';
import { OrderCard } from '@/components/marketplace/order-card';
import { Pagination } from '@/components/pagination';
import { cn } from '@/lib/utils';
import { index as buyerOrders } from '@/routes/orders';
import { index as sellerOrders } from '@/routes/seller/orders';
import type { OrderData, Paginator } from '@/types';

export type OrderTab = 'aktif' | 'riwayat';

const tabs: { value: OrderTab; label: string }[] = [
    { value: 'aktif', label: 'Aktif' },
    { value: 'riwayat', label: 'Riwayat' },
];

export function OrdersPage({
    orders,
    tab,
    viewer,
}: {
    orders: Paginator<OrderData>;
    tab: OrderTab;
    viewer: 'buyer' | 'seller';
}) {
    const index = viewer === 'buyer' ? buyerOrders : sellerOrders;

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
                            href={index.url({ query: { tab: item.value } })}
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

            {orders.data.length === 0 ? (
                <div className="border border-border bg-card px-4 py-10 text-center">
                    <PackageSearch
                        aria-hidden
                        className="mx-auto size-8 text-muted-foreground"
                    />
                    <p className="mt-3 font-display text-lg font-semibold">
                        Belum ada pesanan
                    </p>
                    <p className="mx-auto mt-1 max-w-prose text-sm text-muted-foreground">
                        {viewer === 'buyer'
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
