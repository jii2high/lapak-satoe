import { Head, Link, router, useForm } from '@inertiajs/react';
import { SearchX, SlidersHorizontal } from 'lucide-react';
import { useState } from 'react';
import { ProductCard } from '@/components/marketplace/product-card';
import { SearchInput } from '@/components/marketplace/search-input';
import { StoreCard } from '@/components/marketplace/store-card';
import { Pagination } from '@/components/pagination';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
    SheetTrigger,
} from '@/components/ui/sheet';
import { Skeleton } from '@/components/ui/skeleton';
import { cn } from '@/lib/utils';
import { explore } from '@/routes';
import type {
    ExploreFilters,
    ExploreResultType,
    Paginator,
    ProductCardData,
    StoreCardData,
} from '@/types';

type CategoryOption = {
    id: number;
    name: string;
    slug: string;
    products_count: number;
};

type Props = {
    products: Paginator<ProductCardData> | null;
    stores: StoreCardData[];
    categories: CategoryOption[];
    filters: ExploreFilters;
    perPage: number;
};

type FilterData = {
    q: string;
    type: ExploreResultType;
    category: string[];
    min: string;
    max: string;
    per_page: string;
};

const resultTypes: { value: ExploreResultType; label: string }[] = [
    { value: 'keduanya', label: 'Produk dan lapak' },
    { value: 'produk', label: 'Produk saja' },
    { value: 'lapak', label: 'Lapak saja' },
];

