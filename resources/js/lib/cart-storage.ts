import { useSyncExternalStore } from 'react';

export type CartItem = {
    productId: number;
    qty: number;
    name: string;
    price: number;
    storeName: string;
    storeSlug: string;
    imageUrl: string | null;
    stock: number;
};

const STORAGE_KEY = 'ls:cart';
const CHANGE_EVENT = 'ls:cart-change';
const EMPTY: CartItem[] = [];

let cache: CartItem[] | null = null;

function isCartItem(value: unknown): value is CartItem {
    if (typeof value !== 'object' || value === null) {
        return false;
    }

    const item = value as Record<string, unknown>;

    return (
        typeof item.productId === 'number' &&
        typeof item.qty === 'number' &&
        typeof item.name === 'string' &&
        typeof item.price === 'number' &&
        typeof item.storeName === 'string' &&
        typeof item.storeSlug === 'string' &&
        (typeof item.imageUrl === 'string' || item.imageUrl === null) &&
        typeof item.stock === 'number'
    );
}

function read(): CartItem[] {
    if (cache !== null) {
        return cache;
    }

    if (typeof window === 'undefined') {
        return EMPTY;
    }

    try {
        const parsed: unknown = JSON.parse(
            window.localStorage.getItem(STORAGE_KEY) ?? '[]',
        );

        cache = Array.isArray(parsed) ? parsed.filter(isCartItem) : [];
    } catch {
        cache = [];
    }

    return cache;
}

function write(items: CartItem[]): void {
    cache = items;

    if (typeof window === 'undefined') {
        return;
    }

    window.localStorage.setItem(STORAGE_KEY, JSON.stringify(items));
    window.dispatchEvent(new Event(CHANGE_EVENT));
}

function subscribe(callback: () => void): () => void {
    window.addEventListener(CHANGE_EVENT, callback);
    window.addEventListener('storage', callback);

    return () => {
        window.removeEventListener(CHANGE_EVENT, callback);
        window.removeEventListener('storage', callback);
    };
}

export function useCart(): CartItem[] {
    return useSyncExternalStore(subscribe, read, () => EMPTY);
}

export function cartCount(items: CartItem[]): number {
    return items.reduce((total, item) => total + item.qty, 0);
}

export function addToCart(item: Omit<CartItem, 'qty'>, qty: number): void {
    const items = read();
    const existing = items.find((entry) => entry.productId === item.productId);
    const nextQty = Math.min(item.stock, (existing?.qty ?? 0) + qty);

    if (nextQty <= 0) {
        return;
    }

    write(
        existing === undefined
            ? [...items, { ...item, qty: nextQty }]
            : items.map((entry) =>
                  entry.productId === item.productId
                      ? { ...item, qty: nextQty }
                      : entry,
              ),
    );
}

export function setCartQty(productId: number, qty: number): void {
    const items = read();

    write(
        items
            .map((item) =>
                item.productId === productId
                    ? { ...item, qty: Math.min(item.stock, qty) }
                    : item,
            )
            .filter((item) => item.qty > 0),
    );
}

export function removeFromCart(productId: number): void {
    write(read().filter((item) => item.productId !== productId));
}

export function clearCart(): void {
    write([]);
}

/**
 * Bentuk minimal yang diterima POST /keranjang/merge.
 */
export function cartPayload(): { product_id: number; qty: number }[] {
    return read().map((item) => ({
        product_id: item.productId,
        qty: item.qty,
    }));
}
