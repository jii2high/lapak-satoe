import { Link, router } from '@inertiajs/react';
import { useState } from 'react';
import InputError from '@/components/input-error';
import { OrderDetailDialog } from '@/components/marketplace/order-detail-dialog';
import { OrderStatusBadge } from '@/components/marketplace/order-status-badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { formatRupiah } from '@/lib/format';
import { cancel, confirm } from '@/routes/orders';
import {
    accept,
    cancel as sellerCancel,
    complete,
    reject,
} from '@/routes/seller/orders';
import { show as storeShow } from '@/routes/stores';
import type { OrderData } from '@/types';

type Viewer = 'buyer' | 'seller';

type DialogKind =
    | 'detail'
    | 'buyer-cancel'
    | 'buyer-confirm'
    | 'seller-accept'
    | 'seller-reject'
    | 'seller-complete'
    | 'seller-cancel';

const confirmCopy: Record<
    Exclude<DialogKind, 'detail' | 'seller-cancel'>,
    { title: string; body: string; action: string; destructive: boolean }
> = {
    'buyer-cancel': {
        title: 'Batalkan pesanan ini?',
        body: 'Reservasi stok dilepas. Kamu bisa memesan lagi selama stoknya masih ada.',
        action: 'Batalkan',
        destructive: true,
    },
    'buyer-confirm': {
        title: 'Pesanan sudah kamu terima?',
        body: 'Pesanan ditutup dan dihitung selesai. Lakukan hanya kalau barangnya sudah kamu terima.',
        action: 'Konfirmasi selesai',
        destructive: false,
    },
    'seller-accept': {
        title: 'Terima pesanan ini?',
        body: 'Stok produk dikurangi dan pesanan masuk ke tab Diproses.',
        action: 'Terima pesanan',
        destructive: false,
    },
    'seller-reject': {
        title: 'Tolak pesanan ini?',
        body: 'Reservasi stok dilepas dan pesanan ditutup.',
        action: 'Tolak',
        destructive: true,
    },
    'seller-complete': {
        title: 'Tandai pesanan sudah selesai?',
        body: 'Pembeli diminta mengonfirmasi. Kalau dibiarkan, pesanan selesai otomatis.',
        action: 'Tandai selesai',
        destructive: false,
    },
};

