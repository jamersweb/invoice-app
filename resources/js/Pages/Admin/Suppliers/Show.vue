<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import Badge from '@/Components/Badge.vue';
import GradientButton from '@/Components/GradientButton.vue';
import DarkInput from '@/Components/DarkInput.vue';

const props = defineProps<{
    supplier: any;
}>();

const activeTab = ref('company');

const form = useForm({
    status: '',
    reason: '',
    notes: props.supplier.kyb_notes || '',
});

const submitReview = (status: 'approved' | 'rejected' | 'under_review') => {
    form.status = status;
    form.post(route('admin.suppliers.status.update', props.supplier.id), {
        onSuccess: () => {
            // Success handling is managed by controller redirect
        }
    });
};

const formatDate = (date: string) => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString();
};
</script>

<template>

    <Head :title="`Review: ${supplier.company_name}`" />

    <AuthenticatedLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                <div>
                    <div class="flex items-center gap-3">
                        <Link :href="route('admin.suppliers.index')"
                            class="text-dark-text-muted hover:text-white transition-colors">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                            </svg>
                        </Link>
                        <h1 class="text-2xl font-bold text-dark-text-primary">{{ supplier.company_name }}</h1>
                        <Badge
                            :variant="supplier.kyb_status === 'approved' ? 'success' : (supplier.kyb_status === 'rejected' ? 'danger' : 'warning')">
                            {{ supplier.kyb_status }}
                        </Badge>
                    </div>
                    <p class="mt-1 text-sm text-dark-text-secondary ml-9">Registered on {{
                        formatDate(supplier.created_at) }}</p>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Main Content (2/3) -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Tabs Navigation -->
                    <div class="flex border-b border-dark-border overflow-x-auto custom-scrollbar">
                        <button v-for="tab in [
                            { id: 'company', name: 'Company Info' },
                            { id: 'business', name: 'Business Details' },
                            { id: 'location', name: 'Location & Contact' },
                            { id: 'documents', name: 'Documents' }
                        ]" :key="tab.id" @click="activeTab = tab.id" :class="[
                            'px-6 py-3 text-sm font-medium whitespace-nowrap transition-colors border-b-2',
                            activeTab === tab.id
                                ? 'border-purple-accent text-purple-accent'
                                : 'border-transparent text-dark-text-secondary hover:text-white hover:border-dark-border'
                        ]">
                            {{ tab.name }}
                        </button>
                    </div>

                    <!-- Tab Content -->
                    <div class="card min-h-[400px]">
                        <!-- Step 1: Company Info -->
                        <div v-if="activeTab === 'company'" class="space-y-6">
                            <h3 class="text-lg font-semibold text-dark-text-primary">Company Information</h3>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                                <div>
                                    <label
                                        class="block text-xs font-medium text-dark-text-muted uppercase tracking-wider mb-1">Company
                                        Name</label>
                                    <p class="text-dark-text-primary font-medium">{{ supplier.company_name }}</p>
                                </div>
                                <div>
                                    <label
                                        class="block text-xs font-medium text-dark-text-muted uppercase tracking-wider mb-1">Legal
                                        Name</label>
                                    <p class="text-dark-text-primary font-medium">{{ supplier.legal_name || '-' }}</p>
                                </div>
                                <div>
                                    <label
                                        class="block text-xs font-medium text-dark-text-muted uppercase tracking-wider mb-1">Tax
                                        Reg Number</label>
                                    <p class="text-dark-text-primary font-medium">{{ supplier.tax_registration_number ||
                                        '-' }}</p>
                                </div>
                                <div>
                                    <label
                                        class="block text-xs font-medium text-dark-text-muted uppercase tracking-wider mb-1">Website</label>
                                    <p class="text-dark-text-primary font-medium">
                                        <a v-if="supplier.website" :href="supplier.website" target="_blank"
                                            class="text-purple-accent hover:underline">
                                            {{ supplier.website }}
                                        </a>
                                        <span v-else>-</span>
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Step 2: Business Details -->
                        <div v-if="activeTab === 'business'" class="space-y-6">
                            <h3 class="text-lg font-semibold text-dark-text-primary">Business & Industry Details</h3>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                                <div>
                                    <label
                                        class="block text-xs font-medium text-dark-text-muted uppercase tracking-wider mb-1">Business
                                        Type</label>
                                    <p class="text-dark-text-primary font-medium">{{ supplier.business_type || '-' }}
                                    </p>
                                </div>
                                <div>
                                    <label
                                        class="block text-xs font-medium text-dark-text-muted uppercase tracking-wider mb-1">Industry</label>
                                    <p class="text-dark-text-primary font-medium">{{ supplier.industry || '-' }}</p>
                                </div>
                                <div>
                                    <label
                                        class="block text-xs font-medium text-dark-text-muted uppercase tracking-wider mb-1">Incorporation
                                        Date</label>
                                    <p class="text-dark-text-primary font-medium">{{
                                        formatDate(supplier.incorporation_date) }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Step 3: Location & Contact -->
                        <div v-if="activeTab === 'location'" class="space-y-6">
                            <h3 class="text-lg font-semibold text-dark-text-primary">Location & Contact Info</h3>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                                <div>
                                    <label
                                        class="block text-xs font-medium text-dark-text-muted uppercase tracking-wider mb-1">Country</label>
                                    <p class="text-dark-text-primary font-medium">{{ supplier.country || '-' }}</p>
                                </div>
                                <div>
                                    <label
                                        class="block text-xs font-medium text-dark-text-muted uppercase tracking-wider mb-1">City
                                        / State</label>
                                    <p class="text-dark-text-primary font-medium">{{ supplier.city }}{{
                                        supplier.state_province ? ', ' + supplier.state_province : '' }}</p>
                                </div>
                                <div class="sm:col-span-2">
                                    <label
                                        class="block text-xs font-medium text-dark-text-muted uppercase tracking-wider mb-1">Address</label>
                                    <p class="text-dark-text-primary font-medium">{{ supplier.address || '-' }} {{
                                        supplier.postal_code }}</p>
                                </div>
                                <div>
                                    <label
                                        class="block text-xs font-medium text-dark-text-muted uppercase tracking-wider mb-1">Contact
                                        Email</label>
                                    <p class="text-dark-text-primary font-medium">{{ supplier.contact_email }}</p>
                                </div>
                                <div>
                                    <label
                                        class="block text-xs font-medium text-dark-text-muted uppercase tracking-wider mb-1">Contact
                                        Phone</label>
                                    <p class="text-dark-text-primary font-medium">{{ supplier.contact_phone || '-' }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Step 4: Documents -->
                        <div v-if="activeTab === 'documents'" class="space-y-6">
                            <h3 class="text-lg font-semibold text-dark-text-primary">Uploaded Documents</h3>
                            <div v-if="supplier.documents && supplier.documents.length > 0"
                                class="grid grid-cols-1 gap-4">
                                <div v-for="doc in supplier.documents" :key="doc.id"
                                    class="p-4 rounded-lg bg-dark-secondary border border-dark-border flex items-center justify-between">
                                    <div class="flex items-center gap-4">
                                        <div
                                            class="h-10 w-10 flex items-center justify-center rounded-lg bg-dark-tertiary">
                                            <svg class="h-6 w-6 text-dark-text-muted" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                            </svg>
                                        </div>
                                        <div>
                                            <div class="text-sm font-medium text-dark-text-primary">{{
                                                doc.document_type?.name || 'Unknown Type' }}</div>
                                            <div class="text-xs text-dark-text-secondary">Uploaded on {{
                                                formatDate(doc.created_at) }}</div>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-3">
                                        <Badge
                                            :variant="doc.status === 'approved' ? 'success' : (doc.status === 'rejected' ? 'danger' : 'warning')">
                                            {{ doc.status }}
                                        </Badge>
                                        <a :href="`/storage/${doc.file_path}`" target="_blank"
                                            class="p-2 rounded-lg hover:bg-dark-tertiary text-purple-accent">
                                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                        </a>
                                    </div>
                                </div>
                            </div>
                            <div v-else class="text-center py-12 text-dark-text-muted">
                                No documents uploaded yet.
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Review Sidebar (1/3) -->
                <div class="lg:col-span-1 space-y-6">
                    <div class="card p-6 bg-dark-secondary/50">
                        <h3 class="text-lg font-semibold text-dark-text-primary mb-4">Review Decision</h3>

                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-dark-text-secondary mb-2">Internal
                                    Notes</label>
                                <textarea v-model="form.notes" rows="3"
                                    class="w-full bg-dark-tertiary border border-dark-border text-white rounded-lg p-3 text-sm outline-none focus:border-purple-accent resize-none"
                                    placeholder="Write internal notes here..."></textarea>
                            </div>

                            <div v-if="form.status === 'rejected' || supplier.kyb_status === 'rejected'"
                                class="p-4 rounded-lg bg-red-500/10 border border-red-500/30">
                                <label
                                    class="block text-xs font-semibold text-red-400 uppercase tracking-wider mb-2">Rejection
                                    Reason (Public)</label>
                                <textarea v-model="form.reason" rows="2"
                                    class="w-full bg-dark-tertiary border border-red-500/30 text-white rounded-lg p-3 text-sm outline-none focus:border-red-500 resize-none"
                                    placeholder="Reason will be visible to supplier..."></textarea>
                                <div v-if="form.errors.reason" class="mt-1 text-xs text-red-500">{{ form.errors.reason
                                    }}</div>
                            </div>

                            <div class="space-y-2 pt-4 border-t border-dark-border">
                                <GradientButton @click="submitReview('approved')" class="w-full h-12"
                                    :disabled="form.processing">
                                    Approve Supplier
                                </GradientButton>

                                <button @click="form.status = 'rejected'" v-if="form.status !== 'rejected'"
                                    class="w-full btn-secondary h-12 text-red-400 hover:text-red-300 hover:border-red-500/50"
                                    :disabled="form.processing">
                                    Reject Application
                                </button>

                                <button v-else @click="submitReview('rejected')"
                                    class="w-full bg-red-600 hover:bg-red-700 text-white font-bold py-3 rounded-lg transition-colors h-12"
                                    :disabled="form.processing">
                                    Confirm Rejection
                                </button>

                                <button v-if="supplier.kyb_status !== 'under_review'"
                                    @click="submitReview('under_review')" class="w-full btn-secondary h-12"
                                    :disabled="form.processing">
                                    Mark as Under Review
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Risk Info (Mock) -->
                    <div class="card p-6 bg-dark-secondary/50">
                        <h3 class="text-lg font-semibold text-dark-text-primary mb-4">Risk Estimation</h3>
                        <div class="space-y-3">
                            <div class="flex justify-between text-sm">
                                <span class="text-dark-text-secondary">Completion Rate</span>
                                <span class="text-green-500 font-medium">100%</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-dark-text-secondary">Email Verified</span>
                                <span class="text-green-500 font-medium">Yes</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-dark-text-secondary">Document Match</span>
                                <span class="text-yellow-500 font-medium">High</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
