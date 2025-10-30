<script setup lang="ts">
defineProps<{
  items: Array<{ icon?: string; title: string; value: string | number; status?: 'success' | 'warning' | 'error' | 'info' }>;
  title?: string;
}>();

const statusColors = {
  success: 'bg-green-50 text-green-700',
  warning: 'bg-yellow-50 text-yellow-700',
  error: 'bg-red-50 text-red-700',
  info: 'bg-blue-50 text-blue-700'
};

const statusIcons = {
  success: '✅',
  warning: '⚠️',
  error: '❌',
  info: 'ℹ️'
};
</script>

<template>
  <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
    <div v-if="title" class="mb-4">
      <h3 class="text-lg font-semibold text-gray-900">{{ title }}</h3>
    </div>

    <ul role="list" class="divide-y divide-gray-100">
      <li v-for="(item, idx) in items" :key="idx" class="flex items-center justify-between py-4">
        <div class="flex items-center gap-3">
          <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-gray-50">
            <span v-if="item.icon" class="text-lg">{{ item.icon }}</span>
            <span v-else class="text-lg">📊</span>
          </div>
          <div>
            <span class="text-sm font-medium text-gray-900">{{ item.title }}</span>
            <div v-if="item.status" class="mt-1 flex items-center gap-1">
              <span class="text-xs">{{ statusIcons[item.status] }}</span>
              <span class="text-xs font-medium" :class="statusColors[item.status]">
                {{ item.status.charAt(0).toUpperCase() + item.status.slice(1) }}
              </span>
            </div>
          </div>
        </div>
        <div class="text-right">
          <span class="text-lg font-semibold text-gray-900">{{ item.value }}</span>
        </div>
      </li>
    </ul>
  </div>
</template>


