import { Head, Link, router } from '@inertiajs/react';
import { Minus, Plus, ShoppingCart, Trash2 } from 'lucide-react';
import { useEffect, useRef } from 'react';
import { Button } from '@/components/ui/button';
import { cartPayload, clearCart } from '@/lib/cart-storage';
import { formatRupiah } from '@/lib/format';
import { explore } from '@/routes';
import { merge as cartMerge } from '@/routes/cart';
import {
    destroy as destroyItem,
    update as updateItem,
} from '@/routes/cart/items';
import { store as checkout } from '@/routes/checkout';
import { show as storeShow } from '@/routes/stores';
import type { CartGroupData } from '@/types';

type Props = {
    groups: CartGroupData[];
};

export default function Cart({ groups }: Props) {
    const merged = useRef(false);

    // Keranjang guest digabung sekali saat halaman dibuka (FR-CART-1).
    useEffect(() => {
        if (merged.current) {
            return;
        }

        merged.current = true;

        if (cartPayload().length === 0) {
            return;
        }

        router.post(
            cartMerge.url(),
            { items: cartPayload() },
            { preserveScroll: true, onSuccess: () => clearCart() },
        );
    }, []);

    const total = groups.reduce((sum, group) => sum + group.subtotal, 0);
    const hasProblem = groups.some((group) => group.problem !== null);

    if (groups.length === 0) {
        return (
            <>
                <Head title="Keranjang" />

                <div className="border border-border bg-card px-4 py-10 text-center">
                    <ShoppingCart
                        aria-hidden
                        className="mx-auto size-8 text-muted-foreground"
                    />
                    <h1 className="mt-3 font-display text-lg font-semibold">
                        Keranjang masih kosong
                    </h1>
                    <p className="mx-auto mt-1 max-w-prose text-sm text-muted-foreground">
                        Tambahkan produk dulu, lalu pesan lewat WhatsApp dari
                        sini.
                    </p>
                    <Button asChild variant="secondary" className="mt-4">
                        <Link href={explore()}>Jelajahi produk</Link>
                    </Button>
                </div>
            </>
        );
    }

    return (
        <>
            <Head title="Keranjang" />

            <div>
                <h1 className="font-display text-2xl font-bold">Keranjang</h1>
                <p className="mt-1 text-sm text-muted-foreground">
                    Satu pesanan per lapak. Pesanan dikirim lewat WhatsApp
                    lapaknya.
                </p>
            </div>

            <div className="mt-6 grid gap-4">
                {groups.map((group) => {
                    const closed =
                        group.store.status !== 'open' ||
                        group.store.is_suspended;

                    return (
                        <section
                            key={group.store.id}
                            className="border border-border bg-card"
                        >
                            <div className="awning awning-sm" />

                            {closed && (
                                <p className="border-b border-border bg-muted px-4 py-2 text-xs text-muted-foreground">
                                    Lapak ini lagi tutup, jadi belum bisa
                                    dipesan.
                                </p>
                            )}

                            <div className="p-4">
                                <Link
                                    href={storeShow.url(group.store.slug)}
                                    className="font-display text-lg font-semibold hover:text-primary"
                                >
                                    {group.store.name}
                                </Link>

                                <ul className="mt-3 divide-y divide-border border-y border-border">
                                    {group.items.map((item) => (
                                        <li key={item.id} className="py-3">
                                            <div className="flex gap-3">
                                                {item.image_url === null ? (
                                                    <span className="size-16 shrink-0 bg-secondary" />
                                                ) : (
                                                    <img
                                                        src={item.image_url}
                                                        alt=""
                                                        width={64}
                                                        height={64}
                                                        loading="lazy"
                                                        className="size-16 shrink-0 object-cover"
                                                    />
                                                )}

                                                <div className="min-w-0 flex-1">
                                                    <p className="truncate text-sm font-medium">
                                                        {item.name}
                                                    </p>
                                                    <p className="text-sm text-price tabular-nums">
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
                                                                router.patch(
                                                                    updateItem.url(
                                                                        item.id,
                                                                    ),
                                                                    {
                                                                        qty:
                                                                            item.qty -
                                                                            1,
                                                                    },
                                                                    {
                                                                        preserveScroll: true,
                                                                    },
                                                                )
                                                            }
                                                        >
                                                            <Minus
                                                                aria-hidden
                                                            />
                                                        </Button>
                                                        <span className="min-w-8 text-center text-sm tabular-nums">
                                                            {item.qty}
                                                        </span>
                                                        <Button
                                                            type="button"
                                                            variant="outline"
                                                            size="icon"
                                                            aria-label={`Tambah jumlah ${item.name}`}
                                                            disabled={
                                                                item.qty >=
                                                                item.available
                                                            }
                                                            onClick={() =>
                                                                router.patch(
                                                                    updateItem.url(
                                                                        item.id,
                                                                    ),
                                                                    {
                                                                        qty:
                                                                            item.qty +
                                                                            1,
                                                                    },
                                                                    {
                                                                        preserveScroll: true,
                                                                    },
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
                                                                router.delete(
                                                                    destroyItem.url(
                                                                        item.id,
                                                                    ),
                                                                    {
                                                                        preserveScroll: true,
                                                                    },
                                                                )
                                                            }
                                                        >
                                                            <Trash2
                                                                aria-hidden
                                                            />
                                                        </Button>
                                                    </div>
                                                </div>
                                            </div>

                                            {item.problem !== null && (
                                                <p className="mt-2 text-xs text-destructive">
                                                    {item.problem}
                                                </p>
                                            )}
                                        </li>
                                    ))}
                                </ul>

                                <div className="mt-3 flex items-center justify-between border-t border-dashed border-border pt-3">
                                    <span className="text-sm text-muted-foreground">
                                        Subtotal
                                    </span>
                                    <span className="font-semibold text-price tabular-nums">
                                        {formatRupiah(group.subtotal)}
                                    </span>
                                </div>

                                <Button
                                    type="button"
                                    size="lg"
                                    className="mt-3 w-full"
                                    disabled={group.problem !== null}
                                    onClick={() =>
                                        router.post(
                                            checkout.url(group.store.slug),
                                        )
                                    }
                                >
                                    Pesan via WhatsApp
                                </Button>

                                {group.problem !== null && (
                                    <p className="mt-2 text-xs text-muted-foreground">
                                        Bereskan dulu item yang bermasalah
                                        supaya bisa memesan.
                                    </p>
                                )}
                            </div>
                        </section>
                    );
                })}
            </div>

            <div className="mt-6 flex items-center justify-between border border-border bg-card px-4 py-3">
                <span className="font-semibold">Total semua lapak</span>
                <span className="font-display text-lg font-bold text-price tabular-nums">
                    {formatRupiah(total)}
                </span>
            </div>

            {hasProblem && (
                <p className="mt-2 text-xs text-muted-foreground">
                    Lapak dengan item bermasalah belum bisa dipesan.
                </p>
            )}
        </>
    );
}
