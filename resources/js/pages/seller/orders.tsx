import { Head } from '@inertiajs/react';
import {
    OrdersPage,
    type OrderTab,
} from '@/components/marketplace/orders-page';
import type { OrderData, Paginator } from '@/types';

type Props = {
    orders: Paginator<OrderData>;
    tab: OrderTab;
};

export default function SellerOrders({ orders, tab }: Props) {
    return (
        <>
            <Head title="Pesanan" />
            <div className="flex flex-1 flex-col px-4 py-6 md:px-6">
                <OrdersPage orders={orders} tab={tab} viewer="seller" />
            </div>
        </>
    );
}
