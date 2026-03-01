<template>
  <div class="max-w-4xl mx-auto py-6 sm:px-6 lg:px-8">
    <!-- Header -->
    <div class="mb-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">
            {{ isEditMode ? 'Editar Keyword' : 'Nova Keyword' }}
          </h1>
          <p class="mt-1 text-sm text-gray-600">
            {{ isEditMode ? 'Atualize os dados da keyword de urgência' : 'Adicione uma nova keyword para detecção de urgência' }}
          </p>
        </div>
        <button
          @click="$router.back()"
          class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50"
        >
          Voltar
        </button>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading && isEditMode" class="bg-white shadow rounded-lg p-6">
      <div class="animate-pulse space-y-4">
        <div class="h-4 bg-gray-200 rounded w-3/4"></div>
        <div class="h-4 bg-gray-200 rounded w-1/2"></div>
      </div>
    </div>

    <!-- Form -->
    <form v-else @submit.prevent="handleSubmit" class="space-y-6">
      <!-- Main Card -->
      <div class="bg-white shadow rounded-lg">
        <div class="px-6 py-5 space-y-6">
          <!-- Keyword -->
          <div>
            <label for="keyword" class="block text-sm font-medium text-gray-700">
              Keyword / Padrão <span class="text-red-500">*</span>
            </label>
            <input
              id="keyword"
              v-model="form.keyword"
              type="text"
              required
              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
              :class="{ 'border-red-300': errors.keyword }"
              placeholder="ex: preso, fogo, elevador (parado|travado)"
            />
            <p v-if="errors.keyword" class="mt-1 text-sm text-red-600">{{ errors.keyword }}</p>
            <p v-else class="mt-1 text-xs text-gray-500">
              Digite a palavra-chave ou padrão regex para detectar urgência
            </p>
          </div>

          <!-- Match Type -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Tipo de Correspondência <span class="text-red-500">*</span>
            </label>
            <div class="grid grid-cols-3 gap-4">
              <label
                v-for="type in matchTypes"
                :key="type.value"
                class="relative flex cursor-pointer rounded-lg border bg-white p-4 shadow-sm focus:outline-none"
                :class="{
                  'border-indigo-500 ring-2 ring-indigo-500': form.match_type === type.value,
                  'border-gray-300': form.match_type !== type.value
                }"
              >
                <input
                  type="radio"
                  v-model="form.match_type"
                  :value="type.value"
                  class="sr-only"
                />
                <span class="flex flex-1">
                  <span class="flex flex-col">
                    <span class="block text-sm font-medium text-gray-900">{{ type.label }}</span>
                    <span class="mt-1 flex items-center text-xs text-gray-500">{{ type.description }}</span>
                  </span>
                </span>
              </label>
            </div>
            <p v-if="errors.match_type" class="mt-1 text-sm text-red-600">{{ errors.match_type }}</p>
          </div>

          <!-- Description -->
          <div>
            <label for="description" class="block text-sm font-medium text-gray-700">
              Descrição
            </label>
            <textarea
              id="description"
              v-model="form.description"
              rows="3"
              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
              placeholder="Descreva quando esta keyword deve ser detectada..."
            ></textarea>
            <p class="mt-1 text-xs text-gray-500">
              Opcional: explique o contexto desta keyword
            </p>
          </div>

          <!-- Priority Level -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Nível de Prioridade <span class="text-red-500">*</span>
            </label>
            <div class="flex items-center space-x-4">
              <input
                type="range"
                v-model.number="form.priority_level"
                min="1"
                max="5"
                step="1"
                class="flex-1 h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer"
              />
              <div class="flex items-center space-x-1">
                <svg
                  v-for="star in 5"
                  :key="star"
                  class="h-6 w-6"
                  :class="star <= form.priority_level ? 'text-yellow-400' : 'text-gray-300'"
                  fill="currentColor"
                  viewBox="0 0 20 20"
                >
                  <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                </svg>
              </div>
              <span class="text-sm font-medium text-gray-700 w-16">Nível {{ form.priority_level }}</span>
            </div>
            <p class="mt-1 text-xs text-gray-500">
              1 = Baixa prioridade, 5 = Urgência máxima
            </p>
          </div>

          <!-- Company -->
          <div>
            <label for="company_id" class="block text-sm font-medium text-gray-700">
              Empresa
            </label>
            <select
              id="company_id"
              v-model="form.company_id"
              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
            >
              <option :value="null">Global (todas as empresas)</option>
              <option v-for="company in companies" :key="company.id" :value="company.id">
                {{ company.name }}
              </option>
            </select>
            <p class="mt-1 text-xs text-gray-500">
              Deixe como "Global" para aplicar a todas as empresas
            </p>
          </div>

          <!-- Settings Row -->
          <div class="grid grid-cols-3 gap-4">
            <!-- Case Sensitive -->
            <div class="flex items-start">
              <div class="flex items-center h-5">
                <input
                  id="case_sensitive"
                  v-model="form.case_sensitive"
                  type="checkbox"
                  class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                />
              </div>
              <div class="ml-3 text-sm">
                <label for="case_sensitive" class="font-medium text-gray-700">Case Sensitive</label>
                <p class="text-gray-500">Diferenciar maiúsculas</p>
              </div>
            </div>

            <!-- Whole Word -->
            <div class="flex items-start">
              <div class="flex items-center h-5">
                <input
                  id="whole_word"
                  v-model="form.whole_word"
                  type="checkbox"
                  class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                />
              </div>
              <div class="ml-3 text-sm">
                <label for="whole_word" class="font-medium text-gray-700">Palavra Completa</label>
                <p class="text-gray-500">Apenas palavra inteira</p>
              </div>
            </div>

            <!-- Active -->
            <div class="flex items-start">
              <div class="flex items-center h-5">
                <input
                  id="active"
                  v-model="form.active"
                  type="checkbox"
                  class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                />
              </div>
              <div class="ml-3 text-sm">
                <label for="active" class="font-medium text-gray-700">Ativa</label>
                <p class="text-gray-500">Usar na detecção</p>
              </div>
            </div>
          </div>

          <!-- Regex Validation Warning -->
          <div
            v-if="form.match_type === 'regex' && form.keyword"
            class="rounded-md p-4"
            :class="regexValidation.valid ? 'bg-green-50' : 'bg-yellow-50'"
          >
            <div class="flex">
              <div class="flex-shrink-0">
                <svg
                  v-if="regexValidation.valid"
                  class="h-5 w-5 text-green-400"
                  viewBox="0 0 20 20"
                  fill="currentColor"
                >
                  <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                </svg>
                <svg
                  v-else
                  class="h-5 w-5 text-yellow-400"
                  viewBox="0 0 20 20"
                  fill="currentColor"
                >
                  <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                </svg>
              </div>
              <div class="ml-3">
                <p class="text-sm" :class="regexValidation.valid ? 'text-green-800' : 'text-yellow-800'">
                  {{ regexValidation.message }}
                </p>
              </div>
            </div>
          </div>
        </div>

        <!-- Form Actions -->
        <div class="bg-gray-50 px-6 py-4 flex items-center justify-between">
          <button
            v-if="isEditMode"
            type="button"
            @click="handleDelete"
            class="inline-flex items-center px-4 py-2 border border-red-300 rounded-md shadow-sm text-sm font-medium text-red-700 bg-white hover:bg-red-50"
          >
            Excluir
          </button>
          <div class="flex-1"></div>
          <div class="flex space-x-3">
            <button
              type="button"
              @click="$router.back()"
              class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50"
            >
              Cancelar
            </button>
            <button
              type="submit"
              :disabled="submitting"
              class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              {{ submitting ? 'Salvando...' : 'Salvar' }}
            </button>
          </div>
        </div>
      </div>

      <!-- Error Alert -->
      <div v-if="error" class="rounded-md bg-red-50 p-4">
        <div class="flex">
          <div class="flex-shrink-0">
            <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
              <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
            </svg>
          </div>
          <div class="ml-3">
            <p class="text-sm font-medium text-red-800">{{ error }}</p>
          </div>
        </div>
      </div>
    </form>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { storeToRefs } from 'pinia'
