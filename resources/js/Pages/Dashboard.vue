<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, usePage } from '@inertiajs/vue3';
import KpiCard from '@/Components/Dashboard/KpiCard.vue';
import RevenueChart from '@/Components/Dashboard/RevenueChart.vue';
import OverviewList from '@/Components/Dashboard/OverviewList.vue';
import PaymentStatistics from '@/Components/Dashboard/PaymentStatistics.vue';
import RecentActivity from '@/Components/Dashboard/RecentActivity.vue';
import { useDashboardStore } from '@/stores/dashboard';
import { onMounted, computed, ref, watch } from 'vue';

const store = useDashboardStore();
const from = ref<string>('');
const to = ref<string>('');
const currentTime = ref<Date>(new Date());
const recentActivity = ref<Array<any>>([]);
const loadingActivity = ref(false);

// Update time every minute
setInterval(() => {
    currentTime.value = new Date();
}, 60000);

// Dynamic greeting based on time of day
const greeting = computed(() => {
    const hour = currentTime.value.getHours();
    if (hour < 12) return 'Good morning';
    if (hour < 17) return 'Good afternoon';
    return 'Good evening';
});

// Format date and time
const formattedDate = computed(() => {
    return currentTime.value.toLocaleDateString(undefined, {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
});

const formattedTime = computed(() => {
    return currentTime.value.toLocaleTimeString(undefined, {
        hour: '2-digit',
        minute: '2-digit',
    });
});

// Fetch all dashboard data
async function fetchDashboardData() {
    await store.fetchMetrics();
    await store.fetchPaymentStats();
    await store.fetchOverview();
    await fetchRecentActivity();
}

async function fetchRecentActivity() {
    loadingActivity.value = true;
    try {
        const res = await fetch('/api/v1/dashboard/recent-activity', {
            headers: { 'Accept': 'application/json' },
            credentials: 'same-origin',
        });
        if (res.ok) {
            const data = await res.json();
            recentActivity.value = data.items || [];
        }
    } catch (e: any) {
        console.error('Failed to fetch recent activity:', e);
    } finally {
        loadingActivity.value = false;
    }
}

onMounted(() => {
    fetchDashboardData();
});

// Watch date range changes
watch([from, to], () => {
    const qs = new URLSearchParams();
    if (from.value) qs.set('from', from.value);
    if (to.value) qs.set('to', to.value);
    store.fetchMetrics(qs.toString());
});

const page = usePage();
const t = (page.props as any)?.t as Record<string, string> | undefined;

// Normalize chart series to expected array shape
const seriesArr = computed(() => {
    const s = store.series;
    if (Array.isArray(s)) return s;
    return [] as Array<{ date: string; funded: number; repaid: number }>;
});

// Admin reporting widgets (silent fail if 403)
const aging = ref<{ current: number; d1_30: number; d31_60: number; d60p: number } | null>(null);
const topSuppliers = ref<Array<{ supplier_id: number; total: number }>>([]);

async function fetchAdminWidgets() {
    try {
        const [a, s] = await Promise.all([
            fetch('/api/v1/admin/reporting/aging', { credentials: 'include' }),
            fetch('/api/v1/admin/reporting/top-suppliers', { credentials: 'include' }),
        ]);
        if (a.ok) aging.value = await a.json();
        if (s.ok) {
            const js = await s.json();
            topSuppliers.value = js?.data ?? [];
        }
    } catch { }
}

onMounted(fetchAdminWidgets);

// Calculate trend percentages (mock for now, can be enhanced with historical data)
// Calculate trend percentages
const calculateTrend = (current: number, previous: number = 0): { value: string, direction: 'up' | 'down' | 'neutral' } => {
    if (previous === 0) return { value: '0.0%', direction: 'neutral' };
    const change = ((current - previous) / previous) * 100;
    const sign = change >= 0 ? '+' : '';
    const direction = change > 0 ? 'up' : change < 0 ? 'down' : 'neutral';
    return {
        value: `${sign}${change.toFixed(1)}%`,
        direction
    };
};

const kpis = computed(() => {
    const k = store.kpis;
    const fmt = (n: number) => new Intl.NumberFormat(undefined, { style: 'currency', currency: 'USD', maximumFractionDigits: 0 }).format(n);

    // Use fallback values if store data isn't loaded yet
    const totalFunded = k?.totalFunded || 0;
    const totalRepaid = k?.totalRepaid || 0;
    const outstanding = k?.outstanding || 0;
    const overdue = k?.overdue || 0;

    // Calculate trends (simplified - in production, compare with previous period)
    const fundedTrend = calculateTrend(totalFunded, totalFunded * 0.9);
    const repaidTrend = calculateTrend(totalRepaid, totalRepaid * 0.92);
    const overdueTrend = overdue > 0 ? calculateTrend(overdue, overdue * 1.05) : { value: '0.0%', direction: 'neutral' as const };

    return [
        {
            title: t?.total_funded || 'Total Funded',
            value: fmt(totalFunded),
            icon: '💸',
            color: 'blue' as const,
            trend: fundedTrend.direction,
            delta: fundedTrend.value,
        },
        {
            title: t?.total_repaid || 'Total Repaid',
            value: fmt(totalRepaid),
            icon: '🏦',
            color: 'green' as const,
            trend: repaidTrend.direction,
            delta: repaidTrend.value,
        },
        {
            title: t?.outstanding || 'Outstanding',
            value: fmt(outstanding),
            icon: '📉',
            color: 'yellow' as const,
            trend: 'neutral' as const,
            delta: '0.0%',
        },
        {
            title: t?.overdue || 'Overdue',
            value: fmt(overdue),
            icon: '⏰',
            color: 'red' as const,
            trend: overdueTrend.direction,
            delta: overdueTrend.value,
            reverseColor: true,
        },
    ];
});

// Payment statistics from store
const paymentStats = computed(() => {
    const stats = store.paymentStats;
    if (!stats) {
        return {
            total: 0,
            paid: 0,
            partiallyPaid: 0,
            overdue: 0,
        };
    }
    return stats;
});

// Overview items from store
const overviewItems = computed(() => {
    return store.overview || [];
});

// Refresh all data
async function handleRefresh() {
    await fetchDashboardData();
}
</script>

<template>

    <Head :title="t?.dashboard || 'Dashboard'" />

    <AuthenticatedLayout>
        <div class="space-y-6">
            <!-- Page Title -->
            <div class="mb-6 flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-dark-text-primary">Dashboard</h1>
                    <p class="mt-1 text-sm text-dark-text-secondary">Welcome back! Here's what's happening with your
                        invoices.</p>
                </div>
                <button @click="handleRefresh" :disabled="store.loading"
                    class="btn-secondary flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed">
                    <svg class="h-4 w-4" :class="{ 'animate-spin': store.loading }" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    Refresh
                </button>
            </div>

            <!-- Error State -->
            <div v-if="store.error"
                class="rounded-xl border text-card-foreground shadow bg-slate-800/40 backdrop-blur-sm border-slate-700/50 p-8 group border-red-500/50 bg-red-500/10">
                <div class="flex items-center gap-3">
                    <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                            d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                            clip-rule="evenodd" />
                    </svg>
                    <div>
                        <p class="font-medium text-red-400">Error loading dashboard</p>
                        <p class="text-sm text-red-300">{{ store.error }}</p>
                    </div>
                </div>
            </div>

            <!-- Greeting & Date Time Container -->
            <div
                class="rounded-xl border text-card-foreground shadow bg-slate-800/40 backdrop-blur-sm border-slate-700/50 p-8 group">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 class="text-xl font-semibold text-dark-text-primary">{{ greeting }}!</h2>
                        <p class="text-sm text-dark-text-secondary mt-1">Here's your overview for {{ formattedDate }}
                        </p>
                    </div>
                    <div class="flex items-center gap-4">
                        <div class="flex items-center gap-2 text-sm text-dark-text-secondary">
                            <svg width="13" height="13" fill="none" viewBox="0 0 13 13" class="text-dark-text-muted">
                                <path stroke="currentColor" stroke-width="1.5" d="M2 3h9v8H2zM3.5 1v4M9.5 1v4" />
                            </svg>
                            <span>{{ currentTime.toLocaleDateString() }}</span>
                        </div>
                        <div class="h-5 w-px bg-dark-border"></div>
                        <div class="flex items-center gap-2 text-sm text-dark-text-secondary">
                            <svg width="13" height="13" fill="none" viewBox="0 0 13 13" class="text-dark-text-muted">
                                <circle cx="6.5" cy="6.5" r="5" stroke="currentColor" stroke-width="1.5" />
                                <path stroke="currentColor" stroke-width="1.5" d="M6.5 3v3.5l2.5 1.5" />
                            </svg>
                            <span>{{ formattedTime }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- KPIs -->
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
                <KpiCard v-for="(k, i) in kpis" :key="i" :title="k.title" :value="k.value" :icon="k.icon"
                    :color="k.color" :trend="k.trend" :delta="k.delta" :reverse-color="k.reverseColor" />
            </div>

            <!-- Loading State -->
            <div v-if="store.loading && !store.kpis"
                class="rounded-xl border text-card-foreground shadow bg-slate-800/40 backdrop-blur-sm border-slate-700/50 p-8 group text-center py-12">
                <div
                    class="inline-block h-8 w-8 animate-spin rounded-full border-4 border-solid border-purple-accent border-r-transparent">
                </div>
                <p class="mt-4 text-dark-text-secondary">Loading dashboard data...</p>
            </div>

            <!-- Charts and Overview -->
            <div v-if="!store.loading || store.kpis" class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                <!-- Payment Statistics -->
                <PaymentStatistics :total="paymentStats.total" :paid="paymentStats.paid"
                    :partially-paid="paymentStats.partiallyPaid" :overdue="paymentStats.overdue" />

                <!-- Revenue Chart -->
                <div class="lg:col-span-2">
                    <div class="mb-4 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <div class="flex items-center gap-3">
                            <label class="text-sm font-medium text-dark-text-secondary">Date Range:</label>
                            <div class="flex items-center gap-2">
                                <input type="date" v-model="from"
                                    class="input-dark !py-2 !px-3 text-sm!py-2 !px-3 text-sm" />
                                <span class="text-sm text-dark-text-muted">to</span>
                                <input type="date" v-model="to"
                                    class="input-dark !py-2 !px-3 text-sm!py-2 !px-3 text-sm" />
                            </div>
                        </div>
                        <button @click="from = ''; to = ''; store.fetchMetrics()"
                            class="btn-secondary text-sm py-2 px-4">
                            Clear
                        </button>
                    </div>
                    <RevenueChart :title="t?.revenue || 'Revenue Overview'" :series="seriesArr" />
                </div>
            </div>

            <!-- Overview List -->
            <div v-if="overviewItems.length > 0" class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                <OverviewList :title="t?.overview_title || 'Quick Overview'" :items="overviewItems" />
            </div>

            <!-- Recent Activity -->
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                <RecentActivity :items="recentActivity" :loading="loadingActivity" @refresh="fetchRecentActivity" />
            </div>

            <!-- Admin reporting widgets -->
            <div v-if="aging || (topSuppliers && topSuppliers.length)" class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                <div
                    class="rounded-xl border text-card-foreground shadow bg-slate-800/40 backdrop-blur-sm border-slate-700/50 p-8 group lg:col-span-1">
                    <div class="mb-4 text-base font-semibold text-dark-text-primary">Repayment Aging</div>
                    <div v-if="aging" class="space-y-2 text-sm">
                        <div class="flex justify-between text-dark-text-primary">
                            <span class="text-dark-text-secondary">Current</span>
                            <span>{{ Number(aging.current || 0).toLocaleString() }}</span>
                        </div>
                        <div class="flex justify-between text-dark-text-primary">
                            <span class="text-dark-text-secondary">1-30</span>
                            <span>{{ Number(aging.d1_30 || 0).toLocaleString() }}</span>
                        </div>
                        <div class="flex justify-between text-dark-text-primary">
                            <span class="text-dark-text-secondary">31-60</span>
                            <span>{{ Number(aging.d31_60 || 0).toLocaleString() }}</span>
                        </div>
                        <div class="flex justify-between text-dark-text-primary">
                            <span class="text-dark-text-secondary">60+</span>
                            <span>{{ Number(aging.d60p || 0).toLocaleString() }}</span>
                        </div>
                    </div>
                    <div v-else class="text-sm text-dark-text-muted">No data</div>
                </div>
                <div
                    class="rounded-xl border text-card-foreground shadow bg-slate-800/40 backdrop-blur-sm border-slate-700/50 p-8 group lg:col-span-2">
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
                                    <td>{{ Number(row.total).toLocaleString() }}</td>
                                </tr>
                                <tr v-if="!topSuppliers || topSuppliers.length === 0">
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
