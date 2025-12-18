<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import DocumentUpload from '@/Components/Onboarding/DocumentUpload.vue';
import GradientButton from '@/Components/GradientButton.vue';
import DarkInput from '@/Components/DarkInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';

const props = defineProps<{
  onSuccess?: () => void;
}>();

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
const autoSaveTimeout = ref<ReturnType<typeof setTimeout> | null>(null);
const lastSaved = ref<Date | null>(null);

const autoSave = () => {
  if (autoSaveTimeout.value) {
    clearTimeout(autoSaveTimeout.value);
  }

  autoSaveTimeout.value = setTimeout(() => {
    window.axios.post('/api/v1/supplier/kyc/save', form.data())
      .then(() => {
        lastSaved.value = new Date();
      })
      .catch(() => {
        // Silently fail for auto-save
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
      const missingRequired = documentTypes.value.filter(dt => 
        dt.required && (!uploadedDocuments.value[dt.id] || uploadedDocuments.value[dt.id].length === 0)
      );
      if (missingRequired.length > 0) {
        validationErrors.value.documents = `The following documents are required: ${missingRequired.map(d => d.name).join(', ')}`;
      }
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
    form.kyc_data = { ...(form.kyc_data || {}), document_notes: documentNotes.value } as any;
    form.post('/api/v1/supplier/kyc/submit', {
      onSuccess: () => {
        if (props.onSuccess) {
          props.onSuccess();
        } else {
          window.location.href = '/onboarding/success';
        }
      }
    });
  }
};

onMounted(() => {
  // Load existing supplier data if available
  fetch('/api/v1/supplier/profile')
    .then(res => res.json())
    .then(data => {
      if (data.supplier) {
        Object.assign(form, data.supplier);
      }
    })
    .catch(() => {});

  // Load KYB checklist
  fetch('/api/v1/me/kyb/checklist')
    .then(res => res.json())
    .then(payload => {
      const rules = payload?.data || [];
      const ruleById = new Map();
      rules.forEach((r: any) => ruleById.set(r.document_type_id, { is_required: !!r.is_required, expires_in_days: r.expires_in_days ?? null }));
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
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between sticky top-0 bg-dark-primary z-10 pb-4 border-b border-dark-border">
      <div>
        <h1 class="text-2xl font-bold text-dark-text-primary">Registration & KYB</h1>
        <p class="mt-1 text-sm text-dark-text-secondary">Please provide the required information to complete your KYC/KYB verification</p>
      </div>
      <div class="text-right">
        <div class="text-sm text-dark-text-secondary">Progress</div>
        <div class="text-lg font-semibold text-purple-accent">{{ progress }}%</div>
      </div>
    </div>

    <!-- Progress Steps -->
    <div class="card p-4">
      <div class="flex items-center justify-between">
        <div v-for="(step, index) in steps" :key="step.id" class="flex items-center flex-1">
          <div class="flex items-center">
            <button
              @click="goToStep(step.id)"
              :class="[
                'flex h-8 w-8 items-center justify-center rounded-full border-2 text-xs font-medium transition-colors',
                currentStep >= step.id
                  ? 'border-purple-accent bg-purple-accent text-white'
                  : 'border-dark-border bg-dark-secondary text-dark-text-secondary hover:border-dark-border/70'
              ]"
            >
              {{ step.id }}
            </button>
            <div class="ml-3 hidden md:block">
              <div class="text-xs font-medium text-dark-text-primary">{{ step.title }}</div>
            </div>
          </div>
          <div v-if="index < steps.length - 1" class="ml-4 h-0.5 flex-1 bg-dark-border"></div>
        </div>
      </div>
    </div>

    <!-- Form Content -->
    <div class="grid grid-cols-1 gap-6">
      <div class="card p-6">
        <!-- Step 1: Company Information -->
        <div v-if="currentStep === 1" class="space-y-6">
          <div>
            <h3 class="text-lg font-semibold text-dark-text-primary">Company Information</h3>
          </div>

          <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
            <div>
              <InputLabel for="company_name" value="Company Name *" class="text-dark-text-secondary mb-2" />
              <DarkInput
                id="company_name"
                v-model="form.company_name"
                placeholder="Enter your company name"
                :class="{ 'border-red-500': validationErrors.company_name }"
              />
              <InputError :message="validationErrors.company_name" />
            </div>

            <div>
              <InputLabel for="legal_name" value="Legal Name *" class="text-dark-text-secondary mb-2" />
              <DarkInput
                id="legal_name"
                v-model="form.legal_name"
                placeholder="Enter legal company name"
                :class="{ 'border-red-500': validationErrors.legal_name }"
              />
              <InputError :message="validationErrors.legal_name" />
            </div>

            <div>
              <InputLabel for="tax_registration_number" value="Tax Registration Number *" class="text-dark-text-secondary mb-2" />
              <DarkInput
                id="tax_registration_number"
                v-model="form.tax_registration_number"
                placeholder="Enter tax registration number"
                :class="{ 'border-red-500': validationErrors.tax_registration_number }"
              />
              <InputError :message="validationErrors.tax_registration_number" />
            </div>

            <div>
              <InputLabel for="website" value="Website" class="text-dark-text-secondary mb-2" />
              <DarkInput
                id="website"
                v-model="form.website"
                type="url"
                placeholder="https://yourcompany.com"
              />
            </div>
          </div>
        </div>

        <!-- Step 2: Business Details -->
        <div v-if="currentStep === 2" class="space-y-6">
          <div>
            <h3 class="text-lg font-semibold text-dark-text-primary">Business Details</h3>
          </div>

          <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
            <div>
              <InputLabel for="business_type" value="Business Type *" class="text-dark-text-secondary mb-2" />
              <select
                id="business_type"
                v-model="form.business_type"
                class="input-dark w-full bg-dark-secondary border-dark-border text-white rounded-lg p-2.5 outline-none focus:border-purple-accent"
                :class="{ 'border-red-500': validationErrors.business_type }"
              >
                <option value="">Select business type</option>
                <option v-for="type in businessTypes" :key="type" :value="type">{{ type }}</option>
              </select>
              <InputError :message="validationErrors.business_type" />
            </div>

            <div>
              <InputLabel for="industry" value="Industry *" class="text-dark-text-secondary mb-2" />
              <select
                id="industry"
                v-model="form.industry"
                class="input-dark w-full bg-dark-secondary border-dark-border text-white rounded-lg p-2.5 outline-none focus:border-purple-accent"
                :class="{ 'border-red-500': validationErrors.industry }"
              >
                <option value="">Select industry</option>
                <option v-for="ind in industries" :key="ind" :value="ind">{{ ind }}</option>
              </select>
              <InputError :message="validationErrors.industry" />
            </div>

            <div class="sm:col-span-2">
              <InputLabel for="incorporation_date" value="Incorporation Date *" class="text-dark-text-secondary mb-2" />
              <DarkInput
                id="incorporation_date"
                v-model="form.incorporation_date"
                type="date"
                :class="{ 'border-red-500': validationErrors.incorporation_date }"
              />
              <InputError :message="validationErrors.incorporation_date" />
            </div>
          </div>
        </div>

        <!-- Step 3: Location & Contact -->
        <div v-if="currentStep === 3" class="space-y-6">
          <div>
            <h3 class="text-lg font-semibold text-dark-text-primary">Location & Contact</h3>
          </div>

          <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
            <div>
              <InputLabel for="country" value="Country *" class="text-dark-text-secondary mb-2" />
              <select
                id="country"
                v-model="form.country"
                class="input-dark w-full bg-dark-secondary border-dark-border text-white rounded-lg p-2.5 outline-none focus:border-purple-accent"
                :class="{ 'border-red-500': validationErrors.country }"
              >
                <option value="">Select country</option>
                <option value="US">United States</option>
                <option value="CA">Canada</option>
                <option value="UK">United Kingdom</option>
                <option value="AE">United Arab Emirates</option>
                <option value="SA">Saudi Arabia</option>
                <option value="PK">Pakistan</option>
              </select>
              <InputError :message="validationErrors.country" />
            </div>

            <div>
              <InputLabel for="city" value="City *" class="text-dark-text-secondary mb-2" />
              <DarkInput
                id="city"
                v-model="form.city"
                placeholder="Enter city"
                :class="{ 'border-red-500': validationErrors.city }"
              />
              <InputError :message="validationErrors.city" />
            </div>

            <div class="sm:col-span-2">
              <InputLabel for="contact_email" value="Contact Email *" class="text-dark-text-secondary mb-2" />
              <DarkInput
                id="contact_email"
                v-model="form.contact_email"
                type="email"
                placeholder="contact@yourcompany.com"
                :class="{ 'border-red-500': validationErrors.contact_email }"
              />
              <InputError :message="validationErrors.contact_email" />
            </div>

            <div class="sm:col-span-2">
              <InputLabel for="contact_phone" value="Contact Phone *" class="text-dark-text-secondary mb-2" />
              <DarkInput
                id="contact_phone"
                v-model="form.contact_phone"
                type="tel"
                placeholder="+1 (555) 123-4567"
                :class="{ 'border-red-500': validationErrors.contact_phone }"
              />
              <InputError :message="validationErrors.contact_phone" />
            </div>
          </div>
        </div>

        <!-- Step 4: Document Upload -->
        <div v-if="currentStep === 4" class="space-y-6">
          <div>
            <h3 class="text-lg font-semibold text-dark-text-primary">Document Upload</h3>
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

          <div v-if="validationErrors.documents" class="rounded-lg bg-red-500/20 border border-red-500/30 p-4">
            <p class="text-sm text-red-400">{{ validationErrors.documents }}</p>
          </div>
        </div>

        <!-- Navigation Buttons -->
        <div class="mt-8 flex items-center justify-between border-t border-dark-border pt-6">
          <button
            v-if="currentStep > 1"
            @click="prevStep"
            type="button"
            class="btn-secondary"
          >
            Previous
          </button>
          <div v-else></div>

          <div class="flex items-center space-x-3">
            <GradientButton
              v-if="currentStep < totalSteps"
              @click="nextStep"
              type="button"
            >
              Next
            </GradientButton>
            <GradientButton
              v-else
              @click="submitForm"
              type="button"
              :disabled="form.processing"
              class="disabled:opacity-50"
            >
              {{ form.processing ? 'Submitting...' : 'Submit Application' }}
            </GradientButton>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
  width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-track {
  background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
  background: #3f3f46;
  border-radius: 4px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
  background: #52525b;
}
</style>
