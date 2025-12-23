<template>
  <Head title="Chat Support" />
  <AuthenticatedLayout>
    <div class="h-[calc(100vh-140px)] flex bg-slate-900/50 rounded-2xl border border-slate-700/50 overflow-hidden backdrop-blur-sm">
      
      <!-- Conversations Sidebar -->
      <div class="w-80 border-r border-slate-700/50 flex flex-col bg-slate-900/40">
        <div class="p-4 border-b border-slate-700/50">
          <h2 class="text-xl font-bold text-white flex items-center gap-2">
            <span class="p-2 bg-purple-600/20 rounded-lg">
              <svg class="w-5 h-5 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
              </svg>
            </span>
            Messages
          </h2>
        </div>

        <div class="flex-1 overflow-y-auto custom-scrollbar p-2 space-y-1">
          <button 
            v-for="chat in conversations" 
            :key="chat.id"
            @click="selectConversation(chat)"
            class="w-full text-left p-3 rounded-xl transition-all duration-200 flex items-center gap-3 border"
            :class="selectedId === chat.id 
              ? 'bg-purple-600/20 border-purple-500/50 text-white' 
              : 'border-transparent text-slate-400 hover:bg-slate-800/50'"
          >
            <div class="h-10 w-10 rounded-full bg-slate-800 flex items-center justify-center ring-2 ring-slate-700">
              {{ (isAdmin ? chat.customer.name : chat.admin.name).charAt(0) }}
            </div>
            <div class="flex-1 min-w-0">
              <div class="flex justify-between items-center mb-0.5">
                <span class="font-semibold truncate">{{ isAdmin ? chat.customer.name : chat.admin.name }}</span>
                <span class="text-[10px] opacity-60">{{ formatTime(chat.last_message_at) }}</span>
              </div>
              <p class="text-xs truncate opacity-70">
                {{ chat.messages?.[0]?.message || 'No messages yet' }}
              </p>
            </div>
          </button>
        </div>
      </div>

      <!-- Chat Window -->
      <div v-if="selectedId" class="flex-1 flex flex-col bg-slate-900/20 relative">
        <!-- Header -->
        <div class="p-4 border-b border-slate-700/50 flex items-center justify-between bg-slate-900/40 backdrop-blur-md">
          <div class="flex items-center gap-3">
            <div class="h-10 w-10 rounded-full bg-purple-600/20 flex items-center justify-center ring-2 ring-purple-500/30">
              <span class="text-purple-400 font-bold">{{ conversationTitle.charAt(0) }}</span>
            </div>
            <div>
              <h3 class="text-white font-bold">{{ conversationTitle }}</h3>
              <p class="text-[10px] text-emerald-400 flex items-center gap-1">
                <span class="h-1.5 w-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                Active Support
              </p>
            </div>
          </div>
        </div>

        <!-- Messages Area -->
        <div class="flex-1 overflow-y-auto p-6 space-y-4 custom-scrollbar" ref="messagesBox">
          <div v-for="msg in activeMessages" :key="msg.id"
            class="flex flex-col"
            :class="msg.sender_id === currentUser.id ? 'items-end' : 'items-start'"
          >
            <div 
              class="max-w-[70%] rounded-2xl p-3 text-sm"
              :class="msg.sender_id === currentUser.id 
                ? 'bg-purple-600 text-white rounded-tr-none shadow-lg shadow-purple-900/20' 
                : 'bg-slate-800 text-slate-200 rounded-tl-none border border-slate-700/50'"
            >
              <p>{{ msg.message }}</p>
              
              <!-- Attachments -->
              <div v-if="msg.attachments && msg.attachments.length" class="mt-2 pt-2 border-t border-white/10 space-y-1">
                <a v-for="file in msg.attachments" :key="file.path" :href="file.path" target="_blank"
                  class="flex items-center gap-2 p-1.5 rounded bg-black/20 hover:bg-black/30 text-[10px] transition-colors"
                >
                  <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                  </svg>
                  <span class="truncate">{{ file.name }}</span>
                </a>
              </div>
            </div>
            <span class="text-[10px] text-slate-500 mt-1 px-1">
              {{ msg.sender_id === currentUser.id ? 'You' : msg.sender.name }} • {{ formatTime(msg.created_at) }}
            </span>
          </div>
        </div>

        <!-- Input Area -->
        <div class="p-4 bg-slate-900/60 border-t border-slate-700/50">
          <form @submit.prevent="sendMessage" class="flex flex-col gap-2">
            <!-- Attachment Preview (simplified) -->
            <div v-if="selectedFiles.length" class="flex flex-wrap gap-2 mb-2">
              <div v-for="(file, idx) in selectedFiles" :key="idx" 
                class="flex items-center gap-2 bg-slate-800 p-1.5 rounded-lg border border-slate-700"
              >
                <span class="text-[10px] text-slate-300 truncate max-w-[100px]">{{ file.name }}</span>
                <button @click="removeFile(idx)" type="button" class="text-slate-500 hover:text-red-400">
                  <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                  </svg>
                </button>
              </div>
            </div>

            <div class="flex items-end gap-3 bg-slate-800/50 rounded-2xl border border-slate-700 p-2 focus-within:border-purple-500/50 transition-all">
              <button type="button" @click="fileInput?.click()" class="p-2 text-slate-400 hover:text-purple-400 rounded-xl hover:bg-purple-500/10 transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                </svg>
              </button>
              <textarea 
                v-model="newMessage" 
                @keydown.enter.prevent="sendMessage"
                rows="1"
                placeholder="Type your message..."
                class="flex-1 bg-transparent border-none focus:ring-0 text-white placeholder-slate-500 py-2 custom-scrollbar resize-none max-h-32"
              ></textarea>
              <button type="submit" :disabled="sending || (!newMessage.trim() && !selectedFiles.length)"
                class="p-2 bg-purple-600 hover:bg-purple-500 text-white rounded-xl shadow-lg shadow-purple-900/40 disabled:opacity-50 transition-all"
              >
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                </svg>
              </button>
            </div>
            <input type="file" multiple class="hidden" ref="fileInput" @change="handleFileSelect" />
          </form>
        </div>
      </div>

      <!-- No Conversation Selected -->
      <div v-else class="flex-1 flex flex-col items-center justify-center p-12 text-center bg-slate-900/10">
        <div class="w-24 h-24 bg-slate-800/50 rounded-full flex items-center justify-center mb-6 animate-bounce">
          <svg class="w-12 h-12 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
          </svg>
        </div>
        <h3 class="text-xl font-bold text-white mb-2">Select a Conversation</h3>
        <p class="text-slate-500 max-w-xs">Pick a chat from the sidebar to start a secure conversation with our team.</p>
        <button v-if="!conversations.length" @click="startNewChat" :disabled="starting"
          class="mt-6 bg-purple-600 hover:bg-purple-500 text-white px-6 py-2 rounded-xl transition-all disabled:opacity-50">
          {{ starting ? 'Starting...' : 'Start New Chat' }}
        </button>
      </div>

    </div>
  </AuthenticatedLayout>
