<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { onMounted } from 'vue';
import AppHeader from '@/Components/AppHeader.vue';
import AppFooter from '@/Components/AppFooter.vue';
onMounted(() => { try { fetch(`/api/v1/analytics/pv?path=${encodeURIComponent(location.pathname)}`); } catch {} });
</script>

<template>
  <Head title="How it works" />
  <AppHeader />
  <div class="mx-auto max-w-5xl px-6 py-10">
    <h1 class="text-3xl font-bold">How it works</h1>
    <ol class="mt-6 space-y-4 list-decimal list-inside text-gray-700">
      <li>Register and complete KYB/KYC.</li>
      <li>Submit invoices and required documents.</li>
      <li>Receive an offer and review the terms.</li>
      <li>Accept electronically and receive funds.</li>
      <li>Buyer pays on due date; you track status.</li>
    </ol>
    <div class="mt-8">
      <Link href="/apply" class="text-indigo-600 hover:underline">Start your application →</Link>
    </div>
  </div>
  <AppFooter />
</template>


