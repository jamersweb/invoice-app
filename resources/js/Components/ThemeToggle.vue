<script setup lang="ts">
import { ref, onMounted } from 'vue';

const isDark = ref(true); // Default to dark theme

function toggleTheme() {
    isDark.value = !isDark.value;
    if (isDark.value) {
        document.documentElement.classList.add('dark');
        document.body.classList.add('dark');
        localStorage.setItem('theme', 'dark');
    } else {
        document.documentElement.classList.remove('dark');
        document.body.classList.remove('dark');
        localStorage.setItem('theme', 'light');
    }
}

onMounted(() => {
    const savedTheme = localStorage.getItem('theme');
    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
    isDark.value = savedTheme !== 'light' && (savedTheme === 'dark' || prefersDark);
    
    if (isDark.value) {
        document.documentElement.classList.add('dark');
        document.body.classList.add('dark');
    }
});
</script>

<template>
    <button
        @click="toggleTheme"
        class="p-2 rounded-lg bg-dark-secondary hover:bg-dark-tertiary border border-dark-border transition-colors"
        type="button"
        aria-label="Toggle theme"
    >
        <!-- Sun icon for light mode (when dark is active, show moon) -->
        <svg v-if="isDark" width="16" height="16" fill="none" viewBox="0 0 16 16" class="text-dark-text-secondary">
            <path stroke="currentColor" stroke-width="1.5" d="M8 1.333v1.334M8 13.333v1.334M2.667 8H1.333M14.667 8H13.333M3.753 3.753l-.943.943M13.19 12.19l-.943.943M3.753 12.247l-.943-.943M13.19 3.81l-.943-.943M10.667 8a2.667 2.667 0 11-5.334 0 2.667 2.667 0 015.334 0z"/>
        </svg>
        <!-- Moon icon for dark mode (when light is active, show sun) -->
        <svg v-else width="16" height="16" fill="none" viewBox="0 0 16 16" class="text-dark-text-secondary">
            <path stroke="currentColor" stroke-width="1.5" d="M8 2.667V1.333M8 14.667V13.333M13.333 8H14.667M1.333 8H2.667M12.453 3.547l.947-.947M2.6 13.4l.947-.947M3.547 12.453l-.947.947M13.4 2.6l-.947.947M10.667 8A2.667 2.667 0 118 5.333 2.667 2.667 0 012.667 8 2.667 2.667 0 0110.667 8z"/>
        </svg>
    </button>
</template>

