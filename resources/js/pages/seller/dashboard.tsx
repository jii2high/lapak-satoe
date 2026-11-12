import { Head } from '@inertiajs/react';
import { dashboard } from '@/routes/seller';

export default function SellerDashboard() {
    return (
        <>
            <Head title="Dashboard seller" />

            <div className="flex flex-1 flex-col gap-8 px-4 py-6 md:px-6">
                <div>
                    <h1 className="font-display text-2xl font-bold">
                        Dashboard
                    </h1>
                    <p className="mt-1 text-sm text-muted-foreground">
                        Ringkasan lapakmu akan tampil di sini.
                    </p>
                </div>

                <section className="border border-border bg-card px-4 py-6">
                    <h2 className="font-display text-lg font-semibold">
                        Lapak belum dibuat
                    </h2>
                    <p className="mt-1 max-w-prose text-sm text-muted-foreground">
                        Setelah lapak dibuat, pendapatan dan pesanan masuk akan
                        muncul di halaman ini.
                    </p>
                </section>
            </div>
        </>
    );
}

SellerDashboard.layout = {
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: dashboard(),
        },
    ],
};
