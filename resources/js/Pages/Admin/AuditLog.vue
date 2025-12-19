<template>
  <AuthenticatedLayout>

    <Head title="Admin - Audit Log" />
    <div class="space-y-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold text-gray-900">Audit Log</h1>
          <p class="mt-2 text-sm text-gray-600">Complete audit trail of all system actions with correlation IDs for
            tracing.</p>
        </div>
        <div class="flex space-x-3">
          <button @click="exportCsv"
            class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
            Export CSV
          </button>
        </div>
      </div>

      <!-- Filters -->
      <div
        class="rounded-lg border rounded-xl border text-card-foreground shadow bg-slate-800/40 border-slate-700/50 p-4 hover:bg-slate-800/60 transition-all">
        <form @submit.prevent="applyFilters" class="grid grid-cols-1 gap-4 sm:grid-cols-3 lg:grid-cols-4">
          <div>
            <label class="block text-xs font-medium text-gray-700">Actor</label>
            <select v-model="filters.actor_id"
              class="mt-1 block w-full shadow-sm sm:text-sm rounded-xl border text-card-foreground shadow bg-slate-800/40 backdrop-blur-sm border-slate-700/50 ">
              <option value="">All Actors</option>
              <option v-for="a in actors" :key="a.id" :value="a.id">{{ a.name }}</option>
            </select>
          </div>
          <div>
            <label class="block text-xs font-medium text-gray-700">Entity Type</label>
            <select v-model="filters.entity_type"
              class="mt-1 block w-full shadow-sm sm:text-sm rounded-xl border text-card-foreground shadow bg-slate-800/40 backdrop-blur-sm border-slate-700/50 ">
              <option value="">All Types</option>
              <option v-for="t in entity_types" :key="t" :value="t">{{ t }}</option>
            </select>
          </div>
          <div>
            <label class="block text-xs font-medium text-gray-700">Action</label>
            <input v-model="filters.action" type="text" placeholder="Search action..."
              class="mt-1 block w-full shadow-sm sm:text-sm rounded-xl border text-card-foreground shadow bg-slate-800/40 backdrop-blur-sm border-slate-700/50 " />
          </div>
          <div>
            <label class="block text-xs font-medium text-gray-700">Correlation ID</label>
            <input v-model="filters.correlation_id" type="text" placeholder="UUID..."
              class="mt-1 block w-full shadow-sm sm:text-sm rounded-xl border text-card-foreground shadow bg-slate-800/40 backdrop-blur-sm border-slate-700/50  font-mono text-xs" />
          </div>
          <div>
            <label class="block text-xs font-medium text-gray-700">Date From</label>
            <input v-model="filters.date_from" type="date"
              class="mt-1 block w-full shadow-sm sm:text-sm rounded-xl border text-card-foreground shadow bg-slate-800/40 backdrop-blur-sm border-slate-700/50 " />
          </div>
          <div>
            <label class="block text-xs font-medium text-gray-700">Date To</label>
            <input v-model="filters.date_to" type="date"
              class="mt-1 block w-full shadow-sm sm:text-sm rounded-xl border text-card-foreground shadow bg-slate-800/40 backdrop-blur-sm border-slate-700/50 " />
          </div>
          <div class="flex items-end">
            <button type="submit"
              class="w-full rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">Filter</button>
          </div>
          <div class="flex items-end">
            <button type="button" @click="clearFilters"
              class="w-full rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Clear</button>
          </div>
        </form>
      </div>

      <!-- Table -->
      <div
        class="rounded-xl border text-card-foreground shadow bg-slate-800/40 border-slate-700/50 p-4 hover:bg-slate-800/60 transition-all overflow-x-auto custom-scrollbar">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Timestamp</th>
              <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Actor</th>
              <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Action</th>
              <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Entity</th>
              <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Correlation ID
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">IP</th>
              <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Changes</th>
              <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200 bg-white">
            <tr v-for="log in logs.data" :key="log.id" class="hover:bg-gray-50">
              <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900">{{ log.created_at }}</td>
              <td class="px-6 py-4 text-sm text-gray-900">{{ log.actor }}</td>
              <td class="px-6 py-4 text-sm text-gray-900 font-mono text-xs">{{ log.action }}</td>
              <td class="px-6 py-4 text-sm text-gray-500">{{ log.entity_type }} #{{ log.entity_id }}</td>
              <td class="px-6 py-4 text-sm font-mono text-xs text-gray-500">
                <button @click="copyCorrelationId(log.correlation_id)" class="hover:text-indigo-600"
                  :title="log.correlation_id">
                  {{ log.correlation_id ? log.correlation_id.substring(0, 8) + '...' : '—' }}
                </button>
              </td>
              <td class="px-6 py-4 text-sm text-gray-500">{{ log.ip || '—' }}</td>
              <td class="px-6 py-4 text-sm text-gray-500">{{ log.diff_summary || '—' }}</td>
              <td class="whitespace-nowrap px-6 py-4 text-sm">
                <button @click="viewDetails(log.id)" class="text-indigo-600 hover:text-indigo-900">View</button>
              </td>
            </tr>
          </tbody>
        </table>
        <div v-if="logs.data.length === 0" class="px-6 py-12 text-center text-gray-500">No audit logs found.</div>
        <div v-if="logs.links && logs.links.length > 3" class="border-t border-gray-200 bg-white px-4 py-3 sm:px-6">
          <div class="flex items-center justify-between">
            <div class="flex-1 flex justify-between sm:hidden">
              <a v-if="logs.prev_page_url" :href="logs.prev_page_url"
                class="relative inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Previous</a>
              <a v-if="logs.next_page_url" :href="logs.next_page_url"
                class="relative ml-3 inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Next</a>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Detail Modal -->
    <div v-if="selectedLog" class="fixed inset-0 z-50 overflow-y-auto" @click.self="selectedLog = null">
      <div class="flex min-h-screen items-center justify-center p-4">
        <div class="w-full max-w-4xl rounded-lg bg-white shadow-xl">
          <div class="border-b border-gray-200 px-6 py-4">
            <h3 class="text-lg font-semibold text-gray-900">Audit Log Details</h3>
          </div>
          <div class="px-6 py-4 space-y-4 max-h-96 overflow-y-auto">
            <div>
              <label class="block text-xs font-medium text-gray-500 uppercase">Correlation ID</label>
              <p class="mt-1 text-sm font-mono text-gray-900">{{ selectedLog.correlation_id || '—' }}</p>
            </div>
            <div>
              <label class="block text-xs font-medium text-gray-500 uppercase">Actor</label>
              <p class="mt-1 text-sm text-gray-900">{{ selectedLog.actor?.name || 'System' }}</p>
            </div>
            <div>
              <label class="block text-xs font-medium text-gray-500 uppercase">Action</label>
              <p class="mt-1 text-sm font-mono text-gray-900">{{ selectedLog.action }}</p>
            </div>
            <div>
              <label class="block text-xs font-medium text-gray-500 uppercase">IP Address</label>
              <p class="mt-1 text-sm text-gray-900">{{ selectedLog.ip || '—' }}</p>
            </div>
            <div>
              <label class="block text-xs font-medium text-gray-500 uppercase">User Agent</label>
              <p class="mt-1 text-sm text-gray-500">{{ selectedLog.ua || '—' }}</p>
            </div>
            <div v-if="selectedLog.diff_json">
              <label class="block text-xs font-medium text-gray-500 uppercase">Changes</label>
              <pre
                class="mt-1 text-xs bg-gray-50 p-4 rounded overflow-auto">{{ JSON.stringify(selectedLog.diff_json, null, 2) }}</pre>
            </div>
          </div>
          <div class="border-t border-gray-200 px-6 py-4 flex justify-end">
            <button @click="selectedLog = null"
              class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">Close</button>
          </div>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>

<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { ref } from 'vue'

const props = defineProps<{
  logs: any
  actors: Array<{ id: number; name: string }>
  entity_types: string[]
  filters: { actor_id?: string; entity_type?: string; action?: string; correlation_id?: string; date_from?: string; date_to?: string }
}>()

const filters = ref({ ...props.filters })
const selectedLog = ref<any>(null)

function applyFilters() {
  router.get(route('admin.audit-log'), filters.value, { preserveState: true })
}

function clearFilters() {
  filters.value = {}
  router.get(route('admin.audit-log'), {}, { preserveState: true })
}

function exportCsv() {
  const params = new URLSearchParams()
  Object.entries(filters.value).forEach(([k, v]) => {
    if (v) params.append(k, v as string)
  })
  window.location.href = route('admin.api.audit-log.export') + (params.toString() ? '?' + params.toString() : '')
}

async function viewDetails(id: number) {
  const response = await fetch(`/admin/api/audit-log/${id}`, { credentials: 'include' })
  selectedLog.value = await response.json()
}

function copyCorrelationId(id: string) {
  navigator.clipboard.writeText(id)
  // Could show toast notification here
}
</script>
