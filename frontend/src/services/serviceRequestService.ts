import apiClient from './api'

export interface WhatsappInstance {
  id: number
  name: string
  status: 'disconnected' | 'qr_required' | 'connecting' | 'connected'
  phone_number: string | null
}

export interface Attendant {
  id: number
  name: string
  role: string
}

export interface ServiceRequest {
  id: number
  company_id: number
  whatsapp_instance_id: number | null
  attendant_id: number | null
  contact_name: string
  contact_phone: string
  contact_message: string
  status: ServiceRequestStatus
  urgency_level: UrgencyLevel
  urgency_keywords: string[]
  channel: Channel
  collected_data: Record<string, any> | null
  api_response: Record<string, any> | null
  api_sent_at: string | null
  api_attempts: number
  external_ticket_id: string | null
  attended_at: string | null
  resolved_at: string | null
  notes: string | null
  created_at: string
  updated_at: string
  attendant?: Attendant | null
  whatsapp_instance?: WhatsappInstance | null
}

export type ServiceRequestStatus =
  | 'pending'
  | 'ai_collecting'
  | 'awaiting_review'
  | 'in_progress'
  | 'confirmed_manual'
  | 'sent_api'
  | 'resolved'
  | 'failed'

export type UrgencyLevel = 'normal' | 'urgent' | 'critical'
export type Channel = 'whatsapp' | 'voip' | 'manual'

export interface ServiceRequestStats {
  total: number
  pending: number
  urgent: number
  resolved: number
}

export interface ServiceRequestFilters {
  status?: ServiceRequestStatus
  urgency_level?: UrgencyLevel
  channel?: Channel
  search?: string
  per_page?: number
  page?: number
}

export interface PaginatedResponse<T> {
  data: T[]
  total: number
  per_page: number
  current_page: number
  last_page: number
  from: number | null
  to: number | null
}

export interface Message {
  id: number
  service_request_id: number
  direction: 'inbound' | 'outbound'
  sender_type: 'contact' | 'attendant' | 'ai' | 'system'
  sender_id: number | null
  content: string | null
  media_url: string | null
  media_type: 'image' | 'audio' | 'video' | 'document' | null
  whatsapp_message_id: string | null
  is_read: boolean
  created_at: string
  updated_at: string
  sender?: Attendant | null
}

const serviceRequestService = {
  async getStats(): Promise<ServiceRequestStats> {
    const { data } = await apiClient.get('/v1/service-requests/stats')
    return data
  },

  async list(filters: ServiceRequestFilters = {}): Promise<PaginatedResponse<ServiceRequest>> {
    const { data } = await apiClient.get('/v1/service-requests', { params: filters })
    return data
  },

  async get(id: number): Promise<ServiceRequest> {
    const { data } = await apiClient.get(`/v1/service-requests/${id}`)
    return data
  },

  async create(payload: {
    contact_name: string
    contact_phone: string
    contact_message: string
    notes?: string
  }): Promise<ServiceRequest> {
    const { data } = await apiClient.post('/v1/service-requests', payload)
    return data
  },

  async assign(id: number): Promise<ServiceRequest> {
    const { data } = await apiClient.post(`/v1/service-requests/${id}/assign`)
    return data
  },

  async updateStatus(
    id: number,
    status: ServiceRequestStatus,
    notes?: string,
  ): Promise<ServiceRequest> {
    const { data } = await apiClient.patch(`/v1/service-requests/${id}/status`, { status, notes })
    return data
  },

  async getMessages(serviceRequestId: number): Promise<Message[]> {
    const { data } = await apiClient.get(`/v1/service-requests/${serviceRequestId}/messages`)
    return data
  },

  async sendMessage(
    serviceRequestId: number,
    payload: { content?: string; media_url?: string; media_type?: string },
  ): Promise<Message> {
    const { data } = await apiClient.post(
      `/v1/service-requests/${serviceRequestId}/messages`,
      payload,
    )
    return data
  },
}

export default serviceRequestService
