<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { onMounted } from 'vue';
import AppHeader from '@/Components/AppHeader.vue';
import AppFooter from '@/Components/AppFooter.vue';
onMounted(() => { try { fetch(`/api/v1/analytics/pv?path=${encodeURIComponent(location.pathname)}`); } catch { } });
const faqs = [
  { q: 'Who is eligible?', a: 'Registered businesses with active operations.' },
  { q: 'How fast is funding?', a: 'Typically within 24-48 hours after acceptance.' },
  { q: 'What documents are required?', a: 'Trade license, MoA, owner IDs, and bank letter.' },
];
</script>

<template>

  <Head title="FAQs" />
  <AppHeader />
  <div class="min-h-screen bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900 relative overflow-hidden "
    bis_skin_checked="1">
    <div class="absolute inset-0 overflow-hidden" bis_skin_checked="1">
      <div class="absolute inset-0 bg-gradient-to-br from-blue-600/8 via-purple-600/8 to-emerald-600/8"
        bis_skin_checked="1"></div>
      <div class="absolute top-0 left-1/4 w-96 h-96 bg-blue-500/15 rounded-full blur-3xl animate-pulse"
        bis_skin_checked="1"></div>
      <div class="absolute bottom-0 right-1/4 w-96 h-96 bg-purple-500/15 rounded-full blur-3xl animate-pulse"
        bis_skin_checked="1" style="animation-delay: 1s;"></div>
      <div
        class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-[600px] h-[600px] bg-emerald-500/10 rounded-full blur-3xl animate-pulse"
        bis_skin_checked="1" style="animation-delay: 2s;"></div>
      <div class="absolute top-1/4 left-1/3 w-2 h-2 bg-blue-400/40 rounded-full animate-ping" bis_skin_checked="1"
        style="animation-duration: 3s;"></div>
      <div class="absolute top-1/3 right-1/4 w-2 h-2 bg-purple-400/40 rounded-full animate-ping" bis_skin_checked="1"
        style="animation-duration: 4s; animation-delay: 1s;"></div>
      <div class="absolute bottom-1/4 left-1/4 w-2 h-2 bg-emerald-400/40 rounded-full animate-ping" bis_skin_checked="1"
        style="animation-duration: 5s; animation-delay: 2s;"></div>
      <div class="absolute top-1/2 right-1/3 w-2 h-2 bg-blue-400/40 rounded-full animate-ping" bis_skin_checked="1"
        style="animation-duration: 4s; animation-delay: 1.5s;"></div>
      <div class="absolute inset-0" bis_skin_checked="1"
        style="background-image: linear-gradient(rgba(148, 163, 184, 0.05) 1px, transparent 1px), linear-gradient(90deg, rgba(148, 163, 184, 0.05) 1px, transparent 1px); background-size: 50px 50px;">
      </div>
    </div>
    <div class="relative faqs" bis_skin_checked="1">
      <div class="mx-auto max-w-2xl px-6 py-10">
        <h1 class="text-2xl sm:text-4xl font-bold text-white mb-12 text-center">Frequently Asked Questions</h1>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 sm:gap-8" bis_skin_checked="1">
          <div
            class="rounded-xl border text-card-foreground shadow bg-slate-800/40 backdrop-blur-sm border-slate-700/50 p-8 hover:border-blue-500/50 transition-all duration-300 hover:transform hover:scale-105 group"
            bis_skin_checked="1">
            <div class="text-2xl font-bold text-white mb-4 text-center" bis_skin_checked="1">Who is eligible?</div>
            <div class="mt-2 text-sm text-gray-600" bis_skin_checked="1">Registered businesses with active operations.
            </div>
          </div>
          <div
            class="rounded-xl border text-card-foreground shadow bg-slate-800/40 backdrop-blur-sm border-slate-700/50 p-8 hover:border-purple-500/50 transition-all duration-300 hover:transform hover:scale-105 group"
            bis_skin_checked="1">
            <div class="text-2xl font-bold text-white mb-4 text-center" bis_skin_checked="1">How fast is funding?</div>
            <div class="mt-2 text-sm text-gray-600" bis_skin_checked="1">Typically within 24-48 hours after acceptance.
            </div>
          </div>
          <div
            class="rounded-xl border text-card-foreground shadow bg-slate-800/40 backdrop-blur-sm border-slate-700/50 p-8 hover:border-emerald-500/50 transition-all duration-300 hover:transform hover:scale-105 group"
            bis_skin_checked="1">
            <div class="text-2xl font-bold text-white mb-4 text-center" bis_skin_checked="1">What documents are
              required?</div>
            <div class="mt-2 text-sm text-gray-600" bis_skin_checked="1">Trade license, MoA, owner IDs, and bank letter.
            </div>
          </div>
        </div>

      </div>
    </div>



  </div>
  <AppFooter />
</template>
