import { useState } from 'react';
import { useSessionCache, useSessionKey } from '@/hooks/use-session-cache';
import { fetchJson } from '@/lib/http';

type Stats = { range: string };

/**
 * Statistik dashboard dari endpoint JSON (TRD 5, 8). Data awal datang dari props
 * Inertia, dikunci per rentang di cache sesi supaya rentang yang pernah dibuka
 * tampil instan lalu disegarkan di latar.
 */
export function useDashboardStats<T extends Stats>(initial: T, path: string) {
    const [range, setRange] = useState(initial.range);
    const key = useSessionKey(`dashboard:${range}`);

    const { data, revalidating, failed, refresh } = useSessionCache<T>(
        key,
        () => fetchJson<T>(`${path}?range=${encodeURIComponent(range)}`),
        range === initial.range ? initial : undefined,
    );

    return {
        stats: data ?? initial,
        range,
        setRange,
        loading: revalidating,
        failed,
        retry: refresh,
    };
}
