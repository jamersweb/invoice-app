<script setup lang="ts">
import { computed } from 'vue';

const props = defineProps<{
  total: number;
  paid: number;
  partiallyPaid: number;
  overdue: number;
}>();

// Simple pie chart calculation for display
const paidPercentage = computed(() => {
  const total = props.total || 1;
  return Math.min((props.paid / total) * 100, 100);
});

const partiallyPaidPercentage = computed(() => {
  const total = props.total || 1;
  return Math.min((props.partiallyPaid / total) * 100, 100);
});

const overduePercentage = computed(() => {
  const total = props.total || 1;
  return Math.min((props.overdue / total) * 100, 100);
});

const circumference = 2 * Math.PI * 40; // radius is 40
const paidDashArray = computed(() => {
  const percentage = paidPercentage.value;
  const dashLength = (percentage / 100) * circumference;
  return `${dashLength} ${circumference}`;
});

const partiallyPaidDashArray = computed(() => {
  const percentage = partiallyPaidPercentage.value;
  const dashLength = (percentage / 100) * circumference;
  return `${dashLength} ${circumference}`;
});

const partiallyPaidDashOffset = computed(() => {
  return -((paidPercentage.value / 100) * circumference);
});

const overdueDashArray = computed(() => {
  const percentage = overduePercentage.value;
  const dashLength = (percentage / 100) * circumference;
  return `${dashLength} ${circumference}`;
});

const overdueDashOffset = computed(() => {
  return -(((paidPercentage.value + partiallyPaidPercentage.value) / 100) * circumference);
});
</script>

<template>
  <div
    class="relative overflow-hidden rounded-card rounded-xl border text-card-foreground shadow bg-slate-800/40 backdrop-blur-sm border-slate-700/50 p-8 group">
    <h3 class="mb-4 text-lg font-semibold text-dark-text-primary">Payment Statistics</h3>

    <div class="flex items-center justify-between">
      <!-- Pie Chart Visualization (simplified) -->
      <div class="relative h-32 w-32">
        <svg class="transform -rotate-90" viewBox="0 0 100 100">
          <!-- Paid (green) -->
          <circle cx="50" cy="50" r="40" fill="none" stroke="#10B981" stroke-width="20"
            :stroke-dasharray="paidDashArray" class="transition-all duration-500" />
          <!-- Partially Paid (yellow) -->
          <circle cx="50" cy="50" r="40" fill="none" stroke="#F59E0B" stroke-width="20"
            :stroke-dasharray="partiallyPaidDashArray" :stroke-dashoffset="partiallyPaidDashOffset"
            class="transition-all duration-500" />
          <!-- Overdue (red) -->
          <circle cx="50" cy="50" r="40" fill="none" stroke="#EF4444" stroke-width="20"
            :stroke-dasharray="overdueDashArray" :stroke-dashoffset="overdueDashOffset"
            class="transition-all duration-500" />
        </svg>
        <div class="absolute inset-0 flex items-center justify-center">
          <div class="text-center">
            <div class="text-2xl font-bold text-dark-text-primary">{{ total.toLocaleString() }}</div>
            <div class="text-xs text-dark-text-muted">Total</div>
          </div>
        </div>
      </div>

      <!-- Legend -->
      <div class="space-y-3">
        <div class="flex items-center gap-2">
          <div class="h-3 w-3 rounded-full bg-green-500"></div>
          <span class="text-sm text-dark-text-secondary">Paid</span>
        </div>
        <div class="flex items-center gap-2">
          <div class="h-3 w-3 rounded-full bg-yellow-500"></div>
          <span class="text-sm text-dark-text-secondary">Partially Paid</span>
        </div>
        <div class="flex items-center gap-2">
          <div class="h-3 w-3 rounded-full bg-red-500"></div>
          <span class="text-sm text-dark-text-secondary">Overdue</span>
        </div>
      </div>
    </div>

    <!-- Stats Footer -->
    <div class="mt-6 grid grid-cols-3 gap-4 pt-4 border-t border-dark-border">
      <div>
        <div class="text-xs text-dark-text-muted mb-1">Invoiced</div>
        <div class="text-lg font-semibold text-dark-text-primary">{{ total.toLocaleString() }}</div>
      </div>
      <div>
        <div class="text-xs text-dark-text-muted mb-1">Received</div>
        <div class="text-lg font-semibold text-dark-text-primary">{{ paid.toLocaleString() }}</div>
      </div>
      <div>
        <div class="text-xs text-dark-text-muted mb-1">Outstanding</div>
        <div class="text-lg font-semibold text-dark-text-primary">{{ (total - paid).toLocaleString() }}</div>
      </div>
    </div>
  </div>
</template>
