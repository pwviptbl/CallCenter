<script setup lang="ts">
import { onMounted, ref } from 'vue'
import apiClient from '../services/api'

interface WhatsappInstance {
  id: number
  name: string
  instance_key: string
  status: 'disconnected' | 'qr_required' | 'connecting' | 'connected'
  phone_number: string | null
  evolution_api_url: string
  is_active: boolean
  created_at: string
}

const instances = ref<WhatsappInstance[]>([])
const loading = ref(false)
const showForm = ref(false)
const saving = ref(false)
const deleting = ref<number | null>(null)
const errorMsg = ref('')
const successMsg = ref('')

const form = ref({
  id: null as number | null,
  name: '',
  instance_key: '',
  evolution_api_url: '',
  evolution_api_token: '',
  is_active: true,
})

const resetForm = () => {
  form.value = { id: null, name: '', instance_key: '', evolution_api_url: '', evolution_api_token: '', is_active: true }
  showForm.value = false
  errorMsg.value = ''
}

const fetchInstances = async () => {
  loading.value = true
  try {
    const { data } = await apiClient.get('/v1/whatsapp-instances')
    instances.value = data
  } catch {
    errorMsg.value = 'Erro ao carregar instâncias.'
  } finally {
    loading.value = false
  }
}

const openCreate = () => {
  resetForm()
  showForm.value = true
}

const openEdit = (inst: WhatsappInstance) => {
  form.value = {
    id: inst.id,
    name: inst.name,
    instance_key: inst.instance_key,
    evolution_api_url: inst.evolution_api_url,
    evolution_api_token: '',
    is_active: inst.is_active,
  }
  showForm.value = true
}

const save = async () => {
  errorMsg.value = ''
  saving.value = true
  try {
    if (form.value.id) {
      // Editar
      const payload: Record<string, any> = {
        name: form.value.name,
        evolution_api_url: form.value.evolution_api_url,
        is_active: form.value.is_active,
      }
      if (form.value.evolution_api_token) {
        payload.evolution_api_token = form.value.evolution_api_token
      }
      await apiClient.put(`/v1/whatsapp-instances/${form.value.id}`, payload)
    } else {
      // Criar
      await apiClient.post('/v1/whatsapp-instances', {
        name: form.value.name,
        instance_key: form.value.instance_key,
        evolution_api_url: form.value.evolution_api_url,
        evolution_api_token: form.value.evolution_api_token,
        is_active: form.value.is_active,
      })
    }
    successMsg.value = form.value.id ? 'Instância atualizada!' : 'Instância criada!'
    resetForm()
    await fetchInstances()
    setTimeout(() => (successMsg.value = ''), 3000)
  } catch (e: any) {
    errorMsg.value = e.response?.data?.message ?? 'Erro ao salvar.'
  } finally {
    saving.value = false
  }
}

const deleteInstance = async (id: number) => {
  if (!confirm('Deseja realmente excluir esta instância?')) return
  deleting.value = id
  try {
    await apiClient.delete(`/v1/whatsapp-instances/${id}`)
    instances.value = instances.value.filter((i) => i.id !== id)
  } catch (e: any) {
    errorMsg.value = e.response?.data?.message ?? 'Erro ao excluir.'
  } finally {
    deleting.value = null
  }
}

const refreshStatus = async (id: number) => {
  try {
    const { data } = await apiClient.get(`/v1/whatsapp-instances/${id}/status`)
    const idx = instances.value.findIndex((i) => i.id === id)
    if (idx !== -1) instances.value[idx].status = data.status
  } catch { /* silencioso */ }
}

// ── Helpers visuais ────────────────────────────────────────────────────────
const statusColor: Record<string, string> = {
  connected:    'bg-green-100 text-green-700 border border-green-300',
  connecting:   'bg-yellow-100 text-yellow-700 border border-yellow-300',
  qr_required:  'bg-blue-100 text-blue-700 border border-blue-300',
  disconnected: 'bg-gray-100 text-gray-600 border border-gray-300',
}

const statusLabel: Record<string, string> = {
  connected:    '🟢 Conectado',
  connecting:   '🟡 Conectando',
  qr_required:  '🔵 QR Code',
  disconnected: '⚫ Desconectado',
}

onMounted(fetchInstances)
</script>

