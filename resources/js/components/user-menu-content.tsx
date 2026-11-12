import { Link, router, usePage } from '@inertiajs/react';
import { LogOut, Palette, Settings, UserRound } from 'lucide-react';
import {
    DropdownMenuGroup,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
} from '@/components/ui/dropdown-menu';
import { UserInfo } from '@/components/user-info';
import { clearSessionCache } from '@/lib/session-cache';
import { useMobileNavigation } from '@/hooks/use-mobile-navigation';
import { logout } from '@/routes';
import { edit as account, preference } from '@/routes/account';
import { edit as security } from '@/routes/security';
import type { User } from '@/types';

type Props = {
    user: User;
};

export function UserMenuContent({ user }: Props) {
    const cleanup = useMobileNavigation();
    const { auth } = usePage().props;

    const handleLogout = () => {
        cleanup();
        clearSessionCache(auth.user.id);
        router.flushAll();
    };

    return (
        <>
            <DropdownMenuLabel className="p-0 font-normal">
                <div className="flex items-center gap-2 px-1 py-1.5 text-left text-sm">
                    <UserInfo user={user} showEmail={true} />
                </div>
            </DropdownMenuLabel>
            <DropdownMenuSeparator />
            <DropdownMenuGroup>
                <DropdownMenuItem asChild>
                    <Link
                        className="block w-full cursor-pointer"
                        href={account()}
                        prefetch
                        onClick={cleanup}
                    >
                        <UserRound className="mr-2" />
                        Akun
                    </Link>
                </DropdownMenuItem>
                <DropdownMenuItem asChild>
                    <Link
                        className="block w-full cursor-pointer"
                        href={preference()}
                        prefetch
                        onClick={cleanup}
                    >
                        <Palette className="mr-2" />
                        Preferensi tampilan
                    </Link>
                </DropdownMenuItem>
                <DropdownMenuItem asChild>
                    <Link
                        className="block w-full cursor-pointer"
                        href={security()}
                        prefetch
                        onClick={cleanup}
                    >
                        <Settings className="mr-2" />
                        Pengaturan keamanan
                    </Link>
                </DropdownMenuItem>
            </DropdownMenuGroup>
            <DropdownMenuSeparator />
            <DropdownMenuItem asChild>
                <Link
                    className="block w-full cursor-pointer"
                    href={logout()}
                    as="button"
                    onClick={handleLogout}
                    data-test="logout-button"
                >
                    <LogOut className="mr-2" />
                    Keluar
                </Link>
            </DropdownMenuItem>
        </>
    );
}
