<script setup lang="ts">
import Checkbox from '@/Components/Checkbox.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import GradientButton from '@/Components/GradientButton.vue';
import DarkInput from '@/Components/DarkInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps<{
    canResetPassword?: boolean;
    status?: string;
}>();

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => {
            form.reset('password');
        },
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Log in" />
        
        <div class="space-y-6">
            <!-- Header -->
            <div class="text-center mb-6">
                <h2 class="text-2xl font-bold text-dark-text-primary mb-2">Login</h2>
                <p class="text-sm text-dark-text-secondary">Welcome back! Please enter your details below.</p>
            </div>

            <div v-if="status" class="mb-4 text-sm font-medium text-green-400 text-center bg-green-500/20 border border-green-500/30 rounded-lg p-3">
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

                <div>
                    <InputLabel for="password" value="Password" class="text-dark-text-secondary mb-2" />
                    <DarkInput
                        id="password"
                        v-model="form.password"
                        type="password"
                        placeholder="Enter your password"
                        icon="lock"
                        required
                        autocomplete="current-password"
                    />
                    <InputError class="mt-2" :message="form.errors.password" />
                </div>

                <div class="flex items-center justify-between">
                    <label class="flex items-center space-x-2 cursor-pointer">
                        <Checkbox name="remember" v-model:checked="form.remember" />
                        <span class="text-sm text-dark-text-secondary">Remember me</span>
                    </label>
                    <Link
                        v-if="canResetPassword"
                        :href="route('password.request')"
                        class="text-sm text-purple-accent hover:text-purple-hover font-medium transition-colors"
                    >
                        Forgot your password?
                    </Link>
                </div>

                <GradientButton type="submit" class="w-full" :disabled="form.processing">
                    Log in
                </GradientButton>
            </form>

            <!-- Divider -->
            <div class="relative my-6">
                <div class="absolute inset-0 flex items-center">
                    <div class="w-full border-t border-dark-border"></div>
                </div>
                <div class="relative flex justify-center text-sm">
                    <span class="px-2 bg-dark-card text-dark-text-muted">OR</span>
                </div>
            </div>

            <!-- Social Login -->
            <div class="grid grid-cols-2 gap-3">
                <button type="button" class="flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg bg-dark-secondary hover:bg-dark-tertiary border border-dark-border text-dark-text-primary transition-colors">
                    <svg width="14" height="14" fill="currentColor" viewBox="0 0 14 14">
                        <path d="M13.714 7.071A6.714 6.714 0 1 0 7 13.714a6.678 6.678 0 0 0 4.357-1.571L11.357 10a4.285 4.285 0 0 1-2.714.928c-2.357 0-4.286-1.929-4.286-4.286S6.286 2.357 8.643 2.357c1.071 0 2 .393 2.714 1.036l1.786-1.786A6.714 6.714 0 0 0 7 .286a6.714 6.714 0 0 0 0 13.428 6.714 6.714 0 0 0 6.714-6.643Z"/>
                    </svg>
                    <span class="text-sm">Google</span>
                </button>
                <button type="button" class="flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg bg-dark-secondary hover:bg-dark-tertiary border border-dark-border text-dark-text-primary transition-colors">
                    <svg width="14" height="14" fill="currentColor" viewBox="0 0 14 14">
                        <path d="M7 0a7 7 0 0 0-2.188 13.643v-4.833H4.143V6.857h.669V5.286c0-2.071 1.571-3.857 3.857-3.857 1.071 0 2 .357 2.714 1.071v1.714H9.857c-.857 0-1.214.429-1.214 1.143v1.143h2.429l-.429 2.953H8.643v4.833A7.001 7.001 0 0 0 7 0Z"/>
                    </svg>
                    <span class="text-sm">Facebook</span>
                </button>
            </div>

            <!-- Register Link -->
            <div class="text-center text-sm">
                <span class="text-dark-text-secondary">Don't have an account? </span>
                <Link :href="route('register')" class="text-purple-accent hover:text-purple-hover font-medium transition-colors">
                    Sign Up
                </Link>
            </div>
        </div>
    </GuestLayout>
</template>
