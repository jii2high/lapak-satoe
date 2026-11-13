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

export default function BuyerOrders({ orders, tab }: Props) {
    return (
        <>
            <Head title="Pesanan" />
            <OrdersPage orders={orders} tab={tab} viewer="buyer" />
        </>
    );
}
