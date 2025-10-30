<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';

const props = defineProps<{
  title?: string;
  series?: Array<{ date: string; funded: number; repaid: number }>;
}>();

const chartRef = ref<HTMLCanvasElement | null>(null);

const maxVal = computed(() => {
  if (!props.series || props.series.length === 0) return 0;
  return Math.max(...props.series.map(s => Math.max(s.funded, s.repaid)));
});

const chartData = computed(() => {
  if (!props.series || props.series.length === 0) return { funded: [], repaid: [], labels: [] };

  return {
    funded: props.series.map(s => s.funded),
    repaid: props.series.map(s => s.repaid),
    labels: props.series.map(s => new Date(s.date).toLocaleDateString('en-US', { month: 'short', day: 'numeric' }))
  };
});

onMounted(() => {
  if (chartRef.value && props.series && props.series.length > 0) {
    drawChart();
  }
});

function drawChart() {
  if (!chartRef.value) return;

  const canvas = chartRef.value;
  const ctx = canvas.getContext('2d');
  if (!ctx) return;

  const { funded, repaid, labels } = chartData.value;
  const maxValue = maxVal.value;

  // Set canvas size
  canvas.width = canvas.offsetWidth;
  canvas.height = canvas.offsetHeight;

  // Clear canvas
  ctx.clearRect(0, 0, canvas.width, canvas.height);

  // Chart dimensions
  const padding = 40;
  const chartWidth = canvas.width - (padding * 2);
  const chartHeight = canvas.height - (padding * 2);
  const barWidth = chartWidth / funded.length;

  // Draw bars
  funded.forEach((value, index) => {
    const x = padding + (index * barWidth) + (barWidth * 0.1);
    const barWidthActual = barWidth * 0.35;
    const height = maxValue > 0 ? (value / maxValue) * chartHeight : 0;
    const y = padding + chartHeight - height;

    // Funded bar
    ctx.fillStyle = '#4F46E5'; // indigo-600
    ctx.fillRect(x, y, barWidthActual, height);

    // Repaid bar
    const repaidValue = repaid[index];
    const repaidHeight = maxValue > 0 ? (repaidValue / maxValue) * chartHeight : 0;
    const repaidY = padding + chartHeight - repaidHeight;

    ctx.fillStyle = '#10B981'; // emerald-500
    ctx.fillRect(x + barWidthActual + 2, repaidY, barWidthActual, repaidHeight);
  });

  // Draw labels
  ctx.fillStyle = '#6B7280'; // gray-500
  ctx.font = '12px Inter, sans-serif';
  ctx.textAlign = 'center';

  labels.forEach((label, index) => {
    const x = padding + (index * barWidth) + (barWidth / 2);
    const y = canvas.height - 10;
    ctx.fillText(label, x, y);
  });
}
</script>

<template>
  <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
    <div class="mb-6 flex items-center justify-between">
      <h3 class="text-lg font-semibold text-gray-900">{{ title || 'Revenue Overview' }}</h3>
      <div class="flex items-center gap-4 text-sm text-gray-500">
        <div class="flex items-center gap-2">
          <div class="h-3 w-3 rounded-sm bg-indigo-500"></div>
          <span>Funded</span>
        </div>
        <div class="flex items-center gap-2">
          <div class="h-3 w-3 rounded-sm bg-emerald-500"></div>
          <span>Repaid</span>
        </div>
      </div>
    </div>

    <div class="h-64 w-full">
      <canvas
        ref="chartRef"
        class="h-full w-full"
        :width="400"
        :height="256"
      ></canvas>
    </div>

    <!-- Fallback for no data -->
    <div v-if="!series || series.length === 0" class="flex h-64 items-center justify-center text-gray-500">
      <div class="text-center">
        <div class="text-4xl mb-2">📊</div>
        <p>No data available</p>
      </div>
    </div>
  </div>
</template>


