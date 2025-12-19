<template>

  <Head :title="`${investor.name} - Dashboard`" />
  <AuthenticatedLayout>
    <div class="p-6">
      <div class="max-w-[1800px] mx-auto">
        <!-- Header -->
        <div class="mb-8">
          <div class="flex items-center gap-3 mb-2">
            <Link :href="route('forfaiting.investors.index')" class="text-slate-400 hover:text-white">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
              </svg>
            </Link>
            <div class="p-3 bg-purple-600/20 rounded-xl">
              <svg class="w-8 h-8 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
              </svg>
            </div>
            <div>
              <h2 class="text-3xl font-bold text-white">{{ investor.name }}</h2>
              <p class="text-slate-400 mt-1">Investor Dashboard & Performance</p>
            </div>
          </div>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
          <div class="bg-slate-800/50 border border-slate-700 rounded-lg p-4">
            <p class="text-slate-400 text-sm mb-1">Principal</p>
            <p class="text-blue-400 text-2xl font-bold">{{ formatCurrency(investor.principal) }}</p>
          </div>
          <div class="bg-slate-800/50 border border-slate-700 rounded-lg p-4">
            <p class="text-slate-400 text-sm mb-1">Realized Profit</p>
            <p class="text-green-400 text-2xl font-bold">{{ formatCurrency(investor.profitActual) }}</p>
          </div>
          <div class="bg-slate-800/50 border border-slate-700 rounded-lg p-4">
            <p class="text-slate-400 text-sm mb-1">Total Capital</p>
            <p class="text-purple-400 text-2xl font-bold">{{ formatCurrency(investor.totalActual) }}</p>
          </div>
          <div class="bg-slate-800/50 border border-slate-700 rounded-lg p-4">
            <p class="text-slate-400 text-sm mb-1">Profit %</p>
            <p class="text-emerald-400 text-2xl font-bold">{{ formatPercent(investor.profitPercentActual) }}</p>
          </div>
        </div>

        <!-- Investments -->
        <div class="bg-slate-800/30 border border-slate-700 rounded-lg overflow-hidden mb-8">
          <div class="p-6 border-b border-slate-700">
            <h3 class="text-white text-lg font-semibold">Investments</h3>
          </div>
          <div class="overflow-x-auto custom-scrollbar">
            <table class="w-full">
              <thead class="bg-slate-800/50 border-b border-slate-700">
                <tr>
                  <th class="text-left text-slate-300 text-xs p-3">Amount</th>
                  <th class="text-right text-slate-300 text-xs p-3">Currency</th>
                  <th class="text-right text-slate-300 text-xs p-3">Date</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="investment in investments" :key="investment.id"
                  class="border-b border-slate-700/50 hover:bg-slate-800/30">
                  <td class="text-blue-400 font-mono text-xs p-3">{{ formatCurrency(investment.amount) }}</td>
                  <td class="text-right text-slate-300 text-xs p-3">{{ investment.currency }}</td>
                  <td class="text-right text-slate-300 text-xs p-3">{{ formatDate(investment.date) }}</td>
                </tr>
                <tr v-if="investments.length === 0">
                  <td colspan="3" class="text-center text-slate-400 py-8">No investments found</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Profit Allocations -->
        <div class="bg-slate-800/30 border border-slate-700 rounded-lg overflow-hidden">
          <div class="p-6 border-b border-slate-700">
            <h3 class="text-white text-lg font-semibold">Profit Allocations</h3>
          </div>
          <div class="overflow-x-auto custom-scrollbar">
            <table class="w-full">
              <thead class="bg-slate-800/50 border-b border-slate-700">
                <tr>
                  <th class="text-left text-slate-300 text-xs p-3">Transaction #</th>
                  <th class="text-right text-slate-300 text-xs p-3">Individual Profit</th>
                  <th class="text-center text-slate-300 text-xs p-3">Weightage</th>
                  <th class="text-right text-slate-300 text-xs p-3">Date</th>
                  <th class="text-center text-slate-300 text-xs p-3">Status</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="allocation in allocations" :key="allocation.id"
                  class="border-b border-slate-700/50 hover:bg-slate-800/30">
                  <td class="text-white font-medium text-xs p-3">
                    {{ allocation.transaction?.transaction_number || 'N/A' }}
                  </td>
                  <td class="text-right text-emerald-400 font-mono font-semibold text-xs p-3">
                    {{ formatCurrency(allocation.individual_profit) }}
                  </td>
                  <td class="text-center text-amber-400 font-semibold text-xs p-3">
                    {{ formatPercentage(allocation.profit_percentage) }}
                  </td>
                  <td class="text-right text-slate-300 text-xs p-3">{{ formatDate(allocation.allocation_date) }}</td>
                  <td class="text-center p-3">
                    <span :class="`px-2 py-1 rounded text-xs ${allocation.deal_status === 'Ended'
                        ? 'bg-green-500/20 text-green-400 border border-green-500/50'
                        : 'bg-blue-500/20 text-blue-400 border border-blue-500/50'
                      }`">
                      {{ allocation.deal_status }}
                    </span>
                  </td>
                </tr>
                <tr v-if="allocations.length === 0">
                  <td colspan="5" class="text-center text-slate-400 py-8">No allocations found</td>
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
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

interface Transaction {
  id: number;
  transaction_number: number;
}

interface Investment {
  id: number;
  amount: number;
  currency: string;
  date: string;
}

interface ProfitAllocation {
  id: number;
  individual_profit: number;
  profit_percentage: number;
  deal_status: string;
  allocation_date: string;
  transaction?: Transaction;
}

interface Investor {
  name: string;
  investor_id?: string;
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
  allocations: ProfitAllocation[];
}

const props = defineProps<Props>();

const formatCurrency = (value: number) => {
  return new Intl.NumberFormat('en-AE', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  }).format(value);
};

const formatDate = (dateString: string) => {
  const date = new Date(dateString);
  return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
};

const formatPercent = (value: number) => {
  return value.toFixed(2) + '%';
};

const formatPercentage = (value: number) => {
  return value.toFixed(1) + '%';
};
</script>
