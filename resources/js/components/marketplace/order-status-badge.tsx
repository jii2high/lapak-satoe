import { cn } from '@/lib/utils';
import type { OrderStatus } from '@/types';

type Viewer = 'buyer' | 'seller';

type BadgeStyle = {
    label: (viewer: Viewer) => string;
    className: string;
    dot: string;
};

const styles: Record<OrderStatus, BadgeStyle> = {
    unconfirmed: {
        label: () => 'Menunggu konfirmasi',
        className:
            'bg-highlight-soft text-[#A84305] dark:bg-secondary dark:text-foreground',
        dot: 'bg-highlight',
    },
    pending: {
        label: () => 'Diproses',
        className: 'bg-secondary text-secondary-foreground',
        dot: 'bg-primary',
    },
    awaiting_buyer: {
        label: (viewer) =>
            viewer === 'buyer' ? 'Menunggu konfirmasimu' : 'Menunggu buyer',
        className:
            'border border-primary bg-card text-primary dark:border-transparent dark:bg-secondary dark:text-foreground',
        dot: 'bg-primary',
    },
    completed: {
        label: () => 'Selesai',
        className:
            'bg-primary text-primary-foreground dark:bg-secondary dark:text-foreground',
        dot: 'bg-primary-foreground dark:bg-primary',
    },
    rejected: {
        label: () => 'Ditolak',
        className: 'bg-muted text-muted-foreground',
        dot: 'bg-muted-foreground',
    },
    cancelled: {
        label: () => 'Dibatalkan',
        className: 'bg-muted text-muted-foreground',
        dot: 'bg-muted-foreground',
    },
    disputed: {
        label: () => 'Dalam laporan',
        className: 'bg-destructive/10 text-destructive',
        dot: 'bg-destructive',
    },
};

export function OrderStatusBadge({
    status,
    viewer,
}: {
    status: OrderStatus;
    viewer: Viewer;
}) {
    const style = styles[status];

    return (
        <span
            className={cn(
                'inline-flex h-6 items-center gap-1.5 rounded-md px-2 text-xs font-semibold',
                style.className,
            )}
        >
            <span aria-hidden className={cn('size-2', style.dot)} />
            {style.label(viewer)}
        </span>
    );
}
