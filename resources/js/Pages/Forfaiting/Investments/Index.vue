<template>

  <Head title="Principal Investments" />
  <AuthenticatedLayout>
    <div class="p-6">
      <div class="max-w-7xl mx-auto">
        <!-- Header -->
        <div class="mb-8">
          <div class="flex items-center gap-3 mb-2">
            <div class="p-3 bg-blue-600/20 rounded-xl">
              <svg class="w-8 h-8 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
              </svg>
            </div>
            <div>
              <h2 class="text-3xl font-bold text-white">Principal Investments</h2>
              <p class="text-slate-400 mt-1">Track investor principal investments and dates</p>
            </div>
          </div>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
          <div class="bg-slate-800/50 border border-slate-700 rounded-lg p-4">
            <p class="text-slate-400 text-sm mb-1">Total Investments</p>
            <p class="text-white text-2xl font-bold">{{ investments.total }}</p>
          </div>
          <div class="bg-slate-800/50 border border-slate-700 rounded-lg p-4">
            <p class="text-slate-400 text-sm mb-1">Total Principal</p>
            <p class="text-blue-400 text-2xl font-bold">{{ formatCurrency(stats.totalPrincipal) }}</p>
          </div>
          <div class="bg-slate-800/50 border border-slate-700 rounded-lg p-4">
            <p class="text-slate-400 text-sm mb-1">Unique Investors</p>
            <p class="text-purple-400 text-2xl font-bold">{{ stats.uniqueInvestors }}</p>
          </div>
        </div>

        <!-- Action Buttons -->
        <div v-if="!isFormOpen" class="mb-6 flex justify-end gap-3">
          <a :href="route('forfaiting.investments.export')"
            class="bg-slate-700 hover:bg-slate-600 text-white px-4 py-2 rounded-lg flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            Download Excel
          </a>
          <button @click="isFormOpen = true"
            class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            Add New Investment
          </button>
        </div>

        <!-- Form -->
        <div v-if="isFormOpen" class="mb-6 bg-slate-800/50 border border-slate-700 rounded-lg p-6">
          <div class="flex justify-between items-center mb-4">
            <h3 class="text-white text-lg font-semibold">Add New Investment</h3>
            <button @click="cancelForm" class="text-slate-400 hover:text-white">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
          <form @submit.prevent="submitForm" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
              <div>
                <label class="block text-sm font-medium text-slate-300 mb-1">Investor Name</label>
                <input v-model="form.name" type="text" required
                  class="w-full rounded-lg border border-slate-600 bg-slate-900/50 p-2 text-white"
                  placeholder="Investor name" />
              </div>
              <div>
                <label class="block text-sm font-medium text-slate-300 mb-1">Amount (AED)</label>
                <input v-model.number="form.amount" type="number" step="0.01" required
                  class="w-full rounded-lg border border-slate-600 bg-slate-900/50 p-2 text-white"
                  placeholder="e.g., 100000" />
              </div>
              <div>
                <label class="block text-sm font-medium text-slate-300 mb-1">Date</label>
                <input v-model="form.date" type="date" required
                  class="w-full rounded-lg border border-slate-600 bg-slate-900/50 p-2 text-white" />
              </div>
              <div class="md:col-span-3">
                <label class="block text-sm font-medium text-slate-300 mb-1">Notes</label>
                <textarea v-model="form.notes"
                  class="w-full rounded-lg border border-slate-600 bg-slate-900/50 p-2 text-white" rows="3"
                  placeholder="Optional notes..."></textarea>
              </div>
            </div>
            <div class="flex justify-end gap-3">
              <button type="button" @click="cancelForm"
                class="px-4 py-2 rounded-lg border border-slate-600 text-white hover:bg-slate-700">
                Cancel
              </button>
              <button type="submit" :disabled="form.processing"
                class="px-4 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white disabled:opacity-50">
                {{ form.processing ? 'Saving...' : 'Create Investment' }}
              </button>
            </div>
          </form>
        </div>

        <!-- Filters -->
        <div class="mb-6 flex flex-wrap gap-4">
          <div class="flex-1 min-w-[200px]">
            <input v-model="filters.search" type="text" placeholder="Search investments..."
              class="w-full rounded-lg border border-slate-600 bg-slate-800/50 p-2 text-white placeholder-slate-400" />
          </div>
          <select v-model="filters.investor" class="rounded-lg border border-slate-600 bg-slate-800/50 p-2 text-white">
            <option value="all">All Investors</option>
            <option v-for="investor in uniqueInvestors" :key="investor" :value="investor">
              {{ investor }}
            </option>
          </select>
        </div>

        <!-- Investment Table -->
        <div class="bg-slate-800/30 border border-slate-700 rounded-lg overflow-hidden">
          <div class="overflow-x-auto custom-scrollbar">
            <table class="w-full">
              <thead class="bg-slate-800/50 border-b border-slate-700">
                <tr>
                  <th class="text-left text-slate-300 text-xs p-3">Investor</th>
                  <th class="text-right text-slate-300 text-xs p-3">Amount</th>
                  <th class="text-right text-slate-300 text-xs p-3">Currency</th>
                  <th class="text-right text-slate-300 text-xs p-3">Date</th>
                  <th class="text-right text-slate-300 text-xs p-3">Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="investment in filteredInvestments" :key="investment.id"
                  class="border-b border-slate-700/50 hover:bg-slate-800/30">
                  <td class="text-white font-medium text-xs p-3 truncate max-w-[150px]">{{ investment.name }}</td>
                  <td class="text-right text-blue-400 font-mono text-xs p-3">
                    {{ formatCurrency(investment.amount) }}
                  </td>
                  <td class="text-right text-slate-300 text-xs p-3">{{ investment.currency }}</td>
                  <td class="text-right text-slate-300 text-xs p-3">
                    {{ formatDate(investment.date) }}
                  </td>
                  <td class="text-right p-3">
                    <button @click="deleteInvestment(investment.id)" class="text-red-400 hover:text-red-300">
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                      </svg>
                    </button>
                  </td>
                </tr>
                <tr v-if="filteredInvestments.length === 0">
                  <td colspan="5" class="text-center text-slate-400 py-8">No investments found</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Pagination -->
        <div v-if="investments.links && investments.links.length > 3" class="mt-6 flex justify-center">
          <div class="flex gap-2">
            <Link v-for="link in investments.links" :key="link.label" :href="link.url || '#'" v-html="link.label"
              :class="`px-3 py-2 rounded-lg ${link.active
                ? 'bg-blue-600 text-white'
                : 'bg-slate-800/50 text-slate-300 hover:bg-slate-700'
                } ${!link.url ? 'opacity-50 cursor-not-allowed' : ''}`" />
          </div>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>

<script setup lang="ts">
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { ref, computed } from 'vue';

interface Investment {
  id: number;
  name: string;
  amount: number;
  currency: string;
  date: string;
  notes?: string;
}

interface Props {
  investments: {
    data: Investment[];
    links: any[];
    total: number;
  };
}

const props = defineProps<Props>();

const isFormOpen = ref(false);

const form = useForm({
  name: '',
  amount: 0,
  currency: 'AED',
  date: '',
  notes: '',
});

const filters = ref({
  search: '',
  investor: 'all',
});

const stats = computed(() => {
  const data = props.investments.data;
  const uniqueInvestors = new Set(data.map((i) => i.name));
  return {
    totalPrincipal: data.reduce((sum, i) => sum + i.amount, 0),
    uniqueInvestors: uniqueInvestors.size,
  };
});

const uniqueInvestors = computed(() => {
  const investors = new Set(props.investments.data.map((i) => i.name));
  return Array.from(investors).sort();
});

const filteredInvestments = computed(() => {
  let result = props.investments.data;

  if (filters.value.search) {
    const search = filters.value.search.toLowerCase();
    result = result.filter((i) => i.name.toLowerCase().includes(search));
  }

  if (filters.value.investor !== 'all') {
    result = result.filter((i) => i.name === filters.value.investor);
  }

  return result;
});

const cancelForm = () => {
  isFormOpen.value = false;
  form.reset();
};

const submitForm = () => {
  form.post(route('forfaiting.investments.store'), {
    preserveScroll: true,
    onSuccess: () => {
      cancelForm();
    },
  });
};

const deleteInvestment = (id: number) => {
  if (confirm('Are you sure you want to delete this investment?')) {
    router.delete(route('forfaiting.investments.destroy', id), {
      preserveScroll: true,
    });
  }
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
</script>
