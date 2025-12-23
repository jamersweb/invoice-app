<script setup lang="ts">
import { ref, onMounted, watch } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import GradientButton from '@/Components/GradientButton.vue';
import { 
    PlusIcon, 
    ArrowPathIcon, 
    DocumentArrowUpIcon, 
    DocumentPlusIcon,
    TrashIcon,
    ArrowDownTrayIcon,
    PaperAirplaneIcon,
    FunnelIcon,
    XMarkIcon,
    CheckCircleIcon,
    ClockIcon,
    TagIcon,
    LinkIcon
} from '@heroicons/vue/24/outline/index.js';

const props = defineProps<{
    templates: any[];
    suppliers: any[];
}>();

const agreements = ref<any>({ data: [] });
const loading = ref(false);
const showCreateModal = ref(false);
const createStep = ref(1); // 1: Supplier, 2: Type, 3: Invoice, 4: Details
const modalLoading = ref(false);

const filters = ref({
    supplier_id: '',
    status: ''
});

const form = ref({
    supplier_id: '',
    invoice_id: '',
    type: 'template', // 'template' or 'upload'
    template_id: '',
    document_type: '',
    category: 'contract',
    notes: '',
    file: null as File | null,
    variables: {} as any
});

const supplierInvoices = ref<any[]>([]);

const fetchAgreements = async (page = 1) => {
    loading.value = true;
    try {
        const response = await axios.get(route('admin.api.agreements.list'), {
            params: {
                page,
                ...filters.value
            }
        });
        agreements.value = response.data;
    } catch (e) {
        console.error(e);
    } finally {
        loading.value = false;
    }
};

const handleSupplierSelect = async () => {
    if (!form.value.supplier_id) return;
    modalLoading.value = true;
    try {
        const response = await axios.get(route('admin.api.agreements.supplier_invoices', form.value.supplier_id));
        supplierInvoices.value = response.data;
        createStep.value = 2;
    } catch (e) {
        console.error(e);
    } finally {
        modalLoading.value = false;
    }
};

const handleTypeSelect = (type: 'template' | 'upload') => {
    form.value.type = type;
    createStep.value = 3;
};

const handleInvoiceSelect = (id: string) => {
    form.value.invoice_id = id;
    createStep.value = 4;
};

const handleFileChange = (e: any) => {
    form.value.file = e.target.files[0];
};

const submitCreate = async () => {
    modalLoading.value = true;
    try {
        if (form.value.type === 'template') {
            await axios.post(route('admin.api.agreements.generate'), {
                supplier_id: form.value.supplier_id,
                invoice_id: form.value.invoice_id,
                template_id: form.value.template_id,
                variables: {
                    ...form.value.variables,
                    invoice_id: form.value.invoice_id
                }
            });
        } else {
            const formData = new FormData();
            formData.append('supplier_id', form.value.supplier_id);
            if (form.value.invoice_id) formData.append('invoice_id', form.value.invoice_id);
            formData.append('document_type', form.value.document_type || 'Manual Upload');
            formData.append('category', form.value.category);
            formData.append('notes', form.value.notes);
            if (form.value.file) formData.append('file', form.value.file);

            await axios.post(route('admin.api.agreements.upload'), formData, {
                headers: { 'Content-Type': 'multipart/form-data' }
            });
        }
        showCreateModal.value = false;
        resetForm();
        fetchAgreements();
    } catch (e) {
        alert('Error creating agreement. Please check details.');
        console.error(e);
    } finally {
        modalLoading.value = false;
    }
};

const deleteAgreement = async (id: number) => {
    if (!confirm('Are you sure you want to delete this agreement?')) return;
    try {
        await axios.delete(route('admin.api.agreements.delete', id));
        fetchAgreements();
    } catch (e) {
        console.error(e);
    }
};

const sendAgreement = async (id: number) => {
    try {
        await axios.post(route('admin.api.agreements.send', id));
        fetchAgreements();
        alert('Contract sent to customer.');
    } catch (e) {
        console.error(e);
    }
};

const resetForm = () => {
    form.value = {
        supplier_id: '',
        invoice_id: '',
        type: 'template',
        template_id: '',
        document_type: '',
        category: 'contract',
        notes: '',
        file: null,
        variables: {}
    };
    createStep.value = 1;
    supplierInvoices.value = [];
};

