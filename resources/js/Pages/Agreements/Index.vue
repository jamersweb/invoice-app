<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, Link, usePage } from '@inertiajs/vue3';
import GradientButton from '@/Components/GradientButton.vue';
import { ref } from 'vue';

const props = defineProps<{ 
    templates: Array<{id:number, name:string, version:string}>, 
    agreements: Array<{
        id: number, 
        agreement_template_id: number, 
        version: string, 
        status: string, 
        signed_at: string | null,
        signed_pdf: string | null,
        document_type?: string,
        template?: { id: number, name: string }
    }> 
}>();

const form = useForm({ 
    agreement_id: null as number | null,
    template_id: null as number | null 
});

const selectedAgreementToSign = ref<any>(null);

function initiateSign(agreement: any) {
    selectedAgreementToSign.value = agreement;
    form.agreement_id = agreement.id;
    form.template_id = null;
}

function submitSign() {
    form.post(route('agreements.sign'), {
        onSuccess: () => {
            selectedAgreementToSign.value = null;
        }
    });
}

function getStatusClass(status: string) {
    switch (status.toLowerCase()) {
        case 'signed': return 'bg-green-500/10 text-green-500 border-green-500/20';
        case 'sent': return 'bg-blue-500/10 text-blue-500 border-blue-500/20';
        case 'draft': return 'bg-slate-500/10 text-slate-400 border-slate-500/20';
        default: return 'bg-slate-500/10 text-slate-400 border-slate-500/20';
    }
}
</script>

