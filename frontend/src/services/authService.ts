import { apiClient } from './api'

export interface User {
  id: number
  name: string
  email: string
  role: 'admin' | 'attendant'
  is_active: boolean
  company_id: number | null
  last_login_at: string | null
  created_at: string
}

export interface LoginRequest {
  email: string
  password: string
}

export interface LoginResponse {
  user: User
  token: string
}

export interface AuthMeResponse {
  user: User
}

class AuthService {
  /**
   * Login com email e senha
   */
  async login(credentials: LoginRequest): Promise<LoginResponse> {
    const response = await apiClient.post<LoginResponse>('/auth/login', credentials)
    return response.data
  }

  /**
   * Obter usuário autenticado
   */
  async me(): Promise<AuthMeResponse> {
    const response = await apiClient.get<AuthMeResponse>('/auth/me')
    return response.data
  }

  /**
   * Logout do usuário
   */
  async logout(): Promise<void> {
    await apiClient.post('/auth/logout')
  }

  /**
   * Refresh token
   */
  async refresh(): Promise<LoginResponse> {
    const response = await apiClient.post<LoginResponse>('/auth/refresh')
    return response.data
  }

  /**
   * Obter token do localStorage
   */
  getToken(): string | null {
    return localStorage.getItem('auth_token')
  }

  /**
   * Guardar token no localStorage
   */
  setToken(token: string): void {
    localStorage.setItem('auth_token', token)
    // Atualizar header do axios
    apiClient.defaults.headers.common['Authorization'] = `Bearer ${token}`
  }

  /**
   * Remover token
   */
  removeToken(): void {
    localStorage.removeItem('auth_token')
    delete apiClient.defaults.headers.common['Authorization']
  }

  /**
   * Verificar se está autenticado
   */
  isAuthenticated(): boolean {
    return !!this.getToken()
  }
}

export default new AuthService()
