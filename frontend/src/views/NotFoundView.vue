<template>
  <div class="min-h-screen bg-gray-50 flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full text-center">
      <div class="mb-6">
        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10a4 4 0 018 0m-8 4a4 4 0 018 0" />
        </svg>
      </div>
      <h1 class="text-4xl font-bold text-gray-900 mb-2">404</h1>
      <p class="text-xl text-gray-600 mb-6">
        Página não encontrada
      </p>
      <p class="text-gray-500 mb-8">
        A página que você está procurando não existe ou foi movida.
      </p>
      <RouterLink
        to="/"
        class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700"
      >
        Voltar ao Início
      </RouterLink>
    </div>
  </div>
</template>

<script setup lang="ts">
import { RouterLink } from 'vue-router'
</script>
