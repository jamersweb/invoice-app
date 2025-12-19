<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref, onMounted, computed } from 'vue';

const loading = ref(true);
const kpis = ref({ invoices: 0, funded: 0, outstanding: 0 });
const recentInvoices = ref<Array<{ id: number, invoice_number?: string, amount: number, status: string }>>([]);
const activeOffers = ref<Array<any>>([]);
const repaymentSchedule = ref<Array<{ id: number, invoice_id: number, amount: number, due_date: string, status: string }>>([]);
const supplier = ref<{ kyb_status?: string } | null>(null);
const fundingBlocked = computed(() => (supplier.value?.kyb_status ?? 'pending') !== 'approved');

onMounted(async () => {
  try {
    // Placeholder: reuse dashboard metrics if needed, or call a customer-specific endpoint later
    const res = await fetch('/api/v1/dashboard/metrics', { credentials: 'include' });
    if (res.ok) {
      const data = await res.json();
      kpis.value = {
        invoices: data?.kpis?.totalFunded ? 4 : 0,
        funded: data?.kpis?.totalFunded ?? 0,
        outstanding: data?.kpis?.outstanding ?? 0,
      };
    }

    const [offersRes, invoicesRes, scheduleRes, profileRes] = await Promise.all([
      fetch('/api/v1/me/offers/active', { credentials: 'include' }),
      fetch('/api/v1/me/invoices/recent', { credentials: 'include' }),
      fetch('/api/v1/me/repayments/schedule', { credentials: 'include' }),
      fetch('/api/v1/supplier/profile', { credentials: 'include' }),
    ]);
    if (offersRes.ok) {
      const json = await offersRes.json();
      activeOffers.value = json?.data ?? [];
    }
    if (invoicesRes.ok) {
      const json = await invoicesRes.json();
      recentInvoices.value = json?.data ?? [];
    }
    if (scheduleRes.ok) {
      const json = await scheduleRes.json();
      repaymentSchedule.value = json?.data ?? [];
    }
    if (profileRes.ok) {
      const json = await profileRes.json();
      supplier.value = json?.supplier ?? null;
    }
  } finally {
    loading.value = false;
  }
});
</script>

<template>

  <Head title="Customer Dashboard" />
  <AuthenticatedLayout>
    <template #header>
      <div class="flex items-center justify-between">
        <div>
          <h2 class="text-2xl font-bold text-gray-900">Customer Dashboard</h2>
          <p class="mt-1 text-sm text-gray-500">Overview of your activity and invoices</p>
        </div>
      </div>
    </template>

    <div class="mx-auto max-w-7xl">
      <div v-if="loading" class="flex items-center justify-center py-16">
        <div class="mx-auto h-8 w-8 animate-spin rounded-full border-4 border-gray-300 border-t-indigo-600" />
      </div>
      <div v-else class="space-y-8">
        <!-- Funding blocked banner -->
        <div v-if="fundingBlocked" class="rounded-lg border border-yellow-300 bg-yellow-50 p-4 text-sm text-yellow-800">
          Funding actions are blocked until your KYB is approved. Complete onboarding in <a href="/onboarding/kyc"
            class="underline">KYC/KYB</a>.
        </div>
        <!-- KPIs -->
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
          <div class="rounded-xl border border-gray-200  p-6">
            <div class="text-sm text-gray-500">Invoices</div>
            <div class="mt-2 text-2xl font-semibold text-gray-900">{{ kpis.invoices }}</div>
          </div>
          <div class="rounded-xl border border-gray-200  p-6">
            <div class="text-sm text-gray-500">Total Funded</div>
            <div class="mt-2 text-2xl font-semibold text-gray-900">{{ kpis.funded.toLocaleString() }}</div>
          </div>
          <div class="rounded-xl border border-gray-200  p-6">
            <div class="text-sm text-gray-500">Outstanding</div>
            <div class="mt-2 text-2xl font-semibold text-gray-900">{{ kpis.outstanding.toLocaleString() }}</div>
          </div>
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
          <!-- Recent Invoices -->
          <div class="rounded-xl border border-gray-200  p-6 lg:col-span-1">
            <div class="mb-4 flex items-center justify-between">
              <h3 class="text-base font-semibold text-gray-900">Recent Invoices</h3>
            </div>
            <div class="divide-y divide-gray-100">
              <div v-for="inv in recentInvoices" :key="inv.id" class="flex items-center justify-between py-3">
                <div>
                  <div class="text-sm font-medium text-gray-900">{{ inv.invoice_number || ('INV-' + inv.id) }}</div>
                  <div class="text-xs text-gray-500">{{ inv.status }}</div>
                </div>
                <div class="text-sm text-gray-900">{{ Number(inv.amount).toLocaleString() }}</div>
              </div>
              <div v-if="recentInvoices.length === 0" class="py-6 text-center text-sm text-gray-500">No invoices</div>
            </div>
          </div>

          <!-- Active Offers -->
          <div class="rounded-xl border border-gray-200  p-6 lg:col-span-1">
            <div class="mb-4 flex items-center justify-between">
              <h3 class="text-base font-semibold text-gray-900">Active Offers</h3>
            </div>
            <div class="divide-y divide-gray-100">
              <div v-for="off in activeOffers" :key="off.id" class="flex items-center justify-between py-3">
                <div>
                  <div class="text-sm font-medium text-gray-900">Offer #{{ off.id }}</div>
                  <div class="text-xs text-gray-500">Expires {{ new Date(off.expires_at).toLocaleString() }}</div>
                </div>
                <div class="text-sm text-gray-900">{{ Number(off.net_amount ?? off.amount).toLocaleString() }}</div>
              </div>
              <div v-if="activeOffers.length === 0" class="py-6 text-center text-sm text-gray-500">No active offers
              </div>
            </div>
          </div>

          <!-- Repayment Schedule -->
          <div class="rounded-xl border border-gray-200  p-6 lg:col-span-1">
            <div class="mb-4 flex items-center justify-between">
              <h3 class="text-base font-semibold text-gray-900">Repayment Schedule</h3>
            </div>
            <div class="divide-y divide-gray-100">
              <div v-for="er in repaymentSchedule" :key="er.id" class="flex items-center justify-between py-3">
                <div>
                  <div class="text-sm font-medium text-gray-900">Invoice #{{ er.invoice_id }}</div>
                  <div class="text-xs text-gray-500">Due {{ new Date(er.due_date).toLocaleDateString() }} — {{ er.status
                    }}
                  </div>
                </div>
                <div class="text-sm text-gray-900">{{ Number(er.amount).toLocaleString() }}</div>
              </div>
              <div v-if="repaymentSchedule.length === 0" class="py-6 text-center text-sm text-gray-500">No upcoming
                repayments</div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
