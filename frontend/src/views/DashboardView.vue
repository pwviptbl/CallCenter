<script setup lang="ts">
import { onMounted, onUnmounted, ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import { storeToRefs } from 'pinia'
import { useServiceRequestStore } from '../stores/serviceRequestStore'
import { useAuthStore } from '../stores/authStore'
import { useEcho } from '../composables/useEcho'
import type { ServiceRequestStatus, UrgencyLevel, Channel } from '../services/serviceRequestService'

const router = useRouter()
const store = useServiceRequestStore()
const authStore = useAuthStore()
const echo = useEcho()

const { items, stats, loading, total, currentPage, lastPage } = storeToRefs(store)
const { user } = storeToRefs(authStore)

// Badge de evento em tempo real
const realtimeFlash = ref(false)
let flashTimer: ReturnType<typeof setTimeout>

const flashBadge = () => {
  realtimeFlash.value = true
  clearTimeout(flashTimer)
  flashTimer = setTimeout(() => (realtimeFlash.value = false), 3000)
}

// ── Filtros ────────────────────────────────────────────────────────────────
const searchText = ref('')
const filterStatus = ref<ServiceRequestStatus | ''>('')
const filterUrgency = ref<UrgencyLevel | ''>('')
const filterChannel = ref<Channel | ''>('')

let searchTimeout: ReturnType<typeof setTimeout>

const applyFilters = () => {
  store.applyFilters({
    search: searchText.value || undefined,
    status: filterStatus.value || undefined,
    urgency_level: filterUrgency.value || undefined,
    channel: filterChannel.value || undefined,
  })
}

const onSearchInput = () => {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(applyFilters, 400)
}

const clearFilters = () => {
  searchText.value = ''
  filterStatus.value = ''
  filterUrgency.value = ''
  filterChannel.value = ''
  store.applyFilters({})
}

const hasFilters = computed(
  () => searchText.value || filterStatus.value || filterUrgency.value || filterChannel.value,
)

// ── Ciclo de vida ──────────────────────────────────────────────────────────
onMounted(async () => {
  await Promise.all([store.fetchStats(), store.fetchList()])
  subscribeRealtime()
})

onUnmounted(() => {
  if (user.value?.company_id) {
    echo.leaveChannel(`company.${user.value.company_id}`)
  }
})

const subscribeRealtime = () => {
  const companyId = user.value?.company_id
  if (!companyId) return

  try {
    echo.channel(`company.${companyId}`)
      .listen('.service-request.created', () => {
        store.fetchStats()
        store.fetchList()
        flashBadge()
      })
      .listen('.service-request.updated', (payload: any) => {
        store.items.splice(
          store.items.findIndex((i) => i.id === payload.id),
          1,
          { ...store.items.find((i) => i.id === payload.id)!, ...payload },
        )
        store.fetchStats()
        flashBadge()
      })
      .listen('.service-request.escalated', () => {
        store.fetchStats()
        store.fetchList()
        flashBadge()
      })
      .listen('.service-request.message', () => {
        flashBadge()
      })
  } catch (e) {
    // Reverb não disponível — modo offline, polling manual apenas
    console.warn('[WS] Reverb indisponível, modo manual.')
  }
}

// ── Helpers visuais ────────────────────────────────────────────────────────
const urgencyLabel: Record<string, string> = {
  critical: 'Crítico',
  urgent: 'Urgente',
  normal: 'Normal',
}

const urgencyClass: Record<string, string> = {
  critical: 'bg-red-100 text-red-800 ring-1 ring-red-500',
  urgent: 'bg-yellow-100 text-yellow-800 ring-1 ring-yellow-400',
  normal: 'bg-gray-100 text-gray-700',
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

const channelIcon: Record<string, string> = {
  whatsapp: '💬',
  voip: '📞',
  manual: '🖊️',
}

const timeAgo = (dateStr: string): string => {
  const diff = Math.floor((Date.now() - new Date(dateStr).getTime()) / 1000)
  if (diff < 60) return `${diff}s`
  if (diff < 3600) return `${Math.floor(diff / 60)}min`
  if (diff < 86400) return `${Math.floor(diff / 3600)}h`
  return `${Math.floor(diff / 86400)}d`
}

const openDetail = (id: number) => router.push({ name: 'service-request-detail', params: { id } })
</script>

<template>
  <div class="min-h-screen bg-gray-50">
    <!-- ── Cabeçalho da página ── -->
    <div class="bg-white border-b border-gray-200 px-6 py-4">
      <div class="max-w-7xl mx-auto flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">Painel de Atendimento</h1>
          <p class="text-sm text-gray-500 mt-0.5 flex items-center gap-2">
            Solicitações em tempo real
            <span
              :class="realtimeFlash ? 'bg-green-500 animate-ping' : 'bg-green-400'"
              class="inline-block w-2 h-2 rounded-full transition-colors"
              title="Tempo real ativo"
            />
          </p>
        </div>
        <button
          @click="store.fetchStats(); store.fetchList()"
          class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors"
        >
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
          </svg>
          Atualizar
        </button>
      </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 py-6 space-y-6">

      <!-- ── Cards de estatísticas ── -->
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-xl shadow-sm p-5 border border-gray-100">
          <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Total</p>
          <p class="mt-1 text-3xl font-bold text-gray-900">{{ stats.total }}</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-5 border border-orange-100">
          <p class="text-xs font-semibold text-orange-600 uppercase tracking-wider">Pendentes</p>
          <p class="mt-1 text-3xl font-bold text-orange-600">{{ stats.pending }}</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-5 border border-red-100">
          <p class="text-xs font-semibold text-red-600 uppercase tracking-wider">Urgentes</p>
          <p class="mt-1 text-3xl font-bold text-red-600">{{ stats.urgent }}</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-5 border border-green-100">
          <p class="text-xs font-semibold text-green-600 uppercase tracking-wider">Resolvidos</p>
          <p class="mt-1 text-3xl font-bold text-green-600">{{ stats.resolved }}</p>
        </div>
      </div>

      <!-- ── Filtros ── -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
        <div class="flex flex-wrap gap-3 items-end">
          <div class="flex-1 min-w-48">
            <label class="block text-xs font-medium text-gray-600 mb-1">Buscar</label>
            <input
              v-model="searchText"
              @input="onSearchInput"
              type="text"
              placeholder="Nome ou telefone..."
              class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500"
            />
          </div>
          <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Urgência</label>
            <select
              v-model="filterUrgency"
              @change="applyFilters"
              class="px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500"
            >
              <option value="">Todas</option>
              <option value="critical">Crítico</option>
              <option value="urgent">Urgente</option>
              <option value="normal">Normal</option>
            </select>
          </div>
          <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Status</label>
            <select
              v-model="filterStatus"
              @change="applyFilters"
              class="px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500"
            >
              <option value="">Todos</option>
              <option value="pending">Pendente</option>
              <option value="in_progress">Em andamento</option>
              <option value="awaiting_review">Aguard. revisão</option>
              <option value="resolved">Resolvido</option>
              <option value="failed">Falhou</option>
            </select>
          </div>
          <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Canal</label>
            <select
              v-model="filterChannel"
              @change="applyFilters"
              class="px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500"
            >
              <option value="">Todos</option>
              <option value="whatsapp">WhatsApp</option>
              <option value="voip">VoIP</option>
              <option value="manual">Manual</option>
            </select>
          </div>
          <button
            v-if="hasFilters"
            @click="clearFilters"
            class="px-3 py-2 text-sm text-gray-600 hover:text-gray-900 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors"
          >
            Limpar
          </button>
        </div>
      </div>

      <!-- ── Lista de solicitações ── -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <!-- Loading -->
        <div v-if="loading" class="flex justify-center items-center py-20">
          <svg class="w-8 h-8 animate-spin text-indigo-600" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
          </svg>
        </div>

        <!-- Vazio -->
        <div v-else-if="!items.length" class="flex flex-col items-center justify-center py-20 text-gray-400">
          <svg class="w-12 h-12 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
              d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
          </svg>
          <p class="text-sm font-medium">Nenhuma solicitação encontrada</p>
        </div>

        <!-- Tabela -->
        <table v-else class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider w-24">Urgência</th>
              <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Contato</th>
              <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider hidden md:table-cell">Mensagem</th>
              <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider w-36">Status</th>
              <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider hidden sm:table-cell w-32">Atendente</th>
              <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider w-16">Há</th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-100">
            <tr
              v-for="sr in items"
              :key="sr.id"
              @click="openDetail(sr.id)"
              class="hover:bg-indigo-50 cursor-pointer transition-colors"
            >
              <td class="px-4 py-3">
                <span
                  :class="urgencyClass[sr.urgency_level]"
                  class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold"
                >
                  <span v-if="sr.urgency_level === 'critical'" class="w-1.5 h-1.5 bg-red-500 rounded-full animate-pulse" />
                  <span v-else-if="sr.urgency_level === 'urgent'" class="w-1.5 h-1.5 bg-yellow-500 rounded-full" />
                  {{ urgencyLabel[sr.urgency_level] }}
                </span>
              </td>
              <td class="px-4 py-3">
                <div class="flex items-center gap-2">
                  <span class="text-lg">{{ channelIcon[sr.channel] }}</span>
                  <div>
                    <p class="text-sm font-semibold text-gray-900">{{ sr.contact_name }}</p>
                    <p class="text-xs text-gray-500">{{ sr.contact_phone }}</p>
                  </div>
                </div>
              </td>
              <td class="px-4 py-3 hidden md:table-cell max-w-xs">
                <p class="text-sm text-gray-600 truncate">{{ sr.contact_message }}</p>
                <div v-if="sr.urgency_keywords?.length" class="flex gap-1 mt-1 flex-wrap">
                  <span
                    v-for="kw in sr.urgency_keywords.slice(0, 3)"
                    :key="kw"
                    class="px-1.5 py-0.5 bg-red-50 text-red-600 text-xs rounded"
                  >{{ kw }}</span>
                </div>
              </td>
              <td class="px-4 py-3">
                <span
                  :class="statusClass[sr.status]"
                  class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium"
                >
                  {{ statusLabel[sr.status] }}
                </span>
              </td>
              <td class="px-4 py-3 hidden sm:table-cell">
                <span v-if="sr.attendant" class="text-sm text-gray-700">{{ sr.attendant.name }}</span>
                <span v-else class="text-xs text-gray-400 italic">Nenhum</span>
              </td>
              <td class="px-4 py-3 text-xs text-gray-400 whitespace-nowrap">
                {{ timeAgo(sr.created_at) }}
              </td>
            </tr>
          </tbody>
        </table>

        <!-- Paginação -->
        <div v-if="lastPage > 1" class="flex items-center justify-between px-6 py-3 border-t border-gray-100">
          <p class="text-sm text-gray-600">
            Total: <strong>{{ total }}</strong> solicitações
          </p>
          <div class="flex gap-1">
            <button
              v-for="page in lastPage"
              :key="page"
              @click.stop="store.changePage(page)"
              :class="[
                'px-3 py-1 text-sm rounded-lg transition-colors',
                page === currentPage
                  ? 'bg-indigo-600 text-white'
                  : 'text-gray-600 hover:bg-gray-100'
              ]"
            >{{ page }}</button>
          </div>
        </div>
      </div>

    </div>
  </div>
</template>
