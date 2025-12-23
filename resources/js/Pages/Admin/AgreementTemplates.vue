<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';
import GradientButton from '@/Components/GradientButton.vue';

type Tpl = { id: number, name: string, version: string, effective_from?: string | null, effective_to?: string | null, content: string };
const items = ref<Tpl[]>([]);
const loading = ref(true);
const submitting = ref(false);
const form = ref<Omit<Tpl, 'id'>>({ name: '', version: 'v1', effective_from: '', effective_to: '', content: '' });

async function load() {
  loading.value = true;
  try { 
    const res = await fetch('/admin/api/agreements/templates', { credentials: 'include' }); 
    const js = await res.json(); 
    items.value = js?.data ?? []; 
  } finally { 
    loading.value = false; 
  }
}
onMounted(load);

async function save() {
  if (submitting.value) return;
  submitting.value = true;
  try {
    const res = await fetch('/admin/api/agreements/templates', { 
      method: 'POST', 
      headers: { 'Content-Type': 'application/json' }, 
      credentials: 'include', 
      body: JSON.stringify(form.value) 
    });
    if (res.ok) { 
      form.value = { name: '', version: 'v1', effective_from: '', effective_to: '', content: '' }; 
      await load(); 
    }
  } finally {
    submitting.value = false;
  }
}

async function remove(id: number) { 
  if (!confirm('Are you sure you want to delete this template?')) return;
  await fetch('/admin/api/agreements/templates/' + id, { method: 'DELETE', credentials: 'include' }); 
  await load(); 
}
</script>

<template>
  <Head title="Agreement Templates" />
  <AuthenticatedLayout>
    <div class="mx-auto max-w-7xl py-8 px-4 sm:px-6 lg:px-8 space-y-8">
      <!-- Header -->
      <div class="flex justify-between items-end">
        <div>
          <h1 class="text-3xl font-bold text-white tracking-tight">Agreement Templates</h1>
          <p class="text-slate-400 mt-2">Manage legal agreement versions and their effective periods.</p>
        </div>
      </div>

      <div class="grid grid-cols-1 gap-8 lg:grid-cols-3">
        <!-- List Section -->
        <div class="lg:col-span-2 space-y-6">
          <div class="glass-card p-6 border border-slate-700/50 rounded-3xl bg-slate-800/40 backdrop-blur-xl">
            <h2 class="text-xl font-semibold text-white mb-6 flex items-center gap-2">
              <span class="p-2 bg-indigo-500/10 rounded-lg text-indigo-400">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                </svg>
              </span>
              Saved Templates
            </h2>

            <div v-if="loading" class="py-20 text-center">
                <div class="animate-spin inline-block w-8 h-8 border-4 border-indigo-500 border-t-transparent rounded-full mb-4"></div>
                <p class="text-slate-500">Loading templates...</p>
            </div>
            
            <div v-else class="overflow-hidden">
              <div v-if="items.length > 0" class="overflow-x-auto custom-scrollbar">
                <table class="min-w-full text-left">
                  <thead>
                    <tr class="text-slate-500 text-xs uppercase tracking-wider border-b border-slate-700/50">
                      <th class="pb-3 px-4 font-semibold">Name</th>
                      <th class="pb-3 px-4 font-semibold">Version</th>
                      <th class="pb-3 px-4 font-semibold">Period</th>
                      <th class="pb-3 px-4 h-full"></th>
                    </tr>
                  </thead>
                  <tbody class="divide-y divide-slate-700/30">
                    <tr v-for="t in items" :key="t.id" class="hover:bg-slate-700/20 transition-colors group">
                      <td class="py-4 px-4">
                        <div class="font-medium text-white truncate max-w-[200px]" :title="t.name">{{ t.name }}</div>
                      </td>
                      <td class="py-4 px-4">
                        <span class="px-2 py-1 rounded-md bg-slate-700/50 text-slate-300 text-xs">{{ t.version }}</span>
                      </td>
                      <td class="py-4 px-4 text-slate-400 text-sm">
                        <div class="flex items-center gap-2">
                           <span>{{ (t.effective_from || 'Always') }}</span>
                           <span class="text-slate-600">→</span>
                           <span>{{ (t.effective_to || 'Always') }}</span>
                        </div>
                      </td>
                      <td class="py-4 px-4 text-right">
                        <button class="text-slate-500 hover:text-red-400 transition-colors p-2" @click="remove(t.id)" title="Delete Template">
                          <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                             <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                          </svg>
                        </button>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <div v-else class="text-center py-12">
                <div class="text-4xl mb-4 opacity-20">📜</div>
                <p class="text-slate-500">No templates found. Create your first one on the right.</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Form Section -->
        <div class="space-y-6">
          <div class="glass-card p-6 border border-slate-700/50 rounded-3xl bg-slate-800/40 backdrop-blur-xl">
            <h2 class="text-xl font-semibold text-white mb-6 flex items-center gap-2">
              <span class="p-2 bg-purple-500/10 rounded-lg text-purple-400">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
              </span>
              New Template
            </h2>
            
            <div class="space-y-4">
              <div>
                <label class="block text-sm font-medium text-slate-400 mb-1">Template Name</label>
                <input v-model="form.name" placeholder="e.g. Master Service Agreement" 
                  class="w-full bg-slate-900/50 border border-slate-700/50 rounded-xl px-4 py-2.5 text-white placeholder:text-slate-600 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-none transition-all" 
                />
              </div>
              
              <div class="grid grid-cols-2 gap-4">
                <div>
                  <label class="block text-sm font-medium text-slate-400 mb-1">Version</label>
                  <input v-model="form.version" placeholder="v1, v2.1 etc." 
                    class="w-full bg-slate-900/50 border border-slate-700/50 rounded-xl px-4 py-2.5 text-white placeholder:text-slate-600 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-none transition-all" 
                  />
                </div>
                <div>
                  <label class="block text-sm font-medium text-slate-400 mb-1">Starts On</label>
                  <input type="date" v-model="form.effective_from"
                    class="w-full bg-slate-900/50 border border-slate-700/50 rounded-xl px-4 py-2 text-white focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-none transition-all [color-scheme:dark]" 
                  />
                </div>
              </div>
              
              <div>
                <label class="block text-sm font-medium text-slate-400 mb-1">Ends On (Optional)</label>
                <input type="date" v-model="form.effective_to"
                  class="w-full bg-slate-900/50 border border-slate-700/50 rounded-xl px-4 py-2 text-white focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-none transition-all [color-scheme:dark]" 
                />
              </div>
              
              <div>
                <label class="block text-sm font-medium text-slate-400 mb-1">HTML Content</label>
                <textarea v-model="form.content" rows="10" placeholder="Paste your agreement HTML here..."
                  class="w-full bg-slate-900/50 border border-slate-700/50 rounded-xl px-4 py-3 text-white placeholder:text-slate-600 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-none transition-all font-mono text-xs custom-scrollbar"
                ></textarea>
              </div>
              
              <div class="pt-4">
                <GradientButton class="w-full" @click="save" :disabled="submitting || !form.name || !form.content">
                  {{ submitting ? 'Saving Template...' : 'Save Agreement Template' }}
                </GradientButton>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>

<style scoped>
.glass-card {
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}
.glass-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
}
.custom-scrollbar::-webkit-scrollbar {
    width: 6px;
    height: 6px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #334155;
    border-radius: 10px;
}
</style>
