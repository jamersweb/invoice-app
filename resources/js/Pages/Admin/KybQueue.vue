<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { ref, watchEffect, onMounted, computed } from 'vue';

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
  } catch {}
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
  // optimistic
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
  // optimistic
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
  // optimistic
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
</script>

<template>
  <Head title="KYB Queue" />
  <AuthenticatedLayout>
    <template #header>
      <h2 class="text-xl font-semibold leading-tight text-gray-800">KYB Queue</h2>
    </template>

    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
      <div class="mb-4 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-6">
        <input v-model="status" class="rounded border p-2" placeholder="status" />
        <select v-model="assigned_to" class="rounded border p-2">
          <option value="">Assigned to</option>
          <option v-for="u in reviewers" :key="u.id" :value="String(u.id)">{{ u.name }}</option>
        </select>
        <select v-model="vip" class="rounded border p-2">
          <option value="">VIP?</option>
          <option value="1">Yes</option>
          <option value="0">No</option>
        </select>
        <input v-model="age" class="rounded border p-2" placeholder="age (e.g. 24h, 2d)" />
        <select v-model="sort" class="rounded border p-2">
          <option value="priority">priority</option>
          <option value="created_at">created_at</option>
        </select>
        <select v-model="dir" class="rounded border p-2">
          <option value="desc">desc</option>
          <option value="asc">asc</option>
        </select>
        <div class="col-span-full flex flex-wrap gap-2">
          <button @click="status='pending_review'; vip='1'; page=1" class="rounded border px-3 py-1 text-xs">VIP Pending</button>
          <button @click="status='under_review'; assigned_to=String(currentUserId||''); page=1" class="rounded border px-3 py-1 text-xs">My In-Progress</button>
          <button @click="status=''; assigned_to=''; vip=''; age='24h'; sort='priority'; dir='desc'; page=1" class="rounded border px-3 py-1 text-xs">Older than 24h</button>
        </div>
      </div>

      <div v-if="error" class="mb-4 rounded border border-red-200 bg-red-50 p-3 text-sm text-red-700">{{ error }}</div>

      <div class="overflow-hidden rounded-xl border border-gray-200 bg-white">
        <div class="flex items-center justify-between border-b p-3 text-sm">
          <div class="text-gray-600">Results</div>
          <a :href="exportUrl" class="rounded border px-3 py-1">Export CSV</a>
        </div>
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">ID</th>
              <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Type</th>
              <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Status</th>
              <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">VIP</th>
              <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Priority</th>
              <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Assigned to</th>
              <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr v-for="doc in data?.data || []" :key="doc.id">
              <td class="px-4 py-3">
                <button class="text-indigo-600 underline" @click="openDrawer(doc.id)">{{ doc.id }}</button>
              </td>
              <td class="px-4 py-3">{{ doc.document_type_id }}</td>
              <td class="px-4 py-3">
                <span :class="{
                  'rounded px-2 py-1 text-xs': true,
                  'bg-gray-100 text-gray-700': doc.status === 'pending',
                  'bg-amber-100 text-amber-800': doc.status === 'pending_review',
                  'bg-indigo-100 text-indigo-800': doc.status === 'under_review',
                  'bg-emerald-100 text-emerald-800': doc.status === 'approved',
                  'bg-rose-100 text-rose-800': doc.status === 'rejected',
                }">{{ doc.status }}</span>
              </td>
              <td class="px-4 py-3">
                <span v-if="doc.vip" class="rounded bg-amber-100 px-2 py-1 text-xs text-amber-800">VIP</span>
              </td>
              <td class="px-4 py-3">{{ doc.priority ?? '-' }}</td>
              <td class="px-4 py-3">{{ doc.assigned_to ?? '-' }}</td>
              <td class="px-4 py-3 text-right">
                <button @click="claim(doc.id)" class="rounded bg-indigo-600 px-3 py-1 text-xs font-medium text-white">Claim</button>
                <button @click="review(doc.id, 'approve')" class="ml-2 rounded bg-emerald-600 px-3 py-1 text-xs font-medium text-white">Approve</button>
                <button @click="review(doc.id, 'reject')" class="ml-2 rounded bg-rose-600 px-3 py-1 text-xs font-medium text-white">Reject</button>
                <input v-model="reviewNotes[doc.id]" class="ml-2 w-40 rounded border p-1 text-xs" placeholder="notes..." />
                <select @change="(e:any)=>reassign(doc.id, e.target.value)" class="ml-2 rounded border p-1 text-xs">
                  <option value="">Reassign →</option>
                  <option value="1">User #1</option>
                  <option value="2">User #2</option>
                </select>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div class="mt-4 flex items-center justify-between text-sm text-gray-600">
        <div>Page {{ data?.meta?.current_page || page }} of {{ data?.meta?.last_page || '?' }}</div>
        <div class="space-x-2">
          <button :disabled="(data?.meta?.current_page || 1) <= 1" @click="page = (data?.meta?.current_page || 1) - 1" class="rounded border px-3 py-1 disabled:opacity-50">Prev</button>
          <button :disabled="(data?.meta?.current_page || 1) >= (data?.meta?.last_page || 1)" @click="page = (data?.meta?.current_page || 1) + 1" class="rounded border px-3 py-1 disabled:opacity-50">Next</button>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
  <!-- Toasts -->
  <div class="fixed bottom-4 right-4 space-y-2">
    <div v-for="t in toasts" :key="t.id" :class="['rounded px-4 py-2 shadow', t.type==='error' ? 'bg-rose-600 text-white' : 'bg-emerald-600 text-white']">
      {{ t.text }}
    </div>
  </div>
  <!-- Drawer -->
  <div v-if="showDrawer" class="fixed inset-0 z-50">
    <div class="absolute inset-0 bg-black/30" @click="showDrawer=false"></div>
    <div class="absolute right-0 top-0 h-full w-full max-w-xl overflow-auto bg-white shadow-xl">
      <div class="flex items-center justify-between border-b p-4">
        <h3 class="text-lg font-medium">Document #{{ drawerDoc?.id }}</h3>
        <button class="rounded border px-3 py-1 text-sm" @click="showDrawer=false">Close</button>
      </div>
      <div class="p-4 space-y-4">
        <div class="grid grid-cols-2 gap-3 text-sm">
          <div><span class="text-gray-500">Type</span><div>{{ drawerDoc?.document_type_id }}</div></div>
          <div><span class="text-gray-500">Status</span><div>{{ drawerDoc?.status }}</div></div>
          <div><span class="text-gray-500">Owner</span><div>{{ drawerDoc?.owner_type }} #{{ drawerDoc?.owner_id }}</div></div>
          <div><span class="text-gray-500">Created</span><div>{{ drawerDoc?.created_at }}</div></div>
          <div><span class="text-gray-500">Assigned</span><div>{{ drawerDoc?.assigned_to ?? '-' }}</div></div>
          <div><span class="text-gray-500">Priority</span><div>{{ drawerDoc?.priority ?? '-' }}</div></div>
        </div>
        <div>
          <div class="mb-2 text-sm font-medium">Preview</div>
          <div v-if="drawerDoc?.file_path" class="aspect-[4/3] w-full overflow-hidden rounded border">
            <iframe :src="`/storage/${drawerDoc.file_path}`" class="h-full w-full"></iframe>
          </div>
        </div>
        <div>
          <div class="mb-2 text-sm font-medium">History</div>
          <ul class="space-y-2 text-sm">
            <li v-for="h in drawerHistory" :key="h.id" class="rounded border p-2">
              <div class="flex items-center justify-between">
                <span class="font-medium">{{ h.action }}</span>
                <span class="text-gray-500">{{ h.created_at }}</span>
              </div>
              <div class="mt-1 text-gray-600">By {{ h.actor_name || ('#'+h.actor_id) }}</div>
            </li>
          </ul>
        </div>
      </div>
    </div>
  </div>
</template>


