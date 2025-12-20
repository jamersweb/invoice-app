<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import GradientButton from '@/Components/GradientButton.vue';
import DarkInput from '@/Components/DarkInput.vue';

const props = defineProps<{
    invoice: {
        id: number;
        invoice_number: string;
        amount: number;
        currency: string;
        due_date: string;
        issue_date?: string;
        description?: string;
    };
}>();

const form = useForm({
    invoice_number: props.invoice.invoice_number,
    amount: props.invoice.amount,
    currency: props.invoice.currency,
    due_date: props.invoice.due_date,
    issue_date: props.invoice.issue_date || '',
    description: props.invoice.description || '',
    file: null as File | null,
});

function onFile(e: Event) {
    const t = e.target as HTMLInputElement;
    form.file = t.files?.[0] ?? null;
}

function submit() {
    form.post(route('invoices.update', props.invoice.id), {
        forceFormData: true,
        _method: 'put',
    });
}
</script>

<template>
    <Head title="Edit Invoice" />

    <AuthenticatedLayout>
        <div class="space-y-6">
            <div class="mb-6">
                <h1 class="text-2xl font-bold text-dark-text-primary">Edit Invoice #{{ invoice.invoice_number }}</h1>
            </div>

            <div class="card">
                <form @submit.prevent="submit">
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

                        <div>
                            <label class="block text-sm font-medium text-dark-text-secondary mb-2">Replace Invoice File (Optional)</label>
                            <input @change="onFile" type="file" accept=".pdf,.jpg,.jpeg,.png"
                                class="input-dark !py-2 !px-3 text-sm" />
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end gap-4">
                        <GradientButton type="submit" :disabled="form.processing">Update Invoice</GradientButton>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
