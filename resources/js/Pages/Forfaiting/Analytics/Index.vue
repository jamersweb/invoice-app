<template>
  <Head title="Analytics" />
  <AuthenticatedLayout>
    <div class="p-6">
      <div class="max-w-[1800px] mx-auto">
        <!-- Header -->
        <div class="mb-8">
          <div class="flex items-center gap-3 mb-2">
            <div class="p-3 bg-indigo-600/20 rounded-xl">
              <svg class="w-8 h-8 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
              </svg>
            </div>
            <div>
              <h2 class="text-3xl font-bold text-white">Analytics</h2>
              <p class="text-slate-400 mt-1">Portfolio performance and health metrics</p>
            </div>
          </div>
        </div>

        <!-- Health Score -->
        <div class="bg-gradient-to-br from-emerald-900/30 to-teal-900/30 border-emerald-700/50 rounded-lg p-6 mb-8">
          <div class="flex items-center justify-between mb-4">
            <h3 class="text-white text-lg font-semibold">Portfolio Health Score</h3>
            <div
              :class="`text-4xl font-bold ${
                healthScore >= 70
                  ? 'text-green-400'
                  : healthScore >= 50
                  ? 'text-amber-400'
                  : 'text-red-400'
              }`"
            >
              {{ Math.round(healthScore) }}
            </div>
          </div>
          <div class="w-full bg-slate-800/50 rounded-full h-4">
            <div
              :class="`h-4 rounded-full transition-all ${
                healthScore >= 70
                  ? 'bg-green-500'
                  : healthScore >= 50
                  ? 'bg-amber-500'
                  : 'bg-red-500'
              }`"
              :style="{ width: healthScore + '%' }"
            ></div>
          </div>
          <p class="text-slate-400 text-sm mt-2">
            {{ healthScore >= 70 ? 'Excellent' : healthScore >= 50 ? 'Good' : 'Needs Attention' }}
          </p>
        </div>

        <!-- Metrics -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
          <div class="bg-slate-800/50 border border-slate-700 rounded-lg p-4">
            <p class="text-slate-400 text-sm mb-1">Utilization Rate</p>
            <p class="text-white text-2xl font-bold">{{ formatPercent(metrics.utilizationRate) }}</p>
          </div>
          <div class="bg-slate-800/50 border border-slate-700 rounded-lg p-4">
            <p class="text-slate-400 text-sm mb-1">Profitability Rate</p>
            <p class="text-emerald-400 text-2xl font-bold">{{ formatPercent(metrics.profitabilityRate) }}</p>
          </div>
          <div class="bg-slate-800/50 border border-slate-700 rounded-lg p-4">
            <p class="text-slate-400 text-sm mb-1">Total Fund</p>
            <p class="text-blue-400 text-2xl font-bold">{{ formatCurrency(metrics.totalFund) }}</p>
          </div>
          <div class="bg-slate-800/50 border border-slate-700 rounded-lg p-4">
            <p class="text-slate-400 text-sm mb-1">Available Balance</p>
            <p class="text-purple-400 text-2xl font-bold">{{ formatCurrency(metrics.availableBalance) }}</p>
          </div>
        </div>

        <!-- Alerts -->
        <div v-if="alerts.length > 0" class="bg-gradient-to-br from-red-900/20 to-amber-900/20 border-red-700/50 rounded-lg p-6 mb-8">
          <div class="flex items-center gap-2 mb-4">
            <svg class="w-5 h-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
            </svg>
            <h3 class="text-white text-lg font-semibold">Alerts ({{ alerts.length }})</h3>
          </div>
          <div class="space-y-3">
            <div
              v-for="(alert, idx) in alerts"
              :key="idx"
              :class="`p-3 rounded-lg border ${
                alert.type === 'danger'
                  ? 'bg-red-900/30 border-red-700/50'
                  : 'bg-amber-900/30 border-amber-700/50'
              }`"
            >
              <p
                :class="`font-semibold mb-1 ${
                  alert.type === 'danger' ? 'text-red-400' : 'text-amber-400'
                }`"
              >
                {{ alert.title }}
              </p>
              <p class="text-white text-sm">{{ alert.message }}</p>
            </div>
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
  healthScore: number;
  metrics: {
    utilizationRate: number;
    profitabilityRate: number;
    totalFund: number;
    availableBalance: number;
    lockedAmount: number;
  };
  alerts: Array<{
    type: string;
    title: string;
    message: string;
  }>;
}

const props = defineProps<Props>();

const formatCurrency = (value: number) => {
  return new Intl.NumberFormat('en-AE', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  }).format(value);
};

const formatPercent = (value: number) => {
  return value.toFixed(2) + '%';
};
</script>
