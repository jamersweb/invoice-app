<script setup lang="ts">
import GuestLayout from '@/Layouts/GuestLayout.vue';
import GradientButton from '@/Components/GradientButton.vue';
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
      return 'bg-yellow-500/20 text-yellow-400 border-yellow-500/30';
    case 'under_review':
      return 'bg-blue-500/20 text-blue-400 border-blue-500/30';
    case 'approved':
      return 'bg-green-500/20 text-green-400 border-green-500/30';
    case 'rejected':
      return 'bg-red-500/20 text-red-400 border-red-500/30';
    default:
      return 'bg-gray-500/20 text-gray-400 border-gray-500/30';
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

  <GuestLayout>
    <template #header>
      <div class="text-center mb-10">
        <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-full bg-green-500/10 border border-green-500/30 mb-6">
          <svg class="h-10 w-10 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
          </svg>
        </div>
        <h2 class="text-3xl font-bold text-dark-text-primary mb-2">Application Submitted!</h2>
        <p class="text-sm text-dark-text-secondary max-w-md mx-auto">
          Your KYC/KYB application has been successfully received. Our compliance team is now reviewing your information.
        </p>
      </div>
    </template>

    <div class="space-y-8">
      <!-- Status Card -->
      <div v-if="!loading && supplier" class="card p-6 bg-dark-secondary/50">
        <h3 class="text-xl font-semibold text-dark-text-primary mb-6">Application Overview</h3>
        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
          <div>
            <label class="block text-xs font-medium text-dark-text-muted uppercase tracking-wider mb-1">Status</label>
            <div class="mt-1">
              <span
                :class="['inline-flex rounded-lg px-3 py-1 text-sm font-medium border', getStatusBadge(supplier.kyb_status)]"
              >
                {{ getStatusText(supplier.kyb_status) }}
              </span>
            </div>
          </div>
          <div>
            <label class="block text-xs font-medium text-dark-text-muted uppercase tracking-wider mb-1">Company Name</label>
            <p class="mt-1 text-lg font-medium text-dark-text-primary">{{ supplier.company_name || 'Not provided' }}</p>
          </div>
          <div>
            <label class="block text-xs font-medium text-dark-text-muted uppercase tracking-wider mb-1">Submitted On</label>
            <p class="mt-1 text-sm text-dark-text-secondary">{{ new Date(supplier.updated_at).toLocaleDateString() }}</p>
          </div>
        </div>
      </div>

      <!-- Next Steps -->
      <div class="space-y-4">
        <h3 class="text-lg font-semibold text-dark-text-primary">What Happens Next?</h3>
        
        <div class="space-y-4">
          <div class="flex items-start">
            <div class="flex-shrink-0 mt-1">
              <div class="flex h-8 w-8 items-center justify-center rounded-full bg-purple-accent/10 border border-purple-accent/30 text-purple-accent font-bold text-sm">
                1
              </div>
            </div>
            <div class="ml-4">
              <h4 class="text-sm font-semibold text-dark-text-primary">Compliance Review</h4>
              <p class="text-xs text-dark-text-secondary mt-1">Our team verifies your legal documents and business structure details.</p>
            </div>
          </div>

          <div class="flex items-start">
            <div class="flex-shrink-0 mt-1">
              <div class="flex h-8 w-8 items-center justify-center rounded-full bg-dark-tertiary border border-dark-border text-dark-text-muted font-bold text-sm">
                2
              </div>
            </div>
            <div class="ml-4">
              <h4 class="text-sm font-semibold text-dark-text-primary">Verification Confirmation</h4>
              <p class="text-xs text-dark-text-secondary mt-1">We may reach out via email if we need any additional documentation or data.</p>
            </div>
          </div>

          <div class="flex items-start">
            <div class="flex-shrink-0 mt-1">
              <div class="flex h-8 w-8 items-center justify-center rounded-full bg-dark-tertiary border border-dark-border text-dark-text-muted font-bold text-sm">
                3
              </div>
            </div>
            <div class="ml-4">
              <h4 class="text-sm font-semibold text-dark-text-primary">Account Activation</h4>
              <p class="text-xs text-dark-text-secondary mt-1">Once approved, you'll gain full access to the supplier dashboard and invoicing features.</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Footer Actions -->
      <div class="flex flex-col sm:flex-row gap-4 pt-6 border-t border-dark-border">
        <Link
          :href="route('supplier.dashboard')"
          class="flex-1"
        >
          <button class="w-full btn-secondary h-12">
            Go to Dashboard
          </button>
        </Link>
        <Link
          :href="route('onboarding.kyc')"
          class="flex-1"
        >
          <GradientButton class="w-full h-12">
            Update Documents
          </GradientButton>
        </Link>
      </div>

      <div class="text-center">
        <p class="text-xs text-dark-text-muted">
          Need help? Contact us at <a href="mailto:support@invoiceapp.com" class="text-purple-accent hover:underline">support@invoiceapp.com</a>
        </p>
      </div>
    </div>
  </GuestLayout>
</template>
