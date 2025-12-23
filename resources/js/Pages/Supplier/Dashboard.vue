<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, usePage } from '@inertiajs/vue3';
import KpiCard from '@/Components/Dashboard/KpiCard.vue';
import RevenueChart from '@/Components/Dashboard/RevenueChart.vue';
import OverviewList from '@/Components/Dashboard/OverviewList.vue';
import PaymentStatistics from '@/Components/Dashboard/PaymentStatistics.vue';
import { useDashboardStore } from '@/stores/dashboard';
import { onMounted, computed, ref, watch } from 'vue';

const store = useDashboardStore();
const from = ref<string>('');
const to = ref<string>('');
onMounted(() => store.fetchMetrics());
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
    const s: any = (store as any).series;
    if (Array.isArray(s)) return s;
    if (s && typeof s === 'object' && 'fundedLast30' in s && 'repaidLast30' in s) {
        return [{ date: 'Last 30d', funded: s.fundedLast30, repaid: s.repaidLast30 }];
    }
    return [] as Array<{ date: string; funded: number; repaid: number }>;
});

const kpis = computed(() => {
    const k = store.kpis;
    const fmt = (n: number) => new Intl.NumberFormat(undefined, { style: 'currency', currency: 'USD', maximumFractionDigits: 0 }).format(n);

    // Use fallback values if store data isn't loaded yet
    const totalFunded = k?.totalFunded || 0;
    const totalRepaid = k?.totalRepaid || 0;
    const outstanding = k?.outstanding || 0;
    const overdue = k?.overdue || 0;

    return [
        { title: t?.total_funded || 'Total Funded', value: fmt(totalFunded), icon: '💸', color: 'blue' as const, trend: 'up' as const, delta: '+12.5%' },
        { title: t?.total_repaid || 'Total Repaid', value: fmt(totalRepaid), icon: '🏦', color: 'green' as const, trend: 'up' as const, delta: '+8.2%' },
        { title: t?.outstanding || 'Outstanding', value: fmt(outstanding), icon: '📉', color: 'yellow' as const, trend: 'neutral' as const, delta: '0.0%' },
        { title: t?.overdue || 'Overdue', value: fmt(overdue), icon: '⏰', color: 'red' as const, trend: 'down' as const, delta: '-5.1%' },
    ];
});

// Mock payment statistics data
const paymentStats = computed(() => ({
    total: 100000,
    paid: 65000,
    partiallyPaid: 20000,
    overdue: 15000,
}));
</script>

