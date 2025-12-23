<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import GradientButton from '@/Components/GradientButton.vue';
import DarkInput from '@/Components/DarkInput.vue';
import DarkConfirmModal from '@/Components/DarkConfirmModal.vue';
import { ref, computed } from 'vue';

const props = defineProps<{
    invoice: any;
}>();

const invoiceData = computed(() => props.invoice.data || props.invoice);

const activeTab = ref<'upload' | 'bank'>('upload');
const showDeleteModal = ref(false);
const attachmentToDelete = ref<number | null>(null);

const form = useForm({
    _method: 'put',
    invoice_number: invoiceData.value.invoice_number,
    amount: invoiceData.value.amount,
    currency: invoiceData.value.currency,
    due_date: invoiceData.value.due_date,
    issue_date: invoiceData.value.issue_date || '',
    description: invoiceData.value.description || '',
    files: [] as File[],
    bank_account_name: invoiceData.value.bank_account_name || '',
    bank_name: invoiceData.value.bank_name || '',
    bank_branch: invoiceData.value.bank_branch || '',
    bank_iban: invoiceData.value.bank_iban || '',
    bank_swift: invoiceData.value.bank_swift || '',
});

function onFile(e: Event) {
    const t = e.target as HTMLInputElement;
    if (t.files) {
        form.files = Array.from(t.files);
    }
}

function removeAttachment(attachmentId: number) {
    attachmentToDelete.value = attachmentId;
    showDeleteModal.value = true;
}

function confirmRemoval() {
    if (attachmentToDelete.value) {
        router.delete(route('invoices.attachments.destroy', { id: invoiceData.value.id, attachmentId: attachmentToDelete.value }), {
            onSuccess: () => {
                showDeleteModal.value = false;
                attachmentToDelete.value = null;
            }
        });
    }
}

function submit() {
    form.post(route('invoices.update', invoiceData.value.id), {
        forceFormData: true,
        preserveScroll: true,
    });
}
</script>

<template>
    <Head title="Edit Invoice" />

    <AuthenticatedLayout>
        <div class="space-y-6">
            <div class="mb-6">
                <h1 class="text-2xl font-bold text-dark-text-primary">Edit Invoice #{{ invoiceData.invoice_number }}</h1>
            </div>

            <!-- Tabs -->
            <div class="card">
                <div class="flex space-x-1 border-b border-dark-border mb-6">
                    <button @click="activeTab = 'upload'" :class="[
                        'px-4 py-2 text-sm font-medium transition-colors',
                        activeTab === 'upload'
                            ? 'text-purple-accent border-b-2 border-purple-accent'
                            : 'text-dark-text-secondary hover:text-dark-text-primary'
                    ]">
                        Invoice Details
                    </button>
                    <button @click="activeTab = 'bank'" :class="[
                        'px-4 py-2 text-sm font-medium transition-colors',
                        activeTab === 'bank'
                            ? 'text-purple-accent border-b-2 border-purple-accent'
                            : 'text-dark-text-secondary hover:text-dark-text-primary'
                    ]">
                        Bank Details
                    </button>
                </div>

                <!-- Invoice Details Tab -->
                <div v-if="activeTab === 'upload'" class="space-y-6">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-dark-text-secondary mb-2">Invoice Number</label>
                            <DarkInput v-model="form.invoice_number" required />
                        </div>

                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div>
                                <label class="block text-sm font-medium text-dark-text-secondary mb-2">Issue Date</label>
                                <DarkInput v-model="form.issue_date" type="date" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-dark-text-secondary mb-2">Due Date</label>
                                <DarkInput v-model="form.due_date" type="date" required />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-dark-text-secondary mb-2">Amount</label>
                                <DarkInput v-model.number="form.amount" type="number" step="0.01" required />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-dark-text-secondary mb-2">Currency</label>
                                <DarkInput v-model="form.currency" maxlength="3" />
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-dark-text-secondary mb-2">Description</label>
                            <textarea v-model="form.description" 
                                class="input-dark !py-2 !px-3 text-sm min-h-[100px] resize-none"></textarea>
                        </div>

                        <!-- Existing Attachments -->
                        <div v-if="invoiceData.attachments?.length" class="space-y-2">
                            <label class="block text-xs font-bold text-dark-text-muted uppercase">Existing Attachments</label>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                <div v-for="att in invoiceData.attachments" :key="att.id" 
                                     class="flex items-center justify-between p-2 bg-dark-secondary/30 border border-dark-border rounded-lg group">
                                    <span class="text-xs text-dark-text-primary truncate">{{ att.file_name }}</span>
                                    <div class="flex items-center gap-2">
                                        <a :href="'/storage/' + att.file_path" target="_blank" class="text-[10px] text-purple-accent font-bold px-2 py-1">VIEW</a>
                                        <button type="button" @click="removeAttachment(att.id)" 
                                            class="text-[10px] text-red-400 font-bold px-2 py-1 hover:text-red-300 transition-all">
                                            REMOVE
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-dark-text-secondary mb-2">Add More Files (Optional)</label>
                            <input @change="onFile" type="file" multiple accept=".pdf,.jpg,.jpeg,.png"
                                class="input-dark !py-2 !px-3 text-sm file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-purple-accent/20 file:text-purple-accent hover:file:bg-purple-accent/30" />
                            
                            <div v-if="form.files.length > 0" class="mt-3 space-y-2">
                                <p class="text-xs font-semibold text-purple-accent">Selected to Upload:</p>
                                <div v-for="(f, i) in form.files" :key="i" class="flex items-center justify-between p-2 bg-dark-secondary/50 rounded-lg border border-dark-border">
                                    <span class="text-xs text-dark-text-primary">{{ f.name }}</span>
                                    <span class="text-[10px] text-dark-text-muted">{{ (f.size / 1024).toFixed(1) }} KB</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end">
                        <button type="button" class="btn-secondary" @click="activeTab = 'bank'">Next: Bank Details</button>
                    </div>
                </div>

                <!-- Bank Details Tab -->
                <div v-if="activeTab === 'bank'" class="space-y-6 text-left">
                    <h3 class="text-lg font-semibold text-dark-text-primary">Bank Details</h3>

                    <form @submit.prevent="submit">
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-dark-text-secondary mb-2">Account Name</label>
                                <DarkInput v-model="form.bank_account_name" placeholder="Enter account name" />
                            </div>

                            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                <div>
                                    <label class="block text-sm font-medium text-dark-text-secondary mb-2">Bank</label>
                                    <DarkInput v-model="form.bank_name" placeholder="Enter bank name" />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-dark-text-secondary mb-2">Branch</label>
                                    <DarkInput v-model="form.bank_branch" placeholder="Enter branch name" />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-dark-text-secondary mb-2">IBAN</label>
                                    <DarkInput v-model="form.bank_iban" placeholder="Enter IBAN" />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-dark-text-secondary mb-2">SWIFT</label>
                                    <DarkInput v-model="form.bank_swift" placeholder="Enter SWIFT code" />
                                </div>
                            </div>
                        </div>

                        <div class="mt-8 flex justify-end gap-4">
                            <button type="button" class="btn-secondary" @click="activeTab = 'upload'">Back</button>
                            <GradientButton type="submit" :disabled="form.processing">Update Invoice</GradientButton>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <DarkConfirmModal 
            :show="showDeleteModal"
            title="Remove Attachment"
            message="Are you sure you want to remove this attachment? This action cannot be undone."
            confirm-text="Remove"
            type="danger"
            @close="showDeleteModal = false"
            @confirm="confirmRemoval"
        />
    </AuthenticatedLayout>
</template>
