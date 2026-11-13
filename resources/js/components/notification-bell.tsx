import { router } from '@inertiajs/react';
import { Bell } from 'lucide-react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import {
    Popover,
    PopoverContent,
    PopoverHeader,
    PopoverTitle,
    PopoverTrigger,
} from '@/components/ui/popover';
import {
    useNotifications,
    type NotificationItem,
} from '@/hooks/use-notifications';
import { cn } from '@/lib/utils';
import { confirm } from '@/routes/orders';

const timeFormatter = new Intl.DateTimeFormat('id-ID', {
    dateStyle: 'medium',
    timeStyle: 'short',
});

export function NotificationBell({
    triggerClassName,
}: {
    triggerClassName?: string;
}) {
    const { items, unread, markRead, refresh } = useNotifications();
    const [open, setOpen] = useState(false);

    const openItem = async (item: NotificationItem) => {
        setOpen(false);
        await markRead(item.id);

        if (item.data.url) {
            router.visit(item.data.url);
        }
    };

    const confirmOrder = (item: NotificationItem) => {
        setOpen(false);
        void markRead(item.id);
        router.post(
            confirm.url(item.data.order_id as number),
            {},
            { preserveScroll: true, onFinish: () => void refresh() },
        );
    };

    return (
        <Popover
            open={open}
            onOpenChange={(next) => {
                setOpen(next);

                if (next) {
                    void refresh();
                }
            }}
        >
            <PopoverTrigger asChild>
                <Button
                    type="button"
                    variant="ghost"
                    size="icon"
                    aria-label={
                        unread > 0
                            ? `Notifikasi, ${unread} belum dibaca`
                            : 'Notifikasi'
                    }
                    className={cn('relative', triggerClassName)}
                >
                    <Bell aria-hidden className="size-5" />
                    {unread > 0 && (
                        <span className="absolute -top-0.5 -right-0.5 flex h-[18px] min-w-[18px] items-center justify-center bg-highlight px-1 text-xs font-bold text-highlight-foreground">
                            {unread}
                        </span>
                    )}
                </Button>
            </PopoverTrigger>

            <PopoverContent
                align="end"
                className="w-[min(21rem,calc(100vw-2rem))] p-0"
            >
                <PopoverHeader className="flex-row items-center justify-between gap-2 border-b border-border p-3">
                    <PopoverTitle className="font-display">
                        Notifikasi
                    </PopoverTitle>
                    {unread > 0 && (
                        <Button
                            type="button"
                            variant="ghost"
                            size="sm"
                            onClick={() => void markRead()}
                        >
                            Tandai semua dibaca
                        </Button>
                    )}
                </PopoverHeader>

                {items.length === 0 ? (
                    <p className="p-4 text-sm text-muted-foreground">
                        Belum ada notifikasi.
                    </p>
                ) : (
                    <ul className="max-h-80 overflow-y-auto">
                        {items.map((item) => (
                            <li
                                key={item.id}
                                className="border-b border-border last:border-b-0"
                            >
                                <button
                                    type="button"
                                    onClick={() => void openItem(item)}
                                    className="flex w-full min-h-11 flex-col items-start gap-1 p-3 text-left text-sm hover:bg-secondary"
                                >
                                    <span className="flex items-center gap-2 font-medium">
                                        {item.read_at === null && (
                                            <span
                                                aria-hidden
                                                className="size-2 shrink-0 bg-highlight"
                                            />
                                        )}
                                        {item.data.message}
                                    </span>
                                    {item.created_at !== null && (
                                        <span className="text-xs text-muted-foreground">
                                            {timeFormatter.format(
                                                new Date(item.created_at),
                                            )}
                                        </span>
                                    )}
                                </button>

                                {item.data.type === 'order_awaiting_buyer' &&
                                    item.read_at === null && (
                                        <div className="px-3 pb-3">
                                            <Button
                                                type="button"
                                                size="sm"
                                                onClick={() => confirmOrder(item)}
                                            >
                                                Konfirmasi selesai
                                            </Button>
                                        </div>
                                    )}
                            </li>
                        ))}
                    </ul>
                )}
            </PopoverContent>
        </Popover>
    );
}
