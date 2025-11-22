<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, usePage } from '@inertiajs/vue3';
import KpiCard from '@/Components/Dashboard/KpiCard.vue';
import RevenueChart from '@/Components/Dashboard/RevenueChart.vue';
import OverviewList from '@/Components/Dashboard/OverviewList.vue';
import PaymentStatistics from '@/Components/Dashboard/PaymentStatistics.vue';
import { useDashboardStore } from '@/stores/dashboard';
import { onMounted, computed, ref, watch } from 'vue';

// Store and state management
const store = useDashboardStore();
const page = usePage();
const t = (page.props as any)?.t as Record<string, string> | undefined;

// Date range filters
const from = ref<string>('');
const to = ref<string>('');

// Loading states
const isLoadingMetrics = ref(false);
const isLoadingWidgets = ref(false);
const isLoadingOverview = ref(false);

// Data refs
const paymentStats = ref<{ total: number; paid: number; partiallyPaid: number; overdue: number } | null>(null);
const overviewItems = ref<Array<{ title: string; value: number; icon: string; status: string }>>([]);
const aging = ref<{ current: number; d1_30: number; d31_60: number; d60p: number } | null>(null);
const topSuppliers = ref<Array<{ supplier_id: number; total: number }>>([]);

// Format currency helper
const formatCurrency = (n: number) => 
    new Intl.NumberFormat(undefined, { style: 'currency', currency: 'USD', maximumFractionDigits: 0 }).format(n);

// Format number helper
const formatNumber = (n: number) => new Intl.NumberFormat().format(n);

// Fetch main metrics
const fetchMetrics = async () => {
    isLoadingMetrics.value = true;
    try {
        const qs = new URLSearchParams();
        if (from.value) qs.set('from', from.value);
        if (to.value) qs.set('to', to.value);
        await store.fetchMetrics(qs.toString());
    } finally {
        isLoadingMetrics.value = false;
    }
};

// Fetch payment statistics
const fetchPaymentStats = async () => {
    try {
        const response = await fetch('/api/v1/dashboard/payment-stats', { credentials: 'include' });
        if (response.ok) {
            paymentStats.value = await response.json();
        }
    } catch (error) {
        console.error('Failed to fetch payment stats:', error);
    }
};

// Fetch overview items
const fetchOverview = async () => {
    isLoadingOverview.value = true;
    try {
        const response = await fetch('/api/v1/dashboard/overview', { credentials: 'include' });
        if (response.ok) {
            const data = await response.json();
            overviewItems.value = data.items || [];
        }
    } catch (error) {
        console.error('Failed to fetch overview:', error);
    } finally {
        isLoadingOverview.value = false;
    }
};

// Fetch admin reporting widgets
const fetchAdminWidgets = async () => {
    isLoadingWidgets.value = true;
    try {
        const [agingRes, suppliersRes] = await Promise.all([
            fetch('/api/v1/admin/reporting/aging', { credentials: 'include' }),
            fetch('/api/v1/admin/reporting/top-suppliers', { credentials: 'include' }),
        ]);
        
        if (agingRes.ok) {
            aging.value = await agingRes.json();
        }
        
        if (suppliersRes.ok) {
            const js = await suppliersRes.json();
            topSuppliers.value = js?.data ?? [];
        }
    } catch (error) {
        console.error('Failed to fetch admin widgets:', error);
    } finally {
        isLoadingWidgets.value = false;
    }
};

// Normalize chart series to expected array shape
const seriesArr = computed(() => {
    const s: any = (store as any).series;
    if (Array.isArray(s)) return s;
    if (s && typeof s === 'object' && 'fundedLast30' in s && 'repaidLast30' in s) {
        return [{ date: 'Last 30d', funded: s.fundedLast30, repaid: s.repaidLast30 }];
    }
    return [] as Array<{ date: string; funded: number; repaid: number }>;
});

