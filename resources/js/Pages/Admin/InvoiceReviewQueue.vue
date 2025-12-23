<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, usePage } from '@inertiajs/vue3';
import { ref, watchEffect, onMounted, computed } from 'vue';
import Badge from '@/Components/Badge.vue';
import DarkInput from '@/Components/DarkInput.vue';
import GradientButton from '@/Components/GradientButton.vue';
import DarkConfirmModal from '@/Components/DarkConfirmModal.vue';

type InvoiceAttachment = {
  id: number;
  file_path: string;
  file_name: string;
};

type Invoice = {
  id: number;
  invoice_number: string;
  status: string;
  amount: number;
  currency: string;
  supplier_id: number;
  buyer_id: number;
  due_date: string;
  created_at: string;
  assigned_to: number | null;
  priority: number | null;
  file_path?: string;
  attachments?: InvoiceAttachment[];
  bank_account_name?: string;
  bank_name?: string;
  bank_branch?: string;
  bank_iban?: string;
  bank_swift?: string;
  supplier?: { legal_name: string; company_name: string };
  buyer?: { name: string };
};

const loading = ref(false);
const submitting = ref<'approve' | 'reject' | null>(null);
const error = ref<string | null>(null);
const data = ref<{ data: Invoice[]; meta?: any } | null>(null);

const viewMode = ref<'queue' | 'details'>('queue');
const status = ref<string>('under_review');
const age = ref<string>('');
const sort = ref<string>('created_at');
const dir = ref<string>('asc');
const page = ref<number>(1);
const reviewers = ref<Array<{ id: number; name: string }>>([]);
const pageCtx = usePage();
const currentUserId = (pageCtx.props as any)?.auth?.user?.id as number | undefined;

// Review State
const selectedInvoice = ref<Invoice | null>(null);
const reviewForm = ref({
    notes: '',
});

// Toasts
type Toast = { id: number; text: string; type?: 'success' | 'error' };
const toasts = ref<Toast[]>([]);
let toastSeq = 0;
function notify(text: string, type: 'success' | 'error' = 'success') {
  const id = ++toastSeq;
  toasts.value.push({ id, text, type });
  setTimeout(() => {
    toasts.value = toasts.value.filter(t => t.id !== id);
  }, 3000);
}

async function load() {
  loading.value = true;
  error.value = null;
  try {
    const p = new URLSearchParams();
    if (status.value) p.set('status', status.value);
    if (age.value) p.set('age', age.value);
    if (sort.value) p.set('sort', sort.value);
    if (dir.value) p.set('dir', dir.value);
    p.set('page', String(page.value));
    
    const res = await fetch(`/admin/api/invoice-review/queue?${p.toString()}`, { 
        headers: { Accept: 'application/json' }, 
        credentials: 'same-origin' 
    });
    if (!res.ok) throw new Error(`HTTP ${res.status}`);
    data.value = await res.json();
  } catch (e: any) {
    error.value = e?.message || 'Failed to load';
  } finally {
    loading.value = false;
  }
}

watchEffect(() => { load(); });

