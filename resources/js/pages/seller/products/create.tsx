import { Head } from '@inertiajs/react';
import { ProductForm } from '@/components/seller/product-form';
import {
    create as createProduct,
    index as products,
} from '@/routes/seller/products';

type Props = {
    categories: { id: number; name: string }[];
};

export default function SellerProductCreate({ categories }: Props) {
    return (
        <>
            <Head title="Tambah produk" />

            <div className="flex flex-1 flex-col gap-6 px-4 py-6 md:px-6">
                <div>
                    <h1 className="font-display text-2xl font-bold">
                        Tambah produk
                    </h1>
                    <p className="mt-1 text-sm text-muted-foreground">
                        Produk langsung tampil di katalog setelah disimpan dan
                        diaktifkan.
                    </p>
                </div>

                <ProductForm categories={categories} />
            </div>
        </>
    );
}

SellerProductCreate.layout = {
    breadcrumbs: [
        {
            title: 'Produk',
            href: products(),
        },
        {
            title: 'Tambah produk',
            href: createProduct(),
        },
    ],
};