export function OrderCard({
    order,
    viewer,
}: {
    order: OrderData;
    viewer: Viewer;
}) {
    const [dialog, setDialog] = useState<DialogKind | null>(null);
    const [note, setNote] = useState('');
    const [noteError, setNoteError] = useState<string>();
    const [busy, setBusy] = useState(false);

    const close = () => {
        setDialog(null);
        setNote('');
        setNoteError(undefined);
    };

    const run = (url: string, data: Record<string, string> = {}) => {
        setBusy(true);
        router.post(url, data, {
            preserveScroll: true,
            onError: (errors) => {
                if (errors.note !== undefined) {
                    setNoteError(errors.note);
                }
            },
            onSuccess: close,
            onFinish: () => setBusy(false),
        });
    };

    const submitNote = () => {
        setNoteError(undefined);
        setBusy(true);

        router.post(
            sellerCancel.url(order.id),
            { note },
            {
                preserveScroll: true,
                onError: (errors) => setNoteError(errors.note),
                onSuccess: close,
                onFinish: () => setBusy(false),
            },
        );
    };

    const summary =
        order.items.length <= 2
            ? order.items.map((item) => `${item.name} ×${item.qty}`).join(', ')
            : `${order.items[0].name} ×${order.items[0].qty}, dan ${
                  order.items.length - 1
              } produk lain`;

    const confirmDialog =
        dialog === null || dialog === 'detail' || dialog === 'seller-cancel'
            ? null
            : confirmCopy[dialog];

    return (
        <article className="border border-border bg-card">
            <div className="flex flex-wrap items-start justify-between gap-2 border-b border-border p-4">
                <div className="min-w-0">
                    <p className="font-display text-base font-semibold tabular-nums">
                        {order.code}
                    </p>
                    {viewer === 'buyer' ? (
                        <Link
                            href={storeShow.url(order.store.slug)}
                            className="text-sm text-muted-foreground hover:text-primary"
                        >
                            {order.store.name}
                        </Link>
                    ) : (
                        <p className="text-sm text-muted-foreground">
                            @{order.buyer.username}
                        </p>
                    )}
                </div>

                <OrderStatusBadge status={order.status} viewer={viewer} />
            </div>

            <div className="p-4 text-sm">
                <p className="text-muted-foreground">{summary}</p>
                <div className="mt-2 flex items-center justify-between border-t border-dashed border-border pt-2">
                    <span className="text-muted-foreground">Total</span>
                    <span className="font-semibold text-price tabular-nums">
                        {formatRupiah(order.total)}
                    </span>
                </div>

                {order.cancel_note !== null && (
                    <p className="mt-2 text-xs text-muted-foreground">
                        Alasan pembatalan: {order.cancel_note}
                    </p>
                )}
            </div>

            <div className="flex flex-wrap gap-2 border-t border-border p-4">
                <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    onClick={() => setDialog('detail')}
                >
                    Detail
                </Button>

                {viewer === 'seller' && order.status === 'unconfirmed' && (
                    <>
                        <Button
                            type="button"
                            size="sm"
                            onClick={() => setDialog('seller-accept')}
                        >
                            Terima
                        </Button>
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            onClick={() => setDialog('seller-reject')}
                        >
                            Tolak
                        </Button>
                    </>
                )}

                {viewer === 'seller' && order.status === 'pending' && (
                    <>
                        <Button
                            type="button"
                            size="sm"
                            onClick={() => setDialog('seller-complete')}
                        >
                            Selesai
                        </Button>
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            onClick={() => setDialog('seller-cancel')}
                        >
                            Batalkan
                        </Button>
                    </>
                )}

                {viewer === 'buyer' && order.status === 'unconfirmed' && (
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        onClick={() => setDialog('buyer-cancel')}
                    >
                        Batalkan
                    </Button>
                )}

                {viewer === 'buyer' && order.status === 'awaiting_buyer' && (
                    <Button
                        type="button"
                        size="sm"
                        onClick={() => setDialog('buyer-confirm')}
                    >
                        Konfirmasi selesai
                    </Button>
                )}
            </div>

            <OrderDetailDialog
                order={order}
                viewer={viewer}
                open={dialog === 'detail'}
                onOpenChange={(open) => setDialog(open ? 'detail' : null)}
            />

            {confirmDialog !== null && (
                <Dialog open onOpenChange={close}>
                    <DialogContent className="sm:max-w-[480px]">
                        <DialogHeader>
                            <DialogTitle className="font-display text-xl">
                                {confirmDialog.title}
                            </DialogTitle>
                            <DialogDescription>
                                {confirmDialog.body}
                            </DialogDescription>
                        </DialogHeader>
                        <DialogFooter>
                            <Button
                                type="button"
                                variant="outline"
                                onClick={close}
                            >
                                Batal
                            </Button>
                            <Button
                                type="button"
                                disabled={busy}
                                variant={
                                    confirmDialog.destructive
                                        ? 'destructive'
                                        : 'default'
                                }
                                onClick={() => run(actionUrl(dialog, order.id))}
                            >
                                {confirmDialog.action}
                            </Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>
            )}

            <Dialog
                open={dialog === 'seller-cancel'}
                onOpenChange={(open) => (open ? null : close())}
            >
                <DialogContent className="sm:max-w-[480px]">
                    <DialogHeader>
                        <DialogTitle className="font-display text-xl">
                            Batalkan pesanan ini?
                        </DialogTitle>
                        <DialogDescription>
                            Stok produk dikembalikan. Tulis alasan supaya
                            pembeli tahu.
                        </DialogDescription>
                    </DialogHeader>

                    <div className="grid gap-2">
                        <Label htmlFor={`cancel-note-${order.id}`}>
                            Alasan pembatalan
                        </Label>
                        <Textarea
                            id={`cancel-note-${order.id}`}
                            value={note}
                            maxLength={500}
                            onChange={(event) => setNote(event.target.value)}
                        />
                        <InputError message={noteError} />
                    </div>

                    <DialogFooter>
                        <Button type="button" variant="outline" onClick={close}>
                            Batal
                        </Button>
                        <Button
                            type="button"
                            variant="destructive"
                            disabled={busy || note.trim() === ''}
                            onClick={submitNote}
                        >
                            Batalkan pesanan
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </article>
    );
}

function actionUrl(dialog: DialogKind | null, orderId: number): string {
    switch (dialog) {
        case 'buyer-cancel':
            return cancel.url(orderId);
        case 'buyer-confirm':
            return confirm.url(orderId);
        case 'seller-accept':
            return accept.url(orderId);
        case 'seller-reject':
            return reject.url(orderId);
        case 'seller-complete':
            return complete.url(orderId);
        default:
            return '';
    }
}
