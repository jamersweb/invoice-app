<script setup lang="ts">
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('register'), {
        onFinish: () => {
            form.reset('password', 'password_confirmation');
        },
    });
};
</script>

<template>
    <GuestLayout bg='gradient'>
        <Head title="Register" />
        <div class="flex flex-col items-center justify-center min-h-screen bg-gradient-to-tr from-indigo-100 via-white to-purple-100">
            <div class="w-full max-w-md p-8 mx-auto bg-white rounded-2xl shadow-2xl border border-gray-100 mt-10">
                <div class="mb-8 text-center">
                    <h2 class="mb-2 text-3xl font-extrabold text-gray-900 font-sans">Create your account</h2>
                    <p class="text-gray-500 text-sm">Fill in the details to get started on Invoice App.</p>
                </div>
                <form @submit.prevent="submit" class="space-y-6">
                    <div>
                        <InputLabel for="name" value="Name" />
                        <div class="relative mt-1">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">
                                <svg width="20" height="20" fill="none" viewBox="0 0 20 20"><path stroke="currentColor" stroke-width="1.5" d="M13.25 6a3.25 3.25 0 11-6.5 0 3.25 3.25 0 016.5 0zM4.75 15.25a4.5 4.5 0 0110.5 0v.5a.75.75 0 01-.75.75h-9a.75.75 0 01-.75-.75v-.5z"/></svg>
                            </span>
                            <TextInput id="name" type="text" class="pl-10 pr-3 py-2 mt-1 block w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 shadow transition" v-model="form.name" required autofocus autocomplete="name" placeholder="Full Name" />
                        </div>
                        <InputError class="mt-2" :message="form.errors.name" />
                    </div>
                    <div>
                        <InputLabel for="email" value="Email" />
                        <div class="relative mt-1">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">
                                <svg width="20" height="20" fill="none" viewBox="0 0 20 20"><path stroke="currentColor" stroke-width="1.5" d="M3 7.5L10 13l7-5.5M5 5h10a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2z"/></svg>
                            </span>
                            <TextInput id="email" type="email" class="pl-10 pr-3 py-2 mt-1 block w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 shadow transition" v-model="form.email" required autocomplete="username" placeholder="Email address" />
                        </div>
                        <InputError class="mt-2" :message="form.errors.email" />
                    </div>
                    <div>
                        <InputLabel for="password" value="Password" />
                        <div class="relative mt-1">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">
                                <svg width="20" height="20" fill="none" viewBox="0 0 20 20"><path stroke="currentColor" stroke-width="1.5" d="M10 13a2 2 0 100-4 2 2 0 000 4zm8-3V7a5 5 0 00-10 0v3a2 2 0 00-2 2v5a2 2 0 002 2h6a2 2 0 002-2v-5a2 2 0 00-2-2z"/></svg>
                            </span>
                            <TextInput id="password" type="password" class="pl-10 pr-3 py-2 mt-1 block w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 shadow transition" v-model="form.password" required autocomplete="new-password" placeholder="Create a password" />
                        </div>
                        <InputError class="mt-2" :message="form.errors.password" />
                    </div>
                    <div>
                        <InputLabel for="password_confirmation" value="Confirm Password" />
                        <div class="relative mt-1">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">
                                <svg width="20" height="20" fill="none" viewBox="0 0 20 20"><path stroke="currentColor" stroke-width="1.5" d="M10 13a2 2 0 100-4 2 2 0 000 4zm8-3V7a5 5 0 00-10 0v3a2 2 0 00-2 2v5a2 2 0 002 2h6a2 2 0 002-2v-5a2 2 0 00-2-2z"/></svg>
                            </span>
                            <TextInput id="password_confirmation" type="password" class="pl-10 pr-3 py-2 mt-1 block w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 shadow transition" v-model="form.password_confirmation" required autocomplete="new-password" placeholder="Confirm password" />
                        </div>
                        <InputError class="mt-2" :message="form.errors.password_confirmation" />
                    </div>
                    <div class="flex items-center justify-between mt-6">
                        <Link :href="route('login')" class="rounded-md text-indigo-600 hover:text-indigo-800 font-medium underline transition">
                            Already registered?
                        </Link>
                        <PrimaryButton class="ms-4 px-7 py-3 text-base rounded-lg bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 transition text-white font-semibold shadow-lg" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                            Register
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </div>
    </GuestLayout>
</template>
