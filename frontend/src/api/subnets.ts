import apiClient, { USE_MOCK_API } from './client'
import { mockApi } from './mock'
import type { Subnet, SubnetForm, PaginatedResponse, IPAddress } from '@/types'

export const subnetsApi = {
  async list(params?: {
    page?: number
    per_page?: number
    search?: string
    parent_id?: number | null
  }): Promise<PaginatedResponse<Subnet>> {
    if (USE_MOCK_API) {
      return mockApi.getSubnets(params)
    }
    return apiClient.get<PaginatedResponse<Subnet>>('/subnets', params as Record<string, string | number>)
  },

  async get(id: number): Promise<Subnet> {
    if (USE_MOCK_API) {
      const subnet = await mockApi.getSubnet(id)
      if (!subnet) throw { message: 'Subnet not found' }
      return subnet
    }
    return apiClient.get<Subnet>(`/subnets/${id}`)
  },

  async create(data: SubnetForm): Promise<Subnet> {
    return apiClient.post<Subnet>('/subnets', data)
  },

  async update(id: number, data: Partial<SubnetForm>): Promise<Subnet> {
    return apiClient.put<Subnet>(`/subnets/${id}`, data)
  },

  async delete(id: number): Promise<void> {
    return apiClient.delete(`/subnets/${id}`)
  },

  async getChildren(id: number): Promise<Subnet[]> {
    return apiClient.get<Subnet[]>(`/subnets/${id}/children`)
  },

  async getIpAddresses(id: number, params?: {
    page?: number
    per_page?: number
    status?: string
    search?: string
  }): Promise<PaginatedResponse<IPAddress>> {
    return apiClient.get<PaginatedResponse<IPAddress>>(
      `/subnets/${id}/ip-addresses`,
      params as Record<string, string | number>
    )
  },

  async getAvailableIps(id: number, count?: number): Promise<string[]> {
    return apiClient.get<string[]>(`/subnets/${id}/available-ips`, { count: count || 10 })
  },

  async getUtilization(id: number): Promise<{
    total: number
    used: number
    available: number
    percent: number
  }> {
    return apiClient.get(`/subnets/${id}/utilization`)
  },

  async getTree(): Promise<Subnet[]> {
    return apiClient.get<Subnet[]>('/subnets/tree')
  },

  async scan(id: number): Promise<{ message: string; job_id: string }> {
    return apiClient.post(`/subnets/${id}/scan`)
  },
}

export default subnetsApi
