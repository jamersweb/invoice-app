import { defineStore } from 'pinia';

type Kpis = {
  totalFunded: number;
  totalRepaid: number;
  outstanding: number;
  overdue: number;
};

type SeriesItem = {
  date: string;
  funded: number;
  repaid: number;
};

type PaymentStats = {
  total: number;
  paid: number;
  partiallyPaid: number;
  overdue: number;
};

type OverviewItem = {
  title: string;
  value: number;
  icon: string;
  status: 'success' | 'warning' | 'error' | 'info';
};

export const useDashboardStore = defineStore('dashboard', {
  state: () => ({
    loading: false as boolean,
    kpis: null as Kpis | null,
    series: null as SeriesItem[] | null,
    paymentStats: null as PaymentStats | null,
    overview: null as OverviewItem[] | null,
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
        this.series = data.series || [];
      } catch (e: any) {
        this.error = e?.message || 'Failed to load';
      } finally {
        this.loading = false;
      }
    },
    async fetchPaymentStats() {
      try {
        const res = await fetch('/api/v1/dashboard/payment-stats', {
          headers: { 'Accept': 'application/json' },
          credentials: 'same-origin',
        });
        if (res.ok) {
          const data = await res.json();
          this.paymentStats = data;
        }
      } catch (e: any) {
        console.error('Failed to fetch payment stats:', e);
      }
    },
    async fetchOverview() {
      try {
        const res = await fetch('/api/v1/dashboard/overview', {
          headers: { 'Accept': 'application/json' },
          credentials: 'same-origin',
        });
        if (res.ok) {
          const data = await res.json();
          this.overview = data.items || [];
        }
      } catch (e: any) {
        console.error('Failed to fetch overview:', e);
      }
    },
    async refresh() {
      await Promise.all([
        this.fetchMetrics(),
        this.fetchPaymentStats(),
        this.fetchOverview(),
      ]);
    },
  },
});


