import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import authService from '../services/authService'
import type { User } from '../services/authService'

export const useAuthStore = defineStore('auth', () => {
  const user = ref<User | null>(null)
  const token = ref<string | null>(null)
  const loading = ref(false)
  const error = ref<string | null>(null)

  // Computed
  const isAuthenticated = computed(() => !!token.value && !!user.value)

  // Estados
  const setToken = (newToken: string) => {
    token.value = newToken
    authService.setToken(newToken)
  }

  const setUser = (newUser: User) => {
    user.value = newUser
  }

  // Ações
  const login = async (email: string, password: string) => {
    loading.value = true
    error.value = null

    try {
      const response = await authService.login({ email, password })
      setUser(response.user)
      setToken(response.token)
      return response.user
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Erro ao fazer login'
      throw err
    } finally {
      loading.value = false
    }
  }

  const logout = async () => {
    loading.value = true
    error.value = null

    try {
      await authService.logout()
      user.value = null
      token.value = null
      authService.removeToken()
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Erro ao fazer logout'
      throw err
    } finally {
      loading.value = false
    }
  }

  const me = async () => {
    loading.value = true
    error.value = null

    try {
      const response = await authService.me()
      setUser(response.user)
      return response.user
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Erro ao obter dados do usuário'
      // Se erro 401, limpar autenticação
      if (err.response?.status === 401) {
        user.value = null
        token.value = null
        authService.removeToken()
      }
      throw err
    } finally {
      loading.value = false
    }
  }

  const initAuth = async () => {
    // Restaurar token do localStorage
    const savedToken = authService.getToken()
    if (savedToken) {
      token.value = savedToken
      // Tentar obter dados do usuário
      try {
        await me()
      } catch {
        // Token expirou ou inválido
        logout()
      }
    }
  }

  const clearError = () => {
    error.value = null
  }

  return {
    user,
    token,
    loading,
    error,
    isAuthenticated,
    login,
    logout,
    me,
    initAuth,
    setToken,
    setUser,
    clearError
  }
})
