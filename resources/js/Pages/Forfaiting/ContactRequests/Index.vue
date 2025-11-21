<template>
  <Head title="Contact Requests" />
  <AuthenticatedLayout>
    <div class="p-6">
      <div class="max-w-[1800px] mx-auto">
        <!-- Header -->
        <div class="mb-8">
          <div class="flex items-center gap-3 mb-2">
            <div class="p-3 bg-blue-600/20 rounded-xl">
              <svg class="w-8 h-8 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
              </svg>
            </div>
            <div>
              <h2 class="text-3xl font-bold text-white">Contact Requests</h2>
              <p class="text-slate-400 mt-1">Manage incoming contact requests and inquiries</p>
            </div>
          </div>
        </div>

        <!-- Filters -->
        <div class="mb-6 flex flex-wrap gap-4">
          <div class="flex-1 min-w-[200px]">
            <input
              v-model="filters.search"
              type="text"
              placeholder="Search requests..."
              class="w-full rounded-lg border border-slate-600 bg-slate-800/50 p-2 text-white placeholder-slate-400"
            />
          </div>
          <select
            v-model="filters.status"
            class="rounded-lg border border-slate-600 bg-slate-800/50 p-2 text-white"
          >
            <option value="all">All Status</option>
            <option value="new">New</option>
            <option value="contacted">Contacted</option>
            <option value="resolved">Resolved</option>
            <option value="archived">Archived</option>
          </select>
        </div>

        <!-- Contact Requests Table -->
        <div class="bg-slate-800/30 border border-slate-700 rounded-lg overflow-hidden">
          <div class="overflow-x-auto">
            <table class="w-full">
              <thead class="bg-slate-800/50 border-b border-slate-700">
                <tr>
                  <th class="text-left text-slate-300 text-xs p-3">Name</th>
                  <th class="text-left text-slate-300 text-xs p-3">Email</th>
                  <th class="text-left text-slate-300 text-xs p-3">Company</th>
                  <th class="text-left text-slate-300 text-xs p-3">Message</th>
                  <th class="text-right text-slate-300 text-xs p-3">Date</th>
                  <th class="text-center text-slate-300 text-xs p-3">Status</th>
                  <th class="text-right text-slate-300 text-xs p-3">Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="request in filteredRequests"
                  :key="request.id"
                  class="border-b border-slate-700/50 hover:bg-slate-800/30"
                >
                  <td class="text-white font-medium text-xs p-3">{{ request.name }}</td>
                  <td class="text-slate-300 text-xs p-3">{{ request.email }}</td>
                  <td class="text-slate-300 text-xs p-3">{{ request.company || '-' }}</td>
                  <td class="text-slate-300 text-xs p-3 max-w-xs truncate">{{ request.message }}</td>
                  <td class="text-right text-slate-300 text-xs p-3">{{ formatDate(request.created_at) }}</td>
                  <td class="text-center p-3">
                    <span
                      :class="`px-2 py-1 rounded text-xs ${
                        request.status === 'new'
                          ? 'bg-blue-500/20 text-blue-400 border border-blue-500/50'
                          : request.status === 'contacted'
                          ? 'bg-amber-500/20 text-amber-400 border border-amber-500/50'
                          : request.status === 'resolved'
                          ? 'bg-green-500/20 text-green-400 border border-green-500/50'
                          : 'bg-gray-500/20 text-gray-400 border border-gray-500/50'
                      }`"
                    >
                      {{ request.status }}
                    </span>
                  </td>
                  <td class="text-right p-3">
                    <select
                      @change="(e) => updateStatus(request.id, (e.target as HTMLSelectElement).value)"
                      :value="request.status"
                      class="rounded border border-slate-600 bg-slate-800/50 p-1 text-white text-xs"
                    >
                      <option value="new">New</option>
                      <option value="contacted">Contacted</option>
                      <option value="resolved">Resolved</option>
                      <option value="archived">Archived</option>
                    </select>
                  </td>
                </tr>
                <tr v-if="filteredRequests.length === 0">
                  <td colspan="7" class="text-center text-slate-400 py-8">No contact requests found</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Pagination -->
        <div v-if="contactRequests.links && contactRequests.links.length > 3" class="mt-6 flex justify-center">
          <div class="flex gap-2">
            <Link
              v-for="link in contactRequests.links"
              :key="link.label"
              :href="link.url || '#'"
              v-html="link.label"
              :class="`px-3 py-2 rounded-lg ${
                link.active
                  ? 'bg-blue-600 text-white'
                  : 'bg-slate-800/50 text-slate-300 hover:bg-slate-700'
              } ${!link.url ? 'opacity-50 cursor-not-allowed' : ''}`"
            />
          </div>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>

<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { ref, computed } from 'vue';

interface ContactRequest {
  id: number;
  name: string;
  email: string;
  phone?: string;
  company?: string;
  message: string;
  status: string;
  created_at: string;
}

interface Props {
  contactRequests: {
    data: ContactRequest[];
    links: any[];
  };
}

const props = defineProps<Props>();

const filters = ref({
  search: '',
  status: 'all',
});

const filteredRequests = computed(() => {
  let result = props.contactRequests.data;

  if (filters.value.search) {
    const search = filters.value.search.toLowerCase();
    result = result.filter(
      (r) =>
        r.name.toLowerCase().includes(search) ||
        r.email.toLowerCase().includes(search) ||
        (r.company && r.company.toLowerCase().includes(search))
    );
  }

  if (filters.value.status !== 'all') {
    result = result.filter((r) => r.status === filters.value.status);
  }

  return result;
});

const updateStatus = (id: number, status: string) => {
  router.put(
    route('forfaiting.contact-requests.update-status', id),
    { status },
    {
      preserveScroll: true,
    }
  );
};

const formatDate = (dateString: string) => {
  const date = new Date(dateString);
  return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
};
</script>
