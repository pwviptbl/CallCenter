<script setup lang="ts">
import { ref, onMounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import userService from '../services/userService'
import { useAuthStore } from '../stores/authStore'
import type { User } from '../services/userService'

const router = useRouter()
const authStore = useAuthStore()

const users = ref<User[]>([])
const loading = ref(false)
const error = ref('')
const currentPage = ref(1)
const lastPage = ref(1)
const total = ref(0)

// Filtros
const search = ref('')
const filterRole = ref<'admin' | 'attendant' | ''>('')
const filterActive = ref<'' | 'true' | 'false'>('')

// Confirmação de exclusão
const deleteTarget = ref<User | null>(null)
const deleteLoading = ref(false)

const loadUsers = async (page = 1) => {
  loading.value = true
  error.value = ''
  try {
    const result = await userService.list({
      search: search.value || undefined,
      role: filterRole.value || undefined,
      is_active: filterActive.value === '' ? undefined : filterActive.value === 'true',
      page,
      per_page: 15,
    })
    users.value = result.data
    currentPage.value = result.current_page
    lastPage.value = result.last_page
    total.value = result.total
  } catch (e: any) {
    error.value = e.response?.data?.message || 'Erro ao carregar usuários'
  } finally {
    loading.value = false
  }
}

const handleToggleActive = async (user: User) => {
  // Prevenir auto-desativação
  if (user.id === authStore.user?.id) return
  try {
    const res = await userService.toggleActive(user.id)
    const idx = users.value.findIndex((u) => u.id === user.id)
    if (idx !== -1) users.value[idx] = res.data
  } catch (e: any) {
    error.value = e.response?.data?.message || 'Erro ao alterar status'
  }
}

const handleSetRole = async (user: User, role: 'admin' | 'attendant') => {
  if (user.id === authStore.user?.id) return
  try {
    const res = await userService.setRole(user.id, role)
    const idx = users.value.findIndex((u) => u.id === user.id)
    if (idx !== -1) users.value[idx] = res.data
  } catch (e: any) {
    error.value = e.response?.data?.message || 'Erro ao alterar perfil'
  }
}

const confirmDelete = (user: User) => {
  deleteTarget.value = user
}

const handleDelete = async () => {
  if (!deleteTarget.value) return
  deleteLoading.value = true
  try {
    await userService.delete(deleteTarget.value.id)
    deleteTarget.value = null
    await loadUsers(currentPage.value)
  } catch (e: any) {
    error.value = e.response?.data?.message || 'Erro ao excluir usuário'
    deleteTarget.value = null
  } finally {
    deleteLoading.value = false
  }
}

// Debounce para busca
let searchTimer: ReturnType<typeof setTimeout>
watch(search, () => {
  clearTimeout(searchTimer)
  searchTimer = setTimeout(() => loadUsers(1), 400)
})
watch([filterRole, filterActive], () => loadUsers(1))

onMounted(() => loadUsers())
</script>

<template>
  <div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
    <!-- Header -->
    <div class="sm:flex sm:items-center sm:justify-between mb-6">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">Usuários</h1>
        <p class="mt-1 text-sm text-gray-500">{{ total }} usuário(s) cadastrado(s)</p>
      </div>
      <button
        @click="router.push({ name: 'user-create' })"
        class="mt-4 sm:mt-0 inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700"
      >
        + Novo Usuário
      </button>
    </div>

    <!-- Filtros -->
    <div class="bg-white shadow rounded-lg p-4 mb-6 flex flex-col sm:flex-row gap-3">
      <input
        v-model="search"
        type="text"
        placeholder="Buscar por nome ou email..."
        class="flex-1 rounded-md border-gray-300 shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500 px-3 py-2 border"
      />
      <select
        v-model="filterRole"
        class="rounded-md border border-gray-300 shadow-sm text-sm px-3 py-2 focus:ring-indigo-500 focus:border-indigo-500"
      >
        <option value="">Todos os perfis</option>
        <option value="admin">Admin</option>
        <option value="attendant">Atendente</option>
      </select>
      <select
        v-model="filterActive"
        class="rounded-md border border-gray-300 shadow-sm text-sm px-3 py-2 focus:ring-indigo-500 focus:border-indigo-500"
      >
        <option value="">Todos os status</option>
        <option value="true">Ativos</option>
        <option value="false">Bloqueados</option>
      </select>
    </div>

    <!-- Erro -->
    <div v-if="error" class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded">
      {{ error }}
    </div>

    <!-- Tabela -->
    <div class="bg-white shadow rounded-lg overflow-hidden">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Usuário</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Perfil</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Último acesso</th>
            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          <tr v-if="loading">
            <td colspan="5" class="px-6 py-8 text-center text-gray-400">Carregando...</td>
          </tr>
          <tr v-else-if="users.length === 0">
            <td colspan="5" class="px-6 py-8 text-center text-gray-400">Nenhum usuário encontrado</td>
          </tr>
          <tr v-for="user in users" :key="user.id" class="hover:bg-gray-50">
            <td class="px-6 py-4">
              <div class="flex items-center">
                <div class="h-9 w-9 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-700 font-semibold text-sm mr-3">
                  {{ user.name[0].toUpperCase() }}
                </div>
                <div>
                  <div class="text-sm font-medium text-gray-900 flex items-center gap-1">
                    {{ user.name }}
                    <span v-if="user.id === authStore.user?.id" class="text-xs text-gray-400">(você)</span>
                  </div>
                  <div class="text-xs text-gray-500">{{ user.email }}</div>
                </div>
              </div>
            </td>
            <td class="px-6 py-4">
              <span
                :class="user.role === 'admin' ? 'bg-indigo-100 text-indigo-800' : 'bg-gray-100 text-gray-700'"
                class="px-2 py-1 rounded-full text-xs font-medium"
              >
                {{ user.role === 'admin' ? 'Admin' : 'Atendente' }}
              </span>
            </td>
            <td class="px-6 py-4">
              <span
                :class="user.is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-700'"
                class="px-2 py-1 rounded-full text-xs font-medium"
              >
                {{ user.is_active ? 'Ativo' : 'Bloqueado' }}
              </span>
            </td>
            <td class="px-6 py-4 text-sm text-gray-500">
              {{ user.last_login_at ? new Date(user.last_login_at).toLocaleDateString('pt-BR') : 'Nunca' }}
            </td>
            <td class="px-6 py-4 text-right text-sm font-medium">
              <div class="flex items-center justify-end gap-2">
                <!-- Alterar perfil -->
                <template v-if="user.id !== authStore.user?.id">
                  <button
                    v-if="user.role === 'attendant'"
                    @click="handleSetRole(user, 'admin')"
                    class="text-indigo-600 hover:text-indigo-800 text-xs"
                    title="Tornar Admin"
                  >
                    ↑ Admin
                  </button>
                  <button
                    v-else
                    @click="handleSetRole(user, 'attendant')"
                    class="text-yellow-600 hover:text-yellow-800 text-xs"
                    title="Revogar Admin"
                  >
                    ↓ Atendente
                  </button>
                </template>
                <!-- Toggle ativo/bloqueado -->
                <button
                  v-if="user.id !== authStore.user?.id"
                  @click="handleToggleActive(user)"
                  :class="user.is_active ? 'text-red-500 hover:text-red-700' : 'text-green-500 hover:text-green-700'"
                  class="text-xs"
                >
                  {{ user.is_active ? 'Bloquear' : 'Ativar' }}
                </button>
                <!-- Editar -->
                <button
                  @click="router.push({ name: 'user-edit', params: { id: user.id } })"
                  class="text-gray-500 hover:text-gray-700 text-xs"
                >
                  Editar
                </button>
                <!-- Excluir -->
                <button
                  v-if="user.id !== authStore.user?.id"
                  @click="confirmDelete(user)"
                  class="text-red-500 hover:text-red-700 text-xs"
                >
                  Excluir
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>

      <!-- Paginação -->
      <div v-if="lastPage > 1" class="px-6 py-3 bg-gray-50 border-t border-gray-200 flex items-center justify-between text-sm">
        <span class="text-gray-500">Página {{ currentPage }} de {{ lastPage }}</span>
        <div class="flex gap-2">
          <button
            :disabled="currentPage <= 1"
            @click="loadUsers(currentPage - 1)"
            class="px-3 py-1 rounded border text-gray-600 disabled:opacity-40 hover:bg-gray-100"
          >
            Anterior
          </button>
          <button
            :disabled="currentPage >= lastPage"
            @click="loadUsers(currentPage + 1)"
            class="px-3 py-1 rounded border text-gray-600 disabled:opacity-40 hover:bg-gray-100"
          >
            Próxima
          </button>
        </div>
      </div>
    </div>

    <!-- Modal de confirmação de exclusão -->
    <div
      v-if="deleteTarget"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/40"
    >
      <div class="bg-white rounded-lg shadow-xl p-6 max-w-sm w-full mx-4">
        <h3 class="text-lg font-semibold text-gray-900 mb-2">Confirmar exclusão</h3>
        <p class="text-sm text-gray-600 mb-5">
          Deseja excluir o usuário <strong>{{ deleteTarget.name }}</strong>? Esta ação não pode ser desfeita.
        </p>
        <div class="flex justify-end gap-3">
          <button
            @click="deleteTarget = null"
            class="px-4 py-2 text-sm text-gray-700 border border-gray-300 rounded hover:bg-gray-50"
          >
            Cancelar
          </button>
          <button
            @click="handleDelete"
            :disabled="deleteLoading"
            class="px-4 py-2 text-sm text-white bg-red-600 rounded hover:bg-red-700 disabled:opacity-60"
          >
            {{ deleteLoading ? 'Excluindo...' : 'Excluir' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
