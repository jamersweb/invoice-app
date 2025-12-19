<template>

  <Head :title="`${customer.name} - Profile`" />
  <AuthenticatedLayout>
    <div class="p-6">
      <div class="max-w-[1800px] mx-auto">
        <!-- Header -->
        <div class="mb-8">
          <div class="flex items-center gap-3 mb-2">
            <Link :href="route('forfaiting.customers.index')" class="text-slate-400 hover:text-white">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
              </svg>
            </Link>
            <div class="p-3 bg-gradient-to-br from-indigo-600 to-purple-600 rounded-xl">
              <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
              </svg>
            </div>
            <div>
              <h2 class="text-3xl font-bold text-white">{{ customer.name }}</h2>
              <p class="text-slate-400 mt-1">Customer Profile & Transaction History</p>
            </div>
          </div>
        </div>

        <!-- Customer Info -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
          <div class="bg-slate-800/50 border border-slate-700 rounded-lg p-6">
            <h3 class="text-white text-lg font-semibold mb-4">Contact Information</h3>
            <div class="space-y-3">
              <div>
                <p class="text-slate-400 text-sm">Email</p>
                <p class="text-white">{{ customer.email || '-' }}</p>
              </div>
              <div>
                <p class="text-slate-400 text-sm">Phone</p>
                <p class="text-white">{{ customer.phone || '-' }}</p>
              </div>
              <div>
                <p class="text-slate-400 text-sm">Company</p>
                <p class="text-white">{{ customer.company_name || '-' }}</p>
              </div>
              <div>
                <p class="text-slate-400 text-sm">Address</p>
                <p class="text-white">{{ customer.address || '-' }}</p>
              </div>
              <div>
                <p class="text-slate-400 text-sm">City, Country</p>
                <p class="text-white">{{ [customer.city, customer.country].filter(Boolean).join(', ') || '-' }}</p>
              </div>
            </div>
          </div>

          <div class="bg-slate-800/50 border border-slate-700 rounded-lg p-6">
            <h3 class="text-white text-lg font-semibold mb-4">Transaction Summary</h3>
            <div class="space-y-3">
              <div>
                <p class="text-slate-400 text-sm">Total Transactions</p>
                <p class="text-white text-2xl font-bold">{{ customer.transactions?.length || 0 }}</p>
              </div>
              <div>
                <p class="text-slate-400 text-sm">Status</p>
                <span :class="`px-2 py-1 rounded text-xs ${customer.status === 'active'
                    ? 'bg-green-500/20 text-green-400 border border-green-500/50'
                    : 'bg-red-500/20 text-red-400 border border-red-500/50'
                  }`">
                  {{ customer.status }}
                </span>
              </div>
            </div>
          </div>
        </div>

        <!-- Transactions -->
        <div class="bg-slate-800/30 border border-slate-700 rounded-lg overflow-hidden">
          <div class="p-6 border-b border-slate-700">
            <h3 class="text-white text-lg font-semibold">Transactions</h3>
          </div>
          <div class="overflow-x-auto custom-scrollbar">
            <table class="w-full">
              <thead class="bg-slate-800/50 border-b border-slate-700">
                <tr>
                  <th class="text-left text-slate-300 text-xs p-3">#</th>
                  <th class="text-right text-slate-300 text-xs p-3">Net Amount</th>
                  <th class="text-right text-slate-300 text-xs p-3">Profit %</th>
                  <th class="text-right text-slate-300 text-xs p-3">Sales Cycle</th>
                  <th class="text-right text-slate-300 text-xs p-3">Date</th>
                  <th class="text-center text-slate-300 text-xs p-3">Status</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="transaction in customer.transactions" :key="transaction.id"
                  class="border-b border-slate-700/50 hover:bg-slate-800/30">
                  <td class="text-white font-medium text-xs p-3">{{ transaction.transaction_number }}</td>
                  <td class="text-right text-purple-400 font-mono text-xs p-3">
                    {{ formatCurrency(transaction.net_amount) }}
                  </td>
                  <td class="text-right text-emerald-400 text-xs p-3">{{ transaction.profit_margin }}%</td>
                  <td class="text-right text-cyan-400 text-xs p-3">{{ transaction.sales_cycle }}d</td>
                  <td class="text-right text-slate-300 text-xs p-3">
                    {{ formatDate(transaction.date_of_transaction) }}
                  </td>
                  <td class="text-center p-3">
                    <span :class="`px-2 py-1 rounded text-xs ${transaction.status === 'Ongoing'
                        ? 'bg-blue-500/20 text-blue-400 border border-blue-500/50'
                        : transaction.status === 'Ended'
                          ? 'bg-green-500/20 text-green-400 border border-green-500/50'
                          : 'bg-gray-500/20 text-gray-400 border border-gray-500/50'
                      }`">
                      {{ transaction.status }}
                    </span>
                  </td>
                </tr>
                <tr v-if="!customer.transactions || customer.transactions.length === 0">
                  <td colspan="6" class="text-center text-slate-400 py-8">No transactions found</td>
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
  net_amount: number;
  profit_margin: number;
  sales_cycle: number;
  date_of_transaction: string;
  status: string;
}

interface Customer {
  id: number;
  name: string;
  email?: string;
  phone?: string;
  company_name?: string;
  address?: string;
  city?: string;
  country?: string;
  status: string;
  transactions?: Transaction[];
  documents?: any[];
}

interface Props {
  customer: Customer;
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
</script>
