import apiClient, { USE_MOCK_API } from './client'
import { mockApi } from './mock'
import type { IPAddress, IPAddressForm, PaginatedResponse } from '@/types'

export const ipAddressesApi = {
  async list(params?: {
    page?: number
    per_page?: number
    subnet_id?: number
    status?: string
    search?: string
  }): Promise<PaginatedResponse<IPAddress>> {
    if (USE_MOCK_API) {
      return mockApi.getIPAddresses(params)
    }
    return apiClient.get<PaginatedResponse<IPAddress>>('/ip-addresses', params as Record<string, string | number>)
  },

  async get(id: number): Promise<IPAddress> {
    return apiClient.get<IPAddress>(`/ip-addresses/${id}`)
  },

  async create(data: IPAddressForm): Promise<IPAddress> {
    return apiClient.post<IPAddress>('/ip-addresses', data)
  },

  async update(id: number, data: Partial<IPAddressForm>): Promise<IPAddress> {
    return apiClient.put<IPAddress>(`/ip-addresses/${id}`, data)
  },

  async delete(id: number): Promise<void> {
    return apiClient.delete(`/ip-addresses/${id}`)
  },

  async assign(id: number, data: {
    hostname?: string
    mac_address?: string
    assigned_to?: string
    device_type?: string
    description?: string
  }): Promise<IPAddress> {
    return apiClient.post<IPAddress>(`/ip-addresses/${id}/assign`, data)
  },

  async release(id: number): Promise<IPAddress> {
    return apiClient.post<IPAddress>(`/ip-addresses/${id}/release`)
  },

  async reserve(id: number, data?: {
    description?: string
  }): Promise<IPAddress> {
    return apiClient.post<IPAddress>(`/ip-addresses/${id}/reserve`, data)
  },

  async ping(id: number): Promise<{
    ip_address: string
    reachable: boolean
    latency_ms: number | null
  }> {
    return apiClient.post(`/ip-addresses/${id}/ping`)
  },

  async lookup(ip: string): Promise<IPAddress | null> {
    return apiClient.get<IPAddress | null>(`/ip-addresses/lookup`, { ip })
  },

  async bulkAssign(data: {
    subnet_id: number
    count: number
    hostname_prefix?: string
    assigned_to?: string
  }): Promise<IPAddress[]> {
    return apiClient.post<IPAddress[]>('/ip-addresses/bulk-assign', data)
  },

  async bulkRelease(ids: number[]): Promise<{ released: number }> {
    return apiClient.post('/ip-addresses/bulk-release', { ids })
  },
}

export default ipAddressesApi
