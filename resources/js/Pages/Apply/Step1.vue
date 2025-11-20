<script setup lang="ts">
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

const form = useForm({ 
  name: '',
  email: '', 
  password: '',
  password_confirmation: ''
});

const submit = () => {
  form.post(route('apply.register'), {
    onSuccess: () => {
      // Will redirect to email verification
    }
  });
};

const loginWithGoogle = () => {
  window.location.href = route('auth.google');
};
</script>

<template>
  <Head title="Apply Now - Registration" />
  <GuestLayout>
    <div class="mx-auto max-w-lg py-8 px-4 sm:px-6 lg:px-8">
      <div class="rounded-xl border border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-6">
        <div class="mb-4 text-base font-semibold text-gray-900 dark:text-white">Step 1: Create Your Account</div>
        <form @submit.prevent="submit" class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-slate-300">Full Name</label>
            <input 
              v-model="form.name" 
              type="text" 
              required 
              class="mt-1 w-full rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 p-2 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-slate-400 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500" 
              placeholder="Enter your full name"
            />
            <div v-if="form.errors.name" class="text-red-500 text-sm mt-1">{{ form.errors.name }}</div>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-slate-300">Email Address</label>
            <input 
              v-model="form.email" 
              type="email" 
              required 
              class="mt-1 w-full rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 p-2 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-slate-400 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500" 
              placeholder="Enter your email"
            />
            <div v-if="form.errors.email" class="text-red-500 text-sm mt-1">{{ form.errors.email }}</div>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-slate-300">Password</label>
            <input 
              v-model="form.password" 
              type="password" 
              required 
              class="mt-1 w-full rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 p-2 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-slate-400 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500" 
              placeholder="Create a password"
            />
            <div v-if="form.errors.password" class="text-red-500 text-sm mt-1">{{ form.errors.password }}</div>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-slate-300">Confirm Password</label>
            <input 
              v-model="form.password_confirmation" 
              type="password" 
              required 
              class="mt-1 w-full rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 p-2 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-slate-400 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500" 
              placeholder="Confirm your password"
            />
          </div>
          <button 
            type="submit" 
            :disabled="form.processing"
            class="w-full rounded-lg bg-indigo-600 px-4 py-2 text-white hover:bg-indigo-700 disabled:opacity-50"
          >
            {{ form.processing ? 'Creating Account...' : 'Create Account & Continue' }}
          </button>
        </form>

        <!-- Divider -->
        <div class="relative my-6">
          <div class="absolute inset-0 flex items-center">
            <div class="w-full border-t border-gray-300 dark:border-slate-600"></div>
          </div>
          <div class="relative flex justify-center text-sm">
            <span class="px-2 bg-white dark:bg-slate-800 text-gray-500 dark:text-slate-400">OR</span>
          </div>
        </div>

        <!-- Google Login Button -->
        <button 
          type="button"
          @click="loginWithGoogle"
          class="w-full flex items-center justify-center gap-2 px-4 py-2 rounded-lg bg-white dark:bg-slate-700 border border-gray-300 dark:border-slate-600 text-gray-700 dark:text-slate-300 hover:bg-gray-50 dark:hover:bg-slate-600 transition-colors"
        >
          <svg width="18" height="18" fill="currentColor" viewBox="0 0 18 18">
            <path d="M17.64 9.2c0-.637-.057-1.251-.164-1.84H9v3.481h4.844c-.209 1.125-.843 2.078-1.796 2.717v2.258h2.908c1.702-1.567 2.684-3.874 2.684-6.616z" fill="#4285F4"/>
            <path d="M9 18c2.43 0 4.467-.806 5.96-2.184l-2.908-2.258c-.806.54-1.837.86-3.052.86-2.347 0-4.33-1.584-5.04-3.71H.957v2.332C2.438 15.983 5.482 18 9 18z" fill="#34A853"/>
            <path d="M3.96 10.71c-.18-.54-.282-1.117-.282-1.71s.102-1.17.282-1.71V4.958H.957C.347 6.173 0 7.55 0 9s.348 2.827.957 4.042l3.003-2.332z" fill="#FBBC05"/>
            <path d="M9 3.58c1.321 0 2.508.454 3.44 1.345l2.582-2.58C13.463.891 11.426 0 9 0 5.482 0 2.438 2.017.957 4.958L3.96 7.29C4.67 5.163 6.653 3.58 9 3.58z" fill="#EA4335"/>
          </svg>
          <span>Continue with Google</span>
        </button>
      </div>
    </div>
  </GuestLayout>
</template>
