import { Head } from '@inertiajs/react';
import { Store as StoreIcon } from 'lucide-react';
import { ProductCard } from '@/components/marketplace/product-card';
import { ReportDialog } from '@/components/marketplace/report-dialog';
import { WishlistButton } from '@/components/marketplace/wishlist-button';
import { Pagination } from '@/components/pagination';
import type { Paginator, ProductCardData, StoreCardData } from '@/types';

type StoreDetail = StoreCardData & {
    previous_name: string | null;
};

type Props = {
    store: StoreDetail;
    products: Paginator<ProductCardData>;
};

export default function StoreShow({ store, products }: Props) {
    return (
        <>
            <Head title={store.name} />

            <section className="border border-border bg-card">
                <div className="awning awning-lg" />

                {store.banner_url !== null && (
                    <img
                        src={store.banner_url}
                        alt={`Banner ${store.name}`}
                        width={1200}
                        height={400}
                        fetchPriority="high"
                        decoding="async"
                        className="aspect-[2/1] w-full object-cover md:aspect-[3/1]"
                    />
                )}

                <div className="flex flex-col gap-4 p-4 sm:flex-row sm:items-start sm:justify-between md:p-6">
                    <div className="flex min-w-0 items-start gap-4">
                        {store.logo_url === null ? (
                            <span className="flex size-14 shrink-0 items-center justify-center bg-secondary text-muted-foreground">
                                <StoreIcon aria-hidden className="size-6" />
                            </span>
                        ) : (
                            <img
                                src={store.logo_url}
                                alt={`Logo ${store.name}`}
                                width={56}
                                height={56}
                                decoding="async"
                                className="size-14 shrink-0 object-cover"
                            />
                        )}

                        <div className="min-w-0">
                            <h1 className="font-display text-2xl font-bold">
                                {store.name}
                            </h1>

                            {store.previous_name !== null && (
                                <p className="mt-0.5 text-xs text-muted-foreground">
                                    Sebelumnya {store.previous_name}
                                </p>
                            )}

                            <p className="mt-1 max-w-prose text-sm text-muted-foreground">
                                {store.description ??
                                    'Lapak ini belum menulis deskripsi.'}
                            </p>

                            <p className="mt-2 text-xs text-muted-foreground">
                                {store.product_count} produk
                            </p>
                        </div>
                    </div>

                    <div className="flex flex-col gap-2 sm:flex-row">
                        <WishlistButton storeId={store.id} withLabel />
                        <ReportDialog
                            target={{ label: store.name, storeId: store.id }}
                        />
                    </div>
                </div>
            </section>

            <section className="mt-8 md:mt-10">
                <h2 className="font-display text-xl font-semibold">
                    Produk lapak
                </h2>

                {products.data.length === 0 ? (
                    <div className="mt-4 border border-border bg-card px-4 py-10 text-center">
                        <StoreIcon
                            aria-hidden
                            className="mx-auto size-8 text-muted-foreground"
                        />
                        <p className="mx-auto mt-3 max-w-prose text-sm text-muted-foreground">
                            Lapak ini belum punya produk yang tampil. Cek lagi
                            nanti.
                        </p>
                    </div>
                ) : (
                    <>
                        <div className="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-4 sm:gap-4 lg:grid-cols-6">
                            {products.data.map((product) => (
                                <ProductCard
                                    key={product.id}
                                    product={product}
                                />
                            ))}
                        </div>

                        <Pagination
                            links={products.links}
                            lastPage={products.last_page}
                        />
                    </>
                )}
            </section>
        </>
    );
}
