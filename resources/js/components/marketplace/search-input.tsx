import { Link, router } from '@inertiajs/react';
import { Loader2, Search, X } from 'lucide-react';
import { useEffect, useId, useRef, useState } from 'react';
import { Input } from '@/components/ui/input';
import { useDebounce } from '@/hooks/use-debounce';
import { formatRupiah } from '@/lib/format';
import { cn } from '@/lib/utils';
import { explore } from '@/routes';
import { show as productShow } from '@/routes/products';
import { suggest } from '@/routes/search';
import { show as storeShow } from '@/routes/stores';
import type { SearchSuggestData } from '@/types';

const RECENT_KEY = 'ls:recent-search';
const RECENT_LIMIT = 8;

function readRecent(): string[] {
    try {
        const parsed: unknown = JSON.parse(
            window.localStorage.getItem(RECENT_KEY) ?? '[]',
        );

        return Array.isArray(parsed)
            ? parsed.filter(
                  (value): value is string => typeof value === 'string',
              )
            : [];
    } catch {
        return [];
    }
}

function saveRecent(query: string): void {
    const next = [
        query,
        ...readRecent().filter((entry) => entry !== query),
    ].slice(0, RECENT_LIMIT);

    window.localStorage.setItem(RECENT_KEY, JSON.stringify(next));
}

type Props = {
    autoFocus?: boolean;
    className?: string;
    placeholder?: string;
    defaultValue?: string;
};

