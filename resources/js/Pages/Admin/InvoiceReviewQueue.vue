<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, usePage } from '@inertiajs/vue3';
import { ref, watchEffect, onMounted, computed } from 'vue';
import Badge from '@/Components/Badge.vue';
import DarkInput from '@/Components/DarkInput.vue';
import GradientButton from '@/Components/GradientButton.vue';

type Invoice = {
  id: number;
  invoice_number: string;
  status: string;
  amount: number;
  supplier_id: number;
  buyer_id: number;
  due_date: string;
  created_at: string;
  assigned_to: number | null;
  priority: number | null;
  supplier?: { legal_name: string; company_name: string };
  buyer?: { name: string };
};

const loading = ref(false);
const error = ref<string | null>(null);
const data = ref<{ data: Invoice[]; meta?: any } | null>(null);

const status = ref<string>('under_review');
const assigned_to = ref<string>('');
const age = ref<string>('');
const sort = ref<string>('created_at');
const dir = ref<string>('asc');
const page = ref<number>(1);
const reviewers = ref<Array<{ id: number; name: string }>>([]);
const pageCtx = usePage();
const currentUserId = (pageCtx.props as any)?.auth?.user?.id as number | undefined;

// Review Modal State
const showReviewModal = ref(false);
const selectedInvoice = ref<Invoice | null>(null);
const reviewForm = ref({
    notes: '',
    priority: 0,
    repayment_parts: 1,
    repayment_interval_days: 30,
    extra_percentage: 0
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
    if (assigned_to.value) p.set('assigned_to', assigned_to.value);
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

async function claim(invoiceId: number) {
  try {
    const res = await fetch(`/admin/api/invoice-review/${invoiceId}/claim`, { 
        method: 'POST', 
        headers: { 
            'X-Requested-With': 'XMLHttpRequest', 
            'X-CSRF-TOKEN': (document.querySelector('meta[name=csrf-token]') as HTMLMetaElement)?.content 
        }, 
        credentials: 'same-origin' 
    });
    if (!res.ok) throw new Error(String(res.status));
    notify('Invoice claimed', 'success');
    load();
  } catch (e) {
    notify('Failed to claim', 'error');
  }
}

function openReview(invoice: Invoice) {
    selectedInvoice.value = invoice;
    reviewForm.value = {
        notes: '',
        priority: invoice.priority || 0,
        repayment_parts: 1,
        repayment_interval_days: 30,
        extra_percentage: 0
    };
    showReviewModal.value = true;
}

async function submitReview(action: 'approve' | 'reject') {
    if (!selectedInvoice.value) return;
    
    try {
        const url = `/admin/api/invoice-review/${selectedInvoice.value.id}/${action}`;
        const res = await fetch(url, { 
            method: 'POST', 
            headers: { 
                'Content-Type': 'application/json', 
                'X-CSRF-TOKEN': (document.querySelector('meta[name=csrf-token]') as HTMLMetaElement)?.content 
            }, 
            body: JSON.stringify(reviewForm.value),
            credentials: 'same-origin' 
        });
        
        if (!res.ok) throw new Error(String(res.status));
        
        notify(`Invoice ${action}d successfully`, 'success');
        showReviewModal.value = false;
        load();
    } catch (e) {
        notify(`Failed to ${action} invoice`, 'error');
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

function formatCurrency(amount: number) {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
    }).format(amount);
}
</script>

<template>
  <Head title="Invoice Review Queue" />
  <AuthenticatedLayout>
    <div class="space-y-6">
      <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-dark-text-primary">Invoice Review Queue</h1>
      </div>

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
            <label class="block text-sm font-medium text-dark-text-secondary mb-2">Assigned To</label>
            <select v-model="assigned_to" class="input-dark">
              <option value="">All Assignees</option>
              <option value="unassigned">Unassigned</option>
              <option v-for="u in reviewers" :key="u.id" :value="String(u.id)">{{ u.name }}</option>
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
                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-dark-text-secondary">Supplier</th>
                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-dark-text-secondary">Buyer</th>
                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-dark-text-secondary">Amount</th>
                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-dark-text-secondary">Status</th>
                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-dark-text-secondary">Assigned</th>
                <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-dark-text-secondary">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="inv in data?.data || []" :key="inv.id" class="hover:bg-dark-secondary">
                <td class="px-4 py-3 font-medium text-dark-text-primary">{{ inv.invoice_number }}</td>
                <td class="px-4 py-3 text-dark-text-secondary">
                  {{ inv.supplier?.company_name || inv.supplier?.legal_name || 'N/A' }}
                </td>
                <td class="px-4 py-3 text-dark-text-secondary">{{ inv.buyer?.name || 'N/A' }}</td>
                <td class="px-4 py-3 text-dark-text-primary font-semibold">{{ formatCurrency(inv.amount) }}</td>
                <td class="px-4 py-3">
                  <Badge :variant="getStatusBadge(inv.status)">{{ inv.status.replace('_', ' ') }}</Badge>
                </td>
                <td class="px-4 py-3 text-dark-text-secondary">
                    <span v-if="inv.assigned_to === currentUserId" class="text-purple-accent font-medium">Me</span>
                    <span v-else-if="inv.assigned_to">{{ reviewers.find(r => r.id === inv.assigned_to)?.name || 'Other' }}</span>
                    <span v-else class="text-dark-text-muted italic">Unassigned</span>
                </td>
                <td class="px-4 py-3 text-right">
                  <div class="flex items-center justify-end gap-2">
                    <button v-if="!inv.assigned_to" @click="claim(inv.id)" class="btn-primary text-xs py-1.5 px-3">
                      Claim
                    </button>
                    <button v-if="inv.assigned_to === currentUserId && (inv.status === 'draft' || inv.status === 'under_review')" 
                            @click="openReview(inv)" 
                            class="bg-purple-accent hover:bg-purple-hover text-white text-xs font-medium py-1.5 px-3 rounded-lg transition-colors">
                      Review
                    </button>
                  </div>
                </td>
              </tr>
              <tr v-if="!loading && (!data?.data || data.data.length === 0)">
                <td colspan="7" class="px-4 py-8 text-center text-dark-text-muted">
                  No invoices found in queue
                </td>
              </tr>
              <tr v-if="loading">
                <td colspan="7" class="px-4 py-8 text-center text-dark-text-muted">
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

    <!-- Review Modal -->
    <div v-if="showReviewModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="showReviewModal = false"></div>
        <div class="relative w-full max-w-lg bg-dark-card border border-dark-border rounded-xl shadow-2xl overflow-hidden">
            <div class="px-6 py-4 border-b border-dark-border flex items-center justify-between">
                <h3 class="text-lg font-semibold text-dark-text-primary">Review Invoice #{{ selectedInvoice?.invoice_number }}</h3>
                <button @click="showReviewModal = false" class="text-dark-text-secondary hover:text-dark-text-primary">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
            </div>
            <div class="p-6 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-dark-text-secondary mb-2">Review Notes</label>
                    <textarea v-model="reviewForm.notes" class="input-dark w-full h-24 resize-none" placeholder="Provide reason for approval/rejection..."></textarea>
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-dark-text-secondary mb-2">Repayment Parts</label>
                        <select v-model="reviewForm.repayment_parts" class="input-dark">
                            <option v-for="n in 12" :key="n" :value="n">{{ n }} parts</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-dark-text-secondary mb-2">Interval (Days)</label>
                        <select v-model="reviewForm.repayment_interval_days" class="input-dark">
                            <option :value="30">30 Days</option>
                            <option :value="60">60 Days</option>
                            <option :value="90">90 Days</option>
                        </select>
                    </div>
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-dark-text-secondary mb-2">Priority (0-255)</label>
                        <input type="number" v-model="reviewForm.priority" class="input-dark" min="0" max="255" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-dark-text-secondary mb-2">Extra Rate (%)</label>
                        <input type="number" v-model="reviewForm.extra_percentage" class="input-dark" step="0.1" />
                    </div>
                </div>
            </div>
            <div class="px-6 py-4 bg-dark-secondary flex items-center justify-end gap-3">
                <button @click="submitReview('reject')" class="px-4 py-2 bg-red-500/10 text-red-500 hover:bg-red-500/20 rounded-lg font-medium transition-colors">Reject Invoice</button>
                <button @click="submitReview('approve')" class="px-4 py-2 bg-green-500 text-white hover:bg-green-600 rounded-lg font-medium transition-colors">Approve Invoice</button>
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
