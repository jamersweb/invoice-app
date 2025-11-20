<template>
    <Head :title="`Customer: ${customer.name}`" />
    <AuthenticatedLayout>
        <div class="p-6 space-y-6">
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h1 class="text-3xl font-bold text-white">{{ customer.name }}</h1>
                    <p class="text-slate-400 mt-1">{{ customer.company_name || 'Customer Profile' }}</p>
                </div>
                <a
                    :href="route('forfaiting.customers.index')"
                    class="px-4 py-2 bg-slate-600 hover:bg-slate-700 text-white rounded-lg font-medium transition-colors"
                >
                    Back to Customers
                </a>
            </div>

            <!-- Customer Info -->
            <div class="bg-slate-800/30 border border-slate-700 rounded-lg p-6 mb-6">
                <h2 class="text-xl font-semibold text-white mb-4">Customer Information</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-1">Email</label>
                        <p class="text-white">{{ customer.email || '-' }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-1">Phone</label>
                        <p class="text-white">{{ customer.phone || '-' }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-1">Address</label>
                        <p class="text-white">{{ customer.address || '-' }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-1">Location</label>
                        <p class="text-white">{{ [customer.city, customer.country].filter(Boolean).join(', ') || '-' }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-1">Status</label>
                        <span :class="customer.status === 'active' ? 'bg-green-500/20 text-green-400' : 'bg-red-500/20 text-red-400'"
                              class="px-2 py-1 rounded text-sm">
                            {{ customer.status }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Transactions -->
            <div class="bg-slate-800/30 border border-slate-700 rounded-lg p-6 mb-6">
                <h2 class="text-xl font-semibold text-white mb-4">Transactions</h2>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b border-slate-700">
                                <th class="text-left text-slate-300 py-2">Transaction #</th>
                                <th class="text-left text-slate-300 py-2">Date</th>
                                <th class="text-right text-slate-300 py-2">Amount</th>
                                <th class="text-right text-slate-300 py-2">Profit Margin</th>
                                <th class="text-center text-slate-300 py-2">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="transaction in customer.transactions" :key="transaction.id" class="border-b border-slate-700/50">
                                <td class="text-white py-2">{{ transaction.transaction_number }}</td>
                                <td class="text-slate-300 py-2">{{ formatDate(transaction.date_of_transaction) }}</td>
                                <td class="text-right text-purple-400 font-mono py-2">{{ formatCurrency(transaction.net_amount) }}</td>
                                <td class="text-right text-amber-400 py-2">{{ transaction.profit_margin }}%</td>
                                <td class="text-center py-2">
                                    <span :class="transaction.status === 'Ongoing' ? 'bg-blue-500/20 text-blue-400' : 'bg-green-500/20 text-green-400'"
                                          class="px-2 py-1 rounded text-xs">
                                        {{ transaction.status }}
                                    </span>
                                </td>
                            </tr>
                            <tr v-if="customer.transactions.length === 0">
                                <td colspan="5" class="text-center text-slate-400 py-8">No transactions found</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Documents -->
            <div class="bg-slate-800/30 border border-slate-700 rounded-lg p-6">
                <h2 class="text-xl font-semibold text-white mb-4">Documents</h2>
                <form @submit.prevent="uploadDocument" class="mb-4">
                    <div class="flex gap-4">
                        <input
                            ref="fileInput"
                            type="file"
                            @change="handleFileChange"
                            class="hidden"
                        />
                        <button
                            type="button"
                            @click="fileInput?.click()"
                            class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition-colors"
                        >
                            Upload Document
                        </button>
                        <input
                            v-model="documentForm.document_type"
                            type="text"
                            placeholder="Document Type"
                            class="px-3 py-2 bg-slate-700 border border-slate-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                        />
                    </div>
                </form>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b border-slate-700">
                                <th class="text-left text-slate-300 py-2">Document Type</th>
                                <th class="text-left text-slate-300 py-2">File Name</th>
                                <th class="text-left text-slate-300 py-2">Upload Date</th>
                                <th class="text-left text-slate-300 py-2">Expiry Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="doc in customer.documents" :key="doc.id" class="border-b border-slate-700/50">
                                <td class="text-white py-2">{{ doc.document_type }}</td>
                                <td class="text-slate-300 py-2">{{ doc.file_name }}</td>
                                <td class="text-slate-300 py-2">{{ formatDate(doc.created_at) }}</td>
                                <td class="text-slate-300 py-2">{{ doc.expiry_date ? formatDate(doc.expiry_date) : '-' }}</td>
                            </tr>
                            <tr v-if="customer.documents.length === 0">
                                <td colspan="4" class="text-center text-slate-400 py-8">No documents found</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { ref } from 'vue';

interface Transaction {
    id: number;
    transaction_number: string;
    date_of_transaction: string;
    net_amount: number;
    profit_margin: number;
    status: string;
}

interface Document {
    id: number;
    document_type: string;
    file_name: string;
    created_at: string;
    expiry_date: string | null;
}

interface Customer {
    id: number;
    name: string;
    email: string | null;
    phone: string | null;
    company_name: string | null;
    address: string | null;
    city: string | null;
    country: string | null;
    status: string;
    transactions: Transaction[];
    documents: Document[];
}

interface Props {
    customer: Customer;
}

const props = defineProps<Props>();

const fileInput = ref<HTMLInputElement | null>(null);
const documentForm = ref({
    document_type: '',
    file: null as File | null,
});

const handleFileChange = (event: Event) => {
    const target = event.target as HTMLInputElement;
    if (target.files && target.files[0]) {
        documentForm.value.file = target.files[0];
    }
};

const uploadDocument = () => {
    if (!documentForm.value.file || !documentForm.value.document_type) {
        alert('Please select a file and enter document type');
        return;
    }

    const formData = new FormData();
    formData.append('file', documentForm.value.file);
    formData.append('document_type', documentForm.value.document_type);

    router.post(route('forfaiting.customers.upload-document', props.customer.id), formData, {
        preserveScroll: true,
        onSuccess: () => {
            documentForm.value.file = null;
            documentForm.value.document_type = '';
            if (fileInput.value) fileInput.value.value = '';
        },
    });
};

const formatCurrency = (value: number) => {
    return new Intl.NumberFormat('en-AE', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(value);
};

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
};
</script>

