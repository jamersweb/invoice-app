<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import Badge from '@/Components/Badge.vue';
import GradientButton from '@/Components/GradientButton.vue';
import { computed } from 'vue';

const props = defineProps<{
    invoice: any;
}>();

const invoiceData = computed(() => props.invoice.data || props.invoice);

function formatCurrency(amount: any, currency = 'AED') {
    return new Intl.NumberFormat('en-AE', {
        style: 'currency',
        currency: currency || 'AED',
        minimumFractionDigits: 2,
    }).format(amount || 0);
}
</script>

<template>

    <Head :title="`Invoice ${invoiceData.invoice_number}`" />

    <AuthenticatedLayout>
        <div class="space-y-6">
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-dark-text-primary">Invoice Details</h1>
                </div>
                <div class="flex items-center gap-3">
                    <Link v-if="invoiceData.status === 'draft' || invoiceData.status === 'under_review'"
                          :href="route('invoices.edit', invoiceData.id)">
                        <GradientButton>Edit Invoice</GradientButton>
                    </Link>
                    <a v-if="invoiceData.file_path" :href="'/storage/' + invoiceData.file_path" target="_blank">
                        <GradientButton>Download PDF</GradientButton>
                    </a>
                </div>
            </div>

            <div class="card space-y-8">
                <div class="flex justify-between items-start border-b border-dark-border pb-6">
                    <div>
                        <h2 class="text-xl font-bold text-dark-text-primary mb-1">#{{ invoiceData.invoice_number }}</h2>
                        <p class="text-sm text-dark-text-secondary">Submitted on {{ new Date().toLocaleDateString() }}</p>
                    </div>
                    <Badge :variant="invoiceData.status === 'paid' ? 'success' : invoiceData.status === 'overdue' ? 'danger' : 'warning'">
                        {{ invoiceData.status?.toUpperCase() }}
                    </Badge>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <div>
                        <h3 class="text-xs font-semibold text-dark-text-muted uppercase tracking-wider mb-2">Amount</h3>
                        <p class="text-xl font-bold text-dark-text-primary">{{ formatCurrency(invoiceData.amount, invoiceData.currency) }}</p>
                    </div>
                    <div>
                        <h3 class="text-xs font-semibold text-dark-text-muted uppercase tracking-wider mb-2">Due Date</h3>
                        <p class="text-lg text-dark-text-primary">{{ new Date(invoiceData.due_date).toLocaleDateString() }}</p>
                    </div>
                    <div>
                        <h3 class="text-xs font-semibold text-dark-text-muted uppercase tracking-wider mb-2">Currency</h3>
                        <p class="text-lg text-dark-text-primary">{{ invoiceData.currency }}</p>
                    </div>
                </div>

                <!-- Bank Details -->
                <div v-if="invoiceData.bank_account_name || invoiceData.bank_name" class="pt-6 border-t border-dark-border">
                    <h3 class="text-sm font-semibold text-dark-text-primary mb-4">Bank Details</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <div v-if="invoiceData.bank_account_name">
                            <p class="text-[10px] text-dark-text-secondary uppercase font-bold mb-1">Account Name</p>
                            <p class="text-sm font-medium text-dark-text-primary">{{ invoiceData.bank_account_name }}</p>
                        </div>
                        <div v-if="invoiceData.bank_name">
                            <p class="text-[10px] text-dark-text-secondary uppercase font-bold mb-1">Bank</p>
                            <p class="text-sm font-medium text-dark-text-primary">{{ invoiceData.bank_name }}</p>
                        </div>
                        <div v-if="invoiceData.bank_branch">
                            <p class="text-[10px] text-dark-text-secondary uppercase font-bold mb-1">Branch</p>
                            <p class="text-sm font-medium text-dark-text-primary">{{ invoiceData.bank_branch }}</p>
                        </div>
                        <div v-if="invoiceData.bank_iban">
                            <p class="text-[10px] text-dark-text-secondary uppercase font-bold mb-1">IBAN</p>
                            <p class="text-sm font-medium text-dark-text-primary">{{ invoiceData.bank_iban }}</p>
                        </div>
                        <div v-if="invoiceData.bank_swift">
                            <p class="text-[10px] text-dark-text-secondary uppercase font-bold mb-1">SWIFT</p>
                            <p class="text-sm font-medium text-dark-text-primary">{{ invoiceData.bank_swift }}</p>
                        </div>
                    </div>
                </div>

                <!-- Attachments -->
                <div v-if="invoiceData.attachments?.length || invoiceData.file_path" class="pt-6 border-t border-dark-border">
                    <h3 class="text-sm font-semibold text-dark-text-primary mb-4">Attachments</h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                        <!-- New Attachments -->
                        <div v-for="att in invoiceData.attachments" :key="att.id" 
                             class="flex items-center justify-between p-3 bg-dark-secondary/30 border border-dark-border rounded-xl">
                            <div class="flex items-center gap-3 overflow-hidden">
                                <div class="w-8 h-8 bg-purple-accent/20 rounded flex items-center justify-center text-purple-accent shrink-0">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" /></svg>
                                </div>
                                <span class="text-xs text-dark-text-primary truncate">{{ att.file_name }}</span>
                            </div>
                            <a :href="'/storage/' + att.file_path" target="_blank" class="text-[10px] font-bold text-purple-accent border border-purple-accent/30 rounded px-2 py-1 hover:bg-purple-accent hover:text-white transition-all shrink-0">VIEW</a>
                        </div>

                        <!-- Legacy File -->
                        <div v-if="invoiceData.file_path && !invoiceData.attachments?.length" 
                             class="flex items-center justify-between p-3 bg-dark-secondary/30 border border-dark-border rounded-xl">
                            <div class="flex items-center gap-3 overflow-hidden">
                                <div class="w-8 h-8 bg-purple-accent/20 rounded flex items-center justify-center text-purple-accent shrink-0">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" /></svg>
                                </div>
                                <span class="text-xs text-dark-text-primary truncate">Main Document</span>
                            </div>
                            <a :href="'/storage/' + invoiceData.file_path" target="_blank" class="text-[10px] font-bold text-purple-accent border border-purple-accent/30 rounded px-2 py-1 hover:bg-purple-accent hover:text-white transition-all shrink-0">VIEW</a>
                        </div>
                    </div>
                </div>

                <div v-if="invoiceData.ocr_data" class="pt-6 border-t border-dark-border">
                    <h3 class="text-sm font-semibold text-dark-text-primary mb-4">OCR Analysis Results</h3>
                    <div class="bg-dark-secondary/20 p-4 rounded-xl space-y-2">
                        <div v-for="(val, key) in invoiceData.ocr_data" :key="key" class="flex justify-between text-sm">
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
