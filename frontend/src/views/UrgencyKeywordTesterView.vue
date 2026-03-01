<template>
  <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
    <!-- Header -->
    <div class="mb-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">Testador de Keywords</h1>
          <p class="mt-1 text-sm text-gray-600">
            Teste padrões de keywords e analise textos para detecção de urgência
          </p>
        </div>
        <button
          @click="$router.push({ name: 'urgency-keywords' })"
          class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50"
        >
          Voltar para Keywords
        </button>
      </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <!-- Pattern Tester -->
      <div class="bg-white shadow rounded-lg">
        <div class="px-6 py-5 border-b border-gray-200">
          <h2 class="text-lg font-medium text-gray-900">Testar Padrão</h2>
          <p class="mt-1 text-sm text-gray-500">
            Teste uma keyword específica contra um texto
          </p>
        </div>

        <div class="px-6 py-5 space-y-4">
          <!-- Keyword Input -->
          <div>
            <label for="test-keyword" class="block text-sm font-medium text-gray-700">
              Keyword / Padrão
            </label>
            <input
              id="test-keyword"
              v-model="testForm.keyword"
              type="text"
              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
              placeholder="ex: preso, fogo, elevador.*parado"
            />
          </div>

          <!-- Match Type -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Tipo de Correspondência
            </label>
            <div class="grid grid-cols-3 gap-2">
              <button
                v-for="type in matchTypes"
                :key="type.value"
                type="button"
                @click="testForm.match_type = type.value"
                class="px-3 py-2 text-sm font-medium rounded-md"
                :class="{
                  'bg-indigo-600 text-white': testForm.match_type === type.value,
                  'bg-gray-100 text-gray-700 hover:bg-gray-200': testForm.match_type !== type.value
                }"
              >
                {{ type.label }}
              </button>
            </div>
          </div>

          <!-- Settings -->
          <div class="space-y-2">
            <div class="flex items-center">
              <input
                id="test-case-sensitive"
                v-model="testForm.settings.case_sensitive"
                type="checkbox"
                class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
              />
              <label for="test-case-sensitive" class="ml-2 text-sm text-gray-700">
                Case Sensitive
              </label>
            </div>
            <div class="flex items-center">
              <input
                id="test-whole-word"
                v-model="testForm.settings.whole_word"
                type="checkbox"
                class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
              />
              <label for="test-whole-word" class="ml-2 text-sm text-gray-700">
                Palavra Completa
              </label>
            </div>
          </div>

          <!-- Test Text -->
          <div>
            <label for="test-text" class="block text-sm font-medium text-gray-700">
              Texto para Testar
            </label>
            <textarea
              id="test-text"
              v-model="testForm.text"
              rows="4"
              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
              placeholder="Digite o texto que deseja testar..."
            ></textarea>
          </div>

          <!-- Test Button -->
          <button
            @click="handleTest"
            :disabled="!testForm.keyword || !testForm.text || testLoading"
            class="w-full inline-flex justify-center items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            {{ testLoading ? 'Testando...' : 'Testar Padrão' }}
          </button>

          <!-- Test Result -->
          <div v-if="testResult" class="rounded-md p-4" :class="testResult.matches ? 'bg-green-50' : 'bg-gray-50'">
            <div class="flex items-start">
              <div class="flex-shrink-0">
                <svg
                  v-if="testResult.matches"
                  class="h-6 w-6 text-green-400"
                  fill="none"
                  viewBox="0 0 24 24"
                  stroke="currentColor"
                >
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                <svg
                  v-else
                  class="h-6 w-6 text-gray-400"
                  fill="none"
                  viewBox="0 0 24 24"
                  stroke="currentColor"
                >
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </div>
              <div class="ml-3 flex-1">
                <h3 class="text-sm font-medium" :class="testResult.matches ? 'text-green-800' : 'text-gray-800'">
                  {{ testResult.matches ? '✓ Correspondência Encontrada' : '✗ Nenhuma Correspondência' }}
                </h3>
                <div class="mt-2 text-sm" :class="testResult.matches ? 'text-green-700' : 'text-gray-700'">
                  <p><strong>Keyword:</strong> {{ testResult.keyword }}</p>
                  <p><strong>Tipo:</strong> {{ testResult.settings.match_type }}</p>
                  <p v-if="testResult.settings.case_sensitive"><strong>Case Sensitive:</strong> Sim</p>
                  <p v-if="testResult.settings.whole_word"><strong>Palavra Completa:</strong> Sim</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Urgency Analyzer -->
      <div class="bg-white shadow rounded-lg">
        <div class="px-6 py-5 border-b border-gray-200">
          <h2 class="text-lg font-medium text-gray-900">Analisar Urgência</h2>
          <p class="mt-1 text-sm text-gray-500">
            Analise um texto completo com todas as keywords ativas
          </p>
        </div>

        <div class="px-6 py-5 space-y-4">
          <!-- Company Selector -->
          <div>
            <label for="analyze-company" class="block text-sm font-medium text-gray-700">
              Empresa (Opcional)
            </label>
            <select
              id="analyze-company"
              v-model="analyzeForm.company_id"
              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
            >
              <option :value="null">Global (todas as keywords)</option>
              <option v-for="company in companies" :key="company.id" :value="company.id">
                {{ company.name }}
              </option>
            </select>
            <p class="mt-1 text-xs text-gray-500">
              Selecione uma empresa para usar keywords específicas
            </p>
          </div>

          <!-- Analyze Text -->
          <div>
            <label for="analyze-text" class="block text-sm font-medium text-gray-700">
              Texto para Analisar
            </label>
            <textarea
              id="analyze-text"
              v-model="analyzeForm.text"
              rows="6"
              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
              placeholder="Digite o texto que deseja analisar para detectar urgência..."
            ></textarea>
          </div>

          <!-- Quick Test Buttons -->
          <div class="flex space-x-2">
            <button
              @click="analyzeForm.text = 'Olá, preciso de ajuda com a limpeza da piscina'"
              type="button"
              class="text-xs px-2 py-1 border border-gray-300 rounded text-gray-600 hover:bg-gray-50"
            >
              Teste Normal
            </button>
            <button
              @click="analyzeForm.text = 'Socorro! Tem uma pessoa presa no elevador e está gritando!'"
              type="button"
              class="text-xs px-2 py-1 border border-gray-300 rounded text-gray-600 hover:bg-gray-50"
            >
              Teste Urgente
            </button>
            <button
              @click="analyzeForm.text = 'Tem fumaça saindo do apartamento 302, acho que é fogo!'"
              type="button"
              class="text-xs px-2 py-1 border border-gray-300 rounded text-gray-600 hover:bg-gray-50"
            >
              Teste Crítico
            </button>
          </div>

          <!-- Analyze Button -->
          <button
            @click="handleAnalyze"
            :disabled="!analyzeForm.text || analyzeLoading"
            class="w-full inline-flex justify-center items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            {{ analyzeLoading ? 'Analisando...' : 'Analisar Texto' }}
          </button>

          <!-- Analyze Result -->
          <div v-if="analyzeResult" class="space-y-3">
            <!-- Urgency Status -->
            <div class="rounded-md p-4" :class="analyzeResult.is_urgent ? 'bg-red-50' : 'bg-green-50'">
              <div class="flex items-center">
                <div class="flex-shrink-0">
                  <svg
                    v-if="analyzeResult.is_urgent"
                    class="h-8 w-8 text-red-400"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                  >
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                  </svg>
                  <svg
                    v-else
                    class="h-8 w-8 text-green-400"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                  >
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                </div>
                <div class="ml-3 flex-1">
                  <h3 class="text-lg font-medium" :class="analyzeResult.is_urgent ? 'text-red-800' : 'text-green-800'">
                    {{ analyzeResult.is_urgent ? '⚠ URGÊNCIA DETECTADA' : '✓ Sem Urgência' }}
                  </h3>
                  <p class="mt-1 text-sm" :class="analyzeResult.is_urgent ? 'text-red-700' : 'text-green-700'">
                    Nível de Prioridade: {{ analyzeResult.priority_level }}
                  </p>
                  <div class="flex items-center mt-1">
                    <svg
                      v-for="star in 5"
                      :key="star"
                      class="h-5 w-5"
                      :class="star <= analyzeResult.priority_level ? 'text-yellow-400' : 'text-gray-300'"
                      fill="currentColor"
                      viewBox="0 0 20 20"
                    >
                      <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                    </svg>
                  </div>
                </div>
              </div>
            </div>

            <!-- Matched Keywords -->
            <div v-if="analyzeResult.matched_keywords.length > 0" class="rounded-md bg-blue-50 p-4">
              <h4 class="text-sm font-medium text-blue-800 mb-2">
                Keywords Detectadas ({{ analyzeResult.matched_keywords.length }})
              </h4>
              <div class="space-y-2">
                <div
                  v-for="(keyword, index) in analyzeResult.matched_keywords"
                  :key="index"
                  class="flex items-center justify-between bg-white rounded px-3 py-2"
                >
                  <div class="flex items-center space-x-3">
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-purple-100 text-purple-800">
                      {{ keyword.keyword }}
                    </span>
                    <span class="text-sm text-gray-600">{{ keyword.match_type }}</span>
                  </div>
                  <div class="flex items-center">
                    <svg
                      v-for="star in 5"
                      :key="star"
                      class="h-4 w-4"
                      :class="star <= keyword.priority_level ? 'text-yellow-400' : 'text-gray-300'"
                      fill="currentColor"
                      viewBox="0 0 20 20"
                    >
                      <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                    </svg>
                  </div>
                </div>
              </div>
            </div>

            <!-- No Keywords Found -->
            <div v-else class="rounded-md bg-gray-50 p-4">
              <p class="text-sm text-gray-600">
                Nenhuma keyword de urgência foi detectada neste texto.
              </p>
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
import { useUrgencyKeywordStore } from '../stores/urgencyKeywordStore'
import { useCompanyStore } from '../stores/companyStore'
import type { TestResult, AnalyzeResult } from '../services/urgencyKeywordService'

