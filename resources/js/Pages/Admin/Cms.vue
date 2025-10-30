<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';

type Cms = { id:number, key:string, locale:string, title?:string, body?:string, cta_text?:string, cta_href?:string, image_url?:string, is_active:boolean };
const items = ref<Cms[]>([]);
const loading = ref(true);
const form = ref<Cms>({ id: 0, key: '', locale: 'en', title: '', body: '', cta_text: '', cta_href: '', image_url: '', is_active: true });

async function load() {
  loading.value = true;
  try {
    const res = await fetch('/admin/api/cms', { credentials: 'include' });
    const js = await res.json();
    items.value = js?.data ?? [];
  } finally {
    loading.value = false;
  }
}
onMounted(load);

async function save() {
  const payload = { ...form.value } as any;
  if (!payload.id) {
    const res = await fetch('/admin/api/cms', { method: 'POST', headers: { 'Content-Type': 'application/json' }, credentials: 'include', body: JSON.stringify(payload) });
    if (res.ok) { form.value = { id: 0, key: '', locale: 'en', title: '', body: '', cta_text: '', cta_href: '', image_url: '', is_active: true }; await load(); }
  } else {
    const res = await fetch('/admin/api/cms/' + payload.id, { method: 'PUT', headers: { 'Content-Type': 'application/json' }, credentials: 'include', body: JSON.stringify(payload) });
    if (res.ok) await load();
  }
}
function edit(row: Cms) { form.value = { ...row }; }
async function remove(id: number) { await fetch('/admin/api/cms/' + id, { method: 'DELETE', credentials: 'include' }); await load(); }
</script>

<template>
  <Head title="CMS" />
  <AuthenticatedLayout>
    <template #header>
      <div class="flex items-center justify-between">
        <h2 class="text-2xl font-bold text-gray-900">CMS Blocks</h2>
      </div>
    </template>

    <div class="mx-auto max-w-7xl py-8 px-4 sm:px-6 lg:px-8">
      <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="rounded-xl border border-gray-200 bg-white p-6 lg:col-span-2">
          <div class="mb-4 text-base font-semibold text-gray-900">Blocks</div>
          <div v-if="loading" class="py-10 text-center text-sm text-gray-500">Loading…</div>
          <div v-else class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
              <thead>
                <tr class="text-gray-500">
                  <th class="py-2">Key</th>
                  <th class="py-2">Locale</th>
                  <th class="py-2">Title</th>
                  <th class="py-2">Active</th>
                  <th class="py-2"></th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="row in items" :key="row.id" class="border-t">
                  <td class="py-2">{{ row.key }}</td>
                  <td class="py-2">{{ row.locale }}</td>
                  <td class="py-2">{{ row.title }}</td>
                  <td class="py-2">{{ row.is_active ? 'Yes' : 'No' }}</td>
                  <td class="py-2 text-right">
                    <button class="text-indigo-600 hover:underline mr-3" @click="edit(row)">Edit</button>
                    <button class="text-red-600 hover:underline" @click="remove(row.id)">Delete</button>
                  </td>
                </tr>
                <tr v-if="items.length===0"><td colspan="5" class="py-6 text-center text-gray-500">No blocks</td></tr>
              </tbody>
            </table>
          </div>
        </div>

        <div class="rounded-xl border border-gray-200 bg-white p-6">
          <div class="mb-4 text-base font-semibold text-gray-900">{{ form.id ? 'Edit' : 'Create' }} Block</div>
          <div class="space-y-3">
            <div>
              <label class="block text-sm font-medium text-gray-700">Key</label>
              <input v-model="form.key" class="mt-1 w-full rounded-lg border border-gray-300 p-2" :disabled="!!form.id" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700">Locale</label>
              <input v-model="form.locale" class="mt-1 w-full rounded-lg border border-gray-300 p-2" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700">Title</label>
              <input v-model="form.title" class="mt-1 w-full rounded-lg border border-gray-300 p-2" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700">Body</label>
              <textarea v-model="form.body" rows="6" class="mt-1 w-full rounded-lg border border-gray-300 p-2" />
            </div>
            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="block text-sm font-medium text-gray-700">CTA Text</label>
                <input v-model="form.cta_text" class="mt-1 w-full rounded-lg border border-gray-300 p-2" />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700">CTA Href</label>
                <input v-model="form.cta_href" class="mt-1 w-full rounded-lg border border-gray-300 p-2" />
              </div>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700">Image URL</label>
              <input v-model="form.image_url" class="mt-1 w-full rounded-lg border border-gray-300 p-2" />
            </div>
            <div class="flex items-center gap-2">
              <input id="is_active" type="checkbox" v-model="form.is_active" />
              <label for="is_active" class="text-sm text-gray-700">Active</label>
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


