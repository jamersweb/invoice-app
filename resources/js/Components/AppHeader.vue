<script setup lang="ts">
import { ref, computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

const isMobileMenuOpen = ref(false);
const page = usePage();
const user = computed(() => page.props.auth.user);

const toggleMobileMenu = () => {
  isMobileMenuOpen.value = !isMobileMenuOpen.value;
};
</script>

<template>
  <header class="bg-slate-900/50 border-b border-slate-700/50 backdrop-blur-sm sticky top-0 z-50">
    <nav class="mx-auto max-w-7xl px-6 lg:px-8" aria-label="Global">
      <div class="flex items-center justify-between py-4">
        <div class="flex lg:flex-1">
          <a class="flex items-center gap-2 sm:gap-3 group" href="/" data-discover="true">
            <div class="relative" bis_skin_checked="1">
              <div
                class="w-8 h-8 sm:w-10 sm:h-10 bg-black rounded-full shadow-lg border border-slate-600 transition-transform group-hover:scale-110"
                bis_skin_checked="1"></div>
              <div
                class="absolute inset-0 w-8 h-8 sm:w-10 sm:h-10 bg-gradient-to-br from-slate-700/20 to-slate-900/20 rounded-full blur-md group-hover:blur-lg transition-all"
                bis_skin_checked="1"></div>
            </div>
            <div bis_skin_checked="1">
              <h1 class="text-base sm:text-lg font-bold text-white group-hover:text-blue-400 transition-colors">BlackDot
                Forfaiting</h1>
            </div>
          </a>
        </div>
        <!-- Mobile menu button -->
        <div class="flex lg:hidden">
          <button type="button"
            class="-m-2.5 inline-flex items-center justify-center rounded-md p-2.5 text-gray-400 hover:text-white"
            @click="toggleMobileMenu">
            <span class="sr-only">Open main menu</span>
            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
              aria-hidden="true">
              <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
            </svg>
          </button>
        </div>
        <!-- Desktop menu -->
        <div class="hidden lg:flex lg:gap-x-8 text-sm text-gray-300">
          <a href="/" class="hover:text-white transition-colors">Home</a>
          <a href="/how-it-works" class="hover:text-white transition-colors">How it works</a>
          <a href="/faqs" class="hover:text-white transition-colors">FAQs</a>
          <a href="/contact" class="hover:text-white transition-colors">Contact</a>
          <a v-if="!user" href="/login" class="hover:text-white transition-colors">Sign In</a>
          <a v-else href="/dashboard" class="hover:text-white transition-colors">My Dashboard</a>
        </div>
        <div class="hidden lg:flex lg:flex-1 lg:justify-end">
          <a v-if="!user" href="/apply"
            class="rounded-lg bg-indigo-600 px-3 py-1.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
            Apply Now
          </a>
        </div>
      </div>
    </nav>

    <Teleport to="body">
      <!-- Mobile menu, show/hide based on menu state. -->
      <div class="lg:hidden" role="dialog" aria-modal="true">
        <Transition enter-active-class="transition-opacity ease-linear duration-300" enter-from-class="opacity-0"
          enter-to-class="opacity-100" leave-active-class="transition-opacity ease-linear duration-300"
          leave-from-class="opacity-100" leave-to-class="opacity-0">
          <div class="fixed inset-0 z-[60]" v-show="isMobileMenuOpen">
            <!-- Background backdrop -->
            <div class="fixed inset-0 bg-slate-900/80 backdrop-blur-sm" @click="toggleMobileMenu"></div>
          </div>
        </Transition>

        <Transition enter-active-class="transition ease-in-out duration-300 transform"
          enter-from-class="translate-x-full" enter-to-class="translate-x-0"
          leave-active-class="transition ease-in-out duration-300 transform" leave-from-class="translate-x-0"
          leave-to-class="translate-x-full">
          <div v-show="isMobileMenuOpen"
            class="fixed inset-y-0 right-0 z-[60] w-full overflow-y-auto bg-slate-900 px-6 py-6 sm:max-w-sm sm:ring-1 sm:ring-white/10">
            <div class="flex items-center justify-between">
              <a class="flex items-center gap-2 sm:gap-3 group" href="/" data-discover="true">
                <div class="relative" bis_skin_checked="1">
                  <div
                    class="w-8 h-8 sm:w-10 sm:h-10 bg-black rounded-full shadow-lg border border-slate-600 transition-transform group-hover:scale-110"
                    bis_skin_checked="1"></div>
                  <div
                    class="absolute inset-0 w-8 h-8 sm:w-10 sm:h-10 bg-gradient-to-br from-slate-700/20 to-slate-900/20 rounded-full blur-md group-hover:blur-lg transition-all"
                    bis_skin_checked="1"></div>
                </div>
                <div bis_skin_checked="1">
                  <h1 class="text-base sm:text-lg font-bold text-white group-hover:text-blue-400 transition-colors">
                    BlackDot Forfaiting</h1>
                </div>
              </a>
              <button type="button" class="-m-2.5 rounded-md p-2.5 text-gray-400 hover:text-white"
                @click="toggleMobileMenu">
                <span class="sr-only">Close menu</span>
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                  aria-hidden="true">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>
            <div class="mt-6 flow-root">
              <div class="-my-6 divide-y divide-gray-500/25">
                <div class="space-y-2 py-6">
                  <a href="/"
                    class="-mx-3 block rounded-lg px-3 py-2 text-base font-semibold leading-7 text-white hover:bg-slate-800">Home</a>
                  <a href="/how-it-works"
                    class="-mx-3 block rounded-lg px-3 py-2 text-base font-semibold leading-7 text-white hover:bg-slate-800">How
                    it works</a>
                  <a href="/faqs"
                    class="-mx-3 block rounded-lg px-3 py-2 text-base font-semibold leading-7 text-white hover:bg-slate-800">FAQs</a>
                  <a href="/contact"
                    class="-mx-3 block rounded-lg px-3 py-2 text-base font-semibold leading-7 text-white hover:bg-slate-800">Contact</a>
                  <a v-if="!user" href="/login"
                    class="-mx-3 block rounded-lg px-3 py-2 text-base font-semibold leading-7 text-white hover:bg-slate-800">Sign
                    In</a>
                  <a v-else href="/dashboard"
                    class="-mx-3 block rounded-lg px-3 py-2 text-base font-semibold leading-7 text-white hover:bg-slate-800">My
                    Dashboard</a>
                </div>
                <div class="py-6" v-if="!user">
                  <a href="/apply"
                    class="-mx-3 block rounded-lg px-3 py-2.5 text-base font-semibold leading-7 text-white hover:bg-slate-800">Apply
                    Now</a>
                </div>
              </div>
            </div>
          </div>
        </Transition>
      </div>
    </Teleport>
  </header>
</template>