import { useUrgencyKeywordStore } from '../stores/urgencyKeywordStore'
import { useCompanyStore } from '../stores/companyStore'
import type { UrgencyKeyword } from '../services/urgencyKeywordService'

const router = useRouter()
const route = useRoute()
const urgencyKeywordStore = useUrgencyKeywordStore()
const companyStore = useCompanyStore()

const { loading, error } = storeToRefs(urgencyKeywordStore)
const { companies } = storeToRefs(companyStore)

const isEditMode = computed(() => !!route.params.id)
const submitting = ref(false)

const matchTypes = [
  { value: 'exact', label: 'Exata', description: 'Correspondência exata' },
  { value: 'contains', label: 'Contém', description: 'Texto contém keyword' },
  { value: 'regex', label: 'Regex', description: 'Expressão regular' }
]

const form = ref<Partial<UrgencyKeyword>>({
  keyword: '',
  match_type: 'contains',
  description: '',
  priority_level: 3,
  company_id: null,
  case_sensitive: false,
  whole_word: false,
  active: true
})

const errors = ref<Record<string, string>>({})

const regexValidation = computed(() => {
  if (form.value.match_type !== 'regex' || !form.value.keyword) {
    return { valid: true, message: '' }
  }

  try {
    new RegExp(form.value.keyword)
    return {
      valid: true,
      message: '✓ Padrão regex válido'
    }
  } catch (e) {
    return {
      valid: false,
      message: `⚠ Regex inválido: ${e instanceof Error ? e.message : 'Erro desconhecido'}`
    }
  }
})

