<template>
    <Head title="Contact Requests" />
    <AuthenticatedLayout>
        <div class="p-6 space-y-6">
            <div class="mb-6">
                <h1 class="text-3xl font-bold text-white">Contact Requests</h1>
                <p class="text-slate-400 mt-1">Manage incoming contact form submissions</p>
            </div>

            <!-- Filters -->
            <div class="bg-slate-800/30 border border-slate-700 rounded-lg p-4 mb-6">
                <div class="flex flex-wrap gap-4">
                    <input
                        v-model="filters.search"
                        type="text"
                        placeholder="Search contact requests..."
                        class="px-3 py-2 bg-slate-700 border border-slate-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                    />
                    <select
                        v-model="filters.status"
                        class="px-3 py-2 bg-slate-700 border border-slate-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >
                        <option value="">All Status</option>
                        <option value="new">New</option>
                        <option value="contacted">Contacted</option>
                        <option value="resolved">Resolved</option>
                        <option value="archived">Archived</option>
                    </select>
                </div>
            </div>

            <!-- Contact Requests Table -->
            <div class="bg-slate-800/30 border border-slate-700 rounded-lg overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-slate-800/50">
                            <tr>
                                <th class="text-left text-slate-300 py-3 px-4">Name</th>
                                <th class="text-left text-slate-300 py-3 px-4">Email</th>
                                <th class="text-left text-slate-300 py-3 px-4">Company</th>
                                <th class="text-left text-slate-300 py-3 px-4">Message</th>
                                <th class="text-left text-slate-300 py-3 px-4">Date</th>
                                <th class="text-center text-slate-300 py-3 px-4">Status</th>
                                <th class="text-right text-slate-300 py-3 px-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="request in filteredRequests" :key="request.id" class="border-t border-slate-700/50">
                                <td class="text-white font-medium py-3 px-4">{{ request.name }}</td>
                                <td class="text-slate-300 py-3 px-4">{{ request.email }}</td>
                                <td class="text-slate-300 py-3 px-4">{{ request.company || '-' }}</td>
                                <td class="text-slate-400 py-3 px-4 max-w-xs truncate">{{ request.message }}</td>
                                <td class="text-slate-300 py-3 px-4">{{ formatDate(request.created_at) }}</td>
                                <td class="text-center py-3 px-4">
                                    <span :class="getStatusClass(request.status)"
                                          class="px-2 py-1 rounded text-xs">
                                        {{ request.status }}
                                    </span>
                                </td>
                                <td class="text-right py-3 px-4">
                                    <div class="flex justify-end gap-2">
                                        <button
                                            @click="updateStatus(request.id, 'contacted')"
                                            v-if="request.status === 'new'"
                                            class="text-blue-400 hover:text-blue-300 transition-colors"
                                        >
                                            Mark Contacted
                                        </button>
                                        <button
                                            @click="updateStatus(request.id, 'resolved')"
                                            v-if="request.status !== 'resolved'"
                                            class="text-green-400 hover:text-green-300 transition-colors"
                                        >
                                            Resolve
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="filteredRequests.length === 0">
                                <td colspan="7" class="text-center text-slate-400 py-8">No contact requests found</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="contactRequests.links" class="bg-slate-800/50 px-4 py-3 border-t border-slate-700">
                    <div class="flex justify-between items-center">
                        <div class="text-sm text-slate-400">
                            Showing {{ contactRequests.from }} to {{ contactRequests.to }} of {{ contactRequests.total }} requests
                        </div>
                        <div class="flex gap-2">
                            <a
                                v-for="link in contactRequests.links"
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
import { Head, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { ref, computed } from 'vue';
import { route } from 'ziggy-js';

interface ContactRequest {
    id: number;
    name: string;
    email: string;
    company: string | null;
    message: string;
    status: string;
    created_at: string;
}

interface Props {
    contactRequests: {
        data: ContactRequest[];
        links: Array<{ label: string; url: string | null; active: boolean }>;
        from: number;
        to: number;
        total: number;
    };
}

const props = defineProps<Props>();

const filters = ref({
    search: '',
    status: '',
});

const filteredRequests = computed(() => {
    let filtered = props.contactRequests.data;

    if (filters.value.search) {
        const search = filters.value.search.toLowerCase();
        filtered = filtered.filter(r => 
            r.name.toLowerCase().includes(search) ||
            r.email.toLowerCase().includes(search) ||
            (r.company && r.company.toLowerCase().includes(search))
        );
    }

    if (filters.value.status) {
        filtered = filtered.filter(r => r.status === filters.value.status);
    }

    return filtered;
});

const updateStatus = (id: number, status: string) => {
    router.put(route('forfaiting.contact-requests.update-status', id), { status }, {
        preserveScroll: true,
    });
};

const getStatusClass = (status: string) => {
    const classes: Record<string, string> = {
        'new': 'bg-blue-500/20 text-blue-400',
        'contacted': 'bg-yellow-500/20 text-yellow-400',
        'resolved': 'bg-green-500/20 text-green-400',
        'archived': 'bg-slate-500/20 text-slate-400',
    };
    return classes[status] || 'bg-slate-500/20 text-slate-400';
};

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
};
</script>