</template>

<script setup lang="ts">
import { Head, usePage, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { ref, computed, onMounted, nextTick, watch } from 'vue';
import axios from 'axios';

const page = usePage();
const currentUser = computed(() => (page.props.auth as any).user);
const isAdmin = computed(() => (currentUser.value.roles || []).some((r: any) => r.name === 'Admin'));

const props = defineProps<{
  conversations: any[];
  selectedId: number | null;
}>();

const selectedId = ref<number | null>(props.selectedId);
const activeMessages = ref<any[]>([]);
const newMessage = ref('');
const sending = ref(false);
const starting = ref(false);
const selectedFiles = ref<File[]>([]);
const messagesBox = ref<HTMLElement | null>(null);
const fileInput = ref<HTMLInputElement | null>(null);

// Watch for prop changes (e.g. after redirect from start)
watch(() => props.selectedId, (newId) => {
  if (newId) {
    const chat = props.conversations.find(c => c.id === newId);
    if (chat) selectConversation(chat);
  }
});

const conversationTitle = computed(() => {
  if (!selectedId.value) return '';
  const chat = props.conversations.find(c => c.id === selectedId.value);
  if (!chat) return '';
  return isAdmin.value ? chat.customer.name : chat.admin.name;
});

const selectConversation = async (chat: any) => {
  selectedId.value = chat.id;
  await fetchMessages();
  scrollToBottom();
};

const startNewChat = () => {
  starting.value = true;
  router.post(route('chat.start'), {}, {
    onFinish: () => {
      starting.value = false;
    }
  });
};

const fetchMessages = async () => {
  if (!selectedId.value) return;
  const res = await axios.get(route('chat.messages', selectedId.value));
  activeMessages.value = res.data;
};

const handleFileSelect = (e: Event) => {
  const files = (e.target as HTMLInputElement).files;
  if (files) {
    selectedFiles.value.push(...Array.from(files));
  }
};

const removeFile = (idx: number) => {
  selectedFiles.value.splice(idx, 1);
};

const sendMessage = async () => {
  if (!selectedId.value || sending.value) return;
  if (!newMessage.value.trim() && !selectedFiles.value.length) return;

  sending.value = true;
  const formData = new FormData();
  formData.append('message', newMessage.value);
  selectedFiles.value.forEach((file, i) => {
    formData.append(`attachments[${i}]`, file);
  });

  try {
    const res = await axios.post(route('chat.store', selectedId.value), formData);
    activeMessages.value.push(res.data);
    newMessage.value = '';
    selectedFiles.value = [];
    scrollToBottom();
  } catch (err) {
    console.error(err);
  } finally {
    sending.value = false;
  }
};

const scrollToBottom = () => {
  nextTick(() => {
    if (messagesBox.value) {
      messagesBox.value.scrollTop = messagesBox.value.scrollHeight;
    }
  });
};

const formatTime = (ts: string) => {
  return new Date(ts).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
};

onMounted(() => {
  if (selectedId.value) {
    const chat = props.conversations.find(c => c.id === selectedId.value);
    if (chat) selectConversation(chat);
  } else if (props.conversations.length > 0) {
    // Optionally auto-select first
    // selectConversation(props.conversations[0]);
  }
});
</script>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
  width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-track {
  background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
  background: rgba(139, 92, 246, 0.2);
  border-radius: 10px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
  background: rgba(139, 92, 246, 0.4);
}
</style>