// Compute KPIs
const kpis = computed(() => {
    const k = store.kpis;
    
    const totalFunded = k?.totalFunded || 0;
    const totalRepaid = k?.totalRepaid || 0;
    const outstanding = k?.outstanding || 0;
    const overdue = k?.overdue || 0;
    
    return [
        { 
            title: t?.total_funded || 'Total Funded', 
            value: formatCurrency(totalFunded), 
            icon: '💸', 
            color: 'blue' as const, 
            trend: 'up' as const, 
            delta: '+12.5%' 
        },
        { 
            title: t?.total_repaid || 'Total Repaid', 
            value: formatCurrency(totalRepaid), 
            icon: '🏦', 
            color: 'green' as const, 
            trend: 'up' as const, 
            delta: '+8.2%' 
        },
        { 
            title: t?.outstanding || 'Outstanding', 
            value: formatCurrency(outstanding), 
            icon: '📉', 
            color: 'yellow' as const, 
            trend: 'neutral' as const, 
            delta: '0.0%' 
        },
        { 
            title: t?.overdue || 'Overdue', 
            value: formatCurrency(overdue), 
            icon: '⏰', 
            color: 'red' as const, 
            trend: 'down' as const, 
            delta: '-5.1%' 
        },
    ];
});

// Get greeting based on time of day
const greeting = computed(() => {
    const hour = new Date().getHours();
    if (hour < 12) return 'Good morning';
    if (hour < 17) return 'Good afternoon';
    return 'Good evening';
});

// Format date
const currentDate = computed(() => new Date().toLocaleDateString());

// Format time
const currentTime = computed(() => 
    new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
);

// Watch date range changes
watch([from, to], () => {
    fetchMetrics();
});

// Initialize data on mount
onMounted(() => {
    fetchMetrics();
    fetchPaymentStats();
    fetchOverview();
    fetchAdminWidgets();
});
</script>

