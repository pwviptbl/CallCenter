<script setup lang="ts">
import { onMounted, ref, nextTick, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { storeToRefs } from 'pinia'
import { useServiceRequestStore } from '../stores/serviceRequestStore'
import { useAuthStore } from '../stores/authStore'
import type { ServiceRequestStatus } from '../services/serviceRequestService'

const route = useRoute()
const router = useRouter()
const store = useServiceRequestStore()
const authStore = useAuthStore()

const { currentItem, messages, loading, messagesLoading } = storeToRefs(store)
const { user, isAdmin } = storeToRefs(authStore)

const id = Number(route.params.id)

const newMessage = ref('')
const sendingMessage = ref(false)
const updatingStatus = ref(false)
const assigning = ref(false)
const messagesEndRef = ref<HTMLElement | null>(null)

const isMyRequest = computed(
  () => currentItem.value?.attendant_id === user.value?.id,
)
const isClosed = computed(() =>
  currentItem.value
    ? ['resolved', 'failed'].includes(currentItem.value.status)
    : false,
)

// ── Ciclo de vida ─────────────────────────────────────────────────────────
onMounted(async () => {
  await Promise.all([store.fetchItem(id), store.fetchMessages(id)])
  scrollToBottom()
})

const scrollToBottom = () => {
  nextTick(() => {
    messagesEndRef.value?.scrollIntoView({ behavior: 'smooth' })
  })
}

// ── Ações ──────────────────────────────────────────────────────────────────
const handleAssign = async () => {
  assigning.value = true
  try {
    await store.assign(id)
  } finally {
    assigning.value = false
  }
}

const handleUpdateStatus = async (status: ServiceRequestStatus) => {
  updatingStatus.value = true
  try {
    await store.updateStatus(id, status)
  } finally {
    updatingStatus.value = false
  }
}

const handleSendMessage = async () => {
  const content = newMessage.value.trim()
  if (!content || sendingMessage.value) return
  sendingMessage.value = true
  try {
    await store.sendMessage(id, content)
    newMessage.value = ''
    scrollToBottom()
  } finally {
    sendingMessage.value = false
  }
}

const onEnterKey = (e: KeyboardEvent) => {
  if (!e.shiftKey) {
    e.preventDefault()
    handleSendMessage()
  }
}

// ── Helpers visuais ────────────────────────────────────────────────────────
const urgencyClass: Record<string, string> = {
  critical: 'bg-red-100 text-red-800 border border-red-300',
  urgent: 'bg-yellow-100 text-yellow-800 border border-yellow-300',
  normal: 'bg-gray-100 text-gray-600 border border-gray-200',
}

const urgencyLabel: Record<string, string> = {
  critical: '🔴 Crítico',
  urgent: '🟡 Urgente',
  normal: '⚪ Normal',
}

const statusLabel: Record<string, string> = {
  pending: 'Pendente',
  ai_collecting: 'Coletando (IA)',
  awaiting_review: 'Aguard. revisão',
  in_progress: 'Em andamento',
  confirmed_manual: 'Confirmado',
  sent_api: 'Enviado API',
  resolved: 'Resolvido',
  failed: 'Falhou',
}

const statusClass: Record<string, string> = {
  pending: 'bg-orange-100 text-orange-700',
  ai_collecting: 'bg-purple-100 text-purple-700',
  awaiting_review: 'bg-blue-100 text-blue-700',
  in_progress: 'bg-indigo-100 text-indigo-700',
  confirmed_manual: 'bg-teal-100 text-teal-700',
  sent_api: 'bg-cyan-100 text-cyan-700',
  resolved: 'bg-green-100 text-green-700',
  failed: 'bg-red-100 text-red-700',
}

const channelLabel: Record<string, string> = {
  whatsapp: '💬 WhatsApp',
  voip: '📞 VoIP',
  manual: '🖊️ Manual',
}

const formatDate = (dateStr: string) => {
  return new Date(dateStr).toLocaleString('pt-BR', {
    day: '2-digit',
    month: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
  })
}

const senderName = (msg: { sender_type: string; sender?: { name: string } | null }) => {
  if (msg.sender_type === 'contact') return currentItem.value?.contact_name ?? 'Contato'
  if (msg.sender_type === 'ai') return '🤖 IA'
  if (msg.sender_type === 'system') return '⚙️ Sistema'
  return msg.sender?.name ?? 'Atendente'
}
</script>

<template>
  <div class="min-h-screen bg-gray-50">
    <!-- ── Cabeçalho ── -->
    <div class="bg-white border-b border-gray-200 px-4 py-3 sticky top-0 z-10">
      <div class="max-w-5xl mx-auto flex items-center gap-3">
        <button
          @click="router.push({ name: 'dashboard' })"
          class="p-2 rounded-lg hover:bg-gray-100 text-gray-600 transition-colors"
        >
          <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
          </svg>
        </button>
        <div v-if="currentItem" class="flex-1 min-w-0">
          <div class="flex items-center gap-2 flex-wrap">
            <h1 class="text-lg font-bold text-gray-900 truncate">{{ currentItem.contact_name }}</h1>
            <span
              :class="urgencyClass[currentItem.urgency_level]"
              class="px-2 py-0.5 rounded-full text-xs font-semibold"
            >
              {{ urgencyLabel[currentItem.urgency_level] }}
            </span>
            <span
              :class="statusClass[currentItem.status]"
              class="px-2 py-0.5 rounded-full text-xs font-medium"
            >
              {{ statusLabel[currentItem.status] }}
            </span>
          </div>
          <p class="text-sm text-gray-500">{{ currentItem.contact_phone }} · {{ channelLabel[currentItem.channel] }}</p>
        </div>
        <div v-if="loading" class="text-gray-400 text-sm">Carregando...</div>
      </div>
    </div>

    <div class="max-w-5xl mx-auto px-4 py-4 grid grid-cols-1 lg:grid-cols-3 gap-4">

      <!-- ── Mensagens (lado esquerdo / principal) ── -->
      <div class="lg:col-span-2 flex flex-col">
        <!-- Thread -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 flex flex-col h-[560px]">
          <div class="flex-1 overflow-y-auto p-4 space-y-3" style="scroll-behavior: smooth;">
            <div v-if="messagesLoading" class="flex justify-center py-10">
              <svg class="w-6 h-6 animate-spin text-indigo-500" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
              </svg>
            </div>

            <div v-else-if="!messages.length" class="flex items-center justify-center h-full text-gray-400 text-sm">
              Nenhuma mensagem ainda.
            </div>

            <div
              v-for="msg in messages"
              :key="msg.id"
              :class="msg.direction === 'outbound' ? 'flex justify-end' : 'flex justify-start'"
            >
              <div
                :class="[
                  'max-w-[75%] rounded-2xl px-4 py-2.5 shadow-sm',
                  msg.direction === 'outbound'
                    ? 'bg-indigo-600 text-white rounded-br-sm'
                    : msg.sender_type === 'ai'
                      ? 'bg-purple-50 text-purple-900 border border-purple-200 rounded-bl-sm'
                      : msg.sender_type === 'system'
                        ? 'bg-gray-100 text-gray-600 text-xs italic rounded-bl-sm'
                        : 'bg-white text-gray-900 border border-gray-200 rounded-bl-sm',
                ]"
              >
                <p class="text-xs font-semibold opacity-70 mb-0.5">{{ senderName(msg) }}</p>
                <p class="text-sm whitespace-pre-wrap">{{ msg.content }}</p>
                <p class="text-xs opacity-50 mt-1 text-right">{{ formatDate(msg.created_at) }}</p>
              </div>
            </div>

            <div ref="messagesEndRef" />
          </div>

          <!-- Input de resposta -->
          <div class="border-t border-gray-100 p-3">
            <div v-if="isClosed" class="text-center text-sm text-gray-400 py-2">
              Solicitação encerrada — respostas desabilitadas.
            </div>
            <div v-else class="flex gap-2">
              <textarea
                v-model="newMessage"
                @keydown.enter="onEnterKey"
                rows="2"
                placeholder="Digite sua resposta... (Enter para enviar, Shift+Enter para nova linha)"
                class="flex-1 resize-none px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500"
              />
              <button
                @click="handleSendMessage"
                :disabled="!newMessage.trim() || sendingMessage"
                class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700 disabled:opacity-40 disabled:cursor-not-allowed transition-colors self-end"
              >
                <svg v-if="sendingMessage" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                </svg>
                <svg v-else class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                </svg>
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- ── Painel lateral ── -->
      <div class="space-y-4">
        <div v-if="!currentItem && loading" class="bg-white rounded-xl p-6 text-center text-gray-400 text-sm">
          Carregando...
        </div>

        <template v-if="currentItem">
          <!-- Info do contato -->
          <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 space-y-3">
            <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wide">Contato</h2>
            <div class="space-y-1.5 text-sm">
              <div class="flex justify-between">
                <span class="text-gray-500">Nome</span>
                <span class="font-medium text-gray-900">{{ currentItem.contact_name }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-gray-500">Telefone</span>
                <span class="font-medium text-gray-900">{{ currentItem.contact_phone }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-gray-500">Canal</span>
                <span class="font-medium text-gray-900">{{ channelLabel[currentItem.channel] }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-gray-500">Criado</span>
                <span class="font-medium text-gray-900">{{ formatDate(currentItem.created_at) }}</span>
              </div>
              <div v-if="currentItem.attended_at" class="flex justify-between">
                <span class="text-gray-500">Atendido</span>
                <span class="font-medium text-gray-900">{{ formatDate(currentItem.attended_at) }}</span>
              </div>
              <div v-if="currentItem.resolved_at" class="flex justify-between">
                <span class="text-gray-500">Resolvido</span>
                <span class="font-medium text-green-700">{{ formatDate(currentItem.resolved_at) }}</span>
              </div>
            </div>
          </div>

          <!-- Keywords detectadas -->
          <div
            v-if="currentItem.urgency_keywords?.length"
            class="bg-red-50 rounded-xl border border-red-200 p-4"
          >
            <h2 class="text-xs font-semibold text-red-700 uppercase tracking-wide mb-2">Keywords detectadas</h2>
            <div class="flex flex-wrap gap-1">
              <span
                v-for="kw in currentItem.urgency_keywords"
                :key="kw"
                class="px-2 py-0.5 bg-red-100 text-red-700 rounded text-xs font-medium"
              >{{ kw }}</span>
            </div>
          </div>

          <!-- Ações -->
          <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 space-y-3">
            <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wide">Ações</h2>

            <!-- Atribuir a mim -->
            <button
              v-if="!currentItem.attendant_id && !isClosed"
              @click="handleAssign"
              :disabled="assigning"
              class="w-full py-2 px-3 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 disabled:opacity-50 transition-colors"
            >
              {{ assigning ? 'Atribuindo...' : 'Atribuir a mim' }}
            </button>

            <!-- Atendente atual -->
            <div v-if="currentItem.attendant" class="text-sm text-gray-600">
              👤 Atendente: <strong>{{ currentItem.attendant.name }}</strong>
            </div>

            <!-- Alterar status -->
            <div v-if="!isClosed" class="space-y-2">
              <p class="text-xs text-gray-500 font-medium">Alterar status:</p>
              <div class="grid grid-cols-2 gap-2">
                <button
                  v-if="currentItem.status !== 'in_progress'"
                  @click="handleUpdateStatus('in_progress')"
                  :disabled="updatingStatus"
                  class="py-1.5 px-2 text-xs font-medium bg-indigo-50 text-indigo-700 rounded-lg hover:bg-indigo-100 disabled:opacity-50 transition-colors"
                >
                  Em andamento
                </button>
                <button
                  v-if="currentItem.status !== 'awaiting_review'"
                  @click="handleUpdateStatus('awaiting_review')"
                  :disabled="updatingStatus"
                  class="py-1.5 px-2 text-xs font-medium bg-blue-50 text-blue-700 rounded-lg hover:bg-blue-100 disabled:opacity-50 transition-colors"
                >
                  Aguard. revisão
                </button>
                <button
                  @click="handleUpdateStatus('resolved')"
                  :disabled="updatingStatus"
                  class="py-1.5 px-2 text-xs font-medium bg-green-50 text-green-700 rounded-lg hover:bg-green-100 disabled:opacity-50 transition-colors"
                >
                  ✓ Resolver
                </button>
                <button
                  v-if="isAdmin"
                  @click="handleUpdateStatus('failed')"
                  :disabled="updatingStatus"
                  class="py-1.5 px-2 text-xs font-medium bg-red-50 text-red-700 rounded-lg hover:bg-red-100 disabled:opacity-50 transition-colors"
                >
                  ✗ Falhou
                </button>
              </div>
            </div>

            <!-- Status encerrado -->
            <div v-if="isClosed" class="text-sm text-center py-2">
              <span :class="statusClass[currentItem.status]" class="px-3 py-1 rounded-full text-xs font-semibold">
                {{ statusLabel[currentItem.status] }}
              </span>
            </div>
          </div>

          <!-- Notas -->
          <div v-if="currentItem.notes" class="bg-yellow-50 rounded-xl border border-yellow-200 p-4">
            <h2 class="text-xs font-semibold text-yellow-700 uppercase tracking-wide mb-1">Notas</h2>
            <p class="text-sm text-yellow-900">{{ currentItem.notes }}</p>
          </div>
        </template>
      </div>

    </div>
  </div>
</template>
