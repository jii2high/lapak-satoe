import { Link } from '@inertiajs/react';
import { Package } from 'lucide-react';
import { WishlistButton } from '@/components/marketplace/wishlist-button';
import { formatRupiah } from '@/lib/format';
import { show as productShow } from '@/routes/products';
import { show as storeShow } from '@/routes/stores';
import type { ProductCardData } from '@/types';

export function ProductCard({ product }: { product: ProductCardData }) {
    const soldOut = product.stock_available <= 0;

    return (
        <article className="group relative rounded-md border border-border bg-card transition-colors focus-within:border-primary hover:border-primary">
            <WishlistButton
                productId={product.id}
                className="absolute top-2 right-2 z-10"
            />

            <Link
                href={productShow.url(product.id)}
                className="block focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring"
            >
                <div className="relative aspect-square overflow-hidden bg-secondary">
                    {product.image_url === null ? (
                        <span className="flex size-full items-center justify-center text-muted-foreground">
                            <Package aria-hidden className="size-8" />
                            <span className="sr-only">
                                {product.name} belum punya foto
                            </span>
                        </span>
                    ) : (
                        <img
                            src={product.image_url}
                            alt={product.name}
                            width={480}
                            height={480}
                            loading="lazy"
                            decoding="async"
                            className="size-full object-cover"
                        />
                    )}

                    {soldOut && (
                        <span className="absolute inset-0 flex items-center justify-center bg-background/80 text-sm font-semibold">
                            Habis
                        </span>
                    )}
                </div>

                <div className="px-3 pt-3">
                    <h3 className="line-clamp-2 text-sm font-medium">
                        {product.name}
                    </h3>
                    <p className="mt-1 text-base font-semibold text-price">
                        {formatRupiah(product.price)}
                    </p>
                </div>
            </Link>

            <Link
                href={storeShow.url(product.store.slug)}
                className="block px-3 pt-1 pb-3 text-xs text-muted-foreground hover:text-primary focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring"
            >
                {product.store.name}
            </Link>
        </article>
    );
}
