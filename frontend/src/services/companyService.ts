import apiClient from './api'

export interface Company {
  id?: number
  name: string
  document?: string | null
  email?: string | null
  phone?: string | null
  address?: string | null
  city?: string | null
  state?: string | null
  zip_code?: string | null
  whatsapp_number?: string | null
  business_hours?: string
  timezone?: string
  max_users?: number
  max_simultaneous_chats?: number
  required_fields?: Record<string, any>
  api_endpoint?: string | null
  api_method?: 'POST' | 'PUT' | 'PATCH'
  api_headers?: Record<string, any> | null
  api_key?: string | null
  api_enabled?: boolean
  ai_prompt?: string | null
  ai_temperature?: number
  ai_max_tokens?: number
  active?: boolean
  notes?: string | null
  created_at?: string
  updated_at?: string
  deleted_at?: string | null
}

export interface CompanyListResponse {
  data: Company[]
  current_page: number
  last_page: number
  per_page: number
  total: number
}

export interface CompanyFilters {
  search?: string
  active_only?: boolean
  sort_by?: string
  sort_direction?: 'asc' | 'desc'
  page?: number
  per_page?: number
}

class CompanyService {
  async list(filters: CompanyFilters = {}): Promise<CompanyListResponse> {
    const params = new URLSearchParams()
    
    if (filters.search) params.append('search', filters.search)
    if (filters.active_only !== undefined) params.append('active_only', filters.active_only ? '1' : '0')
    if (filters.sort_by) params.append('sort_by', filters.sort_by)
    if (filters.sort_direction) params.append('sort_direction', filters.sort_direction)
    if (filters.page) params.append('page', filters.page.toString())
    if (filters.per_page) params.append('per_page', filters.per_page.toString())

    const response = await apiClient.get(`/companies?${params.toString()}`)
    return response.data
  }

  async get(id: number): Promise<Company> {
    const response = await apiClient.get(`/companies/${id}`)
    return response.data
  }

  async create(company: Company): Promise<Company> {
    const response = await apiClient.post('/companies', company)
    return response.data.data
  }

  async update(id: number, company: Partial<Company>): Promise<Company> {
    const response = await apiClient.put(`/companies/${id}`, company)
    return response.data.data
  }

  async delete(id: number): Promise<void> {
    await apiClient.delete(`/companies/${id}`)
  }

  async restore(id: number): Promise<Company> {
    const response = await apiClient.post(`/companies/${id}/restore`)
    return response.data.data
  }
}

export default new CompanyService()
