<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import GradientButton from '@/Components/GradientButton.vue';
import DarkInput from '@/Components/DarkInput.vue';
import { ref } from 'vue';

const props = defineProps<{
    templates: Array<{ id: number; name: string; version: string }>;
    hasSignedAgreement: boolean;
    supplier_id: number | null;
    buyers: Array<{ id: number; name: string }>;
}>();

const activeTab = ref<'upload'>('upload');

// signContract function removed as per request

const supplierForm = useForm({
    supplier_name: '',
    supplier_email: '',
    supplier_phone: '',
    supplier_address: '',
});

const invoiceForm = useForm({
    supplier_id: props.supplier_id?.toString() || '',
    buyer_id: null as number | null,
    invoice_number: '',
    amount: '',
    currency: 'AED',
    due_date: '',
    issue_date: '',
    description: '',
    file: null as File | null,
});

// fundingForm removed as per request

function onFile(e: Event) {
    const t = e.target as HTMLInputElement;
    invoiceForm.file = t.files?.[0] ?? null;
}

function submitInvoice() {
    invoiceForm.post(route('invoices.store'), {
        forceFormData: true,
        onSuccess: () => {
            // Handle success
        },
    });
}
</script>

<template>

    <Head title="Upload Invoice" />

    <AuthenticatedLayout>
        <div class="space-y-6">
            <!-- Page Title -->
            <div class="mb-6">
                <h1 class="text-2xl font-bold text-dark-text-primary">Upload Invoice</h1>
            </div>

            <!-- Tabs -->
            <div class="card">
                <div class="flex space-x-1 border-b border-dark-border mb-6">
                    <!-- Register Supplier tab removed as per request -->
                    <button @click="activeTab = 'upload'" :class="[
                        'px-4 py-2 text-sm font-medium transition-colors',
                        activeTab === 'upload'
                            ? 'text-purple-accent border-b-2 border-purple-accent'
                            : 'text-dark-text-secondary hover:text-dark-text-primary'
                    ]">
                        Upload Invoice
                    </button>
                    <!-- Manual Funding tab removed as per request -->
                </div>

                <!-- Register Supplier section removed as per request -->

                <!-- Upload Invoice Tab -->
                <div v-if="activeTab === 'upload'" class="space-y-6">
                    <h3 class="text-lg font-semibold text-dark-text-primary mb-4">Upload Invoice</h3>

                    <form @submit.prevent="submitInvoice">
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-dark-text-secondary mb-2">Invoice
                                    Number</label>
                                <DarkInput v-model="invoiceForm.invoice_number" placeholder="Enter invoice number"
                                    required />
                            </div>

                            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                <!-- Supplier ID field hidden as per request -->
                                <!-- Buyer field hidden as per request -->
                                <div>
                                    <label class="block text-sm font-medium text-dark-text-secondary mb-2">Issue
                                        Date</label>
                                    <DarkInput v-model="invoiceForm.issue_date" type="date"
                                        placeholder="Select issue date" />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-dark-text-secondary mb-2">Due
                                        Date</label>
                                    <DarkInput v-model="invoiceForm.due_date" type="date" placeholder="Select due date"
                                        required />
                                </div>
                                <div>
                                    <label
                                        class="block text-sm font-medium text-dark-text-secondary mb-2">Amount</label>
                                    <DarkInput v-model.number="invoiceForm.amount" type="number" step="0.01"
                                        placeholder="Enter amount" required />
                                </div>
                                <div>
                                    <label
                                        class="block text-sm font-medium text-dark-text-secondary mb-2">Currency</label>
                                    <DarkInput v-model="invoiceForm.currency" placeholder="Currency" maxlength="3" />
                                </div>
                            </div>

                            <div>
                                <label
                                    class="block text-sm font-medium text-dark-text-secondary mb-2">Description</label>
                                <textarea v-model="invoiceForm.description"
                                    class="input-dark !py-2 !px-3 text-smmin-h-[100px] resize-none"
                                    placeholder="Enter invoice description"></textarea>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-dark-text-secondary mb-2">Upload Invoice
                                    File</label>
                                <input @change="onFile" type="file" accept=".pdf,.jpg,.jpeg,.png"
                                    class="input-dark !py-2 !px-3 text-smfile:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-purple-accent/20 file:text-purple-accent hover:file:bg-purple-accent/30" />
                            </div>
                        </div>

                        <div class="mt-6 flex justify-end gap-4">
                            <button type="button" class="btn-secondary">Cancel</button>
                            <GradientButton type="submit" :disabled="invoiceForm.processing">
                                Save Invoice
                            </GradientButton>
                        </div>
                    </form>
                </div>

                <!-- E-sign section removed as per request -->

                <!-- Add Manual Funding section removed as per request -->
            </div>
        </div>
    </AuthenticatedLayout>
</template>