const getStatusColor = (status: string) => {
    switch (status.toLowerCase()) {
        case 'signed': return 'text-emerald-400 bg-emerald-400/10 border-emerald-400/20';
        case 'sent': return 'text-blue-400 bg-blue-400/10 border-blue-400/20';
        case 'draft': return 'text-amber-400 bg-amber-400/10 border-amber-400/20';
        default: return 'text-slate-400 bg-slate-400/10 border-slate-400/20';
    }
};

onMounted(() => fetchAgreements());

watch(filters, () => fetchAgreements(), { deep: true });
</script>

<template>
    <Head title="Agreements Hub" />

    <AuthenticatedLayout>
        <div class="p-6 max-w-7xl mx-auto">
            <!-- Header -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
                <div>
                    <h1 class="text-3xl font-bold text-white">Agreements Hub</h1>
                    <p class="text-slate-400 mt-1">Manage, generate, and track all supplier documents.</p>
                </div>
                <div class="flex gap-3">
                    <GradientButton @click="showCreateModal = true">
                        New Agreement
                    </GradientButton>
                </div>
            </div>

            <!-- Filters -->
            <div class="flex flex-wrap gap-6 mb-10">
                <div class="min-w-[280px]">
                    <label class="block text-[11px] uppercase tracking-widest text-slate-500 font-bold mb-2 ml-1">Filter by Supplier</label>
                    <div class="glass-card bg-slate-800/20 border-slate-700/40 hover:border-slate-600/50 transition-all shadow-lg overflow-hidden">
                        <select v-model="filters.supplier_id" class="block w-full bg-transparent border-none focus:ring-0 text-white text-sm cursor-pointer py-3.5 px-4 rounded-xl">
                            <option value="" class="bg-slate-900 text-white">All Partners</option>
                            <option v-for="s in props.suppliers" :key="s.id" :value="s.id" class="bg-slate-900 text-white">{{ s.company_name }}</option>
                        </select>
                    </div>
                </div>
                
                <div class="min-w-[220px]">
                    <label class="block text-[11px] uppercase tracking-widest text-slate-500 font-bold mb-2 ml-1">Agreement Status</label>
                    <div class="glass-card bg-slate-800/20 border-slate-700/40 hover:border-slate-600/50 transition-all shadow-lg overflow-hidden">
                        <select v-model="filters.status" class="block w-full bg-transparent border-none focus:ring-0 text-white text-sm cursor-pointer py-3.5 px-4 rounded-xl">
                            <option value="" class="bg-slate-900 text-white">Any Status</option>
                            <option value="Draft" class="bg-slate-900 text-white">Draft</option>
                            <option value="Sent" class="bg-slate-900 text-white">Sent</option>
                            <option value="Signed" class="bg-slate-900 text-white">Signed</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Table -->
            <div class="glass-card overflow-hidden border border-slate-700/30 shadow-2xl">
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="bg-slate-800/80 border-b border-slate-700/50">
                                <th class="px-6 py-5 text-slate-400 font-semibold text-xs uppercase tracking-wider">Agreement / Document</th>
                                <th class="px-6 py-5 text-slate-400 font-semibold text-xs uppercase tracking-wider">Supplier</th>
                                <th class="px-6 py-5 text-slate-400 font-semibold text-xs uppercase tracking-wider">Target Invoice</th>
                                <th class="px-6 py-5 text-slate-400 font-semibold text-xs uppercase tracking-wider text-center">Status</th>
                                <th class="px-6 py-5 text-slate-400 font-semibold text-xs uppercase tracking-wider text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-700/50">
                            <tr v-for="agr in agreements.data" :key="agr.id" class="hover:bg-slate-700/30 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="p-2 bg-indigo-500/10 rounded-lg">
                                            <DocumentPlusIcon v-if="agr.template" class="w-5 h-5 text-indigo-400" />
                                            <DocumentArrowUpIcon v-else class="w-5 h-5 text-blue-400" />
                                        </div>
                                        <div>
                                            <p class="text-white font-medium">{{ agr.template ? agr.template.name : agr.document_type }}</p>
                                            <p class="text-slate-500 text-xs">{{ agr.file_name || 'Template Generated' }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <p class="text-slate-300">{{ agr.supplier?.company_name || 'N/A' }}</p>
                                    <p class="text-slate-500 text-xs">{{ agr.supplier?.contact_email }}</p>
                                </td>
                                <td class="px-6 py-4">
                                    <div v-if="agr.invoice" class="flex items-center gap-1.5 text-slate-300">
                                        <LinkIcon class="w-4 h-4 text-slate-500" />
                                        <span>#{{ agr.invoice.invoice_number }}</span>
                                    </div>
                                    <span v-else class="text-slate-600">-</span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex justify-center">
                                        <span :class="[getStatusColor(agr.status), 'px-2.5 py-1 rounded-full text-xs font-semibold border']">
                                            {{ agr.status }}
                                        </span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex justify-end gap-2 text-slate-400">
                                        <button v-if="agr.status === 'Draft'" @click="sendAgreement(agr.id)" class="p-2 hover:text-indigo-400 transition-colors" title="Send for signing">
                                            <PaperAirplaneIcon class="w-5 h-5" />
                                        </button>
                                        <a v-if="agr.signed_pdf" :href="'/storage/' + agr.signed_pdf" target="_blank" class="p-2 hover:text-emerald-400 transition-colors" title="Download">
                                            <ArrowDownTrayIcon class="w-5 h-5" />
                                        </a>
                                        <button @click="deleteAgreement(agr.id)" class="p-2 hover:text-red-400 transition-colors" title="Delete">
                                            <TrashIcon class="w-5 h-5" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Empty State -->
                <div v-if="agreements.data.length === 0" class="p-12 text-center">
                    <DocumentPlusIcon class="w-16 h-16 text-slate-700 mx-auto mb-4" />
                    <p class="text-slate-500 text-lg">No agreements found.</p>
                    <button @click="showCreateModal = true" class="text-indigo-400 hover:text-indigo-300 font-medium mt-2">Generate your first one</button>
                </div>
            </div>
        </div>

        <!-- Creation Modal -->
        <div v-if="showCreateModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm">
            <div class="glass-card w-full max-w-2xl overflow-hidden animate-in fade-in zoom-in duration-200">
                <div class="p-6 border-b border-slate-700/50 flex justify-between items-center bg-slate-800/50">
                    <div>
                        <h3 class="text-xl font-bold text-white">Create New Agreement</h3>
                        <p class="text-slate-400 text-sm">Step {{ createStep }} of 4</p>
                    </div>
                    <button @click="showCreateModal = false" class="text-slate-400 hover:text-white">
                        <XMarkIcon class="w-6 h-6" />
                    </button>
                </div>

                <div class="p-8">
                    <!-- Step 1: Select Supplier -->
                    <div v-if="createStep === 1" class="space-y-6">
                        <div class="text-center">
                            <div class="w-16 h-16 bg-indigo-500/10 rounded-full flex items-center justify-center mx-auto mb-4">
                                <PlusIcon class="w-8 h-8 text-indigo-400" />
                            </div>
                            <h4 class="text-lg font-semibold text-white">Select a Supplier</h4>
                            <p class="text-slate-400 text-sm">Which partner is this for?</p>
                        </div>
                        <select v-model="form.supplier_id" @change="handleSupplierSelect" class="w-full bg-slate-900/50 border-slate-700 text-white rounded-xl py-3 px-4 focus:ring-indigo-500 focus:border-indigo-500 transition-all cursor-pointer">
                            <option value="" disabled>Choose a supplier...</option>
                            <option v-for="s in props.suppliers" :key="s.id" :value="s.id">{{ s.company_name }}</option>
                        </select>
                        <div v-if="modalLoading" class="flex justify-center">
                            <ArrowPathIcon class="w-6 h-6 text-indigo-400 animate-spin" />
                        </div>
                    </div>

                    <!-- Step 2: Choose Action -->
                    <div v-if="createStep === 2" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <button @click="handleTypeSelect('template')" class="p-6 glass-card hover:border-indigo-500/50 transition-all text-left group">
                            <DocumentPlusIcon class="w-10 h-10 text-indigo-400 mb-4 group-hover:scale-110 transition-transform" />
                            <h4 class="text-lg font-bold text-white mb-2">Generate from Template</h4>
                            <p class="text-slate-400 text-sm">Create a dynamic contract using our legal templates.</p>
                        </button>
                        <button @click="handleTypeSelect('upload')" class="p-6 glass-card hover:border-blue-500/50 transition-all text-left group">
                            <DocumentArrowUpIcon class="w-10 h-10 text-blue-400 mb-4 group-hover:scale-110 transition-transform" />
                            <h4 class="text-lg font-bold text-white mb-2">Manual Upload</h4>
                            <p class="text-slate-400 text-sm">Upload a signed PDF, cheque scan, or other document.</p>
                        </button>
                    </div>

                    <!-- Step 3: Select Invoice -->
                    <div v-if="createStep === 3" class="space-y-4">
                        <h4 class="text-lg font-semibold text-white mb-4">Link to Specific Invoice (Optional)</h4>
                        <div class="grid grid-cols-1 gap-3 max-h-60 overflow-y-auto pr-2 custom-scrollbar">
                            <button @click="handleInvoiceSelect('')" :class="[form.invoice_id === '' ? 'border-indigo-500 bg-indigo-500/10' : 'border-slate-700 hover:bg-slate-800/50']" class="p-4 border rounded-xl text-left transition-all">
                                <p class="text-white font-medium">None / General Document</p>
                                <p class="text-slate-500 text-xs">Not linked to a specific transaction</p>
                            </button>
                            <button v-for="inv in supplierInvoices" :key="inv.id" @click="handleInvoiceSelect(inv.id)" :class="[form.invoice_id === inv.id ? 'border-indigo-500 bg-indigo-500/10' : 'border-slate-700 hover:bg-slate-800/50']" class="p-4 border rounded-xl text-left transition-all flex justify-between items-center">
                                <div>
                                    <p class="text-white font-medium">#{{ inv.invoice_number }}</p>
                                    <p class="text-slate-500 text-xs">{{ inv.created_at }}</p>
                                </div>
                                <p class="text-indigo-400 font-bold">{{ inv.amount?.toLocaleString() }} {{ inv.currency }}</p>
                            </button>
                        </div>
                        <div class="flex justify-between pt-4">
                            <button @click="createStep = 2" class="text-slate-400 hover:text-white">Back</button>
                        </div>
                    </div>

                    <!-- Step 4: Details -->
                    <div v-if="createStep === 4" class="space-y-4">
                        <div v-if="form.type === 'template'">
                            <label class="block text-slate-300 mb-2">Select Template</label>
                            <select v-model="form.template_id" class="w-full bg-slate-900/50 border-slate-700 text-white rounded-xl p-3">
                                <option value="" disabled>Choose template...</option>
                                <option v-for="t in props.templates" :key="t.id" :value="t.id">{{ t.name }} (v{{ t.version }})</option>
                            </select>
                        </div>
                        <div v-else class="space-y-4">
                            <div>
                                <label class="block text-slate-300 mb-2">Document Type</label>
                                <select v-model="form.document_type" class="w-full bg-slate-900/50 border-slate-700 text-white rounded-xl p-3">
                                    <option value="Master Agreement">Master Agreement</option>
                                    <option value="Purchase Agreement">Purchase Agreement</option>
                                    <option value="Cheque">Cheque</option>
                                    <option value="Insurance PDF">Insurance PDF</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-slate-300 mb-2">Attachment</label>
                                <input type="file" @change="handleFileChange" class="w-full text-slate-400 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-semibold file:bg-indigo-600 file:text-white hover:file:bg-indigo-700" />
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-slate-300 mb-2">Notes</label>
                            <textarea v-model="form.notes" class="w-full bg-slate-900/50 border-slate-700 text-white rounded-xl p-3 h-24" placeholder="Brief description..."></textarea>
                        </div>

                        <div class="flex justify-between pt-6 border-t border-slate-700/50">
                            <button @click="createStep = 3" class="text-slate-400 hover:text-white">Back</button>
                            <GradientButton @click="submitCreate" :disabled="modalLoading">
                                <ArrowPathIcon v-if="modalLoading" class="w-5 h-5 mr-2 animate-spin" />
                                <CheckCircleIcon v-else class="w-5 h-5 mr-2" />
                                {{ form.type === 'template' ? 'Generate Agreement' : 'Save Document' }}
                            </GradientButton>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.glass-card {
    background: rgba(15, 23, 42, 0.4);
    backdrop-filter: blur(12px);
    border: 1px solid rgba(51, 65, 85, 0.3);
    border-radius: 1rem;
}

.custom-scrollbar::-webkit-scrollbar {
    width: 6px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: rgba(79, 70, 229, 0.2);
    border-radius: 10px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: rgba(79, 70, 229, 0.4);
}
</style>
