<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, usePage } from '@inertiajs/vue3';
const page = usePage();
const token = (page.props as any)?.token as string | undefined;
</script>

<template>
  <Head title="Apply - Step 2" />
  <AuthenticatedLayout>
    <template #header>
      <h2 class="text-2xl font-bold text-gray-900">Verify Email</h2>
    </template>
    <div class="mx-auto max-w-lg py-8 px-4 sm:px-6 lg:px-8">
      <div class="rounded-xl border border-gray-200 bg-white p-6">
        <div class="text-sm text-gray-700">We sent a verification link to your email. If you didn’t receive it, check spam or click below to verify now.</div>
        <div class="mt-4">
          <a :href="route('apply.verify', { token })" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm text-white">Verify Now</a>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>

<script setup lang="ts">
import { Head, useForm, router } from '@inertiajs/vue3'

const props = defineProps<{ token: string | null }>()

const form = useForm({
  company_name: '',
  contact_name: ''
})

function submit() {
  // For MVP, we can't fetch lead by token here; show placeholder
  router.visit('/apply')
}
</script>

<template>
  <Head title="Apply - Step 2" />
  <div class="max-w-xl mx-auto p-6">
    <h1 class="text-2xl font-semibold mb-4">Apply Now - Step 2</h1>
    <p class="text-sm text-gray-600 mb-4">Token: {{ props.token }}</p>
    <form @submit.prevent="submit" class="space-y-4">
      <div>
        <label class="block text-sm font-medium">Company Name</label>
        <input v-model="form.company_name" type="text" class="mt-1 block w-full border rounded p-2" required />
      </div>
      <div>
        <label class="block text-sm font-medium">Contact Name</label>
        <input v-model="form.contact_name" type="text" class="mt-1 block w-full border rounded p-2" />
      </div>
      <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded" :disabled="form.processing">
        Continue
      </button>
    </form>
  </div>
  </template>


