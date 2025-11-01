<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue';

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

watch(() => props.series, () => {
  if (chartRef.value && props.series && props.series.length > 0) {
    drawChart();
  }
}, { deep: true });

onMounted(() => {
  if (chartRef.value && props.series && props.series.length > 0) {
    setTimeout(() => drawChart(), 100);
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
  const padding = 50;
  const chartWidth = canvas.width - (padding * 2);
  const chartHeight = canvas.height - (padding * 2);
  const barWidth = chartWidth / funded.length;

  // Draw grid lines
  ctx.strokeStyle = '#3A3A3A';
  ctx.lineWidth = 1;
  for (let i = 0; i <= 4; i++) {
    const y = padding + (chartHeight / 4) * i;
    ctx.beginPath();
    ctx.moveTo(padding, y);
    ctx.lineTo(padding + chartWidth, y);
    ctx.stroke();
  }

  // Draw bars
  funded.forEach((value, index) => {
    const x = padding + (index * barWidth) + (barWidth * 0.1);
    const barWidthActual = barWidth * 0.35;
    const height = maxValue > 0 ? (value / maxValue) * chartHeight : 0;
    const y = padding + chartHeight - height;

    // Funded bar (purple gradient)
    const gradient = ctx.createLinearGradient(x, y, x, padding + chartHeight);
    gradient.addColorStop(0, '#9333EA');
    gradient.addColorStop(1, '#7C3AED');
    ctx.fillStyle = gradient;
    ctx.fillRect(x, y, barWidthActual, height);

    // Repaid bar (green)
    const repaidValue = repaid[index];
    const repaidHeight = maxValue > 0 ? (repaidValue / maxValue) * chartHeight : 0;
    const repaidY = padding + chartHeight - repaidHeight;

    const repaidGradient = ctx.createLinearGradient(x + barWidthActual + 2, repaidY, x + barWidthActual + 2, padding + chartHeight);
    repaidGradient.addColorStop(0, '#10B981');
    repaidGradient.addColorStop(1, '#059669');
    ctx.fillStyle = repaidGradient;
    ctx.fillRect(x + barWidthActual + 2, repaidY, barWidthActual, repaidHeight);
  });

  // Draw labels
  ctx.fillStyle = '#B0B0B0';
  ctx.font = '12px Figtree, sans-serif';
  ctx.textAlign = 'center';

  labels.forEach((label, index) => {
    const x = padding + (index * barWidth) + (barWidth / 2);
    const y = canvas.height - 15;
    ctx.fillText(label, x, y);
  });
}
</script>

<template>
  <div class="card">
    <div class="mb-6 flex items-center justify-between">
      <h3 class="text-lg font-semibold text-dark-text-primary">{{ title || 'Revenue Overview' }}</h3>
      <div class="flex items-center gap-4 text-sm">
        <div class="flex items-center gap-2">
          <div class="h-3 w-3 rounded-sm bg-purple-accent"></div>
          <span class="text-dark-text-secondary">Funded</span>
        </div>
        <div class="flex items-center gap-2">
          <div class="h-3 w-3 rounded-sm bg-green-500"></div>
          <span class="text-dark-text-secondary">Repaid</span>
        </div>
      </div>
    </div>

    <div class="h-64 w-full">
      <canvas
        ref="chartRef"
        class="h-full w-full"
        :width="600"
        :height="256"
      ></canvas>
    </div>

    <!-- Fallback for no data -->
    <div v-if="!series || series.length === 0" class="flex h-64 items-center justify-center text-dark-text-muted">
      <div class="text-center">
        <div class="text-4xl mb-2">📊</div>
        <p>No data available</p>
      </div>
    </div>
  </div>
</template>
