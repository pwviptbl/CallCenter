<template>
  <div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <!-- Header -->
      <div class="mb-8">
        <div class="flex justify-between items-center">
          <div>
            <h1 class="text-3xl font-bold text-gray-900">Empresas</h1>
            <p class="mt-2 text-sm text-gray-600">
              Gerencie as empresas cadastradas no sistema
            </p>
          </div>
          <router-link
            to="/companies/new"
            class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
          >
            <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            Nova Empresa
          </router-link>
        </div>
      </div>

      <!-- Filtros -->
      <div class="bg-white rounded-lg shadow mb-6 p-4">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Buscar</label>
            <input
              v-model="filters.search"
              type="text"
              placeholder="Nome da empresa..."
              class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
              @input="debouncedSearch"
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
            <select
              v-model="filters.active_only"
              class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
              @change="loadCompanies"
            >
              <option :value="undefined">Todas</option>
              <option :value="true">Apenas Ativas</option>
              <option :value="false">Apenas Inativas</option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Por página</label>
            <select
              v-model="filters.per_page"
              class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
              @change="loadCompanies"
            >
              <option :value="10">10</option>
              <option :value="15">15</option>
              <option :value="25">25</option>
              <option :value="50">50</option>
            </select>
          </div>
        </div>
      </div>

      <!-- Tabela -->
      <div class="bg-white rounded-lg shadow overflow-hidden">
        <div v-if="loading" class="p-8 text-center">
          <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600"></div>
          <p class="mt-2 text-gray-600">Carregando...</p>
        </div>

        <div v-else-if="error" class="p-8 text-center">
          <p class="text-red-600">{{ error }}</p>
          <button
            @click="loadCompanies"
            class="mt-4 inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700"
          >
            Tentar Novamente
          </button>
        </div>

        <div v-else-if="companies.length === 0" class="p-8 text-center">
          <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
          </svg>
          <h3 class="mt-2 text-sm font-medium text-gray-900">Nenhuma empresa encontrada</h3>
          <p class="mt-1 text-sm text-gray-500">Comece criando uma nova empresa.</p>
        </div>

        <table v-else class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Empresa
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Contato
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                WhatsApp
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Limites
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Status
              </th>
              <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                Ações
              </th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="company in companies" :key="company.id" class="hover:bg-gray-50">
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm font-medium text-gray-900">{{ company.name }}</div>
                <div v-if="company.document" class="text-sm text-gray-500">{{ company.document }}</div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div v-if="company.email" class="text-sm text-gray-900">{{ company.email }}</div>
                <div v-if="company.phone" class="text-sm text-gray-500">{{ company.phone }}</div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span v-if="company.whatsapp_number" class="text-sm text-gray-900">
                  {{ company.whatsapp_number }}
                </span>
                <span v-else class="text-sm text-gray-400">—</span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                <div>{{ company.max_users || 10 }} usuários</div>
                <div>{{ company.max_simultaneous_chats || 5 }} chats</div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span
                  v-if="company.deleted_at"
                  class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800"
                >
                  Excluída
                </span>
                <span
                  v-else-if="company.active"
                  class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800"
                >
                  Ativa
                </span>
                <span
                  v-else
                  class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800"
                >
                  Inativa
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                <div class="flex justify-end space-x-2">
                  <router-link
                    v-if="!company.deleted_at"
                    :to="`/companies/${company.id}/edit`"
                    class="text-indigo-600 hover:text-indigo-900"
                  >
                    Editar
                  </router-link>
                  <button
                    v-if="company.deleted_at"
                    @click="handleRestore(company.id!)"
                    class="text-green-600 hover:text-green-900"
                  >
                    Restaurar
                  </button>
                  <button
                    v-else
                    @click="handleDelete(company.id!)"
                    class="text-red-600 hover:text-red-900"
                  >
                    Excluir
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>

        <!-- Paginação -->
        <div v-if="companies.length > 0" class="bg-white px-4 py-3 border-t border-gray-200 sm:px-6">
          <div class="flex items-center justify-between">
            <div class="flex-1 flex justify-between sm:hidden">
              <button
                @click="previousPage"
                :disabled="pagination.currentPage <= 1"
                class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50"
              >
                Anterior
              </button>
              <button
                @click="nextPage"
                :disabled="pagination.currentPage >= pagination.lastPage"
                class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50"
              >
                Próxima
              </button>
            </div>
            <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
              <div>
                <p class="text-sm text-gray-700">
                  Mostrando
                  <span class="font-medium">{{ (pagination.currentPage - 1) * pagination.perPage + 1 }}</span>
                  até
                  <span class="font-medium">{{ Math.min(pagination.currentPage * pagination.perPage, pagination.total) }}</span>
                  de
                  <span class="font-medium">{{ pagination.total }}</span>
                  resultados
                </p>
              </div>
              <div>
                <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
                  <button
                    @click="previousPage"
                    :disabled="pagination.currentPage <= 1"
                    class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50"
                  >
                    <span class="sr-only">Anterior</span>
                    <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                    </svg>
                  </button>
                  <button
                    @click="nextPage"
                    :disabled="pagination.currentPage >= pagination.lastPage"
                    class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50"
                  >
                    <span class="sr-only">Próxima</span>
                    <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                    </svg>
                  </button>
                </nav>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { storeToRefs } from 'pinia'
import { useCompanyStore } from '@/stores/companyStore'

const companyStore = useCompanyStore()
const { companies, loading, error, pagination } = storeToRefs(companyStore)

const filters = ref({
  search: '',
  active_only: undefined as boolean | undefined,
  per_page: 15,
  page: 1
})

let searchTimeout: ReturnType<typeof setTimeout>

function debouncedSearch() {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    filters.value.page = 1
    loadCompanies()
  }, 500)
}

async function loadCompanies() {
  await companyStore.fetchCompanies(filters.value)
}

async function handleDelete(id: number) {
  if (!confirm('Tem certeza que deseja excluir esta empresa?')) return
  
  try {
    await companyStore.deleteCompany(id)
    loadCompanies()
  } catch (err) {
    console.error('Erro ao excluir empresa:', err)
  }
}

async function handleRestore(id: number) {
  if (!confirm('Tem certeza que deseja restaurar esta empresa?')) return
  
  try {
    await companyStore.restoreCompany(id)
    loadCompanies()
  } catch (err) {
    console.error('Erro ao restaurar empresa:', err)
  }
}

function previousPage() {
  if (filters.value.page > 1) {
    filters.value.page--
    loadCompanies()
  }
}

function nextPage() {
  if (filters.value.page < pagination.value.lastPage) {
    filters.value.page++
    loadCompanies()
  }
}

onMounted(() => {
  loadCompanies()
})
</script>
