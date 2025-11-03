<template>
  <AuthenticatedLayout>
    <Head title="Admin - Banking Details" />
    <div class="space-y-6">
      <div>
        <h1 class="text-3xl font-bold text-gray-900">Banking Details</h1>
        <p class="mt-2 text-sm text-gray-600">View and manage all supplier banking information. Full details visible.</p>
      </div>

      <div v-if="$page.props.flash?.success" class="rounded-lg bg-green-50 p-4 text-green-700">
        {{ $page.props.flash.success }}
      </div>

      <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Supplier</th>
              <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Account Name</th>
              <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">IBAN</th>
              <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">SWIFT</th>
              <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Bank</th>
              <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Branch</th>
              <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200 bg-white">
            <tr v-for="account in accounts" :key="account.id">
              <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900">{{ account.supplier_name }}</td>
              <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900">{{ account.account_name }}</td>
              <td class="whitespace-nowrap px-6 py-4 text-sm font-mono text-gray-900">{{ account.iban }}</td>
              <td class="whitespace-nowrap px-6 py-4 text-sm font-mono text-gray-900">{{ account.swift || '—' }}</td>
              <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900">{{ account.bank_name || '—' }}</td>
              <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900">{{ account.branch || '—' }}</td>
              <td class="whitespace-nowrap px-6 py-4 text-sm">
                <button @click="openEditModal(account)" class="text-indigo-600 hover:text-indigo-900">Edit</button>
              </td>
            </tr>
          </tbody>
        </table>
        <div v-if="accounts.length === 0" class="px-6 py-12 text-center text-gray-500">No banking details found.</div>
      </div>
    </div>

    <!-- Edit Modal -->
    <div v-if="editingAccount" class="fixed inset-0 z-50 overflow-y-auto" @click.self="closeModal">
      <div class="flex min-h-screen items-center justify-center p-4">
        <div class="w-full max-w-lg rounded-lg bg-white shadow-xl">
          <div class="border-b border-gray-200 px-6 py-4">
            <h3 class="text-lg font-semibold text-gray-900">Edit Banking Details</h3>
            <p class="mt-1 text-sm text-gray-500">Correction note required for audit trail.</p>
          </div>
          <form @submit.prevent="submitEdit" class="px-6 py-4 space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700">Account Name *</label>
              <input v-model="editForm.account_name" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700">IBAN *</label>
              <input v-model="editForm.iban" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm font-mono" required />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700">SWIFT</label>
              <input v-model="editForm.swift" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm font-mono" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700">Bank Name</label>
              <input v-model="editForm.bank_name" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700">Branch</label>
              <input v-model="editForm.branch" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700">Correction Note *</label>
              <textarea v-model="editForm.correction_note" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" placeholder="Explain why this change is needed..." required></textarea>
            </div>
            <div class="flex justify-end space-x-3 pt-4">
              <button type="button" @click="closeModal" class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Cancel</button>
              <button type="submit" :disabled="editForm.processing" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50">
                {{ editForm.processing ? 'Saving...' : 'Save Changes' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>

<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { ref } from 'vue'

const props = defineProps<{ accounts: any[] }>()

const editingAccount = ref<any>(null)
const editForm = useForm({
  account_name: '',
  iban: '',
  swift: '',
  bank_name: '',
  branch: '',
  correction_note: '',
})

function openEditModal(account: any) {
  editingAccount.value = account
  editForm.account_name = account.account_name
  editForm.iban = account.iban
  editForm.swift = account.swift || ''
  editForm.bank_name = account.bank_name || ''
  editForm.branch = account.branch || ''
  editForm.correction_note = ''
}

function closeModal() {
  editingAccount.value = null
  editForm.reset()
}

function submitEdit() {
  if (!editingAccount.value) return
  editForm.put(route('admin.api.bank.update', editingAccount.value.id), {
    onSuccess: () => {
      closeModal()
    },
  })
}
</script>









