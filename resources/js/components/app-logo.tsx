import { usePage } from '@inertiajs/react';
import AppLogoIcon from '@/components/app-logo-icon';

export default function AppLogo() {
    const { name } = usePage().props;

    return (
        <div className="flex min-w-0 items-center gap-2">
            <AppLogoIcon
                aria-hidden
                className="size-8 shrink-0 group-data-[collapsible=icon]:size-4"
            />
            <span className="truncate font-display text-lg font-bold group-data-[collapsible=icon]:hidden">
                {name || 'Lapak Satoe'}
            </span>
        </div>
    );
}
