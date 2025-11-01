<script setup lang="ts">
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Link } from '@inertiajs/vue3';

defineProps<{
    bg?: 'gradient' | 'dark';
}>();
</script>

<template>
    <div class="min-h-screen bg-dark-primary dark flex items-center justify-center p-4">
        <!-- Background Pattern/Cubes -->
        <div class="absolute inset-0 overflow-hidden opacity-10">
            <div class="absolute top-0 left-0 w-full h-full" style="background-image: repeating-linear-gradient(0deg, transparent, transparent 2px, rgba(147, 51, 234, 0.1) 2px, rgba(147, 51, 234, 0.1) 4px), repeating-linear-gradient(90deg, transparent, transparent 2px, rgba(147, 51, 234, 0.1) 2px, rgba(147, 51, 234, 0.1) 4px); background-size: 32px 32px;"></div>
        </div>

        <div class="relative w-full max-w-md">
            <!-- Logo -->
            <div class="flex justify-center mb-8">
                <Link href="/" class="flex items-center gap-2">
                    <div class="w-6 h-6 rounded bg-purple-accent flex items-center justify-center">
                        <span class="text-white text-xs font-bold">+</span>
                    </div>
                    <span class="text-xl font-semibold text-dark-text-primary">InvoiceApp</span>
                </Link>
            </div>

            <!-- Form Container -->
            <div class="bg-dark-card border border-dark-border rounded-xl shadow-2xl p-8">
                <slot />
            </div>
        </div>
    </div>
</template>
