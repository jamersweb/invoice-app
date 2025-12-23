<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ref, onMounted, computed } from 'vue';

interface Supplier {
  id: number;
  company_name: string;
  legal_name: string;
  kyb_status: string;
  grade: string | null;
  completion_percentage: number;
  kyb_approved_at: string | null;
  kyb_notes: string | null;
  contact_email: string;
  created_at: string;
  updated_at: string;
}

const supplier = ref<Supplier | null>(null);
const loading = ref(true);
const error = ref<string | null>(null);
const exporting = ref(false);

const statusConfig = {
  pending: {
    label: 'Pending Review',
    color: 'bg-yellow-500/20 text-yellow-500 border border-yellow-500/50',
    icon: '⏳',
    description: 'Your application is waiting to be reviewed by our compliance team.'
  },
  under_review: {
    label: 'Under Review',
    color: 'bg-blue-500/20 text-blue-400 border border-blue-500/50',
    icon: '🔍',
    description: 'Our team is currently reviewing your application and documents.'
  },
  approved: {
    label: 'Approved',
    color: 'bg-green-500/20 text-green-400 border border-green-500/50',
    icon: '✅',
    description: 'Congratulations! Your application has been approved.'
  },
  rejected: {
    label: 'Rejected',
    color: 'bg-red-500/20 text-red-400 border border-red-500/50',
    icon: '❌',
    description: 'Your application requires additional information or corrections.'
  }
};

const loadData = async () => {
  try {
    loading.value = true;

    // Load supplier profile
    const supplierResponse = await fetch('/api/v1/supplier/profile');
    if (supplierResponse.ok) {
      const supplierData = await supplierResponse.json();
      supplier.value = supplierData.supplier;
    }
  } catch (err) {
    error.value = 'Failed to load data';
    console.error('Error loading data:', err);
  } finally {
    loading.value = false;
  }
};

const getStatusInfo = (status: string) => {
  return statusConfig[status as keyof typeof statusConfig] || statusConfig.pending;
};

const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  });
};

const getCompletionColor = (percentage: number) => {
  if (percentage >= 100) return 'bg-green-500';
  if (percentage >= 75) return 'bg-blue-500';
  if (percentage >= 50) return 'bg-yellow-500';
  return 'bg-red-500';
};

const exportData = async (format: 'excel' | 'csv') => {
  if (!supplier.value) return;

  try {
    exporting.value = true;

    const response = await fetch(`/api/v1/supplier/export?format=${format}`, {
      method: 'GET',
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      }
    });

    if (response.ok) {
      const blob = await response.blob();
      const url = window.URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url;
      a.download = `kyc_export_${supplier.value.id}_${new Date().toISOString().split('T')[0]}.${format === 'excel' ? 'xlsx' : 'csv'}`;
      document.body.appendChild(a);
      a.click();
      window.URL.revokeObjectURL(url);
      document.body.removeChild(a);
    } else {
      throw new Error('Export failed');
    }
  } catch (err) {
    error.value = 'Failed to export data';
    console.error('Export error:', err);
  } finally {
    exporting.value = false;
  }
};

onMounted(loadData);
</script>

