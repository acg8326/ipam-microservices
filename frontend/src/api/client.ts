import type { ApiError } from '@/types'

const API_BASE_URL = import.meta.env.VITE_API_URL || 'http://localhost:8080/api'

// Enable mock mode when VITE_MOCK_API is true or when API is unreachable
export const USE_MOCK_API = import.meta.env.VITE_MOCK_API === 'true'

// Token refresh threshold - refresh when less than 5 minutes remaining
const TOKEN_REFRESH_THRESHOLD_MS = 5 * 60 * 1000

class ApiClient {
  private baseUrl: string
  private token: string | null = null
  private tokenExpiry: number | null = null
  private refreshPromise: Promise<void> | null = null
  private isRefreshing = false

  constructor(baseUrl: string) {
    this.baseUrl = baseUrl
    this.token = localStorage.getItem('access_token')
    const expiry = localStorage.getItem('token_expiry')
    this.tokenExpiry = expiry ? parseInt(expiry, 10) : null
    
    // Start background refresh timer
    this.startRefreshTimer()
  }

  setToken(token: string | null, expiresIn?: number) {
    this.token = token
    if (token && expiresIn) {
      // Calculate expiry timestamp (expiresIn is in seconds)
      this.tokenExpiry = Date.now() + (expiresIn * 1000)
      localStorage.setItem('access_token', token)
      localStorage.setItem('token_expiry', this.tokenExpiry.toString())
    } else if (token) {
      // Default to 1 hour if no expiry provided
      this.tokenExpiry = Date.now() + (3600 * 1000)
      localStorage.setItem('access_token', token)
      localStorage.setItem('token_expiry', this.tokenExpiry.toString())
    } else {
      this.tokenExpiry = null
      localStorage.removeItem('access_token')
      localStorage.removeItem('token_expiry')
    }
  }

  getToken(): string | null {
    return this.token
  }

  getTokenExpiry(): number | null {
    return this.tokenExpiry
  }

  isTokenExpiringSoon(): boolean {
    if (!this.tokenExpiry) return false
    return (this.tokenExpiry - Date.now()) < TOKEN_REFRESH_THRESHOLD_MS
  }

  isTokenExpired(): boolean {
    if (!this.tokenExpiry) return true
    return Date.now() >= this.tokenExpiry
  }

  private startRefreshTimer() {
    // Check every minute if token needs refresh
    setInterval(() => {
      if (this.token && this.isTokenExpiringSoon() && !this.isRefreshing) {
        this.refreshTokenSilently()
      }
    }, 60 * 1000)
  }

  private async refreshTokenSilently(): Promise<void> {
    if (this.isRefreshing) {
      return this.refreshPromise || Promise.resolve()
    }

    this.isRefreshing = true
    this.refreshPromise = this.performRefresh()
    
    try {
      await this.refreshPromise
    } finally {
      this.isRefreshing = false
      this.refreshPromise = null
    }
  }

  private async performRefresh(): Promise<void> {
    try {
      const response = await fetch(`${this.baseUrl}/auth/refresh`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'Authorization': `Bearer ${this.token}`,
        },
      })

      if (response.ok) {
        const data = await response.json()
        this.setToken(data.access_token, data.expires_in)
        console.log('Token refreshed successfully')
      } else {
        // Refresh failed - token invalid, redirect to login
        this.setToken(null)
        window.location.href = '/login'
      }
    } catch (error) {
      console.error('Token refresh failed:', error)
      // Don't clear token on network error, let it try again
    }
  }

  private async request<T>(
    endpoint: string,
    options: RequestInit = {},
    isRetry = false
  ): Promise<T> {
    // Check if token needs refresh before making request
    if (this.token && this.isTokenExpiringSoon() && !this.isRefreshing && !endpoint.includes('/auth/refresh')) {
      await this.refreshTokenSilently()
    }

    const url = `${this.baseUrl}${endpoint}`
    
    const headers: HeadersInit = {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      ...options.headers,
    }

    if (this.token) {
      ;(headers as Record<string, string>)['Authorization'] = `Bearer ${this.token}`
    }

    const response = await fetch(url, {
      ...options,
      headers,
    })

    if (!response.ok) {
      if (response.status === 401 && !isRetry && !endpoint.includes('/auth/')) {
        // Try to refresh token once on 401
        try {
          await this.refreshTokenSilently()
          if (this.token) {
            // Retry the original request with new token
            return this.request<T>(endpoint, options, true)
          }
        } catch {
          // Refresh failed, redirect to login
        }
        this.setToken(null)
        // Only redirect if not the /auth/user endpoint (used during initialization)
        if (!endpoint.includes('/auth/user')) {
          window.location.href = '/login'
        }
        throw new Error('Unauthorized')
      }

      if (response.status === 401) {
        this.setToken(null)
        // Only redirect if not the /auth/user endpoint (used during initialization)
        if (!endpoint.includes('/auth/user')) {
          window.location.href = '/login'
        }
        throw new Error('Unauthorized')
      }

      const error: ApiError = await response.json().catch(() => ({
        message: 'An unexpected error occurred',
      }))
      
      throw error
    }

    // Handle 204 No Content
    if (response.status === 204) {
      return {} as T
    }

    return response.json()
  }

  async get<T>(endpoint: string, params?: Record<string, string | number>): Promise<T> {
    let url = endpoint
    if (params) {
      const searchParams = new URLSearchParams()
      Object.entries(params).forEach(([key, value]) => {
        searchParams.append(key, String(value))
      })
      url += `?${searchParams.toString()}`
    }
    return this.request<T>(url, { method: 'GET' })
  }

  async post<T>(endpoint: string, data?: unknown): Promise<T> {
    return this.request<T>(endpoint, {
      method: 'POST',
      body: data ? JSON.stringify(data) : undefined,
    })
  }

  async put<T>(endpoint: string, data?: unknown): Promise<T> {
    return this.request<T>(endpoint, {
      method: 'PUT',
      body: data ? JSON.stringify(data) : undefined,
    })
  }

  async patch<T>(endpoint: string, data?: unknown): Promise<T> {
    return this.request<T>(endpoint, {
      method: 'PATCH',
      body: data ? JSON.stringify(data) : undefined,
    })
  }

  async delete<T>(endpoint: string): Promise<T> {
    return this.request<T>(endpoint, { method: 'DELETE' })
  }
}

export const apiClient = new ApiClient(API_BASE_URL)
export default apiClient
