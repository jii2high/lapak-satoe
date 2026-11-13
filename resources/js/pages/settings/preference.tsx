import { Head } from '@inertiajs/react';
import AppearanceToggleTab from '@/components/appearance-tabs';
import { edit as account } from '@/routes/account';

export default function Preference() {
    return (
        <>
            <Head title="Preferensi tampilan" />

            <h1 className="font-display text-2xl font-bold">
                Preferensi tampilan
            </h1>
            <p className="mt-1 text-sm text-muted-foreground">
                Pilih tema yang nyaman untuk matamu.
            </p>

            <section className="mt-8 max-w-2xl rounded-md border border-border bg-card px-4 py-5">
                <h2 className="font-display text-lg font-semibold">Tema</h2>
                <p className="mt-1 text-sm text-muted-foreground">
                    Pilihan disimpan di perangkat ini. Pilih sistem untuk
                    mengikuti pengaturan HP atau laptopmu.
                </p>
                <AppearanceToggleTab className="mt-4" />
            </section>
        </>
    );
}

Preference.layout = {
    breadcrumbs: [
        {
            title: 'Pengaturan',
            href: account(),
        },
    ],
};
