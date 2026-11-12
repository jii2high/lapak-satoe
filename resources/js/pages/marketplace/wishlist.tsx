import { Head, Link } from '@inertiajs/react';
import { Heart } from 'lucide-react';
import { ProductCard } from '@/components/marketplace/product-card';
import { StoreCard } from '@/components/marketplace/store-card';
import { Button } from '@/components/ui/button';
import { explore } from '@/routes';
import type { ProductCardData, StoreCardData } from '@/types';

type Props = {
    products: ProductCardData[];
    stores: StoreCardData[];
};

export default function WishlistIndex({ products, stores }: Props) {
    const isEmpty = products.length === 0 && stores.length === 0;

    return (
        <>
            <Head title="Wishlist" />

            <div>
                <h1 className="font-display text-2xl font-bold">Wishlist</h1>
                <p className="mt-1 text-sm text-muted-foreground">
                    Produk dan lapak yang kamu simpan untuk nanti.
                </p>
            </div>

            {isEmpty ? (
                <div className="mt-6 border border-border bg-card px-4 py-10 text-center">
                    <Heart
                        aria-hidden
                        className="mx-auto size-8 text-muted-foreground"
                    />
                    <h2 className="mt-3 font-display text-lg font-semibold">
                        Wishlist masih kosong
                    </h2>
                    <p className="mx-auto mt-2 max-w-prose text-sm text-muted-foreground">
                        Tekan ikon hati di produk atau lapak untuk menyimpannya
                        di sini.
                    </p>
                    <Button asChild variant="secondary" className="mt-4">
                        <Link href={explore()}>Jelajahi produk</Link>
                    </Button>
                </div>
            ) : (
                <>
                    {products.length > 0 && (
                        <section className="mt-8">
                            <h2 className="font-display text-xl font-semibold">
                                Produk
                            </h2>
                            <div className="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-4 sm:gap-4 lg:grid-cols-6">
                                {products.map((product) => (
                                    <ProductCard
                                        key={product.id}
                                        product={product}
                                    />
                                ))}
                            </div>
                        </section>
                    )}

                    {stores.length > 0 && (
                        <section className="mt-10">
                            <h2 className="font-display text-xl font-semibold">
                                Lapak
                            </h2>
                            <div className="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                                {stores.map((store) => (
                                    <StoreCard key={store.id} store={store} />
                                ))}
                            </div>
                        </section>
                    )}
                </>
            )}
        </>
    );
}
