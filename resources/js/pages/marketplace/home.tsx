import { Head, Link } from '@inertiajs/react';
import { Compass } from 'lucide-react';
import { ProductCard } from '@/components/marketplace/product-card';
import { StoreCard } from '@/components/marketplace/store-card';
import { cn } from '@/lib/utils';
import { explore, home } from '@/routes';
import type { ProductCardData, StoreCardData } from '@/types';

type CategorySummary = {
    id: number;
    name: string;
    slug: string;
    products_count: number;
};

type SelectedCategory = {
    name: string;
    slug: string;
};

type Props = {
    newProducts: ProductCardData[];
    bestSellers: ProductCardData[];
    categories: CategorySummary[];
    stores: StoreCardData[];
    selectedCategory: SelectedCategory | null;
    categoryProducts: ProductCardData[];
};

const chipBase =
    'flex min-h-11 shrink-0 items-center border-b-2 px-3 text-sm focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring';

const seeAllLink =
    'text-sm font-medium text-primary hover:underline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring';

function CategoryTabs({
    categories,
    selected,
}: {
    categories: CategorySummary[];
    selected: SelectedCategory | null;
}) {
    return (
        <nav aria-label="Kategori produk">
            <ul className="-mx-4 flex gap-1 overflow-x-auto px-4 md:mx-0 md:flex-wrap md:px-0">
                <li>
                    <Link
                        href={home()}
                        aria-current={selected === null ? 'page' : undefined}
                        className={cn(
                            chipBase,
                            selected === null
                                ? 'border-primary font-semibold text-primary'
                                : 'border-transparent text-muted-foreground hover:border-border hover:text-foreground',
                        )}
                    >
                        Semua
                    </Link>
                </li>

                {categories.map((category) => (
                    <li key={category.id}>
                        <Link
                            href={home.url({
                                query: { category: category.slug },
                            })}
                            aria-current={
                                selected?.slug === category.slug
                                    ? 'page'
                                    : undefined
                            }
                            className={cn(
                                chipBase,
                                selected?.slug === category.slug
                                    ? 'border-primary font-semibold text-primary'
                                    : 'border-transparent text-muted-foreground hover:border-border hover:text-foreground',
                            )}
                        >
                            {category.name}
                        </Link>
                    </li>
                ))}
            </ul>
        </nav>
    );
}

const productGrid =
    'mt-4 grid grid-cols-2 gap-3 sm:grid-cols-4 sm:gap-4 lg:grid-cols-6';

function ProductRack({
    title,
    products,
}: {
    title: string;
    products: ProductCardData[];
}) {
    if (products.length === 0) {
        return null;
    }

    return (
        <section className="mt-10 md:mt-16">
            <div className="flex items-center justify-between gap-4">
                <h2 className="font-display text-xl font-semibold">{title}</h2>
                <Link href={explore()} className={seeAllLink}>
                    Lihat semua
                </Link>
            </div>

            <div className="-mx-4 mt-4 flex gap-3 overflow-x-auto px-4 pb-2 sm:mx-0 sm:grid sm:grid-cols-4 sm:gap-4 sm:overflow-visible sm:px-0 lg:grid-cols-6">
                {products.map((product) => (
                    <div
                        key={product.id}
                        className="w-[46%] shrink-0 sm:w-auto"
                    >
                        <ProductCard product={product} />
                    </div>
                ))}
            </div>
        </section>
    );
}

function CategoryResults({
    category,
    products,
}: {
    category: SelectedCategory;
    products: ProductCardData[];
}) {
    return (
        <section className="mt-8 md:mt-10">
            <div className="flex items-center justify-between gap-4">
                <h2 className="font-display text-xl font-semibold">
                    {category.name}
                </h2>
                <Link
                    href={explore.url({ query: { category: category.slug } })}
                    className={seeAllLink}
                >
                    Lihat semua di Jelajah
                </Link>
            </div>

            {products.length === 0 ? (
                <div className="mt-4 border border-border bg-card px-4 py-10 text-center">
                    <Compass
                        aria-hidden
                        className="mx-auto size-8 text-muted-foreground"
                    />
                    <h3 className="mt-3 font-display text-lg font-semibold">
                        Belum ada produk di kategori ini
                    </h3>
                    <p className="mx-auto mt-2 max-w-prose text-sm text-muted-foreground">
                        Produk dari lapak siswa akan muncul di sini.
                    </p>
                    <Link
                        href={home()}
                        className="mt-4 inline-flex min-h-11 items-center rounded-md border border-border bg-card px-4 text-sm font-medium hover:bg-accent focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring"
                    >
                        Lihat semua kategori
                    </Link>
                </div>
            ) : (
                <div className={productGrid}>
                    {products.map((product) => (
                        <ProductCard key={product.id} product={product} />
                    ))}
                </div>
            )}
        </section>
    );
}

export default function MarketplaceHome({
    newProducts,
    bestSellers,
    categories,
    stores,
    selectedCategory,
    categoryProducts,
}: Props) {
    const hasProducts =
        newProducts.length > 0 || bestSellers.length > 0 || stores.length > 0;

    return (
        <>
            <Head title="Beranda" />

            <h1 className="sr-only">Belanja dari lapak teman SMKN 1 Bekasi</h1>

            <CategoryTabs categories={categories} selected={selectedCategory} />

            {selectedCategory !== null ? (
                <CategoryResults
                    category={selectedCategory}
                    products={categoryProducts}
                />
            ) : (
                <>
                    {!hasProducts && (
                        <section className="mt-8 border border-border bg-card px-4 py-10 text-center md:mt-10">
                            <Compass
                                aria-hidden
                                className="mx-auto size-8 text-muted-foreground"
                            />
                            <h2 className="mt-3 font-display text-xl font-semibold">
                                Katalog belum terisi
                            </h2>
                            <p className="mx-auto mt-2 max-w-prose text-sm text-muted-foreground">
                                Belum ada lapak yang buka. Produk dari lapak
                                siswa akan muncul di sini.
                            </p>
                        </section>
                    )}

                    <ProductRack title="Baru di lapak" products={newProducts} />
                    <ProductRack title="Paling laris" products={bestSellers} />

                    {stores.length > 0 && (
                        <section className="mt-10 md:mt-16">
                            <div className="flex items-center justify-between gap-4">
                                <h2 className="font-display text-xl font-semibold">
                                    Lapak
                                </h2>
                                <Link
                                    href={explore.url({
                                        query: { type: 'lapak' },
                                    })}
                                    className={seeAllLink}
                                >
                                    Cari lapak
                                </Link>
                            </div>

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
