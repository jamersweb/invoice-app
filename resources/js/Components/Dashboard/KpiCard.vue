<script setup lang="ts">
defineProps<{
  title: string;
  value: string | number;
  delta?: string;
  icon?: string;
  trend?: 'up' | 'down' | 'neutral';
  color?: 'blue' | 'green' | 'yellow' | 'red' | 'purple';
}>();

const colorClasses = {
  blue: 'from-blue-500 to-blue-600',
  green: 'from-green-500 to-green-600',
  yellow: 'from-yellow-500 to-yellow-600',
  red: 'from-red-500 to-red-600',
  purple: 'from-purple-accent to-purple-600'
};

const iconBgClasses = {
  blue: 'bg-blue-500/20 text-blue-400',
  green: 'bg-green-500/20 text-green-400',
  yellow: 'bg-yellow-500/20 text-yellow-400',
  red: 'bg-red-500/20 text-red-400',
  purple: 'bg-purple-accent/20 text-purple-accent'
};
</script>

<template>
  <div class="relative overflow-hidden rounded-card card">
    <div class="relative">
      <div class="flex items-center justify-between">
        <div class="flex-1">
          <p class="text-sm font-medium text-dark-text-secondary">{{ title }}</p>
          <p class="mt-2 text-3xl font-bold text-dark-text-primary">{{ value }}</p>

          <!-- Trend indicator -->
          <div v-if="delta" class="mt-3 flex items-center gap-1.5 text-sm">
            <svg
              v-if="trend === 'up'"
              class="h-4 w-4 text-green-400"
              fill="currentColor"
              viewBox="0 0 20 20"
            >
              <path fill-rule="evenodd" d="M5.293 7.707a1 1 0 010-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 01-1.414 1.414L11 5.414V17a1 1 0 11-2 0V5.414L6.707 7.707a1 1 0 01-1.414 0z" clip-rule="evenodd" />
            </svg>
            <svg
              v-else-if="trend === 'down'"
              class="h-4 w-4 text-red-400"
              fill="currentColor"
              viewBox="0 0 20 20"
            >
              <path fill-rule="evenodd" d="M14.707 12.293a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 111.414-1.414L9 14.586V3a1 1 0 012 0v11.586l2.293-2.293a1 1 0 011.414 0z" clip-rule="evenodd" />
            </svg>
            <svg
              v-else
              class="h-4 w-4 text-dark-text-muted"
              fill="currentColor"
              viewBox="0 0 20 20"
            >
              <path fill-rule="evenodd" d="M3 10a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd" />
            </svg>
            <span :class="{
              'text-green-400': trend === 'up',
              'text-red-400': trend === 'down',
              'text-dark-text-muted': trend === 'neutral' || !trend
            }">{{ delta }}</span>
          </div>
        </div>

        <!-- Icon -->
        <div class="flex-shrink-0">
          <div class="flex h-12 w-12 items-center justify-center rounded-lg" :class="iconBgClasses[color || 'purple']">
            <span v-if="icon" class="text-2xl">{{ icon }}</span>
            <span v-else class="text-2xl">📊</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
