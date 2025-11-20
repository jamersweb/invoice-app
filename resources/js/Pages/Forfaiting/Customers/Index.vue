<template>
    <Head title="Customers" />
    <AuthenticatedLayout>
        <div class="p-6 space-y-6">
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h1 class="text-3xl font-bold text-white">Customers</h1>
                    <p class="text-slate-400 mt-1">Manage customer profiles and relationships</p>
                </div>
                <button
                    @click="showAddForm = !showAddForm"
                    class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition-colors"
                >
                    + Add Customer
                </button>
            </div>

            <!-- Add Customer Form -->
            <div v-if="showAddForm" class="bg-slate-800/30 border border-slate-700 rounded-lg p-6 mb-6">
                <h2 class="text-xl font-semibold text-white mb-4">Add New Customer</h2>
                <form @submit.prevent="submit">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-300 mb-2">Name</label>
                            <input
                                v-model="form.name"
                                type="text"
                                required
                                class="w-full px-3 py-2 bg-slate-700 border border-slate-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-300 mb-2">Email</label>
                            <input
                                v-model="form.email"
                                type="email"
                                class="w-full px-3 py-2 bg-slate-700 border border-slate-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-300 mb-2">Phone</label>
                            <input
                                v-model="form.phone"
                                type="text"
                                class="w-full px-3 py-2 bg-slate-700 border border-slate-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-300 mb-2">Company Name</label>
                            <input
                                v-model="form.company_name"
                                type="text"
                                class="w-full px-3 py-2 bg-slate-700 border border-slate-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-300 mb-2">Country</label>
                            <input
                                v-model="form.country"
                                type="text"
                                class="w-full px-3 py-2 bg-slate-700 border border-slate-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-300 mb-2">City</label>
                            <input
                                v-model="form.city"
                                type="text"
                                class="w-full px-3 py-2 bg-slate-700 border border-slate-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                            />
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-slate-300 mb-2">Address</label>
                            <textarea
                                v-model="form.address"
                                rows="2"
                                class="w-full px-3 py-2 bg-slate-700 border border-slate-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                            ></textarea>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-300 mb-2">Status</label>
                            <select
                                v-model="form.status"
                                required
                                class="w-full px-3 py-2 bg-slate-700 border border-slate-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                            >
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                    <div class="flex gap-3 mt-4">
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition-colors disabled:opacity-50"
                        >
                            {{ form.processing ? 'Saving...' : 'Save Customer' }}
                        </button>
                        <button
                            type="button"
                            @click="showAddForm = false; form.reset()"
                            class="px-4 py-2 bg-slate-600 hover:bg-slate-700 text-white rounded-lg font-medium transition-colors"
                        >
                            Cancel
                        </button>
                    </div>
                </form>
            </div>

            <!-- Filters -->
            <div class="bg-slate-800/30 border border-slate-700 rounded-lg p-4 mb-6">
                <div class="flex flex-wrap gap-4">
                    <input
                        v-model="filters.search"
                        type="text"
                        placeholder="Search customers..."
                        class="px-3 py-2 bg-slate-700 border border-slate-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                    />
                    <select
                        v-model="filters.status"
                        class="px-3 py-2 bg-slate-700 border border-slate-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >
                        <option value="">All Status</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
            </div>

            <!-- Customers Table -->
            <div class="bg-slate-800/30 border border-slate-700 rounded-lg overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-slate-800/50">
                            <tr>
                                <th class="text-left text-slate-300 py-3 px-4">Name</th>
                                <th class="text-left text-slate-300 py-3 px-4">Company</th>
                                <th class="text-left text-slate-300 py-3 px-4">Email</th>
                                <th class="text-left text-slate-300 py-3 px-4">Phone</th>
                                <th class="text-left text-slate-300 py-3 px-4">Location</th>
                                <th class="text-center text-slate-300 py-3 px-4">Transactions</th>
                                <th class="text-center text-slate-300 py-3 px-4">Status</th>
                                <th class="text-right text-slate-300 py-3 px-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="customer in filteredCustomers" :key="customer.id" class="border-t border-slate-700/50">
                                <td class="text-white font-medium py-3 px-4">{{ customer.name }}</td>
                                <td class="text-white py-3 px-4">{{ customer.company_name || '-' }}</td>
                                <td class="text-slate-300 py-3 px-4">{{ customer.email || '-' }}</td>
                                <td class="text-slate-300 py-3 px-4">{{ customer.phone || '-' }}</td>
                                <td class="text-slate-300 py-3 px-4">{{ [customer.city, customer.country].filter(Boolean).join(', ') || '-' }}</td>
                                <td class="text-center text-slate-300 py-3 px-4">{{ customer.transactions_count || 0 }}</td>
                                <td class="text-center py-3 px-4">
                                    <span :class="customer.status === 'active' ? 'bg-green-500/20 text-green-400' : 'bg-red-500/20 text-red-400'"
                                          class="px-2 py-1 rounded text-xs">
                                        {{ customer.status }}
                                    </span>
                                </td>
                                <td class="text-right py-3 px-4">
                                    <a
                                        :href="route('forfaiting.customers.show', customer.id)"
                                        class="text-blue-400 hover:text-blue-300 transition-colors"
                                    >
                                        View
                                    </a>
                                </td>
                            </tr>
                            <tr v-if="filteredCustomers.length === 0">
                                <td colspan="8" class="text-center text-slate-400 py-8">No customers found</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="customers.links" class="bg-slate-800/50 px-4 py-3 border-t border-slate-700">
                    <div class="flex justify-between items-center">
                        <div class="text-sm text-slate-400">
                            Showing {{ customers.from }} to {{ customers.to }} of {{ customers.total }} customers
                        </div>
                        <div class="flex gap-2">
                            <a
                                v-for="link in customers.links"
                                :key="link.label"
                                :href="link.url"
                                v-html="link.label"
                                :class="[
                                    'px-3 py-1 rounded text-sm',
                                    link.active ? 'bg-blue-600 text-white' : 'bg-slate-700 text-slate-300 hover:bg-slate-600',
                                    !link.url ? 'opacity-50 cursor-not-allowed' : 'cursor-pointer'
                                ]"
                            ></a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { ref, computed } from 'vue';
import { route } from 'ziggy-js';

interface Customer {
    id: number;
    name: string;
    email: string | null;
    phone: string | null;
    company_name: string | null;
    city: string | null;
    country: string | null;
    status: string;
    transactions_count?: number;
}

interface Props {
    customers: {
        data: Customer[];
        links: Array<{ label: string; url: string | null; active: boolean }>;
        from: number;
        to: number;
        total: number;
    };
}

const props = defineProps<Props>();

const showAddForm = ref(false);
const filters = ref({
    search: '',
    status: '',
});

const form = useForm({
    name: '',
    email: '',
    phone: '',
    company_name: '',
    address: '',
    country: '',
    city: '',
    status: 'active',
});

const submit = () => {
    form.post(route('forfaiting.customers.store'), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            showAddForm.value = false;
        },
    });
};

const filteredCustomers = computed(() => {
    let filtered = props.customers.data;

    if (filters.value.search) {
        const search = filters.value.search.toLowerCase();
        filtered = filtered.filter(c => 
            c.name.toLowerCase().includes(search) ||
            (c.email && c.email.toLowerCase().includes(search)) ||
            (c.company_name && c.company_name.toLowerCase().includes(search))
        );
    }

    if (filters.value.status) {
        filtered = filtered.filter(c => c.status === filters.value.status);
    }

    return filtered;
});
</script>