// Watch regex validation
watch(() => form.value.keyword, () => {
  if (form.value.match_type === 'regex') {
    if (!regexValidation.value.valid) {
      errors.value.keyword = regexValidation.value.message
    } else {
      delete errors.value.keyword
    }
  }
})

onMounted(async () => {
  // Load companies for dropdown
  await companyStore.fetchCompanies()

  // Load keyword if editing
  if (isEditMode.value) {
    const id = Number(route.params.id)
    await urgencyKeywordStore.fetchKeyword(id)
    
    if (urgencyKeywordStore.currentKeyword) {
      form.value = { ...urgencyKeywordStore.currentKeyword }
    }
  }
})

const validateForm = (): boolean => {
  errors.value = {}

  if (!form.value.keyword?.trim()) {
    errors.value.keyword = 'Keyword é obrigatória'
    return false
  }

  if (form.value.match_type === 'regex' && !regexValidation.value.valid) {
    errors.value.keyword = regexValidation.value.message
    return false
  }

  if (!form.value.priority_level || form.value.priority_level < 1 || form.value.priority_level > 5) {
    errors.value.priority_level = 'Prioridade deve ser entre 1 e 5'
    return false
  }

  return true
}

const handleSubmit = async () => {
  if (!validateForm()) {
    return
  }

  try {
    submitting.value = true

    if (isEditMode.value) {
      const id = Number(route.params.id)
      await urgencyKeywordStore.updateKeyword(id, form.value)
    } else {
      await urgencyKeywordStore.createKeyword(form.value)
    }

    router.push({ name: 'urgency-keywords' })
  } catch (e) {
    console.error('Erro ao salvar keyword:', e)
  } finally {
    submitting.value = false
  }
}

const handleDelete = async () => {
  if (!confirm('Tem certeza que deseja excluir esta keyword?')) {
    return
  }

  try {
    const id = Number(route.params.id)
    await urgencyKeywordStore.deleteKeyword(id)
    router.push({ name: 'urgency-keywords' })
  } catch (e) {
    console.error('Erro ao excluir keyword:', e)
  }
}
</script>