export function SearchInput({
    autoFocus = false,
    className,
    placeholder = 'Cari produk atau lapak',
    defaultValue = '',
}: Props) {
    const [query, setQuery] = useState(defaultValue);
    const [open, setOpen] = useState(false);
    const [recent, setRecent] = useState<string[]>([]);
    const [results, setResults] = useState<SearchSuggestData | null>(null);
    const [loading, setLoading] = useState(false);
    const [failed, setFailed] = useState(false);
    const inputId = useId();

    const debounced = useDebounce(query, 200);
    const cache = useRef(new Map<string, SearchSuggestData>());
    const container = useRef<HTMLDivElement>(null);

    useEffect(() => {
        setRecent(readRecent());
    }, []);

    useEffect(() => {
        if (!open) {
            return;
        }

        const key = debounced.trim();
        const cached = cache.current.get(key);

        if (cached !== undefined) {
            setResults(cached);
            setFailed(false);
            setLoading(false);

            return;
        }

        let active = true;

        setLoading(true);
        setFailed(false);

        fetch(suggest.url({ query: { q: key } }), {
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        })
            .then((response) => {
                if (!response.ok) {
                    throw new Error('request failed');
                }

                return response.json() as Promise<SearchSuggestData>;
            })
            .then((data) => {
                cache.current.set(key, data);

                if (active) {
                    setResults(data);
                }
            })
            .catch(() => {
                if (active) {
                    setFailed(true);
                }
            })
            .finally(() => {
                if (active) {
                    setLoading(false);
                }
            });

        return () => {
            active = false;
        };
    }, [debounced, open]);

    useEffect(() => {
        if (!open) {
            return;
        }

        const close = (event: PointerEvent) => {
            if (!container.current?.contains(event.target as Node)) {
                setOpen(false);
            }
        };

        document.addEventListener('pointerdown', close);

        return () => document.removeEventListener('pointerdown', close);
    }, [open]);

    const submit = (value: string) => {
        const trimmed = value.trim();

        if (trimmed === '') {
            return;
        }

        saveRecent(trimmed);
        setRecent(readRecent());
        setOpen(false);
        router.get(explore.url({ query: { q: trimmed } }));
    };

    const showPanel = open;
    const hasRecent = query.trim() === '' && recent.length > 0;

    return (
        <div ref={container} className={cn('relative', className)}>
            <form
                role="search"
                onSubmit={(event) => {
                    event.preventDefault();
                    submit(query);
                }}
            >
                <label className="sr-only" htmlFor={inputId}>
                    Cari produk atau lapak
                </label>
                <div className="relative">
                    <Search
                        aria-hidden
                        className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                    />
                    <Input
                        id={inputId}
                        type="search"
                        value={query}
                        autoFocus={autoFocus}
                        autoComplete="off"
                        placeholder={placeholder}
                        aria-expanded={showPanel}
                        aria-controls={`${inputId}-panel`}
                        onFocus={() => setOpen(true)}
                        onKeyDown={(event) => {
                            if (event.key === 'Escape') {
                                setOpen(false);
                            }
                        }}
                        onChange={(event) => {
                            setQuery(event.target.value);
                            setOpen(true);
                        }}
                        className="h-11 bg-card pr-10 pl-9 text-base md:h-10 md:text-sm"
                    />
                    {query !== '' && (
                        <button
                            type="button"
                            onClick={() => setQuery('')}
                            className="absolute top-1/2 right-1 flex size-9 -translate-y-1/2 items-center justify-center text-muted-foreground hover:text-foreground"
                        >
                            <X aria-hidden className="size-4" />
                            <span className="sr-only">
                                Hapus kata pencarian
                            </span>
                        </button>
                    )}
                </div>
            </form>

            {showPanel && (
                <div
                    id={`${inputId}-panel`}
                    className="absolute inset-x-0 top-full z-30 mt-1 max-h-[70vh] overflow-y-auto rounded-md border border-border bg-popover text-popover-foreground shadow-[0_8px_24px_rgba(16,24,18,.18)]"
                >
                    {loading && (
                        <p className="flex items-center gap-2 px-4 py-3 text-sm text-muted-foreground">
                            <Loader2
                                aria-hidden
                                className="size-4 animate-spin"
                            />
                            Mencari...
                        </p>
                    )}

                    {!loading && failed && (
                        <p className="px-4 py-3 text-sm text-destructive">
                            Pencarian gagal. Periksa koneksi lalu coba lagi.
                        </p>
                    )}

                    {hasRecent && (
                        <section className="border-b border-border px-2 py-2">
                            <div className="flex items-center justify-between px-2 py-1">
                                <h2 className="text-xs font-semibold text-muted-foreground">
                                    Pencarian terakhir
                                </h2>
                                <button
                                    type="button"
                                    onClick={() => {
                                        window.localStorage.removeItem(
                                            RECENT_KEY,
                                        );
                                        setRecent([]);
                                    }}
                                    className="rounded-md px-2 py-1 text-xs text-muted-foreground hover:text-foreground"
                                >
                                    Hapus riwayat
                                </button>
                            </div>
                            <div className="flex flex-wrap gap-1 px-1 pt-1 pb-2">
                                {recent.map((entry) => (
                                    <button
                                        key={entry}
                                        type="button"
                                        onClick={() => {
                                            setQuery(entry);
                                            submit(entry);
                                        }}
                                        className="rounded-md border border-border px-2 py-1 text-xs hover:bg-accent"
                                    >
                                        {entry}
                                    </button>
                                ))}
                            </div>
                        </section>
                    )}

                    {!loading && !failed && results !== null && (
                        <>
                            {results.products.length > 0 && (
                                <section className="border-b border-border py-2">
                                    <h2 className="px-4 py-1 text-xs font-semibold text-muted-foreground">
                                        Produk
                                    </h2>
                                    <ul>
                                        {results.products.map((product) => (
                                            <li key={product.id}>
                                                <Link
                                                    href={productShow.url(
                                                        product.id,
                                                    )}
                                                    onClick={() => {
                                                        saveRecent(
                                                            product.name,
                                                        );
                                                        setOpen(false);
                                                    }}
                                                    className="flex min-h-11 items-center gap-3 px-4 py-2 hover:bg-accent"
                                                >
                                                    {product.image_url !==
                                                    null ? (
                                                        <img
                                                            src={
                                                                product.image_url
                                                            }
                                                            alt=""
                                                            width={40}
                                                            height={40}
                                                            loading="lazy"
                                                            className="size-10 object-cover"
                                                        />
                                                    ) : (
                                                        <span className="size-10 bg-secondary" />
                                                    )}
                                                    <span className="min-w-0 flex-1">
                                                        <span className="block truncate text-sm">
                                                            {product.name}
                                                        </span>
                                                        <span className="block text-xs text-price">
                                                            {formatRupiah(
                                                                product.price,
                                                            )}
                                                        </span>
                                                    </span>
                                                </Link>
                                            </li>
                                        ))}
                                    </ul>
                                </section>
                            )}

                            {results.stores.length > 0 && (
                                <section className="border-b border-border py-2">
                                    <h2 className="px-4 py-1 text-xs font-semibold text-muted-foreground">
                                        Lapak
                                    </h2>
                                    <ul>
                                        {results.stores.map((store) => (
                                            <li key={store.id}>
                                                <Link
                                                    href={storeShow.url(
                                                        store.slug,
                                                    )}
                                                    onClick={() =>
                                                        setOpen(false)
                                                    }
                                                    className="flex min-h-11 items-center gap-3 px-4 py-2 hover:bg-accent"
                                                >
                                                    {store.logo_url !== null ? (
                                                        <img
                                                            src={store.logo_url}
                                                            alt=""
                                                            width={40}
                                                            height={40}
                                                            loading="lazy"
                                                            className="size-10 object-cover"
                                                        />
                                                    ) : (
                                                        <span className="size-10 bg-secondary" />
                                                    )}
                                                    <span className="truncate text-sm">
                                                        {store.name}
                                                    </span>
                                                </Link>
                                            </li>
                                        ))}
                                    </ul>
                                </section>
                            )}

                            {query.trim() === '' &&
                                results.best_sellers.length > 0 && (
                                    <section className="py-2">
                                        <h2 className="px-4 py-1 text-xs font-semibold text-muted-foreground">
                                            Paling laris
                                        </h2>
                                        <ul>
                                            {results.best_sellers.map(
                                                (product) => (
                                                    <li key={product.id}>
                                                        <Link
                                                            href={productShow.url(
                                                                product.id,
                                                            )}
                                                            onClick={() =>
                                                                setOpen(false)
                                                            }
                                                            className="flex min-h-11 items-center gap-3 px-4 py-2 hover:bg-accent"
                                                        >
                                                            <span className="min-w-0 flex-1 truncate text-sm">
                                                                {product.name}
                                                            </span>
                                                            <span className="text-xs text-price">
                                                                {formatRupiah(
                                                                    product.price,
                                                                )}
                                                            </span>
                                                        </Link>
                                                    </li>
                                                ),
                                            )}
                                        </ul>
                                    </section>
                                )}

                            {query.trim() !== '' &&
                                results.products.length === 0 &&
                                results.stores.length === 0 && (
                                    <p className="px-4 py-3 text-sm text-muted-foreground">
                                        Tidak ada hasil untuk &quot;
                                        {query.trim()}&quot;. Coba kata lain.
                                    </p>
                                )}
                        </>
                    )}

                    {query.trim() !== '' && (
                        <button
                            type="button"
                            onClick={() => submit(query)}
                            className="block w-full border-t border-border px-4 py-3 text-left text-sm font-medium text-primary hover:bg-accent"
                        >
                            Lihat semua hasil untuk &quot;{query.trim()}
                            &quot;
                        </button>
                    )}
                </div>
            )}
        </div>
    );
}