<template>

    <Head :title="t?.dashboard || 'Supplier Dashboard'" />

    <AuthenticatedLayout>
        <div class="space-y-6">
            <!-- Page Title -->
            <div class="mb-6">
                <h1 class="text-2xl font-bold text-dark-text-primary">Supplier Dashboard</h1>
                <p class="mt-1 text-sm text-dark-text-secondary">Welcome back! Here's what's happening with your
                    invoices.</p>
            </div>

            <!-- Greeting & Date Time Container -->
            <div
                class="rounded-xl border text-card-foreground shadow bg-slate-800/40 border-slate-700/50 p-4 hover:bg-slate-800/60 transition-all">
                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                    <div>
                        <h2 class="text-xl font-semibold text-dark-text-primary">Good morning!</h2>
                        <p class="text-sm text-dark-text-secondary mt-1">Here's your overview for today</p>
                    </div>
                    <div class="flex items-center gap-4">
                        <div class="flex items-center gap-2 text-sm text-dark-text-secondary">
                            <svg width="13" height="13" fill="none" viewBox="0 0 13 13" class="text-dark-text-muted">
                                <path stroke="currentColor" stroke-width="1.5" d="M2 3h9v8H2zM3.5 1v4M9.5 1v4" />
                            </svg>
                            <span>{{ new Date().toLocaleDateString() }}</span>
                        </div>
                        <div class="h-5 w-px bg-dark-border"></div>
                        <div class="flex items-center gap-2 text-sm text-dark-text-secondary">
                            <svg width="13" height="13" fill="none" viewBox="0 0 13 13" class="text-dark-text-muted">
                                <circle cx="6.5" cy="6.5" r="5" stroke="currentColor" stroke-width="1.5" />
                                <path stroke="currentColor" stroke-width="1.5" d="M6.5 3v3.5l2.5 1.5" />
                            </svg>
                            <span>{{ new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pending Actions Alert -->
            <div v-if="store.pendingContracts > 0" 
                class="relative overflow-hidden rounded-2xl bg-indigo-500/10 border border-indigo-500/20 p-6 flex flex-col sm:flex-row items-center justify-between gap-4 animate-in fade-in slide-in-from-top duration-500"
            >
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-indigo-500/20 flex items-center justify-center text-2xl">
                        ✍️
                    </div>
                    <div>
                        <h3 class="font-bold text-indigo-100 italic">Action Required: Pending Contracts</h3>
                        <p class="text-sm text-indigo-300/80">You have {{ store.pendingContracts }} agreement(s) waiting for your digital signature.</p>
                    </div>
                </div>
                <Link :href="route('agreements.index')" 
                    class="px-6 py-2 bg-indigo-500 hover:bg-indigo-600 text-white rounded-xl font-bold transition-all shadow-lg shadow-indigo-500/20 whitespace-nowrap"
                >
                    Review & Sign Now
                </Link>
                <!-- Decorative background light -->
                <div class="absolute -right-10 -top-10 w-32 h-32 bg-indigo-500/10 blur-3xl rounded-full"></div>
            </div>

            <!-- KPIs -->
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
                <KpiCard v-for="(k, i) in kpis" :key="i" :title="k.title" :value="k.value" :icon="k.icon"
                    :color="k.color" :trend="k.trend" :delta="k.delta" />
            </div>

            <!-- Show message if no KPIs available -->
            <div v-if="kpis.length === 0"
                class="rounded-xl border text-card-foreground shadow bg-slate-800/40 border-slate-700/50 p-4 hover:bg-slate-800/60 transition-all text-center py-12">
                <p class="text-dark-text-secondary">Loading dashboard data...</p>
            </div>

            <!-- Charts and Overview -->
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                <!-- Payment Statistics -->
                <PaymentStatistics :total="paymentStats.total" :paid="paymentStats.paid"
                    :partially-paid="paymentStats.partiallyPaid" :overdue="paymentStats.overdue" />

                <!-- Revenue Chart -->
                <div class="lg:col-span-2">
                    <div class="mb-4 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div class="flex flex-col sm:flex-row sm:items-center gap-3 w-full sm:w-auto">
                            <label class="text-sm font-medium text-dark-text-secondary">Date Range:</label>
                            <div class="flex items-center gap-2 w-full sm:w-auto">
                                <input type="date" v-model="from"
                                    class="input-dark !py-2 !px-3 text-sm flex-1 sm:flex-none" />
                                <span class="text-sm text-dark-text-muted">to</span>
                                <input type="date" v-model="to"
                                    class="input-dark !py-2 !px-3 text-sm flex-1 sm:flex-none" />
                            </div>
                        </div>
                        <button @click="from = ''; to = ''; store.fetchMetrics()"
                            class="btn-secondary text-sm py-2 px-4 w-full sm:w-auto">
                            Clear
                        </button>
                    </div>
                    <RevenueChart :title="t?.revenue || 'Revenue Overview'" :series="seriesArr" />
                </div>
            </div>

            <!-- Overview List -->
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                <OverviewList :title="t?.overview_title || 'Quick Overview'" :items="[
                    { title: t?.overview_new_invoices || 'New invoices today', value: 12, icon: '🧾', status: 'success' },
                    { title: t?.overview_kyb_pending || 'KYB pending', value: 7, icon: '🪪', status: 'warning' },
                    { title: t?.overview_funding_approvals || 'Funding approvals', value: 4, icon: '✅', status: 'info' },
                ]" />
            </div>
        </div>
    </AuthenticatedLayout>
</template>
