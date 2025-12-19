<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';

type Rule = { id: number, tenor_min: number, tenor_max: number, amount_min: number, amount_max: number, base_rate: number, vip_adjust: number, is_active: boolean };
const rules = ref<Rule[]>([]);
const loading = ref(true);
const form = ref<Omit<Rule, 'id'>>({ tenor_min: 0, tenor_max: 30, amount_min: 0, amount_max: 1000000, base_rate: 6.0, vip_adjust: -0.5, is_active: true });

async function load() {
  loading.value = true;
  try { const res = await fetch('/admin/api/pricing-rules', { credentials: 'include' }); const js = await res.json(); rules.value = js?.data ?? []; } finally { loading.value = false; }
}
onMounted(load);

async function save() {
  const res = await fetch('/admin/api/pricing-rules', { method: 'POST', headers: { 'Content-Type': 'application/json' }, credentials: 'include', body: JSON.stringify(form.value) });
  if (res.ok) { await load(); }
}
async function toggle(id: number, field: 'is_active') { await fetch('/admin/api/pricing-rules/' + id, { method: 'PUT', headers: { 'Content-Type': 'application/json' }, credentials: 'include', body: JSON.stringify({ [field]: !rules.value.find(r => r.id === id)?.[field] }) }); await load(); }
async function remove(id: number) { await fetch('/admin/api/pricing-rules/' + id, { method: 'DELETE', credentials: 'include' }); await load(); }
</script>

<template>

  <Head title="Pricing Rules" />
  <AuthenticatedLayout>
    <template #header>
      <h2 class="text-2xl font-bold text-gray-900">Pricing Rules</h2>
    </template>
    <div class="mx-auto max-w-7xl py-8 px-4 sm:px-6 lg:px-8">
      <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="rounded-xl border border-gray-200  p-6 lg:col-span-2">
          <div class="mb-4 text-base font-semibold text-gray-900">Rules</div>
          <div v-if="loading" class="py-10 text-center text-sm text-gray-500">Loading…</div>
          <div v-else class="overflow-x-auto custom-scrollbar">
            <table class="min-w-full text-left text-sm">
              <thead>
                <tr class="text-gray-500">
                  <th class="py-2">Tenor</th>
                  <th class="py-2">Amount</th>
                  <th class="py-2">Base %</th>
                  <th class="py-2">VIP adj %</th>
                  <th class="py-2">Active</th>
                  <th class="py-2"></th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="r in rules" :key="r.id" class="border-t">
                  <td class="py-2">{{ r.tenor_min }}-{{ r.tenor_max }} days</td>
                  <td class="py-2">{{ r.amount_min.toLocaleString() }} - {{ r.amount_max.toLocaleString() }}</td>
                  <td class="py-2">{{ r.base_rate }}</td>
                  <td class="py-2">{{ r.vip_adjust }}</td>
                  <td class="py-2"><button class="text-indigo-600 hover:underline" @click="toggle(r.id, 'is_active')">{{
                    r.is_active ? 'Yes' : 'No' }}</button></td>
                  <td class="py-2 text-right"><button class="text-red-600 hover:underline"
                      @click="remove(r.id)">Delete</button></td>
                </tr>
                <tr v-if="rules.length === 0">
                  <td colspan="6" class="py-6 text-center text-gray-500">No rules</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
        <div class="rounded-xl border border-gray-200  p-6">
          <div class="mb-4 text-base font-semibold text-gray-900">Add Rule</div>
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-sm font-medium text-gray-700">Tenor Min</label>
              <input type="number" v-model.number="form.tenor_min"
                class="mt-1 w-full rounded-lg border border-gray-300 p-2" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700">Tenor Max</label>
              <input type="number" v-model.number="form.tenor_max"
                class="mt-1 w-full rounded-lg border border-gray-300 p-2" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700">Amount Min</label>
              <input type="number" v-model.number="form.amount_min"
                class="mt-1 w-full rounded-lg border border-gray-300 p-2" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700">Amount Max</label>
              <input type="number" v-model.number="form.amount_max"
                class="mt-1 w-full rounded-lg border border-gray-300 p-2" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700">Base %</label>
              <input type="number" step="0.001" v-model.number="form.base_rate"
                class="mt-1 w-full rounded-lg border border-gray-300 p-2" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700">VIP Adj %</label>
              <input type="number" step="0.001" v-model.number="form.vip_adjust"
                class="mt-1 w-full rounded-lg border border-gray-300 p-2" />
            </div>
            <div class="col-span-2 flex items-center gap-2">
              <input id="is_active" type="checkbox" v-model="form.is_active" />
              <label for="is_active" class="text-sm text-gray-700">Active</label>
            </div>
            <div class="col-span-2 pt-2">
              <button @click="save"
                class="rounded-lg bg-indigo-600 px-4 py-2 text-white hover:bg-indigo-700">Save</button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
