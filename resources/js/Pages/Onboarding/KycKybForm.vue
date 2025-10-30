<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, usePage } from '@inertiajs/vue3';
import { ref, computed, onMounted, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import DocumentUpload from '@/Components/Onboarding/DocumentUpload.vue';

// Form steps
const currentStep = ref(1);
const totalSteps = 4;

const steps = [
  { id: 1, title: 'Company Information', description: 'Basic company details' },
  { id: 2, title: 'Business Details', description: 'Industry and legal information' },
  { id: 3, title: 'Location & Contact', description: 'Address and contact information' },
  { id: 4, title: 'Document Upload', description: 'Required documents and verification' }
];

// Form data
const form = useForm({
  // Step 1: Company Information
  company_name: '',
  legal_name: '',
  tax_registration_number: '',
  website: '',

  // Step 2: Business Details
  business_type: '',
  industry: '',
  incorporation_date: '',

  // Step 3: Location & Contact
  country: '',
  state_province: '',
  city: '',
  address: '',
  postal_code: '',
  contact_email: '',
  contact_phone: '',

  // Step 4: Documents
  documents: [] as File[],

  // Additional KYC data
  kyc_data: {} as Record<string, any>
});

// Auto-save functionality
const autoSaveTimeout = ref<NodeJS.Timeout | null>(null);
const lastSaved = ref<Date | null>(null);

const autoSave = () => {
  if (autoSaveTimeout.value) {
    clearTimeout(autoSaveTimeout.value);
  }

  autoSaveTimeout.value = setTimeout(() => {
    form.post('/api/v1/supplier/kyc/save', {
      preserveState: true,
      preserveScroll: true,
      onSuccess: () => {
        lastSaved.value = new Date();
      }
    });
  }, 2000);
};

// Watch for form changes to trigger auto-save
watch(() => form.data(), () => {
  autoSave();
}, { deep: true });

// Form validation
const validationErrors = ref<Record<string, string>>({});

const validateStep = (step: number): boolean => {
  validationErrors.value = {};

  switch (step) {
    case 1:
      if (!form.company_name) validationErrors.value.company_name = 'Company name is required';
      if (!form.legal_name) validationErrors.value.legal_name = 'Legal name is required';
      if (!form.tax_registration_number) validationErrors.value.tax_registration_number = 'Tax registration number is required';
      break;
    case 2:
      if (!form.business_type) validationErrors.value.business_type = 'Business type is required';
      if (!form.industry) validationErrors.value.industry = 'Industry is required';
      if (!form.incorporation_date) validationErrors.value.incorporation_date = 'Incorporation date is required';
      break;
    case 3:
      if (!form.country) validationErrors.value.country = 'Country is required';
      if (!form.city) validationErrors.value.city = 'City is required';
      if (!form.contact_email) validationErrors.value.contact_email = 'Contact email is required';
      if (!form.contact_phone) validationErrors.value.contact_phone = 'Contact phone is required';
      break;
    case 4:
      if (form.documents.length === 0) validationErrors.value.documents = 'At least one document is required';
      break;
  }

  return Object.keys(validationErrors.value).length === 0;
};

// Navigation
const nextStep = () => {
  if (validateStep(currentStep.value)) {
    if (currentStep.value < totalSteps) {
      currentStep.value++;
    }
  }
};

const prevStep = () => {
  if (currentStep.value > 1) {
    currentStep.value--;
  }
};

const goToStep = (step: number) => {
  if (step <= currentStep.value || validateStep(currentStep.value)) {
    currentStep.value = step;
  }
};

// Document upload
const documentTypes = ref([
  { id: 1, name: 'Business License', required: true, description: 'Official business license document' },
  { id: 2, name: 'Tax Registration Certificate', required: true, description: 'Tax registration certificate' },
  { id: 3, name: 'Articles of Incorporation', required: false, description: 'Articles of incorporation or similar' },
  { id: 4, name: 'Bank Statement', required: false, description: 'Recent bank statement (last 3 months)' },
  { id: 5, name: 'Financial Statements', required: false, description: 'Annual financial statements' }
]);

const uploadedDocuments = ref<Record<number, File[]>>({});
const documentNotes = ref<Record<number, string>>({});

const handleFileUpload = (event: Event, documentTypeId: number) => {
  const target = event.target as HTMLInputElement;
  if (target.files) {
    uploadedDocuments.value[documentTypeId] = Array.from(target.files);
    form.documents = Object.values(uploadedDocuments.value).flat();
  }
};

const handleDocumentUpload = (documentTypeId: number, files: File[]) => {
  uploadedDocuments.value[documentTypeId] = files;
  form.documents = Object.values(uploadedDocuments.value).flat();
};

const removeDocument = (documentTypeId: number, index: number) => {
  const arr = uploadedDocuments.value[documentTypeId] || [];
  arr.splice(index, 1);
  uploadedDocuments.value[documentTypeId] = arr;
  form.documents = Object.values(uploadedDocuments.value).flat();
};

// Progress calculation
const progress = computed(() => {
  return Math.round((currentStep.value / totalSteps) * 100);
});

// Business types and industries
const businessTypes = [
  'Corporation', 'LLC', 'Partnership', 'Sole Proprietorship', 'Non-Profit', 'Government Entity'
];

const industries = [
  'Technology', 'Manufacturing', 'Retail', 'Healthcare', 'Finance', 'Real Estate',
  'Construction', 'Transportation', 'Education', 'Food & Beverage', 'Other'
];

// Submit form
const submitForm = () => {
  if (validateStep(4)) {
    // merge notes into kyc_data
    form.kyc_data = { ...(form.kyc_data || {}), document_notes } as any;
    form.post('/api/v1/supplier/kyc/submit', {
      onSuccess: () => {
        // Redirect to success page or dashboard
        window.location.href = '/onboarding/success';
      }
    });
  }
};

// Load existing data
onMounted(() => {
  // Load existing supplier data if available
  fetch('/api/v1/supplier/profile')
    .then(res => res.json())
    .then(data => {
      if (data.supplier) {
        Object.assign(form, data.supplier);
      }
    })
    .catch(() => {
      // Handle error silently
    });

  // Load KYB checklist and mark required/expiry hints
  fetch('/api/v1/me/kyb/checklist')
    .then(res => res.json())
    .then(payload => {
      const rules: Array<{ document_type_id:number, is_required:boolean, expires_in_days:number|null }>= payload?.data || [];
      const ruleById = new Map<number, { is_required:boolean, expires_in_days:number|null }>();
      rules.forEach(r => ruleById.set(r.document_type_id, { is_required: !!r.is_required, expires_in_days: r.expires_in_days ?? null }));
      documentTypes.value = documentTypes.value.map(dt => {
        const rule = ruleById.get(dt.id);
        if (!rule) return dt;
        const exp = rule.expires_in_days ? ` — expires every ${rule.expires_in_days} days` : '';
        return { ...dt, required: rule.is_required, description: (dt.description || '') + exp };
      });
    })
    .catch(() => {});
});
</script>

<template>
  <Head title="KYC/KYB Onboarding" />

  <AuthenticatedLayout>
    <template #header>
      <div class="flex items-center justify-between">
        <div>
          <h2 class="text-2xl font-bold text-gray-900">Complete Your Profile</h2>
          <p class="mt-1 text-sm text-gray-500">Please provide the required information to complete your KYC/KYB verification</p>
        </div>
        <div class="text-right">
          <div class="text-sm text-gray-500">Progress</div>
          <div class="text-lg font-semibold text-indigo-600">{{ progress }}%</div>
        </div>
      </div>
    </template>

    <div class="mx-auto max-w-6xl px-4 py-8 sm:px-6 lg:px-8">
      <!-- Progress Steps -->
      <div class="mb-8">
        <div class="flex items-center justify-between">
          <div v-for="(step, index) in steps" :key="step.id" class="flex items-center">
            <div class="flex items-center">
              <button
                @click="goToStep(step.id)"
                :class="[
                  'flex h-10 w-10 items-center justify-center rounded-full border-2 text-sm font-medium transition-colors',
                  currentStep >= step.id
                    ? 'border-indigo-600 bg-indigo-600 text-white'
                    : 'border-gray-300 bg-white text-gray-500 hover:border-gray-400'
                ]"
              >
                {{ step.id }}
              </button>
              <div class="ml-3 hidden sm:block">
                <div class="text-sm font-medium text-gray-900">{{ step.title }}</div>
                <div class="text-xs text-gray-500">{{ step.description }}</div>
              </div>
            </div>
            <div v-if="index < steps.length - 1" class="ml-8 h-0.5 w-16 bg-gray-300"></div>
          </div>
        </div>
      </div>

      <!-- Auto-save indicator -->
      <div v-if="lastSaved" class="mb-4 flex items-center justify-end text-sm text-gray-500">
        <svg class="mr-1 h-4 w-4 text-green-500" fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
        </svg>
        Last saved: {{ lastSaved.toLocaleTimeString() }}
      </div>

      <!-- Form Content -->
      <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <!-- Main card -->
        <div class="rounded-xl border border-gray-200 bg-white p-8 shadow-sm lg:col-span-2">
        <!-- Step 1: Company Information -->
        <div v-if="currentStep === 1" class="space-y-6">
          <div>
            <h3 class="text-lg font-semibold text-gray-900">Company Information</h3>
            <p class="mt-1 text-sm text-gray-500">Please provide your company's basic information</p>
          </div>

          <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
            <div>
              <label class="block text-sm font-medium text-gray-700">Company Name *</label>
              <input
                v-model="form.company_name"
                type="text"
                class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                :class="{ 'border-red-500': validationErrors.company_name }"
                placeholder="Enter your company name"
              />
              <p v-if="validationErrors.company_name" class="mt-1 text-sm text-red-600">{{ validationErrors.company_name }}</p>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700">Legal Name *</label>
              <input
                v-model="form.legal_name"
                type="text"
                class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                :class="{ 'border-red-500': validationErrors.legal_name }"
                placeholder="Enter legal company name"
              />
              <p v-if="validationErrors.legal_name" class="mt-1 text-sm text-red-600">{{ validationErrors.legal_name }}</p>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700">Tax Registration Number *</label>
              <input
                v-model="form.tax_registration_number"
                type="text"
                class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                :class="{ 'border-red-500': validationErrors.tax_registration_number }"
                placeholder="Enter tax registration number"
              />
              <p v-if="validationErrors.tax_registration_number" class="mt-1 text-sm text-red-600">{{ validationErrors.tax_registration_number }}</p>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700">Website</label>
              <input
                v-model="form.website"
                type="url"
                class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                placeholder="https://yourcompany.com"
              />
            </div>
          </div>
        </div>

        <!-- Step 2: Business Details -->
        <div v-if="currentStep === 2" class="space-y-6">
          <div>
            <h3 class="text-lg font-semibold text-gray-900">Business Details</h3>
            <p class="mt-1 text-sm text-gray-500">Tell us about your business structure and industry</p>
          </div>

          <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
            <div>
              <label class="block text-sm font-medium text-gray-700">Business Type *</label>
              <select
                v-model="form.business_type"
                class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                :class="{ 'border-red-500': validationErrors.business_type }"
              >
                <option value="">Select business type</option>
                <option v-for="type in businessTypes" :key="type" :value="type">{{ type }}</option>
              </select>
              <p v-if="validationErrors.business_type" class="mt-1 text-sm text-red-600">{{ validationErrors.business_type }}</p>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700">Industry *</label>
              <select
                v-model="form.industry"
                class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                :class="{ 'border-red-500': validationErrors.industry }"
              >
                <option value="">Select industry</option>
                <option v-for="industry in industries" :key="industry" :value="industry">{{ industry }}</option>
              </select>
              <p v-if="validationErrors.industry" class="mt-1 text-sm text-red-600">{{ validationErrors.industry }}</p>
            </div>

            <div class="sm:col-span-2">
              <label class="block text-sm font-medium text-gray-700">Incorporation Date *</label>
              <input
                v-model="form.incorporation_date"
                type="date"
                class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                :class="{ 'border-red-500': validationErrors.incorporation_date }"
              />
              <p v-if="validationErrors.incorporation_date" class="mt-1 text-sm text-red-600">{{ validationErrors.incorporation_date }}</p>
            </div>
          </div>
        </div>

        <!-- Step 3: Location & Contact -->
        <div v-if="currentStep === 3" class="space-y-6">
          <div>
            <h3 class="text-lg font-semibold text-gray-900">Location & Contact Information</h3>
            <p class="mt-1 text-sm text-gray-500">Provide your business address and contact details</p>
          </div>

          <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
            <div>
              <label class="block text-sm font-medium text-gray-700">Country *</label>
              <select
                v-model="form.country"
                class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                :class="{ 'border-red-500': validationErrors.country }"
              >
                <option value="">Select country</option>
                <option value="US">United States</option>
                <option value="CA">Canada</option>
                <option value="UK">United Kingdom</option>
                <option value="DE">Germany</option>
                <option value="FR">France</option>
                <option value="AE">United Arab Emirates</option>
                <option value="SA">Saudi Arabia</option>
                <option value="EG">Egypt</option>
                <option value="JO">Jordan</option>
                <option value="LB">Lebanon</option>
                <option value="KW">Kuwait</option>
                <option value="QA">Qatar</option>
                <option value="BH">Bahrain</option>
                <option value="OM">Oman</option>
              </select>
              <p v-if="validationErrors.country" class="mt-1 text-sm text-red-600">{{ validationErrors.country }}</p>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700">State/Province</label>
              <input
                v-model="form.state_province"
                type="text"
                class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                placeholder="Enter state or province"
              />
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700">City *</label>
              <input
                v-model="form.city"
                type="text"
                class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                :class="{ 'border-red-500': validationErrors.city }"
                placeholder="Enter city"
              />
              <p v-if="validationErrors.city" class="mt-1 text-sm text-red-600">{{ validationErrors.city }}</p>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700">Postal Code</label>
              <input
                v-model="form.postal_code"
                type="text"
                class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                placeholder="Enter postal code"
              />
            </div>

            <div class="sm:col-span-2">
              <label class="block text-sm font-medium text-gray-700">Address</label>
              <textarea
                v-model="form.address"
                rows="3"
                class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                placeholder="Enter complete address"
              ></textarea>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700">Contact Email *</label>
              <input
                v-model="form.contact_email"
                type="email"
                class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                :class="{ 'border-red-500': validationErrors.contact_email }"
                placeholder="contact@yourcompany.com"
              />
              <p v-if="validationErrors.contact_email" class="mt-1 text-sm text-red-600">{{ validationErrors.contact_email }}</p>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700">Contact Phone *</label>
              <input
                v-model="form.contact_phone"
                type="tel"
                class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                :class="{ 'border-red-500': validationErrors.contact_phone }"
                placeholder="+1 (555) 123-4567"
              />
              <p v-if="validationErrors.contact_phone" class="mt-1 text-sm text-red-600">{{ validationErrors.contact_phone }}</p>
            </div>
          </div>
        </div>

        <!-- Step 4: Document Upload -->
        <div v-if="currentStep === 4" class="space-y-6">
          <div>
            <h3 class="text-lg font-semibold text-gray-900">Document Upload</h3>
            <p class="mt-1 text-sm text-gray-500">Upload the required documents for verification</p>
          </div>

          <div class="space-y-6">
            <DocumentUpload
              v-for="docType in documentTypes"
              :key="docType.id"
              :document-type="docType"
              :uploaded-files="uploadedDocuments[docType.id] || []"
              :max-files="3"
              :accepted-types="['.pdf', '.jpg', '.jpeg', '.png']"
              @files-uploaded="(files) => handleDocumentUpload(docType.id, files)"
              @file-removed="(index) => removeDocument(docType.id, index)"
              @version-note="(note) => (documentNotes[docType.id] = note)"
            />
          </div>

          <div v-if="validationErrors.documents" class="rounded-lg bg-red-50 p-4">
            <p class="text-sm text-red-600">{{ validationErrors.documents }}</p>
          </div>
        </div>

        <!-- Navigation Buttons -->
        <div class="mt-8 flex items-center justify-between">
          <button
            v-if="currentStep > 1"
            @click="prevStep"
            type="button"
            class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500"
          >
            Previous
          </button>
          <div v-else></div>

          <div class="flex items-center space-x-3">
            <button
              v-if="currentStep < totalSteps"
              @click="nextStep"
              type="button"
              class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500"
            >
              Next
            </button>
            <button
              v-else
              @click="submitForm"
              type="button"
              :disabled="form.processing"
              class="rounded-lg bg-green-600 px-6 py-2 text-sm font-medium text-white hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 disabled:opacity-50"
            >
              {{ form.processing ? 'Submitting...' : 'Submit Application' }}
            </button>
          </div>
        </div>
        </div>

        <!-- Checklist side panel -->
        <aside class="rounded-xl border border-gray-200 bg-white p-6 h-fit">
          <div class="mb-3 text-base font-semibold text-gray-900">Requirements</div>
          <ul class="space-y-3">
            <li v-for="dt in documentTypes" :key="dt.id" class="flex items-start justify-between">
              <div>
                <div class="text-sm font-medium text-gray-900 flex items-center gap-2">
                  <span>{{ dt.name }}</span>
                  <span v-if="dt.required" class="rounded bg-red-50 px-2 py-0.5 text-xs font-medium text-red-700">Required</span>
                </div>
                <div v-if="dt.description" class="text-xs text-gray-500 max-w-xs">{{ dt.description }}</div>
              </div>
              <div class="text-xs font-medium" :class="(uploadedDocuments[dt.id]?.length||0) > 0 ? 'text-green-700' : 'text-gray-500'">
                {{ (uploadedDocuments[dt.id]?.length||0) > 0 ? 'Attached' : 'Pending' }}
              </div>
            </li>
          </ul>
        </aside>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
