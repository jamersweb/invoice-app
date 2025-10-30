<script setup lang="ts">
import { ref, computed, watch } from 'vue';

interface Props {
  value: string | number | null;
  rules: Array<(value: any) => string | null>;
  label: string;
  type?: string;
  placeholder?: string;
  required?: boolean;
  disabled?: boolean;
  options?: Array<{ value: string | number; label: string }>;
  rows?: number;
}

const props = withDefaults(defineProps<Props>(), {
  type: 'text',
  placeholder: '',
  required: false,
  disabled: false,
  rows: 3
});

const emit = defineEmits<{
  'update:value': [value: string | number | null];
}>();

const inputRef = ref<HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement>();
const isFocused = ref(false);
const hasBeenTouched = ref(false);

const validationErrors = computed(() => {
  if (!hasBeenTouched.value && !isFocused.value) return [];

  return props.rules
    .map(rule => rule(props.value))
    .filter(error => error !== null);
});

const hasError = computed(() => validationErrors.value.length > 0);
const isValid = computed(() => validationErrors.value.length === 0 && props.value !== null && props.value !== '');

const inputClasses = computed(() => [
  'block w-full rounded-lg border px-3 py-2 text-sm transition-colors focus:outline-none focus:ring-1',
  {
    'border-gray-300 focus:border-indigo-500 focus:ring-indigo-500': !hasError.value,
    'border-red-500 focus:border-red-500 focus:ring-red-500': hasError.value,
    'bg-gray-50 cursor-not-allowed': props.disabled
  }
]);

const labelClasses = computed(() => [
  'block text-sm font-medium transition-colors',
  {
    'text-gray-700': !hasError.value,
    'text-red-700': hasError.value
  }
]);

const handleInput = (event: Event) => {
  const target = event.target as HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement;
  emit('update:value', target.value);
};

const handleFocus = () => {
  isFocused.value = true;
};

const handleBlur = () => {
  isFocused.value = false;
  hasBeenTouched.value = true;
};

const focus = () => {
  inputRef.value?.focus();
};

// Expose focus method for parent components
defineExpose({ focus });
</script>

<template>
  <div class="space-y-1">
    <!-- Label -->
    <label :class="labelClasses">
      {{ label }}
      <span v-if="required" class="text-red-500">*</span>
    </label>

    <!-- Input Field -->
    <div class="relative">
      <!-- Text Input -->
      <input
        v-if="type === 'text' || type === 'email' || type === 'tel' || type === 'url' || type === 'date'"
        ref="inputRef"
        :type="type"
        :value="value"
        :placeholder="placeholder"
        :disabled="disabled"
        :class="inputClasses"
        @input="handleInput"
        @focus="handleFocus"
        @blur="handleBlur"
      />

      <!-- Select Input -->
      <select
        v-else-if="type === 'select'"
        ref="inputRef"
        :value="value"
        :disabled="disabled"
        :class="inputClasses"
        @change="handleInput"
        @focus="handleFocus"
        @blur="handleBlur"
      >
        <option value="">{{ placeholder || 'Select an option' }}</option>
        <option v-for="option in options" :key="option.value" :value="option.value">
          {{ option.label }}
        </option>
      </select>

      <!-- Textarea -->
      <textarea
        v-else-if="type === 'textarea'"
        ref="inputRef"
        :value="value"
        :placeholder="placeholder"
        :disabled="disabled"
        :rows="rows"
        :class="inputClasses"
        @input="handleInput"
        @focus="handleFocus"
        @blur="handleBlur"
      ></textarea>

      <!-- Number Input -->
      <input
        v-else-if="type === 'number'"
        ref="inputRef"
        type="number"
        :value="value"
        :placeholder="placeholder"
        :disabled="disabled"
        :class="inputClasses"
        @input="handleInput"
        @focus="handleFocus"
        @blur="handleBlur"
      />

      <!-- Validation Icons -->
      <div class="absolute inset-y-0 right-0 flex items-center pr-3">
        <!-- Success Icon -->
        <svg
          v-if="isValid && hasBeenTouched"
          class="h-5 w-5 text-green-500"
          fill="currentColor"
          viewBox="0 0 20 20"
        >
          <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
        </svg>

        <!-- Error Icon -->
        <svg
          v-else-if="hasError"
          class="h-5 w-5 text-red-500"
          fill="currentColor"
          viewBox="0 0 20 20"
        >
          <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
        </svg>
      </div>
    </div>

    <!-- Error Messages -->
    <div v-if="hasError" class="space-y-1">
      <p v-for="error in validationErrors" :key="error" class="text-sm text-red-600">
        {{ error }}
      </p>
    </div>

    <!-- Help Text -->
    <p v-if="!hasError && placeholder && isFocused" class="text-sm text-gray-500">
      {{ placeholder }}
    </p>
  </div>
</template>
