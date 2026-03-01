<script setup lang="ts">
import { RouterView, RouterLink, useRoute, useRouter } from 'vue-router'
import { onMounted, computed } from 'vue'
import { useAuthStore } from './stores/authStore'
import { storeToRefs } from 'pinia'

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()
const { isAuthenticated, isAdmin, user } = storeToRefs(authStore)

const hideNav = computed(() => (route.meta.hideNav as boolean) || false)

const handleLogout = async () => {
  try {
    await authStore.logout()
    router.push({ name: 'login' })
  } catch {
    // Erro já tratado na store
  }
}

onMounted(async () => {
  await authStore.initAuth()
  
  // Após carregar auth, redirecionar se necessário
  if (route.path === '/' || route.path === '') {
    if (isAuthenticated.value) {
      router.push({ name: 'dashboard' })
    } else {
      router.push({ name: 'login' })
    }
  }
})
</script>

<template>
  <div id="app">
    <!-- Navigation Bar (hide on login page) -->
    <nav v-if="!hideNav && isAuthenticated" class="bg-white shadow-sm">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
          <div class="flex">
            <div class="flex-shrink-0 flex items-center">
              <h1 class="text-xl font-bold text-indigo-600">CallCenter</h1>
            </div>
            <div class="hidden sm:ml-6 sm:flex sm:space-x-8">
              <RouterLink
                to="/dashboard"
                class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium"
                active-class="border-indigo-500 text-gray-900"
              >
                Dashboard
              </RouterLink>

              <!-- Links visíveis apenas para ADMIN -->
              <template v-if="isAdmin">
                <RouterLink
                  to="/companies"
                  class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium"
                  active-class="border-indigo-500 text-gray-900"
                >
                  Empresas
                </RouterLink>
                <RouterLink
                  to="/urgency-keywords"
                  class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium"
                  active-class="border-indigo-500 text-gray-900"
                >
                  Keywords
                </RouterLink>
                <RouterLink
                  to="/users"
                  class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium"
                  active-class="border-indigo-500 text-gray-900"
                >
                  Usuários
                </RouterLink>
              </template>
            </div>
          </div>

          <!-- User Menu -->
          <div class="hidden sm:ml-6 sm:flex sm:items-center">
            <div class="ml-3 relative">
              <div class="flex items-center space-x-3">
                <span class="text-sm text-gray-700">{{ user?.name }}</span>
                <span
                  :class="isAdmin ? 'bg-indigo-100 text-indigo-800' : 'bg-gray-100 text-gray-600'"
                  class="px-2 py-0.5 rounded text-xs font-medium"
                >
                  {{ isAdmin ? 'Admin' : 'Atendente' }}
                </span>
                <button
                  @click="handleLogout"
                  class="px-3 py-2 rounded-md text-sm font-medium text-gray-700 hover:text-gray-900 hover:bg-gray-50"
                >
                  Sair
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </nav>

    <!-- Main Content -->
    <RouterView />
  </div>
</template>

<style scoped>
#app {
  min-height: 100vh;
  background-color: #f9fafb;
}
</style>
