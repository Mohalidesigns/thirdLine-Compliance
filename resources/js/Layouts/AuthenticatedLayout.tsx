import React, { useState, useEffect, useCallback } from 'react';
import { Link, usePage } from '@inertiajs/react';
import {
    Squares2X2Icon,
    BookOpenIcon,
    ClipboardDocumentListIcon,
    ClipboardDocumentCheckIcon,
    DocumentTextIcon,
    ShieldExclamationIcon,
    CalendarDaysIcon,
    UserCircleIcon,
    Bars3Icon,
    XMarkIcon,
    BellIcon,
    MagnifyingGlassIcon,
    ChevronLeftIcon,
    ChevronRightIcon,
    BeakerIcon,
    ExclamationTriangleIcon,
    AcademicCapIcon,
    FireIcon,
} from '@heroicons/react/24/outline';
import Dropdown from '@/Components/Dropdown';
import FlashNotification from '@/Components/FlashNotification';
import { PageProps } from '@/types';
import { usePermissions } from '@/hooks/usePermission';

interface Props {
    children: React.ReactNode;
    header?: React.ReactNode;
}

type HeroIcon = React.ForwardRefExoticComponent<
    React.PropsWithoutRef<React.SVGProps<SVGSVGElement>> & {
        title?: string;
        titleId?: string;
    } & React.RefAttributes<SVGSVGElement>
>;

interface NavItem {
    label: string;
    routeName: string;
    Icon: HeroIcon;
    /** When set, the item is only visible to users who have this permission. */
    permission?: string;
}

const mainNavItems: NavItem[] = [
    { label: 'Dashboard',        routeName: 'dashboard',              Icon: Squares2X2Icon },
    { label: 'Library',          routeName: 'instruments.index',      Icon: BookOpenIcon },
    { label: 'Obligations',      routeName: 'obligations.index',      Icon: ClipboardDocumentListIcon },
    { label: 'Policies',         routeName: 'policies.index',         Icon: DocumentTextIcon,      permission: 'policies.view' },
    { label: 'Risk Assessments', routeName: 'risk-assessments.index', Icon: ShieldExclamationIcon, permission: 'cycles.view' },
    { label: 'Controls',         routeName: 'controls.index',         Icon: BeakerIcon,            permission: 'controls.view' },
    { label: 'Issues',           routeName: 'issues.index',           Icon: ExclamationTriangleIcon,      permission: 'issues.view' },
    { label: 'Incidents',        routeName: 'incidents.index',        Icon: FireIcon,                     permission: 'incidents.view' },
    { label: 'My Training',      routeName: 'my.training.index',      Icon: AcademicCapIcon,              permission: 'training.view' },
    { label: 'My Attestations',  routeName: 'my.attestations.index',  Icon: ClipboardDocumentCheckIcon,   permission: 'attestations.sign' },
    { label: 'Sanctions KB',     routeName: 'sanctions.index',        Icon: ShieldExclamationIcon },
    { label: 'Calendar',         routeName: 'calendar.index',         Icon: CalendarDaysIcon },
];

const accountNavItems: NavItem[] = [
    { label: 'Profile', routeName: 'profile.edit', Icon: UserCircleIcon },
];

function SidebarNavItem({
    item,
    collapsed,
    onClick,
}: {
    item: NavItem;
    collapsed: boolean;
    onClick?: () => void;
}) {
    const isActive = route().current(item.routeName);
    const { Icon } = item;

    return (
        <Link
            href={route(item.routeName)}
            onClick={onClick}
            className={`sidebar-nav-item${isActive ? ' active' : ''}`}
        >
            <Icon aria-hidden className="w-5 h-5 flex-shrink-0" />
            {!collapsed && <span className="truncate">{item.label}</span>}
        </Link>
    );
}

