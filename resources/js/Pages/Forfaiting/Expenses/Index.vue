<template>
    <Head title="Expenses" />
    <AuthenticatedLayout>
        <div class="p-6 space-y-6">
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h1 class="text-3xl font-bold text-white">Expenses</h1>
                    <p class="text-slate-400 mt-1">Track and manage operational expenses</p>
                </div>
                <button
                    @click="showAddForm = !showAddForm"
                    class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition-colors"
                >
                    + Add Expense
                </button>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-4 gap-4 mb-6">
                <div class="bg-gradient-to-br from-blue-800 to-blue-900 border border-blue-700 rounded-lg p-4">
                    <p class="text-blue-200 text-sm font-medium mb-1">Total Expenses</p>
                    <p class="text-white text-2xl font-bold">{{ formatCurrency(stats.total) }}</p>
                </div>
                <div class="bg-gradient-to-br from-yellow-800 to-yellow-900 border border-yellow-700 rounded-lg p-4">
                    <p class="text-yellow-200 text-sm font-medium mb-1">Pending</p>
                    <p class="text-white text-2xl font-bold">{{ formatCurrency(stats.pending) }}</p>
                </div>
                <div class="bg-gradient-to-br from-green-800 to-green-900 border border-green-700 rounded-lg p-4">
                    <p class="text-green-200 text-sm font-medium mb-1">Approved</p>
                    <p class="text-white text-2xl font-bold">{{ formatCurrency(stats.approved) }}</p>
                </div>
                <div class="bg-gradient-to-br from-red-800 to-red-900 border border-red-700 rounded-lg p-4">
                    <p class="text-red-200 text-sm font-medium mb-1">Rejected</p>
                    <p class="text-white text-2xl font-bold">{{ formatCurrency(stats.rejected) }}</p>
                </div>
            </div>

            <!-- Add Expense Form -->
            <div v-if="showAddForm" class="bg-slate-800/30 border border-slate-700 rounded-lg p-6 mb-6">
                <h2 class="text-xl font-semibold text-white mb-4">Add New Expense</h2>
                <form @submit.prevent="submit">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-300 mb-2">Category</label>
                            <select
                                v-model="form.category"
                                required
                                class="w-full px-3 py-2 bg-slate-700 border border-slate-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                            >
                                <option value="">Select Category</option>
                                <option value="Operational">Operational</option>
                                <option value="Marketing">Marketing</option>
                                <option value="Legal">Legal</option>
                                <option value="Technology">Technology</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-300 mb-2">Description</label>
                            <input
                                v-model="form.description"
                                type="text"
                                required
                                class="w-full px-3 py-2 bg-slate-700 border border-slate-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-300 mb-2">Amount</label>
                            <input
                                v-model="form.amount"
                                type="number"
                                step="0.01"
                                required
                                class="w-full px-3 py-2 bg-slate-700 border border-slate-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-300 mb-2">Currency</label>
                            <select
                                v-model="form.currency"
                                required
                                class="w-full px-3 py-2 bg-slate-700 border border-slate-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                            >
                                <option value="AED">AED</option>
                                <option value="USD">USD</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-300 mb-2">Expense Date</label>
                            <input
                                v-model="form.expense_date"
                                type="date"
                                required
                                class="w-full px-3 py-2 bg-slate-700 border border-slate-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                            />
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
                            {{ form.processing ? 'Saving...' : 'Save Expense' }}
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
                        placeholder="Search expenses..."
                        class="px-3 py-2 bg-slate-700 border border-slate-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                    />
                    <select
                        v-model="filters.status"
                        class="px-3 py-2 bg-slate-700 border border-slate-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >
                        <option value="">All Status</option>
                        <option value="Pending">Pending</option>
                        <option value="Approved">Approved</option>
                        <option value="Rejected">Rejected</option>
                    </select>
                    <select
                        v-model="filters.category"
                        class="px-3 py-2 bg-slate-700 border border-slate-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >
                        <option value="">All Categories</option>
                        <option value="Operational">Operational</option>
                        <option value="Marketing">Marketing</option>
                        <option value="Legal">Legal</option>
                        <option value="Technology">Technology</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
            </div>

            <!-- Expenses Table -->
            <div class="bg-slate-800/30 border border-slate-700 rounded-lg overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-slate-800/50">
                            <tr>
                                <th class="text-left text-slate-300 py-3 px-4">Category</th>
                                <th class="text-left text-slate-300 py-3 px-4">Description</th>
                                <th class="text-right text-slate-300 py-3 px-4">Amount</th>
                                <th class="text-left text-slate-300 py-3 px-4">Date</th>
                                <th class="text-center text-slate-300 py-3 px-4">Status</th>
                                <th class="text-right text-slate-300 py-3 px-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="expense in filteredExpenses" :key="expense.id" class="border-t border-slate-700/50">
                                <td class="text-white py-3 px-4">{{ expense.category }}</td>
                                <td class="text-white py-3 px-4">{{ expense.description }}</td>
                                <td class="text-right text-purple-400 font-mono py-3 px-4">{{ formatCurrency(expense.amount) }} {{ expense.currency }}</td>
                                <td class="text-slate-300 py-3 px-4">{{ formatDate(expense.expense_date) }}</td>
                                <td class="text-center py-3 px-4">
                                    <span :class="getStatusClass(expense.status)"
                                          class="px-2 py-1 rounded text-xs">
                                        {{ expense.status }}
                                    </span>
                                </td>
                                <td class="text-right py-3 px-4">
                                    <div class="flex justify-end gap-2">
                                        <button
                                            v-if="expense.status === 'Pending'"
                                            @click="updateStatus(expense.id, 'Approved')"
                                            class="text-green-400 hover:text-green-300 transition-colors"
                                        >
                                            Approve
                                        </button>
                                        <button
                                            v-if="expense.status === 'Pending'"
                                            @click="updateStatus(expense.id, 'Rejected')"
                                            class="text-red-400 hover:text-red-300 transition-colors"
                                        >
                                            Reject
                                        </button>
                                        <button
                                            @click="deleteExpense(expense.id)"
                                            class="text-red-400 hover:text-red-300 transition-colors"
                                        >
                                            Delete
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

                <!-- Pagination -->
                <div v-if="expenses.links" class="bg-slate-800/50 px-4 py-3 border-t border-slate-700">
                    <div class="flex justify-between items-center">
                        <div class="text-sm text-slate-400">
                            Showing {{ expenses.from }} to {{ expenses.to }} of {{ expenses.total }} expenses
                        </div>
                        <div class="flex gap-2">
                            <a
                                v-for="link in expenses.links"
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

