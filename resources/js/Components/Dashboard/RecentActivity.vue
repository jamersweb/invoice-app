<script setup lang="ts">
import { computed } from 'vue';

type ActivityItem = {
  id: number;
  type: 'funding' | 'repayment' | 'invoice' | 'approval';
  title: string;
  description: string;
  amount?: number;
  date: string;
  status?: 'success' | 'warning' | 'error' | 'info';
};

const props = defineProps<{
  items?: ActivityItem[];
  loading?: boolean;
}>();

const typeIcons = {
  funding: '💸',
  repayment: '🏦',
  invoice: '🧾',
  approval: '✅',
};

const typeColors = {
  funding: 'bg-purple-accent/20 text-purple-accent',
  repayment: 'bg-green-500/20 text-green-400',
  invoice: 'bg-blue-500/20 text-blue-400',
  approval: 'bg-yellow-500/20 text-yellow-400',
};

const formatDate = (date: string) => {
  const d = new Date(date);
  const now = new Date();
  const diffMs = now.getTime() - d.getTime();
  const diffMins = Math.floor(diffMs / 60000);
  const diffHours = Math.floor(diffMs / 3600000);
  const diffDays = Math.floor(diffMs / 86400000);

  if (diffMins < 1) return 'Just now';
  if (diffMins < 60) return `${diffMins}m ago`;
  if (diffHours < 24) return `${diffHours}h ago`;
  if (diffDays < 7) return `${diffDays}d ago`;
  return d.toLocaleDateString();
};

const formatAmount = (amount?: number) => {
  if (!amount) return '';
  return new Intl.NumberFormat(undefined, { style: 'currency', currency: 'USD', maximumFractionDigits: 0 }).format(amount);
};
</script>

<template>
  <div class="card">
    <div class="mb-4 flex items-center justify-between">
      <h3 class="text-lg font-semibold text-dark-text-primary">Recent Activity</h3>
      <button
        v-if="!loading"
        @click="$emit('refresh')"
        class="text-sm text-dark-text-secondary hover:text-dark-text-primary transition-colors"
      >
        Refresh
      </button>
    </div>

    <div v-if="loading" class="space-y-4 py-4">
      <div v-for="i in 5" :key="i" class="flex items-center gap-3 animate-pulse">
        <div class="h-10 w-10 rounded-lg bg-dark-secondary"></div>
        <div class="flex-1 space-y-2">
          <div class="h-4 w-3/4 rounded bg-dark-secondary"></div>
          <div class="h-3 w-1/2 rounded bg-dark-secondary"></div>
        </div>
      </div>
    </div>

    <div v-else-if="items && items.length > 0" class="space-y-4">
      <div
        v-for="item in items"
        :key="item.id"
        class="flex items-start gap-3 p-3 rounded-lg hover:bg-dark-secondary/50 transition-colors"
      >
        <div
          class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-lg"
          :class="typeColors[item.type]"
        >
          <span class="text-lg">{{ typeIcons[item.type] }}</span>
        </div>
        <div class="flex-1 min-w-0">
          <div class="flex items-start justify-between gap-2">
            <div class="flex-1 min-w-0">
              <p class="text-sm font-medium text-dark-text-primary truncate">{{ item.title }}</p>
              <p class="mt-1 text-xs text-dark-text-secondary line-clamp-2">{{ item.description }}</p>
            </div>
            <div v-if="item.amount" class="flex-shrink-0 text-right">
              <p class="text-sm font-semibold text-dark-text-primary">{{ formatAmount(item.amount) }}</p>
            </div>
          </div>
          <div class="mt-2 flex items-center justify-between">
            <span class="text-xs text-dark-text-muted">{{ formatDate(item.date) }}</span>
            <span
              v-if="item.status"
              class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium border"
              :class="{
                'bg-green-500/20 text-green-400 border-green-500/30': item.status === 'success',
                'bg-yellow-500/20 text-yellow-400 border-yellow-500/30': item.status === 'warning',
                'bg-red-500/20 text-red-400 border-red-500/30': item.status === 'error',
                'bg-blue-500/20 text-blue-400 border-blue-500/30': item.status === 'info',
              }"
            >
              {{ item.status.charAt(0).toUpperCase() + item.status.slice(1) }}
            </span>
          </div>
        </div>
      </div>
    </div>

    <div v-else class="py-12 text-center">
      <div class="text-4xl mb-2">📋</div>
      <p class="text-dark-text-secondary">No recent activity</p>
    </div>
  </div>
</template>

