<script setup lang="ts">
import Modal from '@/Components/Modal.vue';
import GradientButton from '@/Components/GradientButton.vue';

defineProps<{
    show: boolean;
    title?: string;
    message?: string;
    confirmText?: string;
    cancelText?: string;
    type?: 'warning' | 'danger' | 'info';
}>();

const emit = defineEmits(['close', 'confirm']);

const close = () => {
    emit('close');
};

const confirm = () => {
    emit('confirm');
    close();
};
</script>

<template>
    <Modal :show="show" max-width="sm" @close="close">
        <div class="p-6 bg-dark-primary border border-dark-border rounded-lg overflow-hidden relative">
            <!-- Glow background -->
            <div class="absolute -top-24 -right-24 w-48 h-48 bg-purple-accent/10 blur-[80px] rounded-full"></div>
            
            <div class="relative z-10 flex flex-col items-center text-center">
                <!-- Icon -->
                <div :class="[
                    'w-16 h-16 rounded-2xl flex items-center justify-center mb-4 transition-all duration-500',
                    type === 'danger' ? 'bg-red-500/20 text-red-400' : 'bg-purple-accent/20 text-purple-accent'
                ]">
                    <svg v-if="type === 'danger'" class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                    <svg v-else class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>

                <h3 class="text-xl font-bold text-dark-text-primary mb-2">{{ title || 'Are you sure?' }}</h3>
                <p class="text-sm text-dark-text-secondary leading-relaxed mb-8">
                    {{ message || 'Do you really want to perform this action? This might be permanent.' }}
                </p>

                <div class="flex flex-col sm:flex-row gap-3 w-full">
                    <button 
                        @click="close"
                        class="flex-1 px-6 py-3 rounded-xl bg-dark-secondary hover:bg-dark-hover text-dark-text-primary text-sm font-semibold transition-all border border-dark-border"
                    >
                        {{ cancelText || 'Cancel' }}
                    </button>
                    <GradientButton 
                        @click="confirm"
                        class="flex-1 !rounded-xl !py-3 !text-sm"
                        :class="type === 'danger' ? 'from-red-600 to-red-500 hover:from-red-500 hover:to-red-400' : ''"
                    >
                        {{ confirmText || 'Confirm' }}
                    </GradientButton>
                </div>
            </div>
        </div>
    </Modal>
</template>
