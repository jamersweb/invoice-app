<template>

  <Head title="Notifications" />
  <AuthenticatedLayout>
    <div class="p-6">
      <div class="max-w-[1800px] mx-auto">
        <!-- Header -->
        <div class="mb-8">
          <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <div class="flex items-center gap-3">
              <div class="p-3 bg-yellow-600/20 rounded-xl">
                <svg class="w-8 h-8 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                </svg>
              </div>
              <div>
                <h2 class="text-3xl font-bold text-white">Notifications</h2>
                <p class="text-slate-400 mt-1">Manage system notifications and alerts</p>
              </div>
            </div>
            <button v-if="!isFormOpen" @click="isFormOpen = true"
              class="w-full sm:w-auto bg-yellow-600 hover:bg-yellow-700 text-white px-4 py-2 rounded-lg flex items-center justify-center gap-2">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
              </svg>
              Create Notification
            </button>
          </div>
        </div>

        <!-- Form -->
        <div v-if="isFormOpen" class="mb-6 bg-slate-800/50 border border-slate-700 rounded-lg p-6">
          <div class="flex justify-between items-center mb-4">
            <h3 class="text-white text-lg font-semibold">Create New Notification</h3>
            <button @click="cancelForm" class="text-slate-400 hover:text-white">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
          <form @submit.prevent="submitForm" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-slate-300 mb-1">Type</label>
                <input v-model="form.type" type="text" required
                  class="w-full rounded-lg border border-slate-600 bg-slate-900/50 p-2 text-white"
                  placeholder="Notification type" />
              </div>
              <div>
                <label class="block text-sm font-medium text-slate-300 mb-1">Title</label>
                <input v-model="form.title" type="text" required
                  class="w-full rounded-lg border border-slate-600 bg-slate-900/50 p-2 text-white"
                  placeholder="Notification title" />
              </div>
              <div class="md:col-span-2">
                <label class="block text-sm font-medium text-slate-300 mb-1">Message</label>
                <textarea v-model="form.message" required
                  class="w-full rounded-lg border border-slate-600 bg-slate-900/50 p-2 text-white" rows="4"
                  placeholder="Notification message"></textarea>
              </div>
              <div>
                <label class="block text-sm font-medium text-slate-300 mb-1">Scheduled At</label>
                <input v-model="form.scheduled_at" type="datetime-local"
                  class="w-full rounded-lg border border-slate-600 bg-slate-900/50 p-2 text-white" />
              </div>
            </div>
            <div class="flex justify-end gap-3">
              <button type="button" @click="cancelForm"
                class="px-4 py-2 rounded-lg border border-slate-600 text-white hover:bg-slate-700">
                Cancel
              </button>
              <button type="submit" :disabled="form.processing"
                class="px-4 py-2 rounded-lg bg-yellow-600 hover:bg-yellow-700 text-white disabled:opacity-50">
                {{ form.processing ? 'Creating...' : 'Create' }}
              </button>
            </div>
          </form>
        </div>

        <!-- Filters -->
        <div class="mb-6 flex flex-wrap gap-4">
          <select v-model="filters.type" class="rounded-lg border border-slate-600 bg-slate-800/50 p-2 text-white">
            <option value="all">All Types</option>
            <option v-for="type in uniqueTypes" :key="type" :value="type">{{ type }}</option>
          </select>
          <select v-model="filters.status" class="rounded-lg border border-slate-600 bg-slate-800/50 p-2 text-white">
            <option value="all">All Status</option>
            <option value="pending">Pending</option>
            <option value="sent">Sent</option>
            <option value="failed">Failed</option>
          </select>
        </div>

        <!-- Notifications Table -->
        <div class="bg-slate-800/30 border border-slate-700 rounded-lg overflow-hidden">
          <div class="overflow-x-auto custom-scrollbar">
            <table class="w-full">
              <thead class="bg-slate-800/50 border-b border-slate-700">
                <tr>
                  <th class="text-left text-slate-300 text-xs p-3">Type</th>
                  <th class="text-left text-slate-300 text-xs p-3">Title</th>
                  <th class="text-left text-slate-300 text-xs p-3">Message</th>
                  <th class="text-right text-slate-300 text-xs p-3">Scheduled</th>
                  <th class="text-center text-slate-300 text-xs p-3">Status</th>
                  <th class="text-right text-slate-300 text-xs p-3">Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="notification in filteredNotifications" :key="notification.id"
                  class="border-b border-slate-700/50 hover:bg-slate-800/30">
                  <td class="text-white text-xs p-3">{{ notification.type }}</td>
                  <td class="text-white font-medium text-xs p-3 truncate max-w-[200px]">{{ notification.title }}</td>
                  <td class="text-slate-300 text-xs p-3 max-w-xs truncate">{{ notification.message }}</td>
                  <td class="text-right text-slate-300 text-xs p-3">
                    {{ notification.scheduled_at ? formatDate(notification.scheduled_at) : '-' }}
                  </td>
                  <td class="text-center p-3">
                    <span :class="`px-2 py-1 rounded text-xs ${notification.status === 'sent'
                      ? 'bg-green-500/20 text-green-400 border border-green-500/50'
                      : notification.status === 'pending'
                        ? 'bg-amber-500/20 text-amber-400 border border-amber-500/50'
                        : 'bg-red-500/20 text-red-400 border border-red-500/50'
                      }`">
                      {{ notification.status }}
                    </span>
                  </td>
                  <td class="text-right p-3">
                    <select @change="(e) => updateStatus(notification.id, (e.target as HTMLSelectElement).value)"
                      :value="notification.status"
                      class="rounded border border-slate-600 bg-slate-800/50 p-1 text-white text-xs">
                      <option value="pending">Pending</option>
                      <option value="sent">Sent</option>
                      <option value="failed">Failed</option>
                    </select>
                  </td>
                </tr>
                <tr v-if="filteredNotifications.length === 0">
                  <td colspan="6" class="text-center text-slate-400 py-8">No notifications found</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Pagination -->
        <div v-if="notifications.links && notifications.links.length > 3" class="mt-6 flex justify-center">
          <div class="flex gap-2">
            <Link v-for="link in notifications.links" :key="link.label" :href="link.url || '#'" v-html="link.label"
              :class="`px-3 py-2 rounded-lg ${link.active
                ? 'bg-yellow-600 text-white'
                : 'bg-slate-800/50 text-slate-300 hover:bg-slate-700'
                } ${!link.url ? 'opacity-50 cursor-not-allowed' : ''}`" />
          </div>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>

<script setup lang="ts">
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { ref, computed } from 'vue';

interface Notification {
  id: number;
  type: string;
  title: string;
  message: string;
  scheduled_at?: string;
  status: string;
}

interface Props {
  notifications: {
    data: Notification[];
    links: any[];
  };
}

const props = defineProps<Props>();

const isFormOpen = ref(false);

const form = useForm({
  type: '',
  title: '',
  message: '',
  scheduled_at: '',
});

const filters = ref({
  type: 'all',
  status: 'all',
});

const uniqueTypes = computed(() => {
  const types = new Set(props.notifications.data.map((n) => n.type));
  return Array.from(types).sort();
});

const filteredNotifications = computed(() => {
  let result = props.notifications.data;

  if (filters.value.type !== 'all') {
    result = result.filter((n) => n.type === filters.value.type);
  }

  if (filters.value.status !== 'all') {
    result = result.filter((n) => n.status === filters.value.status);
  }

  return result;
});

const cancelForm = () => {
  isFormOpen.value = false;
  form.reset();
};

const submitForm = () => {
  form.post(route('forfaiting.notifications.store'), {
    preserveScroll: true,
    onSuccess: () => {
      cancelForm();
    },
  });
};

const updateStatus = (id: number, status: string) => {
  router.put(
    route('forfaiting.notifications.update-status', id),
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
