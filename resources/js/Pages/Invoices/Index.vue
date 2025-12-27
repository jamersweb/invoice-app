<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import Badge from '@/Components/Badge.vue';
import DarkInput from '@/Components/DarkInput.vue';
import GradientButton from '@/Components/GradientButton.vue';
import { ref, computed, onMounted } from 'vue';
import { usePage, router } from '@inertiajs/vue3';

const page = usePage();
const supplier = computed(() => (page.props as any).auth?.supplier);

onMounted(() => {
    if (supplier.value && supplier.value.kyb_status !== 'approved') {
        router.visit(route('onboarding.kyc'));
    }
});

const props = defineProps<{
    invoices: Array<{
        id: number;
        invoice_number: string;
        customer_name?: string;
        customer_avatar?: string;
        amount: number;
        currency: string;
        status: string;
        due_date: string;
        created_at: string;
    }>;
}>();

const searchQuery = ref('');

const filteredInvoices = computed(() => {
    if (!searchQuery.value) return props.invoices;
    const q = searchQuery.value.toLowerCase();
    return props.invoices.filter(i => 
        i.invoice_number.toLowerCase().includes(q)
    );
});

function getStatusBadge(status: string) {
    const statusMap: Record<string, 'success' | 'warning' | 'danger'> = {
        paid: 'success',
        partially_paid: 'warning',
        pending: 'warning',
        overdue: 'danger',
    };
    return statusMap[status] || 'info';
}

function formatCurrency(amount: number, currencyCode: string = 'AED') {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: currencyCode || 'AED',
        minimumFractionDigits: 0,
    }).format(amount);
}
</script>

<template>

    <Head title="Invoices" />

    <AuthenticatedLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-dark-text-primary">Invoices</h1>
                </div>
                <div class="flex items-center gap-4">
                    <Link :href="route('invoices.submit')">
                        <GradientButton size="md">New Invoice</GradientButton>
                    </Link>
                </div>
            </div>

            <!-- Search and Filters -->
            <div class="card">
                <div class="flex items-center gap-4">
                    <div class="flex-1">
                        <DarkInput v-model="searchQuery" placeholder="Search invoices..." icon="search"
                            class="!pr-10" />
                    </div>
                </div>
            </div>

            <!-- Invoices Table -->
            <div class="card overflow-hidden p-0">
                <div class="overflow-x-auto custom-scrollbar">
                    <table class="table-dark bg-none">
                        <thead>
                            <tr>
                                <th class="w-24">ID</th>
                                <th>Created On</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Due Date</th>
                                <th class="w-32 text-right px-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="invoice in filteredInvoices" :key="invoice.id">
                                <td class="font-medium text-purple-accent">{{ invoice.invoice_number }}</td>
                                <td class="text-dark-text-secondary">
                                    {{ new Date(invoice.created_at).toLocaleDateString() }}
                                </td>
                                <td>{{ formatCurrency(invoice.amount, invoice.currency) }}</td>
                                <td>
                                    <Badge :variant="getStatusBadge(invoice.status)">
                                        {{ invoice.status.replace('_', ' ').toUpperCase() }}
                                    </Badge>
                                </td>
                                <td class="text-dark-text-secondary">{{ new Date(invoice.due_date).toLocaleDateString() }}</td>
                                <td class="px-4 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <Link :href="route('invoices.show', invoice.id)" 
                                              class="p-1.5 hover:bg-dark-tertiary rounded transition-colors text-dark-text-secondary hover:text-purple-accent"
                                              title="View">
                                            <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                        </Link>
                                        <Link v-if="invoice.status === 'draft' || invoice.status === 'under_review'"
                                              :href="route('invoices.edit', invoice.id)" 
                                              class="p-1.5 hover:bg-dark-tertiary rounded transition-colors text-dark-text-secondary hover:text-purple-accent"
                                              title="Edit">
                                            <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                        </Link>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="filteredInvoices.length > 0" class="border-t border-dark-border px-4 py-3 flex items-center justify-between bg-dark-secondary/10">
                    <div class="text-sm text-dark-text-secondary">
                        Showing {{ filteredInvoices.length }} of {{ props.invoices.length }} results
                    </div>
                    <div class="flex items-center gap-2">
                        <button
                            class="px-3 py-1.5 rounded-lg bg-dark-secondary hover:bg-dark-tertiary text-dark-text-primary text-sm transition-colors border border-dark-border">
                            Previous
                        </button>
                        <button class="px-3 py-1.5 rounded-lg bg-purple-accent text-white text-sm font-medium">
                            1
                        </button>
                        <button
                            class="px-3 py-1.5 rounded-lg bg-dark-secondary hover:bg-dark-tertiary text-dark-text-primary text-sm transition-colors border border-dark-border">
                            Next
                        </button>
                    </div>
                </div>
                <div v-else class="px-4 py-12 text-center text-dark-text-muted">
                    No invoices found.
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
