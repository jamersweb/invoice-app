<template>
  <Head title="Investors" />
  <AuthenticatedLayout>
    <div class="p-6">
      <div class="max-w-[1800px] mx-auto">
        <!-- Header -->
        <div class="mb-8">
          <div class="flex items-center gap-3 mb-2">
            <div class="p-3 bg-purple-600/20 rounded-xl">
              <svg class="w-8 h-8 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
              </svg>
            </div>
            <div>
              <h2 class="text-3xl font-bold text-white">Investors</h2>
              <p class="text-slate-400 mt-1">Manage investor profiles and track performance</p>
            </div>
          </div>
        </div>

        <!-- Investor Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          <div
            v-for="investor in investors"
            :key="investor.name"
            @click="viewDashboard(investor)"
            class="bg-gradient-to-br from-slate-800 to-slate-900 border border-slate-700 rounded-lg p-6 cursor-pointer hover:border-purple-500/50 transition-colors"
          >
            <div class="flex items-center justify-between mb-4">
              <h3 class="text-white text-xl font-bold">{{ investor.name }}</h3>
              <svg class="w-5 h-5 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
              </svg>
            </div>
            <div class="space-y-3">
              <div>
                <p class="text-slate-400 text-sm">Principal</p>
                <p class="text-blue-400 text-2xl font-bold">{{ formatCurrency(investor.principal) }}</p>
              </div>
              <div>
                <p class="text-slate-400 text-sm">Realized Profit</p>
                <p class="text-green-400 text-xl font-semibold">{{ formatCurrency(investor.profitActual) }}</p>
              </div>
              <div>
                <p class="text-slate-400 text-sm">Total Capital</p>
                <p class="text-purple-400 text-xl font-semibold">{{ formatCurrency(investor.totalActual) }}</p>
              </div>
              <div>
                <p class="text-slate-400 text-sm">Profit % (Realized)</p>
                <p class="text-emerald-400 text-lg font-semibold">{{ formatPercent(investor.profitPercentActual) }}</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>

<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

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
  investors: Investor[];
}

const props = defineProps<Props>();

const viewDashboard = (investor: Investor) => {
  const url = investor.investor_id
    ? route('forfaiting.investors.dashboard', { id: investor.investor_id })
    : route('forfaiting.investors.dashboard', { name: investor.name });
  router.visit(url);
};

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
