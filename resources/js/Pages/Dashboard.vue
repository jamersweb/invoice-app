<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, usePage } from '@inertiajs/vue3';
import KpiCard from '@/Components/Dashboard/KpiCard.vue';
import RevenueChart from '@/Components/Dashboard/RevenueChart.vue';
import OverviewList from '@/Components/Dashboard/OverviewList.vue';
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
// Admin reporting widgets (silent fail if 403)
const aging = ref<{ current:number, d1_30:number, d31_60:number, d60p:number }|null>(null);
const topSuppliers = ref<Array<{ supplier_id:number, total:number }>>([]);
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
    } catch {}
}
onMounted(fetchAdminWidgets);

const kpis = computed(() => {
    const k = store.kpis;
    if (!k) return [] as Array<any>;
    const fmt = (n: number) => new Intl.NumberFormat(undefined, { style: 'currency', currency: 'USD', maximumFractionDigits: 0 }).format(n);
    return [
        { title: t?.total_funded || 'Total Funded', value: fmt(k.totalFunded), icon: '💸', color: 'blue', trend: 'up', delta: '+12.5%' },
        { title: t?.total_repaid || 'Total Repaid', value: fmt(k.totalRepaid), icon: '🏦', color: 'green', trend: 'up', delta: '+8.2%' },
        { title: t?.outstanding || 'Outstanding', value: fmt(k.outstanding), icon: '📉', color: 'yellow', trend: 'neutral', delta: '0.0%' },
        { title: t?.overdue || 'Overdue', value: fmt(k.overdue), icon: '⏰', color: 'red', trend: 'down', delta: '-5.1%' },
    ];
});

const overviewItems = [
    { title: 'New invoices today', value: 12, icon: '🧾', status: 'success' },
    { title: 'KYB pending', value: 7, icon: '🪪', status: 'warning' },
    { title: 'Funding approvals', value: 4, icon: '✅', status: 'info' },
];
</script>

<template>
    <Head :title="t?.dashboard || 'Dashboard'" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900">{{ t?.dashboard || 'Dashboard' }}</h2>
                    <p class="mt-1 text-sm text-gray-500">Welcome back! Here's what's happening with your invoices.</p>
                </div>
                <div class="flex items-center gap-4">
                    <div class="text-right">
                        <div class="text-sm text-gray-500">Last updated</div>
                        <div class="text-sm font-medium text-gray-900">{{ new Date().toLocaleDateString() }}</div>
                    </div>
                    <div class="h-8 w-px bg-gray-200"></div>
                    <div class="text-right">
                        <div class="text-sm text-gray-500">Current time</div>
                        <div class="text-sm font-medium text-gray-900">{{ new Date().toLocaleTimeString() }}</div>
                    </div>
                </div>
            </div>
        </template>

        <div class="py-10">
            <div class="mx-auto max-w-7xl space-y-8 px-4 sm:px-6 lg:px-8">
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

                <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                    <!-- Revenue chart -->
                    <div class="lg:col-span-2">
                        <div class="mb-4 flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <label class="text-sm font-medium text-gray-700">Date Range:</label>
                                <div class="flex items-center gap-2">
                                    <input
                                        type="date"
                                        v-model="from"
                                        class="rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                                    />
                                    <span class="text-sm text-gray-500">to</span>
                                    <input
                                        type="date"
                                        v-model="to"
                                        class="rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                                    />
                                </div>
                            </div>
                            <button
                                @click="from = ''; to = ''; store.fetchMetrics()"
                                class="rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                            >
                                Clear
                            </button>
                        </div>
                        <RevenueChart :title="t?.revenue || 'Revenue Overview'" :series="seriesArr" />
                    </div>
                    <!-- Overview list -->
                    <OverviewList
                        :title="t?.overview_title || 'Quick Overview'"
                        :items="[
                            { title: t?.overview_new_invoices || 'New invoices today', value: overviewItems[0].value, icon: '🧾', status: 'success' },
                            { title: t?.overview_kyb_pending || 'KYB pending', value: overviewItems[1].value, icon: '🪪', status: 'warning' },
                            { title: t?.overview_funding_approvals || 'Funding approvals', value: overviewItems[2].value, icon: '✅', status: 'info' },
                        ]"
                    />
                </div>

                <!-- Admin reporting widgets -->
                <div v-if="aging || (topSuppliers && topSuppliers.length)" class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                    <div class="rounded-xl border border-gray-200 bg-white p-6 lg:col-span-1">
                        <div class="mb-4 text-base font-semibold text-gray-900">Repayment Aging</div>
                        <div v-if="aging" class="space-y-2 text-sm">
                            <div class="flex justify-between"><span>Current</span><span>{{ Number(aging.current||0).toLocaleString() }}</span></div>
                            <div class="flex justify-between"><span>1-30</span><span>{{ Number(aging.d1_30||0).toLocaleString() }}</span></div>
                            <div class="flex justify-between"><span>31-60</span><span>{{ Number(aging.d31_60||0).toLocaleString() }}</span></div>
                            <div class="flex justify-between"><span>60+</span><span>{{ Number(aging.d60p||0).toLocaleString() }}</span></div>
                        </div>
                        <div v-else class="text-sm text-gray-500">No data</div>
                    </div>
                    <div class="rounded-xl border border-gray-200 bg-white p-6 lg:col-span-2">
                        <div class="mb-4 text-base font-semibold text-gray-900">Top Suppliers by Funded</div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full text-left text-sm">
                                <thead>
                                    <tr class="text-gray-500">
                                        <th class="py-2">Supplier ID</th>
                                        <th class="py-2">Total Funded</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="row in topSuppliers" :key="row.supplier_id" class="border-t">
                                        <td class="py-2">{{ row.supplier_id }}</td>
                                        <td class="py-2">{{ Number(row.total).toLocaleString() }}</td>
                                    </tr>
                                    <tr v-if="!topSuppliers || topSuppliers.length===0">
                                        <td colspan="2" class="py-4 text-center text-gray-500">No data</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
