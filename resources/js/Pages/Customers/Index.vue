<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import Badge from '@/Components/Badge.vue';
import DarkInput from '@/Components/DarkInput.vue';
import { ref, onMounted } from 'vue';

const searchQuery = ref('');

const customers = ref([
    { id: 1, name: 'John Doe', email: 'john@example.com', risk_grade: 'A', total_invoices: 15, status: 'active' },
    { id: 2, name: 'Jane Smith', email: 'jane@example.com', risk_grade: 'B', total_invoices: 8, status: 'active' },
    { id: 3, name: 'Bob Johnson', email: 'bob@example.com', risk_grade: 'C', total_invoices: 3, status: 'pending' },
]);

// Try to load real data if API available
onMounted(async () => {
    try {
        const res = await fetch('/api/v1/admin/buyers', {
            headers: { Accept: 'application/json' },
            credentials: 'same-origin'
        });
        if (res.ok) {
            const data = await res.json();
            if (data?.data && Array.isArray(data.data)) {
                customers.value = data.data.map((b: any) => ({
                    id: b.id,
                    name: b.name || 'Unknown',
                    email: b.email || '',
                    risk_grade: b.risk_grade || 'N/A',
                    total_invoices: 0,
                    status: 'active'
                }));
            }
        }
    } catch (e) {
        // Keep mock data if API fails
        console.log('Using mock customer data');
    }
});
</script>

<template>

    <Head title="Customers" />

    <AuthenticatedLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-dark-text-primary" style="color: #FFFFFF;">Customers</h1>
                    <p class="mt-1 text-sm text-dark-text-secondary" style="color: #B0B0B0;">Manage your customer
                        relationships</p>
                </div>
            </div>

            <!-- Search and Filters -->
            <div class="card">
                <div class="flex items-center gap-4">
                    <div class="flex-1">
                        <DarkInput v-model="searchQuery" placeholder="Search customers..." icon="search"
                            class="!pr-10" />
                    </div>
                </div>
            </div>

            <!-- Customers Table -->
            <div class="card overflow-hidden p-0">
                <div class="overflow-x-auto custom-scrollbar">
                    <table class="table-dark w-full">
                        <thead>
                            <tr>
                                <th
                                    class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-dark-text-secondary">
                                    ID</th>
                                <th
                                    class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-dark-text-secondary">
                                    Name</th>
                                <th
                                    class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-dark-text-secondary">
                                    Email</th>
                                <th
                                    class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-dark-text-secondary">
                                    Risk Grade</th>
                                <th
                                    class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-dark-text-secondary">
                                    Total Invoices</th>
                                <th
                                    class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-dark-text-secondary">
                                    Status</th>
                                <th
                                    class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-dark-text-secondary">
                                    Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template v-if="customers && customers.length > 0">
                                <tr v-for="customer in customers" :key="customer.id" class="hover:bg-dark-secondary">
                                    <td class="px-4 py-3 font-medium text-dark-text-primary">{{ customer.id }}</td>
                                    <td class="px-4 py-3 text-dark-text-primary">{{ customer.name }}</td>
                                    <td class="px-4 py-3 text-dark-text-secondary">{{ customer.email }}</td>
                                    <td class="px-4 py-3">
                                        <Badge
                                            :variant="customer.risk_grade === 'A' ? 'success' : customer.risk_grade === 'B' ? 'info' : 'warning'">
                                            {{ customer.risk_grade }}
                                        </Badge>
                                    </td>
                                    <td class="px-4 py-3 text-dark-text-primary">{{ customer.total_invoices }}</td>
                                    <td class="px-4 py-3">
                                        <Badge :variant="customer.status === 'active' ? 'success' : 'warning'">
                                            {{ customer.status.toUpperCase() }}
                                        </Badge>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <div class="flex items-center justify-end gap-2">
                                            <button class="p-1.5 hover:bg-dark-tertiary rounded transition-colors">
                                                <svg width="16" height="16" fill="none" viewBox="0 0 16 16"
                                                    class="text-dark-text-secondary">
                                                    <path stroke="currentColor" stroke-width="1.5"
                                                        d="M11.333 2.667L5 9M11.333 2.667h-4v4h4v-4z" />
                                                </svg>
                                            </button>
                                            <button class="p-1.5 hover:bg-dark-tertiary rounded transition-colors">
                                                <svg width="16" height="16" fill="none" viewBox="0 0 16 16"
                                                    class="text-dark-text-secondary">
                                                    <path stroke="currentColor" stroke-width="1.5"
                                                        d="M2 4h12M6 8h4M4 12h8" />
                                                </svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                            <tr v-else>
                                <td colspan="7" class="px-4 py-8 text-center text-dark-text-muted">
                                    No customers found
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
