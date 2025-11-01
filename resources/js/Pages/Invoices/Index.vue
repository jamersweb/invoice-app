<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import Badge from '@/Components/Badge.vue';
import DarkInput from '@/Components/DarkInput.vue';
import GradientButton from '@/Components/GradientButton.vue';
import { ref } from 'vue';

defineProps<{
    invoices?: Array<{
        id: number;
        invoice_number: string;
        customer_name?: string;
        customer_avatar?: string;
        amount: number;
        paid: number;
        status: string;
        payment_mode?: string;
        due_date: string;
        created_at: string;
    }>;
}>();

const searchQuery = ref('');
const dateRange = ref('');

const invoices = [
    { id: 1, invoice_number: 'INV-001', customer_name: 'John Doe', amount: 5000, paid: 5000, status: 'paid', payment_mode: 'Bank', due_date: '2024-12-01', created_at: '2024-11-01' },
    { id: 2, invoice_number: 'INV-002', customer_name: 'Jane Smith', amount: 3200, paid: 1600, status: 'partially_paid', payment_mode: 'Card', due_date: '2024-12-15', created_at: '2024-11-05' },
    { id: 3, invoice_number: 'INV-003', customer_name: 'Bob Johnson', amount: 7800, paid: 0, status: 'overdue', payment_mode: 'Bank', due_date: '2024-11-20', created_at: '2024-10-20' },
];

function getStatusBadge(status: string) {
    const statusMap: Record<string, 'success' | 'warning' | 'danger'> = {
        paid: 'success',
        partially_paid: 'warning',
        pending: 'warning',
        overdue: 'danger',
    };
    return statusMap[status] || 'info';
}

function formatCurrency(amount: number) {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
        minimumFractionDigits: 0,
    }).format(amount);
}
</script>

<template>
    <Head title="Invoices" />

    <AuthenticatedLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-dark-text-primary">Invoices</h1>
                </div>
                <div class="flex items-center gap-4">
                    <div class="relative w-[199px]">
                        <DarkInput
                            v-model="dateRange"
                            placeholder="Date Range"
                            icon="calendar"
                            class="!pr-10"
                        />
                    </div>
                    <Link :href="route('invoices.submit')">
                        <GradientButton size="md">New Invoice</GradientButton>
                    </Link>
                </div>
            </div>

            <!-- Search and Filters -->
            <div class="card">
                <div class="flex items-center gap-4">
                    <div class="flex-1">
                        <DarkInput
                            v-model="searchQuery"
                            placeholder="Search invoices..."
                            icon="search"
                            class="!pr-10"
                        />
                    </div>
                </div>
            </div>

            <!-- Invoices Table -->
            <div class="card overflow-hidden p-0">
                <div class="overflow-x-auto">
                    <table class="table-dark">
                        <thead>
                            <tr>
                                <th class="w-12">
                                    <input type="checkbox" class="rounded border-dark-border bg-dark-secondary text-purple-accent focus:ring-purple-accent" />
                                </th>
                                <th class="w-24">ID</th>
                                <th>Customer</th>
                                <th>Created On</th>
                                <th>Amount</th>
                                <th>Paid</th>
                                <th>Status</th>
                                <th>Payment Mode</th>
                                <th>Due Date</th>
                                <th class="w-24">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="invoice in invoices" :key="invoice.id">
                                <td>
                                    <input type="checkbox" class="rounded border-dark-border bg-dark-secondary text-purple-accent focus:ring-purple-accent" />
                                </td>
                                <td class="font-medium">{{ invoice.invoice_number }}</td>
                                <td>
                                    <div class="flex items-center gap-3">
                                        <div class="h-6 w-6 rounded-full bg-purple-accent flex items-center justify-center">
                                            <span class="text-xs text-white font-medium">
                                                {{ invoice.customer_name?.charAt(0) || 'U' }}
                                            </span>
                                        </div>
                                        <span>{{ invoice.customer_name || 'Unknown' }}</span>
                                    </div>
                                </td>
                                <td class="text-dark-text-secondary">{{ new Date(invoice.created_at).toLocaleDateString() }}</td>
                                <td>{{ formatCurrency(invoice.amount) }}</td>
                                <td>{{ formatCurrency(invoice.paid) }}</td>
                                <td>
                                    <Badge :variant="getStatusBadge(invoice.status)">
                                        {{ invoice.status.replace('_', ' ').toUpperCase() }}
                                    </Badge>
                                </td>
                                <td class="text-dark-text-secondary">{{ invoice.payment_mode || 'N/A' }}</td>
                                <td class="text-dark-text-secondary">{{ new Date(invoice.due_date).toLocaleDateString() }}</td>
                                <td>
                                    <div class="flex items-center gap-2">
                                        <button class="p-1.5 hover:bg-dark-tertiary rounded transition-colors">
                                            <svg width="16" height="16" fill="none" viewBox="0 0 16 16" class="text-dark-text-secondary">
                                                <path stroke="currentColor" stroke-width="1.5" d="M8 1.333v13.334M1.333 8h13.334"/>
                                            </svg>
                                        </button>
                                        <button class="p-1.5 hover:bg-dark-tertiary rounded transition-colors">
                                            <svg width="16" height="16" fill="none" viewBox="0 0 16 16" class="text-dark-text-secondary">
                                                <path stroke="currentColor" stroke-width="1.5" d="M11.333 2.667L5 9M11.333 2.667h-4v4h4v-4z"/>
                                            </svg>
                                        </button>
                                        <button class="p-1.5 hover:bg-dark-tertiary rounded transition-colors">
                                            <svg width="16" height="16" fill="none" viewBox="0 0 16 16" class="text-dark-text-secondary">
                                                <path stroke="currentColor" stroke-width="1.5" d="M2 4h12M6 8h4M4 12h8"/>
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="border-t border-dark-border px-4 py-3 flex items-center justify-between">
                    <div class="text-sm text-dark-text-secondary">
                        Showing 1 to 10 of 50 results
                    </div>
                    <div class="flex items-center gap-2">
                        <button class="px-3 py-1.5 rounded-lg bg-dark-secondary hover:bg-dark-tertiary text-dark-text-primary text-sm transition-colors">
                            Previous
                        </button>
                        <button class="px-3 py-1.5 rounded-lg bg-purple-accent text-white text-sm">
                            1
                        </button>
                        <button class="px-3 py-1.5 rounded-lg bg-dark-secondary hover:bg-dark-tertiary text-dark-text-primary text-sm transition-colors">
                            2
                        </button>
                        <button class="px-3 py-1.5 rounded-lg bg-dark-secondary hover:bg-dark-tertiary text-dark-text-primary text-sm transition-colors">
                            Next
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

