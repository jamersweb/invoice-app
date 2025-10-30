<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
const form = useForm({ company_email: '', company_phone: '' });
const submit = () => form.post('/apply');
</script>

<template>
  <Head title="Apply - Step 1" />
  <AuthenticatedLayout>
    <template #header>
      <h2 class="text-2xl font-bold text-gray-900">Apply Now</h2>
    </template>
    <div class="mx-auto max-w-lg py-8 px-4 sm:px-6 lg:px-8">
      <div class="rounded-xl border border-gray-200 bg-white p-6">
        <div class="mb-4 text-base font-semibold text-gray-900">Step 1: Contact</div>
        <form @submit.prevent="submit" class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700">Company Email</label>
            <input v-model="form.company_email" type="email" required class="mt-1 w-full rounded-lg border border-gray-300 p-2" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700">Company Phone</label>
            <input v-model="form.company_phone" type="text" class="mt-1 w-full rounded-lg border border-gray-300 p-2" />
          </div>
          <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2 text-white">Continue</button>
        </form>
      </div>
    </div>
  </AuthenticatedLayout>
  </template>
