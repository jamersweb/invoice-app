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

const activeTab = ref<'supplier' | 'upload' | 'funding'>('supplier');

const agreementForm = useForm({
    template_id: null as number | null,
});

function signContract() {
    // Find Master Agreement or just the first available template
    const masterTemplate = props.templates.find(t => t.name.includes('Master')) || props.templates[0];
    if (!masterTemplate) {
        alert('No agreement templates available. Please contact support.');
        return;
    }

    agreementForm.template_id = masterTemplate.id;
    agreementForm.post(route('agreements.sign'), {
        preserveScroll: true,
        onSuccess: () => {
            // Success will be reflected in hasSignedAgreement prop via Inertia reload
        },
    });
}

const supplierForm = useForm({
    supplier_name: '',
    supplier_email: '',
    supplier_phone: '',
    supplier_address: '',
});

const invoiceForm = useForm({
    supplier_id: props.supplier_id?.toString() || '',
    buyer_id: '',
    invoice_number: '',
    amount: '',
    currency: 'SAR',
    due_date: '',
    issue_date: '',
    description: '',
    file: null as File | null,
});

const fundingForm = useForm({
    invoice_id: '',
    amount: '',
    funding_date: '',
});

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
                    <button @click="activeTab = 'supplier'" :class="[
                        'px-4 py-2 text-sm font-medium transition-colors',
                        activeTab === 'supplier'
                            ? 'text-purple-accent border-b-2 border-purple-accent'
                            : 'text-dark-text-secondary hover:text-dark-text-primary'
                    ]">
                        Register Supplier
                    </button>
                    <button @click="activeTab = 'upload'" :class="[
                        'px-4 py-2 text-sm font-medium transition-colors',
                        activeTab === 'upload'
                            ? 'text-purple-accent border-b-2 border-purple-accent'
                            : 'text-dark-text-secondary hover:text-dark-text-primary'
                    ]">
                        Upload Invoice
                    </button>
                    <button @click="activeTab = 'funding'" :class="[
                        'px-4 py-2 text-sm font-medium transition-colors',
                        activeTab === 'funding'
                            ? 'text-purple-accent border-b-2 border-purple-accent'
                            : 'text-dark-text-secondary hover:text-dark-text-primary'
                    ]">
                        Add Manual Funding
                    </button>
                </div>

                <!-- Register Supplier Tab -->
                <div v-if="activeTab === 'supplier'" class="space-y-6">
                    <h3 class="text-lg font-semibold text-dark-text-primary mb-4">Register Supplier</h3>
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div>
                            <label class="block text-sm font-medium text-dark-text-secondary mb-2">Supplier Name</label>
                            <DarkInput v-model="supplierForm.supplier_name" placeholder="Enter supplier name" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-dark-text-secondary mb-2">Email</label>
                            <DarkInput v-model="supplierForm.supplier_email" type="email" placeholder="Enter email" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-dark-text-secondary mb-2">Phone</label>
                            <DarkInput v-model="supplierForm.supplier_phone" type="tel"
                                placeholder="Enter phone number" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-dark-text-secondary mb-2">Address</label>
                            <DarkInput v-model="supplierForm.supplier_address" placeholder="Enter address" />
                        </div>
                    </div>
                    <div class="flex justify-end">
                        <GradientButton>Register Supplier</GradientButton>
                    </div>
                </div>

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
                                <div>
                                    <label class="block text-sm font-medium text-dark-text-secondary mb-2">Supplier
                                        ID</label>
                                    <DarkInput v-model="invoiceForm.supplier_id" type="number"
                                        placeholder="Enter supplier ID" readonly
                                        class="opacity-70 cursor-not-allowed" />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-dark-text-secondary mb-2">Buyer</label>
                                    <select v-model="invoiceForm.buyer_id"
                                        class="input-dark w-full bg-dark-secondary border-dark-border text-white rounded-lg p-2.5 outline-none focus:border-purple-accent"
                                        required>
                                        <option value="">Select Buyer</option>
                                        <option v-for="buyer in props.buyers" :key="buyer.id" :value="buyer.id">
                                            {{ buyer.name }}
                                        </option>
                                    </select>
                                </div>
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

                <!-- E-sign Contract Section -->
                <div v-if="activeTab === 'upload'" class="mt-6 pt-6 border-t border-dark-border">
                    <h3 class="text-lg font-semibold text-dark-text-primary mb-2">E-sign Contract</h3>
                    <div class="card bg-dark-secondary/50">
                        <div v-if="hasSignedAgreement" class="flex items-center gap-3 text-green-500">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M5 13l4 4L19 7"></path>
                            </svg>
                            <span class="font-medium">Agreement Signed Successfully</span>
                        </div>
                        <template v-else>
                            <p class="text-sm text-dark-text-secondary mb-4">
                                Once your invoice is uploaded, you can sign the funding agreement digitally.
                            </p>
                            <GradientButton @click="signContract" :disabled="agreementForm.processing" size="md">
                                {{ agreementForm.processing ? 'Signing...' : 'Sign Contract' }}
                            </GradientButton>
                        </template>
                    </div>
                </div>

                <!-- Add Manual Funding Tab -->
                <div v-if="activeTab === 'funding'" class="space-y-6">
                    <h3 class="text-lg font-semibold text-dark-text-primary mb-4">Add Manual Funding</h3>
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div>
                            <label class="block text-sm font-medium text-dark-text-secondary mb-2">Invoice ID</label>
                            <DarkInput v-model="fundingForm.invoice_id" type="number" placeholder="Enter invoice ID" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-dark-text-secondary mb-2">Amount</label>
                            <DarkInput v-model.number="fundingForm.amount" type="number" step="0.01"
                                placeholder="Enter funding amount" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-dark-text-secondary mb-2">Funding Date</label>
                            <DarkInput v-model="fundingForm.funding_date" type="date"
                                placeholder="Select funding date" />
                        </div>
                    </div>
                    <div class="flex justify-end">
                        <GradientButton>Add Funding</GradientButton>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
