import { Head } from '@inertiajs/react';
import {
    OrdersPage,
    type OrderFilters,
    type OrderTab,
} from '@/components/marketplace/orders-page';
import { useSessionCache, useSessionKey } from '@/hooks/use-session-cache';
import { fetchJson } from '@/lib/http';
import { orders as ordersData } from '@/routes/seller/data';
import type { OrderData, Paginator } from '@/types';

type Props = {
    orders: Paginator<OrderData>;
    tab: OrderTab;
    filters: OrderFilters;
};

export default function SellerOrders(props: Props) {
    const { orders, tab, filters } = props;
    const key = useSessionKey(
        `seller:orders:${tab}:${orders.current_page}:${filters.q}:${filters.status}`,
    );

    const { data } = useSessionCache<Props>(
        key,
        () =>
            fetchJson<Props>(
                ordersData.url({
                    query: {
                        tab,
                        ...(filters.q === '' ? {} : { q: filters.q }),
                        ...(filters.status === 'all'
                            ? {}
                            : { status: filters.status }),
                        page: orders.current_page,
                    },
                }),
            ),
        props,
    );

    const view = data ?? props;

    return (
        <>
            <Head title="Pesanan" />
            <div className="flex flex-1 flex-col px-4 py-6 md:px-6">
                <OrdersPage
                    orders={view.orders}
                    tab={view.tab}
                    viewer="seller"
                    filters={view.filters}
                />
            </div>
        </>
    );
}
