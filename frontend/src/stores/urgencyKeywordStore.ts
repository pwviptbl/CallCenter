import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import urgencyKeywordService, { 
  type UrgencyKeyword, 
  type UrgencyKeywordFilters, 
  type UrgencyKeywordListResponse,
  type AnalyzeResult,
  type TestResult
} from '@/services/urgencyKeywordService'

export const useUrgencyKeywordStore = defineStore('urgencyKeyword', () => {
  // State
  const keywords = ref<UrgencyKeyword[]>([])
  const currentKeyword = ref<UrgencyKeyword | null>(null)
  const loading = ref(false)
  const error = ref<string | null>(null)
  const pagination = ref({
    currentPage: 1,
    lastPage: 1,
    perPage: 15,
    total: 0
  })

  // Getters
  const activeKeywords = computed(() => 
    keywords.value.filter(k => k.active && !k.deleted_at)
  )

  const globalKeywords = computed(() =>
    keywords.value.filter(k => k.company_id === null)
  )

  // Actions
  async function fetchKeywords(filters: UrgencyKeywordFilters = {}) {
    loading.value = true
    error.value = null
    try {
      const response: UrgencyKeywordListResponse = await urgencyKeywordService.list(filters)
      keywords.value = response.data
      pagination.value = {
        currentPage: response.current_page,
        lastPage: response.last_page,
        perPage: response.per_page,
        total: response.total
      }
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Erro ao carregar keywords'
      console.error('Erro ao buscar keywords:', err)
    } finally {
      loading.value = false
    }
  }

  async function fetchKeyword(id: number) {
    loading.value = true
    error.value = null
    try {
      currentKeyword.value = await urgencyKeywordService.get(id)
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Erro ao carregar keyword'
      console.error('Erro ao buscar keyword:', err)
    } finally {
      loading.value = false
    }
  }

  async function createKeyword(keyword: UrgencyKeyword) {
    loading.value = true
    error.value = null
    try {
      const newKeyword = await urgencyKeywordService.create(keyword)
      keywords.value.unshift(newKeyword)
      return newKeyword
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Erro ao criar keyword'
      console.error('Erro ao criar keyword:', err)
      throw err
    } finally {
      loading.value = false
    }
  }

  async function updateKeyword(id: number, keyword: Partial<UrgencyKeyword>) {
    loading.value = true
    error.value = null
    try {
      const updatedKeyword = await urgencyKeywordService.update(id, keyword)
      const index = keywords.value.findIndex(k => k.id === id)
      if (index !== -1) {
        keywords.value[index] = updatedKeyword
      }
      if (currentKeyword.value?.id === id) {
        currentKeyword.value = updatedKeyword
      }
      return updatedKeyword
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Erro ao atualizar keyword'
      console.error('Erro ao atualizar keyword:', err)
      throw err
    } finally {
      loading.value = false
    }
  }

  async function deleteKeyword(id: number) {
    loading.value = true
    error.value = null
    try {
      await urgencyKeywordService.delete(id)
      const index = keywords.value.findIndex(k => k.id === id)
      if (index !== -1) {
        keywords.value.splice(index, 1)
      }
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Erro ao excluir keyword'
      console.error('Erro ao excluir keyword:', err)
      throw err
    } finally {
      loading.value = false
    }
  }

  async function restoreKeyword(id: number) {
    loading.value = true
    error.value = null
    try {
      const restoredKeyword = await urgencyKeywordService.restore(id)
      const index = keywords.value.findIndex(k => k.id === id)
      if (index !== -1) {
        keywords.value[index] = restoredKeyword
      } else {
        keywords.value.unshift(restoredKeyword)
      }
      return restoredKeyword
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Erro ao restaurar keyword'
      console.error('Erro ao restaurar keyword:', err)
      throw err
    } finally {
      loading.value = false
    }
  }

  async function testKeyword(data: {
    keyword: string
    match_type: 'exact' | 'contains' | 'regex'
    text: string
    case_sensitive?: boolean
    whole_word?: boolean
  }): Promise<TestResult> {
    loading.value = true
    error.value = null
    try {
      return await urgencyKeywordService.test(data)
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Erro ao testar keyword'
      console.error('Erro ao testar keyword:', err)
      throw err
    } finally {
      loading.value = false
    }
  }

  async function analyzeText(text: string, companyId?: number): Promise<AnalyzeResult> {
    loading.value = true
    error.value = null
    try {
      return await urgencyKeywordService.analyze(text, companyId)
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Erro ao analisar texto'
      console.error('Erro ao analisar texto:', err)
      throw err
    } finally {
      loading.value = false
    }
  }

  function clearError() {
    error.value = null
  }

  function setCurrentKeyword(keyword: UrgencyKeyword | null) {
    currentKeyword.value = keyword
  }

  return {
    keywords,
    currentKeyword,
    loading,
    error,
    pagination,
    activeKeywords,
    globalKeywords,
    fetchKeywords,
    fetchKeyword,
    createKeyword,
    updateKeyword,
    deleteKeyword,
    restoreKeyword,
    testKeyword,
    analyzeText,
    clearError,
    setCurrentKeyword
  }
})
