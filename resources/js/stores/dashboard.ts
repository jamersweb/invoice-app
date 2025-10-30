import { defineStore } from 'pinia';

type Kpis = {
  totalFunded: number;
  totalRepaid: number;
  outstanding: number;
  overdue: number;
};

type Series = {
  fundedLast30: number;
  repaidLast30: number;
};

export const useDashboardStore = defineStore('dashboard', {
  state: () => ({
    loading: false as boolean,
    kpis: null as Kpis | null,
    series: null as Series | null,
    error: null as string | null,
  }),
  actions: {
    async fetchMetrics(query?: string) {
      if (this.loading) return;
      this.loading = true;
      this.error = null;
      try {
        const url = '/api/v1/dashboard/metrics' + (query ? ('?' + query) : '');
        const res = await fetch(url, {
          headers: { 'Accept': 'application/json' },
          credentials: 'same-origin',
        });
        if (!res.ok) throw new Error(`HTTP ${res.status}`);
        const data = await res.json();
        this.kpis = data.kpis;
        this.series = data.series;
      } catch (e: any) {
        this.error = e?.message || 'Failed to load';
      } finally {
        this.loading = false;
      }
    },
  },
});


