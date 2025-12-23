<script setup lang="ts">
import { ref, computed } from 'vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import { Link, usePage } from '@inertiajs/vue3';
import ThemeToggle from '@/Components/ThemeToggle.vue';
import DarkInput from '@/Components/DarkInput.vue';
import Notification from '@/Components/Notification.vue';
import { useNotificationStore } from '@/stores/notification';
import { watch } from 'vue';

const showingNavigationDropdown = ref(false);
const sidebarOpen = ref(false);
const searchQuery = ref('');
const forfaitingExpanded = ref(true);

const page = usePage();
const user = computed(() => (page.props as any).auth?.user);
const supplier = computed(() => (page.props as any).auth?.supplier);

const isAdmin = computed(() => {
    const userRoles = (user.value as any)?.roles || [];
    return userRoles.some((r: any) => r.name === 'Admin');
});

const isSupplier = computed(() => {
    const userRoles = (user.value as any)?.roles || [];
    return userRoles.some((r: any) => r.name === 'Supplier');
});


interface Investment {
  id: number;
  name: string;
  amount: number;
  currency: string;
  date: string;
  status: string;
  investor_id?: number;
  transaction_id?: number;
  notes?: string;
  transaction?: {
    transaction_number: string;
  };
}

interface Props {
  investments: {
    data: Investment[];
    links: any[];
    total: number;
  };
  investors: Array<{id: number, name: string}>;
  transactions: Array<{id: number, transaction_number: string}>;
}

interface NavItem {
    name: string;
    href: string;
    icon: string;
    section: 'main' | 'bottom' | 'admin' | 'forfaiting';
    isUrl?: boolean;
    badge?: number | string;
    dot?: boolean;
}

const navigation: NavItem[] = [
    // Main navigation - Dashboard link will be set dynamically based on role
    { name: 'Dashboard', href: '', icon: '📊', section: 'main' },
    { name: 'Invoices', href: 'invoices.index', icon: '🧾', section: 'main' },
    { name: 'KYC', href: 'onboarding.kyc', icon: '🪪', section: 'main' },
    { name: 'Chat', href: 'chat.index', icon: '💬', section: 'main' },
    { name: 'Agreements', href: 'agreements.index', icon: '📜', section: 'main' },


    // Admin routes - organized as shown in images
    { name: 'Customer Dashboard', href: 'admin.buyers', icon: '👔', section: 'admin' },
    { name: 'Suppliers', href: 'admin.suppliers.index', icon: '🏪', section: 'admin' },
    { name: 'KYB Queue', href: 'admin.kyb.queue', icon: '🪪', section: 'admin', dot: true },
    { name: 'Invoice Review', href: 'admin.invoice-review', icon: '🔍', section: 'admin', dot: true },
    { name: 'Collections', href: 'admin.collections', icon: '💰', section: 'admin', dot: true },
    { name: 'CMS', href: 'admin.cms', icon: '⭐', section: 'admin' },
    { name: 'KYB Checklist', href: 'admin.kyb.checklist', icon: '✅', section: 'admin' },
    { name: 'Pricing Rules', href: 'admin.pricing.rules', icon: '📊', section: 'admin' },
    { name: 'Leads', href: 'admin.leads', icon: '🧲', section: 'admin', dot: true },
    { name: 'Doc Requests', href: 'admin.doc_requests', icon: '📥', section: 'admin', dot: true },
    { name: 'Agreement Templates', href: 'admin.agreements.templates', icon: '📄', section: 'admin' },
    { name: 'Admin Agreements', href: 'admin.agreements', icon: '📜', section: 'admin' },
    { name: 'Banking', href: 'bank.index', icon: '🏦', section: 'admin' },
    { name: 'Funding Logs', href: 'admin.funding-logs', icon: '💵', section: 'admin' },
    { name: 'Audit Log', href: 'admin.audit-log', icon: '📋', section: 'admin' },

    // Forfaiting routes - grouped separately
    { name: 'Dashboard', href: 'forfaiting.dashboard', icon: '📈', section: 'forfaiting' },
    { name: 'Investments', href: 'forfaiting.investments.index', icon: '💼', section: 'forfaiting' },
    { name: 'Transactions', href: 'forfaiting.transactions.index', icon: '💳', section: 'forfaiting' },
    { name: 'Profit Allocations', href: 'forfaiting.profit-allocations.index', icon: '💰', section: 'forfaiting' },
    { name: 'Expenses', href: 'forfaiting.expenses.index', icon: '💸', section: 'forfaiting' },
    { name: 'Customers', href: 'forfaiting.customers.index', icon: '👥', section: 'forfaiting' },
    { name: 'Investors', href: 'forfaiting.investors.index', icon: '🤝', section: 'forfaiting' },
    { name: 'Analytics', href: 'forfaiting.analytics.index', icon: '📊', section: 'forfaiting' },
    { name: 'Contact Requests', href: 'forfaiting.contact-requests.index', icon: '📧', section: 'forfaiting' },
    { name: 'Notifications', href: 'forfaiting.notifications.index', icon: '🔔', section: 'forfaiting' },
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
    // Map navigation items and adjust routes for admin users
    return navigation.filter(n => n.section === 'admin').map(item => {
        // Admin users should use admin.bank instead of bank.index
        if (item.href === 'bank.index') {
            return { ...item, href: 'admin.bank' };
        }
        return item;
    });
});
const forfaitingNav = computed(() => {
    // Only show forfaiting nav if user is admin
    if (!isAdmin.value) return [];
    return navigation.filter(n => n.section === 'forfaiting');
});

