<script setup lang="ts">
import { Head, useForm, usePage } from '@inertiajs/vue3'

const page = usePage<any>()

const form = useForm({
  document_type_id: 1,
  file: null as File | null
})

function submit() {
  form.post(route('documents.store'), {
    forceFormData: true,
    onSuccess: () => form.reset('file')
  })
}
</script>

<template>
  <Head title="Upload Document" />
  <div class="max-w-xl mx-auto p-6">
    <h1 class="text-2xl font-semibold mb-4">Upload Document</h1>
    <p v-if="page.props.flash?.success" class="mb-3 text-green-700">{{ page.props.flash.success }}</p>
    <form @submit.prevent="submit" class="space-y-4">
      <div>
        <label class="block text-sm font-medium">Document Type ID</label>
        <input v-model.number="form.document_type_id" type="number" min="1" class="mt-1 block w-full border rounded p-2" />
        <div v-if="form.errors.document_type_id" class="text-red-600 text-sm mt-1">{{ form.errors.document_type_id }}</div>
      </div>
      <div>
        <label class="block text-sm font-medium">File</label>
        <input type="file" accept=".pdf,.jpg,.jpeg,.png" @change="(e:any)=> form.file = e.target.files?.[0] ?? null" class="mt-1 block w-full" />
        <div v-if="form.errors.file" class="text-red-600 text-sm mt-1">{{ form.errors.file }}</div>
      </div>
      <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded" :disabled="form.processing">
        Upload
      </button>
    </form>
  </div>
</template>


