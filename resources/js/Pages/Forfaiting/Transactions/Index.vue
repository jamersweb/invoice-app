<template>
    <Head title="Transactions" />
    <AuthenticatedLayout>
        <div class="p-6 space-y-6">
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h1 class="text-3xl font-bold text-white">Transactions</h1>
                    <p class="text-slate-400 mt-1">Manage forfaiting transactions and deals</p>
                </div>
                <button
                    @click="showAddForm = !showAddForm"
                    class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition-colors"
                >
                    + Add Transaction
                </button>
            </div>

            <!-- Add Transaction Form -->
            <div v-if="showAddForm" class="bg-slate-800/30 border border-slate-700 rounded-lg p-6 mb-6">
                <h2 class="text-xl font-semibold text-white mb-4">Add New Transaction</h2>
                <form @submit.prevent="submit">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-300 mb-2">Transaction Number</label>
                            <input
                                v-model="form.transaction_number"
                                type="text"
                                required
                                class="w-full px-3 py-2 bg-slate-700 border border-slate-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                            />
                            <div v-if="form.errors.transaction_number" class="text-red-400 text-sm mt-1">{{ form.errors.transaction_number }}</div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-300 mb-2">Customer</label>
                            <input
                                v-model="form.customer"
                                type="text"
                                required
                                class="w-full px-3 py-2 bg-slate-700 border border-slate-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-300 mb-2">Date of Transaction</label>
                            <input
                                v-model="form.date_of_transaction"
                                type="date"
                                required
                                class="w-full px-3 py-2 bg-slate-700 border border-slate-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-300 mb-2">Net Amount</label>
                            <input
                                v-model="form.net_amount"
                                type="number"
                                step="0.01"
                                required
                                class="w-full px-3 py-2 bg-slate-700 border border-slate-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-300 mb-2">Profit Margin (%)</label>
                            <input
                                v-model="form.profit_margin"
                                type="number"
                                step="0.01"
                                required
                                class="w-full px-3 py-2 bg-slate-700 border border-slate-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-300 mb-2">Disbursement Charges</label>
                            <input
                                v-model="form.disbursement_charges"
                                type="number"
                                step="0.01"
                                class="w-full px-3 py-2 bg-slate-700 border border-slate-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-300 mb-2">Sales Cycle (Days)</label>
                            <input
                                v-model="form.sales_cycle"
                                type="number"
                                required
                                class="w-full px-3 py-2 bg-slate-700 border border-slate-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-300 mb-2">Status</label>
                            <select
                                v-model="form.status"
                                required
                                class="w-full px-3 py-2 bg-slate-700 border border-slate-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                            >
                                <option value="Ongoing">Ongoing</option>
                                <option value="Ended">Ended</option>
                            </select>
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-slate-300 mb-2">Notes</label>
                            <textarea
                                v-model="form.notes"
                                rows="3"
                                class="w-full px-3 py-2 bg-slate-700 border border-slate-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                            ></textarea>
                        </div>
                    </div>
                    <div class="flex gap-3 mt-4">
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition-colors disabled:opacity-50"
                        >
                            {{ form.processing ? 'Saving...' : 'Save Transaction' }}
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
                        placeholder="Search by transaction number or customer..."
                        class="px-3 py-2 bg-slate-700 border border-slate-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                    />
                    <select
                        v-model="filters.status"
                        class="px-3 py-2 bg-slate-700 border border-slate-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >
                        <option value="">All Status</option>
                        <option value="Ongoing">Ongoing</option>
                        <option value="Ended">Ended</option>
                    </select>
                </div>
            </div>

            <!-- Transactions Table -->
            <div class="bg-slate-800/30 border border-slate-700 rounded-lg overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-slate-800/50">
                            <tr>
                                <th class="text-left text-slate-300 py-3 px-4">Transaction #</th>
                                <th class="text-left text-slate-300 py-3 px-4">Customer</th>
                                <th class="text-left text-slate-300 py-3 px-4">Date</th>
                                <th class="text-right text-slate-300 py-3 px-4">Net Amount</th>
                                <th class="text-right text-slate-300 py-3 px-4">Profit Margin</th>
                                <th class="text-right text-slate-300 py-3 px-4">Sales Cycle</th>
                                <th class="text-center text-slate-300 py-3 px-4">Status</th>
                                <th class="text-right text-slate-300 py-3 px-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="transaction in filteredTransactions" :key="transaction.id" class="border-t border-slate-700/50">
                                <td class="text-white py-3 px-4 font-medium">{{ transaction.transaction_number }}</td>
                                <td class="text-white py-3 px-4">{{ transaction.customer }}</td>
                                <td class="text-slate-300 py-3 px-4">{{ formatDate(transaction.date_of_transaction) }}</td>
                                <td class="text-right text-purple-400 font-mono py-3 px-4">{{ formatCurrency(transaction.net_amount) }}</td>
                                <td class="text-right text-amber-400 py-3 px-4">{{ transaction.profit_margin }}%</td>
                                <td class="text-right text-slate-300 py-3 px-4">{{ transaction.sales_cycle }} days</td>
                                <td class="text-center py-3 px-4">
                                    <span :class="transaction.status === 'Ongoing' ? 'bg-blue-500/20 text-blue-400' : 'bg-green-500/20 text-green-400'"
                                          class="px-2 py-1 rounded text-xs">
                                        {{ transaction.status }}
                                    </span>
                                </td>
                                <td class="text-right py-3 px-4">
                                    <button
                                        @click="deleteTransaction(transaction.id)"
                                        class="text-red-400 hover:text-red-300 transition-colors mr-3"
                                    >
                                        Delete
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="filteredTransactions.length === 0">
                                <td colspan="8" class="text-center text-slate-400 py-8">No transactions found</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="transactions.links" class="bg-slate-800/50 px-4 py-3 border-t border-slate-700">
                    <div class="flex justify-between items-center">
                        <div class="text-sm text-slate-400">
                            Showing {{ transactions.from }} to {{ transactions.to }} of {{ transactions.total }} transactions
                        </div>
                        <div class="flex gap-2">
                            <a
                                v-for="link in transactions.links"
                                :key="link.label"
                                :href="link.url ?? undefined"
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
import { Head, useForm, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { ref, computed } from 'vue';
import { route } from 'ziggy-js';

interface Transaction {
    id: number;
    transaction_number: string;
    customer: string;
    date_of_transaction: string;
    net_amount: number;
    profit_margin: number;
    disbursement_charges: number;
    sales_cycle: number;
    status: string;
}

interface Props {
    transactions: {
        data: Transaction[];
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
    transaction_number: '',
    customer: '',
    customer_id: null,
    date_of_transaction: '',
    net_amount: '',
    profit_margin: '',
    disbursement_charges: 0,
    sales_cycle: '',
    status: 'Ongoing',
    notes: '',
});

const submit = () => {
    form.post(route('forfaiting.transactions.store'), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            showAddForm.value = false;
        },
    });
};

const deleteTransaction = (id: number) => {
    if (confirm('Are you sure you want to delete this transaction?')) {
        router.delete(route('forfaiting.transactions.destroy', id), {
            preserveScroll: true,
        });
    }
};

const filteredTransactions = computed(() => {
    let filtered = props.transactions.data;

    if (filters.value.search) {
        const search = filters.value.search.toLowerCase();
        filtered = filtered.filter(t => 
            t.transaction_number.toLowerCase().includes(search) ||
            t.customer.toLowerCase().includes(search)
        );
    }

    if (filters.value.status) {
        filtered = filtered.filter(t => t.status === filters.value.status);
    }

    return filtered;
});

const formatCurrency = (value: number) => {
    return new Intl.NumberFormat('en-AE', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(value);
};

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
};
</script>

