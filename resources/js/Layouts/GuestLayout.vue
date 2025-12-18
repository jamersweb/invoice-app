<script setup lang="ts">
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Link } from '@inertiajs/vue3';

defineProps<{
    bg?: 'gradient' | 'dark';
}>();
</script>

<template>
    <div class="min-h-screen bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900 relative overflow-hidden"
        bis_skin_checked="1">
        <div class="absolute inset-0 overflow-hidden" bis_skin_checked="1">
            <div class="absolute inset-0 bg-gradient-to-br from-blue-600/8 via-purple-600/8 to-emerald-600/8"
                bis_skin_checked="1"></div>
            <div class="absolute top-0 left-1/4 w-96 h-96 bg-blue-500/15 rounded-full blur-3xl animate-pulse"
                bis_skin_checked="1"></div>
            <div class="absolute bottom-0 right-1/4 w-96 h-96 bg-purple-500/15 rounded-full blur-3xl animate-pulse"
                bis_skin_checked="1" style="animation-delay: 1s;"></div>
            <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-[600px] h-[600px] bg-emerald-500/10 rounded-full blur-3xl animate-pulse"
                bis_skin_checked="1" style="animation-delay: 2s;"></div>
            <div class="absolute top-1/4 left-1/3 w-2 h-2 bg-blue-400/40 rounded-full animate-ping" bis_skin_checked="1"
                style="animation-duration: 3s;"></div>
            <div class="absolute top-1/3 right-1/4 w-2 h-2 bg-purple-400/40 rounded-full animate-ping"
                bis_skin_checked="1" style="animation-duration: 4s; animation-delay: 1s;"></div>
            <div class="absolute bottom-1/4 left-1/4 w-2 h-2 bg-emerald-400/40 rounded-full animate-ping"
                bis_skin_checked="1" style="animation-duration: 5s; animation-delay: 2s;"></div>
            <div class="absolute top-1/2 right-1/3 w-2 h-2 bg-blue-400/40 rounded-full animate-ping"
                bis_skin_checked="1" style="animation-duration: 4s; animation-delay: 1.5s;"></div>
            <div class="absolute inset-0" bis_skin_checked="1"
                style="background-image: linear-gradient(rgba(148, 163, 184, 0.05) 1px, transparent 1px), linear-gradient(90deg, rgba(148, 163, 184, 0.05) 1px, transparent 1px); background-size: 50px 50px;">
            </div>
        </div>
        <div class="relative" bis_skin_checked="1">
            <div class="mx-auto max-w-2xl px-6 py-10">
                <h1 class="text-3xl font-bold text-center">Frequently Asked Questions</h1>
                <div class="mt-8 space-y-6">
                    <!-- Form Container -->
                    <div class="border border-dark-border rounded-xl p-4">
                        <slot />
                    </div>
                </div>
            </div>
        </div>



    </div>

</template>
