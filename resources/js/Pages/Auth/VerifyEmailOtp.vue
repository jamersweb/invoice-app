<script setup lang="ts">
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import GradientButton from '@/Components/GradientButton.vue';
import DarkInput from '@/Components/DarkInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps<{
    status?: string;
    otp?: string;
}>();

const form = useForm({
    otp: '',
});

const otpDigits = ref(['', '', '', '', '', '']);
const digitInputs = ref<HTMLInputElement[]>([]);

const handleInput = (index: number, event: Event) => {
    const input = event.target as HTMLInputElement;
    const value = input.value;
    
    // Only allow digits
    if (!/^\d*$/.test(value)) {
        otpDigits.value[index] = '';
        return;
    }

    // Handle single digit input
    if (value.length > 1) {
        otpDigits.value[index] = value.slice(-1);
    }

    // Move to next input
    if (value && index < 5) {
        digitInputs.value[index + 1]?.focus();
    }

    updateOtpValue();
};

const handleKeydown = (index: number, event: KeyboardEvent) => {
    if (event.key === 'Backspace' && !otpDigits.value[index] && index > 0) {
        digitInputs.value[index - 1]?.focus();
    }
};

const handlePaste = (event: ClipboardEvent) => {
    event.preventDefault();
    const pastedData = event.clipboardData?.getData('text').slice(0, 6) || '';
    const digits = pastedData.split('').filter(char => /^\d$/.test(char));
    
    digits.forEach((digit, index) => {
        if (index < 6) {
            otpDigits.value[index] = digit;
        }
    });

    updateOtpValue();
    
    // Focus appropriate input
    const nextIndex = Math.min(digits.length, 5);
    digitInputs.value[nextIndex]?.focus();
};

const updateOtpValue = () => {
    form.otp = otpDigits.value.join('');
};

const verificationLinkSent = computed(() => props.status === 'verification-link-sent');

const submit = () => {
    if (form.otp.length !== 6) return;
    form.post(route('verification.otp.verify'));
};
</script>

<template>
    <GuestLayout>
        <Head title="Email Verification" />

        <div class="space-y-6">
            <!-- Header -->
            <div class="text-center mb-6">
                <h2 class="text-2xl font-bold text-dark-text-primary mb-2">Verify Your Email</h2>
                <p class="text-sm text-dark-text-secondary">
                    Thanks for signing up! Before getting started, could you verify your email address by entering the 6-digit code we just emailed to you? 
                </p>
            </div>

            <div v-if="verificationLinkSent" class="mb-4 text-sm font-medium text-green-400 text-center bg-green-500/20 border border-green-500/30 rounded-lg p-3">
                A new verification code has been sent to the email address you provided during registration.
            </div>

            <!-- Local Development OTP Display -->
            <div v-if="otp" class="mb-6 p-4 bg-purple-500/10 border border-purple-500/30 rounded-lg text-center">
                <p class="text-xs text-purple-300 uppercase tracking-wider font-semibold mb-1">Local Development OTP</p>
                <p class="text-2xl font-mono font-bold text-white tracking-widest">{{ otp }}</p>
            </div>

            <form @submit.prevent="submit" class="space-y-8">
                <div>
                    <div class="flex justify-between gap-2 sm:gap-4 max-w-sm mx-auto">
                        <template v-for="(_, index) in 6" :key="index">
                            <input
                                :ref="el => { if (el) digitInputs[index] = el as HTMLInputElement }"
                                v-model="otpDigits[index]"
                                type="text"
                                inputmode="numeric"
                                maxlength="1"
                                class="w-10 h-12 sm:w-12 sm:h-14 text-center text-xl font-bold rounded-lg bg-dark-secondary border border-dark-border text-white focus:border-purple-accent focus:ring-1 focus:ring-purple-accent transition-all duration-200 outline-none shadow-lg"
                                @input="handleInput(index, $event)"
                                @keydown="handleKeydown(index, $event)"
                                @paste="handlePaste"
                                :autofocus="index === 0"
                            />
                        </template>
                    </div>
                    <InputError class="mt-4 text-center" :message="form.errors.otp" />
                </div>

                <GradientButton type="submit" class="w-full h-12 text-lg" :disabled="form.processing || form.otp.length !== 6">
                    Verify Email
                </GradientButton>
            </form>

            <div class="flex items-center justify-between mt-6">
                <Link
                    :href="route('verification.otp.resend')"
                    method="post"
                    as="button"
                    class="text-sm text-purple-accent hover:text-purple-hover font-medium transition-colors"
                >
                    Resend Code
                </Link>

                <Link
                    :href="route('logout')"
                    method="post"
                    as="button"
                    class="text-sm text-dark-text-secondary hover:text-dark-text-primary transition-colors"
                >
                    Log Out
                </Link>
            </div>
        </div>
    </GuestLayout>
</template>