const urgencyKeywordStore = useUrgencyKeywordStore()
const companyStore = useCompanyStore()

const { companies } = storeToRefs(companyStore)

const matchTypes = [
  { value: 'exact', label: 'Exata' },
  { value: 'contains', label: 'Contém' },
  { value: 'regex', label: 'Regex' }
]

// Test Form
const testForm = ref({
  keyword: '',
  match_type: 'contains' as 'exact' | 'contains' | 'regex',
  text: '',
  settings: {
    match_type: 'contains' as 'exact' | 'contains' | 'regex',
    case_sensitive: false,
    whole_word: false
  }
})

const testLoading = ref(false)
const testResult = ref<TestResult | null>(null)

// Analyze Form
const analyzeForm = ref({
  text: '',
  company_id: null as number | null
})

const analyzeLoading = ref(false)
const analyzeResult = ref<AnalyzeResult | null>(null)

onMounted(async () => {
  await companyStore.fetchCompanies()
})

const handleTest = async () => {
  try {
    testLoading.value = true
    testResult.value = null

    // Update settings match_type to match selected match_type
    testForm.value.settings.match_type = testForm.value.match_type

    const result = await urgencyKeywordStore.testKeyword({
      keyword: testForm.value.keyword,
      match_type: testForm.value.match_type,
      text: testForm.value.text,
      settings: testForm.value.settings
    })

    testResult.value = result
  } catch (error) {
    console.error('Erro ao testar keyword:', error)
  } finally {
    testLoading.value = false
  }
}

const handleAnalyze = async () => {
  try {
    analyzeLoading.value = true
    analyzeResult.value = null

    const result = await urgencyKeywordStore.analyzeText(
      analyzeForm.value.text,
      analyzeForm.value.company_id
    )

    analyzeResult.value = result
  } catch (error) {
    console.error('Erro ao analisar texto:', error)
  } finally {
    analyzeLoading.value = false
  }
}
</script>