const notificationStore = useNotificationStore();

// Watch for Inertia errors
watch(() => page.props.errors, (errors: any) => {
    if (errors && Object.keys(errors).length > 0) {
        Object.keys(errors).forEach(key => {
            notificationStore.error(errors[key]);
        });
    }
}, { deep: true, immediate: true });

// Watch for flash messages
watch(() => (page.props as any).flash, (flash: any) => {
    if (flash?.success) notificationStore.success(flash.success);
    if (flash?.error) notificationStore.error(flash.error);
    if (flash?.warning) notificationStore.warning(flash.warning);
}, { deep: true, immediate: true });

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
        <!-- Global Notifications -->
        <Notification />
        <!-- Sidebar -->
        <div class="fixed inset-y-0 left-0 z-50 w-[275px] bg-gradient-to-br from-slate-950 via-slate-900 to-slate-950 border-r border-dark-border transform transition-transform duration-300 ease-in-out lg:translate-x-0"
            :class="[sidebarOpen ? 'translate-x-0' : '-translate-x-full']">
            <div class="flex h-full flex-col">
                <!-- Logo -->
                <div class="flex h-16 shrink-0 items-center px-6 border-b border-dark-border">
                    <Link :href="isAdmin ? route('admin.dashboard') : route('supplier.dashboard')"
                        class="flex items-center gap-2">
                        <div class="w-6 h-6 rounded bg-purple-accent flex items-center justify-center">
                            <span class="text-white text-xs font-bold">+</span>
                        </div>
                        <span class="text-lg font-semibold text-dark-text-primary">InvoiceApp</span>
                    </Link>
                </div>

                <!-- Navigation -->
                <nav class="flex-1 px-6 py-6 space-y-6 overflow-y-auto custom-scrollbar">
                    <!-- Main Menu -->
                    <div>
                        <div class="mb-4">
                            <h3 class="text-xs font-semibold text-dark-text-muted uppercase tracking-wider">Main</h3>
                        </div>
                        <div class="space-y-1">
                            <Link v-for="item in mainNav" :key="item.name"
                                :href="item.isUrl ? item.href : (route().has(item.href) ? route(item.href) : '#')"
                                :class="[
                                    currentRoute(item.href)
                                        ? 'bg-purple-accent/20 text-purple-accent border-l-2 border-purple-accent'
                                        : 'text-dark-text-secondary hover:bg-dark-tertiary hover:text-dark-text-primary',
                                    'group flex items-center justify-between px-3 py-2 text-sm font-medium rounded-lg transition-all duration-200'
                                ]">
                                <div class="flex items-center">
                                    <span class="mr-3 text-base">{{ item.icon }}</span>
                                    {{ item.name }}
                                </div>
                                <span v-if="item.badge"
                                    class="ml-2 rounded-full bg-purple-accent px-2 py-0.5 text-xs font-semibold text-white">{{
                                        item.badge }}</span>
                                <span v-else-if="item.dot" class="ml-2 h-2 w-2 rounded-full bg-purple-accent"></span>
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
                            <Link v-for="item in adminNav" :key="item.name"
                                :href="item.isUrl ? item.href : (route().has(item.href) ? route(item.href) : '#')"
                                :class="[
                                    currentRoute(item.href)
                                        ? 'bg-purple-accent/20 text-purple-accent border-l-2 border-purple-accent'
                                        : 'text-dark-text-secondary hover:bg-dark-tertiary hover:text-dark-text-primary',
                                    'group flex items-center justify-between px-3 py-2 text-sm font-medium rounded-lg transition-all duration-200'
                                ]">
                                <div class="flex items-center">
                                    <span class="mr-3 text-base">{{ item.icon }}</span>
                                    {{ item.name }}
                                </div>
                                <span v-if="item.badge"
                                    class="ml-2 rounded-full bg-purple-accent px-2 py-0.5 text-xs font-semibold text-white">{{
                                        item.badge }}</span>
                                <span v-else-if="item.dot" class="ml-2 h-2 w-2 rounded-full bg-purple-accent"></span>
                            </Link>
                        </div>
                    </div>

                    <!-- Forfaiting Menu (if admin) -->
                    <div v-if="forfaitingNav.length > 0">
                        <div class="border-t border-dark-border my-6"></div>
                        <button @click="forfaitingExpanded = !forfaitingExpanded"
                            class="mb-4 flex w-full items-center justify-between text-xs font-semibold text-dark-text-muted uppercase tracking-wider hover:text-dark-text-primary transition-colors">
                            <span>Forfaiting</span>
                            <svg class="h-4 w-4 transition-transform" :class="{ 'rotate-180': forfaitingExpanded }"
                                fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>
                        <div v-show="forfaitingExpanded" class="space-y-1">
                            <Link v-for="item in forfaitingNav" :key="item.name"
                                :href="item.isUrl ? item.href : (route().has(item.href) ? route(item.href) : '#')"
                                :class="[
                                    currentRoute(item.href)
                                        ? 'bg-purple-accent/20 text-purple-accent border-l-2 border-purple-accent'
                                        : 'text-dark-text-secondary hover:bg-dark-tertiary hover:text-dark-text-primary',
                                    'group flex items-center justify-between px-3 py-2 text-sm font-medium rounded-lg transition-all duration-200'
                                ]">
                                <div class="flex items-center">
                                    <span class="mr-3 text-base">{{ item.icon }}</span>
                                    {{ item.name }}
                                </div>
                                <span v-if="item.badge"
                                    class="ml-2 rounded-full bg-purple-accent px-2 py-0.5 text-xs font-semibold text-white">{{
                                        item.badge }}</span>
                                <span v-else-if="item.dot" class="ml-2 h-2 w-2 rounded-full bg-purple-accent"></span>
                            </Link>
                        </div>
                    </div>
                </nav>


            </div>
        </div>

        <!-- Mobile sidebar overlay -->
        <div v-if="sidebarOpen" class="fixed inset-0 z-40 bg-black/50 lg:hidden" @click="sidebarOpen = false"></div>

        <!-- Main content -->
        <div class="lg:pl-[275px] min-h-screen bg-gradient-to-br from-slate-950 via-slate-900 to-slate-950">
            <!-- Top Header -->
            <!-- bg-slate-900/50 border-b border-slate-700/50 backdrop-blur-sm sticky top-0 z-50 -->

            <div
                class="sticky top-0 z-40 flex h-16 shrink-0 items-center gap-x-2 sm:gap-x-4 border-b border-dark-border bg-slate-900/50 border-b border-slate-700/50 backdrop-blur-sm px-4 sm:gap-x-6 sm:px-6 lg:px-8">
                <button type="button" class="-m-2.5 p-2.5 text-dark-text-secondary lg:hidden"
                    @click="sidebarOpen = true">
                    <span class="sr-only">Open sidebar</span>
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                    </svg>
                </button>

                <div class="flex flex-1 gap-x-2 sm:gap-x-4 self-stretch lg:gap-x-6">
                    <!-- Breadcrumb -->
                    <div class="flex flex-1 items-center">
                    </div>

                    <!-- Actions Container -->
                    <div class="flex items-center gap-x-2 sm:gap-x-4">


                        <!-- Avatar -->
                        <div class="relative">
                            <Dropdown align="right" width="48">
                                <template #trigger>
                                    <button type="button" class="flex items-center gap-2">
                                        <div
                                            class="h-8 w-8 rounded-full bg-purple-accent flex items-center justify-center ring-2 ring-purple-accent/50">
                                            <span class="text-sm font-medium text-white">{{ user?.name?.charAt(0) || 'U'
                                                }}</span>
                                        </div>
                                    </button>
                                </template>

                                <template #content>
                                    <DropdownLink :href="route('profile.edit')">Profile</DropdownLink>
                                    <DropdownLink :href="route('logout')" method="post" as="button">Log Out
                                    </DropdownLink>
                                </template>
                            </Dropdown>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Page Content -->
            <main class="py-6 ">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <slot />
                </div>
            </main>
        </div>

    </div>
</template>
