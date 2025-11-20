<script setup lang="ts">
import { Head } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { route } from 'ziggy-js'

const props = defineProps<{ 
  token: string | null,
  email: string | null,
  verified: boolean
}>()
</script>

<template>
  <Head title="Verify Your Email" />
  <AuthenticatedLayout>
    <template #header>
      <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Verify Your Email</h2>
    </template>
    <div class="mx-auto max-w-lg py-8 px-4 sm:px-6 lg:px-8">
      <div class="rounded-xl border border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-6">
        <div v-if="!verified" class="space-y-4">
          <div class="text-sm text-gray-700 dark:text-slate-300">
            We've sent a verification code to <strong>{{ email }}</strong>. 
            Please check your email and click the verification link to continue.
          </div>
          <div class="text-sm text-gray-600 dark:text-slate-400">
            If you didn't receive the email, check your spam folder or click below to resend.
          </div>
          <div class="flex gap-3">
            <a 
              :href="route('apply.verify', { token })" 
              class="rounded-lg bg-indigo-600 px-4 py-2 text-sm text-white hover:bg-indigo-700"
            >
              Verify Email
            </a>
            <a 
              :href="route('verification.send')" 
              class="rounded-lg bg-gray-200 dark:bg-slate-700 px-4 py-2 text-sm text-gray-700 dark:text-white hover:bg-gray-300 dark:hover:bg-slate-600"
            >
              Resend Email
            </a>
          </div>
        </div>
        <div v-else class="space-y-4">
          <div class="text-green-600 dark:text-green-400 font-semibold">
            ✓ Email verified successfully!
          </div>
          <div class="text-sm text-gray-700 dark:text-slate-300">
            You can now proceed to complete your KYC/KYB onboarding.
          </div>
          <a 
            :href="route('onboarding.kyc')" 
            class="inline-block rounded-lg bg-indigo-600 px-4 py-2 text-sm text-white hover:bg-indigo-700"
          >
            Continue to KYC/KYB
          </a>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
