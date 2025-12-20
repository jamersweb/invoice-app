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
  created_at: string;
  updated_at: string;
}

interface Document {
  id: number;
  document_type_id: number;
  status: string;
  file_path: string;
  created_at: string;
  reviewed_at: string | null;
}

const supplier = ref<Supplier | null>(null);
const documents = ref<Document[]>([]);
const loading = ref(true);
const error = ref<string | null>(null);
const exporting = ref(false);

const statusConfig = {
  pending: {
    label: 'Pending Review',
    color: 'bg-yellow-100 text-yellow-800',
    icon: '⏳',
    description: 'Your application is waiting to be reviewed by our compliance team.'
  },
  under_review: {
    label: 'Under Review',
    color: 'bg-blue-100 text-blue-800',
    icon: '🔍',
    description: 'Our team is currently reviewing your application and documents.'
  },
  approved: {
    label: 'Approved',
    color: 'bg-green-100 text-green-800',
    icon: '✅',
    description: 'Congratulations! Your application has been approved.'
  },
  rejected: {
    label: 'Rejected',
    color: 'bg-red-100 text-red-800',
    icon: '❌',
    description: 'Your application requires additional information or corrections.'
  }
};

const documentStatusConfig = {
  pending: { label: 'Pending', color: 'bg-gray-100 text-gray-800' },
  pending_review: { label: 'Under Review', color: 'bg-yellow-100 text-yellow-800' },
  approved: { label: 'Approved', color: 'bg-green-100 text-green-800' },
  rejected: { label: 'Rejected', color: 'bg-red-100 text-red-800' }
};

