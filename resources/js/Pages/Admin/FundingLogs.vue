<template>
  <AuthenticatedLayout>
    <Head title="Admin - Funding Logs" />
    <div class="space-y-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold text-gray-900">Funding Logs</h1>
          <p class="mt-2 text-sm text-gray-600">Record and track all bank transfers to suppliers. Append-only log.</p>
        </div>
        <div class="flex space-x-3">
          <button @click="exportCsv" class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
            Export CSV
          </button>
          <button @click="showCreateModal = true" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">
            New Record
          </button>
        </div>
      </div>

      <div v-if="$page.props.flash?.success" class="rounded-lg bg-green-50 p-4 text-green-700">
        {{ $page.props.flash.success }}
      </div>

      <!-- Filters -->
      <div class="rounded-lg border border-gray-200 bg-white p-4">
        <form @submit.prevent="applyFilters" class="grid grid-cols-1 gap-4 sm:grid-cols-4">
          <div>
            <label class="block text-xs font-medium text-gray-700">Date From</label>
            <input v-model="filters.date_from" type="date" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm" />
          </div>
          <div>
            <label class="block text-xs font-medium text-gray-700">Date To</label>
            <input v-model="filters.date_to" type="date" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm" />
          </div>
          <div>
            <label class="block text-xs font-medium text-gray-700">Supplier</label>
            <select v-model="filters.supplier_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm">
              <option value="">All Suppliers</option>
              <option v-for="s in suppliers" :key="s.id" :value="s.id">{{ s.name }}</option>
            </select>
          </div>
          <div class="flex items-end">
            <button type="submit" class="w-full rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">Filter</button>
          </div>
        </form>
      </div>

      <!-- Table -->
      <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Date</th>
              <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Supplier</th>
              <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Amount</th>
              <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Bank Ref</th>
              <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Internal Ref</th>
              <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Recorded By</th>
              <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Notes</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200 bg-white">
            <tr v-for="log in logs.data" :key="log.id">
              <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900">{{ log.transfer_date }}</td>
              <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900">{{ log.supplier_name }}</td>
              <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-medium text-gray-900">{{ log.amount }} {{ log.currency }}</td>
              <td class="whitespace-nowrap px-6 py-4 text-sm font-mono text-gray-500">{{ log.bank_reference || '—' }}</td>
              <td class="whitespace-nowrap px-6 py-4 text-sm font-mono text-gray-500">{{ log.internal_reference || '—' }}</td>
              <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">{{ log.recorded_by }}</td>
              <td class="px-6 py-4 text-sm text-gray-500">{{ log.notes || '—' }}</td>
            </tr>
          </tbody>
        </table>
        <div v-if="logs.data.length === 0" class="px-6 py-12 text-center text-gray-500">No funding logs found.</div>
        <div v-if="logs.links && logs.links.length > 3" class="border-t border-gray-200 bg-white px-4 py-3 sm:px-6">
          <div class="flex items-center justify-between">
            <div class="flex-1 flex justify-between sm:hidden">
              <a v-if="logs.prev_page_url" :href="logs.prev_page_url" class="relative inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Previous</a>
              <a v-if="logs.next_page_url" :href="logs.next_page_url" class="relative ml-3 inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Next</a>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Create Modal -->
    <div v-if="showCreateModal" class="fixed inset-0 z-50 overflow-y-auto" @click.self="showCreateModal = false">
      <div class="flex min-h-screen items-center justify-center p-4">
        <div class="w-full max-w-lg rounded-lg bg-white shadow-xl">
          <div class="border-b border-gray-200 px-6 py-4">
            <h3 class="text-lg font-semibold text-gray-900">Record Funding Transfer</h3>
          </div>
          <form @submit.prevent="submitCreate" class="px-6 py-4 space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700">Supplier *</label>
              <select v-model="createForm.supplier_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                <option value="">Select supplier</option>
                <option v-for="s in suppliers" :key="s.id" :value="s.id">{{ s.name }}</option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700">Transfer Date *</label>
              <input v-model="createForm.transfer_date" type="date" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required />
            </div>
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700">Amount *</label>
                <input v-model.number="createForm.amount" type="number" step="0.01" min="0.01" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700">Currency *</label>
                <input v-model="createForm.currency" type="text" maxlength="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required />
              </div>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700">Bank Reference</label>
              <input v-model="createForm.bank_reference" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700">Internal Reference</label>
              <input v-model="createForm.internal_reference" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700">Notes</label>
              <textarea v-model="createForm.notes" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"></textarea>
            </div>
            <div class="flex justify-end space-x-3 pt-4">
              <button type="button" @click="showCreateModal = false" class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Cancel</button>
              <button type="submit" :disabled="createForm.processing" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50">
                {{ createForm.processing ? 'Recording...' : 'Record Transfer' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>

<script setup lang="ts">
import { Head, useForm, router } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { ref } from 'vue'

const props = defineProps<{
  logs: any
  suppliers: Array<{ id: number; name: string }>
  filters: { date_from?: string; date_to?: string; supplier_id?: string }
}>()

const showCreateModal = ref(false)
const filters = ref({ ...props.filters })

const createForm = useForm({
  supplier_id: '',
  funding_id: null,
  transfer_date: new Date().toISOString().split('T')[0],
  amount: '',
  currency: 'SAR',
  bank_reference: '',
  internal_reference: '',
  notes: '',
})

function applyFilters() {
  router.get(route('admin.funding-logs'), filters.value, { preserveState: true })
}

function exportCsv() {
  const params = new URLSearchParams()
  if (filters.value.date_from) params.append('date_from', filters.value.date_from)
  if (filters.value.date_to) params.append('date_to', filters.value.date_to)
  if (filters.value.supplier_id) params.append('supplier_id', filters.value.supplier_id)
  window.location.href = route('admin.api.funding-logs.export') + (params.toString() ? '?' + params.toString() : '')
}

function submitCreate() {
  createForm.post(route('admin.api.funding-logs'), {
    onSuccess: () => {
      showCreateModal.value = false
      createForm.reset()
    },
  })
}
</script>



