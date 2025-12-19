<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import Badge from '@/Components/Badge.vue';
import GradientButton from '@/Components/GradientButton.vue';

defineProps<{
    invoice?: {
        id: number;
        invoice_number: string;
        issue_date: string;
        due_date: string;
        status: string;
        from_address?: string;
        to_address?: string;
        items?: Array<{
            item_number: number;
            name: string;
            description: string;
            quantity: number;
            price: number;
            total: number;
        }>;
        subtotal: number;
        tax: number;
        total: number;
        bank_details?: {
            bank_name: string;
            account_number: string;
            ifsc_code: string;
            branch: string;
        };
    };
}>();

// Mock data for demonstration
const invoice = {
    id: 1,
    invoice_number: 'INV-2024-001',
    issue_date: '2024-11-01',
    due_date: '2024-12-01',
    status: 'paid',
    from_address: {
        name: 'Invoice App Inc.',
        address: '123 Business Street',
        city: 'New York, NY 10001',
        email: 'info@invoiceapp.com',
    },
    to_address: {
        name: 'John Doe',
        address: '456 Customer Ave',
        city: 'Los Angeles, CA 90001',
        email: 'john@example.com',
    },
    items: [
        { item_number: 1, name: 'Product A', description: 'Description of Product A', quantity: 10, price: 100, total: 1000 },
        { item_number: 2, name: 'Product B', description: 'Description of Product B', quantity: 5, price: 200, total: 1000 },
        { item_number: 3, name: 'Service C', description: 'Description of Service C', quantity: 3, price: 300, total: 900 },
    ],
    subtotal: 2900,
    tax: 290,
    total: 3190,
    bank_details: {
        bank_name: 'ABC Bank',
        account_number: '1234567890',
        ifsc_code: 'ABC1234567',
        branch: 'Main Branch',
    },
};

function formatCurrency(amount: number) {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
        minimumFractionDigits: 2,
    }).format(amount);
}

function numberToWords(num: number): string {
    // Simplified number to words converter
    return 'Three Thousand One Hundred Ninety Only';
}
</script>

