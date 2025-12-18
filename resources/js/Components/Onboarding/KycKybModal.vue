<script setup lang="ts">
import Modal from '@/Components/Modal.vue';
import KycKybFormContent from '@/Components/Onboarding/KycKybFormContent.vue';

defineProps<{
    show: boolean;
}>();

const emit = defineEmits(['close', 'success']);

const handleSuccess = () => {
    emit('success');
};
</script>

<template>
    <Modal :show="show" max-width="4xl" @close="emit('close')">
        <div class="p-6 bg-dark-primary">
            <KycKybFormContent @success="handleSuccess" />
        </div>
    </Modal>
</template>
