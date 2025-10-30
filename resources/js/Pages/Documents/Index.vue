<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'

defineProps<{ documents: Array<{id:number, document_type_id:number, status:string, file_path:string, created_at:string}> }>()
</script>

<template>
  <Head title="My Documents" />
  <div class="max-w-3xl mx-auto p-6">
    <div class="flex items-center justify-between mb-4">
      <h1 class="text-2xl font-semibold">My Documents</h1>
      <Link :href="route('documents.create')" class="bg-blue-600 text-white px-3 py-2 rounded">Upload</Link>
    </div>
    <table class="w-full border text-sm">
      <thead>
        <tr class="bg-gray-100">
          <th class="p-2 border">ID</th>
          <th class="p-2 border">Type ID</th>
          <th class="p-2 border">Status</th>
          <th class="p-2 border">Created</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="d in documents" :key="d.id">
          <td class="p-2 border">{{ d.id }}</td>
          <td class="p-2 border">{{ d.document_type_id }}</td>
          <td class="p-2 border">{{ d.status }}</td>
          <td class="p-2 border">{{ new Date(d.created_at).toLocaleString() }}</td>
        </tr>
        <tr v-if="documents.length===0">
          <td colspan="4" class="p-4 text-center text-gray-500">No documents yet</td>
        </tr>
      </tbody>
    </table>
  </div>
</template>


