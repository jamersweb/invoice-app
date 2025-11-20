<template>
    <Head title="Notifications" />
    <AuthenticatedLayout>
        <div class="p-6 space-y-6">
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h1 class="text-3xl font-bold text-white">Notifications</h1>
                    <p class="text-slate-400 mt-1">Manage system notifications and reminders</p>
                </div>
                <button
                    @click="showAddForm = !showAddForm"
                    class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition-colors"
                >
                    + Add Notification
                </button>
            </div>

            <!-- Add Notification Form -->
            <div v-if="showAddForm" class="bg-slate-800/30 border border-slate-700 rounded-lg p-6 mb-6">
                <h2 class="text-xl font-semibold text-white mb-4">Add New Notification</h2>
                <form @submit.prevent="submit">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-300 mb-2">Type</label>
                            <select
                                v-model="form.type"
                                required
                                class="w-full px-3 py-2 bg-slate-700 border border-slate-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                            >
                                <option value="">Select Type</option>
                                <option value="payment_reminder">Payment Reminder</option>
                                <option value="document_expiry">Document Expiry</option>
                                <option value="deal_ending">Deal Ending</option>
                                <option value="general">General</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-300 mb-2">Title</label>
                            <input
                                v-model="form.title"
                                type="text"
                                required
                                class="w-full px-3 py-2 bg-slate-700 border border-slate-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                            />
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-slate-300 mb-2">Message</label>
                            <textarea
                                v-model="form.message"
                                rows="4"
                                required
                                class="w-full px-3 py-2 bg-slate-700 border border-slate-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                            ></textarea>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-300 mb-2">Scheduled At</label>
                            <input
                                v-model="form.scheduled_at"
                                type="datetime-local"
                                class="w-full px-3 py-2 bg-slate-700 border border-slate-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                            />
                        </div>
                    </div>
                    <div class="flex gap-3 mt-4">
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition-colors disabled:opacity-50"
                        >
                            {{ form.processing ? 'Saving...' : 'Save Notification' }}
                        </button>
                        <button
                            type="button"
                            @click="showAddForm = false; form.reset()"
                            class="px-4 py-2 bg-slate-600 hover:bg-slate-700 text-white rounded-lg font-medium transition-colors"
                        >
                            Cancel
                        </button>
                    </div>
                </form>
            </div>

            <!-- Filters -->
            <div class="bg-slate-800/30 border border-slate-700 rounded-lg p-4 mb-6">
                <div class="flex flex-wrap gap-4">
                    <select
                        v-model="filters.type"
                        class="px-3 py-2 bg-slate-700 border border-slate-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >
                        <option value="">All Types</option>
                        <option value="payment_reminder">Payment Reminder</option>
                        <option value="document_expiry">Document Expiry</option>
                        <option value="deal_ending">Deal Ending</option>
                        <option value="general">General</option>
                    </select>
                    <select
                        v-model="filters.status"
                        class="px-3 py-2 bg-slate-700 border border-slate-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >
                        <option value="">All Status</option>
                        <option value="pending">Pending</option>
                        <option value="sent">Sent</option>
                        <option value="failed">Failed</option>
                    </select>
                </div>
            </div>

            <!-- Notifications Table -->
            <div class="bg-slate-800/30 border border-slate-700 rounded-lg overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-slate-800/50">
                            <tr>
                                <th class="text-left text-slate-300 py-3 px-4">Type</th>
                                <th class="text-left text-slate-300 py-3 px-4">Title</th>
                                <th class="text-left text-slate-300 py-3 px-4">Message</th>
                                <th class="text-left text-slate-300 py-3 px-4">Scheduled At</th>
                                <th class="text-center text-slate-300 py-3 px-4">Status</th>
                                <th class="text-right text-slate-300 py-3 px-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="notification in filteredNotifications" :key="notification.id" class="border-t border-slate-700/50">
                                <td class="text-white py-3 px-4">{{ formatType(notification.type) }}</td>
                                <td class="text-white font-medium py-3 px-4">{{ notification.title }}</td>
                                <td class="text-slate-400 py-3 px-4 max-w-xs truncate">{{ notification.message }}</td>
                                <td class="text-slate-300 py-3 px-4">{{ notification.scheduled_at ? formatDate(notification.scheduled_at) : '-' }}</td>
                                <td class="text-center py-3 px-4">
                                    <span :class="getStatusClass(notification.status)"
                                          class="px-2 py-1 rounded text-xs">
                                        {{ notification.status }}
                                    </span>
                                </td>
                                <td class="text-right py-3 px-4">
                                    <button
                                        v-if="notification.status === 'pending'"
                                        @click="updateStatus(notification.id, 'sent')"
                                        class="text-green-400 hover:text-green-300 transition-colors"
                                    >
                                        Mark Sent
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="filteredNotifications.length === 0">
                                <td colspan="6" class="text-center text-slate-400 py-8">No notifications found</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="notifications.links" class="bg-slate-800/50 px-4 py-3 border-t border-slate-700">
                    <div class="flex justify-between items-center">
                        <div class="text-sm text-slate-400">
                            Showing {{ notifications.from }} to {{ notifications.to }} of {{ notifications.total }} notifications
                        </div>
                        <div class="flex gap-2">
                            <a
                                v-for="link in notifications.links"
                                :key="link.label"
                                :href="link.url ?? undefined"
                                v-html="link.label"
                                :class="[
                                    'px-3 py-1 rounded text-sm',
                                    link.active ? 'bg-blue-600 text-white' : 'bg-slate-700 text-slate-300 hover:bg-slate-600',
                                    !link.url ? 'opacity-50 cursor-not-allowed' : 'cursor-pointer'
                                ]"
                            ></a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup lang="ts">
import { Head, useForm, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { ref, computed } from 'vue';
import { route } from 'ziggy-js';

interface Notification {
    id: number;
    type: string;
    title: string;
    message: string;
    scheduled_at: string | null;
    status: string;
}

interface Props {
    notifications: {
        data: Notification[];
        links: Array<{ label: string; url: string | null; active: boolean }>;
        from: number;
        to: number;
        total: number;
    };
}

const props = defineProps<Props>();

const showAddForm = ref(false);
const filters = ref({
    type: '',
    status: '',
});

const form = useForm({
    type: '',
    title: '',
    message: '',
    recipient_type: null,
    recipient_id: null,
    scheduled_at: '',
    metadata: null,
});

const submit = () => {
    form.post(route('forfaiting.notifications.store'), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            showAddForm.value = false;
        },
    });
};

const updateStatus = (id: number, status: string) => {
    router.put(route('forfaiting.notifications.update-status', id), { status }, {
        preserveScroll: true,
    });
};

const filteredNotifications = computed(() => {
    let filtered = props.notifications.data;

    if (filters.value.type) {
        filtered = filtered.filter(n => n.type === filters.value.type);
    }

    if (filters.value.status) {
        filtered = filtered.filter(n => n.status === filters.value.status);
    }

    return filtered;
});

const formatType = (type: string) => {
    return type.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
};

const getStatusClass = (status: string) => {
    const classes: Record<string, string> = {
        'pending': 'bg-yellow-500/20 text-yellow-400',
        'sent': 'bg-green-500/20 text-green-400',
        'failed': 'bg-red-500/20 text-red-400',
    };
    return classes[status] || 'bg-slate-500/20 text-slate-400';
};

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};
</script>

