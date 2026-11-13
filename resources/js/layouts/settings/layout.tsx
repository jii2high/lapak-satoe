import { Link } from '@inertiajs/react';
import type { PropsWithChildren } from 'react';
import { useCurrentUrl } from '@/hooks/use-current-url';
import { cn, toUrl } from '@/lib/utils';
import { edit as account, preference } from '@/routes/account';
import type { NavItem } from '@/types';

const sidebarNavItems: NavItem[] = [
    {
        title: 'Akun',
        href: account(),
    },
    {
        title: 'Preferensi',
        href: preference(),
    },
];

export default function SettingsLayout({
    children,
    padded = true,
}: PropsWithChildren<{ padded?: boolean }>) {
    const { isCurrentOrParentUrl } = useCurrentUrl();

    return (
        <div className={cn(padded && 'px-4 py-6 md:px-6')}>
            <h1 className="font-display text-2xl font-bold">Pengaturan</h1>
            <p className="mt-1 text-sm text-muted-foreground">
                Kelola akun dan tampilanmu.
            </p>

            <div className="mt-6 flex flex-col gap-6 lg:flex-row lg:gap-12">
                <nav
                    className="flex w-full gap-1 overflow-x-auto border-b border-border pb-2 lg:w-48 lg:flex-col lg:border-b-0 lg:pb-0"
                    aria-label="Pengaturan"
                >
                    {sidebarNavItems.map((item, index) => (
                        <Link
                            key={`${toUrl(item.href)}-${index}`}
                            href={item.href}
                            className={cn(
                                'flex min-h-11 shrink-0 items-center rounded-md px-3 text-sm font-medium transition-colors focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring',
                                isCurrentOrParentUrl(item.href)
                                    ? 'bg-secondary text-secondary-foreground'
                                    : 'text-muted-foreground hover:bg-accent hover:text-accent-foreground',
                            )}
                        >
                            {item.title}
                        </Link>
                    ))}
                </nav>

                <div className="flex-1 md:max-w-2xl">
                    <section className="max-w-xl space-y-12">
                        {children}
                    </section>
                </div>
            </div>
        </div>
    );
}
