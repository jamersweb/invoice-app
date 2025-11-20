<template>
    <Head title="Forfaiting Dashboard" />
    <AuthenticatedLayout>
        <div class="p-6 space-y-6">
            <div class="mb-6">
                <h1 class="text-3xl font-bold text-white">Global Dashboard</h1>
                <p class="text-slate-400 mt-1">Comprehensive overview of all metrics and performance</p>
            </div>

            <!-- Key Metrics Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                <MetricCard
                    title="Total Principal"
                    :value="formatCurrency(metrics.totalPrincipal)"
                    :subvalue="`USD ${formatCurrency(metrics.totalPrincipal / metrics.usdRate)}`"
                    color="blue"
                />
                <MetricCard
                    title="Total Net Profit"
                    :value="formatCurrency(metrics.realizedProfit)"
                    :subvalue="`USD ${formatCurrency(metrics.realizedProfit / metrics.usdRate)}`"
                    color="emerald"
                />
                <MetricCard
                    title="Total Fund"
                    :value="formatCurrency(metrics.totalFund)"
                    :subvalue="`USD ${formatCurrency(metrics.totalFund / metrics.usdRate)}`"
                    color="purple"
                />
                <MetricCard
                    title="Pending Profit"
                    :value="formatCurrency(metrics.pendingProfit)"
                    :subvalue="`USD ${formatCurrency(metrics.pendingProfit / metrics.usdRate)}`"
                    color="amber"
                />
                <MetricCard
                    title="Locked Amount"
                    :value="formatNumber(metrics.lockedAmount)"
                    :subvalue="`USD ${formatNumber(metrics.lockedAmount / metrics.usdRate)}`"
                    color="slate"
                />
                <MetricCard
                    title="Pending Expenses"
                    :value="formatNumber(metrics.pendingExpenses)"
                    :subvalue="`USD ${formatNumber(metrics.pendingExpenses / metrics.usdRate)}`"
                    color="red"
                />
                <MetricCard
                    title="Available Balance"
                    :value="formatCurrency(metrics.availableBalance)"
                    :subvalue="`USD ${formatCurrency(metrics.availableBalance / metrics.usdRate)}`"
                    color="green"
                />
                <MetricCard
                    title="Per Annum"
                    :value="formatPercent(metrics.perAnnumProfit)"
                    subvalue="Per Day × 360"
                    color="orange"
                />
            </div>

            <!-- Upcoming Deals -->
            <div class="bg-slate-800/30 border border-slate-700 rounded-lg p-6 mb-6">
                <h3 class="text-lg font-semibold text-white mb-4">Upcoming Deal Endings</h3>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b border-slate-700">
                                <th class="text-left text-slate-300 py-2">#</th>
                                <th class="text-left text-slate-300 py-2">Customer</th>
                                <th class="text-left text-slate-300 py-2">End Date</th>
                                <th class="text-center text-slate-300 py-2">Days Left</th>
                                <th class="text-right text-slate-300 py-2">Amount</th>
                                <th class="text-right text-slate-300 py-2">Profit</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="deal in upcomingDeals" :key="deal.transaction_number" class="border-b border-slate-700/50">
                                <td class="text-white py-2">{{ deal.transaction_number }}</td>
                                <td class="text-white py-2">{{ deal.customer }}</td>
                                <td class="text-slate-300 py-2">{{ formatDate(deal.endDate) }}</td>
                                <td class="text-center py-2">
                                    <span :class="deal.daysRemaining < 7 ? 'bg-red-500/20 text-red-400' : 'bg-blue-500/20 text-blue-400'" 
                                          class="px-2 py-1 rounded text-xs">
                                        {{ deal.daysRemaining }}d
                                    </span>
                                </td>
                                <td class="text-right text-purple-400 font-mono py-2">{{ formatNumber(deal.amount) }}</td>
                                <td class="text-right text-emerald-400 font-mono py-2">{{ formatNumber(deal.expectedProfit) }}</td>
                            </tr>
                            <tr v-if="upcomingDeals.length === 0">
                                <td colspan="6" class="text-center text-slate-400 py-8">No ongoing deals</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Customer Analysis -->
            <div class="bg-slate-800/30 border border-slate-700 rounded-lg p-6 mb-6">
                <h3 class="text-lg font-semibold text-white mb-4">Customer Yield Ranking & Analysis</h3>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b border-slate-700">
                                <th class="text-left text-slate-300 py-2">Customer</th>
                                <th class="text-center text-slate-300 py-2">Total Txns</th>
                                <th class="text-right text-slate-300 py-2">Disbursed</th>
                                <th class="text-right text-slate-300 py-2">Net Profit</th>
                                <th class="text-right text-slate-300 py-2">Profit %</th>
                                <th class="text-right text-slate-300 py-2">Per Annum %</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="customer in customerAnalysis" :key="customer.name" class="border-b border-slate-700/50">
                                <td class="text-white font-medium py-2">{{ customer.name }}</td>
                                <td class="text-center text-slate-300 py-2">{{ customer.total }}</td>
                                <td class="text-right text-purple-400 font-mono py-2">{{ formatNumber(customer.totalDisbursed) }}</td>
                                <td class="text-right text-emerald-400 font-mono py-2">{{ formatNumber(customer.netProfit) }}</td>
                                <td class="text-right text-amber-400 py-2">{{ formatPercent(customer.profitPercent) }}</td>
                                <td class="text-right text-orange-400 font-semibold py-2">{{ formatPercent(customer.perAnnumProfitPercent) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Investor Summary -->
            <div class="bg-slate-800/30 border border-slate-700 rounded-lg p-6">
                <h3 class="text-lg font-semibold text-white mb-4">Investor Summary - Profit Actual</h3>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b border-slate-700">
                                <th class="text-left text-slate-300 py-2">Name / Entity</th>
                                <th class="text-right text-slate-300 py-2">Profit Actual</th>
                                <th class="text-right text-slate-300 py-2">Principal</th>
                                <th class="text-right text-slate-300 py-2">Profit %</th>
                                <th class="text-right text-slate-300 py-2">Total Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="investor in investorSummary" :key="investor.name" class="border-b border-slate-700/50">
                                <td class="text-white font-medium py-2">{{ investor.name }}</td>
                                <td class="text-right text-emerald-400 font-mono py-2">{{ formatCurrency(investor.profitActual) }}</td>
                                <td class="text-right text-blue-400 font-mono py-2">{{ formatCurrency(investor.principal) }}</td>
                                <td class="text-right text-amber-400 py-2">{{ formatPercent(investor.profitPercentActual) }}</td>
                                <td class="text-right text-purple-400 font-mono py-2">{{ formatCurrency(investor.totalActual) }}</td>
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
import { defineProps, defineComponent } from 'vue';

interface Props {
    metrics: {
        totalPrincipal: number;
        realizedProfit: number;
        pendingProfit: number;
        totalFund: number;
        lockedAmount: number;
        pendingExpenses: number;
        availableBalance: number;
        perAnnumProfit: number;
        usdRate: number;
    };
    upcomingDeals: Array<{
        transaction_number: string;
        customer: string;
        endDate: string;
        daysRemaining: number;
        amount: number;
        expectedProfit: number;
    }>;
    investorSummary: Array<{
        name: string;
        principal: number;
        profitActual: number;
        profitPercentActual: number;
        totalActual: number;
    }>;
    customerAnalysis: Array<{
        name: string;
        total: number;
        totalDisbursed: number;
        netProfit: number;
        profitPercent: number;
        perAnnumProfitPercent: number;
    }>;
}

const props = defineProps<Props>();

const formatCurrency = (value: number) => {
    return new Intl.NumberFormat('en-AE', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(value);
};

const formatNumber = (value: number) => {
    return new Intl.NumberFormat('en-AE', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    }).format(value);
};

const formatPercent = (value: number) => {
    return value.toFixed(2) + '%';
};

const formatDate = (dateString: string) => {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', { month: '2-digit', year: '2-digit' });
};

const MetricCard = defineComponent({
    props: {
        title: String,
        value: String,
        subvalue: String,
        color: String,
    },
    template: `
        <div :class="'bg-gradient-to-br from-' + color + '-800 to-' + color + '-900 border border-' + color + '-700 rounded-lg p-4'">
            <div class="flex justify-between items-start">
                <div>
                    <p :class="'text-' + color + '-200 text-xs font-medium mb-1'">{{ title }}</p>
                    <p class="text-white text-xl font-bold">{{ value }}</p>
                    <p :class="'text-' + color + '-300 text-xs mt-1'">{{ subvalue }}</p>
                </div>
            </div>
        </div>
    `
});
</script>