interface Expense {
    id: number;
    category: string;
    description: string;
    amount: number;
    currency: string;
    expense_date: string;
    status: string;
}

interface Props {
    expenses: {
        data: Expense[];
        links: Array<{ label: string; url: string | null; active: boolean }>;
        from: number;
        to: number;
        total: number;
    };
    stats: {
        total: number;
        pending: number;
        approved: number;
        rejected: number;
    };
}

const props = defineProps<Props>();

const showAddForm = ref(false);
const filters = ref({
    search: '',
    status: '',
    category: '',
});

const form = useForm({
    category: '',
    description: '',
    amount: '',
    currency: 'AED',
    expense_date: '',
    notes: '',
});

const submit = () => {
    form.post(route('forfaiting.expenses.store'), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            showAddForm.value = false;
        },
    });
};

const updateStatus = (id: number, status: string) => {
    router.put(route('forfaiting.expenses.update-status', id), { status }, {
        preserveScroll: true,
    });
};

const deleteExpense = (id: number) => {
    if (confirm('Are you sure you want to delete this expense?')) {
        router.delete(route('forfaiting.expenses.destroy', id), {
            preserveScroll: true,
        });
    }
};

const filteredExpenses = computed(() => {
    let filtered = props.expenses.data;

    if (filters.value.search) {
        const search = filters.value.search.toLowerCase();
        filtered = filtered.filter(e => 
            e.description.toLowerCase().includes(search) ||
            e.category.toLowerCase().includes(search)
        );
    }

    if (filters.value.status) {
        filtered = filtered.filter(e => e.status === filters.value.status);
    }

    if (filters.value.category) {
        filtered = filtered.filter(e => e.category === filters.value.category);
    }

    return filtered;
});

const getStatusClass = (status: string) => {
    const classes: Record<string, string> = {
        'Pending': 'bg-yellow-500/20 text-yellow-400',
        'Approved': 'bg-green-500/20 text-green-400',
        'Rejected': 'bg-red-500/20 text-red-400',
    };
    return classes[status] || 'bg-slate-500/20 text-slate-400';
};

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

