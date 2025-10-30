<template>
  <div class="max-w-2xl mx-auto p-6">
    <h1 class="text-xl font-semibold mb-4">Submit Invoice</h1>
    <form @submit.prevent="submit">
      <div class="space-y-4">
        <input v-model="form.supplier_id" type="number" placeholder="Supplier ID" class="input" />
        <input v-model="form.buyer_id" type="number" placeholder="Buyer ID" class="input" />
        <input v-model="form.invoice_number" type="text" placeholder="Invoice Number" class="input" />
        <input v-model.number="form.amount" type="number" step="0.01" placeholder="Amount" class="input" />
        <input v-model="form.currency" type="text" maxlength="3" placeholder="Currency" class="input" />
        <input v-model="form.due_date" type="date" placeholder="Due Date" class="input" />
        <input @change="onFile" type="file" accept=".pdf,.jpg,.jpeg,.png" class="input" />
      </div>
      <button type="submit" class="mt-6 btn">Submit</button>
    </form>
  </div>
  </template>

<script setup lang="ts">
import { useForm } from '@inertiajs/vue3'

const form = useForm({
  supplier_id: '',
  buyer_id: '',
  invoice_number: '',
  amount: '',
  currency: 'SAR',
  due_date: '',
  file: null as File | null,
})

function onFile(e: Event) {
  const t = e.target as HTMLInputElement
  form.file = t.files?.[0] ?? null
}

function submit() {
  form.post(route('invoices.store'), {
    forceFormData: true,
  })
}
</script>

<style scoped>
.input { @apply w-full border rounded px-3 py-2; }
.btn { @apply bg-blue-600 text-white rounded px-4 py-2; }
</style>


