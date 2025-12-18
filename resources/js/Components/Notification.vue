<script setup lang="ts">
import { useNotificationStore } from '@/stores/notification';
import { storeToRefs } from 'pinia';

const store = useNotificationStore();
const { notifications } = storeToRefs(store);
const { remove } = store;

const getTypeClasses = (type: string) => {
    switch (type) {
        case 'success':
            return 'bg-green-500/10 border-green-500/50 text-green-400';
        case 'error':
            return 'bg-red-500/10 border-red-500/50 text-red-400';
        case 'warning':
            return 'bg-yellow-500/10 border-yellow-500/50 text-yellow-400';
        default:
            return 'bg-blue-500/10 border-blue-500/50 text-blue-400';
    }
};

const getIcon = (type: string) => {
    switch (type) {
        case 'success':
            return '✅';
        case 'error':
            return '❌';
        case 'warning':
            return '⚠️';
        default:
            return 'ℹ️';
    }
};
</script>

<template>
    <div class="fixed top-4 right-4 z-[9999] space-y-3 w-full max-w-sm pointer-events-none">
        <TransitionGroup
            enter-active-class="transform ease-out duration-300 transition"
            enter-from-class="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-4"
            enter-to-class="translate-y-0 opacity-100 sm:translate-x-0"
            leave-active-class="transition ease-in duration-100"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-for="notification in notifications"
                :key="notification.id"
                class="pointer-events-auto overflow-hidden rounded-xl border p-4 shadow-2xl backdrop-blur-md transition-all duration-300"
                :class="getTypeClasses(notification.type)"
            >
                <div class="flex items-start gap-3">
                    <span class="text-xl shrink-0">{{ getIcon(notification.type) }}</span>
                    <div class="flex-1 pt-0.5">
                        <p class="text-sm font-medium leading-tight">
                            {{ notification.message }}
                        </p>
                    </div>
                    <button
                        @click="remove(notification.id)"
                        class="shrink-0 rounded-lg p-1 hover:bg-white/10 transition-colors"
                    >
                        <svg class="h-4 w-4 opacity-50 hover:opacity-100" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                
                <!-- Progress bar -->
                <div v-if="notification.duration && notification.duration > 0" class="absolute bottom-0 left-0 h-0.5 bg-current opacity-30 w-full animate-shrink" :style="{ animationDuration: notification.duration + 'ms' }"></div>
            </div>
        </TransitionGroup>
    </div>
</template>

<style scoped>
@keyframes shrink {
    from { width: 100%; }
    to { width: 0%; }
}
.animate-shrink {
    animation-name: shrink;
    animation-timing-function: linear;
    animation-fill-mode: forwards;
}
</style>
