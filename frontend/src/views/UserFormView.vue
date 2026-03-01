<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import userService from '../services/userService'
import type { CreateUserData, UpdateUserData } from '../services/userService'

const router = useRouter()
const route = useRoute()

const userId = computed(() => (route.params.id ? Number(route.params.id) : null))
const isEdit = computed(() => !!userId.value)

const loading = ref(false)
const saving = ref(false)
const error = ref('')
const success = ref('')

// Form fields
const name = ref('')
const email = ref('')
const password = ref('')
const role = ref<'admin' | 'attendant'>('attendant')
const isActive = ref(true)

const loadUser = async () => {
  if (!userId.value) return
  loading.value = true
  try {
    const res = await userService.get(userId.value)
    const u = res.data
    name.value = u.name
    email.value = u.email
    role.value = u.role
    isActive.value = u.is_active
  } catch (e: any) {
    error.value = e.response?.data?.message || 'Erro ao carregar usuário'
  } finally {
    loading.value = false
  }
}

const handleSubmit = async () => {
  error.value = ''
  success.value = ''
  saving.value = true

  try {
    if (isEdit.value) {
      const payload: UpdateUserData = { name: name.value, email: email.value, role: role.value, is_active: isActive.value }
      if (password.value) payload.password = password.value
      await userService.update(userId.value!, payload)
      success.value = 'Usuário atualizado com sucesso!'
    } else {
      if (!password.value) {
        error.value = 'A senha é obrigatória para novos usuários'
        return
      }
      const payload: CreateUserData = {
        name: name.value,
        email: email.value,
        password: password.value,
        role: role.value,
        is_active: isActive.value,
      }
      await userService.create(payload)
      success.value = 'Usuário criado com sucesso!'
      setTimeout(() => router.push({ name: 'users' }), 1000)
    }
  } catch (e: any) {
    const errors = e.response?.data?.errors
    if (errors) {
      error.value = Object.values(errors).flat().join(' ')
    } else {
      error.value = e.response?.data?.message || 'Erro ao salvar usuário'
    }
  } finally {
    saving.value = false
  }
}

onMounted(loadUser)
</script>

<template>
  <div class="max-w-2xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
    <!-- Header -->
    <div class="mb-6 flex items-center gap-4">
      <button
        @click="router.push({ name: 'users' })"
        class="text-gray-500 hover:text-gray-700 text-sm flex items-center gap-1"
      >
        ← Voltar
      </button>
      <h1 class="text-2xl font-bold text-gray-900">
        {{ isEdit ? 'Editar Usuário' : 'Novo Usuário' }}
      </h1>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="text-center py-8 text-gray-400">Carregando...</div>

    <!-- Form -->
    <form v-else @submit.prevent="handleSubmit" class="bg-white shadow rounded-lg p-6 space-y-5">
      <!-- Erro -->
      <div v-if="error" class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded text-sm">
        {{ error }}
      </div>
      <!-- Sucesso -->
      <div v-if="success" class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded text-sm">
        {{ success }}
      </div>

      <!-- Nome -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Nome *</label>
        <input
          v-model="name"
          type="text"
          required
          placeholder="Nome completo"
          class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
        />
      </div>

      <!-- Email -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Email *</label>
        <input
          v-model="email"
          type="email"
          required
          placeholder="email@exemplo.com"
          class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
        />
      </div>

      <!-- Senha -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">
          Senha {{ isEdit ? '(deixe vazio para manter)' : '*' }}
        </label>
        <input
          v-model="password"
          type="password"
          :required="!isEdit"
          placeholder="Mínimo 8 caracteres"
          class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
        />
      </div>

      <!-- Perfil -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Perfil *</label>
        <select
          v-model="role"
          class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
        >
          <option value="attendant">Atendente</option>
          <option value="admin">Administrador</option>
        </select>
      </div>

      <!-- Status -->
      <div class="flex items-center gap-3">
        <input
          id="is-active"
          v-model="isActive"
          type="checkbox"
          class="h-4 w-4 text-indigo-600 border-gray-300 rounded"
        />
        <label for="is-active" class="text-sm font-medium text-gray-700">Usuário ativo</label>
      </div>

      <!-- Ações -->
      <div class="pt-2 flex items-center justify-end gap-3">
        <button
          type="button"
          @click="router.push({ name: 'users' })"
          class="px-4 py-2 text-sm text-gray-700 border border-gray-300 rounded hover:bg-gray-50"
        >
          Cancelar
        </button>
        <button
          type="submit"
          :disabled="saving"
          class="px-5 py-2 text-sm text-white bg-indigo-600 rounded hover:bg-indigo-700 disabled:opacity-60"
        >
          {{ saving ? 'Salvando...' : (isEdit ? 'Atualizar' : 'Criar Usuário') }}
        </button>
      </div>
    </form>
  </div>
</template>
