import { OrderStatusBadge } from '@/components/marketplace/order-status-badge';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { formatRupiah } from '@/lib/format';
import type { OrderData } from '@/types';

export function OrderDetailDialog({
    order,
    viewer,
    open,
    onOpenChange,
}: {
    order: OrderData;
    viewer: 'buyer' | 'seller';
    open: boolean;
    onOpenChange: (open: boolean) => void;
}) {
    const title =
        viewer === 'buyer' ? order.store.name : `@${order.buyer.username}`;

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-[640px]">
                <DialogHeader>
                    <DialogTitle className="font-display text-xl tabular-nums">
                        {order.code}
                    </DialogTitle>
                    <DialogDescription>{title}</DialogDescription>
                </DialogHeader>

                <div className="flex items-center justify-between border-y border-dashed border-border py-3">
                    <OrderStatusBadge status={order.status} viewer={viewer} />
                    <span className="text-sm text-muted-foreground tabular-nums">
                        {order.created_at === null
                            ? ''
                            : new Date(order.created_at).toLocaleDateString(
                                  'id-ID',
                                  {
                                      day: 'numeric',
                                      month: 'long',
                                      year: 'numeric',
                                  },
                              )}
                    </span>
                </div>

                <ul className="grid gap-3">
                    {order.items.map((item, index) => (
                        <li
                            key={index}
                            className="flex items-start justify-between gap-4"
                        >
                            <span className="min-w-0 text-sm">{item.name}</span>
                            <span className="shrink-0 text-right">
                                <span className="block text-xs text-muted-foreground tabular-nums">
                                    {item.qty} × {formatRupiah(item.price)}
                                </span>
                                <span className="block text-sm font-semibold tabular-nums">
                                    {formatRupiah(item.subtotal)}
                                </span>
                            </span>
                        </li>
                    ))}
                </ul>

                <div className="flex items-center justify-between border-t border-dashed border-border pt-3">
                    <span className="font-semibold">Total</span>
                    <span className="font-display text-lg font-bold tabular-nums">
                        {formatRupiah(order.total)}
                    </span>
                </div>

                {order.cancel_note !== null && (
                    <p className="border border-border bg-muted px-3 py-2 text-sm text-muted-foreground">
                        Alasan pembatalan: {order.cancel_note}
                    </p>
                )}
            </DialogContent>
        </Dialog>
    );
}
