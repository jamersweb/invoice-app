<template>

  <Head title="Expenses & Payables" />
  <AuthenticatedLayout>
    <div class="p-6">
      <div class="max-w-[1800px] mx-auto">
        <!-- Header -->
        <div class="mb-8">
          <div class="flex items-center gap-3 mb-2">
            <div class="p-3 bg-red-600/20 rounded-xl">
              <svg class="w-8 h-8 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2zM10 8.5a1.5 1.5 0 103 0 1.5 1.5 0 00-3 0zm5 0a1.5 1.5 0 103 0 1.5 1.5 0 00-3 0z" />
              </svg>
            </div>
            <div>
              <h2 class="text-3xl font-bold text-white">Expenses & Payables</h2>
              <p class="text-slate-400 mt-1">Track pending and paid expenses affecting available balance</p>
            </div>
          </div>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
          <div class="bg-slate-800/50 border border-slate-700 rounded-lg p-4">
            <p class="text-slate-400 text-sm mb-1">Total Expenses</p>
            <p class="text-white text-2xl font-bold">{{ formatCurrency(stats.total) }}</p>
          </div>
          <div class="bg-slate-800/50 border border-slate-700 rounded-lg p-4">
            <p class="text-slate-400 text-sm mb-1">Pending</p>
            <p class="text-amber-400 text-2xl font-bold">{{ formatCurrency(stats.pending) }}</p>
          </div>
          <div class="bg-slate-800/50 border border-slate-700 rounded-lg p-4">
            <p class="text-slate-400 text-sm mb-1">Approved</p>
            <p class="text-green-400 text-2xl font-bold">{{ formatCurrency(stats.approved) }}</p>
          </div>
          <div class="bg-slate-800/50 border border-slate-700 rounded-lg p-4">
            <p class="text-slate-400 text-sm mb-1">Rejected</p>
            <p class="text-red-400 text-2xl font-bold">{{ formatCurrency(stats.rejected) }}</p>
          </div>
        </div>

        <!-- Action Buttons -->
        <div v-if="!isFormOpen" class="mb-6 flex justify-end gap-3">
          <button @click="isFormOpen = true"
            class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            Add New Expense
          </button>
        </div>

        <!-- Form -->
        <div v-if="isFormOpen" class="mb-6 bg-slate-800/50 border border-slate-700 rounded-lg p-6">
          <div class="flex justify-between items-center mb-4">
            <h3 class="text-white text-lg font-semibold">
              {{ editingExpense ? 'Edit Expense' : 'Add New Expense' }}
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
                <label class="block text-sm font-medium text-slate-300 mb-1">Category</label>
                <input v-model="form.category" type="text" required
                  class="w-full rounded-lg border border-slate-600 bg-slate-900/50 p-2 text-white"
                  placeholder="e.g., Office Supplies" />
              </div>
              <div>
                <label class="block text-sm font-medium text-slate-300 mb-1">Description</label>
                <input v-model="form.description" type="text" required
                  class="w-full rounded-lg border border-slate-600 bg-slate-900/50 p-2 text-white"
                  placeholder="Expense description" />
              </div>
              <div>
                <label class="block text-sm font-medium text-slate-300 mb-1">Amount (AED)</label>
                <input v-model.number="form.amount" type="number" step="0.01" required
                  class="w-full rounded-lg border border-slate-600 bg-slate-900/50 p-2 text-white"
                  placeholder="e.g., 500" />
              </div>
              <div>
                <label class="block text-sm font-medium text-slate-300 mb-1">Date</label>
                <input v-model="form.expense_date" type="date" required
                  class="w-full rounded-lg border border-slate-600 bg-slate-900/50 p-2 text-white" />
              </div>
              <div>
                <label class="block text-sm font-medium text-slate-300 mb-1">Status</label>
                <select v-model="form.status"
                  class="w-full rounded-lg border border-slate-600 bg-slate-900/50 p-2 text-white">
                  <option value="Pending">Pending</option>
                  <option value="Approved">Approved</option>
                  <option value="Rejected">Rejected</option>
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
                class="px-4 py-2 rounded-lg bg-red-600 hover:bg-red-700 text-white disabled:opacity-50">
                {{ form.processing ? 'Saving...' : (editingExpense ? 'Update' : 'Create') }}
              </button>
            </div>
          </form>
        </div>

        <!-- Filters -->
        <div class="mb-6 flex flex-wrap gap-4">
          <div class="flex-1 min-w-[200px]">
            <input v-model="filters.search" type="text" placeholder="Search expenses..."
              class="w-full rounded-lg border border-slate-600 bg-slate-800/50 p-2 text-white placeholder-slate-400" />
          </div>
          <select v-model="filters.status" class="rounded-lg border border-slate-600 bg-slate-800/50 p-2 text-white">
            <option value="all">All Status</option>
            <option value="Pending">Pending</option>
            <option value="Approved">Approved</option>
            <option value="Rejected">Rejected</option>
          </select>
          <select v-model="filters.category" class="rounded-lg border border-slate-600 bg-slate-800/50 p-2 text-white">
            <option value="all">All Categories</option>
            <option v-for="category in uniqueCategories" :key="category" :value="category">
              {{ category }}
            </option>
          </select>
        </div>

        <!-- Expense Table -->
        <div class="bg-slate-800/30 border border-slate-700 rounded-lg overflow-hidden">
          <div class="overflow-x-auto custom-scrollbar">
            <table class="w-full">
              <thead class="bg-slate-800/50 border-b border-slate-700">
                <tr>
                  <th class="text-left text-slate-300 text-xs p-3">Category</th>
                  <th class="text-left text-slate-300 text-xs p-3">Description</th>
                  <th class="text-right text-slate-300 text-xs p-3">Amount</th>
                  <th class="text-right text-slate-300 text-xs p-3">Date</th>
                  <th class="text-center text-slate-300 text-xs p-3">Status</th>
                  <th class="text-right text-slate-300 text-xs p-3">Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="expense in filteredExpenses" :key="expense.id"
                  class="border-b border-slate-700/50 hover:bg-slate-800/30">
                  <td class="text-white text-xs p-3">{{ expense.category }}</td>
                  <td class="text-white text-xs p-3">{{ expense.description }}</td>
                  <td class="text-right text-red-400 font-mono text-xs p-3">
                    {{ formatCurrency(expense.amount) }}
                  </td>
                  <td class="text-right text-slate-300 text-xs p-3">
                    {{ formatDate(expense.expense_date) }}
                  </td>
                  <td class="text-center p-3">
                    <span :class="`px-2 py-1 rounded text-xs ${expense.status === 'Approved'
                        ? 'bg-green-500/20 text-green-400 border border-green-500/50'
                        : expense.status === 'Pending'
                          ? 'bg-amber-500/20 text-amber-400 border border-amber-500/50'
                          : 'bg-red-500/20 text-red-400 border border-red-500/50'
                      }`">
                      {{ expense.status }}
                    </span>
                  </td>
                  <td class="text-right p-3">
                    <div class="flex justify-end gap-2">
                      <button v-if="expense.status === 'Pending'" @click="updateStatus(expense.id, 'Approved')"
                        class="text-green-400 hover:text-green-300" title="Approve">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                      </button>
                      <button v-if="expense.status === 'Pending'" @click="updateStatus(expense.id, 'Rejected')"
                        class="text-red-400 hover:text-red-300" title="Reject">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M6 18L18 6M6 6l12 12" />
                        </svg>
                      </button>
                      <button @click="deleteExpense(expense.id)" class="text-red-400 hover:text-red-300">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                      </button>
                    </div>
                  </td>
                </tr>
                <tr v-if="filteredExpenses.length === 0">
                  <td colspan="6" class="text-center text-slate-400 py-8">No expenses found</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Pagination -->
        <div v-if="expenses.links && expenses.links.length > 3" class="mt-6 flex justify-center">
          <div class="flex gap-2">
            <Link v-for="link in expenses.links" :key="link.label" :href="link.url || '#'" v-html="link.label" :class="`px-3 py-2 rounded-lg ${link.active
                ? 'bg-red-600 text-white'
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

interface Expense {
  id: number;
  category: string;
  description: string;
  amount: number;
  currency: string;
  expense_date: string;
  status: string;
  notes?: string;
}

interface Props {
  expenses: {
    data: Expense[];
    links: any[];
  };
  stats: {
    total: number;
    pending: number;
    approved: number;
    rejected: number;
  };
}

const props = defineProps<Props>();

const isFormOpen = ref(false);
const editingExpense = ref<Expense | null>(null);

const form = useForm({
  category: '',
  description: '',
  amount: 0,
  currency: 'AED',
  expense_date: '',
  status: 'Pending',
  notes: '',
});

const filters = ref({
  search: '',
  status: 'all',
  category: 'all',
});

const uniqueCategories = computed(() => {
  const categories = new Set(props.expenses.data.map((e) => e.category));
  return Array.from(categories).sort();
});

const filteredExpenses = computed(() => {
  let result = props.expenses.data;

  if (filters.value.search) {
    const search = filters.value.search.toLowerCase();
    result = result.filter(
      (e) =>
        e.description.toLowerCase().includes(search) ||
        e.category.toLowerCase().includes(search)
    );
  }

  if (filters.value.status !== 'all') {
    result = result.filter((e) => e.status === filters.value.status);
  }

  if (filters.value.category !== 'all') {
    result = result.filter((e) => e.category === filters.value.category);
  }

  return result;
});

const editExpense = (expense: Expense) => {
  editingExpense.value = expense;
  form.category = expense.category;
  form.description = expense.description;
  form.amount = expense.amount;
  form.currency = expense.currency;
  form.expense_date = expense.expense_date;
  form.status = expense.status;
  form.notes = expense.notes || '';
  isFormOpen.value = true;
};

const cancelForm = () => {
  isFormOpen.value = false;
  editingExpense.value = null;
  form.reset();
};

const submitForm = () => {
  const url = editingExpense.value
    ? route('forfaiting.expenses.update-status', editingExpense.value.id)
    : route('forfaiting.expenses.store');

  const method = editingExpense.value ? 'put' : 'post';

  form[method](url, {
    preserveScroll: true,
    onSuccess: () => {
      cancelForm();
    },
  });
};

const updateStatus = (id: number, status: string) => {
  router.put(
    route('forfaiting.expenses.update-status', id),
    { status },
    {
      preserveScroll: true,
    }
  );
};

const deleteExpense = (id: number) => {
  if (confirm('Are you sure you want to delete this expense?')) {
    router.delete(route('forfaiting.expenses.destroy', id), {
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
