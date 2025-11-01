<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import RevenueChart from '@/Components/Dashboard/RevenueChart.vue';
import PaymentStatistics from '@/Components/Dashboard/PaymentStatistics.vue';
import KpiCard from '@/Components/Dashboard/KpiCard.vue';
import { ref } from 'vue';

const paymentStats = ref({
    total: 150000,
    paid: 95000,
    partiallyPaid: 35000,
    overdue: 20000,
});

const series = ref([
    { date: '2024-10-01', funded: 50000, repaid: 45000 },
    { date: '2024-10-15', funded: 60000, repaid: 50000 },
    { date: '2024-11-01', funded: 55000, repaid: 52000 },
    { date: '2024-11-15', funded: 70000, repaid: 65000 },
]);
</script>

<template>
    <Head title="Reports" />

    <AuthenticatedLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="mb-6">
                <h1 class="text-2xl font-bold text-dark-text-primary" style="color: #FFFFFF;">Reports</h1>
                <p class="mt-1 text-sm text-dark-text-secondary" style="color: #B0B0B0;">View detailed analytics and reports</p>
            </div>
            
            <!-- KPI Cards -->
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
                <KpiCard
                    title="Total Revenue"
                    value="$250,000"
                    icon="💰"
                    :color="'green'"
                    :trend="'up'"
                    delta="+15.2%"
                />
                <KpiCard
                    title="Active Customers"
                    value="1,234"
                    icon="👥"
                    :color="'blue'"
                    :trend="'up'"
                    delta="+8.5%"
                />
                <KpiCard
                    title="Pending Invoices"
                    value="156"
                    icon="📄"
                    :color="'yellow'"
                    :trend="'neutral'"
                    delta="0.0%"
                />
                <KpiCard
                    title="Collection Rate"
                    value="94.2%"
                    icon="📊"
                    :color="'purple'"
                    :trend="'up'"
                    delta="+2.1%"
                />
            </div>

            <!-- Charts -->
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                <PaymentStatistics
                    :total="paymentStats.total"
                    :paid="paymentStats.paid"
                    :partially-paid="paymentStats.partiallyPaid"
                    :overdue="paymentStats.overdue"
                />

                <div class="lg:col-span-2">
                    <RevenueChart title="Revenue Trends" :series="series" />
                </div>
            </div>

            <!-- Additional Reports Section -->
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <div class="card">
                    <h3 class="text-lg font-semibold text-dark-text-primary mb-4">Top Customers</h3>
                    <div class="space-y-3">
                        <div v-for="i in 5" :key="i" class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="h-8 w-8 rounded-full bg-purple-accent flex items-center justify-center">
                                    <span class="text-xs text-white font-medium">{{ i }}</span>
                                </div>
                                <div>
                                    <div class="text-sm font-medium text-dark-text-primary">Customer {{ i }}</div>
                                    <div class="text-xs text-dark-text-secondary">customer{{ i }}@example.com</div>
                                </div>
                            </div>
                            <div class="text-sm font-semibold text-dark-text-primary">${{ (10000 * i).toLocaleString() }}</div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <h3 class="text-lg font-semibold text-dark-text-primary mb-4">Recent Activity</h3>
                    <div class="space-y-3">
                        <div v-for="i in 5" :key="i" class="flex items-center gap-3">
                            <div class="h-10 w-10 rounded-lg bg-dark-secondary flex items-center justify-center">
                                <span class="text-lg">📊</span>
                            </div>
                            <div class="flex-1">
                                <div class="text-sm font-medium text-dark-text-primary">Invoice {{ i }} processed</div>
                                <div class="text-xs text-dark-text-secondary">2 hours ago</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

