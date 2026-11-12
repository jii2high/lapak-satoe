import { router, usePage } from '@inertiajs/react';
import { Heart } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import { toggle as toggleProduct } from '@/routes/wishlist/products';
import { toggle as toggleStore } from '@/routes/wishlist/stores';

type Props = {
    productId?: number;
    storeId?: number;
    withLabel?: boolean;
    className?: string;
};

export function WishlistButton({
    productId,
    storeId,
    withLabel = false,
    className,
}: Props) {
    const { auth } = usePage().props;
    const id = productId ?? storeId;

    if (id === undefined) {
        return null;
    }

    const active =
        productId !== undefined
            ? (auth.user?.wishlist_product_ids.includes(id) ?? false)
            : (auth.user?.wishlist_store_ids.includes(id) ?? false);

    const toggle = () => {
        const url =
            productId !== undefined
                ? toggleProduct.url(productId)
                : toggleStore.url(storeId as number);

        router.post(url, {}, { preserveScroll: true });
    };

    if (withLabel) {
        return (
            <Button
                type="button"
                variant="outline"
                onClick={toggle}
                aria-pressed={active}
            >
                <Heart
                    aria-hidden
                    className={active ? 'fill-price text-price' : undefined}
                />
                {active ? 'Tersimpan di wishlist' : 'Simpan ke wishlist'}
            </Button>
        );
    }

    return (
        <Button
            type="button"
            variant="secondary"
            size="icon"
            onClick={toggle}
            aria-pressed={active}
            aria-label={active ? 'Hapus dari wishlist' : 'Simpan ke wishlist'}
            className={cn('border border-border', className)}
        >
            <Heart
                aria-hidden
                className={active ? 'fill-price text-price' : undefined}
            />
        </Button>
    );
}
