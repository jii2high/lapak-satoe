import { usePage } from '@inertiajs/react';
import { useCallback, useEffect, useRef, useState } from 'react';
import {
    invalidateSessionCache,
    readSessionCache,
    sessionCacheKey,
    writeSessionCache,
} from '@/lib/session-cache';
import type { Auth } from '@/types';

/**
 * Iterasi SWR untuk data server (PRD NFR-1, TRD 8): tampilkan cache sesi dulu,
 * lalu segarkan di latar. `initial` adalah props Inertia; selama tidak ada cache,
 * props dipakai apa adanya tanpa permintaan tambahan. Panggil `refresh` setelah
 * mutasi untuk memaksa pengambilan ulang.
 *
 * `initial` harus referensi yang stabil (mis. objek props), bukan literal baru
 * tiap render, supaya efek tidak berjalan berulang.
 */
export function useSessionCache<T>(
    key: string,
    fetcher: () => Promise<T>,
    initial?: T,
) {
    const [data, setData] = useState<T | null>(
        () => readSessionCache<T>(key) ?? initial ?? null,
    );
    const [revalidating, setRevalidating] = useState(false);
    const [failed, setFailed] = useState(false);
    const [token, setToken] = useState(0);
    const fetcherRef = useRef(fetcher);
    const forceRef = useRef(false);
    fetcherRef.current = fetcher;

    useEffect(() => {
        let cancelled = false;
        const cached = readSessionCache<T>(key);
        const mustFetch =
            forceRef.current || cached !== null || initial === undefined;
        forceRef.current = false;

        setFailed(false);

        if (!mustFetch) {
            if (initial !== undefined) {
                setData(initial);
                writeSessionCache(key, initial);
            }

            setRevalidating(false);

            return () => {
                cancelled = true;
            };
        }

        const current = cached ?? initial;

        // Keep the previous payload visible while a different query loads.
        if (current !== undefined) {
            setData(current);
        }

        setRevalidating(true);

        fetcherRef
            .current()
            .then((value) => {
                if (cancelled) {
                    return;
                }

                setData(value);
                writeSessionCache(key, value);
            })
            .catch(() => {
                if (!cancelled) {
                    setFailed(true);
                }
            })
            .finally(() => {
                if (!cancelled) {
                    setRevalidating(false);
                }
            });

        return () => {
            cancelled = true;
        };
    }, [key, token, initial]);

    const refresh = useCallback(() => {
        forceRef.current = true;
        setToken((value) => value + 1);
    }, []);

    return { data, revalidating, failed, refresh };
}

/**
 * Kunci cache untuk pengguna aktif, dibedakan per role.
 */
export function useSessionKey(name: string): string {
    const { auth } = usePage<{ auth: Auth }>().props;

    return sessionCacheKey(
        auth.user?.id ?? 0,
        auth.user?.active_role ?? 'guest',
        name,
    );
}

/**
 * Hapus seluruh halaman cache satu resource (mis. `seller:products`) setelah mutasi.
 */
export function useCacheInvalidator(): (name: string) => void {
    const { auth } = usePage<{ auth: Auth }>().props;

    return useCallback(
        (name: string) =>
            invalidateSessionCache(
                sessionCacheKey(
                    auth.user?.id ?? 0,
                    auth.user?.active_role ?? 'guest',
                    name,
                ),
            ),
        [auth.user?.id, auth.user?.active_role],
    );
}
