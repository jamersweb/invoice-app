<script setup lang="ts">
import { Head, useForm, usePage } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'

const page = usePage<any>()
const props = defineProps<{ account: any | null }>()
const form = useForm({
  account_name: '',
  iban: '',
  swift: '',
  bank_name: '',
  branch: '',
  bank_letter: null as File | null
})

function submit() {
  form.post(route('bank.store'), { forceFormData: true })
}
</script>

<template>
  <Head title="Banking Details" />
  <AuthenticatedLayout>
    <div class="max-w-4xl mx-auto p-6 space-y-6">
      <div>
        <h1 class="text-3xl font-bold text-gray-900">Banking Details</h1>
        <p class="mt-2 text-sm text-gray-600">Manage your beneficiary bank account information for fund disbursements.</p>
      </div>

      <div v-if="page.props.flash?.success" class="rounded-lg bg-green-50 p-4 text-green-700">
        {{ page.props.flash.success }}
      </div>
      <div v-if="page.props.errors?.error" class="rounded-lg bg-red-50 p-4 text-red-700">
        {{ page.props.errors.error }}
      </div>

      <div v-if="props.account" class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Current Details</h2>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
          <div>
            <label class="block text-xs font-medium text-gray-500 uppercase">Account Name</label>
            <p class="mt-1 text-sm text-gray-900">{{ props.account.account_name }}</p>
          </div>
          <div>
            <label class="block text-xs font-medium text-gray-500 uppercase">IBAN</label>
            <p class="mt-1 text-sm text-gray-900 font-mono">{{ props.account.iban }}</p>
          </div>
          <div>
            <label class="block text-xs font-medium text-gray-500 uppercase">SWIFT Code</label>
            <p class="mt-1 text-sm text-gray-900 font-mono">{{ props.account.swift || '—' }}</p>
          </div>
          <div v-if="props.account.bank_name">
            <label class="block text-xs font-medium text-gray-500 uppercase">Bank Name</label>
            <p class="mt-1 text-sm text-gray-900">{{ props.account.bank_name }}</p>
          </div>
          <div v-if="props.account.has_bank_letter">
            <label class="block text-xs font-medium text-gray-500 uppercase">Bank Letter</label>
            <p class="mt-1 text-sm text-green-600">✓ Uploaded</p>
          </div>
        </div>
        <p class="mt-4 text-xs text-gray-500">Your sensitive information is masked for security. Only authorized administrators can view full details.</p>
      </div>

      <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">{{ props.account ? 'Update' : 'Add' }} Banking Details</h2>
        <form @submit.prevent="submit" class="space-y-6">
          <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
            <div>
              <label class="block text-sm font-medium text-gray-700">Account Name <span class="text-red-500">*</span></label>
              <input v-model="form.account_name" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" placeholder="Enter account holder name" required />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700">IBAN <span class="text-red-500">*</span></label>
              <input v-model="form.iban" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm font-mono" placeholder="SA03 8000 0000 6080 1016 7519" required />
              <p class="mt-1 text-xs text-gray-500">International Bank Account Number</p>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700">SWIFT Code</label>
              <input v-model="form.swift" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm font-mono" placeholder="ALBKSAJE" maxlength="11" />
              <p class="mt-1 text-xs text-gray-500">Bank Identifier Code (BIC)</p>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700">Bank Name</label>
              <input v-model="form.bank_name" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" placeholder="e.g., Al Rajhi Bank" />
            </div>
            <div class="sm:col-span-2">
              <label class="block text-sm font-medium text-gray-700">Branch</label>
              <input v-model="form.branch" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" placeholder="Branch name or code" />
            </div>
            <div class="sm:col-span-2">
              <label class="block text-sm font-medium text-gray-700">Bank Letter (PDF/JPG/PNG)</label>
              <input type="file" accept=".pdf,.jpg,.jpeg,.png" @change="(e:any)=> form.bank_letter = e.target.files?.[0] ?? null" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100" />
              <p class="mt-1 text-xs text-gray-500">Upload official bank statement or letter confirming account ownership (max 5MB)</p>
            </div>
          </div>
          <div class="flex items-center justify-end space-x-3">
            <button type="button" @click="$inertia.visit(route('dashboard'))" class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50">Cancel</button>
            <button type="submit" :disabled="form.processing" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-50">
              {{ form.processing ? 'Saving...' : 'Save Details' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </AuthenticatedLayout>
</template>


