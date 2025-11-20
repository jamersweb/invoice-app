<template>
    <Head :title="`Investor Dashboard: ${investor.name}`" />
    <AuthenticatedLayout>
        <div class="p-6 space-y-6">
            <div class="mb-6">
                <h1 class="text-3xl font-bold text-white">{{ investor.name }} - Dashboard</h1>
                <p class="text-slate-400 mt-1">Personalized investor performance overview</p>
            </div>

            <!-- Key Metrics -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                <div class="bg-gradient-to-br from-blue-800 to-blue-900 border border-blue-700 rounded-lg p-4">
                    <p class="text-blue-200 text-sm font-medium mb-1">Principal</p>
                    <p class="text-white text-2xl font-bold">{{ formatCurrency(investor.principal) }}</p>
                </div>
                <div class="bg-gradient-to-br from-emerald-800 to-emerald-900 border border-emerald-700 rounded-lg p-4">
                    <p class="text-emerald-200 text-sm font-medium mb-1">Profit Actual</p>
                    <p class="text-white text-2xl font-bold">{{ formatCurrency(investor.profitActual) }}</p>
                </div>
                <div class="bg-gradient-to-br from-purple-800 to-purple-900 border border-purple-700 rounded-lg p-4">
                    <p class="text-purple-200 text-sm font-medium mb-1">Total Actual</p>
                    <p class="text-white text-2xl font-bold">{{ formatCurrency(investor.totalActual) }}</p>
                </div>
                <div class="bg-gradient-to-br from-amber-800 to-amber-900 border border-amber-700 rounded-lg p-4">
                    <p class="text-amber-200 text-sm font-medium mb-1">Profit %</p>
                    <p class="text-white text-2xl font-bold">{{ formatPercent(investor.profitPercentActual) }}</p>
                </div>
            </div>

            <!-- Investments -->
            <div class="bg-slate-800/30 border border-slate-700 rounded-lg p-6 mb-6">
                <h2 class="text-xl font-semibold text-white mb-4">Investments</h2>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b border-slate-700">
                                <th class="text-left text-slate-300 py-2">Date</th>
                                <th class="text-right text-slate-300 py-2">Amount</th>
                                <th class="text-left text-slate-300 py-2">Currency</th>
                                <th class="text-left text-slate-300 py-2">Notes</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="investment in investments" :key="investment.id" class="border-b border-slate-700/50">
                                <td class="text-white py-2">{{ formatDate(investment.date) }}</td>
                                <td class="text-right text-purple-400 font-mono py-2">{{ formatCurrency(investment.amount) }}</td>
                                <td class="text-slate-300 py-2">{{ investment.currency }}</td>
                                <td class="text-slate-400 py-2">{{ investment.notes || '-' }}</td>
                            </tr>
                            <tr v-if="investments.length === 0">
                                <td colspan="4" class="text-center text-slate-400 py-8">No investments found</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Profit Allocations -->
            <div class="bg-slate-800/30 border border-slate-700 rounded-lg p-6">
                <h2 class="text-xl font-semibold text-white mb-4">Profit Allocations</h2>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b border-slate-700">
                                <th class="text-left text-slate-300 py-2">Transaction</th>
                                <th class="text-right text-slate-300 py-2">Individual Profit</th>
                                <th class="text-right text-slate-300 py-2">Profit %</th>
                                <th class="text-center text-slate-300 py-2">Deal Status</th>
                                <th class="text-left text-slate-300 py-2">Allocation Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="allocation in allocations" :key="allocation.id" class="border-b border-slate-700/50">
                                <td class="text-white py-2">{{ allocation.transaction?.transaction_number || 'N/A' }}</td>
                                <td class="text-right text-emerald-400 font-mono py-2">{{ formatCurrency(allocation.individual_profit) }}</td>
                                <td class="text-right text-amber-400 py-2">{{ allocation.profit_percentage }}%</td>
                                <td class="text-center py-2">
                                    <span :class="allocation.deal_status === 'Ongoing' ? 'bg-blue-500/20 text-blue-400' : 'bg-green-500/20 text-green-400'"
                                          class="px-2 py-1 rounded text-xs">
                                        {{ allocation.deal_status }}
                                    </span>
                                </td>
                                <td class="text-slate-300 py-2">{{ formatDate(allocation.allocation_date) }}</td>
                            </tr>
                            <tr v-if="allocations.length === 0">
                                <td colspan="5" class="text-center text-slate-400 py-8">No allocations found</td>
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

interface Investment {
    id: number;
    date: string;
    amount: number;
    currency: string;
    notes: string | null;
}

interface Allocation {
    id: number;
    individual_profit: number;
    profit_percentage: number;
    deal_status: string;
    allocation_date: string;
    transaction?: {
        transaction_number: string;
    };
}

interface Investor {
    name: string;
    investor_id: string | null;
    principal: number;
    profitActual: number;
    profitWithPending: number;
    totalActual: number;
    totalWithPending: number;
    profitPercentActual: number;
    profitPercentWithPending: number;
}

interface Props {
    investor: Investor;
    investments: Investment[];
    allocations: Allocation[];
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

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
};
</script>

