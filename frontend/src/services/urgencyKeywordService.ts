import apiClient from './api'

export interface UrgencyKeyword {
  id?: number
  keyword: string
  match_type: 'exact' | 'contains' | 'regex'
  description?: string | null
  priority_level?: number
  company_id?: number | null
  case_sensitive?: boolean
  whole_word?: boolean
  active?: boolean
  created_at?: string
  updated_at?: string
  deleted_at?: string | null
  company?: {
    id: number
    name: string
  } | null
}

export interface UrgencyKeywordListResponse {
  data: UrgencyKeyword[]
  current_page: number
  last_page: number
  per_page: number
  total: number
}

export interface UrgencyKeywordFilters {
  search?: string
  active_only?: boolean
  company_id?: number | 'global' | null
  sort_by?: string
  sort_direction?: 'asc' | 'desc'
  page?: number
  per_page?: number
}

export interface AnalyzeResult {
  is_urgent: boolean
  matched_keywords: Array<{
    id: number
    keyword: string
    description: string
    priority_level: number
  }>
  priority_level: number
}

export interface TestResult {
  matches: boolean
  keyword: string
  match_type: string
  text: string
  settings: {
    case_sensitive: boolean
    whole_word: boolean
  }
}

class UrgencyKeywordService {
  async list(filters: UrgencyKeywordFilters = {}): Promise<UrgencyKeywordListResponse> {
    const params = new URLSearchParams()
    
    if (filters.search) params.append('search', filters.search)
    if (filters.active_only !== undefined) params.append('active_only', filters.active_only ? '1' : '0')
    if (filters.company_id !== undefined && filters.company_id !== null) {
      params.append('company_id', filters.company_id.toString())
    }
    if (filters.sort_by) params.append('sort_by', filters.sort_by)
    if (filters.sort_direction) params.append('sort_direction', filters.sort_direction)
    if (filters.page) params.append('page', filters.page.toString())
    if (filters.per_page) params.append('per_page', filters.per_page.toString())

    const response = await apiClient.get(`/urgency-keywords?${params.toString()}`)
    return response.data
  }

  async get(id: number): Promise<UrgencyKeyword> {
    const response = await apiClient.get(`/urgency-keywords/${id}`)
    return response.data
  }

  async create(keyword: UrgencyKeyword): Promise<UrgencyKeyword> {
    const response = await apiClient.post('/urgency-keywords', keyword)
    return response.data.data
  }

  async update(id: number, keyword: Partial<UrgencyKeyword>): Promise<UrgencyKeyword> {
    const response = await apiClient.put(`/urgency-keywords/${id}`, keyword)
    return response.data.data
  }

  async delete(id: number): Promise<void> {
    await apiClient.delete(`/urgency-keywords/${id}`)
  }

  async restore(id: number): Promise<UrgencyKeyword> {
    const response = await apiClient.post(`/urgency-keywords/${id}/restore`)
    return response.data.data
  }

  async test(data: {
    keyword: string
    match_type: 'exact' | 'contains' | 'regex'
    text: string
    case_sensitive?: boolean
    whole_word?: boolean
  }): Promise<TestResult> {
    const response = await apiClient.post('/urgency-keywords/test', data)
    return response.data
  }

  async analyze(text: string, companyId?: number): Promise<AnalyzeResult> {
    const response = await apiClient.post('/urgency-keywords/analyze', {
      text,
      company_id: companyId
    })
    return response.data
  }
}

export default new UrgencyKeywordService()
