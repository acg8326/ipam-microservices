import apiClient, { USE_MOCK_API } from './client'
import { mockApi } from './mock'
import type { IPAddress, IPAddressForm, PaginatedResponse } from '@/types'

export const ipAddressesApi = {
  async list(params?: {
    page?: number
    per_page?: number
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
}

export default ipAddressesApi
