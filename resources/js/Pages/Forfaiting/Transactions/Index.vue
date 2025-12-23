<template>

  <Head title="Master Transactions" />
  <AuthenticatedLayout>
    <div class="p-6">
      <div class="max-w-[1800px] mx-auto">
        <!-- Header -->
        <div class="mb-8">
          <div class="flex items-center gap-3 mb-2">
            <div class="p-3 bg-emerald-600/20 rounded-xl">
              <svg class="w-8 h-8 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
              </svg>
            </div>
            <div>
              <h2 class="text-3xl font-bold text-white">Master Transactions</h2>
              <p class="text-slate-400 mt-1">Forfaiting Deal Management & Tracking</p>
            </div>
          </div>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
          <div class="bg-slate-800/50 border border-slate-700 rounded-lg p-4">
            <p class="text-slate-400 text-sm mb-1">Total Transactions</p>
            <p class="text-white text-2xl font-bold">{{ transactions.total }}</p>
          </div>
          <div class="bg-slate-800/50 border border-slate-700 rounded-lg p-4">
            <p class="text-slate-400 text-sm mb-1">Ongoing</p>
            <p class="text-blue-400 text-2xl font-bold">{{ stats.ongoing }}</p>
          </div>
          <div class="bg-slate-800/50 border border-slate-700 rounded-lg p-4">
            <p class="text-slate-400 text-sm mb-1">Ended</p>
            <p class="text-green-400 text-2xl font-bold">{{ stats.ended }}</p>
          </div>
          <div class="bg-slate-800/50 border border-slate-700 rounded-lg p-4">
            <p class="text-slate-400 text-sm mb-1">Total Disbursed</p>
            <p class="text-purple-400 text-2xl font-bold">{{ formatCurrency(stats.totalDisbursed) }}</p>
          </div>
        </div>

        <!-- Action Buttons -->
        <div v-if="!isFormOpen" class="mb-6 flex justify-end gap-3">
          <button @click="isFormOpen = true"
            class="bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded-lg flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            Add New Transaction
          </button>
        </div>

        <!-- Form -->
        <div v-if="isFormOpen" class="mb-6 bg-slate-800/50 border border-slate-700 rounded-lg p-6">
          <div class="flex justify-between items-center mb-4">
            <h3 class="text-white text-lg font-semibold">
              {{ editingTransaction ? 'Edit Transaction' : 'Add New Transaction' }}
            </h3>
            <button @click="cancelForm" class="text-slate-400 hover:text-white">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
          <form @submit.prevent="submitForm" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
              <div>
                <label class="block text-sm font-medium text-slate-300 mb-1">Transaction #</label>
                <input v-model="form.transaction_number" type="number" required
                  class="w-full rounded-lg border border-slate-600 bg-slate-900/50 p-2 text-white"
                  placeholder="e.g., 1" />
              </div>
              <div>
                <label class="block text-sm font-medium text-slate-300 mb-1">Transaction Date</label>
                <input v-model="form.date_of_transaction" type="date" required
                  class="w-full rounded-lg border border-slate-600 bg-slate-900/50 p-2 text-white" />
              </div>
              <div>
                <label class="block text-sm font-medium text-slate-300 mb-1">Customer</label>
                <input v-model="form.customer" type="text" required
                  class="w-full rounded-lg border border-slate-600 bg-slate-900/50 p-2 text-white"
                  placeholder="Customer name" />
              </div>
              <div>
                <label class="block text-sm font-medium text-slate-300 mb-1">Net Amount (AED)</label>
                <input v-model.number="form.net_amount" type="number" step="0.01" required
                  class="w-full rounded-lg border border-slate-600 bg-slate-900/50 p-2 text-white"
                  placeholder="e.g., 60000" />
              </div>
              <div>
                <label class="block text-sm font-medium text-slate-300 mb-1">Profit Margin (%)</label>
                <input v-model.number="form.profit_margin" type="number" step="0.01" required
                  class="w-full rounded-lg border border-slate-600 bg-slate-900/50 p-2 text-white"
                  placeholder="e.g., 5.0" />
              </div>
              <div>
                <label class="block text-sm font-medium text-slate-300 mb-1">Sales Cycle (days)</label>
                <input v-model.number="form.sales_cycle" type="number" required
                  class="w-full rounded-lg border border-slate-600 bg-slate-900/50 p-2 text-white"
                  placeholder="e.g., 45" />
              </div>
              <div>
                <label class="block text-sm font-medium text-slate-300 mb-1">Disbursement Charges (AED)</label>
                <input v-model.number="form.disbursement_charges" type="number" step="0.01"
                  class="w-full rounded-lg border border-slate-600 bg-slate-900/50 p-2 text-white"
                  placeholder="e.g., 0" />
              </div>
              <div>
                <label class="block text-sm font-medium text-slate-300 mb-1">Status</label>
                <select v-model="form.status"
                  class="w-full rounded-lg border border-slate-600 bg-slate-900/50 p-2 text-white">
                  <option value="Not Disbursed">Not Disbursed</option>
                  <option value="Ongoing">Ongoing</option>
                  <option value="Ended">Ended</option>
                </select>
              </div>
            </div>
            <div>
              <label class="block text-sm font-medium text-slate-300 mb-1">Notes</label>
              <textarea v-model="form.notes"
                class="w-full rounded-lg border border-slate-600 bg-slate-900/50 p-2 text-white" rows="3"
                placeholder="Optional notes..."></textarea>
            </div>
            <div class="flex justify-end gap-3">
              <button type="button" @click="cancelForm"
                class="px-4 py-2 rounded-lg border border-slate-600 text-white hover:bg-slate-700">
                Cancel
              </button>
              <button type="submit" :disabled="form.processing"
                class="px-4 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white disabled:opacity-50">
                {{ form.processing ? 'Saving...' : (editingTransaction ? 'Update' : 'Create') }}
              </button>
            </div>
          </form>
        </div>

        <!-- Filters -->
        <div class="mb-6 flex flex-wrap gap-4">
          <div class="flex-1 min-w-[200px]">
            <input v-model="filters.search" type="text" placeholder="Search transactions..."
              class="w-full rounded-lg border border-slate-600 bg-slate-800/50 p-2 text-white placeholder-slate-400" />
          </div>
          <select v-model="filters.status" class="rounded-lg border border-slate-600 bg-slate-800/50 p-2 text-white">
            <option value="all">All Status</option>
            <option value="Ongoing">Ongoing</option>
            <option value="Ended">Ended</option>
            <option value="Not Disbursed">Not Disbursed</option>
          </select>
          <select v-model="filters.customer" class="rounded-lg border border-slate-600 bg-slate-800/50 p-2 text-white">
            <option value="all">All Customers</option>
            <option v-for="customer in uniqueCustomers" :key="customer" :value="customer">
              {{ customer }}
            </option>
          </select>
        </div>

        <!-- Transaction Table -->
        <div class="bg-slate-800/30 border border-slate-700 rounded-lg overflow-hidden">
          <div class="overflow-x-auto custom-scrollbar">
            <table class="w-full">
              <thead class="bg-slate-800/50 border-b border-slate-700">
                <tr>
                  <th class="text-left text-slate-300 text-xs p-3">#</th>
                  <th class="text-left text-slate-300 text-xs p-3">Customer</th>
                  <th class="text-right text-slate-300 text-xs p-3">Net Amount</th>
                  <th class="text-right text-slate-300 text-xs p-3">Profit %</th>
                  <th class="text-right text-slate-300 text-xs p-3">Sales Cycle</th>
                  <th class="text-right text-slate-300 text-xs p-3">Date</th>
                  <th class="text-center text-slate-300 text-xs p-3">Status</th>
                  <th class="text-right text-slate-300 text-xs p-3">Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="transaction in filteredTransactions" :key="transaction.id"
                  class="border-b border-slate-700/50 hover:bg-slate-800/30">
                  <td class="text-white font-medium text-xs p-3">{{ transaction.transaction_number }}</td>
                  <td class="text-white text-xs p-3 truncate max-w-[150px]">{{ transaction.customer }}</td>
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
                  <td class="text-right p-3">
                    <div class="flex justify-end gap-2">
                      <button @click="editTransaction(transaction)" class="text-blue-400 hover:text-blue-300">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                      </button>
                      <button @click="deleteTransaction(transaction.id)" class="text-red-400 hover:text-red-300">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                      </button>
                    </div>
                  </td>
                </tr>
                <tr v-if="filteredTransactions.length === 0">
                  <td colspan="8" class="text-center text-slate-400 py-8">No transactions found</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Pagination -->
        <div v-if="transactions.links && transactions.links.length > 3" class="mt-6 flex justify-center">
          <div class="flex gap-2">
            <Link v-for="link in transactions.links" :key="link.label" :href="link.url || '#'" v-html="link.label"
              :class="`px-3 py-2 rounded-lg ${link.active
                ? 'bg-emerald-600 text-white'
                : 'bg-slate-800/50 text-slate-300 hover:bg-slate-700'
                } ${!link.url ? 'opacity-50 cursor-not-allowed' : ''}`" />
          </div>
        </div>
      </div>
    </div>

    <DarkConfirmModal 
        :show="showDeleteModal"
        title="Delete Transaction"
        message="Are you sure you want to delete this transaction? This action cannot be undone."
        confirm-text="Delete"
        type="danger"
        @close="showDeleteModal = false"
        @confirm="confirmDeletion"
    />
  </AuthenticatedLayout>
</template>

<script setup lang="ts">
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DarkConfirmModal from '@/Components/DarkConfirmModal.vue';
import { ref, computed } from 'vue';

interface Transaction {
  id: number;
  transaction_number: number;
  customer: string;
  date_of_transaction: string;
  net_amount: number;
  profit_margin: number;
  disbursement_charges?: number;
  sales_cycle: number;
  status: string;
  notes?: string;
}

interface Props {
  transactions: {
    data: Transaction[];
    links: any[];
    total: number;
  };
  customers?: Array<{ id: number; name: string }>;
}

const props = defineProps<Props>();

const isFormOpen = ref(false);
const editingTransaction = ref<Transaction | null>(null);
const showDeleteModal = ref(false);
const transactionToDelete = ref<number | null>(null);

const form = useForm({
  transaction_number: '',
  customer: '',
  date_of_transaction: '',
  net_amount: 0,
  profit_margin: 0,
  disbursement_charges: 0,
  sales_cycle: 0,
  status: 'Ongoing',
  notes: '',
});

const filters = ref({
  search: '',
  status: 'all',
  customer: 'all',
});

const stats = computed(() => {
  const data = props.transactions.data;
  return {
    ongoing: data.filter((t) => t.status === 'Ongoing').length,
    ended: data.filter((t) => t.status === 'Ended').length,
    totalDisbursed: data.reduce((sum, t) => sum + t.net_amount, 0),
  };
});

const uniqueCustomers = computed(() => {
  const customers = new Set(props.transactions.data.map((t) => t.customer));
  return Array.from(customers).sort();
});

const filteredTransactions = computed(() => {
  let result = props.transactions.data;

  if (filters.value.search) {
    const search = filters.value.search.toLowerCase();
    result = result.filter(
      (t) =>
        t.transaction_number.toString().includes(search) ||
        t.customer.toLowerCase().includes(search)
    );
  }

  if (filters.value.status !== 'all') {
    result = result.filter((t) => t.status === filters.value.status);
  }

  if (filters.value.customer !== 'all') {
    result = result.filter((t) => t.customer === filters.value.customer);
  }

  return result;
});

const editTransaction = (transaction: Transaction) => {
  editingTransaction.value = transaction;
  form.transaction_number = transaction.transaction_number.toString();
  form.customer = transaction.customer;
  form.date_of_transaction = transaction.date_of_transaction;
  form.net_amount = transaction.net_amount;
  form.profit_margin = transaction.profit_margin;
  form.disbursement_charges = transaction.disbursement_charges || 0;
  form.sales_cycle = transaction.sales_cycle;
  form.status = transaction.status;
  form.notes = transaction.notes || '';
  isFormOpen.value = true;
};

const cancelForm = () => {
  isFormOpen.value = false;
  editingTransaction.value = null;
  form.reset();
};

const submitForm = () => {
  const url = editingTransaction.value
    ? route('forfaiting.transactions.update', editingTransaction.value.id)
    : route('forfaiting.transactions.store');

  const method = editingTransaction.value ? 'put' : 'post';

  form[method](url, {
    preserveScroll: true,
    onSuccess: () => {
      cancelForm();
    },
  });
};

const deleteTransaction = (id: number) => {
  transactionToDelete.value = id;
  showDeleteModal.value = true;
};

const confirmDeletion = () => {
    if (transactionToDelete.value) {
        router.delete(route('forfaiting.transactions.destroy', transactionToDelete.value), {
            preserveScroll: true,
            onSuccess: () => {
                showDeleteModal.value = false;
                transactionToDelete.value = null;
            }
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
