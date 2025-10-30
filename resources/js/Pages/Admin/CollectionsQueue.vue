<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, usePage } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';

type Row = { id:number; invoice_number:string; amount:number; supplier_id:number; buyer_id:number; due_date:string; assigned_to:number|null; priority:number|null };

const loading = ref(false);
const error = ref<string|null>(null);
const rows = ref<Row[]>([]);
const meta = ref<any>(null);

const assigned_to = ref('');
const min_amount = ref('');
const age = ref('');
const page = ref(1);
const reviewers = ref<Array<{ id:number; name:string }>>([]);

async function load() {
  loading.value = true;
  error.value = null;
  try {
    const p = new URLSearchParams();
    if (assigned_to.value) p.set('assigned_to', assigned_to.value);
    if (min_amount.value) p.set('min_amount', min_amount.value);
    if (age.value) p.set('age', age.value);
    p.set('page', String(page.value));
    const res = await fetch(`/api/v1/admin/collections?${p.toString()}`, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
    if (!res.ok) throw new Error(`HTTP ${res.status}`);
    const j = await res.json();
    rows.value = j.data; meta.value = j.meta;
  } catch (e:any) { error.value = e?.message || 'Failed'; } finally { loading.value = false; }
}

async function fetchReviewers() {
  const res = await fetch('/api/v1/admin/reviewers', { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
  if (res.ok) reviewers.value = await res.json();
}

onMounted(() => { fetchReviewers(); load(); });

async function claim(id:number) {
  await fetch(`/api/v1/admin/collections/${id}/claim`, { method:'POST', headers: { 'X-CSRF-TOKEN': (document.querySelector('meta[name=csrf-token]') as HTMLMetaElement)?.content } });
  await load();
}
async function reassign(id:number, uid:string) {
  if (!uid) return;
  await fetch(`/api/v1/admin/collections/${id}/reassign`, { method:'POST', headers: { 'Content-Type':'application/json', 'X-CSRF-TOKEN': (document.querySelector('meta[name=csrf-token]') as HTMLMetaElement)?.content }, body: JSON.stringify({ assigned_to: Number(uid) }) });
  await load();
}
async function remind(id:number) {
  await fetch(`/api/v1/admin/collections/${id}/remind`, { method:'POST', headers: { 'X-CSRF-TOKEN': (document.querySelector('meta[name=csrf-token]') as HTMLMetaElement)?.content } });
}
</script>

<template>
  <Head title="Collections" />
  <AuthenticatedLayout>
    <template #header>
      <h2 class="text-xl font-semibold leading-tight text-gray-800">Overdue Collections</h2>
    </template>
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
      <div class="mb-4 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-6">
        <select v-model="assigned_to" class="rounded border p-2">
          <option value="">Assigned to</option>
          <option v-for="u in reviewers" :key="u.id" :value="String(u.id)">{{ u.name }}</option>
        </select>
        <input v-model="min_amount" class="rounded border p-2" placeholder="min amount" />
        <input v-model="age" class="rounded border p-2" placeholder="age (e.g. 7d)" />
        <button @click="load" class="rounded border px-3 py-2">Apply</button>
      </div>
      <div class="overflow-hidden rounded-xl border border-gray-200 bg-white">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Invoice</th>
              <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Amount</th>
              <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Due</th>
              <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Assigned</th>
              <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr v-for="r in rows" :key="r.id">
              <td class="px-4 py-3">{{ r.invoice_number }}</td>
              <td class="px-4 py-3">{{ new Intl.NumberFormat(undefined, { style:'currency', currency:'USD'}).format(r.amount) }}</td>
              <td class="px-4 py-3">{{ r.due_date }}</td>
              <td class="px-4 py-3">{{ r.assigned_to ?? '-' }}</td>
              <td class="px-4 py-3 text-right">
                <button @click="claim(r.id)" class="rounded bg-indigo-600 px-3 py-1 text-xs font-medium text-white">Claim</button>
                <select @change="(e:any)=>reassign(r.id, e.target.value)" class="ml-2 rounded border p-1 text-xs">
                  <option value="">Reassign →</option>
                  <option v-for="u in reviewers" :key="u.id" :value="String(u.id)">{{ u.name }}</option>
                </select>
                <button @click="remind(r.id)" class="ml-2 rounded border px-3 py-1 text-xs">Remind</button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <div class="mt-4 flex items-center justify-between text-sm text-gray-600">
        <div>Page {{ meta?.current_page || page }} of {{ meta?.last_page || '?' }}</div>
        <div class="space-x-2">
          <button :disabled="(meta?.current_page || 1) <= 1" @click="page = (meta?.current_page || 1) - 1; load();" class="rounded border px-3 py-1 disabled:opacity-50">Prev</button>
          <button :disabled="(meta?.current_page || 1) >= (meta?.last_page || 1)" @click="page = (meta?.current_page || 1) + 1; load();" class="rounded border px-3 py-1 disabled:opacity-50">Next</button>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>


