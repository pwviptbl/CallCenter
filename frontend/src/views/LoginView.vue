<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/authStore'
import { storeToRefs } from 'pinia'

const router = useRouter()
const authStore = useAuthStore()
const { loading, error } = storeToRefs(authStore)

const email = ref('')
const password = ref('')
const formError = ref('')

const handleLogin = async () => {
  formError.value = ''
  
  if (!email.value || !password.value) {
    formError.value = 'Email e senha são obrigatórios'
    return
  }

  try {
    await authStore.login(email.value, password.value)
    router.push('/dashboard')
  } catch (err: any) {
    formError.value = err.response?.data?.message || 'Erro ao fazer login'
  }
}

const fillDemoAdmin = () => {
  email.value = 'admin@callcenter.local'
  password.value = 'Admin@123'
}

const fillDemoUser = () => {
  email.value = 'atendente@callcenter.local'
  password.value = 'Atend@123'
}
</script>

<template>
  <div class="min-h-screen bg-gradient-to-br from-indigo-600 to-blue-600 flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8 bg-white rounded-lg shadow-lg p-8">
      <div>
        <h1 class="text-center text-3xl font-bold text-gray-900 mb-2">
          CallCenter
        </h1>
        <h2 class="text-center text-xl font-semibold text-gray-600">
          Entrar no sistema
        </h2>
        <p class="text-center text-sm text-gray-500 mt-2">
          Sistema de atendimento com IA
        </p>
      </div>

      <!-- Demo Credentials Alert -->
      <div class="rounded-md bg-blue-50 p-4">
        <div class="flex">
          <div class="flex-shrink-0">
            <svg class="h-5 w-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor">
              <path fill-rule="evenodd" d="M18 5v8a2 2 0 01-2 2h-5l-5 4v-4H4a2 2 0 01-2-2V5a2 2 0 012-2h12a2 2 0 012 2z" clip-rule="evenodd" />
            </svg>
          </div>
          <div class="ml-3 text-sm">
            <p class="font-medium text-blue-800">Credenciais de demonstração:</p>
            <div class="mt-2 space-y-1 text-blue-700">
              <p><strong>Admin:</strong> admin@callcenter.local / Admin@123</p>
              <p><strong>Atendente:</strong> atendente@callcenter.local / Atend@123</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Error Alert -->
      <div v-if="formError || error" class="rounded-md bg-red-50 p-4">
        <div class="flex">
          <div class="flex-shrink-0">
            <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
              <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
            </svg>
          </div>
          <div class="ml-3">
            <p class="text-sm font-medium text-red-800">{{ formError || error }}</p>
          </div>
        </div>
      </div>

      <!-- Login Form -->
      <form class="mt-8 space-y-6" @submit.prevent="handleLogin">
        <div class="space-y-4">
          <div>
            <label for="email-address" class="block text-sm font-medium text-gray-700">
              Email
            </label>
            <input
              id="email-address"
              v-model="email"
              name="email"
              type="email"
              autocomplete="email"
              required
              class="mt-1 appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
              placeholder="seu@email.com"
              :disabled="loading"
            />
          </div>
          <div>
            <label for="password" class="block text-sm font-medium text-gray-700">
              Senha
            </label>
            <input
              id="password"
              v-model="password"
              name="password"
              type="password"
              autocomplete="current-password"
              required
              class="mt-1 appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
              placeholder="••••••••"
              :disabled="loading"
            />
          </div>
        </div>

        <!-- Login Button -->
        <button
          type="submit"
          :disabled="loading"
          class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed"
        >
          {{ loading ? 'Entrando...' : 'Entrar' }}
        </button>

        <!-- Demo Buttons -->
        <div class="flex gap-2">
          <button
            type="button"
            @click="fillDemoAdmin"
            :disabled="loading"
            class="flex-1 flex justify-center py-2 px-4 border border-gray-300 rounded-md shadow-sm text-xs font-medium text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50"
          >
            Usar Admin
          </button>
          <button
            type="button"
            @click="fillDemoUser"
            :disabled="loading"
            class="flex-1 flex justify-center py-2 px-4 border border-gray-300 rounded-md shadow-sm text-xs font-medium text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50"
          >
            Usar Usuário
          </button>
        </div>
      </form>
    </div>
  </div>
</template>
