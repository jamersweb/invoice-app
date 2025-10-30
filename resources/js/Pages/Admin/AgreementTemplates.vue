<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';

type Tpl = { id:number, name:string, version:string, effective_from?:string|null, effective_to?:string|null, content:string };
const items = ref<Tpl[]>([]);
const loading = ref(true);
const form = ref<Omit<Tpl,'id'>>({ name:'', version:'v1', effective_from:'', effective_to:'', content:'' });

async function load() {
  loading.value = true;
  try { const res = await fetch('/admin/api/agreements/templates', { credentials:'include' }); const js = await res.json(); items.value = js?.data ?? []; } finally { loading.value = false; }
}
onMounted(load);

async function save() {
  const res = await fetch('/admin/api/agreements/templates', { method:'POST', headers:{'Content-Type':'application/json'}, credentials:'include', body: JSON.stringify(form.value) });
  if (res.ok) { form.value = { name:'', version:'v1', effective_from:'', effective_to:'', content:'' }; await load(); }
}
async function remove(id:number) { await fetch('/admin/api/agreements/templates/'+id, { method:'DELETE', credentials:'include' }); await load(); }
</script>

<template>
  <Head title="Agreement Templates" />
  <AuthenticatedLayout>
    <template #header>
      <h2 class="text-2xl font-bold text-gray-900">Agreement Templates</h2>
    </template>
    <div class="mx-auto max-w-7xl py-8 px-4 sm:px-6 lg:px-8">
      <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="rounded-xl border border-gray-200 bg-white p-6 lg:col-span-2">
          <div class="mb-4 text-base font-semibold text-gray-900">Templates</div>
          <div v-if="loading" class="py-10 text-center text-sm text-gray-500">Loading…</div>
          <div v-else class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
              <thead>
                <tr class="text-gray-500">
                  <th class="py-2">Name</th>
                  <th class="py-2">Version</th>
                  <th class="py-2">Effective</th>
                  <th class="py-2"></th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="t in items" :key="t.id" class="border-t">
                  <td class="py-2">{{ t.name }}</td>
                  <td class="py-2">{{ t.version }}</td>
                  <td class="py-2">{{ (t.effective_from || '-') }} → {{ (t.effective_to || '-') }}</td>
                  <td class="py-2 text-right">
                    <button class="text-red-600 hover:underline" @click="remove(t.id)">Delete</button>
                  </td>
                </tr>
                <tr v-if="items.length===0"><td colspan="4" class="py-6 text-center text-gray-500">No templates</td></tr>
              </tbody>
            </table>
          </div>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-6">
          <div class="mb-4 text-base font-semibold text-gray-900">New Template</div>
          <div class="space-y-3">
            <div>
              <label class="block text-sm font-medium text-gray-700">Name</label>
              <input v-model="form.name" class="mt-1 w-full rounded-lg border border-gray-300 p-2" />
            </div>
            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="block text-sm font-medium text-gray-700">Version</label>
                <input v-model="form.version" class="mt-1 w-full rounded-lg border border-gray-300 p-2" />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700">Effective From</label>
                <input type="date" v-model="form.effective_from" class="mt-1 w-full rounded-lg border border-gray-300 p-2" />
              </div>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700">Effective To</label>
              <input type="date" v-model="form.effective_to" class="mt-1 w-full rounded-lg border border-gray-300 p-2" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700">Content</label>
              <textarea v-model="form.content" rows="8" class="mt-1 w-full rounded-lg border border-gray-300 p-2"></textarea>
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


