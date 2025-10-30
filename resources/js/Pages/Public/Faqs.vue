<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { onMounted } from 'vue';
import AppHeader from '@/Components/AppHeader.vue';
import AppFooter from '@/Components/AppFooter.vue';
onMounted(() => { try { fetch(`/api/v1/analytics/pv?path=${encodeURIComponent(location.pathname)}`); } catch {} });
const faqs = [
  { q: 'Who is eligible?', a: 'Registered businesses with active operations.' },
  { q: 'How fast is funding?', a: 'Typically within 24-48 hours after acceptance.' },
  { q: 'What documents are required?', a: 'Trade license, MoA, owner IDs, and bank letter.' },
];
</script>

<template>
  <Head title="FAQs" />
  <AppHeader />
  <div class="mx-auto max-w-4xl px-6 py-10">
    <h1 class="text-3xl font-bold">Frequently Asked Questions</h1>
    <div class="mt-8 space-y-6">
      <div v-for="(item, idx) in faqs" :key="idx" class="rounded-lg border border-gray-200 p-5">
        <div class="font-medium text-gray-900">{{ item.q }}</div>
        <div class="mt-2 text-sm text-gray-600">{{ item.a }}</div>
      </div>
    </div>
  </div>
  <AppFooter />
  </template>


