<template>
    <Head title="Profit Allocations" />
    <AuthenticatedLayout>
        <div class="p-6 space-y-6">
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h1 class="text-3xl font-bold text-white">Profit Allocations</h1>
                    <p class="text-slate-400 mt-1">Manage profit distribution to investors</p>
                </div>
                <div class="flex gap-3">
                    <button
                        @click="showAddForm = !showAddForm"
                        class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition-colors"
                    >
                        + Add Allocation
                    </button>
                    <button
                        @click="recalculate"
                        class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg font-medium transition-colors"
                    >
                        Recalculate All
                    </button>
                </div>
            </div>

            <!-- Add Allocation Form -->
            <div v-if="showAddForm" class="bg-slate-800/30 border border-slate-700 rounded-lg p-6 mb-6">
                <h2 class="text-xl font-semibold text-white mb-4">Add New Profit Allocation</h2>
                <form @submit.prevent="submit">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-300 mb-2">Transaction</label>
                            <select
                                v-model="form.transaction_id"
                                required
                                class="w-full px-3 py-2 bg-slate-700 border border-slate-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                            >
                                <option value="">Select Transaction</option>
                                <option v-for="txn in transactions" :key="txn.id" :value="txn.id">
                                    {{ txn.transaction_number }} - {{ txn.customer }}
                                </option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-300 mb-2">Investor Name</label>
                            <input
                                v-model="form.investor_name"
                                type="text"
                                required
                                class="w-full px-3 py-2 bg-slate-700 border border-slate-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-300 mb-2">Individual Profit</label>
                            <input
                                v-model="form.individual_profit"
                                type="number"
                                step="0.01"
                                required
                                class="w-full px-3 py-2 bg-slate-700 border border-slate-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-300 mb-2">Profit Percentage</label>
                            <input
                                v-model="form.profit_percentage"
                                type="number"
                                step="0.01"
                                required
                                class="w-full px-3 py-2 bg-slate-700 border border-slate-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-300 mb-2">Deal Status</label>
                            <select
                                v-model="form.deal_status"
                                required
                                class="w-full px-3 py-2 bg-slate-700 border border-slate-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                            >
                                <option value="Ongoing">Ongoing</option>
                                <option value="Ended">Ended</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-300 mb-2">Allocation Date</label>
                            <input
                                v-model="form.allocation_date"
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
                            {{ form.processing ? 'Saving...' : 'Save Allocation' }}
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
                    <select
                        v-model="filters.investor"
                        class="px-3 py-2 bg-slate-700 border border-slate-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >
                        <option value="">All Investors</option>
                        <option v-for="investor in investorList" :key="investor" :value="investor">{{ investor }}</option>
                    </select>
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

            <!-- Allocations Table -->
            <div class="bg-slate-800/30 border border-slate-700 rounded-lg overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-slate-800/50">
                            <tr>
                                <th class="text-left text-slate-300 py-3 px-4">Transaction</th>
                                <th class="text-left text-slate-300 py-3 px-4">Investor</th>
                                <th class="text-right text-slate-300 py-3 px-4">Individual Profit</th>
                                <th class="text-right text-slate-300 py-3 px-4">Profit %</th>
                                <th class="text-center text-slate-300 py-3 px-4">Deal Status</th>
                                <th class="text-left text-slate-300 py-3 px-4">Allocation Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="allocation in filteredAllocations" :key="allocation.id" class="border-t border-slate-700/50">
                                <td class="text-white py-3 px-4">{{ allocation.transaction?.transaction_number || 'N/A' }}</td>
                                <td class="text-white font-medium py-3 px-4">{{ allocation.investor_name }}</td>
                                <td class="text-right text-emerald-400 font-mono py-3 px-4">{{ formatCurrency(allocation.individual_profit) }}</td>
                                <td class="text-right text-amber-400 py-3 px-4">{{ allocation.profit_percentage }}%</td>
                                <td class="text-center py-3 px-4">
                                    <span :class="allocation.deal_status === 'Ongoing' ? 'bg-blue-500/20 text-blue-400' : 'bg-green-500/20 text-green-400'"
                                          class="px-2 py-1 rounded text-xs">
                                        {{ allocation.deal_status }}
                                    </span>
                                </td>
                                <td class="text-slate-300 py-3 px-4">{{ formatDate(allocation.allocation_date) }}</td>
                            </tr>
                            <tr v-if="filteredAllocations.length === 0">
                                <td colspan="6" class="text-center text-slate-400 py-8">No allocations found</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="allocations.links" class="bg-slate-800/50 px-4 py-3 border-t border-slate-700">
                    <div class="flex justify-between items-center">
                        <div class="text-sm text-slate-400">
                            Showing {{ allocations.from }} to {{ allocations.to }} of {{ allocations.total }} allocations
                        </div>
                        <div class="flex gap-2">
                            <a
                                v-for="link in allocations.links"
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

interface Allocation {
    id: number;
    transaction_id: number;
    investor_name: string;
    individual_profit: number;
    profit_percentage: number;
    deal_status: string;
    allocation_date: string;
    transaction?: {
        transaction_number: string;
    };
}

interface Props {
    allocations: {
        data: Allocation[];
        links: Array<{ label: string; url: string | null; active: boolean }>;
        from: number;
        to: number;
        total: number;
    };
    transactions?: Array<{
        id: number;
        transaction_number: string;
        customer: string;
    }>;
}

const props = defineProps<Props>();

const showAddForm = ref(false);
const filters = ref({
    investor: '',
    status: '',
});

const form = useForm({
    transaction_id: '',
    investor_name: '',
    individual_profit: '',
    profit_percentage: '',
    deal_status: 'Ongoing',
    allocation_date: '',
    notes: '',
});

const submit = () => {
    form.post(route('forfaiting.profit-allocations.store'), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            showAddForm.value = false;
        },
    });
};

const recalculate = () => {
    if (confirm('This will recalculate all profit allocations. Continue?')) {
        router.post(route('forfaiting.profit-allocations.recalculate'), {}, {
            preserveScroll: true,
        });
    }
};

const filteredAllocations = computed(() => {
    let filtered = props.allocations.data;

    if (filters.value.investor) {
        filtered = filtered.filter(a => a.investor_name === filters.value.investor);
    }

    if (filters.value.status) {
        filtered = filtered.filter(a => a.deal_status === filters.value.status);
    }

    return filtered;
});

const investorList = computed(() => {
    return [...new Set(props.allocations.data.map(a => a.investor_name))];
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

