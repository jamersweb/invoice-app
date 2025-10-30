<script setup lang="ts">
defineProps<{
  title: string;
  value: string | number;
  delta?: string;
  icon?: string; // emoji or classname
  trend?: 'up' | 'down' | 'neutral';
  color?: 'blue' | 'green' | 'yellow' | 'red' | 'purple';
}>();

const colorClasses = {
  blue: 'from-blue-500 to-blue-600',
  green: 'from-green-500 to-green-600',
  yellow: 'from-yellow-500 to-yellow-600',
  red: 'from-red-500 to-red-600',
  purple: 'from-purple-500 to-purple-600'
};

const iconClasses = {
  blue: 'bg-blue-50 text-blue-600',
  green: 'bg-green-50 text-green-600',
  yellow: 'bg-yellow-50 text-yellow-600',
  red: 'bg-red-50 text-red-600',
  purple: 'bg-purple-50 text-purple-600'
};
</script>

<template>
  <div class="relative overflow-hidden rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200 hover:shadow-md transition-shadow duration-200">
    <!-- Background gradient -->
    <div class="absolute inset-0 bg-gradient-to-br opacity-5" :class="colorClasses[color || 'blue']"></div>

    <div class="relative">
      <div class="flex items-center justify-between">
        <div class="flex-1">
          <p class="text-sm font-medium text-gray-600">{{ title }}</p>
          <p class="mt-2 text-3xl font-bold text-gray-900">{{ value }}</p>

          <!-- Trend indicator -->
          <div v-if="delta" class="mt-3 flex items-center text-sm">
            <svg
              v-if="trend === 'up'"
              class="h-4 w-4 text-green-500 mr-1"
              fill="currentColor"
              viewBox="0 0 20 20"
            >
              <path fill-rule="evenodd" d="M5.293 7.707a1 1 0 010-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 01-1.414 1.414L11 5.414V17a1 1 0 11-2 0V5.414L6.707 7.707a1 1 0 01-1.414 0z" clip-rule="evenodd" />
            </svg>
            <svg
              v-else-if="trend === 'down'"
              class="h-4 w-4 text-red-500 mr-1"
              fill="currentColor"
              viewBox="0 0 20 20"
            >
              <path fill-rule="evenodd" d="M14.707 12.293a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 111.414-1.414L9 14.586V3a1 1 0 012 0v11.586l2.293-2.293a1 1 0 011.414 0z" clip-rule="evenodd" />
            </svg>
            <svg
              v-else
              class="h-4 w-4 text-gray-500 mr-1"
              fill="currentColor"
              viewBox="0 0 20 20"
            >
              <path fill-rule="evenodd" d="M3 10a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd" />
            </svg>
            <span :class="{
              'text-green-600': trend === 'up',
              'text-red-600': trend === 'down',
              'text-gray-600': trend === 'neutral' || !trend
            }">{{ delta }}</span>
          </div>
        </div>

        <!-- Icon -->
        <div class="flex-shrink-0">
          <div class="flex h-12 w-12 items-center justify-center rounded-lg" :class="iconClasses[color || 'blue']">
            <span v-if="icon" class="text-xl">{{ icon }}</span>
            <span v-else class="text-xl">📊</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>


