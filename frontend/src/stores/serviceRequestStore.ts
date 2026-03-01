import { defineStore } from 'pinia'
import { ref } from 'vue'
import serviceRequestService, {
  type ServiceRequest,
  type ServiceRequestFilters,
  type ServiceRequestStats,
  type Message,
  type ServiceRequestStatus,
} from '../services/serviceRequestService'

export const useServiceRequestStore = defineStore('serviceRequest', () => {
  const items = ref<ServiceRequest[]>([])
  const currentItem = ref<ServiceRequest | null>(null)
  const messages = ref<Message[]>([])
  const stats = ref<ServiceRequestStats>({ total: 0, pending: 0, urgent: 0, resolved: 0 })

  const loading = ref(false)
  const messagesLoading = ref(false)
  const error = ref<string | null>(null)

  // Paginação
  const total = ref(0)
  const currentPage = ref(1)
  const lastPage = ref(1)
  const perPage = ref(20)

  // Filtros ativos
  const filters = ref<ServiceRequestFilters>({})

  // ── Ações ──────────────────────────────────────────────────────────────────

  const fetchStats = async () => {
    try {
      stats.value = await serviceRequestService.getStats()
    } catch {
      // silencioso — stats não bloqueia o painel
    }
  }

  const fetchList = async (newFilters?: ServiceRequestFilters) => {
    loading.value = true
    error.value = null
    if (newFilters !== undefined) {
      filters.value = { ...newFilters }
    }
    try {
      const res = await serviceRequestService.list({
        ...filters.value,
        per_page: perPage.value,
        page: currentPage.value,
      })
      items.value = res.data
      total.value = res.total
      lastPage.value = res.last_page
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Erro ao carregar solicitações'
    } finally {
      loading.value = false
    }
  }

  const fetchItem = async (id: number) => {
    loading.value = true
    error.value = null
    currentItem.value = null
    try {
      currentItem.value = await serviceRequestService.get(id)
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Solicitação não encontrada'
    } finally {
      loading.value = false
    }
  }

  const fetchMessages = async (id: number) => {
    messagesLoading.value = true
    try {
      messages.value = await serviceRequestService.getMessages(id)
    } catch {
      messages.value = []
    } finally {
      messagesLoading.value = false
    }
  }

  const assign = async (id: number) => {
    const updated = await serviceRequestService.assign(id)
    _updateInList(updated)
    if (currentItem.value?.id === id) currentItem.value = updated
    return updated
  }

  const updateStatus = async (id: number, status: ServiceRequestStatus, notes?: string) => {
    const updated = await serviceRequestService.updateStatus(id, status, notes)
    _updateInList(updated)
    if (currentItem.value?.id === id) currentItem.value = updated
    await fetchStats()
    return updated
  }

  const sendMessage = async (id: number, content: string) => {
    const msg = await serviceRequestService.sendMessage(id, { content })
    messages.value.push(msg)
    return msg
  }

  const changePage = async (page: number) => {
    currentPage.value = page
    await fetchList()
  }

  const applyFilters = async (newFilters: ServiceRequestFilters) => {
    currentPage.value = 1
    await fetchList(newFilters)
  }

  // ── Privado ────────────────────────────────────────────────────────────────

  const _updateInList = (updated: ServiceRequest) => {
    const idx = items.value.findIndex((i) => i.id === updated.id)
    if (idx !== -1) items.value[idx] = updated
  }

  return {
    items,
    currentItem,
    messages,
    stats,
    loading,
    messagesLoading,
    error,
    total,
    currentPage,
    lastPage,
    perPage,
    filters,
    fetchStats,
    fetchList,
    fetchItem,
    fetchMessages,
    assign,
    updateStatus,
    sendMessage,
    changePage,
    applyFilters,
  }
})
