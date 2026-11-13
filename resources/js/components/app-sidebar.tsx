import { Link, router, usePage } from '@inertiajs/react';
import {
    ArrowLeftRight,
    ClipboardList,
    Home,
    LayoutGrid,
    Package,
    Store,
    Tags,
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
import { switchRole } from '@/routes/account';
import { dashboard as adminDashboard } from '@/routes/admin';
import { index as adminApplications } from '@/routes/admin/applications';
import { index as adminCategories } from '@/routes/admin/categories';
import { dashboard as sellerDashboard } from '@/routes/seller';
import { index as sellerProducts } from '@/routes/seller/products';
import { edit as sellerStore } from '@/routes/seller/store';
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
        ...(role === 'seller'
            ? [
                  {
                      title: 'Produk',
                      href: sellerProducts(),
                      icon: Package,
                  },
                  {
                      title: 'Pengaturan lapak',
                      href: sellerStore(),
                      icon: Store,
                  },
              ]
            : []),
        ...(role === 'admin'
            ? [
                  {
                      title: 'Antrean pendaftaran',
                      href: adminApplications(),
                      icon: ClipboardList,
                  },
                  {
                      title: 'Kategori',
                      href: adminCategories(),
                      icon: Tags,
                  },
              ]
            : []),
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
