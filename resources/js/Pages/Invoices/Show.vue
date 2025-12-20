<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import Badge from '@/Components/Badge.vue';
import GradientButton from '@/Components/GradientButton.vue';

const props = defineProps<{
    invoice: {
        id: number;
        invoice_number: string;
        issue_date?: string;
        due_date: string;
        amount: number;
        currency: string;
        status: string;
        customer_name?: string;
        file_path?: string;
        ocr_data?: any;
    };
}>();

function formatCurrency(amount: any, currency = 'AED') {
    return new Intl.NumberFormat('en-AE', {
        style: 'currency',
        currency: currency || 'AED',
        minimumFractionDigits: 2,
    }).format(amount || 0);
}
</script>

<template>

    <Head :title="`Invoice ${invoice.invoice_number}`" />

    <AuthenticatedLayout>
        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-dark-text-primary">Invoice Details</h1>
                </div>
                <div class="flex items-center gap-3">
                    <Link v-if="invoice.status === 'draft' || invoice.status === 'under_review'"
                          :href="route('invoices.edit', invoice.id)">
                        <GradientButton>Edit Invoice</GradientButton>
                    </Link>
                    <a v-if="invoice.file_path" :href="'/storage/' + invoice.file_path" target="_blank">
                        <GradientButton>Download PDF</GradientButton>
                    </a>
                </div>
            </div>

            <div class="card space-y-8">
                <div class="flex justify-between items-start border-b border-dark-border pb-6">
                    <div>
                        <h2 class="text-xl font-bold text-dark-text-primary mb-1">#{{ invoice.invoice_number }}</h2>
                        <p class="text-sm text-dark-text-secondary">Submitted on {{ new Date().toLocaleDateString() }}</p>
                    </div>
                    <Badge :variant="invoice.status === 'paid' ? 'success' : invoice.status === 'overdue' ? 'danger' : 'warning'">
                        {{ invoice.status.toUpperCase() }}
                    </Badge>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
                    <div>
                        <h3 class="text-xs font-semibold text-dark-text-muted uppercase tracking-wider mb-2">Amount</h3>
                        <p class="text-xl font-bold text-dark-text-primary">{{ formatCurrency(invoice.amount, invoice.currency) }}</p>
                    </div>
                    <div>
                        <h3 class="text-xs font-semibold text-dark-text-muted uppercase tracking-wider mb-2">Due Date</h3>
                        <p class="text-lg text-dark-text-primary">{{ new Date(invoice.due_date).toLocaleDateString() }}</p>
                    </div>
                    <div>
                        <h3 class="text-xs font-semibold text-dark-text-muted uppercase tracking-wider mb-2">Buyer</h3>
                        <p class="text-lg text-dark-text-primary">{{ invoice.customer_name || 'N/A' }}</p>
                    </div>
                    <div>
                        <h3 class="text-xs font-semibold text-dark-text-muted uppercase tracking-wider mb-2">Currency</h3>
                        <p class="text-lg text-dark-text-primary">{{ invoice.currency }}</p>
                    </div>
                </div>

                <div v-if="invoice.ocr_data" class="pt-6 border-t border-dark-border">
                    <h3 class="text-sm font-semibold text-dark-text-primary mb-4">OCR Analysis Results</h3>
                    <div class="bg-dark-secondary/20 p-4 rounded-xl space-y-2">
                        <div v-for="(val, key) in invoice.ocr_data" :key="key" class="flex justify-between text-sm">
                            <span class="text-dark-text-secondary capitalize">{{ key.replace('_', ' ') }}:</span>
                            <span class="text-dark-text-primary font-medium">{{ val }}</span>
                        </div>
                    </div>
                </div>

                <!-- Terms and Conditions -->
                <div class="border-t border-dark-border pt-6">
                    <h3 class="text-sm font-semibold text-dark-text-primary mb-3">Terms and Conditions</h3>
                    <p class="text-sm text-dark-text-secondary">
                        Payment is due within 30 days of invoice date. Late payments may incur additional charges.
                        All disputes must be submitted in writing within 7 days of invoice receipt.
                    </p>
                </div>

                <!-- Thank You -->
                <div class="border-t border-dark-border pt-4">
                    <p class="text-sm font-medium text-dark-text-primary text-center">Thank you for your business!</p>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
