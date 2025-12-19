<template>

  <Head title="Profit Allocations" />
  <AuthenticatedLayout>
    <div class="p-6">
      <div class="max-w-[1800px] mx-auto">
        <!-- Header -->
        <div class="mb-8">
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
              <div class="p-3 bg-purple-600/20 rounded-xl">
                <svg class="w-8 h-8 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M9 7h6m0 10v-5m-3 5h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                </svg>
              </div>
              <div>
                <h2 class="text-3xl font-bold text-white">Profit Allocations</h2>
                <p class="text-slate-400 mt-1">Calculate and track investor profit distributions</p>
              </div>
            </div>
            <div class="flex gap-3">
              <button @click="recalculateAllocations" :disabled="isRecalculating"
                class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg flex items-center gap-2 disabled:opacity-50">
                <svg v-if="isRecalculating" class="w-4 h-4 animate-spin" fill="none" stroke="currentColor"
                  viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
                <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M9 7h6m0 10v-5m-3 5h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                </svg>
                {{ isRecalculating ? 'Calculating...' : 'Calculate Allocations' }}
              </button>
            </div>
          </div>
        </div>

        <!-- Investor Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4 mb-8">
          <div v-for="(summary, investor) in investorSummary" :key="investor"
            class="bg-gradient-to-br from-slate-800 to-slate-900 border border-slate-700 rounded-lg p-4 cursor-pointer hover:border-blue-500/50 transition-colors">
            <div class="flex items-center justify-between mb-2">
              <p class="text-slate-300 font-semibold text-sm">{{ investor }}</p>
              <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
              </svg>
            </div>
            <p class="text-white text-xl font-bold">{{ formatCurrency(summary.totalProfit) }}</p>
            <p class="text-slate-400 text-xs mt-1">Total Profit (AED)</p>
            <div class="mt-3 pt-3 border-t border-slate-700/50 space-y-1">
              <div class="flex justify-between text-xs">
                <span class="text-slate-400">Realized:</span>
                <span class="text-green-400">{{ formatCurrency(summary.realizedProfit) }}</span>
              </div>
              <div class="flex justify-between text-xs">
                <span class="text-slate-400">Expected:</span>
                <span class="text-amber-400">{{ formatCurrency(summary.expectedProfit) }}</span>
              </div>
              <div class="flex justify-between text-xs pt-1 border-t border-slate-700/30">
                <span class="text-slate-400">Capital:</span>
                <span class="text-blue-400">{{ formatCurrency(summary.capital) }}</span>
              </div>
              <div class="flex justify-between text-xs">
                <span class="text-slate-400">Deals:</span>
                <span class="text-purple-400">{{ summary.dealsCount }}</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Filters -->
        <div class="mb-6 flex flex-wrap gap-4">
          <select v-model="filters.investor" class="rounded-lg border border-slate-600 bg-slate-800/50 p-2 text-white">
            <option value="all">All Investors</option>
            <option v-for="investor in uniqueInvestors" :key="investor" :value="investor">
              {{ investor }}
            </option>
          </select>
          <select v-model="filters.status" class="rounded-lg border border-slate-600 bg-slate-800/50 p-2 text-white">
            <option value="all">All Status</option>
            <option value="Ongoing">Ongoing</option>
            <option value="Ended">Ended</option>
          </select>
        </div>

        <!-- Allocations Table -->
        <div v-if="filteredAllocations.length === 0"
          class="bg-slate-800/30 border border-slate-700 rounded-lg p-12 text-center">
          <svg class="w-16 h-16 text-slate-600 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M9 7h6m0 10v-5m-3 5h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
          </svg>
          <p class="text-slate-400 text-lg mb-2">No allocations calculated yet</p>
          <p class="text-slate-500 text-sm mb-6">Click "Calculate Allocations" to generate profit distributions</p>
        </div>

        <div v-else class="bg-slate-800/30 border border-slate-700 rounded-lg overflow-hidden">
          <div class="overflow-x-auto custom-scrollbar">
            <table class="w-full">
              <thead class="bg-slate-800/50 border-b border-slate-700">
                <tr>
                  <th class="text-left text-slate-300 text-xs p-3">#</th>
                  <th class="text-left text-slate-300 text-xs p-3">Investor</th>
                  <th class="text-right text-slate-300 text-xs p-3">Amount</th>
                  <th class="text-right text-slate-300 text-xs p-3">Date</th>
                  <th class="text-center text-slate-300 text-xs p-3">Weightage</th>
                  <th class="text-right text-slate-300 text-xs p-3">Individual Profit</th>
                  <th class="text-center text-slate-300 text-xs p-3">Status</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="allocation in filteredAllocations" :key="allocation.id"
                  class="border-b border-slate-700/50 hover:bg-slate-800/30">
                  <td class="text-white font-medium text-xs p-3">
                    {{ allocation.transaction?.transaction_number || 'N/A' }}
                  </td>
                  <td class="text-white font-medium text-xs p-3">{{ allocation.investor_name }}</td>
                  <td class="text-right text-blue-400 font-mono text-xs p-3">
                    {{ allocation.transaction ? formatCurrency(allocation.transaction.net_amount) : 'N/A' }}
                  </td>
                  <td class="text-slate-300 text-xs p-3">{{ formatDate(allocation.allocation_date) }}</td>
                  <td class="text-center text-amber-400 font-semibold text-xs p-3">
                    {{ formatPercentage(allocation.profit_percentage) }}
                  </td>
                  <td class="text-right text-emerald-400 font-mono font-semibold text-xs p-3">
                    {{ formatCurrency(allocation.individual_profit) }}
                  </td>
                  <td class="text-center p-3">
                    <span :class="`px-2 py-1 rounded text-xs ${allocation.deal_status === 'Ended'
                        ? 'bg-green-500/20 text-green-400 border border-green-500/50'
                        : allocation.deal_status === 'Ongoing'
                          ? 'bg-blue-500/20 text-blue-400 border border-blue-500/50'
                          : 'bg-gray-500/20 text-gray-400 border border-gray-500/50'
                      }`">
                      {{ allocation.deal_status }}
                    </span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Pagination -->
        <div v-if="allocations.links && allocations.links.length > 3" class="mt-6 flex justify-center">
          <div class="flex gap-2">
            <Link v-for="link in allocations.links" :key="link.label" :href="link.url || '#'" v-html="link.label"
              :class="`px-3 py-2 rounded-lg ${link.active
                  ? 'bg-purple-600 text-white'
                  : 'bg-slate-800/50 text-slate-300 hover:bg-slate-700'
                } ${!link.url ? 'opacity-50 cursor-not-allowed' : ''}`" />
          </div>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>

<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { ref, computed } from 'vue';

interface Transaction {
  id: number;
  transaction_number: number;
  net_amount: number;
}

interface ProfitAllocation {
  id: number;
  transaction_id: number;
  investor_name: string;
  individual_profit: number;
  profit_percentage: number;
  deal_status: string;
  allocation_date: string;
  transaction?: Transaction;
}

interface Props {
  allocations: {
    data: ProfitAllocation[];
    links: any[];
  };
  transactions: Transaction[];
}

const props = defineProps<Props>();

const isRecalculating = ref(false);

const filters = ref({
  investor: 'all',
  status: 'all',
});

const uniqueInvestors = computed(() => {
  const investors = new Set(props.allocations.data.map((a) => a.investor_name));
  return Array.from(investors).sort();
});

const filteredAllocations = computed(() => {
  let result = props.allocations.data;

  if (filters.value.investor !== 'all') {
    result = result.filter((a) => a.investor_name === filters.value.investor);
  }

  if (filters.value.status !== 'all') {
    result = result.filter((a) => a.deal_status === filters.value.status);
  }

  return result;
});

const investorSummary = computed(() => {
  const summary: Record<string, any> = {};

  props.allocations.data.forEach((alloc) => {
    if (!summary[alloc.investor_name]) {
      summary[alloc.investor_name] = {
        realizedProfit: 0,
        expectedProfit: 0,
        totalProfit: 0,
        dealsCount: 0,
        capital: 0,
      };
    }

    if (alloc.deal_status === 'Ended') {
      summary[alloc.investor_name].realizedProfit += alloc.individual_profit;
    } else if (alloc.deal_status === 'Ongoing') {
      summary[alloc.investor_name].expectedProfit += alloc.individual_profit;
    }

    summary[alloc.investor_name].totalProfit += alloc.individual_profit;
    summary[alloc.investor_name].dealsCount += 1;
  });

  // Calculate capital (would need investments data)
  Object.keys(summary).forEach((investor) => {
    summary[investor].capital = summary[investor].realizedProfit; // Simplified
  });

  return summary;
});

const recalculateAllocations = () => {
  isRecalculating.value = true;
  router.post(
    route('forfaiting.profit-allocations.recalculate'),
    {},
    {
      preserveScroll: true,
      onFinish: () => {
        isRecalculating.value = false;
      },
    }
  );
};

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

const formatPercentage = (value: number) => {
  return value.toFixed(1) + '%';
};
</script>
