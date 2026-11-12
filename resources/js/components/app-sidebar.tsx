import { Link, router, usePage } from '@inertiajs/react';
import {
    ArrowLeftRight,
    Home,
    LayoutGrid,
    LockKeyhole,
    Palette,
    Tags,
    UserRound,
} from 'lucide-react';
import AppLogo from '@/components/app-logo';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarGroup,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { home } from '@/routes';
import { edit as account, preference, switchRole } from '@/routes/account';
import { edit as security } from '@/routes/security';
import { dashboard as adminDashboard } from '@/routes/admin';
import { index as adminCategories } from '@/routes/admin/categories';
import { dashboard as sellerDashboard } from '@/routes/seller';
import type { NavItem } from '@/types';

export function AppSidebar() {
    const { auth } = usePage().props;
    const role = auth.user.active_role;

    const dashboardHref =
        role === 'admin'
            ? adminDashboard()
            : role === 'seller'
              ? sellerDashboard()
              : home();

    const mainNavItems: NavItem[] = [
        ...(role === 'buyer'
            ? [{ title: 'Beranda', href: home(), icon: Home }]
            : [{ title: 'Dashboard', href: dashboardHref, icon: LayoutGrid }]),
        ...(role === 'admin'
            ? [
                  {
                      title: 'Kategori',
                      href: adminCategories(),
                      icon: Tags,
                  },
              ]
            : []),
        { title: 'Akun', href: account(), icon: UserRound },
        { title: 'Preferensi', href: preference(), icon: Palette },
        { title: 'Keamanan', href: security(), icon: LockKeyhole },
    ];

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={dashboardHref} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={mainNavItems} />
            </SidebarContent>

            <SidebarFooter>
                {role !== 'buyer' && (
                    <SidebarGroup className="p-0">
                        <SidebarMenu>
                            <SidebarMenuItem>
                                <SidebarMenuButton
                                    onClick={() =>
                                        router.post(switchRole.url(), {
                                            role: 'buyer',
                                        })
                                    }
                                    tooltip={{ children: 'Ganti ke Buyer' }}
                                >
                                    <ArrowLeftRight />
                                    <span>
                                        Mode:{' '}
                                        {role === 'admin' ? 'Admin' : 'Seller'}
                                    </span>
                                </SidebarMenuButton>
                                <p className="px-2 pt-1 text-xs text-sidebar-foreground/70 group-data-[collapsible=icon]:hidden">
                                    Ganti ke Buyer
                                </p>
                            </SidebarMenuItem>
                        </SidebarMenu>
                    </SidebarGroup>
                )}
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
