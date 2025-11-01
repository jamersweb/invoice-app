<script setup lang="ts">
defineProps<{
    modelValue?: string | number;
    type?: string;
    placeholder?: string;
    required?: boolean;
    disabled?: boolean;
    icon?: string;
}>();

const emit = defineEmits<{
    'update:modelValue': [value: string | number];
}>();

function updateValue(event: Event) {
    const target = event.target as HTMLInputElement;
    emit('update:modelValue', target.value);
}
</script>

<template>
    <div class="relative">
        <div v-if="icon" class="absolute left-3 top-1/2 -translate-y-1/2 text-dark-text-muted pointer-events-none">
            <slot name="icon">
                <svg v-if="icon === 'email'" width="20" height="20" fill="none" viewBox="0 0 20 20">
                    <path stroke="currentColor" stroke-width="1.5" d="M3 7.5L10 13l7-5.5M5 5h10a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2z"/>
                </svg>
                <svg v-else-if="icon === 'lock'" width="20" height="20" fill="none" viewBox="0 0 20 20">
                    <path stroke="currentColor" stroke-width="1.5" d="M10 13a2 2 0 100-4 2 2 0 000 4zm8-3V7a5 5 0 00-10 0v3a2 2 0 00-2 2v5a2 2 0 002 2h6a2 2 0 002-2v-5a2 2 0 00-2-2z"/>
                </svg>
                <svg v-else-if="icon === 'search'" width="16" height="16" fill="none" viewBox="0 0 16 16">
                    <path stroke="currentColor" stroke-width="1.5" d="M7.333 12.667A5.333 5.333 0 107.333 2a5.333 5.333 0 000 10.667zM14 14l-2.9-2.9"/>
                </svg>
            </slot>
        </div>
        <input
            :type="type || 'text'"
            :value="modelValue"
            :placeholder="placeholder"
            :required="required"
            :disabled="disabled"
            @input="updateValue"
            :class="[
                'input-dark',
                icon && 'pl-10',
            ]"
        />
    </div>
</template>

