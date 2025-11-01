<script setup lang="ts">
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import GradientButton from '@/Components/GradientButton.vue';
import DarkInput from '@/Components/DarkInput.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps<{
    email: string;
    token: string;
}>();

const form = useForm({
    token: props.token,
    email: props.email,
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('password.store'), {
        onFinish: () => {
            form.reset('password', 'password_confirmation');
        },
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Reset Password" />
        
        <div class="space-y-6">
            <!-- Header -->
            <div class="text-center mb-6">
                <h2 class="text-2xl font-bold text-dark-text-primary mb-2">Reset Password</h2>
                <p class="text-sm text-dark-text-secondary">
                    Enter your new password below.
                </p>
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
                        disabled
                    />
                    <InputError class="mt-2" :message="form.errors.email" />
                </div>

                <div>
                    <InputLabel for="password" value="New Password" class="text-dark-text-secondary mb-2" />
                    <DarkInput
                        id="password"
                        v-model="form.password"
                        type="password"
                        placeholder="Enter new password"
                        icon="lock"
                        required
                        autocomplete="new-password"
                    />
                    <InputError class="mt-2" :message="form.errors.password" />
                </div>

                <div>
                    <InputLabel for="password_confirmation" value="Confirm Password" class="text-dark-text-secondary mb-2" />
                    <DarkInput
                        id="password_confirmation"
                        v-model="form.password_confirmation"
                        type="password"
                        placeholder="Confirm new password"
                        icon="lock"
                        required
                        autocomplete="new-password"
                    />
                    <InputError class="mt-2" :message="form.errors.password_confirmation" />
                </div>

                <GradientButton type="submit" class="w-full" :disabled="form.processing">
                    Reset Password
                </GradientButton>
            </form>
        </div>
    </GuestLayout>
</template>
