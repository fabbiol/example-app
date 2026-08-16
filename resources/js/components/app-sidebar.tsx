import { Link, usePage } from '@inertiajs/react';
import {
    ClipboardList,
    Factory,
    GitBranch,
    LayoutGrid,
    Package,
    Percent,
    Scale,
    ScrollText,
    Shield,
    Shovel,
    ShoppingCart,
    Truck,
    UserRound,
    Users,
} from 'lucide-react';
import AppLogo from '@/components/app-logo';
import { NavFooter } from '@/components/nav-footer';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard, flow } from '@/routes';
import { index as activities } from '@/routes/activities';
import { edit as crushingCircuits } from '@/routes/crushing-circuits';
import { index as customers } from '@/routes/customers';
import { index as estimatedLoadings } from '@/routes/estimated-loadings';
import { index as loader } from '@/routes/loader';
import { index as orders } from '@/routes/orders';
import { index as production } from '@/routes/production';
import { index as products } from '@/routes/products';
import { edit as profile } from '@/routes/profile';
import { index as roles } from '@/routes/roles';
import { index as trucks } from '@/routes/trucks';
import { index as users } from '@/routes/users';
import { index as weighTickets } from '@/routes/weigh-tickets';
import type { NavGroup, NavItem } from '@/types';

const mainNavGroups: NavGroup[] = [
    {
        title: 'Operação',
        items: [
            {
                title: 'Dashboard',
                href: dashboard(),
                icon: LayoutGrid,
                permission: 'dashboard',
            },
            {
                title: 'Fluxo',
                href: flow(),
                icon: GitBranch,
                permission: 'flow',
            },
            {
                title: 'Atividades',
                href: activities(),
                icon: ScrollText,
                permission: 'activities',
            },
        ],
    },
    {
        title: 'Expedição',
        items: [
            {
                title: 'Pedidos',
                href: orders(),
                icon: ShoppingCart,
                permission: 'orders',
            },
            {
                title: 'Pá',
                href: loader(),
                icon: Shovel,
                permission: 'loader',
            },
            {
                title: 'Carregamentos',
                href: estimatedLoadings(),
                icon: ClipboardList,
                permission: 'estimated-loadings',
            },
            {
                title: 'Balança',
                href: weighTickets(),
                icon: Scale,
                permission: 'weigh-tickets',
            },
        ],
    },
    {
        title: 'Pátio',
        items: [
            {
                title: 'Produção',
                href: production(),
                icon: Factory,
                permission: 'production',
            },
            {
                title: 'Circuito',
                href: crushingCircuits(),
                icon: Percent,
                permission: 'crushing-circuits',
            },
        ],
    },
    {
        title: 'Cadastros',
        items: [
            {
                title: 'Produtos',
                href: products(),
                icon: Package,
                permission: 'products',
            },
            {
                title: 'Clientes',
                href: customers(),
                icon: Users,
                permission: 'customers',
            },
            {
                title: 'Pessoas',
                href: users(),
                icon: UserRound,
                permission: 'users',
            },
            {
                title: 'Papéis',
                href: roles(),
                icon: Shield,
                permission: 'roles',
            },
            {
                title: 'Caminhões',
                href: trucks(),
                icon: Truck,
                permission: 'trucks',
            },
        ],
    },
];

const footerNavItems: NavItem[] = [];

function allowedNavGroups(
    groups: NavGroup[],
    permissions: string[],
): NavGroup[] {
    return groups
        .map((group) => ({
            ...group,
            items: group.items.filter(
                (item) =>
                    !item.permission || permissions.includes(item.permission),
            ),
        }))
        .filter((group) => group.items.length > 0);
}

export function AppSidebar() {
    const permissions = usePage().props.auth.permissions ?? [];
    const groups = allowedNavGroups(mainNavGroups, permissions);
    const homeHref = groups[0]?.items[0]?.href ?? profile();

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={homeHref} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain groups={groups} />
            </SidebarContent>

            <SidebarFooter>
                <NavFooter items={footerNavItems} className="mt-auto" />
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
