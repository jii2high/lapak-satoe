import { Head, Link } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import AppearanceToggleTab from '@/components/appearance-tabs';
import { edit as account } from '@/routes/account';

export default function Preference() {
    return (
        <>
            <Head title="Preferensi tampilan" />

            <Link
                href={account()}
                className="mb-6 inline-flex min-h-11 items-center gap-2 text-sm font-medium text-muted-foreground hover:text-foreground focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring"
            >
                <ArrowLeft className="size-4" aria-hidden />
                Kembali ke akun
            </Link>

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
