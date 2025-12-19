<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { ref, watchEffect, onMounted, computed } from 'vue';
import Badge from '@/Components/Badge.vue';
import DarkInput from '@/Components/DarkInput.vue';
import GradientButton from '@/Components/GradientButton.vue';

type Doc = {
  id: number;
  document_type_id: number;
  status: string;
  owner_type: string;
  owner_id: number;
  created_at: string;
  assigned_to: number | null;
  priority: number | null;
  vip: boolean | 0 | 1 | null;
};

const loading = ref(false);
const error = ref<string | null>(null);
const data = ref<{ data: Doc[]; meta?: any } | null>(null);

const status = ref<string>('');
const assigned_to = ref<string>('');
const vip = ref<string>('');
const age = ref<string>('');
const sort = ref<string>('priority');
const dir = ref<string>('desc');
const page = ref<number>(1);
const reviewers = ref<Array<{ id: number; name: string }>>([]);
const pageCtx = usePage();
const currentUserId = (pageCtx.props as any)?.auth?.user?.id as number | undefined;
const reviewNotes = ref<Record<number, string>>({});

// Computed properties
const exportUrl = computed(() => {
  const params = new URLSearchParams({
    status: status.value || '',
    assigned_to: assigned_to.value || '',
    vip: vip.value || '',
    age: age.value || ''
  });
  return `/admin/kyb-queue/export?${params.toString()}`;
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

// Drawer
const showDrawer = ref(false);
const drawerDoc = ref<any>(null);
const drawerHistory = ref<any[]>([]);
async function openDrawer(docId: number) {
  showDrawer.value = true;
  drawerDoc.value = null;
  drawerHistory.value = [];
  try {
    const res = await fetch(`/api/v1/admin/documents/${docId}`, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
    if (res.ok) {
      const j = await res.json();
      drawerDoc.value = j.document;
      drawerHistory.value = j.history;
    }
  } catch { }
}

async function load() {
  loading.value = true;
  error.value = null;
  try {
    const p = new URLSearchParams();
    if (status.value) p.set('status', status.value);
    if (assigned_to.value) p.set('assigned_to', assigned_to.value);
    if (vip.value) p.set('vip', vip.value);
    if (age.value) p.set('age', age.value);
    if (sort.value) p.set('sort', sort.value);
    if (dir.value) p.set('dir', dir.value);
    p.set('page', String(page.value));
    const res = await fetch(`/api/v1/admin/kyb-queue?${p.toString()}`, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
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

async function claim(docId: number) {
  const prev = data.value?.data?.find(d => d.id === docId)?.assigned_to;
  const target = data.value?.data?.find(d => d.id === docId);
  if (target && currentUserId) target.assigned_to = currentUserId;
  try {
    const res = await fetch(`/api/v1/admin/kyb-queue/${docId}/claim`, { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': (document.querySelector('meta[name=csrf-token]') as HTMLMetaElement)?.content }, credentials: 'same-origin' });
    if (!res.ok) throw new Error(String(res.status));
    notify('Claimed', 'success');
  } catch (e) {
    if (target) target.assigned_to = prev ?? null;
    notify('Failed to claim', 'error');
  }
}

async function reassign(docId: number, userId: string) {
  if (!userId) return;
  const prev = data.value?.data?.find(d => d.id === docId)?.assigned_to;
  const target = data.value?.data?.find(d => d.id === docId);
  if (target) target.assigned_to = Number(userId);
  try {
    const res = await fetch(`/api/v1/admin/kyb-queue/${docId}/reassign`, { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': (document.querySelector('meta[name=csrf-token]') as HTMLMetaElement)?.content }, body: JSON.stringify({ assigned_to: Number(userId) }), credentials: 'same-origin' });
    if (!res.ok) throw new Error(String(res.status));
    notify('Reassigned', 'success');
  } catch (e) {
    if (target) target.assigned_to = prev ?? null;
    notify('Failed to reassign', 'error');
  }
}

async function review(docId: number, action: 'approve' | 'reject') {
  const target = data.value?.data?.find(d => d.id === docId);
  const prev = target?.status;
  if (target) target.status = action === 'approve' ? 'approved' : 'rejected';
  try {
    const notes = reviewNotes.value[docId] || undefined;
    const res = await fetch(`/documents/${docId}/review`, { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': (document.querySelector('meta[name=csrf-token]') as HTMLMetaElement)?.content }, body: JSON.stringify({ action, review_notes: notes }), credentials: 'same-origin' });
    if (!res.ok) throw new Error(String(res.status));
    notify(action === 'approve' ? 'Approved' : 'Rejected', 'success');
  } catch (e) {
    if (target && prev) target.status = prev;
    notify('Failed to update', 'error');
  }
}

function getStatusBadge(status: string): 'warning' | 'success' | 'danger' | 'info' {
  if (status === 'pending_review') return 'warning';
  if (status === 'approved') return 'success';
  if (status === 'rejected') return 'danger';
  if (status === 'under_review') return 'info';
  return 'warning';
}
</script>

<template>

  <Head title="KYB Queue" />
  <AuthenticatedLayout>
    <div class="space-y-6">
      <!-- Page Title -->
      <div>
        <h1 class="text-2xl font-bold text-dark-text-primary">KYB Queue</h1>
      </div>

      <!-- Filters -->
      <div class="card">
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
          <div>
            <label class="block text-sm font-medium text-dark-text-secondary mb-2">Status</label>
            <select v-model="status" class="input-dark">
              <option value="">All Status</option>
              <option value="pending_review">Pending Review</option>
              <option value="under_review">Under Review</option>
              <option value="approved">Approved</option>
              <option value="rejected">Rejected</option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium text-dark-text-secondary mb-2">Assigned To</label>
            <select v-model="assigned_to" class="input-dark">
              <option value="">All Assignees</option>
              <option v-for="u in reviewers" :key="u.id" :value="String(u.id)">{{ u.name }}</option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium text-dark-text-secondary mb-2">VIP</label>
            <select v-model="vip" class="input-dark">
              <option value="">All</option>
              <option value="1">VIP</option>
              <option value="0">Non-VIP</option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium text-dark-text-secondary mb-2">Age</label>
            <DarkInput v-model="age" placeholder="e.g. 24h, 2d" />
          </div>
        </div>

        <!-- Quick Filters -->
        <div class="mt-4 flex flex-wrap gap-2">
          <button @click="status = 'pending_review'; vip = '1'; page = 1; load()"
            class="btn-secondary text-sm py-2 px-4">
            VIP Pending
          </button>
          <button @click="status = 'under_review'; assigned_to = String(currentUserId || ''); page = 1; load()"
            class="btn-secondary text-sm py-2 px-4">
            My In-Progress
          </button>
          <button
            @click="status = ''; assigned_to = ''; vip = ''; age = '24h'; sort = 'priority'; dir = 'desc'; page = 1; load()"
            class="btn-secondary text-sm py-2 px-4">
            Older than 24h
          </button>
        </div>
      </div>

      <div v-if="error" class="card bg-red-500/20 border-red-500/30">
        <p class="text-sm text-red-400">{{ error }}</p>
      </div>

      <!-- Results Table -->
      <div class="card overflow-hidden p-0">
        <div class="flex items-center justify-between border-b border-dark-border px-6 py-4">
          <div class="text-sm font-medium text-dark-text-primary">Results</div>
          <a :href="exportUrl" class="btn-secondary text-sm py-2 px-4">Export CSV</a>
        </div>

        <div class="overflow-x-auto custom-scrollbar">
          <table class="table-dark bg-none">
            <thead>
              <tr>
                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-dark-text-secondary">ID
                </th>
                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-dark-text-secondary">
                  TYPE</th>
                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-dark-text-secondary">
                  STATUS</th>
                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-dark-text-secondary">
                  VIP</th>
                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-dark-text-secondary">
                  PRIORITY</th>
                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-dark-text-secondary">
                  ASSIGNED TO</th>
                <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-dark-text-secondary">
                  ACTIONS</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="doc in data?.data || []" :key="doc.id" class="hover:bg-dark-secondary">
                <td class="px-4 py-3">
                  <button class="text-purple-accent hover:text-purple-hover underline font-medium"
                    @click="openDrawer(doc.id)">
                    {{ doc.id }}
                  </button>
                </td>
                <td class="px-4 py-3 text-dark-text-primary">{{ doc.document_type_id }}</td>
                <td class="px-4 py-3">
                  <Badge :variant="getStatusBadge(doc.status)">
                    {{ doc.status }}
                  </Badge>
                </td>
                <td class="px-4 py-3">
                  <span v-if="doc.vip" class="badge-warning">VIP</span>
                  <span v-else class="text-dark-text-muted">-</span>
                </td>
                <td class="px-4 py-3 text-dark-text-primary">{{ doc.priority ?? '-' }}</td>
                <td class="px-4 py-3 text-dark-text-primary">{{ doc.assigned_to ?? '-' }}</td>
                <td class="px-4 py-3 text-right">
                  <div class="flex items-center justify-end gap-2">
                    <button @click="claim(doc.id)" class="btn-primary text-xs py-1.5 px-3">
                      Claim
                    </button>
                    <button @click="review(doc.id, 'approve')"
                      class="bg-green-500 hover:bg-green-600 text-white text-xs font-medium py-1.5 px-3 rounded-lg transition-colors">
                      Approve
                    </button>
                    <button @click="review(doc.id, 'reject')"
                      class="bg-red-500 hover:bg-red-600 text-white text-xs font-medium py-1.5 px-3 rounded-lg transition-colors">
                      Reject
                    </button>
                    <DarkInput v-model="reviewNotes[doc.id]" placeholder="notes..."
                      class="!w-32 !py-1.5 !px-2 text-xs" />
                    <select @change="(e: any) => reassign(doc.id, e.target.value)"
                      class="input-dark !py-2 !px-3 text-sm!w-32 !py-1.5 !px-2 text-xs">
                      <option value="">Reassign →</option>
                      <option v-for="u in reviewers" :key="u.id" :value="String(u.id)">{{ u.name }}</option>
                    </select>
                  </div>
                </td>
              </tr>
              <tr v-if="!data?.data || data.data.length === 0">
                <td colspan="7" class="px-4 py-8 text-center text-dark-text-muted">
                  No documents found
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Pagination -->
      <div class="flex items-center justify-between text-sm">
        <div class="text-dark-text-secondary">
          Page {{ data?.meta?.current_page || page }} of {{ data?.meta?.last_page || '?' }}
        </div>
        <div class="flex items-center gap-2">
          <button :disabled="(data?.meta?.current_page || 1) <= 1"
            @click="page = (data?.meta?.current_page || 1) - 1; load()"
            class="btn-secondary text-sm py-2 px-4 disabled:opacity-50">
            Prev
          </button>
          <button :disabled="(data?.meta?.current_page || 1) >= (data?.meta?.last_page || 1)"
            @click="page = (data?.meta?.current_page || 1) + 1; load()"
            class="btn-secondary text-sm py-2 px-4 disabled:opacity-50">
            Next
          </button>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>

  <!-- Toasts -->
  <div class="fixed bottom-4 right-4 space-y-2 z-50">
    <div v-for="t in toasts" :key="t.id" :class="[
      'rounded-lg px-4 py-2 shadow-lg',
      t.type === 'error' ? 'bg-red-500 text-white' : 'bg-green-500 text-white'
    ]">
      {{ t.text }}
    </div>
  </div>

  <!-- Drawer -->
  <div v-if="showDrawer" class="fixed inset-0 z-50">
    <div class="absolute inset-0 bg-black/50" @click="showDrawer = false"></div>
    <div
      class="absolute right-0 top-0 h-full w-full max-w-xl overflow-auto custom-scrollbar bg-dark-card border-l border-dark-border shadow-xl">
      <div class="flex items-center justify-between border-b border-dark-border p-6">
        <h3 class="text-lg font-semibold text-dark-text-primary">Document #{{ drawerDoc?.id }}</h3>
        <button class="btn-secondary text-sm py-2 px-4" @click="showDrawer = false">Close</button>
      </div>
      <div class="p-6 space-y-6">
        <div class="grid grid-cols-2 gap-4 text-sm">
          <div>
            <span class="text-dark-text-secondary block mb-1">Type</span>
            <div class="text-dark-text-primary">{{ drawerDoc?.document_type_id }}</div>
          </div>
          <div>
            <span class="text-dark-text-secondary block mb-1">Status</span>
            <div class="text-dark-text-primary">{{ drawerDoc?.status }}</div>
          </div>
          <div>
            <span class="text-dark-text-secondary block mb-1">Owner</span>
            <div class="text-dark-text-primary">{{ drawerDoc?.owner_type }} #{{ drawerDoc?.owner_id }}</div>
          </div>
          <div>
            <span class="text-dark-text-secondary block mb-1">Created</span>
            <div class="text-dark-text-primary">{{ drawerDoc?.created_at }}</div>
          </div>
          <div>
            <span class="text-dark-text-secondary block mb-1">Assigned</span>
            <div class="text-dark-text-primary">{{ drawerDoc?.assigned_to ?? '-' }}</div>
          </div>
          <div>
            <span class="text-dark-text-secondary block mb-1">Priority</span>
            <div class="text-dark-text-primary">{{ drawerDoc?.priority ?? '-' }}</div>
          </div>
        </div>
        <div>
          <div class="mb-2 text-sm font-medium text-dark-text-primary">Preview</div>
          <div v-if="drawerDoc?.file_path"
            class="aspect-[4/3] w-full overflow-hidden rounded-lg border border-dark-border">
            <iframe :src="`/storage/${drawerDoc.file_path}`" class="h-full w-full bg-dark-secondary"></iframe>
          </div>
        </div>
        <div>
          <div class="mb-2 text-sm font-medium text-dark-text-primary">History</div>
          <ul class="space-y-2">
            <li v-for="h in drawerHistory" :key="h.id" class="card">
              <div class="flex items-center justify-between">
                <span class="font-medium text-dark-text-primary">{{ h.action }}</span>
                <span class="text-dark-text-secondary text-xs">{{ h.created_at }}</span>
              </div>
              <div class="mt-1 text-sm text-dark-text-secondary">By {{ h.actor_name || ('#' + h.actor_id) }}</div>
            </li>
          </ul>
        </div>
      </div>
    </div>
  </div>
</template>
