<script setup lang="ts">
defineProps<{
  items: Array<{ icon?: string; title: string; value: string | number; status?: 'success' | 'warning' | 'error' | 'info' }>;
  title?: string;
}>();

const statusColors = {
  success: 'bg-green-500/20 text-green-400 border-green-500/30',
  warning: 'bg-yellow-500/20 text-yellow-400 border-yellow-500/30',
  error: 'bg-red-500/20 text-red-400 border-red-500/30',
  info: 'bg-blue-500/20 text-blue-400 border-blue-500/30'
};
</script>

<template>
  <div class="card">
    <div v-if="title" class="mb-4">
      <h3 class="text-lg font-semibold text-dark-text-primary">{{ title }}</h3>
    </div>

    <ul role="list" class="divide-y divide-dark-border">
      <li v-for="(item, idx) in items" :key="idx" class="flex items-center justify-between py-4 first:pt-0 last:pb-0">
        <div class="flex items-center gap-3">
          <div class="flex h-11 w-11 items-center justify-center rounded-lg bg-dark-secondary">
            <span v-if="item.icon" class="text-xl">{{ item.icon }}</span>
            <span v-else class="text-xl">📊</span>
          </div>
          <div>
            <span class="text-sm font-medium text-dark-text-primary">{{ item.title }}</span>
            <div v-if="item.status" class="mt-1">
              <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium border" :class="statusColors[item.status]">
                {{ item.status.charAt(0).toUpperCase() + item.status.slice(1) }}
              </span>
            </div>
          </div>
        </div>
        <div class="text-right">
          <span class="text-lg font-semibold text-dark-text-primary">{{ item.value }}</span>
        </div>
      </li>
    </ul>
  </div>
</template>
