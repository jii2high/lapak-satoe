import { createInertiaApp } from '@inertiajs/react';
import { Toaster } from '@/components/ui/sonner';
import { TooltipProvider } from '@/components/ui/tooltip';
import { initializeTheme } from '@/hooks/use-appearance';
import AppLayout from '@/layouts/app-layout';
import AuthLayout from '@/layouts/auth-layout';
import PublicLayout from '@/layouts/public-layout';
import SettingsLayout from '@/layouts/settings/layout';
import type { Auth } from '@/types';

const appName = import.meta.env.VITE_APP_NAME || 'Lapak Satoe';

void createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    layout: (name, page) => {
        switch (true) {
            case name.startsWith('marketplace/'):
            case name.startsWith('legal/'):
                return PublicLayout;
            case name.startsWith('auth/'):
                return AuthLayout;
            case name === 'seller/application':
                return null;
            case name.startsWith('settings/'): {
                const auth = page.props.auth as Auth | undefined;

                return auth?.user.active_role === 'buyer'
                    ? [PublicLayout, [SettingsLayout, { padded: false }]]
                    : [AppLayout, SettingsLayout];
            }
            default:
                return AppLayout;
        }
    },
    strictMode: true,
    withApp(app) {
        return (
            <TooltipProvider delayDuration={0}>
                {app}
                <Toaster />
            </TooltipProvider>
        );
    },
    progress: {
        color: 'var(--highlight)',
        delay: 150,
    },
});

// This will set light / dark mode on load...
initializeTheme();
