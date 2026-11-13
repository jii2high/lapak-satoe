import { Link } from '@inertiajs/react';
import { Store } from 'lucide-react';
import { WishlistButton } from '@/components/marketplace/wishlist-button';
import { show as storeShow } from '@/routes/stores';
import type { StoreCardData } from '@/types';

export function StoreCard({ store }: { store: StoreCardData }) {
    return (
        <article className="relative rounded-md border border-border bg-card transition-colors focus-within:border-primary hover:border-primary">
            <div className="awning awning-sm" />

            <WishlistButton
                storeId={store.id}
                className="absolute top-4 right-2 z-10"
            />

            <Link
                href={storeShow.url(store.slug)}
                prefetch
                className="flex flex-col gap-3 p-4 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring"
            >
                <div className="flex items-center gap-3">
                    {store.logo_url === null ? (
                        <span className="flex size-14 shrink-0 items-center justify-center bg-secondary text-muted-foreground">
                            <Store aria-hidden className="size-6" />
                        </span>
                    ) : (
                        <img
                            src={store.logo_url}
                            alt={`Logo ${store.name}`}
                            width={56}
                            height={56}
                            loading="lazy"
                            decoding="async"
                            className="size-14 shrink-0 object-cover"
                        />
                    )}

                    <div className="min-w-0">
                        <h3 className="truncate font-display text-lg font-semibold">
                            {store.name}
                        </h3>
                        <p className="mt-0.5 text-xs text-muted-foreground">
                            {store.product_count} produk
                        </p>
                    </div>
                </div>

                <p className="line-clamp-1 text-sm text-muted-foreground">
                    {store.description ?? 'Belum ada deskripsi lapak.'}
                </p>
            </Link>
        </article>
    );
}
