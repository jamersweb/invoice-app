<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import Badge from '@/Components/Badge.vue';
import DarkInput from '@/Components/DarkInput.vue';

const props = defineProps<{
    suppliers: {
        data: any[];
        links: any[];
        current_page: number;
        last_page: number;
        total: number;
        from: number;
        to: number;
    };
    filters: {
        status: string;
        search: string;
    };
}>();

const search = ref(props.filters.search || '');
const status = ref(props.filters.status || '');

// Simple debounce implementation
const debounce = (fn: Function, delay: number) => {
    let timeoutId: ReturnType<typeof setTimeout>;
    return (...args: any[]) => {
        clearTimeout(timeoutId);
        timeoutId = setTimeout(() => fn(...args), delay);
    };
};

const updateFilters = debounce(() => {
    router.get(route('admin.suppliers.index'), {
        search: search.value,
        status: status.value,
    }, {
        preserveState: true,
        replace: true,
    });
}, 300);

watch([search, status], () => {
    updateFilters();
});

const getStatusBadge = (status: string) => {
    switch (status) {
        case 'pending': return 'warning';
        case 'under_review': return 'info';
        case 'approved': return 'success';
        case 'rejected': return 'danger';
        default: return 'info';
    }
};
</script>

<template>
    <Head title="Suppliers" />

    <AuthenticatedLayout>
        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-dark-text-primary">Suppliers</h1>
                    <p class="mt-1 text-sm text-dark-text-secondary">Manage and verify supplier onboarding applications</p>
                </div>
            </div>

            <!-- Filters -->
            <div class="card p-4">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <div>
                        <label class="block text-sm font-medium text-dark-text-secondary mb-2">Search</label>
                        <DarkInput
                            v-model="search"
                            placeholder="Company, email, or legal name..."
                            icon="search"
                        />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-dark-text-secondary mb-2">Status</label>
                        <select
                            v-model="status"
                            class="input-dark w-full bg-dark-secondary border-dark-border text-white rounded-lg p-2.5 outline-none focus:border-purple-accent"
                        >
                            <option value="">All Statuses</option>
                            <option value="pending">Pending</option>
                            <option value="under_review">Under Review</option>
                            <option value="approved">Approved</option>
                            <option value="rejected">Rejected</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Table -->
            <div class="card overflow-hidden p-0">
                <div class="overflow-x-auto">
                    <table class="table-dark w-full text-left">
                        <thead>
                            <tr class="border-b border-dark-border bg-dark-tertiary/50">
                                <th class="px-6 py-4 text-xs font-semibold uppercase tracking-wider text-dark-text-secondary text-left">Company</th>
                                <th class="px-6 py-4 text-xs font-semibold uppercase tracking-wider text-dark-text-secondary text-left">Contact</th>
                                <th class="px-6 py-4 text-xs font-semibold uppercase tracking-wider text-dark-text-secondary text-left">Status</th>
                                <th class="px-6 py-4 text-xs font-semibold uppercase tracking-wider text-dark-text-secondary text-left">Submitted</th>
                                <th class="px-6 py-4 text-xs font-semibold uppercase tracking-wider text-dark-text-secondary text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-dark-border">
                            <tr v-for="supplier in suppliers.data" :key="supplier.id" class="hover:bg-dark-tertiary/30 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="text-sm font-medium text-dark-text-primary">{{ supplier.company_name }}</div>
                                    <div class="text-xs text-dark-text-secondary">{{ supplier.legal_name }}</div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-dark-text-primary">{{ supplier.contact_email }}</div>
                                    <div class="text-xs text-dark-text-secondary">{{ supplier.contact_phone || '-' }}</div>
                                </td>
                                <td class="px-6 py-4">
                                    <Badge :variant="getStatusBadge(supplier.kyb_status)">
                                        {{ supplier.kyb_status }}
                                    </Badge>
                                </td>
                                <td class="px-6 py-4 text-sm text-dark-text-secondary">
                                    {{ new Date(supplier.created_at).toLocaleDateString() }}
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <Link
                                        :href="route('admin.suppliers.show', supplier.id)"
                                        class="inline-flex items-center text-sm font-medium text-purple-accent hover:text-purple-hover"
                                    >
                                        Review
                                        <svg class="ml-1 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                        </svg>
                                    </Link>
                                </td>
                            </tr>
                            <tr v-if="suppliers.data.length === 0">
                                <td colspan="5" class="px-6 py-12 text-center text-dark-text-muted">
                                    No suppliers found matching your criteria.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="suppliers.last_page > 1" class="border-t border-dark-border p-4 flex items-center justify-between">
                    <div class="text-sm text-dark-text-secondary">
                        Showing {{ suppliers.from }} to {{ suppliers.to }} of {{ suppliers.total }} suppliers
                    </div>
                    <div class="flex items-center gap-2">
                        <Link
                            v-for="(link, i) in suppliers.links"
                            :key="i"
                            :href="link.url || '#'"
                            class="px-3 py-1 text-sm rounded-md border border-dark-border transition-colors"
                            :class="[
                                link.active ? 'bg-purple-accent text-white border-purple-accent' : 'text-dark-text-secondary hover:bg-dark-tertiary',
                                !link.url ? 'opacity-50 cursor-not-allowed' : ''
                            ]"
                            v-html="link.label"
                        />
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
