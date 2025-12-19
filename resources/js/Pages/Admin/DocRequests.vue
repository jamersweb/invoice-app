<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';

type Row = { id: number, supplier_id: number, document_type_id: number, note?: string, requested_at?: string, resolved_at?: string };
const items = ref<Row[]>([]);
const loading = ref(true);
const supplierId = ref<number | null>(null);
const form = ref<{ supplier_id: number | null, document_type_id: number | null, note: string }>({ supplier_id: null, document_type_id: null, note: '' });
const docTypes = ref<Array<{ id: number, name: string }>>([]);

async function load() {
  loading.value = true;
  try {
    const [list, types] = await Promise.all([
      fetch('/admin/api/doc-requests' + (supplierId.value ? ('?supplier_id=' + supplierId.value) : ''), { credentials: 'include' }),
      fetch('/api/v1/admin/document-types', { credentials: 'include' }).catch(() => null),
    ]);
    const js = await list.json();
    items.value = js?.data ?? [];
    if (types && types.ok) { const tjs = await types.json(); docTypes.value = tjs?.data ?? []; }
  } finally { loading.value = false; }
}
onMounted(load);

async function save() {
  if (!form.value.supplier_id || !form.value.document_type_id) return;
  const res = await fetch('/admin/api/doc-requests', { method: 'POST', headers: { 'Content-Type': 'application/json' }, credentials: 'include', body: JSON.stringify(form.value) });
  if (res.ok) { form.value = { supplier_id: null, document_type_id: null, note: '' }; await load(); }
}
async function resolveReq(id: number) { await fetch('/admin/api/doc-requests/' + id + '/resolve', { method: 'POST', credentials: 'include' }); await load(); }
</script>

<template>

  <Head title="Document Requests" />
  <AuthenticatedLayout>
    <template #header>
      <h2 class="text-2xl font-bold text-gray-900">Document Requests</h2>
    </template>
    <div class="mx-auto max-w-7xl py-8 px-4 sm:px-6 lg:px-8">
      <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="rounded-xl border border-gray-200  p-6 lg:col-span-2">
          <div class="mb-4 flex items-center justify-between">
            <div class="text-base font-semibold text-gray-900">Requests</div>
            <div class="flex items-center gap-2">
              <input type="number" v-model.number="supplierId" placeholder="Supplier ID"
                class="rounded-lg border border-gray-300 px-3 py-2 text-sm" />
              <button @click="load" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm text-white">Filter</button>
            </div>
          </div>
          <div v-if="loading" class="py-10 text-center text-sm text-gray-500">Loading…</div>
          <div v-else class="overflow-x-auto custom-scrollbar">
            <table class="min-w-full text-left text-sm">
              <thead>
                <tr class="text-gray-500">
                  <th class="py-2">Supplier</th>
                  <th class="py-2">Document Type</th>
                  <th class="py-2">Note</th>
                  <th class="py-2">Requested</th>
                  <th class="py-2">Resolved</th>
                  <th class="py-2"></th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="r in items" :key="r.id" class="border-t">
                  <td class="py-2">{{ r.supplier_id }}</td>
                  <td class="py-2">{{ r.document_type_id }}</td>
                  <td class="py-2">{{ r.note || '-' }}</td>
                  <td class="py-2">{{ r.requested_at || '-' }}</td>
                  <td class="py-2">{{ r.resolved_at || '-' }}</td>
                  <td class="py-2 text-right">
                    <button v-if="!r.resolved_at" @click="resolveReq(r.id)" class="text-indigo-600 hover:underline">Mark
                      resolved</button>
                  </td>
                </tr>
                <tr v-if="items.length === 0">
                  <td colspan="6" class="py-6 text-center text-gray-500">No requests</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
        <div class="rounded-xl border border-gray-200  p-6">
          <div class="mb-4 text-base font-semibold text-gray-900">New Request</div>
          <div class="space-y-3">
            <div>
              <label class="block text-sm font-medium text-gray-700">Supplier ID</label>
              <input type="number" v-model.number="form.supplier_id"
                class="mt-1 w-full rounded-lg border border-gray-300 p-2" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700">Document Type</label>
              <select v-model.number="form.document_type_id" class="mt-1 w-full rounded-lg border border-gray-300 p-2">
                <option :value="null" disabled>Select...</option>
                <option v-for="dt in docTypes" :key="dt.id" :value="dt.id">{{ dt.name }}</option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700">Note</label>
              <input v-model="form.note" class="mt-1 w-full rounded-lg border border-gray-300 p-2" />
            </div>
            <div class="pt-2">
              <button @click="save"
                class="rounded-lg bg-indigo-600 px-4 py-2 text-white hover:bg-indigo-700">Create</button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
