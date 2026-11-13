import { useCallback, useEffect, useState } from 'react';

export type NotificationItem = {
    id: string;
    data: {
        type: string;
        message: string;
        url?: string | null;
        order_id?: number;
        [key: string]: unknown;
    };
    read_at: string | null;
    created_at: string | null;
};

type Feed = {
    notifications: NotificationItem[];
    unread_count: number;
};

function csrfToken(): string {
    const match = document.cookie.match(/(?:^|;\s*)XSRF-TOKEN=([^;]+)/);

    return match ? decodeURIComponent(match[1]) : '';
}

/**
 * Lonceng notifikasi (TRD §8): ambil feed tiap 30 detik, jeda saat tab tidak aktif.
 */
export function useNotifications() {
    const [items, setItems] = useState<NotificationItem[]>([]);
    const [unread, setUnread] = useState(0);

    const refresh = useCallback(async () => {
        const response = await fetch('/notifications', {
            headers: { Accept: 'application/json' },
            credentials: 'same-origin',
        });

        if (!response.ok) {
            return;
        }

        const feed: Feed = await response.json();

        setItems(feed.notifications);
        setUnread(feed.unread_count);
    }, []);

    useEffect(() => {
        const tick = () => {
            if (!document.hidden) {
                void refresh();
            }
        };

        tick();

        const timer = window.setInterval(tick, 30000);
        document.addEventListener('visibilitychange', tick);

        return () => {
            window.clearInterval(timer);
            document.removeEventListener('visibilitychange', tick);
        };
    }, [refresh]);

    /** Tanpa `id` semua notifikasi ditandai sudah dibaca. */
    const markRead = useCallback(
        async (id?: string) => {
            await fetch('/notifications/read', {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-XSRF-TOKEN': csrfToken(),
                },
                body: JSON.stringify(id === undefined ? {} : { id }),
            });

            await refresh();
        },
        [refresh],
    );

    return { items, unread, markRead, refresh };
}
