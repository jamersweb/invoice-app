<template>
    <Head title="Investors" />
    <AuthenticatedLayout>
        <div class="p-6 space-y-6">
            <div class="mb-6">
                <h1 class="text-3xl font-bold text-white">Investors</h1>
                <p class="text-slate-400 mt-1">View investor summaries and performance</p>
            </div>

            <!-- Investor Summary Table -->
            <div class="bg-slate-800/30 border border-slate-700 rounded-lg overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-slate-800/50">
                            <tr>
                                <th class="text-left text-slate-300 py-3 px-4">Investor Name</th>
                                <th class="text-right text-slate-300 py-3 px-4">Principal</th>
                                <th class="text-right text-slate-300 py-3 px-4">Profit Actual</th>
                                <th class="text-right text-slate-300 py-3 px-4">Profit + Pending</th>
                                <th class="text-right text-slate-300 py-3 px-4">Profit % (Actual)</th>
                                <th class="text-right text-slate-300 py-3 px-4">Profit % (With Pending)</th>
                                <th class="text-right text-slate-300 py-3 px-4">Total Actual</th>
                                <th class="text-right text-slate-300 py-3 px-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="investor in investors" :key="investor.name" class="border-t border-slate-700/50">
                                <td class="text-white font-medium py-3 px-4">{{ investor.name }}</td>
                                <td class="text-right text-blue-400 font-mono py-3 px-4">{{ formatCurrency(investor.principal) }}</td>
                                <td class="text-right text-emerald-400 font-mono py-3 px-4">{{ formatCurrency(investor.profitActual) }}</td>
                                <td class="text-right text-emerald-400 font-mono py-3 px-4">{{ formatCurrency(investor.profitWithPending) }}</td>
                                <td class="text-right text-amber-400 py-3 px-4">{{ formatPercent(investor.profitPercentActual) }}</td>
                                <td class="text-right text-amber-400 py-3 px-4">{{ formatPercent(investor.profitPercentWithPending) }}</td>
                                <td class="text-right text-purple-400 font-mono py-3 px-4">{{ formatCurrency(investor.totalActual) }}</td>
                                <td class="text-right py-3 px-4">
                                    <a
                                        :href="route('forfaiting.investors.dashboard', { id: investor.investor_id || '', name: investor.name })"
                                        class="text-blue-400 hover:text-blue-300 transition-colors"
                                    >
                                        View Dashboard
                                    </a>
                                </td>
                            </tr>
                            <tr v-if="investors.length === 0">
                                <td colspan="8" class="text-center text-slate-400 py-8">No investors found</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

interface Investor {
    name: string;
    principal: number;
    profitActual: number;
    profitWithPending: number;
    profitPercentActual: number;
    profitPercentWithPending: number;
    totalActual: number;
    investor_id?: string;
}

interface Props {
    investors: Investor[];
}

const props = defineProps<Props>();

const formatCurrency = (value: number) => {
    return new Intl.NumberFormat('en-AE', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(value);
};

const formatPercent = (value: number) => {
    return value.toFixed(2) + '%';
};
</script>