export default function AuthenticatedLayout({ children, header }: Props) {
    const { auth } = usePage<PageProps>().props;
    const permissions = usePermissions();

    const visibleNavItems = mainNavItems.filter(
        (item) => !item.permission || permissions.includes(item.permission),
    );

    const [collapsed, setCollapsed] = useState<boolean>(() => {
        if (typeof window !== 'undefined') {
            return localStorage.getItem('sidebar-collapsed') === 'true';
        }
        return false;
    });

    const [mobileOpen, setMobileOpen] = useState(false);

    useEffect(() => {
        localStorage.setItem('sidebar-collapsed', String(collapsed));
    }, [collapsed]);

    const toggleCollapsed = useCallback(() => setCollapsed((v) => !v), []);
    const closeMobile = useCallback(() => setMobileOpen(false), []);

    return (
        <div className="min-h-screen" style={{ backgroundColor: 'var(--color-bg)' }}>
            {/* Mobile backdrop */}
            {mobileOpen && (
                <div
                    className="fixed inset-0 bg-black/50 z-30 lg:hidden"
                    onClick={closeMobile}
                    aria-hidden="true"
                />
            )}

            {/* Sidebar */}
            <aside
                className={`sidebar${collapsed ? ' collapsed' : ''} ${
                    mobileOpen ? 'translate-x-0' : '-translate-x-full'
                } lg:translate-x-0`}
                aria-label="Sidebar"
            >
                {/* Logo row */}
                <div className="h-16 flex items-center px-6 flex-shrink-0 border-b border-white/10">
                    {collapsed ? (
                        <span
                            className="mx-auto text-lg font-bold"
                            style={{ color: 'var(--color-accent)' }}
                        >
                            A
                        </span>
                    ) : (
                        <Link href="/" className="flex items-center gap-0">
                            <span
                                className="text-xl font-bold leading-none"
                                style={{ color: 'var(--color-accent)' }}
                            >
                                A
                            </span>
                            <span className="text-xl font-bold text-white leading-none">theris</span>
                        </Link>
                    )}
                </div>

                {/* Nav */}
                <nav aria-label="Main navigation" className="flex-1 pt-4 pb-4">
                    {!collapsed && (
                        <p className="text-[10px] font-semibold text-white/40 uppercase tracking-wider px-4 mb-1">
                            Main Menu
                        </p>
                    )}
                    <div className="space-y-0.5">
                        {visibleNavItems.map((item) => (
                            <SidebarNavItem
                                key={item.routeName}
                                item={item}
                                collapsed={collapsed}
                                onClick={closeMobile}
                            />
                        ))}
                    </div>

                    <div className="mx-4 my-3 border-t border-white/10" />

                    {!collapsed && (
                        <p className="text-[10px] font-semibold text-white/40 uppercase tracking-wider px-4 mb-1">
                            Account
                        </p>
                    )}
                    <div className="space-y-0.5">
                        {accountNavItems.map((item) => (
                            <SidebarNavItem
                                key={item.routeName}
                                item={item}
                                collapsed={collapsed}
                                onClick={closeMobile}
                            />
                        ))}
                    </div>
                </nav>

                {/* Collapse toggle */}
                <div className="p-2 border-t border-white/10 flex-shrink-0">
                    <button
                        type="button"
                        onClick={toggleCollapsed}
                        aria-label={collapsed ? 'Expand sidebar' : 'Collapse sidebar'}
                        className="sidebar-nav-item w-full justify-center focus:ring-2 focus:ring-accent focus:ring-offset-primary focus:outline-none"
                    >
                        {collapsed ? (
                            <ChevronRightIcon aria-hidden className="w-4 h-4" />
                        ) : (
                            <>
                                <ChevronLeftIcon aria-hidden className="w-4 h-4" />
                                <span>Collapse</span>
                            </>
                        )}
                    </button>
                </div>
            </aside>

            {/* Main wrapper */}
            <div
                className={`flex flex-col min-h-screen transition-all duration-300 ${
                    collapsed
                        ? 'lg:ml-[var(--sidebar-collapsed-width)]'
                        : 'lg:ml-[var(--sidebar-width)]'
                } ml-0`}
            >
                {/* Topbar */}
                <header
                    className="h-16 sticky top-0 z-30 bg-white border-b border-gray-200 flex items-center px-4 gap-4 flex-shrink-0"
                    style={{ boxShadow: 'var(--shadow-topbar)' }}
                >
                    {/* Mobile hamburger */}
                    <button
                        type="button"
                        className="lg:hidden p-2 rounded-lg text-gray-400 hover:text-gray-700 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-primary"
                        onClick={() => setMobileOpen((v) => !v)}
                        aria-label={mobileOpen ? 'Close navigation' : 'Open navigation'}
                    >
                        {mobileOpen ? (
                            <XMarkIcon aria-hidden className="w-5 h-5" />
                        ) : (
                            <Bars3Icon aria-hidden className="w-5 h-5" />
                        )}
                    </button>

                    <div className="flex-1" />

                    {/* Right controls */}
                    <div className="flex items-center gap-2">
                        <button
                            type="button"
                            aria-label="Search"
                            className="p-2 rounded-lg text-gray-400 hover:text-gray-700 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-primary"
                        >
                            <MagnifyingGlassIcon aria-hidden className="w-5 h-5" />
                        </button>

                        <button
                            type="button"
                            aria-label="Notifications"
                            className="p-2 rounded-lg text-gray-400 hover:text-gray-700 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-primary"
                        >
                            <BellIcon aria-hidden className="w-5 h-5" />
                        </button>

                        <Dropdown>
                            <Dropdown.Trigger>
                                <button
                                    type="button"
                                    className="inline-flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-primary"
                                    aria-label="User menu"
                                >
                                    <span
                                        className="w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold text-white"
                                        style={{ backgroundColor: 'var(--color-primary)' }}
                                    >
                                        {auth.user?.name.charAt(0).toUpperCase()}
                                    </span>
                                    <span className="hidden sm:block max-w-[120px] truncate">
                                        {auth.user?.name}
                                    </span>
                                    <svg
                                        className="w-4 h-4 text-gray-400"
                                        viewBox="0 0 20 20"
                                        fill="currentColor"
                                        aria-hidden="true"
                                    >
                                        <path
                                            fillRule="evenodd"
                                            d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                            clipRule="evenodd"
                                        />
                                    </svg>
                                </button>
                            </Dropdown.Trigger>
                            <Dropdown.Content width="56">
                                <div className="px-4 py-2 border-b border-gray-100">
                                    <p className="text-xs text-gray-500">Signed in as</p>
                                    <p className="text-sm font-semibold text-gray-800 truncate">
                                        {auth.user?.email}
                                    </p>
                                </div>
                                <Dropdown.Link href={route('profile.edit')}>Profile</Dropdown.Link>
                                <Dropdown.Link href={route('logout')} method="post" as="button">
                                    Log Out
                                </Dropdown.Link>
                            </Dropdown.Content>
                        </Dropdown>
                    </div>
                </header>

                {/* Page content */}
                <main className="flex-1 p-6">
                    {header && <div className="mb-6">{header}</div>}
                    {children}
                </main>
            </div>

            <FlashNotification />
        </div>
    );
}
