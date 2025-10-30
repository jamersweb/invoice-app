<script setup lang="ts">
import Checkbox from '@/Components/Checkbox.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
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
    <GuestLayout bg='gradient'>
        <Head title="Log in" />
        <div class="flex flex-col items-center justify-center min-h-screen bg-gradient-to-tr from-indigo-100 via-white to-purple-100">
            <div class="w-full max-w-md p-8 mx-auto bg-white rounded-2xl shadow-2xl border border-gray-100 mt-10">
                <div class="mb-8 text-center">
                    <h2 class="mb-2 text-3xl font-extrabold text-gray-900 font-sans">Sign in to your account</h2>
                    <p class="text-gray-500 text-sm">Welcome back! Please enter your details below.</p>
                </div>
                <div v-if="status" class="mb-4 text-sm font-medium text-green-600 text-center">
                    {{ status }}
                </div>
                <form @submit.prevent="submit" class="space-y-6">
                    <div>
                        <InputLabel for="email" value="Email" />
                        <div class="relative mt-1">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">
                                <svg width="20" height="20" fill="none" viewBox="0 0 20 20"><path stroke="currentColor" stroke-width="1.5" d="M3 7.5L10 13l7-5.5M5 5h10a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2z"/></svg>
                            </span>
                            <TextInput id="email" type="email" class="pl-10 pr-3 py-2 mt-1 block w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 shadow transition" v-model="form.email" required autofocus autocomplete="username" placeholder="Enter your email" />
                        </div>
                        <InputError class="mt-2" :message="form.errors.email" />
                    </div>
                    <div>
                        <InputLabel for="password" value="Password" />
                        <div class="relative mt-1">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">
                                <svg width="20" height="20" fill="none" viewBox="0 0 20 20"><path stroke="currentColor" stroke-width="1.5" d="M10 13a2 2 0 100-4 2 2 0 000 4zm8-3V7a5 5 0 00-10 0v3a2 2 0 00-2 2v5a2 2 0 002 2h6a2 2 0 002-2v-5a2 2 0 00-2-2z"/></svg>
                            </span>
                            <TextInput id="password" type="password" class="pl-10 pr-3 py-2 mt-1 block w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 shadow transition" v-model="form.password" required autocomplete="current-password" placeholder="Enter your password" />
                        </div>
                        <InputError class="mt-2" :message="form.errors.password" />
                    </div>
                    <div class="flex items-center justify-between mt-4">
                        <label class="flex items-center space-x-2 cursor-pointer">
                            <Checkbox name="remember" v-model:checked="form.remember" />
                            <span class="text-sm text-gray-600">Remember me</span>
                        </label>
                        <Link v-if="canResetPassword" :href="route('password.request')"
                            class="text-sm text-indigo-600 hover:text-indigo-800 font-medium underline ml-2 transition">
                            Forgot your password?
                        </Link>
                    </div>
                    <div>
                        <PrimaryButton class="w-full mt-4 py-3 text-base rounded-lg bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 transition text-white font-semibold shadow-lg" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                            Log in
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </div>
    </GuestLayout>
</template>
