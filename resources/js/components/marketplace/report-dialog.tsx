import { Link, useForm, usePage } from '@inertiajs/react';
import { Flag } from 'lucide-react';
import { useState } from 'react';
import InputError from '@/components/input-error';
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
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import { login } from '@/routes';
import { store as storeReport } from '@/routes/reports';

export type ReportTarget = {
    /** Nama objek untuk judul dialog. */
    label: string;
    productId?: number;
    storeId?: number;
    orderId?: number;
    /** Mode "Ada masalah" pada pesanan: pilihan objek di dalam pesanan. */
    items?: { id: number; name: string }[];
};

type Props = {
    target: ReportTarget;
    triggerLabel?: string;
    className?: string;
};

export function ReportDialog({
    target,
    triggerLabel = 'Laporkan',
    className,
}: Props) {
    const { auth, violation_types: violationTypes } = usePage().props;
    const [open, setOpen] = useState(false);
    const [sent, setSent] = useState(false);

    const form = useForm({
        violation_type: '',
        description: '',
        product_id: target.productId ?? null,
        store_id: target.storeId ?? null,
        order_id: target.orderId ?? null,
    });

    if (auth.user === null) {
        return (
            <Button asChild variant="outline" className={className}>
                <Link href={login()}>{triggerLabel}</Link>
            </Button>
        );
    }

    const submit = () => {
        form.post(storeReport.url(), {
            preserveScroll: true,
            onSuccess: () => {
                setOpen(false);
                setSent(true);
            },
        });
    };

    const close = () => {
        setOpen(false);
        form.clearErrors();
    };

    return (
        <>
            <Button
                type="button"
                variant="outline"
                className={className}
                onClick={() => setOpen(true)}
            >
                <Flag aria-hidden />
                {triggerLabel}
            </Button>

            <Dialog
                open={open}
                onOpenChange={(next) => (next ? setOpen(true) : close())}
            >
                <DialogContent className="sm:max-w-[480px]">
                    <DialogHeader>
                        <DialogTitle className="font-display text-xl">
                            Laporkan {target.label}
                        </DialogTitle>
                        <DialogDescription>
                            {target.orderId === undefined
                                ? 'Laporan diteruskan ke admin untuk ditinjau.'
                                : 'Pesanan ini masuk status dalam laporan dan konfirmasi otomatis berhenti sampai admin menanganinya.'}
                        </DialogDescription>
                    </DialogHeader>

                    <div className="grid gap-4">
                        {target.items !== undefined && (
                            <fieldset className="grid gap-2">
                                <legend className="text-sm font-semibold">
                                    Objek yang dilaporkan
                                </legend>
                                {[
                                    { id: null, name: `Lapak ${target.label}` },
                                    ...target.items,
                                ].map((option) => (
                                    <div
                                        key={option.id ?? 'store'}
                                        className="flex min-h-11 items-center gap-2"
                                    >
                                        <input
                                            id={`report-target-${option.id ?? 'store'}`}
                                            type="radio"
                                            name="report-target"
                                            value={option.id ?? ''}
                                            checked={
                                                form.data.product_id ===
                                                option.id
                                            }
                                            onChange={() =>
                                                form.setData(
                                                    'product_id',
                                                    option.id,
                                                )
                                            }
                                            className="size-4 shrink-0 accent-primary"
                                        />
                                        <Label
                                            htmlFor={`report-target-${option.id ?? 'store'}`}
                                        >
                                            {option.name}
                                        </Label>
                                    </div>
                                ))}
                            </fieldset>
                        )}

                        <div className="grid gap-2">
                            <Label htmlFor="report-violation">
                                Jenis pelanggaran
                            </Label>
                            <Select
                                value={form.data.violation_type}
                                onValueChange={(value) =>
                                    form.setData('violation_type', value)
                                }
                            >
                                <SelectTrigger
                                    id="report-violation"
                                    className="w-full"
                                >
                                    <SelectValue placeholder="Pilih jenis pelanggaran" />
                                </SelectTrigger>
                                <SelectContent>
                                    {Object.entries(violationTypes).map(
                                        ([value, label]) => (
                                            <SelectItem
                                                key={value}
                                                value={value}
                                            >
                                                {label}
                                            </SelectItem>
                                        ),
                                    )}
                                </SelectContent>
                            </Select>
                            <InputError message={form.errors.violation_type} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="report-description">
                                Deskripsi
                            </Label>
                            <Textarea
                                id="report-description"
                                value={form.data.description}
                                maxLength={1000}
                                onChange={(event) =>
                                    form.setData(
                                        'description',
                                        event.target.value,
                                    )
                                }
                            />
                            <InputError message={form.errors.description} />
                            <InputError message={form.errors.store_id} />
                            <InputError message={form.errors.product_id} />
                            <InputError message={form.errors.order_id} />
                        </div>
                    </div>

                    <DialogFooter>
                        <Button type="button" variant="outline" onClick={close}>
                            Batal
                        </Button>
                        <Button
                            type="button"
                            disabled={
                                form.processing ||
                                form.data.violation_type === '' ||
                                form.data.description.trim() === ''
                            }
                            onClick={submit}
                        >
                            {form.processing && <Spinner />}
                            Kirim laporan
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            <Dialog open={sent} onOpenChange={setSent}>
                <DialogContent className="sm:max-w-[480px]">
                    <DialogHeader>
                        <DialogTitle className="font-display text-xl">
                            Laporan terkirim
                        </DialogTitle>
                        <DialogDescription>
                            Admin meninjau laporan ini. Kamu dapat pemberitahuan
                            begitu laporan ditangani.
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter>
                        <Button type="button" onClick={() => setSent(false)}>
                            Tutup
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </>
    );
}
