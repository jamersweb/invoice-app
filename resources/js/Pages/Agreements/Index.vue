<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3'

const props = defineProps<{ templates: Array<{id:number,name:string,version:string}>, agreements: Array<any> }>()
const form = useForm({ template_id: null as number | null })

function sign() {
  if (!form.template_id) return
  form.post(route('agreements.sign'))
}
</script>

<template>
  <Head title="Agreements" />
  <div class="max-w-3xl mx-auto p-6 space-y-6">
    <div>
      <h2 class="text-xl font-semibold mb-2">Sign Agreement</h2>
      <select v-model.number="form.template_id" class="border rounded p-2 mr-2">
        <option :value="null">Choose template</option>
        <option v-for="t in props.templates" :key="t.id" :value="t.id">{{ t.name }} ({{ t.version }})</option>
      </select>
      <button class="bg-blue-600 text-white px-3 py-2 rounded" @click="sign" :disabled="form.processing || !form.template_id">Sign</button>
    </div>

    <div>
      <h2 class="text-xl font-semibold mb-2">Your Agreements</h2>
      <ul class="list-disc pl-6">
        <li v-for="a in props.agreements" :key="a.id">#{{ a.id }} — {{ a.status }} — v{{ a.version }} — {{ a.signed_at ?? '—' }}</li>
        <li v-if="props.agreements.length===0" class="text-gray-500">None yet</li>
      </ul>
    </div>
  </div>
</template>


