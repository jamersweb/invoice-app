<script setup lang="ts">
import GuestLayout from '@/Layouts/GuestLayout.vue';
import KycKybFormContent from '@/Components/Onboarding/KycKybFormContent.vue';
import { Head } from '@inertiajs/vue3';
</script>

<template>
    <Head title="Registration & KYB" />

    <GuestLayout widthClass="max-w-[50rem]">
        <template #header>
            <div class="text-center mb-8">
                <h2 class="text-3xl font-bold text-dark-text-primary mb-2">Registration & KYB</h2>
                <p class="text-sm text-dark-text-secondary">Please complete your business profile to get started.</p>
            </div>
        </template>
        
        <KycKybFormContent />
    </GuestLayout>
</template>
