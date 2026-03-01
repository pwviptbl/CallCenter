import apiClient from './api'

export interface User {
  id: number
  name: string
  email: string
  role: 'admin' | 'attendant'
  is_active: boolean
  company_id: number | null
  last_login_at: string | null
  created_at: string
  company?: { id: number; name: string }
}

export interface UserFilters {
  search?: string
  role?: 'admin' | 'attendant' | ''
  is_active?: boolean | ''
  company_id?: number | ''
  page?: number
  per_page?: number
}

export interface PaginatedUsers {
  data: User[]
  current_page: number
  last_page: number
  per_page: number
  total: number
}

export interface CreateUserData {
  name: string
  email: string
  password: string
  role: 'admin' | 'attendant'
  is_active?: boolean
  company_id?: number | null
}

export interface UpdateUserData {
  name?: string
  email?: string
  password?: string
  role?: 'admin' | 'attendant'
  is_active?: boolean
  company_id?: number | null
}

class UserService {
  async list(filters: UserFilters = {}): Promise<PaginatedUsers> {
    const params: Record<string, string | number | boolean> = {}
    if (filters.search) params.search = filters.search
    if (filters.role !== undefined && filters.role !== '') params.role = filters.role
    if (filters.is_active !== undefined && filters.is_active !== '') {
      params.is_active = filters.is_active as boolean
    }
    if (filters.company_id) params.company_id = filters.company_id
    if (filters.page) params.page = filters.page
    if (filters.per_page) params.per_page = filters.per_page

    const response = await apiClient.get<PaginatedUsers>('/users', { params })
    return response.data
  }

  async get(id: number): Promise<{ data: User }> {
    const response = await apiClient.get<{ data: User }>(`/users/${id}`)
    return response.data
  }

  async create(data: CreateUserData): Promise<{ data: User; message: string }> {
    const response = await apiClient.post<{ data: User; message: string }>('/users', data)
    return response.data
  }

  async update(id: number, data: UpdateUserData): Promise<{ data: User; message: string }> {
    const response = await apiClient.put<{ data: User; message: string }>(`/users/${id}`, data)
    return response.data
  }

  async delete(id: number): Promise<{ message: string }> {
    const response = await apiClient.delete<{ message: string }>(`/users/${id}`)
    return response.data
  }

  async toggleActive(id: number): Promise<{ data: User; message: string }> {
    const response = await apiClient.patch<{ data: User; message: string }>(
      `/users/${id}/toggle-active`
    )
    return response.data
  }

  async setRole(id: number, role: 'admin' | 'attendant'): Promise<{ data: User; message: string }> {
    const response = await apiClient.patch<{ data: User; message: string }>(
      `/users/${id}/set-role`,
      { role }
    )
    return response.data
  }
}

export default new UserService()
