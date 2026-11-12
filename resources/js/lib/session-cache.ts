export function clearSessionCache(userId: number): void {
    if (typeof window === 'undefined') {
        return;
    }

    const prefix = `ls:${userId}:`;

    Object.keys(localStorage)
        .filter((key) => key.startsWith(prefix))
        .forEach((key) => localStorage.removeItem(key));
}
