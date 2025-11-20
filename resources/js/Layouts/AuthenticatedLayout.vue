<script setup lang="ts">
import { ref, computed } from 'vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import { Link, usePage } from '@inertiajs/vue3';
import ThemeToggle from '@/Components/ThemeToggle.vue';
import DarkInput from '@/Components/DarkInput.vue';

const showingNavigationDropdown = ref(false);
const sidebarOpen = ref(false);
const searchQuery = ref('');

const page = usePage();
const user = computed(() => (page.props as any).auth?.user);
const isAdmin = computed(() => {
    const userRoles = (user.value as any)?.roles || [];
    return userRoles.some((r: any) => r.name === 'Admin');
});
const isSupplier = computed(() => {
    const userRoles = (user.value as any)?.roles || [];
    return userRoles.some((r: any) => r.name === 'Supplier');
});

interface NavItem {
    name: string;
    href: string;
    icon: string;
    section: 'main' | 'bottom' | 'admin';
    isUrl?: boolean;
}

const navigation: NavItem[] = [
    // Main navigation - Dashboard link will be set dynamically based on role
    { name: 'Dashboard', href: '', icon: '📊', section: 'main' },
    { name: 'Invoices', href: 'invoices.index', icon: '🧾', section: 'main' },
    { name: 'Customers', href: 'customers.index', icon: '👥', section: 'main' },
    { name: 'Reports', href: 'reports.index', icon: '📈', section: 'main' },

    // Other navigation (bottom)
    { name: 'Settings', href: 'profile.edit', icon: '⚙️', section: 'bottom' },
    { name: 'Help', href: '#', icon: '❓', section: 'bottom' },

    // Admin routes - organized as shown in images
    { name: 'KYC/KYB Form', href: 'onboarding.kyc', icon: '📋', section: 'admin' },
    { name: 'KYC Status', href: 'supplier.kyc.status', icon: '📈', section: 'admin' },
    { name: 'Customer Dashboard', href: 'admin.buyers', icon: '👔', section: 'admin' },
    { name: 'Supplier', href: 'admin.buyers', icon: '🏢', section: 'admin' },
    { name: 'KYB Queue', href: 'admin.kyb.queue', icon: '🪪', section: 'admin' },
    { name: 'Collections', href: 'admin.collections', icon: '💰', section: 'admin' },
    { name: 'CMS', href: 'admin.cms', icon: '⭐', section: 'admin' },
    { name: 'KYB Checklist', href: 'admin.kyb.checklist', icon: '✅', section: 'admin' },
    { name: 'Pricing Rules', href: 'admin.pricing.rules', icon: '📊', section: 'admin' },
    { name: 'Leads', href: 'admin.leads', icon: '🧲', section: 'admin' },
    { name: 'Doc Requests', href: 'admin.doc_requests', icon: '📥', section: 'admin' },
    { name: 'Agreement Templates', href: 'admin.agreements.templates', icon: '📄', section: 'admin' },
    { name: 'Banking', href: 'bank.index', icon: '🏦', section: 'admin' },
    { name: 'Funding Logs', href: 'admin.funding-logs', icon: '💵', section: 'admin' },
    { name: 'Audit Log', href: 'admin.audit-log', icon: '📋', section: 'admin' },
    { name: 'Agreements', href: 'agreements.index', icon: '📜', section: 'admin' },
];

const mainNav = computed(() => {
    const nav = navigation.filter(n => n.section === 'main').map(item => {
        // Set dashboard href based on user role
        if (item.name === 'Dashboard') {
            if (isAdmin.value) {
                item.href = 'admin.dashboard';
            } else if (isSupplier.value) {
                item.href = 'supplier.dashboard';
            } else {
                item.href = 'supplier.dashboard'; // Default fallback
            }
        }
        return item;
    });
    return nav;
});
const bottomNav = computed(() => navigation.filter(n => n.section === 'bottom'));
const adminNav = computed(() => {
    // Only show admin nav if user is admin
    if (!isAdmin.value) return [];
    return navigation.filter(n => n.section === 'admin');
});

function currentRoute(routeName: string) {
    try {
        if (routeName.startsWith('/')) {
            return window.location.pathname === routeName;
        }
        return route().current(routeName);
    } catch {
        return false;
    }
}
</script>

