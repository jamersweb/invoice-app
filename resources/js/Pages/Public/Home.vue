<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed, onMounted } from 'vue';
import AppHeader from '@/Components/AppHeader.vue';
import AppFooter from '@/Components/AppFooter.vue';
type CmsMap = Record<string, { key: string, title?: string, body?: string, cta_text?: string, cta_href?: string, image_url?: string }>;
const page = usePage();
const cms = computed(() => (page.props as any)?.cms as CmsMap | undefined);
onMounted(() => {
  try { fetch(`/api/v1/analytics/pv?path=${encodeURIComponent(location.pathname)}`); } catch { }
});
</script>

<template>

  <Head title="Home" />
  <div class="min-h-screen">
    <AppHeader />

    <main>
      <div class="min-h-screen bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900 relative overflow-hidden"
        bis_skin_checked="1">
        <div class="absolute inset-0 overflow-hidden" bis_skin_checked="1">
          <div class="absolute inset-0 bg-gradient-to-br from-blue-600/8 via-purple-600/8 to-emerald-600/8"
            bis_skin_checked="1"></div>
          <div class="absolute top-0 left-1/4 w-96 h-96 bg-blue-500/15 rounded-full blur-3xl animate-pulse"
            bis_skin_checked="1"></div>
          <div class="absolute bottom-0 right-1/4 w-96 h-96 bg-purple-500/15 rounded-full blur-3xl animate-pulse"
            style="animation-delay: 1s;" bis_skin_checked="1"></div>
          <div
            class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-[600px] h-[600px] bg-emerald-500/10 rounded-full blur-3xl animate-pulse"
            style="animation-delay: 2s;" bis_skin_checked="1"></div>
          <div class="absolute top-1/4 left-1/3 w-2 h-2 bg-blue-400/40 rounded-full animate-ping"
            style="animation-duration: 3s;" bis_skin_checked="1"></div>
          <div class="absolute top-1/3 right-1/4 w-2 h-2 bg-purple-400/40 rounded-full animate-ping"
            style="animation-duration: 4s; animation-delay: 1s;" bis_skin_checked="1"></div>
          <div class="absolute bottom-1/4 left-1/4 w-2 h-2 bg-emerald-400/40 rounded-full animate-ping"
            style="animation-duration: 5s; animation-delay: 2s;" bis_skin_checked="1"></div>
          <div class="absolute top-1/2 right-1/3 w-2 h-2 bg-blue-400/40 rounded-full animate-ping"
            style="animation-duration: 4s; animation-delay: 1.5s;" bis_skin_checked="1"></div>
          <div class="absolute inset-0"
            style="background-image: linear-gradient(rgba(148, 163, 184, 0.05) 1px, transparent 1px), linear-gradient(90deg, rgba(148, 163, 184, 0.05) 1px, transparent 1px); background-size: 50px 50px;"
            bis_skin_checked="1"></div>
        </div>
        <div class="relative" bis_skin_checked="1">
          <div class="max-w-6xl mx-auto px-4 sm:px-6 py-16 sm:py-24" bis_skin_checked="1">
            <div class="text-center" bis_skin_checked="1">
              <div class="flex justify-center mb-8" bis_skin_checked="1">
                <div class="relative" bis_skin_checked="1">
                  <div class="w-20 h-20 bg-black rounded-full shadow-2xl border-2 border-slate-600"
                    bis_skin_checked="1"></div>
                  <div
                    class="absolute inset-0 w-20 h-20 bg-gradient-to-br from-slate-700/30 to-slate-900/30 rounded-full blur-xl animate-pulse"
                    bis_skin_checked="1"></div>
                </div>
              </div>
              <h1 class="text-3xl sm:text-5xl md:text-6xl font-bold text-white mb-6 leading-tight">Welcome to<br><span
                  class="bg-gradient-to-r from-blue-400 via-purple-400 to-emerald-400 bg-clip-text text-transparent">BlackDot
                  Forfaiting Services</span></h1>
              <p class="text-lg sm:text-xl text-slate-300 mb-4 max-w-3xl mx-auto leading-relaxed">We specialize in
                purchasing trade receivables to help businesses unlock liquidity, improve cash flow, and reduce payment
                risk.</p>
              <p class="text-base sm:text-lg text-slate-400 mb-10 max-w-2xl mx-auto">Our focus is on real transactions,
                clear structures, and measurable results.</p>
              <div class="flex flex-col sm:flex-row gap-4 justify-center" bis_skin_checked="1"><a href="/login"
                  class="inline-flex items-center justify-center gap-2 whitespace-nowrap focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-50 [&amp;_svg]:pointer-events-none [&amp;_svg]:size-4 [&amp;_svg]:shrink-0 h-10 bg-white hover:bg-slate-100 text-slate-900 shadow-2xl text-base sm:text-lg px-8 py-6 rounded-xl font-semibold transition-all hover:scale-105"><svg
                    xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                    class="lucide lucide-lock w-5 h-5 mr-2">
                    <rect width="18" height="11" x="3" y="11" rx="2" ry="2"></rect>
                    <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                  </svg>Login</a><a href="/contact" data-discover="true"><button
                    class="inline-flex items-center justify-center gap-2 whitespace-nowrap focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-50 [&amp;_svg]:pointer-events-none [&amp;_svg]:size-4 [&amp;_svg]:shrink-0 hover:bg-accent hover:text-accent-foreground h-10 bg-transparent border-2 border-white/30 hover:border-white/50 text-white shadow-2xl text-base sm:text-lg px-8 py-6 rounded-xl font-semibold transition-all hover:scale-105 w-full sm:w-auto"><svg
                      xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                      stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                      class="lucide lucide-mail w-5 h-5 mr-2">
                      <rect width="20" height="16" x="2" y="4" rx="2"></rect>
                      <path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"></path>
                    </svg>Contact Us</button></a></div>
            </div>
          </div>
        </div>
        <div class="relative max-w-6xl mx-auto px-4 sm:px-6 py-16" bis_skin_checked="1">
          <div class="text-center mb-12" bis_skin_checked="1">
            <h2 class="text-2xl sm:text-4xl font-bold text-white mb-4">What We Do</h2>
            <div class="w-20 h-1 bg-gradient-to-r from-blue-400 to-purple-400 mx-auto rounded-full mb-6"
              bis_skin_checked="1"></div>
          </div>
          <div
            class="rounded-xl border text-card-foreground shadow bg-slate-800/30 backdrop-blur-sm border-slate-700/50 p-8 sm:p-12 mb-16"
            bis_skin_checked="1">
            <p class="text-slate-300 text-base sm:text-lg leading-relaxed text-center max-w-4xl mx-auto"><span
                class="font-semibold text-white">BlackDot Forfaiting Services (BDFS)</span> provides forfaiting and
              structured trade finance solutions by purchasing receivables generated from cross-border and domestic
              trade. We work with exporters, suppliers, and intermediaries who need early payment against receivables
              backed by Letters of Credit, SBLCs, or confirmed payment obligations.</p>
          </div>
          <div class="mb-12" bis_skin_checked="1">
            <h2 class="text-2xl sm:text-4xl font-bold text-white mb-12 text-center">Our Three Pillars</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 sm:gap-8" bis_skin_checked="1">
              <div
                class="rounded-xl border text-card-foreground shadow bg-slate-800/40 backdrop-blur-sm border-slate-700/50 p-8 hover:border-blue-500/50 transition-all duration-300 hover:transform hover:scale-105 group"
                bis_skin_checked="1">
                <div class="flex justify-center mb-6" bis_skin_checked="1">
                  <div class="p-4 bg-blue-500/10 rounded-2xl group-hover:bg-blue-500/20 transition-colors"
                    bis_skin_checked="1"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                      viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                      stroke-linejoin="round" class="lucide lucide-shield w-10 h-10 text-blue-400">
                      <path
                        d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z">
                      </path>
                    </svg></div>
                </div>
                <h3 class="text-2xl font-bold text-white mb-4 text-center">1. Transparency</h3>
                <div class="space-y-3 text-slate-300 leading-relaxed" bis_skin_checked="1">
                  <p class="flex items-start gap-2"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                      viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                      stroke-linejoin="round"
                      class="lucide lucide-circle-check w-5 h-5 text-blue-400 flex-shrink-0 mt-0.5">
                      <circle cx="12" cy="12" r="10"></circle>
                      <path d="m9 12 2 2 4-4"></path>
                    </svg><span>Every transaction is conducted with full visibility</span></p>
                  <p class="flex items-start gap-2"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                      viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                      stroke-linejoin="round"
                      class="lucide lucide-circle-check w-5 h-5 text-blue-400 flex-shrink-0 mt-0.5">
                      <circle cx="12" cy="12" r="10"></circle>
                      <path d="m9 12 2 2 4-4"></path>
                    </svg><span>All terms, fees, and timelines are clearly defined</span></p>
                  <p class="flex items-start gap-2"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                      viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                      stroke-linejoin="round"
                      class="lucide lucide-circle-check w-5 h-5 text-blue-400 flex-shrink-0 mt-0.5">
                      <circle cx="12" cy="12" r="10"></circle>
                      <path d="m9 12 2 2 4-4"></path>
                    </svg><span>No hidden costs or unclear conditions</span></p>
                </div>
              </div>
              <div
                class="rounded-xl border text-card-foreground shadow bg-slate-800/40 backdrop-blur-sm border-slate-700/50 p-8 hover:border-purple-500/50 transition-all duration-300 hover:transform hover:scale-105 group"
                bis_skin_checked="1">
                <div class="flex justify-center mb-6" bis_skin_checked="1">
                  <div class="p-4 bg-purple-500/10 rounded-2xl group-hover:bg-purple-500/20 transition-colors"
                    bis_skin_checked="1"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                      viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                      stroke-linejoin="round" class="lucide lucide-target w-10 h-10 text-purple-400">
                      <circle cx="12" cy="12" r="10"></circle>
                      <circle cx="12" cy="12" r="6"></circle>
                      <circle cx="12" cy="12" r="2"></circle>
                    </svg></div>
                </div>
                <h3 class="text-2xl font-bold text-white mb-4 text-center">2. Discipline</h3>
                <div class="space-y-3 text-slate-300 leading-relaxed" bis_skin_checked="1">
                  <p class="flex items-start gap-2"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                      viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                      stroke-linejoin="round"
                      class="lucide lucide-circle-check w-5 h-5 text-purple-400 flex-shrink-0 mt-0.5">
                      <circle cx="12" cy="12" r="10"></circle>
                      <path d="m9 12 2 2 4-4"></path>
                    </svg><span>Structured due diligence process</span></p>
                  <p class="flex items-start gap-2"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                      viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                      stroke-linejoin="round"
                      class="lucide lucide-circle-check w-5 h-5 text-purple-400 flex-shrink-0 mt-0.5">
                      <circle cx="12" cy="12" r="10"></circle>
                      <path d="m9 12 2 2 4-4"></path>
                    </svg><span>Credit assessment for every deal</span></p>
                  <p class="flex items-start gap-2"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                      viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                      stroke-linejoin="round"
                      class="lucide lucide-circle-check w-5 h-5 text-purple-400 flex-shrink-0 mt-0.5">
                      <circle cx="12" cy="12" r="10"></circle>
                      <path d="m9 12 2 2 4-4"></path>
                    </svg><span>Responsible capital deployment and consistent performance management</span></p>
                </div>
              </div>
              <div
                class="rounded-xl border text-card-foreground shadow bg-slate-800/40 backdrop-blur-sm border-slate-700/50 p-8 hover:border-emerald-500/50 transition-all duration-300 hover:transform hover:scale-105 group"
                bis_skin_checked="1">
                <div class="flex justify-center mb-6" bis_skin_checked="1">
                  <div class="p-4 bg-emerald-500/10 rounded-2xl group-hover:bg-emerald-500/20 transition-colors"
                    bis_skin_checked="1"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                      viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                      stroke-linejoin="round" class="lucide lucide-handshake w-10 h-10 text-emerald-400">
                      <path d="m11 17 2 2a1 1 0 1 0 3-3"></path>
                      <path
                        d="m14 14 2.5 2.5a1 1 0 1 0 3-3l-3.88-3.88a3 3 0 0 0-4.24 0l-.88.88a1 1 0 1 1-3-3l2.81-2.81a5.79 5.79 0 0 1 7.06-.87l.47.28a2 2 0 0 0 1.42.25L21 4">
                      </path>
                      <path d="m21 3 1 11h-2"></path>
                      <path d="M3 3 2 14l6.5 6.5a1 1 0 1 0 3-3"></path>
                      <path d="M3 4h8"></path>
                    </svg></div>
                </div>
                <h3 class="text-2xl font-bold text-white mb-4 text-center">3. Partnership</h3>
                <div class="space-y-3 text-slate-300 leading-relaxed" bis_skin_checked="1">
                  <p class="flex items-start gap-2"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                      viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                      stroke-linejoin="round"
                      class="lucide lucide-circle-check w-5 h-5 text-emerald-400 flex-shrink-0 mt-0.5">
                      <circle cx="12" cy="12" r="10"></circle>
                      <path d="m9 12 2 2 4-4"></path>
                    </svg><span>We work alongside our clients, not above them</span></p>
                  <p class="flex items-start gap-2"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                      viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                      stroke-linejoin="round"
                      class="lucide lucide-circle-check w-5 h-5 text-emerald-400 flex-shrink-0 mt-0.5">
                      <circle cx="12" cy="12" r="10"></circle>
                      <path d="m9 12 2 2 4-4"></path>
                    </svg><span>Collaborative approach focused on sustainable trade growth</span></p>
                  <p class="flex items-start gap-2"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                      viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                      stroke-linejoin="round"
                      class="lucide lucide-circle-check w-5 h-5 text-emerald-400 flex-shrink-0 mt-0.5">
                      <circle cx="12" cy="12" r="10"></circle>
                      <path d="m9 12 2 2 4-4"></path>
                    </svg><span>Building long-term trust and partnerships</span></p>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="relative max-w-6xl mx-auto px-4 sm:px-6 py-16" bis_skin_checked="1">
          <div
            class="rounded-xl border text-card-foreground shadow bg-gradient-to-br from-blue-900/40 to-purple-900/40 backdrop-blur-sm border-blue-700/50 p-10 sm:p-16 text-center"
            bis_skin_checked="1">
            <h2 class="text-2xl sm:text-4xl font-bold text-white mb-6">Our Commitment</h2>
            <div class="w-20 h-1 bg-gradient-to-r from-blue-400 to-purple-400 mx-auto rounded-full mb-8"
              bis_skin_checked="1"></div>
            <p class="text-lg sm:text-xl text-slate-200 leading-relaxed max-w-3xl mx-auto mb-4"><span
                class="font-semibold text-white">We simplify trade finance.</span></p>
            <p class="text-base sm:text-lg text-slate-300 leading-relaxed max-w-3xl mx-auto">By purchasing receivables,
              we convert pending payments into working capital — allowing businesses to operate with confidence,
              flexibility, and stability.</p>
          </div>
        </div>
        <div class="h-16" bis_skin_checked="1"></div>
      </div>
    </main>
  </div>
  <AppFooter />
</template>
