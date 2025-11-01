<script setup lang="ts">
defineProps<{
    type?: 'button' | 'submit' | 'reset';
    disabled?: boolean;
    size?: 'sm' | 'md' | 'lg';
}>();

const sizeClasses = {
    sm: 'px-4 py-2 text-sm',
    md: 'px-6 py-2.5 text-base',
    lg: 'px-8 py-3 text-lg',
};
</script>

<template>
    <button
        :type="type || 'button'"
        :disabled="disabled"
        :class="[
            'btn-gradient',
            sizeClasses[size || 'md'],
            disabled && 'opacity-50 cursor-not-allowed',
        ]"
    >
        <slot />
    </button>
</template>

