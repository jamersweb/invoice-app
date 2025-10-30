<script setup lang="ts">
import { ref } from 'vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import NavLink from '@/Components/NavLink.vue';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue';
import { Link } from '@inertiajs/vue3';
import AppFooter from '@/Components/AppFooter.vue';

const showingNavigationDropdown = ref(false);
const sidebarOpen = ref(false);

const navigation = [
  { name: 'Dashboard', href: 'dashboard', icon: '📊' },
  { name: 'KYC/KYB Form', href: 'onboarding.kyc', icon: '📋' },
  { name: 'KYC Status', href: 'supplier.kyc.status', icon: '📈' },
  { name: 'Customer Dashboard', href: 'customer.dashboard', icon: '🧑‍💼' },
  { name: 'Supplier', href: 'supplier.kyc.status', icon: '🏢' },
  { name: 'KYB Queue', href: 'admin.kyb.queue', icon: '🪪', admin: true },
  { name: 'Collections', href: 'admin.collections', icon: '💰', admin: true },
  { name: 'CMS', href: 'admin.cms', icon: '🧩', admin: true },
  { name: 'KYB Checklist', href: 'admin.kyb.checklist', icon: '✅', admin: true },
  { name: 'Pricing Rules', href: 'admin.pricing.rules', icon: '📈', admin: true },
  { name: 'Leads', href: 'admin.leads', icon: '🧲', admin: true },
  { name: 'Doc Requests', href: 'admin.doc_requests', icon: '📥', admin: true },
  { name: 'Agreement Templates', href: 'admin.agreements.templates', icon: '📄', admin: true },
  { name: 'Banking', href: 'admin.bank', icon: '🏦', admin: true },
  { name: 'Funding Logs', href: 'admin.funding-logs', icon: '💸', admin: true },
  { name: 'Audit Log', href: 'admin.audit-log', icon: '📋', admin: true },
  { name: 'Users', href: 'users.index', icon: '👤', admin: true },
  { name: 'Roles & Permissions', href: 'roles.index', icon: '🛡️', admin: true },
  { name: 'Agreements', href: 'agreements.index', icon: '📋' },
  { name: 'Reports', href: 'reports.index', icon: '📈' },
];
</script>

<template>
    <div class="min-h-screen bg-gray-50">
        <!-- Sidebar -->
        <div
            class="fixed inset-y-0 left-0 z-50 w-64 bg-white shadow-lg transform transition-transform duration-300 ease-in-out lg:translate-x-0 lg:static lg:inset-0"
            :class="[sidebarOpen ? 'translate-x-0' : '-translate-x-full']"
        >
            <div class="flex h-full flex-col">
                <!-- Logo -->
                <div class="flex h-16 shrink-0 items-center px-6 border-b border-gray-200">
                    <Link :href="route('dashboard')" class="flex items-center">
                        <ApplicationLogo class="h-8 w-auto fill-current text-indigo-600" />
                        <span class="ml-2 text-lg font-semibold text-gray-900">InvoiceApp</span>
                    </Link>
                </div>

                <!-- Navigation -->
                <nav class="flex-1 px-4 py-6 space-y-2">
                    <div v-for="item in navigation" :key="item.name">
                        <Link
                            v-if="!item.admin || route().has(item.href)"
                            :href="route().has(item.href) ? route(item.href) : '#'"
                            :class="[
                                route().current(item.href)
                                    ? 'bg-indigo-50 text-indigo-700 border-r-2 border-indigo-600'
                                    : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900',
                                'group flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-colors duration-200'
                            ]"
                        >
                            <span class="mr-3 text-lg">{{ item.icon }}</span>
                            {{ item.name }}
                        </Link>
                    </div>
                </nav>

                <!-- User section -->
                <div class="border-t border-gray-200 p-4">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="h-8 w-8 rounded-full bg-indigo-500 flex items-center justify-center">
                                <span class="text-sm font-medium text-white">{{ $page.props.auth.user.name.charAt(0) }}</span>
                            </div>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-gray-700">{{ $page.props.auth.user.name }}</p>
                            <p class="text-xs text-gray-500">{{ $page.props.auth.user.email }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Mobile sidebar overlay -->
        <div v-if="sidebarOpen" class="fixed inset-0 z-40 bg-gray-600 bg-opacity-75 lg:hidden" @click="sidebarOpen = false"></div>

        <!-- Main content -->
        <div class="lg:pl-64">
            <!-- Top navigation -->
            <div class="sticky top-0 z-40 flex h-16 shrink-0 items-center gap-x-4 border-b border-gray-200 bg-white px-4 shadow-sm sm:gap-x-6 sm:px-6 lg:px-8">
                <button type="button" class="-m-2.5 p-2.5 text-gray-700 lg:hidden" @click="sidebarOpen = true">
                    <span class="sr-only">Open sidebar</span>
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                    </svg>
                </button>

                <div class="flex flex-1 gap-x-4 self-stretch lg:gap-x-6">
                    <div class="flex flex-1"></div>
                    <div class="flex items-center gap-x-4 lg:gap-x-6">
                        <!-- Settings Dropdown -->
                        <div class="relative">
                            <Dropdown align="right" width="48">
                                <template #trigger>
                                    <button type="button" class="-m-1.5 flex items-center p-1.5">
                                        <span class="sr-only">Open user menu</span>
                                        <div class="h-8 w-8 rounded-full bg-indigo-500 flex items-center justify-center">
                                            <span class="text-sm font-medium text-white">{{ $page.props.auth.user.name.charAt(0) }}</span>
                                        </div>
                                        <span class="hidden lg:flex lg:items-center">
                                            <span class="ml-4 text-sm font-semibold leading-6 text-gray-900" aria-hidden="true">{{ $page.props.auth.user.name }}</span>
                                            <svg class="ml-2 h-5 w-5 text-gray-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 10.586l3.71-3.354a.75.75 0 111.02 1.1l-4.24 3.83a.75.75 0 01-1.02 0l-4.24-3.83a.75.75 0 01.02-1.1z" clip-rule="evenodd" />
                                            </svg>
                                        </span>
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

            <!-- Page Heading -->
            <header class="bg-white shadow-sm" v-if="$slots.header">
                <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                    <slot name="header" />
                </div>
            </header>

            <!-- Page Content -->
            <main class="py-6">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <slot />
                </div>
            </main>

            <AppFooter />
        </div>
    </div>
</template>
