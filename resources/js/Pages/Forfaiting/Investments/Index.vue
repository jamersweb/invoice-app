<template>
    <Head title="Investments" />
    <AuthenticatedLayout>
        <div class="p-6 space-y-6">
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h1 class="text-3xl font-bold text-white">Investments</h1>
                    <p class="text-slate-400 mt-1">Manage investor capital contributions</p>
                </div>
                <div class="flex gap-3">
                    <button
                        @click="showAddForm = !showAddForm"
                        class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition-colors"
                    >
                        + Add Investment
                    </button>
                    <a
                        :href="route('forfaiting.investments.export')"
                        class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg font-medium transition-colors"
                    >
                        Export Excel
                    </a>
                </div>
            </div>

            <!-- Add Investment Form -->
            <div v-if="showAddForm" class="bg-slate-800/30 border border-slate-700 rounded-lg p-6 mb-6">
                <h2 class="text-xl font-semibold text-white mb-4">Add New Investment</h2>
                <form @submit.prevent="submit">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-300 mb-2">Investor Name</label>
                            <input
                                v-model="form.name"
                                type="text"
                                required
                                class="w-full px-3 py-2 bg-slate-700 border border-slate-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                            />
                            <div v-if="form.errors.name" class="text-red-400 text-sm mt-1">{{ form.errors.name }}</div>
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
                            <div v-if="form.errors.amount" class="text-red-400 text-sm mt-1">{{ form.errors.amount }}</div>
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
                                <option value="EUR">EUR</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-300 mb-2">Date</label>
                            <input
                                v-model="form.date"
                                type="date"
                                required
                                class="w-full px-3 py-2 bg-slate-700 border border-slate-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                            />
                            <div v-if="form.errors.date" class="text-red-400 text-sm mt-1">{{ form.errors.date }}</div>
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
                            {{ form.processing ? 'Saving...' : 'Save Investment' }}
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

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
                <div class="bg-gradient-to-br from-blue-800 to-blue-900 border border-blue-700 rounded-lg p-4">
                    <p class="text-blue-200 text-sm font-medium mb-1">Total Investments</p>
                    <p class="text-white text-2xl font-bold">{{ formatCurrency(totalAmount) }}</p>
                </div>
                <div class="bg-gradient-to-br from-green-800 to-green-900 border border-green-700 rounded-lg p-4">
                    <p class="text-green-200 text-sm font-medium mb-1">Total Investors</p>
                    <p class="text-white text-2xl font-bold">{{ uniqueInvestors }}</p>
                </div>
                <div class="bg-gradient-to-br from-purple-800 to-purple-900 border border-purple-700 rounded-lg p-4">
                    <p class="text-purple-200 text-sm font-medium mb-1">Average Investment</p>
                    <p class="text-white text-2xl font-bold">{{ formatCurrency(averageAmount) }}</p>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-slate-800/30 border border-slate-700 rounded-lg p-4 mb-6">
                <div class="flex flex-wrap gap-4">
                    <input
                        v-model="filters.search"
                        type="text"
                        placeholder="Search by investor name..."
                        class="px-3 py-2 bg-slate-700 border border-slate-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                    />
                    <select
                        v-model="filters.investor"
                        class="px-3 py-2 bg-slate-700 border border-slate-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >
                        <option value="">All Investors</option>
                        <option v-for="investor in investorList" :key="investor" :value="investor">{{ investor }}</option>
                    </select>
                </div>
            </div>

            <!-- Investments Table -->
            <div class="bg-slate-800/30 border border-slate-700 rounded-lg overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-slate-800/50">
                            <tr>
                                <th class="text-left text-slate-300 py-3 px-4">Investor Name</th>
                                <th class="text-right text-slate-300 py-3 px-4">Amount</th>
                                <th class="text-left text-slate-300 py-3 px-4">Currency</th>
                                <th class="text-left text-slate-300 py-3 px-4">Date</th>
                                <th class="text-left text-slate-300 py-3 px-4">Notes</th>
                                <th class="text-right text-slate-300 py-3 px-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="investment in filteredInvestments" :key="investment.id" class="border-t border-slate-700/50">
                                <td class="text-white py-3 px-4 font-medium">{{ investment.name }}</td>
                                <td class="text-right text-purple-400 font-mono py-3 px-4">{{ formatCurrency(investment.amount) }}</td>
                                <td class="text-slate-300 py-3 px-4">{{ investment.currency }}</td>
                                <td class="text-slate-300 py-3 px-4">{{ formatDate(investment.date) }}</td>
                                <td class="text-slate-400 py-3 px-4">{{ investment.notes || '-' }}</td>
                                <td class="text-right py-3 px-4">
                                    <button
                                        @click="deleteInvestment(investment.id)"
                                        class="text-red-400 hover:text-red-300 transition-colors"
                                    >
                                        Delete
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="filteredInvestments.length === 0">
                                <td colspan="6" class="text-center text-slate-400 py-8">No investments found</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="investments.links" class="bg-slate-800/50 px-4 py-3 border-t border-slate-700">
                    <div class="flex justify-between items-center">
                        <div class="text-sm text-slate-400">
                            Showing {{ investments.from }} to {{ investments.to }} of {{ investments.total }} investments
                        </div>
                        <div class="flex gap-2">
                            <a
                                v-for="link in investments.links"
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
import { Head, useForm, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { ref, computed } from 'vue';
import { route } from 'ziggy-js';

interface Investment {
    id: number;
    name: string;
    amount: number;
    currency: string;
    date: string;
    notes: string | null;
}

interface Props {
    investments: {
        data: Investment[];
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
    investor: '',
});

const form = useForm({
    name: '',
    amount: '',
    currency: 'AED',
    date: '',
    notes: '',
});

const submit = () => {
    form.post(route('forfaiting.investments.store'), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            showAddForm.value = false;
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

const filteredInvestments = computed(() => {
    let filtered = props.investments.data;

    if (filters.value.search) {
        filtered = filtered.filter(inv => 
            inv.name.toLowerCase().includes(filters.value.search.toLowerCase())
        );
    }

    if (filters.value.investor) {
        filtered = filtered.filter(inv => inv.name === filters.value.investor);
    }

    return filtered;
});

const investorList = computed(() => {
    return [...new Set(props.investments.data.map(inv => inv.name))];
});

const totalAmount = computed(() => {
    return props.investments.data.reduce((sum, inv) => sum + parseFloat(inv.amount.toString()), 0);
});

const uniqueInvestors = computed(() => {
    return investorList.value.length;
});

const averageAmount = computed(() => {
    return uniqueInvestors.value > 0 ? totalAmount.value / uniqueInvestors.value : 0;
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