<template>

  <Head title="KYC/KYB Status" />

  <AuthenticatedLayout>
    <template #header>
      <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div>
          <h2 class="text-2xl font-bold text-dark-text-primary">KYC/KYB Status</h2>
          <p class="mt-1 text-sm text-dark-text-secondary">Track your application progress and document status</p>
        </div>
        <div class="flex items-center space-x-3">
          <!-- Export Dropdown -->
          <div class="relative">
            <button @click="exporting = !exporting" :disabled="exporting"
              class="inline-flex items-center rounded-lg border border-dark-border bg-dark-secondary px-4 py-2 text-sm font-medium text-dark-text-primary hover:bg-dark-tertiary focus:outline-none focus:ring-2 focus:ring-purple-accent disabled:opacity-50">
              <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                </path>
              </svg>
              Export Data
              <svg class="ml-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
              </svg>
            </button>

            <!-- Export Menu -->
            <div v-if="exporting"
              class="absolute right-0 mt-2 w-48 rounded-md bg-dark-tertiary py-1 shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none z-10 border border-dark-border">
              <button @click="exportData('excel')"
                class="flex w-full items-center px-4 py-2 text-sm text-dark-text-primary hover:bg-dark-secondary">
                <svg class="mr-3 h-4 w-4 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd"
                    d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z"
                    clip-rule="evenodd" />
                </svg>
                Export as Excel
              </button>
              <button @click="exportData('csv')"
                class="flex w-full items-center px-4 py-2 text-sm text-dark-text-primary hover:bg-dark-secondary">
                <svg class="mr-3 h-4 w-4 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd"
                    d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z"
                    clip-rule="evenodd" />
                </svg>
                Export as CSV
              </button>
            </div>
          </div>

          <Link :href="route('onboarding.kyc')"
            class="inline-flex items-center rounded-lg bg-purple-accent px-4 py-2 text-sm font-medium text-white hover:bg-purple-accent/90 focus:outline-none focus:ring-2 focus:ring-purple-accent">
            <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z">
              </path>
            </svg>
            Update Application
          </Link>
        </div>
      </div>
    </template>

    <div class="mx-auto max-w-6xl px-4 py-8 sm:px-6 lg:px-8">
      <!-- Loading State -->
      <div v-if="loading" class="flex items-center justify-center py-12">
        <div class="text-center">
          <div class="mx-auto h-8 w-8 animate-spin rounded-full border-4 border-dark-tertiary border-t-purple-accent"></div>
          <p class="mt-2 text-sm text-dark-text-secondary">Loading your application status...</p>
        </div>
      </div>

      <!-- Error State -->
      <div v-else-if="error" class="rounded-lg border border-red-500/20 bg-red-500/10 p-4">
        <div class="flex">
          <svg class="h-5 w-5 text-red-500" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd"
              d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
              clip-rule="evenodd" />
          </svg>
          <div class="ml-3">
            <h3 class="text-sm font-medium text-red-500">Error</h3>
            <p class="mt-1 text-sm text-red-400">{{ error }}</p>
          </div>
        </div>
      </div>

      <!-- Main Content -->
      <div v-else-if="supplier" class="space-y-8">
        <!-- Application Status Card -->
        <div class="rounded-xl border border-dark-border bg-dark-secondary p-6 shadow-sm">
          <div class="flex items-start justify-between">
            <div class="flex items-start space-x-4">
              <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-purple-accent/10 border border-purple-accent/20">
                <svg v-if="supplier.kyb_status === 'approved'" class="h-6 w-6 text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <svg v-else-if="supplier.kyb_status === 'under_review'" class="h-6 w-6 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
                <svg v-else-if="supplier.kyb_status === 'rejected'" class="h-6 w-6 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <svg v-else class="h-6 w-6 text-yellow-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
              </div>
              <div>
                <h3 class="text-lg font-semibold text-dark-text-primary">{{ supplier.company_name }}</h3>
                <p class="text-sm text-dark-text-secondary">{{ supplier.contact_email }}</p>
                <div class="mt-2">
                  <span
                    :class="['inline-flex rounded-full px-2 py-1 text-xs font-medium', getStatusInfo(supplier.kyb_status).color]">
                    {{ getStatusInfo(supplier.kyb_status).label }}
                  </span>
                  <span v-if="supplier.grade"
                    class="ml-2 inline-flex rounded-full bg-purple-500/20 px-2 py-1 text-xs font-medium text-purple-400 border border-purple-500/50">
                    Grade {{ supplier.grade }}
                  </span>
                </div>
              </div>
            </div>

            <div class="text-right">
              <div class="text-sm text-dark-text-muted">Application ID</div>
              <div class="text-lg font-semibold text-dark-text-primary">#{{ supplier.id }}</div>
            </div>
          </div>

          <!-- Status Description -->
          <div class="mt-4 rounded-lg bg-dark-tertiary p-4">
            <p class="text-sm text-dark-text-secondary">{{ getStatusInfo(supplier.kyb_status).description }}</p>
            <div v-if="supplier.kyb_notes" class="mt-2 text-red-400">
              <p class="text-sm font-medium">Rejection Reason / Notes:</p>
              <p class="text-sm">{{ supplier.kyb_notes }}</p>
            </div>
            
            <div v-if="supplier.kyb_status === 'rejected'" class="mt-4 pt-4 border-t border-dark-border">
              <Link :href="route('onboarding.kyc')" 
                class="inline-flex items-center rounded-lg bg-purple-accent px-4 py-2 text-sm font-medium text-white hover:bg-purple-accent/90 focus:outline-none focus:ring-2 focus:ring-purple-accent">
                <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                </svg>
                Edit & Resubmit Application
              </Link>
            </div>
          </div>

        </div>




      </div>
    </div>
  </AuthenticatedLayout>
</template>