const documentTypes = {
  1: 'Business License',
  2: 'Tax Registration Certificate',
  3: 'Articles of Incorporation',
  4: 'Bank Statement',
  5: 'Financial Statements'
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

    // Load documents
    const documentsResponse = await fetch('/api/v1/supplier/documents');
    if (documentsResponse.ok) {
      const documentsData = await documentsResponse.json();
      documents.value = documentsData.documents || [];
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

const getDocumentStatusInfo = (status: string) => {
  return documentStatusConfig[status as keyof typeof documentStatusConfig] || documentStatusConfig.pending;
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
</script>

<template>

  <Head title="KYC/KYB Status" />

  <AuthenticatedLayout>
    <template #header>
      <div class="flex items-center justify-between">
        <div>
          <h2 class="text-2xl font-bold text-gray-900">KYC/KYB Status</h2>
          <p class="mt-1 text-sm text-gray-500">Track your application progress and document status</p>
        </div>
        <div class="flex items-center space-x-3">
          <!-- Export Dropdown -->
          <div class="relative">
            <button @click="exporting = !exporting" :disabled="exporting"
              class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 disabled:opacity-50">
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
              class="absolute right-0 mt-2 w-48 rounded-md bg-white py-1 shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none z-10">
              <button @click="exportData('excel')"
                class="flex w-full items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                <svg class="mr-3 h-4 w-4 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd"
                    d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z"
                    clip-rule="evenodd" />
                </svg>
                Export as Excel
              </button>
              <button @click="exportData('csv')"
                class="flex w-full items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                <svg class="mr-3 h-4 w-4 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd"
                    d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z"
                    clip-rule="evenodd" />
                </svg>
                Export as CSV
              </button>
            </div>
          </div>

          <Link :href="route('onboarding.kyc')"
            class="inline-flex items-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500">
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
          <div class="mx-auto h-8 w-8 animate-spin rounded-full border-4 border-gray-300 border-t-indigo-600"></div>
          <p class="mt-2 text-sm text-gray-500">Loading your application status...</p>
        </div>
      </div>

      <!-- Error State -->
      <div v-else-if="error" class="rounded-lg border border-red-200 bg-red-50 p-4">
        <div class="flex">
          <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd"
              d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
              clip-rule="evenodd" />
          </svg>
          <div class="ml-3">
            <h3 class="text-sm font-medium text-red-800">Error</h3>
            <p class="mt-1 text-sm text-red-700">{{ error }}</p>
          </div>
        </div>
      </div>

      <!-- Main Content -->
      <div v-else-if="supplier" class="space-y-8">
        <!-- Application Status Card -->
        <div class="rounded-xl border border-gray-200  p-6 shadow-sm">
          <div class="flex items-start justify-between">
            <div class="flex items-start space-x-4">
              <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-indigo-100">
                <span class="text-2xl">{{ getStatusInfo(supplier.kyb_status).icon }}</span>
              </div>
              <div>
                <h3 class="text-lg font-semibold text-gray-900">{{ supplier.company_name }}</h3>
                <p class="text-sm text-gray-500">{{ supplier.legal_name }}</p>
                <div class="mt-2">
                  <span
                    :class="['inline-flex rounded-full px-2 py-1 text-xs font-medium', getStatusInfo(supplier.kyb_status).color]">
                    {{ getStatusInfo(supplier.kyb_status).label }}
                  </span>
                  <span v-if="supplier.grade"
                    class="ml-2 inline-flex rounded-full bg-purple-100 px-2 py-1 text-xs font-medium text-purple-800">
                    Grade {{ supplier.grade }}
                  </span>
                </div>
              </div>
            </div>

            <div class="text-right">
              <div class="text-sm text-gray-500">Application ID</div>
              <div class="text-lg font-semibold text-gray-900">#{{ supplier.id }}</div>
            </div>
          </div>

          <!-- Status Description -->
          <div class="mt-4 rounded-lg bg-gray-50 p-4">
            <p class="text-sm text-gray-700">{{ getStatusInfo(supplier.kyb_status).description }}</p>
            <div v-if="supplier.kyb_notes" class="mt-2">
              <p class="text-sm font-medium">Additional Notes:</p>
              <p class="text-sm text-gray-600">{{ supplier.kyb_notes }}</p>
            </div>
          </div>

          <!-- Progress Bar -->
          <div class="mt-6">
            <div class="flex items-center justify-between text-sm">
              <span class="font-medium text-gray-700">Profile Completion</span>
              <span class="text-gray-500">{{ supplier.completion_percentage }}%</span>
            </div>
            <div class="mt-2 h-2 rounded-full bg-gray-200">
              <div
                :class="['h-2 rounded-full transition-all duration-300', getCompletionColor(supplier.completion_percentage)]"
                :style="{ width: `${supplier.completion_percentage}%` }"></div>
            </div>
          </div>
        </div>

        <!-- Timeline -->
        <div class="rounded-xl border border-gray-200  p-6 shadow-sm">
          <h3 class="text-lg font-semibold text-gray-900 mb-4">Application Timeline</h3>
          <div class="space-y-4">
            <!-- Application Submitted -->
            <div class="flex items-start space-x-3">
              <div class="flex h-8 w-8 items-center justify-center rounded-full bg-green-100">
                <svg class="h-4 w-4 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd"
                    d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                    clip-rule="evenodd" />
                </svg>
              </div>
              <div>
                <p class="text-sm font-medium">Application Submitted</p>
                <p class="text-sm text-gray-500">{{ formatDate(supplier.created_at) }}</p>
              </div>
            </div>

            <!-- Under Review -->
            <div class="flex items-start space-x-3">
              <div
                :class="['flex h-8 w-8 items-center justify-center rounded-full', supplier.kyb_status === 'under_review' || supplier.kyb_status === 'approved' ? 'bg-blue-100' : 'bg-gray-100']">
                <svg
                  :class="['h-4 w-4', supplier.kyb_status === 'under_review' || supplier.kyb_status === 'approved' ? 'text-blue-600' : 'text-gray-400']"
                  fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd"
                    d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z"
                    clip-rule="evenodd" />
                </svg>
              </div>
              <div>
                <p class="text-sm font-medium">Under Review</p>
                <p class="text-sm text-gray-500">
                  {{ supplier.kyb_status === 'under_review' ? 'Currently being reviewed' : 'Pending review' }}
                </p>
              </div>
            </div>

            <!-- Approved -->
            <div v-if="supplier.kyb_status === 'approved'" class="flex items-start space-x-3">
              <div class="flex h-8 w-8 items-center justify-center rounded-full bg-green-100">
                <svg class="h-4 w-4 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd"
                    d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                    clip-rule="evenodd" />
                </svg>
              </div>
              <div>
                <p class="text-sm font-medium">Application Approved</p>
                <p class="text-sm text-gray-500">{{ formatDate(supplier.kyb_approved_at!) }}</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Documents Status -->
        <div class="rounded-xl border border-gray-200  p-6 shadow-sm">
          <h3 class="text-lg font-semibold text-gray-900 mb-4">Document Status</h3>

          <div v-if="documents.length === 0" class="text-center py-8">
            <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-gray-100">
              <svg class="h-6 w-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                </path>
              </svg>
            </div>
            <h3 class="mt-2 text-sm font-medium">No documents uploaded</h3>
            <p class="mt-1 text-sm text-gray-500">Upload your required documents to complete your application.</p>
          </div>

          <div v-else class="space-y-3">
            <div v-for="document in documents" :key="document.id"
              class="flex items-center justify-between rounded-xl border text-card-foreground shadow bg-slate-800/40 border-slate-700/50 p-4 hover:bg-slate-800/60 transition-all">
              <div class="flex items-center space-x-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-gray-100">
                  <span class="text-lg">📄</span>
                </div>
                <div>
                  <p class="text-sm font-medium">
                    {{ documentTypes[document.document_type_id as keyof typeof documentTypes] || 'Document' }}
                  </p>
                  <p class="text-sm text-gray-500">Uploaded {{ formatDate(document.created_at) }}</p>
                </div>
              </div>

              <div class="flex items-center space-x-3">
                <span
                  :class="['inline-flex rounded-full px-2 py-1 text-xs font-medium', getDocumentStatusInfo(document.status).color]">
                  {{ getDocumentStatusInfo(document.status).label }}
                </span>

                <div v-if="document.reviewed_at" class="text-sm text-gray-500">
                  Reviewed {{ formatDate(document.reviewed_at) }}
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Next Steps -->
        <div v-if="supplier.kyb_status !== 'approved'" class="rounded-xl border border-gray-200  p-6 shadow-sm">
          <h3 class="text-lg font-semibold text-gray-900 mb-4">Next Steps</h3>
          <div class="space-y-3">
            <div v-if="supplier.kyb_status === 'pending'" class="flex items-start space-x-3">
              <div class="flex h-6 w-6 items-center justify-center rounded-full bg-blue-100">
                <span class="text-xs font-medium text-blue-600">1</span>
              </div>
              <div>
                <p class="text-sm font-medium">Complete your profile</p>
                <p class="text-sm text-gray-500">Ensure all required information is filled out completely.</p>
              </div>
            </div>

            <div v-if="supplier.kyb_status === 'pending'" class="flex items-start space-x-3">
              <div class="flex h-6 w-6 items-center justify-center rounded-full bg-blue-100">
                <span class="text-xs font-medium text-blue-600">2</span>
              </div>
              <div>
                <p class="text-sm font-medium">Upload required documents</p>
                <p class="text-sm text-gray-500">Submit all necessary verification documents.</p>
              </div>
            </div>

            <div class="flex items-start space-x-3">
              <div class="flex h-6 w-6 items-center justify-center rounded-full bg-blue-100">
                <span class="text-xs font-medium text-blue-600">{{ supplier.kyb_status === 'pending' ? '3' : '1'
                }}</span>
              </div>
              <div>
                <p class="text-sm font-medium">Wait for review</p>
                <p class="text-sm text-gray-500">Our compliance team will review your application within 2-3 business
                  days.
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
