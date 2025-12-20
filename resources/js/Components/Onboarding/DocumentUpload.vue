<script setup lang="ts">
import { ref, computed } from 'vue';
import DocumentPreview from './DocumentPreview.vue';

interface Props {
  documentType: {
    id: number;
    name: string;
    required: boolean;
    description: string;
  };
  uploadedFiles?: File[];
  maxFiles?: number;
  acceptedTypes?: string[];
}

const props = withDefaults(defineProps<Props>(), {
  uploadedFiles: () => [],
  maxFiles: 5,
  acceptedTypes: () => ['.pdf', '.jpg', '.jpeg', '.png']
});

const emit = defineEmits<{
  'files-uploaded': [files: File[]];
  'file-removed': [index: number];
  'version-note': [note: string];
}>();

const files = ref<File[]>(props.uploadedFiles);
const isDragOver = ref(false);
const uploadProgress = ref<Record<string, number>>({});
const showPreview = ref(false);
const previewFile = ref<File | null>(null);

const fileInput = ref<HTMLInputElement>();

const isUploading = computed(() => Object.values(uploadProgress.value).some(progress => progress < 100));

const handleDragOver = (event: DragEvent) => {
  event.preventDefault();
  isDragOver.value = true;
};

const handleDragLeave = (event: DragEvent) => {
  event.preventDefault();
  isDragOver.value = false;
};

const handleDrop = (event: DragEvent) => {
  event.preventDefault();
  isDragOver.value = false;

  const droppedFiles = Array.from(event.dataTransfer?.files || []);
  addFiles(droppedFiles);
};

const handleFileSelect = (event: Event) => {
  const target = event.target as HTMLInputElement;
  if (target.files) {
    const selectedFiles = Array.from(target.files);
    addFiles(selectedFiles);
  }
};

const addFiles = (newFiles: File[]) => {
  const validFiles = newFiles.filter(file => {
    const extension = '.' + file.name.split('.').pop()?.toLowerCase();
    return props.acceptedTypes.includes(extension) && file.size <= 10 * 1024 * 1024; // 10MB limit
  });

  if (files.value.length + validFiles.length > props.maxFiles) {
    alert(`Maximum ${props.maxFiles} files allowed`);
    return;
  }

  files.value.push(...validFiles);
  emit('files-uploaded', files.value);

  // Simulate upload progress
  validFiles.forEach(file => {
    uploadProgress.value[file.name] = 0;
    simulateUpload(file.name);
  });
};

const simulateUpload = (fileName: string) => {
  const interval = setInterval(() => {
    uploadProgress.value[fileName] += Math.random() * 30;
    if (uploadProgress.value[fileName] >= 100) {
      uploadProgress.value[fileName] = 100;
      clearInterval(interval);
    }
  }, 200);
};

const removeFile = (index: number) => {
  files.value.splice(index, 1);
  emit('file-removed', index);
  emit('files-uploaded', files.value);
};

const formatFileSize = (bytes: number): string => {
  if (bytes === 0) return '0 Bytes';
  const k = 1024;
  const sizes = ['Bytes', 'KB', 'MB', 'GB'];
  const i = Math.floor(Math.log(bytes) / Math.log(k));
  return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
};

const getFileIcon = (fileName: string): string => {
  const extension = fileName.split('.').pop()?.toLowerCase();
  switch (extension) {
    case 'pdf':
      return '📄';
    case 'jpg':
    case 'jpeg':
    case 'png':
      return '🖼️';
    case 'doc':
    case 'docx':
      return '📝';
    case 'xls':
    case 'xlsx':
      return '📊';
    default:
      return '📎';
  }
};

const openPreview = (file: File) => {
  previewFile.value = file;
  showPreview.value = true;
};

const closePreview = () => {
  showPreview.value = false;
  previewFile.value = null;
};

const handleDownload = (file: File) => {
  const link = document.createElement('a');
  link.href = URL.createObjectURL(file);
  link.download = file.name;
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
};

const openFileDialog = () => {
  // Ensure file input exists then trigger native click
  if (fileInput.value) {
    fileInput.value.click();
    return;
  }
  const el = document.querySelector<HTMLInputElement>('input[type="file"][ref="fileInput"]');
  el?.click();
};

const versionNote = ref('');
const updateNote = () => emit('version-note', versionNote.value);
</script>

