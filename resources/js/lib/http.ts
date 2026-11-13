export async function fetchJson<T>(
    input: string,
    signal?: AbortSignal,
): Promise<T> {
    const response = await fetch(input, {
        headers: { Accept: 'application/json' },
        signal,
    });

    if (!response.ok) {
        throw new Error(`HTTP ${response.status}`);
    }

    return response.json() as Promise<T>;
}