<template>
    <Head :title="t?.dashboard || 'Admin Dashboard'" />

    <AuthenticatedLayout>
        <div class="space-y-6">
            <!-- Page Title -->
            <div class="mb-6">
                <h1 class="text-2xl font-bold text-dark-text-primary">Admin Dashboard</h1>
                <p class="mt-1 text-sm text-dark-text-secondary">System overview and analytics</p>
            </div>

            <!-- Greeting & Date Time Container -->
            <div class="card">
                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                    <div>
                        <h2 class="text-xl font-semibold text-dark-text-primary">{{ greeting }}, Admin!</h2>
                        <p class="text-sm text-dark-text-secondary mt-1">Here's your system overview for today</p>
                    </div>
                    <div class="flex items-center gap-4">
                        <div class="flex items-center gap-2 text-sm text-dark-text-secondary">
                            <svg width="13" height="13" fill="none" viewBox="0 0 13 13" class="text-dark-text-muted">
                                <path stroke="currentColor" stroke-width="1.5" d="M2 3h9v8H2zM3.5 1v4M9.5 1v4"/>
                            </svg>
                            <span>{{ currentDate }}</span>
                        </div>
                        <div class="h-5 w-px bg-dark-border"></div>
                        <div class="flex items-center gap-2 text-sm text-dark-text-secondary">
                            <svg width="13" height="13" fill="none" viewBox="0 0 13 13" class="text-dark-text-muted">
                                <circle cx="6.5" cy="6.5" r="5" stroke="currentColor" stroke-width="1.5"/>
                                <path stroke="currentColor" stroke-width="1.5" d="M6.5 3v3.5l2.5 1.5"/>
                            </svg>
                            <span>{{ currentTime }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- KPIs -->
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
                <KpiCard
                    v-for="(k, i) in kpis"
                    :key="i"
                    :title="k.title"
                    :value="k.value"
                    :icon="k.icon"
                    :color="k.color"
                    :trend="k.trend"
                    :delta="k.delta"
                />
            </div>

            <!-- Loading state for KPIs -->
            <div v-if="isLoadingMetrics && kpis.length === 0" class="card text-center py-12">
                <p class="text-dark-text-secondary">Loading dashboard data...</p>
            </div>

            <!-- Charts and Overview -->
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                <!-- Payment Statistics -->
                <PaymentStatistics
                    v-if="paymentStats"
                    :total="paymentStats.total"
                    :paid="paymentStats.paid"
                    :partially-paid="paymentStats.partiallyPaid"
                    :overdue="paymentStats.overdue"
                />
                <div v-else class="card flex items-center justify-center min-h-[200px]">
                    <p class="text-dark-text-secondary">Loading payment statistics...</p>
                </div>

                <!-- Revenue Chart -->
                <div class="lg:col-span-2">
                    <div class="mb-4 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <label class="text-sm font-medium text-dark-text-secondary">Date Range:</label>
                            <div class="flex items-center gap-2">
                                <input
                                    type="date"
                                    v-model="from"
                                    class="input-dark !py-2 !px-3 text-sm"
                                />
                                <span class="text-sm text-dark-text-muted">to</span>
                                <input
                                    type="date"
                                    v-model="to"
                                    class="input-dark !py-2 !px-3 text-sm"
                                />
                            </div>
                        </div>
                        <button
                            @click="from = ''; to = ''; store.fetchMetrics()"
                            class="btn-secondary text-sm py-2 px-4"
                        >
                            Clear
                        </button>
                    </div>
                    <RevenueChart :title="t?.revenue || 'Revenue Overview'" :series="seriesArr" />
                </div>
            </div>

            <!-- Overview List -->
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                <OverviewList
                    v-if="overviewItems.length > 0"
                    :title="t?.overview_title || 'Quick Overview'"
                    :items="overviewItems"
                />
                <div v-else-if="isLoadingOverview" class="card flex items-center justify-center min-h-[150px]">
                    <p class="text-dark-text-secondary">Loading overview...</p>
                </div>
                <OverviewList
                    v-else
                    :title="t?.overview_title || 'Quick Overview'"
                    :items="[
                        { title: t?.overview_new_invoices || 'New invoices today', value: 0, icon: '🧾', status: 'success' },
                        { title: t?.overview_kyb_pending || 'KYB pending', value: 0, icon: '🪪', status: 'warning' },
                        { title: t?.overview_funding_approvals || 'Funding approvals', value: 0, icon: '✅', status: 'info' },
                    ]"
                />
            </div>

            <!-- Admin reporting widgets -->
            <div v-if="aging || (topSuppliers && topSuppliers.length)" class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                <div class="card lg:col-span-1">
                    <div class="mb-4 text-base font-semibold text-dark-text-primary">Repayment Aging</div>
                    <div v-if="aging" class="space-y-2 text-sm">
                        <div class="flex justify-between text-dark-text-primary">
                            <span class="text-dark-text-secondary">Current</span>
                            <span>{{ formatNumber(aging.current || 0) }}</span>
                        </div>
                        <div class="flex justify-between text-dark-text-primary">
                            <span class="text-dark-text-secondary">1-30</span>
                            <span>{{ formatNumber(aging.d1_30 || 0) }}</span>
                        </div>
                        <div class="flex justify-between text-dark-text-primary">
                            <span class="text-dark-text-secondary">31-60</span>
                            <span>{{ formatNumber(aging.d31_60 || 0) }}</span>
                        </div>
                        <div class="flex justify-between text-dark-text-primary">
                            <span class="text-dark-text-secondary">60+</span>
                            <span>{{ formatNumber(aging.d60p || 0) }}</span>
                        </div>
                    </div>
                    <div v-else-if="isLoadingWidgets" class="text-sm text-dark-text-muted">Loading...</div>
                    <div v-else class="text-sm text-dark-text-muted">No data</div>
                </div>
                <div class="card lg:col-span-2">
                    <div class="mb-4 text-base font-semibold text-dark-text-primary">Top Suppliers by Funded</div>
                    <div class="overflow-x-auto">
                        <table class="table-dark">
                            <thead>
                                <tr>
                                    <th class="text-dark-text-secondary">Supplier ID</th>
                                    <th class="text-dark-text-secondary">Total Funded</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="row in topSuppliers" :key="row.supplier_id">
                                    <td>{{ row.supplier_id }}</td>
                                    <td>{{ formatCurrency(row.total) }}</td>
                                </tr>
                                <tr v-if="isLoadingWidgets">
                                    <td colspan="2" class="text-center text-dark-text-muted py-4">Loading...</td>
                                </tr>
                                <tr v-else-if="!topSuppliers || topSuppliers.length===0">
                                    <td colspan="2" class="text-center text-dark-text-muted py-4">No data</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

