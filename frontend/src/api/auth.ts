import apiClient, { USE_MOCK_API } from './client'
import { mockApi } from './mock'
import type { AuthResponse, LoginCredentials, User } from '@/types'

export const authApi = {
  async login(credentials: LoginCredentials): Promise<AuthResponse> {
    if (USE_MOCK_API) {
      const response = await mockApi.login(credentials.email, credentials.password)
      apiClient.setToken(response.access_token)
      return response
    }
    const response = await apiClient.post<AuthResponse>('/auth/login', credentials)
    apiClient.setToken(response.access_token)
    return response
  },

  async logout(): Promise<void> {
    try {
      if (!USE_MOCK_API) {
        await apiClient.post('/auth/logout')
      }
    } finally {
      apiClient.setToken(null)
    }
  },

  async me(): Promise<User> {
    if (USE_MOCK_API) {
      return mockApi.me()
    }
    return apiClient.get<User>('/auth/me')
  },

  async refreshToken(): Promise<AuthResponse> {
    const response = await apiClient.post<AuthResponse>('/auth/refresh')
    apiClient.setToken(response.access_token)
    return response
  },

  // Admin-only: Create a new user
  async createUser(data: {
    name: string
    email: string
    password: string
    password_confirmation: string
    role: 'admin' | 'user'
  }): Promise<{ user: User }> {
    return apiClient.post<{ user: User }>('/auth/register', data)
  },

  // Admin-only: Get all users
  async getUsers(): Promise<User[]> {
    return apiClient.get<User[]>('/auth/users')
  },
}

export default authApi
