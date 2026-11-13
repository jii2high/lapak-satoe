import { useEffect, useRef, useState } from 'react';

type Stats = { range: string };

/**
 * Memuat statistik dashboard dari endpoint JSON (TRD 5, 8). Data awal datang dari
 * props Inertia, jadi halaman langsung tampil; fetch hanya jalan saat filter berubah.
 */
export function useDashboardStats<T extends Stats>(initial: T, path: string) {
    const [stats, setStats] = useState(initial);
    const [range, setRange] = useState(initial.range);
    const [loading, setLoading] = useState(false);
    const [failed, setFailed] = useState(false);
    const [attempt, setAttempt] = useState(0);
    const loaded = useRef(initial.range);

    useEffect(() => {
        if (range === loaded.current && attempt === 0) {
            return;
        }

        const controller = new AbortController();
        setLoading(true);
        setFailed(false);

        fetch(`${path}?range=${encodeURIComponent(range)}`, {
            headers: { Accept: 'application/json' },
            signal: controller.signal,
        })
            .then((response) => {
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}`);
                }

                return response.json() as Promise<T>;
            })
            .then((data) => {
                loaded.current = data.range;
                setStats(data);
            })
            .catch((exception: unknown) => {
                if (
                    exception instanceof DOMException &&
                    exception.name === 'AbortError'
                ) {
                    return;
                }

                setFailed(true);
            })
            .finally(() => setLoading(false));

        return () => controller.abort();
    }, [range, attempt, path]);

    return {
        stats,
        range,
        setRange,
        loading,
        failed,
        retry: () => setAttempt((value) => value + 1),
    };
}
