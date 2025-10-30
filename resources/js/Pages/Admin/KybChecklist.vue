<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';

type Row = { id:number, customer_type:string, document_type_id:number, is_required:boolean, expires_in_days?:number|null, is_active:boolean, document_type?:{ id:number, name:string } };
const rows = ref<Row[]>([]);
const loading = ref(true);
const form = ref<{ id:number, customer_type:string, document_type_id:number|null, is_required:boolean, expires_in_days:number|null, is_active:boolean }>({ id:0, customer_type:'LLC', document_type_id:null, is_required:true, expires_in_days:null, is_active:true });
const docTypes = ref<Array<{ id:number, name:string }>>([]);

async function load() {
  loading.value = true;
  try {
    const [list, types] = await Promise.all([
      fetch('/admin/api/kyb-checklist', { credentials: 'include' }),
      fetch('/api/v1/admin/document-types', { credentials: 'include' }).catch(() => null),
    ]);
    const js = await list.json();
    rows.value = js?.data ?? [];
    if (types && types.ok) {
      const tjs = await types.json();
      docTypes.value = tjs?.data ?? [];
    }
  } finally { loading.value = false; }
}
onMounted(load);

async function save() {
  const payload = { ...form.value } as any;
  if (!payload.document_type_id) return;
  const res = await fetch('/admin/api/kyb-checklist', { method: 'POST', headers: { 'Content-Type': 'application/json' }, credentials: 'include', body: JSON.stringify(payload) });
  if (res.ok) { form.value = { id:0, customer_type:'LLC', document_type_id:null, is_required:true, expires_in_days:null, is_active:true }; await load(); }
}
async function toggle(row: Row, field: 'is_required'|'is_active') {
  const body: any = {}; body[field] = !row[field];
  await fetch('/admin/api/kyb-checklist/' + row.id, { method:'PUT', headers:{'Content-Type':'application/json'}, credentials:'include', body: JSON.stringify(body) });
  await load();
}
async function remove(id:number) { await fetch('/admin/api/kyb-checklist/' + id, { method:'DELETE', credentials:'include' }); await load(); }
</script>

<template>
  <Head title="KYB Checklist" />
  <AuthenticatedLayout>
    <template #header>
      <h2 class="text-2xl font-bold text-gray-900">KYB Checklist</h2>
    </template>

    <div class="mx-auto max-w-7xl py-8 px-4 sm:px-6 lg:px-8">
      <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="rounded-xl border border-gray-200 bg-white p-6 lg:col-span-2">
          <div class="mb-4 text-base font-semibold text-gray-900">Rules</div>
          <div v-if="loading" class="py-10 text-center text-sm text-gray-500">Loading…</div>
          <div v-else class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
              <thead>
                <tr class="text-gray-500">
                  <th class="py-2">Customer Type</th>
                  <th class="py-2">Document</th>
                  <th class="py-2">Required</th>
                  <th class="py-2">Active</th>
                  <th class="py-2">Expires (days)</th>
                  <th class="py-2"></th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="r in rows" :key="r.id" class="border-t">
                  <td class="py-2">{{ r.customer_type }}</td>
                  <td class="py-2">{{ r.document_type?.name || r.document_type_id }}</td>
                  <td class="py-2"><button class="text-indigo-600 hover:underline" @click="toggle(r,'is_required')">{{ r.is_required ? 'Yes' : 'No' }}</button></td>
                  <td class="py-2"><button class="text-indigo-600 hover:underline" @click="toggle(r,'is_active')">{{ r.is_active ? 'Yes' : 'No' }}</button></td>
                  <td class="py-2">{{ r.expires_in_days ?? '-' }}</td>
                  <td class="py-2 text-right"><button class="text-red-600 hover:underline" @click="remove(r.id)">Delete</button></td>
                </tr>
                <tr v-if="rows.length===0"><td colspan="6" class="py-6 text-center text-gray-500">No rules</td></tr>
              </tbody>
            </table>
          </div>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-6">
          <div class="mb-4 text-base font-semibold text-gray-900">Add Rule</div>
          <div class="space-y-3">
            <div>
              <label class="block text-sm font-medium text-gray-700">Customer Type</label>
              <input v-model="form.customer_type" class="mt-1 w-full rounded-lg border border-gray-300 p-2" placeholder="e.g., LLC, Sole" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700">Document Type</label>
              <select v-model.number="form.document_type_id" class="mt-1 w-full rounded-lg border border-gray-300 p-2">
                <option :value="null" disabled>Select...</option>
                <option v-for="dt in docTypes" :key="dt.id" :value="dt.id">{{ dt.name }}</option>
              </select>
            </div>
            <div class="grid grid-cols-2 gap-3">
              <div class="flex items-center gap-2">
                <input id="is_required" type="checkbox" v-model="form.is_required" />
                <label for="is_required" class="text-sm text-gray-700">Required</label>
              </div>
              <div class="flex items-center gap-2">
                <input id="is_active" type="checkbox" v-model="form.is_active" />
                <label for="is_active" class="text-sm text-gray-700">Active</label>
              </div>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700">Expires in (days)</label>
              <input type="number" v-model.number="form.expires_in_days" class="mt-1 w-full rounded-lg border border-gray-300 p-2" />
            </div>
            <div class="pt-2">
              <button @click="save" class="rounded-lg bg-indigo-600 px-4 py-2 text-white hover:bg-indigo-700">Save</button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>


