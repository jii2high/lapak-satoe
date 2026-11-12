import { Link } from '@inertiajs/react';
import { Minus, Plus, ShoppingCart, Trash2 } from 'lucide-react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetFooter,
    SheetHeader,
    SheetTitle,
    SheetTrigger,
} from '@/components/ui/sheet';
import {
    cartCount,
    removeFromCart,
    setCartQty,
    useCart,
    type CartItem,
} from '@/lib/cart-storage';
import { formatRupiah } from '@/lib/format';
import { explore } from '@/routes';
import { show as storeShow } from '@/routes/stores';

export function CartSheet() {
    const items = useCart();
    const [open, setOpen] = useState(false);

    const count = cartCount(items);
    const total = items.reduce((sum, item) => sum + item.price * item.qty, 0);

    const groups = new Map<string, CartItem[]>();

    for (const item of items) {
        const group = groups.get(item.storeSlug) ?? [];
        group.push(item);
        groups.set(item.storeSlug, group);
    }

    return (
        <Sheet open={open} onOpenChange={setOpen}>
            <SheetTrigger asChild>
                <Button
                    type="button"
                    variant="ghost"
                    size="icon"
                    aria-label={`Keranjang, ${count} item`}
                    className="relative text-white hover:bg-white/10 hover:text-white dark:text-white"
                >
                    <ShoppingCart aria-hidden className="size-5" />
                    {count > 0 && (
                        <span className="absolute -top-0.5 -right-0.5 flex h-[18px] min-w-[18px] items-center justify-center bg-highlight px-1 text-xs font-bold text-highlight-foreground">
                            {count}
                        </span>
                    )}
                </Button>
            </SheetTrigger>

            <SheetContent>
                <SheetHeader>
                    <SheetTitle className="font-display text-xl">
                        Keranjang
                    </SheetTitle>
                    <SheetDescription>
                        Keranjang tersimpan di perangkat ini. Checkout dibuat
                        setelah kamu masuk.
                    </SheetDescription>
                </SheetHeader>

                <div className="min-h-0 flex-1 overflow-y-auto px-4 pb-4">
                    {items.length === 0 ? (
                        <div className="py-10 text-center">
                            <ShoppingCart
                                aria-hidden
                                className="mx-auto size-8 text-muted-foreground"
                            />
                            <p className="mx-auto mt-3 max-w-prose text-sm text-muted-foreground">
                                Keranjangmu masih kosong. Lihat produk dari
                                lapak teman.
                            </p>
                            <Button
                                asChild
                                variant="secondary"
                                className="mt-4"
                            >
                                <Link
                                    href={explore()}
                                    onClick={() => setOpen(false)}
                                >
                                    Jelajahi produk
                                </Link>
                            </Button>
                        </div>
                    ) : (
                        <div className="grid gap-6">
                            {[...groups.entries()].map(([storeSlug, group]) => (
                                <section key={storeSlug}>
                                    <Link
                                        href={storeShow.url(storeSlug)}
                                        onClick={() => setOpen(false)}
                                        className="text-sm font-semibold hover:text-primary"
                                    >
                                        {group[0].storeName}
                                    </Link>

                                    <ul className="mt-2 divide-y divide-border border-y border-border">
                                        {group.map((item) => (
                                            <li
                                                key={item.productId}
                                                className="flex gap-3 py-3"
                                            >
                                                {item.imageUrl !== null ? (
                                                    <img
                                                        src={item.imageUrl}
                                                        alt=""
                                                        width={56}
                                                        height={56}
                                                        loading="lazy"
                                                        className="size-14 shrink-0 object-cover"
                                                    />
                                                ) : (
                                                    <span className="size-14 shrink-0 bg-secondary" />
                                                )}

                                                <div className="min-w-0 flex-1">
                                                    <p className="truncate text-sm font-medium">
                                                        {item.name}
                                                    </p>
                                                    <p className="text-sm text-price">
                                                        {formatRupiah(
                                                            item.price,
                                                        )}
                                                    </p>

                                                    <div className="mt-1 flex items-center gap-1">
                                                        <Button
                                                            type="button"
                                                            variant="outline"
                                                            size="icon"
                                                            aria-label={`Kurangi jumlah ${item.name}`}
                                                            disabled={
                                                                item.qty <= 1
                                                            }
                                                            onClick={() =>
                                                                setCartQty(
                                                                    item.productId,
                                                                    item.qty -
                                                                        1,
                                                                )
                                                            }
                                                        >
                                                            <Minus
                                                                aria-hidden
                                                            />
                                                        </Button>
                                                        <span className="min-w-8 text-center text-sm">
                                                            {item.qty}
                                                        </span>
                                                        <Button
                                                            type="button"
                                                            variant="outline"
                                                            size="icon"
                                                            aria-label={`Tambah jumlah ${item.name}`}
                                                            disabled={
                                                                item.qty >=
                                                                item.stock
                                                            }
                                                            onClick={() =>
                                                                setCartQty(
                                                                    item.productId,
                                                                    item.qty +
                                                                        1,
                                                                )
                                                            }
                                                        >
                                                            <Plus aria-hidden />
                                                        </Button>

                                                        <Button
                                                            type="button"
                                                            variant="ghost"
                                                            size="icon"
                                                            aria-label={`Hapus ${item.name} dari keranjang`}
                                                            className="ml-auto"
                                                            onClick={() =>
                                                                removeFromCart(
                                                                    item.productId,
                                                                )
                                                            }
                                                        >
                                                            <Trash2
                                                                aria-hidden
                                                            />
                                                        </Button>
                                                    </div>
                                                </div>
                                            </li>
                                        ))}
                                    </ul>
                                </section>
                            ))}
                        </div>
                    )}
                </div>

                {items.length > 0 && (
                    <SheetFooter className="border-t border-border pb-[calc(1rem+env(safe-area-inset-bottom))]">
                        <div className="flex items-center justify-between">
                            <span className="text-sm text-muted-foreground">
                                Total
                            </span>
                            <span className="text-lg font-semibold text-price">
                                {formatRupiah(total)}
                            </span>
                        </div>
                    </SheetFooter>
                )}
            </SheetContent>
        </Sheet>
    );
}
