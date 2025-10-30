<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { onMounted } from 'vue';
import AppHeader from '@/Components/AppHeader.vue';
import AppFooter from '@/Components/AppFooter.vue';
onMounted(() => { try { fetch(`/api/v1/analytics/pv?path=${encodeURIComponent(location.pathname)}`); } catch {} });
const form = useForm({ name: '', email: '', message: '' });
function submit() { form.post('/contact'); }
</script>

<template>
  <Head title="Contact" />
  <AppHeader />
  <div class="mx-auto max-w-xl px-6 py-10">
    <h1 class="text-3xl font-bold">Contact us</h1>
    <form class="mt-8 space-y-4" @submit.prevent="submit">
      <div>
        <label class="block text-sm font-medium text-gray-700">Name</label>
        <input v-model="form.name" class="mt-1 w-full rounded-lg border border-gray-300 p-2" required />
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700">Email</label>
        <input type="email" v-model="form.email" class="mt-1 w-full rounded-lg border border-gray-300 p-2" required />
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700">Message</label>
        <textarea v-model="form.message" rows="5" class="mt-1 w-full rounded-lg border border-gray-300 p-2" required />
      </div>
      <button type="submit" class="rounded-lg bg-indigo-600 px-5 py-2 text-white hover:bg-indigo-700" :disabled="form.processing">Send</button>
      <div v-if="$page.props.flash?.success" class="mt-3 text-sm text-green-600">Thanks! We received your message.</div>
      <div v-if="form.hasErrors" class="mt-3 text-sm text-red-600">Please fix the errors above.</div>
    </form>
  </div>
  <AppFooter />
  </template>


