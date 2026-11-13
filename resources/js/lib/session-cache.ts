const PREFIX = 'ls:';

/**
 * Cache sesi browser (PRD NFR-1, TRD 8). Kunci selalu `ls:{userId}:{role}:{key}`
 * supaya data satu pengguna/role tidak pernah terbaca oleh yang lain.
 */
export function sessionCacheKey(
    userId: number,
    role: string,
    key: string,
): string {
    return `${PREFIX}${userId}:${role}:${key}`;
}

function storage(): Storage | null {
    if (typeof window === 'undefined') {
        return null;
    }

    return window.sessionStorage;
}

export function readSessionCache<T>(key: string): T | null {
    const store = storage();
    const raw = store?.getItem(key) ?? null;

    if (raw === null) {
        return null;
    }

    try {
        return JSON.parse(raw) as T;
    } catch {
        store?.removeItem(key);

        return null;
    }
}

export function writeSessionCache<T>(key: string, value: T): void {
    storage()?.setItem(key, JSON.stringify(value));
}

export function invalidateSessionCache(prefix: string): void {
    const store = storage();

    if (store === null) {
        return;
    }

    Object.keys(store)
        .filter((key) => key.startsWith(prefix))
        .forEach((key) => store.removeItem(key));
}

export function clearSessionCache(userId: number): void {
    invalidateSessionCache(`${PREFIX}${userId}:`);
}
