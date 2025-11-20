<template>
    <Head title="Analytics" />
    <AuthenticatedLayout>
        <div class="p-6 space-y-6">
            <div class="mb-6">
                <h1 class="text-3xl font-bold text-white">Analytics</h1>
                <p class="text-slate-400 mt-1">Portfolio health and performance insights</p>
            </div>

            <!-- Portfolio Health Score -->
            <div class="bg-slate-800/30 border border-slate-700 rounded-lg p-6 mb-6">
                <h2 class="text-xl font-semibold text-white mb-4">Portfolio Health Score</h2>
                <div class="flex items-center gap-6">
                    <div class="relative w-32 h-32">
                        <svg class="transform -rotate-90 w-32 h-32">
                            <circle
                                cx="64"
                                cy="64"
                                r="56"
                                stroke="currentColor"
                                stroke-width="8"
                                fill="none"
                                class="text-slate-700"
                            />
                            <circle
                                cx="64"
                                cy="64"
                                r="56"
                                stroke="currentColor"
                                stroke-width="8"
                                fill="none"
                                :stroke-dasharray="circumference"
                                :stroke-dashoffset="offset"
                                :class="getHealthColor(healthScore)"
                                class="transition-all duration-500"
                            />
                        </svg>
                        <div class="absolute inset-0 flex items-center justify-center">
                            <span class="text-3xl font-bold text-white">{{ healthScore }}</span>
                        </div>
                    </div>
                    <div class="flex-1">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <p class="text-slate-400 text-sm mb-1">Utilization Rate</p>
                                <p class="text-white text-xl font-semibold">{{ metrics.utilizationRate }}%</p>
                            </div>
                            <div>
                                <p class="text-slate-400 text-sm mb-1">Profitability Rate</p>
                                <p class="text-white text-xl font-semibold">{{ metrics.profitabilityRate }}%</p>
                            </div>
                            <div>
                                <p class="text-slate-400 text-sm mb-1">Total Fund</p>
                                <p class="text-white text-xl font-semibold">{{ formatCurrency(metrics.totalFund) }}</p>
                            </div>
                            <div>
                                <p class="text-slate-400 text-sm mb-1">Available Balance</p>
                                <p class="text-white text-xl font-semibold">{{ formatCurrency(metrics.availableBalance) }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Alerts Dashboard -->
            <div class="bg-slate-800/30 border border-slate-700 rounded-lg p-6">
                <h2 class="text-xl font-semibold text-white mb-4">Alerts Dashboard</h2>
                <div v-if="alerts.length === 0" class="text-center text-slate-400 py-8">
                    No alerts at this time. Portfolio is healthy!
                </div>
                <div v-else class="space-y-3">
                    <div
                        v-for="(alert, index) in alerts"
                        :key="index"
                        :class="getAlertClass(alert.type)"
                        class="p-4 rounded-lg border"
                    >
                        <div class="flex items-start gap-3">
                            <div class="text-xl">
                                <span v-if="alert.type === 'danger'">⚠️</span>
                                <span v-else-if="alert.type === 'warning'">⚡</span>
                                <span v-else>ℹ️</span>
                            </div>
                            <div class="flex-1">
                                <h3 class="font-semibold mb-1">{{ alert.title }}</h3>
                                <p class="text-sm opacity-90">{{ alert.message }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { computed } from 'vue';

interface Alert {
    type: 'danger' | 'warning' | 'info';
    title: string;
    message: string;
}

interface Props {
    healthScore: number;
    alerts: Alert[];
    metrics: {
        utilizationRate: number;
        profitabilityRate: number;
        totalFund: number;
        availableBalance: number;
        lockedAmount: number;
    };
}

const props = defineProps<Props>();

const circumference = 2 * Math.PI * 56;
const offset = computed(() => {
    return circumference - (props.healthScore / 100) * circumference;
});

const getHealthColor = (score: number) => {
    if (score >= 80) return 'text-green-500';
    if (score >= 60) return 'text-yellow-500';
    return 'text-red-500';
};

const getAlertClass = (type: string) => {
    const classes: Record<string, string> = {
        danger: 'bg-red-500/10 border-red-500/50 text-red-400',
        warning: 'bg-yellow-500/10 border-yellow-500/50 text-yellow-400',
        info: 'bg-blue-500/10 border-blue-500/50 text-blue-400',
    };
    return classes[type] || classes.info;
};

const formatCurrency = (value: number) => {
    return new Intl.NumberFormat('en-AE', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(value);
};
</script>