<template>

    <Head :title="`Invoice ${invoice?.invoice_number || ''}`" />

    <AuthenticatedLayout>
        <div class="space-y-6">
            <!-- Header Actions -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-dark-text-primary">Invoice Details</h1>
                </div>
                <div class="flex items-center gap-3">
                    <GradientButton>Edit</GradientButton>
                    <GradientButton>Download</GradientButton>
                </div>
            </div>

            <!-- Invoice Card -->
            <div class="card space-y-6">
                <!-- Invoice Header -->
                <div class="flex items-start justify-between border-b border-dark-border pb-6">
                    <div>
                        <h2 class="text-xl font-bold text-dark-text-primary mb-2">Invoice #{{ invoice?.invoice_number }}
                        </h2>
                        <div class="flex items-center gap-4 text-sm text-dark-text-secondary">
                            <div>
                                <span class="text-dark-text-muted">Date: </span>
                                <span>{{ new Date(invoice?.issue_date || '').toLocaleDateString() }}</span>
                            </div>
                            <div>
                                <span class="text-dark-text-muted">Invoice Number: </span>
                                <span>{{ invoice?.invoice_number }}</span>
                            </div>
                        </div>
                    </div>
                    <Badge
                        :variant="invoice?.status === 'paid' ? 'success' : invoice?.status === 'overdue' ? 'danger' : 'warning'">
                        {{ invoice?.status?.toUpperCase() }}
                    </Badge>
                </div>

                <!-- Addresses -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 border-b border-dark-border pb-6">
                    <!-- Invoice To -->
                    <div>
                        <h3 class="text-sm font-semibold text-dark-text-primary mb-3">Invoice To</h3>
                        <div class="text-sm text-dark-text-secondary space-y-1">
                            <p class="font-medium text-dark-text-primary">{{ invoice?.to_address?.name }}</p>
                            <p>{{ invoice?.to_address?.address }}</p>
                            <p>{{ invoice?.to_address?.city }}</p>
                            <p>{{ invoice?.to_address?.email }}</p>
                        </div>
                    </div>

                    <!-- Pay To -->
                    <div>
                        <h3 class="text-sm font-semibold text-dark-text-primary mb-3">Pay To</h3>
                        <div class="text-sm text-dark-text-secondary space-y-1">
                            <p class="font-medium text-dark-text-primary">{{ invoice?.from_address?.name }}</p>
                            <p>{{ invoice?.from_address?.address }}</p>
                            <p>{{ invoice?.from_address?.city }}</p>
                            <p>{{ invoice?.from_address?.email }}</p>
                        </div>
                    </div>

                    <!-- Company Info -->
                    <div>
                        <h3 class="text-sm font-semibold text-dark-text-primary mb-3">Company Info</h3>
                        <div class="text-sm text-dark-text-secondary space-y-1">
                            <p class="font-medium text-dark-text-primary">Invoice App Inc.</p>
                            <p>GST: GST123456789</p>
                            <p>123 Business Street, New York, NY 10001</p>
                            <p>Mobile: +1 (555) 123-4567</p>
                        </div>
                    </div>
                </div>

                <!-- Items Table -->
                <div class="border-b border-dark-border pb-6">
                    <table class="table-dark bg-none">
                        <thead>
                            <tr>
                                <th class="w-12">#</th>
                                <th>Item Name</th>
                                <th>Description</th>
                                <th class="text-right">Quantity</th>
                                <th class="text-right">Price</th>
                                <th class="text-right">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="item in invoice?.items" :key="item.item_number">
                                <td>{{ item.item_number }}</td>
                                <td class="font-medium">{{ item.name }}</td>
                                <td class="text-dark-text-secondary">{{ item.description }}</td>
                                <td class="text-right">{{ item.quantity }}</td>
                                <td class="text-right">{{ formatCurrency(item.price) }}</td>
                                <td class="text-right font-medium">{{ formatCurrency(item.total) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Summary -->
                <div class="flex justify-end">
                    <div class="w-64 space-y-3">
                        <div class="flex justify-between text-sm">
                            <span class="text-dark-text-secondary">Taxable Amount</span>
                            <span class="text-dark-text-primary">{{ formatCurrency(invoice?.subtotal || 0) }}</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-dark-text-secondary">IGST</span>
                            <span class="text-dark-text-primary">{{ formatCurrency(invoice?.tax || 0) }}</span>
                        </div>
                        <div class="border-t border-dark-border pt-3 flex justify-between">
                            <span class="font-semibold text-dark-text-primary">Total</span>
                            <span class="text-xl font-bold text-dark-text-primary">{{ formatCurrency(invoice?.total ||
                                0) }}</span>
                        </div>
                        <div class="text-sm text-dark-text-secondary pt-2">
                            <p>Total Amount in Words:</p>
                            <p class="font-medium text-dark-text-primary">{{ numberToWords(invoice?.total || 0) }}</p>
                        </div>
                    </div>
                </div>

                <!-- Bank Details and Signature -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 border-t border-dark-border pt-6">
                    <div>
                        <h3 class="text-sm font-semibold text-dark-text-primary mb-3">Bank Details</h3>
                        <div class="text-sm text-dark-text-secondary space-y-2">
                            <p><span class="font-medium text-dark-text-primary">Bank Name:</span> {{
                                invoice?.bank_details?.bank_name }}</p>
                            <p><span class="font-medium text-dark-text-primary">Account Number:</span> {{
                                invoice?.bank_details?.account_number }}</p>
                            <p><span class="font-medium text-dark-text-primary">IFSC Code:</span> {{
                                invoice?.bank_details?.ifsc_code }}</p>
                            <p><span class="font-medium text-dark-text-primary">Branch:</span> {{
                                invoice?.bank_details?.branch }}</p>
                        </div>
                    </div>
                    <div>
                        <h3 class="text-sm font-semibold text-dark-text-primary mb-3 text-center">Signature</h3>
                        <div
                            class="h-20 border-2 border-dashed border-dark-border rounded-lg flex items-center justify-center">
                            <span class="text-dark-text-muted text-sm">Signature</span>
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
