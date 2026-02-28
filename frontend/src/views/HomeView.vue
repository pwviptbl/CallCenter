<script setup lang="ts">
</script>

<template>
  <div class="min-h-screen bg-gray-50 flex items-center justify-center">
    <div class="max-w-md w-full space-y-8">
      <div>
        <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
          CallCenter - Sistema de Atendimento
        </h2>
        <p class="mt-2 text-center text-sm text-gray-600">
          Plataforma de gerenciamento de solicitações de manutenção
        </p>
      </div>

      <div class="mt-8 space-y-6">
        <router-link
          to="/login"
          class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
        >
          Acessar Sistema
        </router-link>
      </div>
    </div>
  </div>
</template>
