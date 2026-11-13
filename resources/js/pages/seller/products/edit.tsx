import { Head } from '@inertiajs/react';
import {
    ProductForm,
    type ProductDetail,
} from '@/components/seller/product-form';
import {
    edit as editProduct,
    index as products,
} from '@/routes/seller/products';

type Props = {
    product: ProductDetail;
    categories: { id: number; name: string }[];
};

export default function SellerProductEdit({ product, categories }: Props) {
    return (
        <>
            <Head title={`Ubah ${product.name}`} />

            <div className="flex flex-1 flex-col gap-6 px-4 py-6 md:px-6">
                <div>
                    <h1 className="font-display text-2xl font-bold">
                        Ubah produk
                    </h1>
                    <p className="mt-1 text-sm text-muted-foreground">
                        {product.name}
                    </p>
                </div>

                <ProductForm categories={categories} product={product} />
            </div>
        </>
    );
}

SellerProductEdit.layout = (props: Props) => ({
    breadcrumbs: [
        {
            title: 'Produk',
            href: products(),
        },
        {
            title: 'Ubah produk',
            href: editProduct(props.product.id),
        },
    ],
});
