<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed, onMounted } from 'vue';
import AppHeader from '@/Components/AppHeader.vue';
import AppFooter from '@/Components/AppFooter.vue';
type CmsMap = Record<string, { key:string, title?:string, body?:string, cta_text?:string, cta_href?:string, image_url?:string }>;
const page = usePage();
const cms = computed(() => (page.props as any)?.cms as CmsMap | undefined);
onMounted(() => {
  try { fetch(`/api/v1/analytics/pv?path=${encodeURIComponent(location.pathname)}`); } catch {}
});
</script>

<template>
  <Head title="Home" />
  <div class="min-h-screen bg-white">
    <AppHeader />

    <main class="mx-auto max-w-7xl px-6 pb-20">
      <!-- Hero -->
      <section class="py-20">
        <div class="max-w-3xl">
          <h1 class="text-4xl font-extrabold tracking-tight text-gray-900 sm:text-5xl">{{ cms?.hero_primary?.title || 'Grow with guaranteed cash flow' }}</h1>
          <p class="mt-5 text-lg leading-7 text-gray-600">{{ cms?.hero_primary?.body || 'Reliable trade finance—submit invoices, receive offers, and get funded fast.' }}</p>
          <div class="mt-8 flex gap-3">
            <Link :href="cms?.hero_primary?.cta_href || '/apply'" class="inline-flex items-center rounded-lg bg-indigo-600 px-6 py-3 text-white shadow-sm hover:bg-indigo-700">{{ cms?.hero_primary?.cta_text || 'Contact Us' }}</Link>
            <Link href="/how-it-works" class="inline-flex items-center rounded-lg border border-gray-300 px-6 py-3 text-gray-800 hover:bg-gray-50">How it works</Link>
          </div>
        </div>
      </section>

      <!-- Services (Export Factoring / Supply Chain Finance) -->
      <section class="py-12">
        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
          <div class="rounded-2xl border border-gray-200 p-8">
            <h3 class="text-xl font-semibold text-gray-900">{{ (cms as any)?.services_1?.title || 'Export Factoring' }}</h3>
            <p class="mt-3 text-sm text-gray-600">{{ (cms as any)?.services_1?.body || 'Solve short-term cash flow by advancing up to 95% of invoice value.' }}</p>
            <div class="mt-5">
              <Link :href="(cms as any)?.services_1?.cta_href || '/apply'" class="text-indigo-600 hover:underline">{{ (cms as any)?.services_1?.cta_text || 'Learn more' }}</Link>
            </div>
          </div>
          <div class="rounded-2xl border border-gray-200 p-8">
            <h3 class="text-xl font-semibold text-gray-900">{{ (cms as any)?.services_2?.title || 'Supply Chain Finance' }}</h3>
            <p class="mt-3 text-sm text-gray-600">{{ (cms as any)?.services_2?.body || 'Optimize payables, receivables, and inventory across your supply chain.' }}</p>
            <div class="mt-5">
              <Link :href="(cms as any)?.services_2?.cta_href || '/apply'" class="text-indigo-600 hover:underline">{{ (cms as any)?.services_2?.cta_text || 'Learn more' }}</Link>
            </div>
          </div>
        </div>
      </section>

      <!-- Who we are (pillars) -->
      <section class="py-12">
        <h2 class="text-2xl font-bold text-gray-900">Who we are</h2>
        <div class="mt-6 grid grid-cols-1 gap-6 md:grid-cols-3">
          <div class="rounded-xl border border-gray-200 p-6">
            <div class="text-lg font-semibold">{{ cms?.features_1?.title || 'Tailor-made' }}</div>
            <p class="mt-2 text-sm text-gray-600">{{ cms?.features_1?.body || 'Flexible financing crafted around your needs.' }}</p>
          </div>
          <div class="rounded-xl border border-gray-200 p-6">
            <div class="text-lg font-semibold">{{ cms?.features_2?.title || 'Global focus' }}</div>
            <p class="mt-2 text-sm text-gray-600">{{ cms?.features_2?.body || 'International know-how to support cross-border trade.' }}</p>
          </div>
          <div class="rounded-xl border border-gray-200 p-6">
            <div class="text-lg font-semibold">{{ cms?.features_3?.title || 'Local expertise' }}</div>
            <p class="mt-2 text-sm text-gray-600">{{ cms?.features_3?.body || 'Entrepreneurial team with regional insight.' }}</p>
          </div>
        </div>
      </section>

      <!-- Stats row -->
      <section class="py-8">
        <div class="grid grid-cols-1 gap-6 sm:grid-cols-3">
          <div class="rounded-xl border border-gray-200 p-6 text-center">
            <div class="text-3xl font-extrabold text-gray-900">{{ (cms as any)?.stats_1?.title || '72.5%' }}</div>
            <div class="mt-2 text-sm text-gray-600">{{ (cms as any)?.stats_1?.body || 'Worldwide payments are late' }}</div>
          </div>
          <div class="rounded-xl border border-gray-200 p-6 text-center">
            <div class="text-3xl font-extrabold text-gray-900">{{ (cms as any)?.stats_2?.title || '24–48h' }}</div>
            <div class="mt-2 text-sm text-gray-600">{{ (cms as any)?.stats_2?.body || 'Typical payout time' }}</div>
          </div>
          <div class="rounded-xl border border-gray-200 p-6 text-center">
            <div class="text-3xl font-extrabold text-gray-900">{{ (cms as any)?.stats_3?.title || '82%' }}</div>
            <div class="mt-2 text-sm text-gray-600">{{ (cms as any)?.stats_3?.body || 'SME failures tied to cash flow' }}</div>
          </div>
        </div>
      </section>

      <!-- Footer CTA -->
      <section class="mt-12 rounded-2xl bg-indigo-50 p-8">
        <div class="md:flex md:items-center md:justify-between">
          <div>
            <h3 class="text-xl font-semibold text-gray-900">{{ (cms as any)?.footer_cta?.title || 'Let’s get in touch.' }}</h3>
            <p class="mt-2 text-sm text-gray-700">{{ (cms as any)?.footer_cta?.body || 'Find the right trade finance solution tailored to your needs.' }}</p>
          </div>
          <div class="mt-4 md:mt-0">
            <Link :href="(cms as any)?.footer_cta?.cta_href || '/contact'" class="inline-flex items-center rounded-lg bg-indigo-600 px-5 py-3 text-white hover:bg-indigo-700">{{ (cms as any)?.footer_cta?.cta_text || 'Contact Us' }}</Link>
          </div>
        </div>
      </section>
    </main>
  </div>
  <AppFooter />
</template>