onMounted(async () => {
  const res = await fetch('/api/v1/admin/reviewers', { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
  if (res.ok) reviewers.value = await res.json();
});


const showDeleteModal = ref(false);
const invoiceToDelete = ref<number | null>(null);

function deleteInvoice(invoiceId: number) {
    invoiceToDelete.value = invoiceId;
    showDeleteModal.value = true;
}

async function confirmDeletion() {
    if (!invoiceToDelete.value) return;
    try {
        const res = await fetch(`/admin/api/invoice-review/${invoiceToDelete.value}`, { 
            method: 'DELETE', 
            headers: { 
                'X-Requested-With': 'XMLHttpRequest', 
                'X-CSRF-TOKEN': (document.querySelector('meta[name=csrf-token]') as HTMLMetaElement)?.content 
            }, 
            credentials: 'same-origin' 
        });
        if (!res.ok) throw new Error(String(res.status));
        notify('Invoice deleted', 'success');
        if (selectedInvoice.value?.id === invoiceToDelete.value) {
            viewMode.value = 'queue';
            selectedInvoice.value = null;
        }
        load();
    } catch (e) {
        notify('Failed to delete', 'error');
    } finally {
        showDeleteModal.value = false;
        invoiceToDelete.value = null;
    }
}

function openReview(invoice: Invoice) {
    selectedInvoice.value = invoice;
    reviewForm.value = { notes: '' };
    viewMode.value = 'details';
}

const showContractModal = ref(false); // We'll keep this as a simple boolean toggle or just show it in line
const templates = ref<any[]>([]);
const currentAgreement = ref<any>(null);
const contractForm = ref({
    template_id: '',
    variables: {} as any
});

async function openContractManager(invoice: Invoice) {
    selectedInvoice.value = invoice;
    viewMode.value = 'details';
    loading.value = true;
    try {
        const [tRes, sRes] = await Promise.all([
            fetch('/admin/api/agreements/templates', { headers: { Accept: 'application/json' }, credentials: 'same-origin' }),
            fetch(`/admin/api/agreements/status/${invoice.id}`, { headers: { Accept: 'application/json' }, credentials: 'same-origin' })
        ]);
        
        if (tRes.ok) templates.value = await tRes.json();
        if (sRes.ok) {
            const sData = await sRes.json();
            currentAgreement.value = sData.agreement;
        }

        // Pre-fill variables
        contractForm.value.variables = {
            invoice_number: invoice.invoice_number,
            amount: formatCurrency(invoice.amount, invoice.currency),
            supplier_name: invoice.supplier?.company_name || invoice.supplier?.legal_name,
            buyer_name: invoice.buyer?.name,
            date: new Date().toLocaleDateString()
        };
        
        showContractModal.value = true;
    } catch (e) {
        notify('Failed to load contract details', 'error');
    } finally {
        loading.value = false;
    }
}

async function generateContract() {
    if (!selectedInvoice.value || !contractForm.value.template_id) return;
    
    try {
        const res = await fetch(`/admin/api/agreements/generate/${selectedInvoice.value.id}`, { 
            method: 'POST', 
            headers: { 
                'Content-Type': 'application/json', 
                'X-CSRF-TOKEN': (document.querySelector('meta[name=csrf-token]') as HTMLMetaElement)?.content 
            }, 
            body: JSON.stringify(contractForm.value),
            credentials: 'same-origin' 
        });
        
        if (!res.ok) throw new Error(String(res.status));
        const resData = await res.json();
        currentAgreement.value = resData.agreement;
        notify('Contract draft generated', 'success');
    } catch (e) {
        notify('Failed to generate contract', 'error');
    }
}

async function sendToCustomer() {
    if (!currentAgreement.value) return;
    
    try {
        const res = await fetch(`/admin/api/agreements/${currentAgreement.value.id}/send`, { 
            method: 'POST', 
            headers: { 
                'X-Requested-With': 'XMLHttpRequest', 
                'X-CSRF-TOKEN': (document.querySelector('meta[name=csrf-token]') as HTMLMetaElement)?.content 
            }, 
            credentials: 'same-origin' 
        });
        
        if (!res.ok) throw new Error(String(res.status));
        currentAgreement.value.status = 'Sent';
        notify('Contract sent to customer', 'success');
    } catch (e) {
        notify('Failed to send contract', 'error');
    }
}

async function submitReview(action: 'approve' | 'reject') {
    if (!selectedInvoice.value) return;
    if (action === 'reject' && !reviewForm.value.notes.trim()) {
        notify('Notes are required for rejection', 'error');
        return;
    }

    submitting.value = action;
    try {
        const url = `/admin/api/invoice-review/${selectedInvoice.value.id}/${action}`;
        const res = await fetch(url, { 
            method: 'POST', 
            headers: { 
                'Content-Type': 'application/json', 
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': (document.querySelector('meta[name=csrf-token]') as HTMLMetaElement)?.content 
            }, 
            body: JSON.stringify(reviewForm.value),
            credentials: 'same-origin' 
        });
        
        const data = await res.json();
        
        if (!res.ok) {
            throw new Error(data.message || `HTTP ${res.status}`);
        }
        
        notify(`Invoice ${action}d successfully`, 'success');
        viewMode.value = 'queue';
        selectedInvoice.value = null;
        load();
    } catch (e: any) {
        notify(e.message || `Failed to ${action} invoice`, 'error');
    } finally {
        submitting.value = null;
    }
}

function getStatusBadge(status: string): 'warning' | 'success' | 'danger' | 'info' {
  if (status === 'draft') return 'warning';
  if (status === 'under_review') return 'info';
  if (status === 'approved') return 'success';
  if (status === 'rejected') return 'danger';
  if (status === 'funded') return 'success';
  return 'info';
}

function formatCurrency(amount: number, currency: string = 'USD') {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: currency || 'USD',
    }).format(amount);
}

