export type ProductCardStore = {
    id: number;
    name: string;
    slug: string;
};

export type ProductCardData = {
    id: number;
    name: string;
    price: number;
    stock_available: number;
    image_url: string | null;
    store: ProductCardStore;
};

export type ProductDetailData = ProductCardData & {
    description: string | null;
    category: {
        name: string;
        slug: string;
    };
    images: string[];
};

export type StoreStatus = 'open' | 'closing' | 'closed';

export type StoreCardData = {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    logo_url: string | null;
    banner_url: string | null;
    status: StoreStatus;
    is_suspended: boolean;
    product_count: number;
};

export type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

export type Paginator<T> = {
    data: T[];
    current_page: number;
    last_page: number;
    from: number | null;
    to: number | null;
    total: number;
    per_page: number;
    links: PaginationLink[];
};

export type SearchSuggestData = {
    products: ProductCardData[];
    stores: StoreCardData[];
    best_sellers: ProductCardData[];
};

export type ExploreResultType = 'lapak' | 'produk' | 'keduanya';

export type ExploreFilters = {
    q: string;
    type: ExploreResultType;
    category: string[];
    min: number | null;
    max: number | null;
};