export default function Explore({
    products,
    stores,
    categories,
    filters,
    perPage,
}: Props) {
    const [filterOpen, setFilterOpen] = useState(false);

    const form = useForm<FilterData>({
        q: filters.q,
        type: filters.type,
        category: filters.category,
        min: filters.min === null ? '' : String(filters.min),
        max: filters.max === null ? '' : String(filters.max),
        per_page: String(perPage),
    });

    const apply = () => {
        form.get(explore.url(), {
            preserveState: true,
            preserveScroll: true,
            onSuccess: () => setFilterOpen(false),
        });
    };

    const reset = () => {
        form.setData({
            q: filters.q,
            type: 'keduanya',
            category: [],
            min: '',
            max: '',
            per_page: '20',
        });
        router.get(
            explore.url({
                query: filters.q === '' ? {} : { q: filters.q },
            }),
            { preserveScroll: true },
        );
    };

    const toggleCategory = (slug: string, checked: boolean) => {
        form.setData(
            'category',
            checked
                ? [...form.data.category, slug]
                : form.data.category.filter((value) => value !== slug),
        );
    };

    const perPageUrl = (value: number): string => {
        const query: Record<string, string | number> = {
            per_page: value,
        };

        if (filters.q !== '') query.q = filters.q;
        if (filters.type !== 'keduanya') query.type = filters.type;
        if (filters.category.length > 0)
            query.category = filters.category.join(',');
        if (filters.min !== null) query.min = filters.min;
        if (filters.max !== null) query.max = filters.max;

        return explore.url({ query });
    };

    const filterFields = (idPrefix: string) => (
        <div className="grid gap-5">
            <fieldset className="grid gap-2">
                <legend className="text-sm font-semibold">
                    Tampilkan hasil berupa
                </legend>
                {resultTypes.map((option) => (
                    <div
                        key={option.value}
                        className="flex min-h-11 items-center gap-2"
                    >
                        <input
                            id={`${idPrefix}-type-${option.value}`}
                            type="radio"
                            name={`${idPrefix}-type`}
                            value={option.value}
                            checked={form.data.type === option.value}
                            onChange={() => form.setData('type', option.value)}
                            className="size-4 shrink-0 accent-primary"
                        />
                        <Label htmlFor={`${idPrefix}-type-${option.value}`}>
                            {option.label}
                        </Label>
                    </div>
                ))}
            </fieldset>

            <fieldset className="grid gap-1">
                <legend className="text-sm font-semibold">Kategori</legend>
                {categories.length === 0 && (
                    <p className="text-sm text-muted-foreground">
                        Belum ada kategori.
                    </p>
                )}
                {categories.map((category) => (
                    <div
                        key={category.id}
                        className="flex min-h-11 items-center gap-2"
                    >
                        <Checkbox
                            id={`${idPrefix}-category-${category.slug}`}
                            checked={form.data.category.includes(category.slug)}
                            onCheckedChange={(checked) =>
                                toggleCategory(category.slug, checked === true)
                            }
                        />
                        <Label
                            htmlFor={`${idPrefix}-category-${category.slug}`}
                            className="flex-1"
                        >
                            {category.name}
                        </Label>
                        <span className="text-xs text-muted-foreground">
                            {category.products_count}
                        </span>
                    </div>
                ))}
            </fieldset>

            <fieldset className="grid gap-2">
                <legend className="text-sm font-semibold">Rentang harga</legend>
                <div className="flex items-center gap-2">
                    <Input
                        type="number"
                        inputMode="numeric"
                        min={0}
                        placeholder="Terendah"
                        aria-label="Harga terendah"
                        value={form.data.min}
                        onChange={(event) =>
                            form.setData('min', event.target.value)
                        }
                    />
                    <span className="text-sm text-muted-foreground">
                        sampai
                    </span>
                    <Input
                        type="number"
                        inputMode="numeric"
                        min={0}
                        placeholder="Tertinggi"
                        aria-label="Harga tertinggi"
                        value={form.data.max}
                        onChange={(event) =>
                            form.setData('max', event.target.value)
                        }
                    />
                </div>
            </fieldset>

            <div className="flex flex-wrap gap-2 pt-1">
                <Button type="submit" disabled={form.processing}>
                    Terapkan filter
                </Button>
                <Button type="button" variant="outline" onClick={reset}>
                    Hapus filter
                </Button>
            </div>
        </div>
    );

    const productList = products?.data ?? null;
    const hasProducts = productList !== null && productList.length > 0;
    const hasStores = stores.length > 0;
    const bothEmpty = !hasProducts && !hasStores;

    return (
        <>
            <Head title="Jelajah" />

            <h1 className="font-display text-2xl font-bold">Jelajah</h1>

            <div className="mt-4">
                <SearchInput defaultValue={filters.q} />
            </div>

            <div className="mt-4 flex items-center justify-between gap-4">
                <p className="text-sm text-muted-foreground">
                    {filters.q !== ''
                        ? `Hasil pencarian "${filters.q}".`
                        : 'Semua produk dan lapak yang buka.'}
                </p>

                <div className="flex items-center gap-2 lg:hidden">
                    <Sheet open={filterOpen} onOpenChange={setFilterOpen}>
                        <SheetTrigger asChild>
                            <Button variant="outline">
                                <SlidersHorizontal aria-hidden />
                                Filter
                            </Button>
                        </SheetTrigger>
                        <SheetContent>
                            <SheetHeader>
                                <SheetTitle className="font-display text-xl">
                                    Filter
                                </SheetTitle>
                                <SheetDescription>
                                    Pilih jenis hasil, kategori, atau rentang
                                    harga.
                                </SheetDescription>
                            </SheetHeader>
                            <div className="overflow-y-auto px-4 pb-6">
                                <form
                                    onSubmit={(event) => {
                                        event.preventDefault();
                                        apply();
                                    }}
                                >
                                    {filterFields('sheet')}
                                </form>
                            </div>
                        </SheetContent>
                    </Sheet>
                </div>
            </div>

            <div className="mt-6 lg:grid lg:grid-cols-[260px_1fr] lg:gap-8">
                <aside className="hidden lg:block">
                    <h2 className="text-sm font-semibold">Filter</h2>
                    <form
                        className="mt-3"
                        onSubmit={(event) => {
                            event.preventDefault();
                            apply();
                        }}
                    >
                        {filterFields('aside')}
                    </form>
                </aside>

                <section>
                    {filters.type !== 'lapak' && (
                        <div className="hidden items-center justify-end gap-2 md:flex">
                            <span className="text-sm text-muted-foreground">
                                Per halaman
                            </span>
                            {[20, 60].map((value) => (
                                <Link
                                    key={value}
                                    href={perPageUrl(value)}
                                    preserveScroll
                                    aria-current={
                                        perPage === value ? 'true' : undefined
                                    }
                                    className={cn(
                                        'flex min-h-11 min-w-11 items-center justify-center rounded-md border text-sm md:min-h-9 md:min-w-9',
                                        perPage === value
                                            ? 'border-primary bg-primary font-semibold text-primary-foreground'
                                            : 'border-border bg-card hover:bg-accent',
                                    )}
                                >
                                    {value}
                                </Link>
                            ))}
                        </div>
                    )}

                    {form.processing ? (
                        <div
                            aria-busy="true"
                            className="mt-6 grid grid-cols-2 gap-3 sm:grid-cols-4 sm:gap-4 md:mt-0 lg:grid-cols-5"
                        >
                            <span className="sr-only" role="status">
                                Memuat hasil
                            </span>
                            {Array.from({ length: 10 }, (_, index) => (
                                <div
                                    key={index}
                                    className="overflow-hidden rounded-md border border-border bg-card"
                                >
                                    <Skeleton className="aspect-square rounded-none" />
                                    <div className="grid gap-2 p-3">
                                        <Skeleton className="h-4 w-4/5" />
                                        <Skeleton className="h-5 w-1/2" />
                                        <Skeleton className="h-3 w-2/3" />
                                    </div>
                                </div>
                            ))}
                        </div>
                    ) : bothEmpty ? (
                        <div className="mt-6 border border-border bg-card px-4 py-10 text-center">
                            <SearchX
                                aria-hidden
                                className="mx-auto size-8 text-muted-foreground"
                            />
                            <h2 className="mt-3 font-display text-lg font-semibold">
                                Tidak ada hasil
                            </h2>
                            <p className="mx-auto mt-2 max-w-prose text-sm text-muted-foreground">
                                {filters.q !== ''
                                    ? `Tidak ada hasil untuk "${filters.q}". Coba kata lain atau hapus filter.`
                                    : 'Coba hapus sebagian filter atau lihat semua produk.'}
                            </p>
                            <Button
                                variant="secondary"
                                className="mt-4"
                                onClick={reset}
                            >
                                Lihat semua
                            </Button>
                        </div>
                    ) : (
                        <>
                            {products !== null && (
                                <div className="mt-6 md:mt-0">
                                    <div className="flex items-baseline justify-between gap-4">
                                        <h2 className="font-display text-lg font-semibold">
                                            Produk
                                        </h2>
                                        <span className="text-sm text-muted-foreground">
                                            {products.total} produk
                                        </span>
                                    </div>

                                    {hasProducts ? (
                                        <>
                                            <div className="mt-3 grid grid-cols-2 gap-3 sm:grid-cols-4 sm:gap-4 lg:grid-cols-5">
                                                {productList.map((product) => (
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
                                    ) : (
                                        <p className="mt-3 border border-dashed border-border px-4 py-6 text-sm text-muted-foreground">
                                            Tidak ada produk yang cocok.
                                        </p>
                                    )}
                                </div>
                            )}

                            {hasStores && (
                                <div className="mt-8">
                                    <div className="flex items-baseline justify-between gap-4">
                                        <h2 className="font-display text-lg font-semibold">
                                            Lapak
                                        </h2>
                                        <span className="text-sm text-muted-foreground">
                                            {stores.length} lapak
                                        </span>
                                    </div>

                                    <div className="mt-3 grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                                        {stores.map((store) => (
                                            <StoreCard
                                                key={store.id}
                                                store={store}
                                            />
                                        ))}
                                    </div>
                                </div>
                            )}
                        </>
                    )}
                </section>
            </div>
        </>
    );
}