<template>
  <div class="rounded-lg p-lg-6">
    <div class="flex items-center justify-between">
      <div>
        <h4 class="text-sm font-medium">{{ documentType.name }}</h4>
        <p class="text-sm text-gray-500">{{ documentType.description }}</p>
        <span v-if="documentType.required" class="mt-1 inline-block rounded bg-red-100 px-2 py-1 text-xs text-red-800">
          Required
        </span>
      </div>
      <div>
        <button @click="openFileDialog"
          class="cursor-pointer rounded-lg custom-bg-purple px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500">
          Upload Files
        </button>
        <input ref="fileInput" type="file" multiple :accept="acceptedTypes.join(',')" @change="handleFileSelect"
          class="hidden" />
      </div>
    </div>

    <!-- Drag and Drop Area -->
    <div @dragover="handleDragOver" @dragleave="handleDragLeave" @drop="handleDrop" @click="openFileDialog" :class="[
      'mt-4 rounded-lg border-2 border-dashed p-6 text-center transition-colors cursor-pointer',
      isDragOver ? 'border-indigo-400 bg-indigo-50' : 'border-gray-300 hover:border-gray-400'
    ]">
      <svg :class="[
        'mx-auto h-12 w-12 transition-colors',
        isDragOver ? 'text-indigo-500' : 'text-gray-400'
      ]" stroke="currentColor" fill="none" viewBox="0 0 48 48">
        <path
          d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02"
          stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
      </svg>
      <div class="mt-2">
        <p class="text-sm text-gray-600">
          <span class="font-medium text-indigo-600">Click to upload</span>
          or drag and drop
        </p>
        <p class="text-xs text-gray-500">
          {{ acceptedTypes.join(', ') }} up to 10MB each
        </p>
      </div>
    </div>

    <!-- Version Note -->
    <div class="mt-4">
      <label class="block text-xs font-medium text-gray-700">Version notes (optional)</label>
      <input v-model="versionNote" @input="updateNote" class="mt-1 w-full rounded-lg border border-gray-300 p-2 text-sm"
        placeholder="e.g., Updated trade license 2025" />
    </div>

    <!-- Uploaded Files -->
    <div v-if="files.length > 0" class="mt-4 space-y-2">
      <div v-for="(file, index) in files" :key="index" class="flex items-center justify-between rounded p-3">
        <div class="flex items-center">
          <span class="mr-2 text-lg">{{ getFileIcon(file.name) }}</span>
          <div>
            <p class="text-sm font-medium truncate max-w-[200px] sm:max-w-xs">{{ file.name }}</p>
            <p class="text-xs text-gray-500">{{ formatFileSize(file.size) }}</p>
          </div>
        </div>

        <div class="flex items-center space-x-2">
          <!-- Preview Button -->
          <button @click="openPreview(file)" class="text-blue-600 hover:text-blue-800 focus:outline-none"
            :disabled="isUploading">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z">
              </path>
            </svg>
          </button>

          <!-- Upload Progress -->
          <div v-if="uploadProgress[file.name] !== undefined && uploadProgress[file.name] < 100" class="w-20">
            <div class="h-2 rounded-full bg-gray-200">
              <div class="h-2 rounded-full bg-indigo-600 transition-all duration-300"
                :style="{ width: `${uploadProgress[file.name]}%` }"></div>
            </div>
            <p class="text-xs text-gray-500">{{ Math.round(uploadProgress[file.name]) }}%</p>
          </div>

          <!-- Success Icon -->
          <div v-else-if="uploadProgress[file.name] === 100" class="flex items-center">
            <svg class="h-4 w-4 text-green-500" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd"
                d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                clip-rule="evenodd" />
            </svg>
          </div>

          <!-- Remove Button -->
          <button @click="removeFile(index)" class="text-red-600 hover:text-red-800 focus:outline-none"
            :disabled="isUploading">
            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd"
                d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                clip-rule="evenodd" />
            </svg>
          </button>
        </div>
      </div>
    </div>

    <!-- File Count -->
    <div v-if="files.length > 0" class="mt-2 text-right">
      <p class="text-xs text-gray-500">
        {{ files.length }} of {{ maxFiles }} files uploaded
      </p>
    </div>

    <!-- Document Preview Modal -->
    <DocumentPreview v-if="showPreview && previewFile" :file="previewFile" @close="closePreview"
      @download="handleDownload" />
  </div>
</template>
