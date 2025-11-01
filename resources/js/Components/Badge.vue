<script setup lang="ts">
defineProps<{
    variant?: 'success' | 'warning' | 'danger' | 'info' | 'pending' | 'paid' | 'overdue';
    size?: 'sm' | 'md' | 'lg';
}>();

const variantClasses = {
    success: 'badge-success',
    warning: 'badge-warning',
    danger: 'badge-danger',
    info: 'badge-info',
    pending: 'badge-pending',
    paid: 'badge-paid',
    overdue: 'badge-overdue',
};

const sizeClasses = {
    sm: 'text-xs px-2 py-0.5',
    md: 'text-sm px-3 py-1',
    lg: 'text-base px-4 py-1.5',
};
</script>

<template>
    <span
        :class="[
            variantClasses[variant || 'info'],
            sizeClasses[size || 'md'],
        ]"
    >
        <slot />
    </span>
</template>

