import { Head, Link } from '@inertiajs/react';
import { Minus, Package, Plus, ShoppingCart } from 'lucide-react';
import { useState } from 'react';
import { toast } from 'sonner';
import { ProductCard } from '@/components/marketplace/product-card';
import { WishlistButton } from '@/components/marketplace/wishlist-button';
import { Button } from '@/components/ui/button';
import { addToCart } from '@/lib/cart-storage';
import { formatRupiah } from '@/lib/format';
import { cn } from '@/lib/utils';
import { explore } from '@/routes';
import { show as storeShow } from '@/routes/stores';
import type { ProductCardData, ProductDetailData } from '@/types';

type Props = {
    product: ProductDetailData;
    related: ProductCardData[];
};

export default function ProductShow({ product, related }: Props) {
    const [qty, setQty] = useState(1);
    const [activeImage, setActiveImage] = useState(product.image_url);

    const soldOut = product.stock_available <= 0;

    const add = () => {
        addToCart(
            {
                productId: product.id,
                name: product.name,
                price: product.price,
                storeName: product.store.name,
                storeSlug: product.store.slug,
                imageUrl: product.image_url,
                stock: product.stock_available,
            },
            qty,
        );

        toast.success('Ditambahkan ke keranjang.');
    };

    return (
        <>
            <Head title={product.name} />

            <div className="grid gap-6 lg:grid-cols-2 lg:gap-10">
                <div>
                    <div className="relative aspect-square overflow-hidden border border-border bg-secondary">
                        {activeImage === null ? (
                            <span className="flex size-full items-center justify-center text-muted-foreground">
                                <Package aria-hidden className="size-10" />
                                <span className="sr-only">
                                    Produk ini belum punya foto
                                </span>
                            </span>
                        ) : (
                            <img
                                src={activeImage}
                                alt={product.name}
                                width={800}
                                height={800}
                                fetchPriority="high"
                                decoding="async"
                                className="size-full object-cover"
                            />
                        )}

                        {soldOut && (
                            <span className="absolute inset-x-0 bottom-0 bg-background/90 py-2 text-center text-sm font-semibold">
                                Habis
                            </span>
                        )}
                    </div>

                    {product.images.length > 1 && (
                        <div className="mt-2 flex gap-2 overflow-x-auto pb-1">
                            {product.images.map((image, index) => (
                                <button
                                    key={image}
                                    type="button"
                                    onClick={() => setActiveImage(image)}
                                    aria-pressed={activeImage === image}
                                    aria-label={`Lihat foto ${index + 1}`}
                                    className={cn(
                                        'size-18 shrink-0 border bg-secondary',
                                        activeImage === image
                                            ? 'border-primary'
                                            : 'border-border',
                                    )}
                                >
                                    <img
                                        src={image}
                                        alt=""
                                        width={72}
                                        height={72}
                                        loading="lazy"
                                        decoding="async"
                                        className="size-full object-cover"
                                    />
                                </button>
                            ))}
                        </div>
                    )}
                </div>

                <div>
                    <Link
                        href={explore.url({
                            query: { category: product.category.slug },
                        })}
                        className="text-xs font-medium text-primary hover:underline"
                    >
                        {product.category.name}
                    </Link>

                    <h1 className="mt-1 font-display text-2xl font-bold md:text-2xl">
                        {product.name}
                    </h1>

                    <p className="mt-3 text-xl font-semibold text-price">
                        {formatRupiah(product.price)}
                    </p>

                    <p className="mt-1 text-sm text-muted-foreground">
                        {soldOut
                            ? 'Stok habis'
                            : `Stok tersedia ${product.stock_available}`}
                    </p>

                    {product.description !== null &&
                        product.description !== '' && (
                            <div className="mt-6">
                                <h2 className="text-sm font-semibold">
                                    Deskripsi
                                </h2>
                                <p className="mt-2 max-w-prose text-base whitespace-pre-line text-muted-foreground">
                                    {product.description}
                                </p>
                            </div>
                        )}

                    <div className="mt-6 border border-border bg-card p-4">
                        <p className="text-xs text-muted-foreground">Lapak</p>
                        <Link
                            href={storeShow.url(product.store.slug)}
                            className="font-display text-lg font-semibold hover:text-primary"
                        >
                            {product.store.name}
                        </Link>
                    </div>

                    <div className="mt-4">
                        <WishlistButton
                            productId={product.id}
                            withLabel
                            className="w-full sm:w-auto"
                        />
                    </div>
                </div>
            </div>

            <div className="fixed inset-x-0 bottom-[calc(3.5rem+env(safe-area-inset-bottom))] z-30 border-t border-border bg-background px-4 py-3 md:static md:mt-6 md:border-0 md:bg-transparent md:p-0">
                <div className="mx-auto flex max-w-[1280px] items-center gap-2">
                    <div className="flex items-center gap-1">
                        <Button
                            type="button"
                            variant="outline"
                            size="icon"
                            aria-label="Kurangi jumlah"
                            disabled={soldOut || qty <= 1}
                            onClick={() => setQty((value) => value - 1)}
                        >
                            <Minus aria-hidden />
                        </Button>
                        <span className="min-w-8 text-center text-sm">
                            {qty}
                        </span>
                        <Button
                            type="button"
                            variant="outline"
                            size="icon"
                            aria-label="Tambah jumlah"
                            disabled={soldOut || qty >= product.stock_available}
                            onClick={() => setQty((value) => value + 1)}
                        >
                            <Plus aria-hidden />
                        </Button>
                    </div>

                    <Button
                        type="button"
                        className="flex-1"
                        size="lg"
                        disabled={soldOut}
                        onClick={add}
                    >
                        <ShoppingCart aria-hidden />
                        {soldOut ? 'Stok habis' : 'Tambah ke keranjang'}
                    </Button>
                </div>
            </div>

            {related.length > 0 && (
                <section className="mt-10 md:mt-16">
                    <h2 className="font-display text-xl font-semibold">
                        Produk lain dari lapak ini
                    </h2>
                    <div className="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-4 sm:gap-4 lg:grid-cols-6">
                        {related.map((item) => (
                            <ProductCard key={item.id} product={item} />
                        ))}
                    </div>
                </section>
            )}
        </>
    );
}