<template>
    <Head title="Agreements & Contracts" />

    <AuthenticatedLayout>
        <div class="max-w-6xl mx-auto space-y-8 py-4">
            <!-- Header -->
            <div class="flex justify-between items-end">
                <div>
                    <h1 class="text-3xl font-bold text-white tracking-tight">Agreements & Contracts</h1>
                    <p class="text-slate-400 mt-2">Manage your legal documents and sign pending contracts.</p>
                </div>
            </div>

            <!-- Main Content Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                
                <!-- Left Column: Pending Actions / Agreements List -->
                <div class="lg:col-span-2 space-y-6">
                    <div class="glass-card p-6 border border-slate-700/50 rounded-3xl bg-slate-800/40 backdrop-blur-xl">
                        <h2 class="text-xl font-semibold text-white mb-6 flex items-center gap-2">
                            <span class="p-2 bg-indigo-500/10 rounded-lg text-indigo-400">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                            </span>
                            Your Agreements
                        </h2>

                        <div v-if="props.agreements.length > 0" class="space-y-4">
                            <div v-for="agreement in props.agreements" :key="agreement.id" 
                                class="flex items-center justify-between p-4 rounded-2xl border border-slate-700/30 bg-slate-900/40 hover:bg-slate-800/60 transition-all group"
                            >
                                <div class="flex items-center gap-4">
                                    <div class="w-12 h-12 rounded-xl bg-slate-800 flex items-center justify-center text-2xl group-hover:scale-110 transition-transform">
                                        {{ agreement.document_type === 'Cheque' ? '🏦' : '📄' }}
                                    </div>
                                    <div>
                                        <h3 class="font-bold text-white">{{ agreement.template?.name || agreement.document_type || 'Agreement' }}</h3>
                                        <div class="flex items-center gap-2 mt-1">
                                            <span class="text-xs text-slate-500">v{{ agreement.version || '1.0' }}</span>
                                            <span class="w-1 h-1 bg-slate-700 rounded-full"></span>
                                            <span :class="['text-[10px] uppercase font-bold px-2 py-0.5 rounded-full border', getStatusClass(agreement.status)]">
                                                {{ agreement.status }}
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <div class="flex items-center gap-3">
                                    <template v-if="agreement.status.toLowerCase() === 'sent'">
                                        <GradientButton size="sm" @click="initiateSign(agreement)">
                                            Review & Sign
                                        </GradientButton>
                                    </template>
                                    <template v-else-if="agreement.status.toLowerCase() === 'signed' || agreement.signed_pdf">
                                        <a v-if="agreement.signed_pdf" :href="'/storage/' + agreement.signed_pdf" target="_blank"
                                            class="p-2.5 rounded-xl bg-slate-800 text-slate-400 hover:text-white border border-slate-700 hover:border-slate-500 transition-all"
                                            title="Download Signed PDF"
                                        >
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a2 2 0 002 2h12a2 2 0 002-2v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                            </svg>
                                        </a>
                                        <span v-if="agreement.signed_at" class="text-sm text-slate-500 italic">Signed on {{ new Date(agreement.signed_at).toLocaleDateString() }}</span>
                                    </template>
                                </div>
                            </div>
                        </div>
                        <div v-else class="text-center py-12">
                            <div class="text-4xl mb-4">📭</div>
                            <p class="text-slate-500">No agreements found at the moment.</p>
                        </div>
                    </div>
                </div>

                <!-- Right Column: Info/Help -->
                <div class="space-y-6">
                    <div class="glass-card p-6 border border-slate-700/50 rounded-3xl bg-indigo-500/5 backdrop-blur-xl">
                        <h2 class="text-lg font-bold text-white mb-4">Why sign?</h2>
                        <ul class="space-y-4">
                            <li class="flex gap-3 text-sm text-slate-400">
                                <span class="text-indigo-400">✔</span>
                                Required for invoice financing approval.
                            </li>
                            <li class="flex gap-3 text-sm text-slate-400">
                                <span class="text-indigo-400">✔</span>
                                Legal protection for both parties.
                            </li>
                            <li class="flex gap-3 text-sm text-slate-400">
                                <span class="text-indigo-400">✔</span>
                                Instant digital signature process.
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal for Signing -->
        <div v-if="selectedAgreementToSign" class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6">
            <div class="absolute inset-0 bg-slate-950/80 backdrop-blur-sm" @click="selectedAgreementToSign = null"></div>
            
            <div class="relative w-full max-w-2xl bg-slate-900 border border-slate-700 p-8 rounded-3xl shadow-2xl animate-in fade-in zoom-in duration-300">
                <div class="flex justify-between items-start mb-6">
                    <div>
                        <h3 class="text-2xl font-bold text-white">Review & Sign Agreement</h3>
                        <p class="text-slate-400 mt-1">Please confirm the details before signing.</p>
                    </div>
                    <button @click="selectedAgreementToSign = null" class="text-slate-500 hover:text-white transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="bg-slate-800/50 border border-slate-700/50 rounded-2xl p-6 mb-8 max-h-[40vh] overflow-y-auto custom-scrollbar">
                    <div class="prose prose-invert max-w-none">
                        <p class="text-slate-300">
                            By signing this agreement, you acknowledge and agree to the terms and conditions outlined in the 
                            <strong>{{ selectedAgreementToSign.template?.name }} (v{{ selectedAgreementToSign.version }})</strong>.
                        </p>
                        <p class="text-slate-400 text-sm mt-4">
                            [Terms summary placeholder... Detailed PDF will be generated upon signing.]
                        </p>
                    </div>
                </div>

                <div class="flex flex-col sm:flex-row gap-4">
                    <GradientButton 
                        class="flex-1" 
                        @click="submitSign" 
                        :disabled="form.processing"
                    >
                        {{ form.processing ? 'Processing signature...' : 'Sign Agreement Digitally' }}
                    </GradientButton>
                    <button 
                        @click="selectedAgreementToSign = null" 
                        class="px-8 py-3 bg-slate-800 text-slate-300 rounded-xl font-bold hover:bg-slate-700 transition-colors"
                    >
                        Cancel
                    </button>
                </div>
                
                <p class="text-[10px] text-slate-500 mt-6 text-center italic">
                    Note: Your IP address ({{ (usePage().props as any).auth?.ip || 'logged' }}) and timestamp will be recorded for audit purposes.
                </p>
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
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #334155;
    border-radius: 10px;
}
</style>
