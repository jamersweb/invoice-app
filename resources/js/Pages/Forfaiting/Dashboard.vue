<template>

    <Head title="Forfaiting Dashboard" />
    <AuthenticatedLayout>
        <div class="p-3 sm:p-6">
            <div class="max-w-[1800px] mx-auto">
                <!-- Top KPI Bar - Always Visible -->
                <div
                    class="mb-6 sm:mb-8 sticky top-0 z-40 bg-gradient-to-br from-slate-950 via-slate-900 to-slate-950 pb-4">
                    <div class="grid grid-cols-2 lg:grid-cols-5 gap-3 sm:gap-4">
                        <!-- Weighted Portfolio APY -->
                        <div
                            class="bg-gradient-to-br from-indigo-900/80 to-indigo-800/80 border-indigo-600 rounded-lg p-3 sm:p-4">
                            <div class="flex items-center justify-between mb-1">
                                <p class="text-indigo-200 text-[10px] sm:text-xs font-semibold">WEIGHTED APY</p>
                            </div>
                            <p class="text-white text-xl sm:text-3xl font-bold">{{
                                formatPercent(metrics.weightedPortfolioAPY) }}</p>
                            <p class="text-indigo-300 text-[9px] sm:text-xs mt-1">Current ongoing exposure</p>
                        </div>

                        <!-- Idle % / Liquidity -->
                        <div
                            class="bg-gradient-to-br from-cyan-900/80 to-cyan-800/80 border-cyan-600 rounded-lg p-3 sm:p-4">
                            <div class="flex items-center justify-between mb-1">
                                <p class="text-cyan-200 text-[10px] sm:text-xs font-semibold">IDLE % / LIQUIDITY</p>
                            </div>
                            <p class="text-white text-xl sm:text-3xl font-bold">{{ formatPercent(metrics.idlePercent) }}
                            </p>
                            <p :class="`text-[9px] sm:text-xs mt-1 ${metrics.idlePercent >= 5 && metrics.idlePercent <= 10 ? 'text-green-300' :
                                    metrics.idlePercent > 10 ? 'text-amber-300' : 'text-red-300'
                                }`">
                                Target: 5-10%
                            </p>
                        </div>

                        <!-- HHI + Max Exposure -->
                        <div
                            class="bg-gradient-to-br from-orange-900/80 to-orange-800/80 border-orange-600 rounded-lg p-3 sm:p-4">
                            <div class="flex items-center justify-between mb-1">
                                <p class="text-orange-200 text-[10px] sm:text-xs font-semibold">HHI / MAX EXPOSURE</p>
                            </div>
                            <p class="text-white text-lg sm:text-2xl font-bold">{{ formatNumber(counterpartyRisk.hhi) }}
                            </p>
                            <p class="text-orange-300 text-[9px] sm:text-xs mt-1">
                                Max: {{ formatPercent(counterpartyRisk.maxSingleExposure) }}
                            </p>
                        </div>

                        <!-- Investor Stability -->
                        <div
                            class="bg-gradient-to-br from-purple-900/80 to-purple-800/80 border-purple-600 rounded-lg p-3 sm:p-4">
                            <div class="flex items-center justify-between mb-1">
                                <p class="text-purple-200 text-[10px] sm:text-xs font-semibold">INVESTOR STABILITY</p>
                            </div>
                            <p class="text-white text-xl sm:text-3xl font-bold">{{
                                formatPercent(investorStabilityPercent) }}</p>
                            <p class="text-purple-300 text-[9px] sm:text-xs mt-1">
                                {{investorIntelligence.filter((i: any) => i.redemptionRisk).length}} at risk
                            </p>
                        </div>

                        <!-- Portfolio Health Score -->
                        <div
                            class="bg-gradient-to-br from-emerald-900/80 to-emerald-800/80 border-emerald-600 rounded-lg p-3 sm:p-4">
                            <div class="flex items-center justify-between mb-1">
                                <p class="text-emerald-200 text-[10px] sm:text-xs font-semibold">HEALTH SCORE</p>
                            </div>
                            <p class="text-white text-xl sm:text-3xl font-bold">{{
                                Math.round(portfolioHealthScore.overall) }}</p>
                            <p :class="`text-[9px] sm:text-xs mt-1 ${portfolioHealthScore.overall >= 70 ? 'text-green-300' :
                                    portfolioHealthScore.overall >= 50 ? 'text-amber-300' : 'text-red-300'
                                }`">
                                {{ portfolioHealthScore.overall >= 70 ? 'Excellent' : portfolioHealthScore.overall >= 50
                                    ? 'Good' : 'Needs Attention' }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- System Alerts -->
                <div v-if="alerts.length > 0"
                    class="bg-gradient-to-br from-red-900/20 to-amber-900/20 border-red-700/50 rounded-lg p-4 sm:p-6 mb-6 sm:mb-8">
                    <div class="flex items-center gap-2 mb-4">
                        <svg class="w-5 h-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                        </svg>
                        <h3 class="text-white text-base sm:text-lg font-semibold">Active Alerts ({{ alerts.length }})
                        </h3>
                    </div>
                    <div class="space-y-3">
                        <div v-for="(alert, idx) in alerts" :key="idx" :class="`p-3 rounded-lg border ${alert.severity === 'critical' ? 'bg-red-900/30 border-red-700/50' :
                                alert.severity === 'warning' ? 'bg-amber-900/30 border-amber-700/50' :
                                    'bg-blue-900/30 border-blue-700/50'
                            }`">
                            <p :class="`font-semibold mb-1 ${alert.severity === 'critical' ? 'text-red-400' :
                                    alert.severity === 'warning' ? 'text-amber-400' :
                                        'text-blue-400'
                                }`">{{ alert.title }}</p>
                            <p class="text-white text-sm">{{ alert.message }}</p>
                        </div>
                    </div>
                </div>

                <!-- Predictive Analytics Summary -->
                <div
                    class="bg-gradient-to-br from-violet-900/30 to-purple-900/30 border-violet-700/50 rounded-lg p-4 sm:p-6 mb-6 sm:mb-8">
                    <div class="flex items-center gap-2 mb-4">
                        <svg class="w-5 h-5 text-violet-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                        <h3 class="text-white text-base sm:text-lg font-semibold">Next 30 Days Forecast</h3>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-4">
                        <div class="bg-slate-800/50 rounded-lg p-4">
                            <p class="text-slate-400 text-xs mb-1">Expected Profit</p>
                            <p class="text-emerald-400 text-2xl font-bold">{{
                                formatCurrency(predictiveAnalytics.totalExpectedProfit30d) }}</p>
                        </div>
                        <div class="bg-slate-800/50 rounded-lg p-4">
                            <p class="text-slate-400 text-xs mb-1">Capital Returning</p>
                            <p class="text-cyan-400 text-2xl font-bold">{{
                                formatCurrency(predictiveAnalytics.totalReturningCapital) }}</p>
                        </div>
                        <div class="bg-slate-800/50 rounded-lg p-4">
                            <p class="text-slate-400 text-xs mb-1">Max Deployable</p>
                            <p class="text-purple-400 text-2xl font-bold">{{
                                formatCurrency(predictiveAnalytics.maxDeploymentCapacity) }}</p>
                        </div>
                    </div>
                </div>

                <!-- Customer Quality Scores -->
                <div
                    class="bg-gradient-to-br from-red-900/30 to-orange-900/30 border-red-700/50 rounded-lg p-4 sm:p-6 mb-6 sm:mb-8">
                    <div class="flex items-center gap-2 mb-6">
                        <svg class="w-5 h-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                        </svg>
                        <h3 class="text-white text-base sm:text-lg font-semibold">Counterparty Risk Intelligence</h3>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-4 gap-4 mb-6">
                        <div class="bg-orange-800/50 border-orange-700 rounded-lg p-4">
                            <p class="text-orange-200 text-xs font-medium mb-2">HHI (Concentration)</p>
                            <p class="text-white text-2xl font-bold mb-1">{{ formatNumber(counterpartyRisk.hhi) }}</p>
                            <p class="text-orange-300 text-xs">
                                {{ counterpartyRisk.hhi < 1500 ? '✅ Low' : counterpartyRisk.hhi < 2500 ? '⚠️ Moderate'
                                    : '🔴 High' }} </p>
                        </div>

                        <div class="bg-red-800/50 border-red-700 rounded-lg p-4">
                            <p class="text-red-200 text-xs font-medium mb-2">Max Single Exposure</p>
                            <p class="text-white text-2xl font-bold mb-1">{{
                                formatPercent(counterpartyRisk.maxSingleExposure) }}</p>
                            <p class="text-red-300 text-xs">Of ongoing exposure</p>
                        </div>

                        <div class="bg-amber-800/50 border-amber-700 rounded-lg p-4">
                            <p class="text-amber-200 text-xs font-medium mb-2">Top 3 Profit Share</p>
                            <p class="text-white text-2xl font-bold mb-1">{{
                                formatPercent(counterpartyRisk.top3ProfitPercent) }}</p>
                            <p class="text-amber-300 text-xs">Of total profit</p>
                        </div>

                        <div class="bg-yellow-800/50 border-yellow-700 rounded-lg p-4">
                            <p class="text-yellow-200 text-xs font-medium mb-2">Risk Assessment</p>
                            <p class="text-white text-lg font-bold mb-1">
                                {{ counterpartyRisk.hhi < 1500 && counterpartyRisk.top3ProfitPercent < 60 ? 'Low Risk' :
                                    counterpartyRisk.hhi < 2500 && counterpartyRisk.top3ProfitPercent < 75
                                        ? 'Medium Risk' : 'High Risk' }} </p>
                                    <p class="text-yellow-300 text-xs">Based on HHI & Top 3</p>
                        </div>
                    </div>

                    <div>
                        <h4 class="text-white text-sm font-semibold mb-3">Customer Quality Scores (Min 3 Deals)</h4>
                        <div
                            class="bg-slate-800/50 border border-slate-700 rounded-lg overflow-hidden max-h-80 overflow-y-auto custom-scrollbar">
                            <table class="w-full">
                                <thead>
                                    <tr class="border-b border-slate-700 bg-slate-800/50">
                                        <th class="text-left text-slate-300 text-xs p-3">Customer</th>
                                        <th class="text-center text-slate-300 text-xs p-3">Deals</th>
                                        <th class="text-right text-slate-300 text-xs p-3">Quality Score</th>
                                        <th class="text-right text-slate-300 text-xs p-3">APY %</th>
                                        <th class="text-right text-slate-300 text-xs p-3 hidden sm:table-cell">Avg Tenor
                                        </th>
                                        <th class="text-right text-slate-300 text-xs p-3 hidden md:table-cell">Exposure
                                            %</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(customer, idx) in customerQualityScores.slice(0, 15)" :key="idx"
                                        class="border-b border-slate-700/50">
                                        <td class="text-white font-medium text-xs p-3">{{ customer.customer }}</td>
                                        <td class="text-center text-slate-300 text-xs p-3">{{ customer.dealCount }}</td>
                                        <td class="text-right p-3">
                                            <span :class="`px-2 py-1 rounded text-xs ${customer.qualityScore >= 75 ? 'bg-green-500/20 text-green-400 border border-green-500/50' :
                                                    customer.qualityScore >= 50 ? 'bg-yellow-500/20 text-yellow-400 border border-yellow-500/50' :
                                                        'bg-red-500/20 text-red-400 border border-red-500/50'
                                                }`">
                                                {{ formatNumber(customer.qualityScore) }}
                                            </span>
                                        </td>
                                        <td class="text-right text-purple-400 font-semibold text-xs p-3">{{
                                            formatPercent(customer.perAnnumProfit) }}</td>
                                        <td class="text-right text-cyan-400 text-xs p-3 hidden sm:table-cell">{{
                                            formatNumber(customer.avgTenor) }}d</td>
                                        <td class="text-right text-orange-400 text-xs p-3 hidden md:table-cell">{{
                                            formatPercent(customer.exposurePercent) }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Investor Intelligence -->
                <div
                    class="bg-gradient-to-br from-purple-900/30 to-pink-900/30 border-purple-700/50 rounded-lg p-4 sm:p-6 mb-6 sm:mb-8">
                    <div class="flex items-center gap-2 mb-6">
                        <svg class="w-5 h-5 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                        <h3 class="text-white text-base sm:text-lg font-semibold">Investor Intelligence</h3>
                    </div>

                    <div
                        class="bg-slate-800/50 border border-slate-700 rounded-lg overflow-hidden mb-4 max-h-96 overflow-y-auto custom-scrollbar">
                        <table class="w-full">
                            <thead>
                                <tr class="border-b border-slate-700 bg-slate-800/50">
                                    <th class="text-left text-slate-300 text-xs p-3">Investor</th>
                                    <th class="text-right text-slate-300 text-xs p-3">Yield %</th>
                                    <th class="text-right text-slate-300 text-xs p-3 hidden sm:table-cell">Deployed /
                                        Total</th>
                                    <th class="text-right text-slate-300 text-xs p-3 hidden md:table-cell">Days Since
                                        Top-Up</th>
                                    <th class="text-center text-slate-300 text-xs p-3">Risk</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(investor, idx) in investorIntelligence" :key="idx"
                                    class="border-b border-slate-700/50">
                                    <td class="text-white font-medium text-xs p-3">{{ investor.name }}</td>
                                    <td class="text-right text-emerald-400 font-semibold text-xs p-3">{{
                                        formatPercent(investor.weightedYield) }}</td>
                                    <td class="text-right text-slate-300 text-xs p-3 hidden sm:table-cell">
                                        <span class="text-purple-400 font-mono">{{
                                            formatCurrency(investor.deployedCapital) }}</span>
                                        <span class="text-slate-500"> / </span>
                                        <span class="text-cyan-400 font-mono">{{ formatCurrency(investor.totalCapital)
                                            }}</span>
                                        <span class="text-slate-500 ml-1">({{ formatPercent(investor.deploymentRate)
                                            }})</span>
                                    </td>
                                    <td class="text-right text-slate-400 text-xs p-3 hidden md:table-cell">{{
                                        investor.daysSinceLastInvestment }}d</td>
                                    <td class="text-center p-3">
                                        <span :class="`px-2 py-1 rounded text-xs ${investor.redemptionRisk
                                                ? 'bg-red-500/20 text-red-400 border border-red-500/50'
                                                : 'bg-green-500/20 text-green-400 border border-green-500/50'
                                            }`">
                                            {{ investor.redemptionRisk ? '⚠️ High' : '✅ Low' }}
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div class="bg-purple-800/50 border-purple-700 rounded-lg p-3">
                            <p class="text-purple-200 text-xs mb-1">At Redemption Risk</p>
                            <p class="text-white text-xl font-bold">{{investorIntelligence.filter((i: any) =>
                                i.redemptionRisk).length }}</p>
                            <p class="text-purple-300 text-xs mt-1">No profit in 90d + returns &lt;3%</p>
                        </div>
                        <div class="bg-pink-800/50 border-pink-700 rounded-lg p-3">
                            <p class="text-pink-200 text-xs mb-1">Avg Deployment Rate</p>
                            <p class="text-white text-xl font-bold">
                                {{formatPercent(investorIntelligence.reduce((sum: number, i: any) => sum +
                                    i.deploymentRate, 0) / investorIntelligence.length) }}
                            </p>
                            <p class="text-pink-300 text-xs mt-1">Capital utilization</p>
                        </div>
                        <div class="bg-indigo-800/50 border-indigo-700 rounded-lg p-3">
                            <p class="text-indigo-200 text-xs mb-1">Total Investor Capital</p>
                            <p class="text-white text-xl font-bold">
                                {{formatCurrency(investorIntelligence.reduce((sum: number, i: any) => sum +
                                i.totalCapital, 0)) }}
                            </p>
                            <p class="text-indigo-300 text-xs mt-1">AED</p>
                        </div>
                    </div>
                </div>

                <!-- Next 10 Deals Ending -->
                <div
                    class="bg-gradient-to-br from-emerald-900/30 to-teal-900/30 border-emerald-700/50 rounded-lg p-4 sm:p-6">
                    <div class="flex items-center gap-2 mb-4">
                        <svg class="w-5 h-5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                        <h3 class="text-white text-base sm:text-lg font-semibold">Next 10 Deals Ending</h3>
                    </div>
                    <div class="bg-slate-800/50 border border-slate-700 rounded-lg overflow-hidden">
                        <table class="w-full">
                            <thead>
                                <tr class="border-b border-slate-700 bg-slate-800/50">
                                    <th class="text-left text-slate-300 text-xs p-3">#</th>
                                    <th class="text-left text-slate-300 text-xs p-3">Customer</th>
                                    <th class="text-right text-slate-300 text-xs p-3">Amount</th>
                                    <th class="text-right text-slate-300 text-xs p-3">Profit</th>
                                    <th class="text-right text-slate-300 text-xs p-3">End Date</th>
                                    <th class="text-center text-slate-300 text-xs p-3">Days Left</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(deal, idx) in predictiveAnalytics.next10DealsEnding" :key="idx"
                                    class="border-b border-slate-700/50">
                                    <td class="text-white font-medium text-xs p-3">{{ deal.transactionNumber }}</td>
                                    <td class="text-white text-xs p-3">{{ deal.customer }}</td>
                                    <td class="text-right text-purple-400 font-mono text-xs p-3">{{
                                        formatCurrency(deal.principal) }}</td>
                                    <td class="text-right text-emerald-400 font-mono text-xs p-3">{{
                                        formatCurrency(deal.profit) }}</td>
                                    <td class="text-right text-slate-300 text-xs p-3">{{ formatDate(deal.endDate) }}
                                    </td>
                                    <td class="text-center p-3">
                                        <span :class="`px-2 py-1 rounded text-xs ${deal.daysUntilEnd < 7 ? 'bg-red-500/20 text-red-400 border border-red-500/50' :
                                                deal.daysUntilEnd < 14 ? 'bg-amber-500/20 text-amber-400 border border-amber-500/50' :
                                                    'bg-blue-500/20 text-blue-400 border border-blue-500/50'
                                            }`">
                                            {{ deal.daysUntilEnd }}d
                                        </span>
                                    </td>
                                </tr>
                                <tr v-if="predictiveAnalytics.next10DealsEnding.length === 0">
                                    <td colspan="6" class="text-center text-slate-400 py-8">No deals ending in next 30
                                        days</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

interface Props {
    metrics: {
        totalPrincipal: number;
        realizedProfit: number;
        pendingProfit: number;
        totalFund: number;
        lockedAmount: number;
        pendingExpenses: number;
        availableBalance: number;
        weightedPortfolioAPY: number;
        idlePercent: number;
    };
    portfolioHealthScore: {
        overall: number;
        liquidity: number;
        yield: number;
        counterpartyRisk: number;
        investorStability: number;
        dealVelocity: number;
    };
    counterpartyRisk: {
        hhi: number;
        maxSingleExposure: number;
        top3ProfitPercent: number;
    };
    customerAnalysis: Array<any>;
    customerExposure: Array<any>;
    customerQualityScores: Array<any>;
    investorIntelligence: Array<any>;
    investorStabilityPercent: number;
    dealRecyclingSpeed: {
        cyclesPerMonth: number;
    };
    predictiveAnalytics: {
        totalExpectedProfit30d: number;
        totalReturningCapital: number;
        maxDeploymentCapacity: number;
        next10DealsEnding: Array<any>;
    };
    alerts: Array<{
        severity: string;
        title: string;
        message: string;
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
    return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
};
</script>
