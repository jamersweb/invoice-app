<script setup lang="ts">
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import GradientButton from '@/Components/GradientButton.vue';
import DarkInput from '@/Components/DarkInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps<{
    status?: string;
}>();

const form = useForm({
    email: '',
});

const submit = () => {
    form.post(route('password.email'));
};
</script>

<template>
    <GuestLayout>
        <Head title="Forgot Password" />
        
        <div class="space-y-6">
            <!-- Header -->
            <div class="text-center mb-6">
                <h2 class="text-2xl font-bold text-dark-text-primary mb-2">Forgot Password</h2>
                <p class="text-sm text-dark-text-secondary">
                    No problem. Just let us know your email address and we will email you a password reset link.
                </p>
            </div>

            <div
                v-if="status"
                class="mb-4 text-sm font-medium text-green-400 text-center bg-green-500/20 border border-green-500/30 rounded-lg p-3"
            >
                {{ status }}
            </div>

            <form @submit.prevent="submit" class="space-y-5">
                <div>
                    <InputLabel for="email" value="Email" class="text-dark-text-secondary mb-2" />
                    <DarkInput
                        id="email"
                        v-model="form.email"
                        type="email"
                        placeholder="Enter your email"
                        icon="email"
                        required
                        autofocus
                        autocomplete="username"
                    />
                    <InputError class="mt-2" :message="form.errors.email" />
                </div>

                <GradientButton type="submit" class="w-full" :disabled="form.processing">
                    Email Password Reset Link
                </GradientButton>
            </form>

            <!-- Return to Sign In -->
            <div class="text-center text-sm">
                <span class="text-dark-text-secondary">Remember your password? </span>
                <Link :href="route('login')" class="text-purple-accent hover:text-purple-hover font-medium transition-colors">
                    Sign In
                </Link>
            </div>
        </div>
    </GuestLayout>
</template>