<template>
  <div class="min-h-screen bg-gray-50">
    <!-- Cabeçalho -->
    <div class="bg-white border-b border-gray-200 px-6 py-4">
      <div class="max-w-4xl mx-auto flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">Instâncias WhatsApp</h1>
          <p class="text-sm text-gray-500 mt-0.5">Gerencie as conexões WhatsApp via Evolution API</p>
        </div>
        <button
          @click="openCreate"
          class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition-colors"
        >
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
          </svg>
          Nova instância
        </button>
      </div>
    </div>

    <div class="max-w-4xl mx-auto px-4 py-6 space-y-4">
      <!-- Mensagens de feedback -->
      <div v-if="successMsg" class="px-4 py-3 bg-green-50 border border-green-300 text-green-700 rounded-lg text-sm">
        {{ successMsg }}
      </div>
      <div v-if="errorMsg && !showForm" class="px-4 py-3 bg-red-50 border border-red-300 text-red-700 rounded-lg text-sm">
        {{ errorMsg }}
      </div>

      <!-- Formulário inline -->
      <div v-if="showForm" class="bg-white rounded-xl shadow-sm border border-indigo-200 p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">
          {{ form.id ? 'Editar Instância' : 'Nova Instância' }}
        </h2>

        <div v-if="errorMsg" class="mb-4 px-3 py-2 bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg">
          {{ errorMsg }}
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Nome *</label>
            <input
              v-model="form.name"
              type="text"
              placeholder="Ex: WhatsApp Suporte"
              class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500"
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              Chave da Instância *
              <span v-if="form.id" class="text-gray-400 font-normal">(não editável)</span>
            </label>
            <input
              v-model="form.instance_key"
              type="text"
              :disabled="!!form.id"
              placeholder="Ex: minha-instancia-01"
              class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 disabled:bg-gray-50 disabled:text-gray-400"
            />
          </div>
          <div class="sm:col-span-2">
            <label class="block text-sm font-medium text-gray-700 mb-1">URL da Evolution API *</label>
            <input
              v-model="form.evolution_api_url"
              type="url"
              placeholder="http://evolution-api:8080"
              class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500"
            />
          </div>
          <div class="sm:col-span-2">
            <label class="block text-sm font-medium text-gray-700 mb-1">
              Token da Evolution API *
              <span v-if="form.id" class="text-gray-400 font-normal">(deixe em branco para manter)</span>
            </label>
            <input
              v-model="form.evolution_api_token"
              type="password"
              placeholder="Token de acesso"
              class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500"
            />
          </div>
          <div class="flex items-center gap-2">
            <input v-model="form.is_active" type="checkbox" id="is_active" class="w-4 h-4 rounded text-indigo-600" />
            <label for="is_active" class="text-sm text-gray-700">Instância ativa</label>
          </div>
        </div>

        <div class="flex gap-3 mt-5">
          <button
            @click="save"
            :disabled="saving || !form.name || !form.evolution_api_url"
            class="px-5 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 disabled:opacity-50 transition-colors"
          >
            {{ saving ? 'Salvando...' : (form.id ? 'Salvar alterações' : 'Criar instância') }}
          </button>
          <button
            @click="resetForm"
            class="px-5 py-2 text-gray-600 text-sm font-medium border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors"
          >
            Cancelar
          </button>
        </div>
      </div>

      <!-- Loading -->
      <div v-if="loading" class="flex justify-center py-12">
        <svg class="w-7 h-7 animate-spin text-indigo-500" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
        </svg>
      </div>

      <!-- Lista vazia -->
      <div v-else-if="!instances.length && !showForm" class="bg-white rounded-xl border border-dashed border-gray-300 p-12 text-center text-gray-400">
        <p class="text-sm">Nenhuma instância configurada ainda.</p>
        <button @click="openCreate" class="mt-3 text-indigo-600 text-sm hover:underline">Criar a primeira instância</button>
      </div>

      <!-- Cartões das instâncias -->
      <div v-for="inst in instances" :key="inst.id" class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
        <div class="flex items-start justify-between gap-4">
          <div class="flex-1 min-w-0">
            <div class="flex items-center gap-2 flex-wrap">
              <h3 class="text-base font-semibold text-gray-900">{{ inst.name }}</h3>
              <span
                :class="statusColor[inst.status]"
                class="px-2 py-0.5 rounded-full text-xs font-medium"
              >
                {{ statusLabel[inst.status] }}
              </span>
              <span v-if="!inst.is_active" class="px-2 py-0.5 bg-gray-100 text-gray-500 rounded-full text-xs">
                Inativa
              </span>
            </div>
            <p class="text-xs text-gray-500 mt-1 font-mono">{{ inst.instance_key }}</p>
            <p v-if="inst.phone_number" class="text-sm text-gray-700 mt-0.5">📱 {{ inst.phone_number }}</p>
            <p class="text-xs text-gray-400 mt-0.5 truncate">🔗 {{ inst.evolution_api_url }}</p>
          </div>

          <div class="flex items-center gap-2 shrink-0">
            <button
              @click="refreshStatus(inst.id)"
              class="p-2 text-gray-400 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg transition-colors"
              title="Atualizar status"
            >
              <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
              </svg>
            </button>
            <button
              @click="openEdit(inst)"
              class="p-2 text-gray-400 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg transition-colors"
              title="Editar"
            >
              <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
              </svg>
            </button>
            <button
              @click="deleteInstance(inst.id)"
              :disabled="deleting === inst.id"
              class="p-2 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors disabled:opacity-40"
              title="Excluir"
            >
              <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
              </svg>
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
