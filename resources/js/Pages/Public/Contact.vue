<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { onMounted } from 'vue';
import AppHeader from '@/Components/AppHeader.vue';
import AppFooter from '@/Components/AppFooter.vue';
onMounted(() => { try { fetch(`/api/v1/analytics/pv?path=${encodeURIComponent(location.pathname)}`); } catch { } });
const form = useForm({ name: '', email: '', message: '' });
function submit() { form.post('/contact'); }
</script>

<template>

  <Head title="Contact" />
  <AppHeader />
  <main>
    <div class="min-h-screen bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900 relative overflow-hidden"
      bis_skin_checked="1">
      <div class="absolute inset-0 overflow-hidden" bis_skin_checked="1">
        <div class="absolute inset-0 bg-gradient-to-br from-blue-600/5 via-purple-600/5 to-emerald-600/5"
          bis_skin_checked="1"></div>
        <div class="absolute top-0 left-1/4 w-96 h-96 bg-blue-500/10 rounded-full blur-3xl animate-pulse"
          bis_skin_checked="1"></div>
        <div class="absolute bottom-0 right-1/4 w-96 h-96 bg-purple-500/10 rounded-full blur-3xl animate-pulse"
          style="animation-delay: 1s;" bis_skin_checked="1"></div>
      </div>
      <div class="relative max-w-4xl mx-auto px-4 sm:px-6 py-16 sm:py-24" bis_skin_checked="1">
        <div class="text-center mb-12" bis_skin_checked="1">
          <div class="flex justify-center mb-6" bis_skin_checked="1">
            <div class="p-4 bg-blue-500/20 rounded-2xl" bis_skin_checked="1"><svg xmlns="http://www.w3.org/2000/svg"
                width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-mail w-12 h-12 text-blue-400">
                <rect width="20" height="16" x="2" y="4" rx="2"></rect>
                <path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"></path>
              </svg></div>
          </div>
          <h1 class="text-3xl sm:text-4xl font-bold text-white mb-4">Get in Touch</h1>
          <p class="text-lg text-slate-300 max-w-2xl mx-auto">Have questions about our forfaiting services? We're here
            to help. Send us a message and we'll respond as soon as possible.</p>
        </div>
        <div class="rounded-xl border text-card-foreground shadow bg-slate-800/40 backdrop-blur-sm border-slate-700 p-8"
          bis_skin_checked="1">
          <form class="space-y-6"><input type="text" name="website" tabindex="-1" autocomplete="off" value=""
              style="position: absolute; left: -9999px;">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6" bis_skin_checked="1">
              <div class="space-y-2" bis_skin_checked="1"><label
                  class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70 text-slate-300"
                  for="name">Your Name *</label><input
                  class="flex h-9 w-full rounded-md border px-3 py-1 text-base shadow-sm transition-colors file:border-0 file:bg-transparent file:text-sm file:font-medium file:text-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50 md:text-sm bg-slate-900/50 border-slate-700 text-white placeholder:text-slate-500"
                  id="name" placeholder="John Smith" required value=""></div>
              <div class="space-y-2" bis_skin_checked="1"><label
                  class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70 text-slate-300"
                  for="email">Email Address *</label><input type="email"
                  class="flex h-9 w-full rounded-md border px-3 py-1 text-base shadow-sm transition-colors file:border-0 file:bg-transparent file:text-sm file:font-medium file:text-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50 md:text-sm bg-slate-900/50 border-slate-700 text-white placeholder:text-slate-500"
                  id="email" placeholder="john@company.com" required value=""></div>
            </div>
            <div class="space-y-2" bis_skin_checked="1"><label
                class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70 text-slate-300"
                for="subject">Subject *</label><input
                class="flex h-9 w-full rounded-md border px-3 py-1 text-base shadow-sm transition-colors file:border-0 file:bg-transparent file:text-sm file:font-medium file:text-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50 md:text-sm bg-slate-900/50 border-slate-700 text-white placeholder:text-slate-500"
                id="subject" placeholder="How can we help you?" required value=""></div>
            <div class="space-y-2" bis_skin_checked="1"><label
                class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70 text-slate-300"
                for="message">Message *</label><textarea
                class="flex min-h-[60px] w-full rounded-md border px-3 py-2 text-base shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50 md:text-sm bg-slate-900/50 border-slate-700 text-white placeholder:text-slate-500"
                id="message" placeholder="Please provide details about your inquiry..." required rows="6"></textarea>
            </div>
            <div class="flex justify-end pt-4" bis_skin_checked="1"><button
                class="inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-50 [&amp;_svg]:pointer-events-none [&amp;_svg]:size-4 [&amp;_svg]:shrink-0 shadow h-9 bg-blue-600 hover:bg-blue-700 text-white px-8 py-3"
                type="submit"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                  fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                  class="lucide lucide-send w-4 h-4 mr-2">
                  <path
                    d="M14.536 21.686a.5.5 0 0 0 .937-.024l6.5-19a.496.496 0 0 0-.635-.635l-19 6.5a.5.5 0 0 0-.024.937l7.93 3.18a2 2 0 0 1 1.112 1.11z">
                  </path>
                  <path d="m21.854 2.147-10.94 10.939"></path>
                </svg>Send Message</button></div>
          </form>
        </div>
        <p class="text-center text-slate-400 text-sm mt-8">By submitting this form, you agree to be contacted by
          BlackDot Forfaiting Services regarding your inquiry.</p>
      </div>
    </div>
  </main>
  <AppFooter />
</template>