function formatDate(dateString: string | undefined) {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    return isNaN(date.getTime()) ? dateString : date.toLocaleDateString('en-GB', {
        day: '2-digit',
        month: 'short',
        year: 'numeric'
    });
}
</script>

<template>
  <Head title="Invoice Review Queue" />
  <AuthenticatedLayout>
    <div class="space-y-6">
      <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-dark-text-primary">
          <span v-if="viewMode === 'queue'">Invoice Review Queue</span>
          <span v-else class="flex items-center gap-2 cursor-pointer hover:text-purple-accent transition-colors" @click="viewMode = 'queue'">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h16.5" /></svg>
            Review Invoice #{{ selectedInvoice?.invoice_number }}
          </span>
        </h1>
      </div>

      <!-- Queue View -->
      <div v-if="viewMode === 'queue'" class="space-y-6">
        <!-- Filters -->
        <div class="card">
          <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div>
              <label class="block text-sm font-medium text-dark-text-secondary mb-2">Status</label>
              <select v-model="status" class="input-dark">
                <option value="">All Status</option>
                <option value="draft">Draft/Submitted</option>
                <option value="under_review">Under Review</option>
                <option value="approved">Approved</option>
                <option value="rejected">Rejected</option>
                <option value="funded">Funded</option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-medium text-dark-text-secondary mb-2">Age</label>
              <DarkInput v-model="age" placeholder="e.g. 24h, 2d" />
            </div>
            <div class="flex items-end">
              <GradientButton @click="load" size="md" class="w-full">Apply Filters</GradientButton>
            </div>
          </div>
        </div>

        <div v-if="error" class="card bg-red-500/20 border-red-500/30">
          <p class="text-sm text-red-400">{{ error }}</p>
        </div>

        <!-- Results Table -->
        <div class="card overflow-hidden p-0">
          <div class="overflow-x-auto custom-scrollbar">
            <table class="table-dark bg-none">
              <thead>
                <tr>
                  <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-dark-text-secondary">Invoice #</th>
                  <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-dark-text-secondary">Customer</th>
                  <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-dark-text-secondary">Customer Email</th>
                  <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-dark-text-secondary">Amount</th>
                  <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-dark-text-secondary">Status</th>
                  <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-dark-text-secondary">Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="inv in data?.data || []" :key="inv.id" class="hover:bg-dark-secondary">
                  <td class="px-4 py-3 font-medium text-dark-text-primary">{{ inv.invoice_number }}</td>
                  <td class="px-4 py-3 text-dark-text-secondary">
                    {{ inv.supplier?.company_name || inv.supplier?.legal_name || 'N/A' }}
                  </td>
                  <td class="px-4 py-3 text-dark-text-secondary">{{ (inv.supplier as any)?.contact_email || 'N/A' }}</td>
                  <td class="px-4 py-3 text-dark-text-primary font-semibold">{{ formatCurrency(inv.amount, inv.currency) }}</td>
                  <td class="px-4 py-3">
                    <Badge :variant="getStatusBadge(inv.status)">{{ inv.status.replace('_', ' ') }}</Badge>
                  </td>
                  <td class="px-4 py-3 text-right">
                    <div class="flex items-center justify-end gap-3 text-dark-text-secondary">
                      <!-- View Button -->
                      <button @click="openReview(inv)" class="hover:text-purple-accent transition-colors" title="View/Review">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                      </button>

                      <!-- Sign Contract Button (Only enabled if approved) -->
                      <button @click="openContractManager(inv)" 
                              :disabled="inv.status !== 'approved'"
                              class="transition-colors disabled:opacity-30 disabled:cursor-not-allowed"
                              :class="inv.status === 'approved' ? 'hover:text-blue-500' : ''"
                              title="Manage Contract">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" /></svg>
                      </button>

                      <!-- Delete Button -->
                      <button @click="deleteInvoice(inv.id)" class="hover:text-red-500 transition-colors" title="Delete">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" /></svg>
                      </button>
                    </div>
                  </td>
                </tr>
                <tr v-if="!loading && (!data?.data || data.data.length === 0)">
                  <td colspan="6" class="px-4 py-8 text-center text-dark-text-muted">
                    No invoices found in queue
                  </td>
                </tr>
                <tr v-if="loading">
                  <td colspan="6" class="px-4 py-8 text-center text-dark-text-muted">
                    Loading queue...
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Pagination -->
        <div v-if="data?.meta" class="flex items-center justify-between text-sm">
          <div class="text-dark-text-secondary">
            Showing {{ data.meta.from }} to {{ data.meta.to }} of {{ data.meta.total }}
          </div>
          <div class="flex items-center gap-2">
            <button :disabled="data.meta.current_page === 1"
              @click="page = data.meta.current_page - 1"
              class="btn-secondary text-sm py-2 px-4 disabled:opacity-50">
              Prev
            </button>
            <button :disabled="data.meta.current_page === data.meta.last_page"
              @click="page = data.meta.current_page + 1"
              class="btn-secondary text-sm py-2 px-4 disabled:opacity-50">
              Next
            </button>
          </div>
        </div>
      </div>

      <!-- Detail View -->
      <div v-else class="grid grid-cols-1 lg:grid-cols-3 gap-6 animate-slide-up">
        <div class="lg:col-span-2 space-y-6">
            <!-- Invoice Details Card -->
            <div class="card p-0 overflow-hidden">
                <div class="px-6 py-4 border-b border-dark-border flex items-center justify-between bg-dark-card">
                    <h3 class="text-lg font-semibold text-dark-text-primary">Invoice Summary</h3>
                    <Badge :variant="getStatusBadge(selectedInvoice?.status || '')">{{ selectedInvoice?.status.replace('_', ' ') }}</Badge>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-6 p-4 bg-dark-secondary/50 rounded-xl mb-6">
                        <div>
                            <p class="text-[10px] text-dark-text-secondary uppercase font-bold tracking-wider mb-1">Amount</p>
                            <p class="text-xl font-bold text-purple-accent">{{ formatCurrency(selectedInvoice?.amount || 0, selectedInvoice?.currency) }}</p>
                        </div>
                        <div>
                            <p class="text-[10px] text-dark-text-secondary uppercase font-bold tracking-wider mb-1">Invoice Date</p>
                            <p class="text-sm font-medium text-dark-text-primary">{{ selectedInvoice?.created_at ? new Date(selectedInvoice.created_at).toLocaleDateString() : 'N/A' }}</p>
                        </div>
                        <div>
                            <p class="text-[10px] text-dark-text-secondary uppercase font-bold tracking-wider mb-1">Due Date</p>
                            <p class="text-sm font-medium text-dark-text-primary text-red-400 font-bold">{{ formatDate(selectedInvoice?.due_date) }}</p>
                        </div>
                        <div>
                            <p class="text-[10px] text-dark-text-secondary uppercase font-bold tracking-wider mb-1">Status</p>
                            <p class="text-sm font-medium text-dark-text-primary">{{ selectedInvoice?.status }}</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="p-4 border border-dark-border rounded-xl">
                            <p class="text-xs text-dark-text-secondary font-bold uppercase mb-3">Customer Information</p>
                            <p class="text-sm font-bold text-dark-text-primary">{{ selectedInvoice?.supplier?.company_name || selectedInvoice?.supplier?.legal_name }}</p>
                            <p class="text-xs text-dark-text-muted mt-1">{{ (selectedInvoice?.supplier as any)?.contact_email }}</p>
                        </div>
                    </div>

                    <!-- Bank Details Section -->
                    <div v-if="selectedInvoice?.bank_account_name || selectedInvoice?.bank_name" class="mt-8 pt-8 border-t border-dark-border">
                        <h4 class="text-sm font-bold text-dark-text-primary uppercase tracking-widest mb-6 border-l-4 border-emerald-500 pl-3">Bank Transfer Details</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            <div v-if="selectedInvoice.bank_account_name">
                                <p class="text-[10px] text-dark-text-secondary uppercase font-bold mb-1">Account Name</p>
                                <p class="text-sm font-medium text-dark-text-primary">{{ selectedInvoice.bank_account_name }}</p>
                            </div>
                            <div v-if="selectedInvoice.bank_name">
                                <p class="text-[10px] text-dark-text-secondary uppercase font-bold mb-1">Bank</p>
                                <p class="text-sm font-medium text-dark-text-primary">{{ selectedInvoice.bank_name }}</p>
                            </div>
                            <div v-if="selectedInvoice.bank_branch">
                                <p class="text-[10px] text-dark-text-secondary uppercase font-bold mb-1">Branch</p>
                                <p class="text-sm font-medium text-dark-text-primary">{{ selectedInvoice.bank_branch }}</p>
                            </div>
                            <div v-if="selectedInvoice.bank_iban">
                                <p class="text-[10px] text-dark-text-secondary uppercase font-bold mb-1">IBAN</p>
                                <p class="text-sm font-medium text-dark-text-primary font-mono">{{ selectedInvoice.bank_iban }}</p>
                            </div>
                            <div v-if="selectedInvoice.bank_swift">
                                <p class="text-[10px] text-dark-text-secondary uppercase font-bold mb-1">SWIFT</p>
                                <p class="text-sm font-medium text-dark-text-primary font-mono">{{ selectedInvoice.bank_swift }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Documents Section -->
            <div class="card p-0 overflow-hidden">
                <div class="px-6 py-4 border-b border-dark-border bg-dark-card flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-dark-text-primary">Invoice Documents</h3>
                    <Badge variant="info">{{ (selectedInvoice?.attachments?.length || (selectedInvoice?.file_path ? 1 : 0)) }} Files</Badge>
                </div>
                <div class="p-6 space-y-4">
                    <!-- New Attachments -->
                    <div v-if="selectedInvoice?.attachments?.length" class="grid grid-cols-1 gap-4">
                        <div v-for="att in selectedInvoice.attachments" :key="att.id" 
                             class="group flex items-center justify-between p-4 bg-dark-secondary/50 border border-dark-border rounded-xl hover:border-purple-accent/50 transition-all">
                            <div class="flex items-center gap-4">
                                <div class="w-10 h-10 bg-purple-accent/20 rounded-lg flex items-center justify-center text-purple-accent">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" /></svg>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-dark-text-primary truncate max-w-[200px]">{{ att.file_name }}</p>
                                    <p class="text-[10px] text-dark-text-muted uppercase font-bold tracking-widest">Document Attachment</p>
                                </div>
                            </div>
                            <a :href="'/storage/' + att.file_path" target="_blank" 
                               class="opacity-0 group-hover:opacity-100 px-4 py-2 bg-purple-accent hover:bg-purple-hover text-white text-xs font-bold rounded-lg transition-all shadow-lg shadow-purple-accent/20">
                                View File
                            </a>
                        </div>
                    </div>

                    <!-- Legacy File Support -->
                    <div v-else-if="selectedInvoice?.file_path" class="p-4 bg-dark-secondary/50 border border-dark-border rounded-xl flex items-center justify-between">
                         <div class="flex items-center gap-4">
                            <div class="w-10 h-10 bg-purple-accent/20 rounded-lg flex items-center justify-center text-purple-accent">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" /></svg>
                            </div>
                            <p class="text-sm font-medium text-dark-text-primary">Main Invoice Document</p>
                        </div>
                        <a :href="'/storage/' + selectedInvoice.file_path" target="_blank" class="px-4 py-2 bg-purple-accent hover:bg-purple-hover text-white text-xs font-bold rounded-lg transition-all">View File</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="space-y-6">
            <!-- Review Actions -->
            <div v-if="selectedInvoice?.status === 'draft' || selectedInvoice?.status === 'under_review'" class="card">
                <h3 class="text-md font-bold text-dark-text-primary mb-4 border-l-4 border-purple-accent pl-2">Take Action</h3>
                <div class="space-y-4">
                    <p class="text-xs text-dark-text-secondary leading-relaxed">Please review the invoice details and document above before making a decision.</p>
                    
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-dark-text-secondary mb-2">Review Notes</label>
                            <textarea v-model="reviewForm.notes" class="input-dark w-full h-32 resize-none" placeholder="Provide reason for approval/rejection..."></textarea>
                        </div>
                        <div class="flex flex-col gap-3">
                            <GradientButton 
                                @click="submitReview('approve')" 
                                :disabled="!!submitting"
                                class="!bg-green-600 !hover:bg-green-700 shadow-lg shadow-green-500/20"
                            >
                                {{ submitting === 'approve' ? 'Processing...' : 'Approve Invoice' }}
                            </GradientButton>
                            
                            <button 
                                @click="submitReview('reject')" 
                                :disabled="!!submitting"
                                class="w-full py-2.5 bg-red-500/10 text-red-500 border border-red-500/20 hover:bg-red-500/20 rounded-xl font-bold transition-all disabled:opacity-50"
                            >
                                {{ submitting === 'reject' ? 'Processing...' : 'Reject Invoice' }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Contract Management (In Details Page) -->
            <div v-if="selectedInvoice?.status === 'approved'" class="card">
                <h3 class="text-md font-bold text-dark-text-primary mb-4 border-l-4 border-blue-500 pl-2">Contract Manager</h3>
                <div class="space-y-4">
                    <!-- Status Bar -->
                    <div class="flex items-center gap-4 p-4 bg-dark-secondary rounded-xl border border-dark-border">
                        <div class="flex-1">
                            <p class="text-xs text-dark-text-secondary uppercase font-bold tracking-wider">Status</p>
                            <p class="text-sm font-medium text-dark-text-primary">
                                {{ currentAgreement ? currentAgreement.status : 'Not Generated' }}
                            </p>
                        </div>
                        <Badge v-if="currentAgreement" :variant="currentAgreement.status === 'Signed' ? 'success' : 'warning'">
                            {{ currentAgreement.status }}
                        </Badge>
                    </div>

                    <!-- Step 1: Generate -->
                    <div v-if="!currentAgreement" class="space-y-4">
                        <div>
                            <label class="block text-xs font-medium text-dark-text-secondary mb-2">Agreement Template</label>
                            <select v-model="contractForm.template_id" class="input-dark text-sm" @change="templates.length ? null : openContractManager(selectedInvoice!)">
                                <option value="">Choose template...</option>
                                <option v-for="t in templates" :key="t.id" :value="t.id">{{ t.name }}</option>
                            </select>
                        </div>
                        <div v-if="contractForm.template_id" class="space-y-2">
                             <div v-for="(val, key) in contractForm.variables" :key="key">
                                <label class="block text-[10px] text-dark-text-secondary uppercase font-bold mb-1">{{ key.replace('_', ' ') }}</label>
                                <input v-model="contractForm.variables[key]" class="input-dark text-xs p-2 h-8" />
                            </div>
                        </div>

                        <button @click="generateContract" 
                                :disabled="!contractForm.template_id" 
                                class="w-full py-2 bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white rounded-xl font-bold text-sm transition-all shadow-lg shadow-blue-500/20">
                            Generate Draft
                        </button>
                    </div>

                    <!-- Step 2: Management -->
                    <div v-else class="space-y-4">
                        <div class="flex gap-2">
                            <button v-if="currentAgreement.status === 'Draft'" 
                                    @click="sendToCustomer" 
                                    class="flex-1 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl font-bold text-sm transition-all">
                                Send to Portal
                            </button>
                            <div v-else-if="currentAgreement.status === 'Sent'" 
                                 class="flex-1 py-2 bg-dark-secondary text-dark-text-muted rounded-xl font-bold text-center text-xs">
                                Waiting for Customer
                            </div>
                            <div v-else-if="currentAgreement.status === 'Signed'" 
                                 class="flex-1 py-2 bg-green-500/10 text-green-500 rounded-xl font-bold text-center text-xs border border-green-500/20">
                                Signed & Validated
                            </div>
                        </div>
                        <button v-if="currentAgreement.status === 'Draft'" @click="currentAgreement = null" class="w-full text-[10px] text-dark-text-muted hover:text-red-400 font-bold uppercase tracking-widest text-center">
                            Reset & Regenerate
                        </button>
                    </div>
                </div>
            </div>
        </div>
      </div>
    </div>

    <!-- Toasts -->
    <div class="fixed bottom-4 right-4 space-y-2 z-[60]">
      <div v-for="t in toasts" :key="t.id" :class="[
        'rounded-lg px-4 py-2 shadow-lg border border-white/10 animate-slide-up',
        t.type === 'error' ? 'bg-red-500/90 text-white' : 'bg-green-500/90 text-white'
      ]">
        {{ t.text }}
      </div>
    </div>

    <DarkConfirmModal 
        :show="showDeleteModal"
        title="Delete Invoice"
        message="Are you sure you want to delete this invoice? This action cannot be undone."
        confirm-text="Delete"
        type="danger"
        @close="showDeleteModal = false"
        @confirm="confirmDeletion"
    />
  </AuthenticatedLayout>
</template>

<style scoped>
.animate-slide-up {
    animation: slideUp 0.3s ease-out;
}
@keyframes slideUp {
    from { transform: translateY(20px); opacity: 0; }
    to { transform: translateY(0); opacity: 1; }
}
</style>
