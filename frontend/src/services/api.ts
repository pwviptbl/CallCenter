import axios, { type AxiosInstance } from 'axios'

const apiClient: AxiosInstance = axios.create({
  baseURL: import.meta.env.VITE_API_URL || 'http://localhost:8000/api/v1',
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json'
  },
  timeout: 10000
})

// Request interceptor (para adicionar token de autenticação futuramente)
apiClient.interceptors.request.use(
  (config) => {
    // TODO: Adicionar token quando implementar autenticação
    // const token = localStorage.getItem('auth_token')
    // if (token) {
    //   config.headers.Authorization = `Bearer ${token}`
    // }
    return config
  },
  (error) => {
    return Promise.reject(error)
  }
)

// Response interceptor (para tratar erros globalmente)
apiClient.interceptors.response.use(
  (response) => {
    return response
  },
  (error) => {
    if (error.response?.status === 401) {
      // TODO: Redirecionar para login quando implementar autenticação
      console.warn('Não autenticado')
    }
    return Promise.reject(error)
  }
)

export default apiClient
