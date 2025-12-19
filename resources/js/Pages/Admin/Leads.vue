<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';
type Lead = { id: number, name: string, email: string, phone?: string, company?: string, created_at: string };
const items = ref<Lead[]>([]);
const loading = ref(true);
const search = ref('');
async function load() {
  loading.value = true;
  try {
    const res = await fetch('/admin/api/leads' + (search.value ? ('?search=' + encodeURIComponent(search.value)) : ''), { credentials: 'include' });
    const js = await res.json();
    items.value = js?.data ?? [];
  } finally { loading.value = false; }
}
onMounted(load);
</script>

<template>

  <Head title="Leads" />
  <AuthenticatedLayout>
    <template #header>
      <div class="flex items-center justify-between">
        <h2 class="text-2xl font-bold text-gray-900">Leads</h2>
        <div class="flex items-center gap-3">
          <input v-model="search" @keyup.enter="load" placeholder="Search..."
            class="rounded-lg border border-gray-300 px-3 py-2 text-sm" />
          <button @click="load" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm text-white">Search</button>
          <a href="/admin/api/leads/export" class="rounded-lg border border-gray-300 px-4 py-2 text-sm">Export CSV</a>
        </div>
      </div>
    </template>
    <div class="mx-auto max-w-7xl py-8 px-4 sm:px-6 lg:px-8">
      <div class="rounded-xl border border-gray-200  p-6">
        <div v-if="loading" class="py-10 text-center text-sm text-gray-500">Loading…</div>
        <div v-else class="overflow-x-auto custom-scrollbar">
          <table class="min-w-full text-left text-sm">
            <thead>
              <tr class="text-gray-500">
                <th class="py-2">Name</th>
                <th class="py-2">Email</th>
                <th class="py-2">Phone</th>
                <th class="py-2">Company</th>
                <th class="py-2">Created</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="row in items" :key="row.id" class="border-t">
                <td class="py-2">{{ row.name }}</td>
                <td class="py-2">{{ row.email }}</td>
                <td class="py-2">{{ row.phone || '-' }}</td>
                <td class="py-2">{{ row.company || '-' }}</td>
                <td class="py-2">{{ new Date(row.created_at).toLocaleString() }}</td>
              </tr>
              <tr v-if="items.length === 0">
                <td colspan="5" class="py-6 text-center text-gray-500">No leads</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
