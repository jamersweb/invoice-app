<template>

  <Head title="Customers" />
  <AuthenticatedLayout>
    <div class="p-6">
      <div class="max-w-[1800px] mx-auto">
        <!-- Header -->
        <div class="mb-8">
          <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <div class="flex items-center gap-3">
              <div class="p-3 bg-gradient-to-br from-indigo-600 to-purple-600 rounded-xl">
                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                </svg>
              </div>
              <div>
                <h2 class="text-3xl font-bold text-white">Customers</h2>
                <p class="text-slate-400 mt-1">Manage customer profiles and information</p>
              </div>
            </div>
            <button v-if="!isFormOpen" @click="isFormOpen = true"
              class="w-full sm:w-auto bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg flex items-center justify-center gap-2">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
              </svg>
              Add Customer
            </button>
          </div>
        </div>

        <!-- Form -->
        <div v-if="isFormOpen" class="mb-6 bg-slate-800/50 border border-slate-700 rounded-lg p-6">
          <div class="flex justify-between items-center mb-4">
            <h3 class="text-white text-lg font-semibold">
              {{ editingCustomer ? 'Edit Customer' : 'Add New Customer' }}
            </h3>
            <button @click="cancelForm" class="text-slate-400 hover:text-white">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
          <form @submit.prevent="submitForm" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-slate-300 mb-1">Name *</label>
                <input v-model="form.name" type="text" required
                  class="w-full rounded-lg border border-slate-600 bg-slate-900/50 p-2 text-white"
                  placeholder="Customer name" />
              </div>
              <div>
                <label class="block text-sm font-medium text-slate-300 mb-1">Company Name</label>
                <input v-model="form.company_name" type="text"
                  class="w-full rounded-lg border border-slate-600 bg-slate-900/50 p-2 text-white"
                  placeholder="Company name" />
              </div>
              <div>
                <label class="block text-sm font-medium text-slate-300 mb-1">Email</label>
                <input v-model="form.email" type="email"
                  class="w-full rounded-lg border border-slate-600 bg-slate-900/50 p-2 text-white"
                  placeholder="email@example.com" />
              </div>
              <div>
                <label class="block text-sm font-medium text-slate-300 mb-1">Phone</label>
                <input v-model="form.phone" type="text"
                  class="w-full rounded-lg border border-slate-600 bg-slate-900/50 p-2 text-white"
                  placeholder="+971 50 123 4567" />
              </div>
              <div>
                <label class="block text-sm font-medium text-slate-300 mb-1">City</label>
                <input v-model="form.city" type="text"
                  class="w-full rounded-lg border border-slate-600 bg-slate-900/50 p-2 text-white" placeholder="City" />
              </div>
              <div>
                <label class="block text-sm font-medium text-slate-300 mb-1">Country</label>
                <input v-model="form.country" type="text"
                  class="w-full rounded-lg border border-slate-600 bg-slate-900/50 p-2 text-white"
                  placeholder="Country" />
              </div>
              <div class="md:col-span-2">
                <label class="block text-sm font-medium text-slate-300 mb-1">Address</label>
                <input v-model="form.address" type="text"
                  class="w-full rounded-lg border border-slate-600 bg-slate-900/50 p-2 text-white"
                  placeholder="Full address" />
              </div>
              <div>
                <label class="block text-sm font-medium text-slate-300 mb-1">Status</label>
                <select v-model="form.status"
                  class="w-full rounded-lg border border-slate-600 bg-slate-900/50 p-2 text-white">
                  <option value="active">Active</option>
                  <option value="inactive">Inactive</option>
                </select>
              </div>
            </div>
            <div class="flex justify-end gap-3">
              <button type="button" @click="cancelForm"
                class="px-4 py-2 rounded-lg border border-slate-600 text-white hover:bg-slate-700">
                Cancel
              </button>
              <button type="submit" :disabled="form.processing"
                class="px-4 py-2 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white disabled:opacity-50">
                {{ form.processing ? 'Saving...' : (editingCustomer ? 'Update' : 'Create') }}
              </button>
            </div>
          </form>
        </div>

        <!-- Search -->
        <div class="mb-6">
          <input v-model="filters.search" type="text" placeholder="Search customers..."
            class="w-full max-w-md rounded-lg border border-slate-600 bg-slate-800/50 p-2 text-white placeholder-slate-400" />
        </div>

        <!-- Customers Table -->
        <div class="bg-slate-800/30 border border-slate-700 rounded-lg overflow-hidden">
          <div class="overflow-x-auto custom-scrollbar">
            <table class="w-full">
              <thead class="bg-slate-800/50 border-b border-slate-700">
                <tr>
                  <th class="text-left text-slate-300 text-xs p-3">Customer Name</th>
                  <th class="text-left text-slate-300 text-xs p-3">Email</th>
                  <th class="text-left text-slate-300 text-xs p-3">Phone</th>
                  <th class="text-center text-slate-300 text-xs p-3">Transactions</th>
                  <th class="text-right text-slate-300 text-xs p-3">Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="customer in filteredCustomers" :key="customer.id"
                  class="border-b border-slate-700/50 hover:bg-slate-800/30">
                  <td class="text-white font-medium text-xs p-3">{{ customer.name }}</td>
                  <td class="text-slate-300 text-xs p-3">{{ customer.email || '-' }}</td>
                  <td class="text-slate-300 text-xs p-3">{{ customer.phone || '-' }}</td>
                  <td class="text-center p-3">
                    <span class="px-2 py-1 rounded text-xs bg-blue-500/20 text-blue-400 border border-blue-500/50">
                      {{ customer.transactions_count || 0 }}
                    </span>
                  </td>
                  <td class="text-right p-3">
                    <div class="flex justify-end gap-2">
                      <Link :href="route('forfaiting.customers.show', customer.id)"
                        class="text-indigo-400 hover:text-indigo-300">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                        </svg>
                      </Link>
                      <button @click="editCustomer(customer)" class="text-blue-400 hover:text-blue-300">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                      </button>
                    </div>
                  </td>
                </tr>
                <tr v-if="filteredCustomers.length === 0">
                  <td colspan="5" class="text-center text-slate-400 py-8">No customers found</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Pagination -->
        <div v-if="customers.links && customers.links.length > 3" class="mt-6 flex justify-center">
          <div class="flex gap-2">
            <Link v-for="link in customers.links" :key="link.label" :href="link.url || '#'" v-html="link.label" :class="`px-3 py-2 rounded-lg ${link.active
              ? 'bg-indigo-600 text-white'
              : 'bg-slate-800/50 text-slate-300 hover:bg-slate-700'
              } ${!link.url ? 'opacity-50 cursor-not-allowed' : ''}`" />
          </div>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>

<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { ref, computed } from 'vue';

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
  transactions_count?: number;
}

interface Props {
  customers: {
    data: Customer[];
    links: any[];
  };
}

const props = defineProps<Props>();

const isFormOpen = ref(false);
const editingCustomer = ref<Customer | null>(null);

const form = useForm({
  name: '',
  email: '',
  phone: '',
  company_name: '',
  address: '',
  city: '',
  country: '',
  status: 'active',
});

const filters = ref({
  search: '',
});

const filteredCustomers = computed(() => {
  let result = props.customers.data;

  if (filters.value.search) {
    const search = filters.value.search.toLowerCase();
    result = result.filter(
      (c) =>
        c.name.toLowerCase().includes(search) ||
        (c.email && c.email.toLowerCase().includes(search)) ||
        (c.company_name && c.company_name.toLowerCase().includes(search))
    );
  }

  return result;
});

const editCustomer = (customer: Customer) => {
  editingCustomer.value = customer;
  form.name = customer.name;
  form.email = customer.email || '';
  form.phone = customer.phone || '';
  form.company_name = customer.company_name || '';
  form.address = customer.address || '';
  form.city = customer.city || '';
  form.country = customer.country || '';
  form.status = customer.status;
  isFormOpen.value = true;
};

const cancelForm = () => {
  isFormOpen.value = false;
  editingCustomer.value = null;
  form.reset();
};

const submitForm = () => {
  const url = editingCustomer.value
    ? route('forfaiting.customers.update', editingCustomer.value.id)
    : route('forfaiting.customers.store');

  const method = editingCustomer.value ? 'put' : 'post';

  form[method](url, {
    preserveScroll: true,
    onSuccess: () => {
      cancelForm();
    },
  });
};
</script>
