<template>
  <div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
      <!-- Header -->
      <div class="mb-8">
        <div class="flex items-center">
          <router-link
            to="/companies"
            class="mr-4 text-gray-600 hover:text-gray-900"
          >
            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
          </router-link>
          <div>
            <h1 class="text-3xl font-bold text-gray-900">
              {{ isEditMode ? 'Editar Empresa' : 'Nova Empresa' }}
            </h1>
            <p class="mt-2 text-sm text-gray-600">
              {{ isEditMode ? 'Atualize as informações da empresa' : 'Preencha os dados da nova empresa' }}
            </p>
          </div>
        </div>
      </div>

      <!-- Errors -->
      <div v-if="error" class="mb-6 bg-red-50 border border-red-200 rounded-md p-4">
        <div class="flex">
          <div class="flex-shrink-0">
            <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
            </svg>
          </div>
          <div class="ml-3">
            <p class="text-sm text-red-800">{{ error }}</p>
          </div>
        </div>
      </div>

      <!-- Form -->
      <form @submit.prevent="handleSubmit" class="space-y-6">
        <!-- Tabs -->
        <div class="bg-white shadow rounded-lg">
          <div class="border-b border-gray-200">
            <nav class="-mb-px flex space-x-8 px-6" aria-label="Tabs">
              <button
                v-for="tab in tabs"
                :key="tab.id"
                type="button"
                @click="currentTab = tab.id"
                :class="[
                  currentTab === tab.id
                    ? 'border-indigo-500 text-indigo-600'
                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
                  'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm'
                ]"
              >
                {{ tab.name }}
              </button>
            </nav>
          </div>

          <div class="p-6">
            <!-- Tab: Dados Gerais -->
            <div v-show="currentTab === 'general'" class="space-y-6">
              <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                <div class="sm:col-span-2">
                  <label class="block text-sm font-medium text-gray-700">Nome da Empresa *</label>
                  <input
                    v-model="formData.name"
                    type="text"
                    required
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                  />
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-700">CNPJ</label>
                  <input
                    v-model="formData.document"
                    type="text"
                    placeholder="00.000.000/0000-00"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                  />
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-700">E-mail</label>
                  <input
                    v-model="formData.email"
                    type="email"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                  />
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-700">Telefone</label>
                  <input
                    v-model="formData.phone"
                    type="text"
                    placeholder="(00) 0000-0000"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                  />
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-700">WhatsApp</label>
                  <input
                    v-model="formData.whatsapp_number"
                    type="text"
                    placeholder="5511999999999"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                  />
                </div>

                <div class="sm:col-span-2">
                  <label class="block text-sm font-medium text-gray-700">Endereço</label>
                  <input
                    v-model="formData.address"
                    type="text"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                  />
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-700">Cidade</label>
                  <input
                    v-model="formData.city"
                    type="text"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                  />
                </div>

                <div class="grid grid-cols-2 gap-4">
                  <div>
                    <label class="block text-sm font-medium text-gray-700">Estado</label>
                    <input
                      v-model="formData.state"
                      type="text"
                      maxlength="2"
                      placeholder="SP"
                      class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    />
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700">CEP</label>
                    <input
                      v-model="formData.zip_code"
                      type="text"
                      placeholder="00000-000"
                      class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    />
                  </div>
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-700">Horário de Atendimento</label>
                  <input
                    v-model="formData.business_hours"
                    type="text"
                    placeholder="08:00-18:00"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                  />
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-700">Timezone</label>
                  <input
                    v-model="formData.timezone"
                    type="text"
                    placeholder="America/Sao_Paulo"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                  />
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-700">Máximo de Usuários</label>
                  <input
                    v-model.number="formData.max_users"
                    type="number"
                    min="1"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                  />
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-700">Máximo de Chats Simultâneos</label>
                  <input
                    v-model.number="formData.max_simultaneous_chats"
                    type="number"
                    min="1"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                  />
                </div>

                <div class="sm:col-span-2">
                  <label class="flex items-center">
                    <input
                      v-model="formData.active"
                      type="checkbox"
                      class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    />
                    <span class="ml-2 text-sm text-gray-700">Empresa Ativa</span>
                  </label>
                </div>

                <div class="sm:col-span-2">
                  <label class="block text-sm font-medium text-gray-700">Observações</label>
                  <textarea
                    v-model="formData.notes"
                    rows="3"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                  ></textarea>
                </div>
              </div>
            </div>

            <!-- Tab: Campos Obrigatórios -->
            <div v-show="currentTab === 'fields'" class="space-y-6">
              <p class="text-sm text-gray-600">
                Configure quais campos são obrigatórios para esta empresa no atendimento.
              </p>
              <div class="space-y-3">
                <div v-for="(value, key) in formData.required_fields" :key="key" class="flex items-center">
                  <input
                    v-model="formData.required_fields[key]"
                    type="checkbox"
                    :id="`field-${key}`"
                    class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                  />
                  <label :for="`field-${key}`" class="ml-2 text-sm text-gray-700 capitalize">
                    {{ key.replace('_', ' ') }}
                  </label>
                </div>
              </div>
              <div class="flex items-center space-x-2">
                <input
                  v-model="newFieldName"
                  type="text"
                  placeholder="Nome do novo campo"
                  class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                  @keyup.enter="addField"
                />
                <button
                  type="button"
                  @click="addField"
                  class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700"
                >
                  Adicionar Campo
                </button>
              </div>
            </div>

            <!-- Tab: API -->
            <div v-show="currentTab === 'api'" class="space-y-6">
              <div>
                <label class="flex items-center">
                  <input
                    v-model="formData.api_enabled"
                    type="checkbox"
                    class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                  />
                  <span class="ml-2 text-sm font-medium text-gray-700">Habilitar Integração API</span>
                </label>
              </div>

              <div v-if="formData.api_enabled" class="space-y-6">
                <div>
                  <label class="block text-sm font-medium text-gray-700">Endpoint da API</label>
                  <input
                    v-model="formData.api_endpoint"
                    type="url"
                    placeholder="https://api.exemplo.com/chamados"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                  />
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-700">Método HTTP</label>
                  <select
                    v-model="formData.api_method"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                  >
                    <option value="POST">POST</option>
                    <option value="PUT">PUT</option>
                    <option value="PATCH">PATCH</option>
                  </select>
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-700">Chave da API</label>
                  <input
                    v-model="formData.api_key"
                    type="password"
                    placeholder="Chave secreta da API"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                  />
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-700">Headers (JSON)</label>
                  <textarea
                    v-model="apiHeadersText"
                    rows="4"
                    placeholder='{"Authorization": "Bearer token", "Content-Type": "application/json"}'
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 font-mono text-sm"
                  ></textarea>
                </div>
              </div>
            </div>

            <!-- Tab: IA -->
            <div v-show="currentTab === 'ai'" class="space-y-6">
              <div>
                <label class="block text-sm font-medium text-gray-700">Prompt Customizado</label>
                <textarea
                  v-model="formData.ai_prompt"
                  rows="6"
                  placeholder="Instruções personalizadas para a IA..."
                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                ></textarea>
                <p class="mt-1 text-sm text-gray-500">
                  Deixe em branco para usar o prompt padrão do sistema.
                </p>
              </div>

              <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                <div>
                  <label class="block text-sm font-medium text-gray-700">Temperature</label>
                  <input
                    v-model.number="formData.ai_temperature"
                    type="number"
                    min="0"
                    max="2"
                    step="0.1"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                  />
                  <p class="mt-1 text-sm text-gray-500">0.0 = mais preciso, 2.0 = mais criativo</p>
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-700">Máximo de Tokens</label>
                  <input
                    v-model.number="formData.ai_max_tokens"
                    type="number"
                    min="50"
                    max="2000"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                  />
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Actions -->
        <div class="flex justify-end space-x-3">
          <router-link
            to="/companies"
            class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50"
          >
            Cancelar
          </router-link>
          <button
            type="submit"
            :disabled="loading"
            class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50"
          >
            {{ loading ? 'Salvando...' : 'Salvar Empresa' }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { storeToRefs } from 'pinia'
import { useCompanyStore } from '@/stores/companyStore'
import type { Company } from '@/services/companyService'

const route = useRoute()
const router = useRouter()
const companyStore = useCompanyStore()
const { loading, error } = storeToRefs(companyStore)

const isEditMode = computed(() => !!route.params.id)

const tabs = [
  { id: 'general', name: 'Dados Gerais' },
  { id: 'fields', name: 'Campos Obrigatórios' },
  { id: 'api', name: 'Integração API' },
  { id: 'ai', name: 'Configuração IA' }
]

const currentTab = ref('general')
const newFieldName = ref('')
const apiHeadersText = ref('')

const formData = ref<Company>({
  name: '',
  document: null,
  email: null,
  phone: null,
  address: null,
  city: null,
  state: null,
  zip_code: null,
  whatsapp_number: null,
  business_hours: '08:00-18:00',
  timezone: 'America/Sao_Paulo',
  max_users: 10,
  max_simultaneous_chats: 5,
  required_fields: {
    name: true,
    email: true,
    phone: false
  },
  api_endpoint: null,
  api_method: 'POST',
  api_headers: null,
  api_key: null,
  api_enabled: false,
  ai_prompt: null,
  ai_temperature: 0.7,
  ai_max_tokens: 500,
  active: true,
  notes: null
})

function addField() {
  if (newFieldName.value.trim()) {
    const fieldKey = newFieldName.value.trim().toLowerCase().replace(/\s+/g, '_')
    if (!formData.value.required_fields) {
      formData.value.required_fields = {}
    }
    formData.value.required_fields[fieldKey] = false
    newFieldName.value = ''
  }
}

watch(apiHeadersText, (newValue) => {
  if (newValue.trim()) {
    try {
      formData.value.api_headers = JSON.parse(newValue)
    } catch (e) {
      // Ignora erro de parsing enquanto usuário digita
    }
  } else {
    formData.value.api_headers = null
  }
})

async function handleSubmit() {
  try {
    if (isEditMode.value) {
      await companyStore.updateCompany(Number(route.params.id), formData.value)
    } else {
      await companyStore.createCompany(formData.value)
    }
    router.push('/companies')
  } catch (err) {
    console.error('Erro ao salvar empresa:', err)
  }
}

onMounted(async () => {
  if (isEditMode.value) {
    await companyStore.fetchCompany(Number(route.params.id))
    const company = companyStore.currentCompany
    if (company) {
      formData.value = { ...company }
      if (company.api_headers) {
        apiHeadersText.value = JSON.stringify(company.api_headers, null, 2)
      }
    }
  }
})
</script>
