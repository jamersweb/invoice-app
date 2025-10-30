<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';

const supplier = ref<any>(null);
const loading = ref(true);

onMounted(async () => {
  try {
    const response = await fetch('/api/v1/supplier/profile');
    if (response.ok) {
      const data = await response.json();
      supplier.value = data.supplier;
    }
  } catch (error) {
    console.error('Failed to load supplier data:', error);
  } finally {
    loading.value = false;
  }
});

const getStatusBadge = (status: string) => {
  switch (status) {
    case 'pending':
      return 'bg-yellow-100 text-yellow-800';
    case 'under_review':
      return 'bg-blue-100 text-blue-800';
    case 'approved':
      return 'bg-green-100 text-green-800';
    case 'rejected':
      return 'bg-red-100 text-red-800';
    default:
      return 'bg-gray-100 text-gray-800';
  }
};

const getStatusText = (status: string) => {
  switch (status) {
    case 'pending':
      return 'Pending Review';
    case 'under_review':
      return 'Under Review';
    case 'approved':
      return 'Approved';
    case 'rejected':
      return 'Rejected';
    default:
      return 'Unknown';
  }
};
</script>

<template>
  <Head title="Application Submitted" />

  <AuthenticatedLayout>
    <template #header>
      <div class="flex items-center justify-between">
        <div>
          <h2 class="text-2xl font-bold text-gray-900">Application Submitted</h2>
          <p class="mt-1 text-sm text-gray-500">Your KYC/KYB application has been successfully submitted</p>
        </div>
      </div>
    </template>

    <div class="mx-auto max-w-4xl px-4 py-8 sm:px-6 lg:px-8">
      <!-- Success Message -->
      <div class="rounded-xl border border-green-200 bg-green-50 p-8 text-center">
        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-green-100">
          <svg class="h-8 w-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
          </svg>
        </div>
        <h3 class="mt-4 text-lg font-semibold text-green-900">Application Submitted Successfully!</h3>
        <p class="mt-2 text-sm text-green-700">
          Thank you for submitting your KYC/KYB application. Our team will review your information and documents.
        </p>
      </div>

      <!-- Application Status -->
      <div v-if="!loading && supplier" class="mt-8 rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
        <h3 class="text-lg font-semibold text-gray-900">Application Status</h3>
        <div class="mt-4 grid grid-cols-1 gap-6 sm:grid-cols-2">
          <div>
            <label class="block text-sm font-medium text-gray-500">Status</label>
            <div class="mt-1">
              <span
                :class="['inline-flex rounded-full px-2 py-1 text-xs font-medium', getStatusBadge(supplier.kyb_status)]"
              >
                {{ getStatusText(supplier.kyb_status) }}
              </span>
            </div>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-500">Company Name</label>
            <p class="mt-1 text-sm text-gray-900">{{ supplier.company_name || 'Not provided' }}</p>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-500">Submitted Date</label>
            <p class="mt-1 text-sm text-gray-900">{{ new Date(supplier.updated_at).toLocaleDateString() }}</p>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-500">Completion</label>
            <div class="mt-1">
              <div class="flex items-center">
                <div class="flex-1 rounded-full bg-gray-200">
                  <div
                    class="h-2 rounded-full bg-indigo-600 transition-all duration-300"
                    :style="{ width: `${supplier.completion_percentage || 0}%` }"
                  ></div>
                </div>
                <span class="ml-2 text-sm text-gray-600">{{ supplier.completion_percentage || 0 }}%</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Next Steps -->
      <div class="mt-8 rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
        <h3 class="text-lg font-semibold text-gray-900">What Happens Next?</h3>
        <div class="mt-4 space-y-4">
          <div class="flex items-start">
            <div class="flex-shrink-0">
              <div class="flex h-8 w-8 items-center justify-center rounded-full bg-blue-100">
                <span class="text-sm font-medium text-blue-600">1</span>
              </div>
            </div>
            <div class="ml-3">
              <h4 class="text-sm font-medium text-gray-900">Document Review</h4>
              <p class="mt-1 text-sm text-gray-500">
                Our compliance team will review your submitted documents and information.
              </p>
            </div>
          </div>

          <div class="flex items-start">
            <div class="flex-shrink-0">
              <div class="flex h-8 w-8 items-center justify-center rounded-full bg-gray-100">
                <span class="text-sm font-medium text-gray-600">2</span>
              </div>
            </div>
            <div class="ml-3">
              <h4 class="text-sm font-medium text-gray-900">Verification Process</h4>
              <p class="mt-1 text-sm text-gray-500">
                We may contact you for additional information or clarification if needed.
              </p>
            </div>
          </div>

          <div class="flex items-start">
            <div class="flex-shrink-0">
              <div class="flex h-8 w-8 items-center justify-center rounded-full bg-gray-100">
                <span class="text-sm font-medium text-gray-600">3</span>
              </div>
            </div>
            <div class="ml-3">
              <h4 class="text-sm font-medium text-gray-900">Approval Notification</h4>
              <p class="mt-1 text-sm text-gray-500">
                You'll receive an email notification once your application is approved or if additional information is required.
              </p>
            </div>
          </div>
        </div>
      </div>

      <!-- Contact Information -->
      <div class="mt-8 rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
        <h3 class="text-lg font-semibold text-gray-900">Need Help?</h3>
        <p class="mt-2 text-sm text-gray-500">
          If you have any questions about your application or need to make changes, please contact our support team.
        </p>
        <div class="mt-4 flex flex-col sm:flex-row sm:items-center sm:justify-between">
          <div class="flex items-center">
            <svg class="h-5 w-5 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
              <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z"></path>
              <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z"></path>
            </svg>
            <span class="ml-2 text-sm text-gray-600">support@invoiceapp.com</span>
          </div>
          <div class="mt-2 flex items-center sm:mt-0">
            <svg class="h-5 w-5 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
              <path d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z"></path>
            </svg>
            <span class="ml-2 text-sm text-gray-600">+1 (555) 123-4567</span>
          </div>
        </div>
      </div>

      <!-- Action Buttons -->
      <div class="mt-8 flex flex-col sm:flex-row sm:items-center sm:justify-between">
        <Link
          :href="route('dashboard')"
          class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500"
        >
          <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
          </svg>
          Back to Dashboard
        </Link>

        <Link
          :href="route('onboarding.kyc')"
          class="mt-3 inline-flex items-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 sm:mt-0"
        >
          <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
          </svg>
          Edit Application
        </Link>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
