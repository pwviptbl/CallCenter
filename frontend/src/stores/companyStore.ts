import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import companyService, { type Company, type CompanyFilters, type CompanyListResponse } from '@/services/companyService'

export const useCompanyStore = defineStore('company', () => {
  // State
  const companies = ref<Company[]>([])
  const currentCompany = ref<Company | null>(null)
  const loading = ref(false)
  const error = ref<string | null>(null)
  const pagination = ref({
    currentPage: 1,
    lastPage: 1,
    perPage: 15,
    total: 0
  })

  // Getters
  const activeCompanies = computed(() => 
    companies.value.filter(c => c.active && !c.deleted_at)
  )

  // Actions
  async function fetchCompanies(filters: CompanyFilters = {}) {
    loading.value = true
    error.value = null
    try {
      const response: CompanyListResponse = await companyService.list(filters)
      companies.value = response.data
      pagination.value = {
        currentPage: response.current_page,
        lastPage: response.last_page,
        perPage: response.per_page,
        total: response.total
      }
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Erro ao carregar empresas'
      console.error('Erro ao buscar empresas:', err)
    } finally {
      loading.value = false
    }
  }

  async function fetchCompany(id: number) {
    loading.value = true
    error.value = null
    try {
      currentCompany.value = await companyService.get(id)
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Erro ao carregar empresa'
      console.error('Erro ao buscar empresa:', err)
    } finally {
      loading.value = false
    }
  }

  async function createCompany(company: Company) {
    loading.value = true
    error.value = null
    try {
      const newCompany = await companyService.create(company)
      companies.value.unshift(newCompany)
      return newCompany
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Erro ao criar empresa'
      console.error('Erro ao criar empresa:', err)
      throw err
    } finally {
      loading.value = false
    }
  }

  async function updateCompany(id: number, company: Partial<Company>) {
    loading.value = true
    error.value = null
    try {
      const updatedCompany = await companyService.update(id, company)
      const index = companies.value.findIndex(c => c.id === id)
      if (index !== -1) {
        companies.value[index] = updatedCompany
      }
      if (currentCompany.value?.id === id) {
        currentCompany.value = updatedCompany
      }
      return updatedCompany
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Erro ao atualizar empresa'
      console.error('Erro ao atualizar empresa:', err)
      throw err
    } finally {
      loading.value = false
    }
  }

  async function deleteCompany(id: number) {
    loading.value = true
    error.value = null
    try {
      await companyService.delete(id)
      const index = companies.value.findIndex(c => c.id === id)
      if (index !== -1) {
        companies.value.splice(index, 1)
      }
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Erro ao excluir empresa'
      console.error('Erro ao excluir empresa:', err)
      throw err
    } finally {
      loading.value = false
    }
  }

  async function restoreCompany(id: number) {
    loading.value = true
    error.value = null
    try {
      const restoredCompany = await companyService.restore(id)
      const index = companies.value.findIndex(c => c.id === id)
      if (index !== -1) {
        companies.value[index] = restoredCompany
      } else {
        companies.value.unshift(restoredCompany)
      }
      return restoredCompany
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Erro ao restaurar empresa'
      console.error('Erro ao restaurar empresa:', err)
      throw err
    } finally {
      loading.value = false
    }
  }

  function clearError() {
    error.value = null
  }

  function setCurrentCompany(company: Company | null) {
    currentCompany.value = company
  }

  return {
    companies,
    currentCompany,
    loading,
    error,
    pagination,
    activeCompanies,
    fetchCompanies,
    fetchCompany,
    createCompany,
    updateCompany,
    deleteCompany,
    restoreCompany,
    clearError,
    setCurrentCompany
  }
})
