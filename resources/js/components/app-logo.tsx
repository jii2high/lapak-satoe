import { usePage } from '@inertiajs/react';

export default function AppLogo() {
    const { name } = usePage().props;

    return (
        <span className="truncate font-display text-lg font-bold">
            {name || 'Lapak Satoe'}
        </span>
    );
}
