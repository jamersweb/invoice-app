<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue';

interface Props {
  file: File;
  maxWidth?: number;
  maxHeight?: number;
}

const props = withDefaults(defineProps<Props>(), {
  maxWidth: 400,
  maxHeight: 300
});

const emit = defineEmits<{
  'close': [];
  'download': [file: File];
}>();

const previewRef = ref<HTMLDivElement>();
const isLoading = ref(true);
const error = ref<string | null>(null);
const previewUrl = ref<string | null>(null);

const fileType = computed(() => {
  const extension = props.file.name.split('.').pop()?.toLowerCase();
  return extension;
});

const isImage = computed(() => ['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(fileType.value || ''));
const isPdf = computed(() => fileType.value === 'pdf');
const isDocument = computed(() => ['doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx'].includes(fileType.value || ''));

const formatFileSize = (bytes: number): string => {
  if (bytes === 0) return '0 Bytes';
  const k = 1024;
  const sizes = ['Bytes', 'KB', 'MB', 'GB'];
  const i = Math.floor(Math.log(bytes) / Math.log(k));
  return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
};

const createPreviewUrl = () => {
  if (isImage.value) {
    previewUrl.value = URL.createObjectURL(props.file);
    isLoading.value = false;
  } else if (isPdf.value) {
    previewUrl.value = URL.createObjectURL(props.file);
    isLoading.value = false;
  } else {
    error.value = 'Preview not available for this file type';
    isLoading.value = false;
  }
};

const handleDownload = () => {
  const link = document.createElement('a');
  link.href = URL.createObjectURL(props.file);
  link.download = props.file.name;
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
  emit('download', props.file);
};

const handleClose = () => {
  if (previewUrl.value) {
    URL.revokeObjectURL(previewUrl.value);
  }
  emit('close');
};

onMounted(() => {
  createPreviewUrl();
});

onUnmounted(() => {
  if (previewUrl.value) {
    URL.revokeObjectURL(previewUrl.value);
  }
});
</script>

<template>
  <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-75">
    <div class="relative max-h-[90vh] max-w-4xl overflow-hidden rounded-lg bg-white shadow-xl">
      <!-- Header -->
      <div class="flex items-center justify-between border-b border-gray-200 p-4">
        <div class="flex items-center space-x-3">
          <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-gray-100">
            <span class="text-lg">
              {{ isImage ? '🖼️' : isPdf ? '📄' : isDocument ? '📝' : '📎' }}
            </span>
          </div>
          <div>
            <h3 class="text-lg font-semibold text-gray-900">{{ file.name }}</h3>
            <p class="text-sm text-gray-500">{{ formatFileSize(file.size) }}</p>
          </div>
        </div>

        <div class="flex items-center space-x-2">
          <button
            @click="handleDownload"
            class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500"
          >
            <svg class="mr-1 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
            </svg>
            Download
          </button>

          <button
            @click="handleClose"
            class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500"
          >
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
          </button>
        </div>
      </div>

      <!-- Content -->
      <div class="max-h-[calc(90vh-120px)] overflow-auto p-4">
        <!-- Loading State -->
        <div v-if="isLoading" class="flex h-64 items-center justify-center">
          <div class="text-center">
            <div class="mx-auto h-8 w-8 animate-spin rounded-full border-4 border-gray-300 border-t-indigo-600"></div>
            <p class="mt-2 text-sm text-gray-500">Loading preview...</p>
          </div>
        </div>

        <!-- Error State -->
        <div v-else-if="error" class="flex h-64 items-center justify-center">
          <div class="text-center">
            <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-red-100">
              <svg class="h-8 w-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
              </svg>
            </div>
            <h3 class="mt-2 text-lg font-medium text-gray-900">Preview Not Available</h3>
            <p class="mt-1 text-sm text-gray-500">{{ error }}</p>
            <p class="mt-2 text-sm text-gray-500">You can still download the file to view it.</p>
          </div>
        </div>

        <!-- Image Preview -->
        <div v-else-if="isImage && previewUrl" class="flex justify-center">
          <img
            :src="previewUrl"
            :alt="file.name"
            class="max-h-full max-w-full rounded-lg shadow-sm"
            :style="{ maxWidth: `${maxWidth}px`, maxHeight: `${maxHeight}px` }"
          />
        </div>

        <!-- PDF Preview -->
        <div v-else-if="isPdf && previewUrl" class="flex justify-center">
          <iframe
            :src="previewUrl"
            class="h-96 w-full rounded-lg border"
            :style="{ maxHeight: `${maxHeight}px` }"
          ></iframe>
        </div>

        <!-- Document Preview (Placeholder) -->
        <div v-else-if="isDocument" class="flex h-64 items-center justify-center">
          <div class="text-center">
            <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-blue-100">
              <svg class="h-8 w-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
              </svg>
            </div>
            <h3 class="mt-2 text-lg font-medium text-gray-900">Document Preview</h3>
            <p class="mt-1 text-sm text-gray-500">Preview not available for {{ fileType?.toUpperCase() }} files</p>
            <p class="mt-2 text-sm text-gray-500">Download the file to view it in your preferred application.</p>
          </div>
        </div>
      </div>

      <!-- Footer -->
      <div class="border-t border-gray-200 bg-gray-50 px-4 py-3">
        <div class="flex items-center justify-between">
          <div class="text-sm text-gray-500">
            File type: {{ fileType?.toUpperCase() || 'Unknown' }} • Size: {{ formatFileSize(file.size) }}
          </div>
          <div class="text-sm text-gray-500">
            Last modified: {{ new Date(file.lastModified).toLocaleDateString() }}
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