<template>
    <div class="min-h-screen bg-dark-primary">
        <!-- Sidebar -->
        <div
            class="fixed inset-y-0 left-0 z-50 w-[275px] bg-dark-secondary border-r border-dark-border transform transition-transform duration-300 ease-in-out lg:translate-x-0"
            :class="[sidebarOpen ? 'translate-x-0' : '-translate-x-full']"
        >
            <div class="flex h-full flex-col">
                <!-- Logo -->
                <div class="flex h-16 shrink-0 items-center px-6 border-b border-dark-border">
                    <Link :href="isAdmin ? route('admin.dashboard') : route('supplier.dashboard')" class="flex items-center gap-2">
                        <div class="w-6 h-6 rounded bg-purple-accent flex items-center justify-center">
                            <span class="text-white text-xs font-bold">+</span>
                        </div>
                        <span class="text-lg font-semibold text-dark-text-primary">InvoiceApp</span>
                    </Link>
                </div>

                <!-- Navigation -->
                <nav class="flex-1 px-6 py-6 space-y-6 overflow-y-auto">
                    <!-- Main Menu -->
                    <div>
                        <div class="mb-4">
                            <h3 class="text-xs font-semibold text-dark-text-muted uppercase tracking-wider">Main</h3>
                        </div>
                        <div class="space-y-1">
                            <Link
                                v-for="item in mainNav"
                                :key="item.name"
                                :href="item.isUrl ? item.href : (route().has(item.href) ? route(item.href) : '#')"
                                :class="[
                                    currentRoute(item.href)
                                        ? 'bg-purple-accent/20 text-purple-accent border-l-2 border-purple-accent'
                                        : 'text-dark-text-secondary hover:bg-dark-tertiary hover:text-dark-text-primary',
                                    'group flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-all duration-200'
                                ]"
                            >
                                <span class="mr-3 text-base">{{ item.icon }}</span>
                                {{ item.name }}
                            </Link>
                        </div>
                    </div>

                    <div class="border-t border-dark-border"></div>

                    <!-- Reports & Settings -->
                    <div>
                        <div class="mb-4">
                            <h3 class="text-xs font-semibold text-dark-text-muted uppercase tracking-wider">Other</h3>
                        </div>
                        <div class="space-y-1">
                            <Link
                                v-for="item in bottomNav"
                                :key="item.name"
                                :href="item.isUrl ? item.href : (route().has(item.href) ? route(item.href) : '#')"
                                :class="[
                                    currentRoute(item.href)
                                        ? 'bg-purple-accent/20 text-purple-accent border-l-2 border-purple-accent'
                                        : 'text-dark-text-secondary hover:bg-dark-tertiary hover:text-dark-text-primary',
                                    'group flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-all duration-200'
                                ]"
                            >
                                <span class="mr-3 text-base">{{ item.icon }}</span>
                                {{ item.name }}
                            </Link>
                        </div>
                    </div>

                    <!-- Admin Menu (if admin) -->
                    <div v-if="adminNav.length > 0">
                        <div class="border-t border-dark-border my-6"></div>
                        <div class="mb-4">
                            <h3 class="text-xs font-semibold text-dark-text-muted uppercase tracking-wider">Admin</h3>
                        </div>
                        <div class="space-y-1">
                            <Link
                                v-for="item in adminNav"
                                :key="item.name"
                                :href="item.isUrl ? item.href : (route().has(item.href) ? route(item.href) : '#')"
                                :class="[
                                    currentRoute(item.href)
                                        ? 'bg-purple-accent/20 text-purple-accent border-l-2 border-purple-accent'
                                        : 'text-dark-text-secondary hover:bg-dark-tertiary hover:text-dark-text-primary',
                                    'group flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-all duration-200'
                                ]"
                            >
                                <span class="mr-3 text-base">{{ item.icon }}</span>
                                {{ item.name }}
                            </Link>
                        </div>
                    </div>
                </nav>

                <!-- Sidebar Icons Container -->
                <div class="border-t border-dark-border p-4">
                    <div class="flex flex-col space-y-4">
                        <button class="p-2 text-dark-text-secondary hover:text-dark-text-primary hover:bg-dark-tertiary rounded-lg transition-colors">
                            <svg width="16" height="16" fill="none" viewBox="0 0 16 16">
                                <path stroke="currentColor" stroke-width="1.5" d="M8 1.333v2M8 12.667v2M2.667 8h-2M15.333 8h-2M4.673 4.673L3.333 3.333M12.327 12.327l1.34 1.34M4.673 11.327L3.333 12.667M12.327 3.673l1.34-1.34"/>
                            </svg>
                        </button>
                        <button class="p-2 text-dark-text-secondary hover:text-dark-text-primary hover:bg-dark-tertiary rounded-lg transition-colors">
                            <svg width="16" height="16" fill="none" viewBox="0 0 16 16">
                                <path stroke="currentColor" stroke-width="1.5" d="M8 2.667v10.666M2.667 8h10.666"/>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Mobile sidebar overlay -->
        <div v-if="sidebarOpen" class="fixed inset-0 z-40 bg-black/50 lg:hidden" @click="sidebarOpen = false"></div>

        <!-- Main content -->
        <div class="lg:pl-[275px] bg-dark-primary min-h-screen">
            <!-- Top Header -->
            <div class="sticky top-0 z-40 flex h-14 shrink-0 items-center gap-x-4 border-b border-dark-border bg-dark-secondary px-4 sm:gap-x-6 sm:px-6 lg:px-8">
                <button type="button" class="-m-2.5 p-2.5 text-dark-text-secondary lg:hidden" @click="sidebarOpen = true">
                    <span class="sr-only">Open sidebar</span>
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                    </svg>
                </button>

                <div class="flex flex-1 gap-x-4 self-stretch lg:gap-x-6">
                    <!-- Breadcrumb -->
                    <div class="flex flex-1 items-center">
                        <nav class="flex items-center space-x-2 text-sm">
                            <span class="text-dark-text-muted">Dashboard</span>
                        </nav>
                    </div>

                    <!-- Actions Container -->
                    <div class="flex items-center gap-x-4">
                        <!-- Search -->
                        <div class="relative w-[216px]">
                            <DarkInput
                                v-model="searchQuery"
                                placeholder="Search..."
                                icon="search"
                                class="!pr-10"
                            />
                        </div>

                        <!-- Icons Container -->
                        <div class="flex items-center gap-x-2">
                            <!-- Flag/Language -->
                            <button class="p-2 rounded-lg hover:bg-dark-tertiary transition-colors">
                                <svg width="16" height="16" fill="none" viewBox="0 0 16 16" class="text-dark-text-secondary">
                                    <path stroke="currentColor" stroke-width="1.5" d="M8 1.333L10 4l-2 2-2-2 2-2.667zM8 14.667L6 12l2-2 2 2-2 2.667z"/>
                                </svg>
                            </button>

                            <!-- Notifications -->
                            <button class="relative p-2 rounded-lg hover:bg-dark-tertiary transition-colors">
                                <svg width="16" height="16" fill="none" viewBox="0 0 16 16" class="text-dark-text-secondary">
                                    <path stroke="currentColor" stroke-width="1.5" d="M8 2.667A4 4 0 004 6.667v2.666a2.667 2.667 0 01-.533 1.6L2.4 12.8h11.2l-1.067-1.867A2.667 2.667 0 0112 9.333V6.667a4 4 0 00-4-4zM6 12.8v.8a2 2 0 104 0v-.8"/>
                                </svg>
                                <span class="absolute top-1 right-1 h-2 w-2 bg-red-500 rounded-full"></span>
                            </button>

                            <!-- Theme Toggle -->
                            <ThemeToggle />
                        </div>

                        <!-- Avatar -->
                        <div class="relative">
                            <Dropdown align="right" width="48">
                                <template #trigger>
                                    <button type="button" class="flex items-center gap-2">
                                        <div class="h-8 w-8 rounded-full bg-purple-accent flex items-center justify-center ring-2 ring-purple-accent/50">
                                            <span class="text-sm font-medium text-white">{{ user?.name?.charAt(0) || 'U' }}</span>
                                        </div>
                                    </button>
                                </template>

                                <template #content>
                                    <DropdownLink :href="route('profile.edit')">Profile</DropdownLink>
                                    <DropdownLink :href="route('logout')" method="post" as="button">Log Out</DropdownLink>
                                </template>
                            </Dropdown>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Page Content -->
            <main class="py-6 bg-dark-primary">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <slot />
                </div>
            </main>
        </div>
    </div>
</template>
