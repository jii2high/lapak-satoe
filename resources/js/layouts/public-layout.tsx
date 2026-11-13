import { Link, usePage } from '@inertiajs/react';
import {
    Compass,
    Heart,
    Home,
    ReceiptText,
    Search,
    UserRound,
} from 'lucide-react';
import { type PropsWithChildren, useEffect, useState } from 'react';
import { CartSheet } from '@/components/marketplace/cart-sheet';
import { SearchInput } from '@/components/marketplace/search-input';
import { Button } from '@/components/ui/button';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
    SheetTrigger,
} from '@/components/ui/sheet';
import { useCurrentUrl } from '@/hooks/use-current-url';
import { cn } from '@/lib/utils';
import { explore, home, login, register, terms } from '@/routes';
import { edit as account } from '@/routes/account';
import { index as orders } from '@/routes/orders';
import { index as wishlist } from '@/routes/wishlist';

const headerLink =
    'flex min-h-11 items-center rounded-md px-3 font-medium hover:bg-white/10 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white';

const iconLink =
    'flex size-11 items-center justify-center rounded-md hover:bg-white/10 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white';

function MobileSearchSheet() {
    const [open, setOpen] = useState(false);
    const { url } = usePage();

    useEffect(() => {
        setOpen(false);
    }, [url]);

    return (
        <Sheet open={open} onOpenChange={setOpen}>
            <SheetTrigger asChild>
                <Button
                    type="button"
                    variant="ghost"
                    size="icon"
                    aria-label="Cari produk atau lapak"
                    className={cn(iconLink, 'md:hidden')}
                >
                    <Search aria-hidden className="size-5" />
                </Button>
            </SheetTrigger>

            <SheetContent>
                <SheetHeader>
                    <SheetTitle className="font-display text-xl">
                        Cari
                    </SheetTitle>
                    <SheetDescription>
                        Tulis nama produk atau lapak, lalu tekan Enter.
                    </SheetDescription>
                </SheetHeader>

                <div className="px-4 pb-6">
                    <SearchInput autoFocus />
                </div>
            </SheetContent>
        </Sheet>
    );
}

export default function PublicLayout({ children }: PropsWithChildren) {
    const { auth } = usePage().props;
    const { isCurrentUrl, isCurrentOrParentUrl } = useCurrentUrl();

    const navItems = [
        {
            title: 'Beranda',
            href: home(),
            icon: Home,
            active: isCurrentUrl(home()),
        },
        {
            title: 'Jelajah',
            href: explore(),
            icon: Compass,
            active: isCurrentUrl(explore()),
        },
        {
            title: 'Pesanan',
            href: orders(),
            icon: ReceiptText,
            active: isCurrentOrParentUrl(orders()),
        },
        {
            title: 'Akun',
            href: auth.user ? account() : login(),
            icon: UserRound,
            active: isCurrentOrParentUrl(account()),
        },
    ];

    return (
        <div className="flex min-h-svh flex-col bg-background">
            <header className="bg-[#2F6B4F] text-white dark:bg-[#1E4533]">
                <div className="mx-auto flex h-14 w-full max-w-[1280px] items-center gap-2 px-4 md:h-16 md:gap-6 md:px-6 lg:px-8">
                    <Link
                        href={home()}
                        className="flex min-h-11 shrink-0 items-center font-display text-xl font-bold focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-white"
                    >
                        Lapak Satoe
                    </Link>

                    <Link
                        href={explore()}
                        className={cn(headerLink, 'hidden md:flex')}
                    >
                        Jelajah
                    </Link>

                    <div className="hidden flex-1 md:block">
                        <SearchInput />
                    </div>

                    <nav
                        className="ml-auto flex items-center gap-1 text-sm"
                        aria-label="Navigasi utama"
                    >
                        <MobileSearchSheet />

                        <Link
                            href={wishlist()}
                            aria-label="Wishlist"
                            className={iconLink}
                        >
                            <Heart aria-hidden className="size-5" />
                        </Link>

                        <CartSheet />

                        {auth.user ? (
                            <>
                                <Link
                                    href={account()}
                                    aria-label="Akun"
                                    className={cn(iconLink, 'md:hidden')}
                                >
                                    <UserRound aria-hidden className="size-5" />
                                </Link>
                                <Link
                                    href={account()}
                                    className={cn(
                                        headerLink,
                                        'hidden max-w-40 gap-2 md:flex',
                                    )}
                                >
                                    <UserRound
                                        aria-hidden
                                        className="size-5 shrink-0"
                                    />
                                    <span className="truncate">
                                        {auth.user.display_name}
                                    </span>
                                </Link>
                            </>
                        ) : (
                            <>
                                <Link
                                    href={login()}
                                    aria-label="Masuk"
                                    className={cn(iconLink, 'md:hidden')}
                                >
                                    <UserRound aria-hidden className="size-5" />
                                </Link>
                                <Link
                                    href={login()}
                                    className={cn(
                                        headerLink,
                                        'hidden border border-white/70 md:flex',
                                    )}
                                >
                                    Masuk
                                </Link>
                                <Link
                                    href={register()}
                                    className="hidden min-h-11 items-center rounded-md bg-secondary px-3 font-semibold text-secondary-foreground hover:bg-[#CFE0D4] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white md:flex"
                                >
                                    Daftar
                                </Link>
                            </>
                        )}
                    </nav>
                </div>
                <div className="awning awning-sm awning-intro" />
            </header>

            <main className="mx-auto w-full max-w-[1280px] flex-1 px-4 py-6 pb-24 md:px-6 md:py-10 md:pb-12 lg:px-8">
                {children}
            </main>

            <footer className="bg-[#24543D] text-[#F8F9F5]">
                <div className="awning" />
                <div className="mx-auto flex w-full max-w-[1280px] flex-col gap-2 px-4 pt-6 pb-[calc(6rem+env(safe-area-inset-bottom))] text-sm md:px-6 md:pb-6 lg:px-8">
                    <span className="font-display text-lg font-semibold">
                        Lapak Satoe
                    </span>
                    <p className="text-[#CFE0D4]">
                        Katalog lapak siswa SMKN 1 Kota Bekasi. Transaksi
                        selesai lewat WhatsApp.
                    </p>
                    <Link
                        href={terms()}
                        className="mt-1 flex min-h-11 items-center self-start underline underline-offset-4 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-white"
                    >
                        Syarat dan ketentuan
                    </Link>
                </div>
            </footer>

            <nav
                aria-label="Navigasi bawah"
                className="fixed inset-x-0 bottom-0 z-40 border-t border-border bg-background pb-[env(safe-area-inset-bottom)] md:hidden"
            >
                <ul className="grid grid-cols-4">
                    {navItems.map((item) => (
                        <li key={item.title}>
                            <Link
                                href={item.href}
                                aria-current={item.active ? 'page' : undefined}
                                className={cn(
                                    'flex min-h-14 flex-col items-center justify-center gap-0.5 border-t-2 text-xs',
                                    item.active
                                        ? 'border-primary font-semibold text-primary'
                                        : 'border-transparent text-muted-foreground',
                                )}
                            >
                                <item.icon aria-hidden className="size-5" />
                                {item.title}
                            </Link>
                        </li>
                    ))}
                </ul>
            </nav>
        </div>
    );
}
