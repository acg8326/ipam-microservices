// Mock API for frontend development without backend
import type { AuthResponse, User, IPAddress, PaginatedResponse, DashboardStats, AuditLog } from '@/types'

const mockUser: User = {
  id: 1,
  name: 'Admin User',
  email: 'admin@example.com',
  role: 'admin',
  created_at: '2026-01-01T00:00:00Z',
  updated_at: '2026-01-01T00:00:00Z',
}

let mockIPAddresses: IPAddress[] = [
  {
    id: 1,
    ip_address: '10.0.0.10',
    label: 'Web Server',
    comment: 'Primary web server for production',
    created_by: 1,
    created_by_name: 'Admin User',
    created_at: '2026-01-01T00:00:00Z',
    updated_at: '2026-01-01T00:00:00Z',
  },
  {
    id: 2,
    ip_address: '10.0.0.11',
    label: 'Database Server',
    comment: 'MySQL primary server',
    created_by: 1,
    created_by_name: 'Admin User',
    created_at: '2026-01-01T00:00:00Z',
    updated_at: '2026-01-01T00:00:00Z',
  },
  {
    id: 3,
    ip_address: '10.0.0.12',
    label: 'Cache Server',
    comment: null,
    created_by: 2,
    created_by_name: 'John Doe',
    created_at: '2026-01-15T00:00:00Z',
    updated_at: '2026-01-15T00:00:00Z',
  },
  {
    id: 4,
    ip_address: '192.168.1.100',
    label: 'Developer Workstation',
    comment: 'Development machine - Building A',
    created_by: 2,
    created_by_name: 'John Doe',
    created_at: '2026-02-01T00:00:00Z',
    updated_at: '2026-02-01T00:00:00Z',
  },
  {
    id: 5,
    ip_address: '2001:db8::1',
    label: 'IPv6 Gateway',
    comment: 'Main IPv6 gateway for internal network',
    created_by: 1,
    created_by_name: 'Admin User',
    created_at: '2026-02-03T00:00:00Z',
    updated_at: '2026-02-03T00:00:00Z',
  },
]

const mockAuditLogs: AuditLog[] = [
  {
    id: 1,
    user_id: 1,
    user_name: 'Admin User',
    action: 'login',
    entity_type: 'session',
    entity_id: null,
    ip_address: '192.168.1.1',
    old_values: null,
    new_values: null,
    created_at: '2026-02-03T08:00:00Z',
  },
  {
    id: 2,
    user_id: 1,
    user_name: 'Admin User',
    action: 'create',
    entity_type: 'ip_address',
    entity_id: 5,
    ip_address: '192.168.1.1',
    old_values: null,
    new_values: { ip_address: '2001:db8::1', label: 'IPv6 Gateway' },
    created_at: '2026-02-03T08:05:00Z',
  },
  {
    id: 3,
    user_id: 2,
    user_name: 'John Doe',
    action: 'update',
    entity_type: 'ip_address',
    entity_id: 4,
    ip_address: '192.168.1.50',
    old_values: { label: 'Dev Machine' },
    new_values: { label: 'Developer Workstation' },
    created_at: '2026-02-02T14:30:00Z',
  },
]

let nextIPId = 6

// Helper function to delay responses to simulate network latency
const delay = (ms: number) => new Promise(resolve => setTimeout(resolve, ms))

export const mockApi = {
  async login(email: string, _password: string): Promise<AuthResponse> {
    await delay(500)
    
    if (email === 'admin@example.com' || email === 'user@example.com') {
      const user = { 
        ...mockUser, 
        email,
        role: email.includes('admin') ? 'admin' : 'user' as 'admin' | 'user'
      }
      return {
        access_token: 'mock-jwt-token-' + Date.now(),
        token_type: 'Bearer',
        expires_in: 3600,
        user,
      }
    }
    
    throw new Error('Invalid credentials')
  },

  async logout(): Promise<void> {
    await delay(200)
  },

  async getUser(): Promise<User> {
    await delay(300)
    return mockUser
  },

  async getIPAddresses(params?: {
    page?: number
    per_page?: number
    search?: string
  }): Promise<PaginatedResponse<IPAddress>> {
    await delay(400)
    
    let filtered = [...mockIPAddresses]
    
    if (params?.search) {
      const search = params.search.toLowerCase()
      filtered = filtered.filter(ip => 
        ip.ip_address.toLowerCase().includes(search) ||
        ip.label.toLowerCase().includes(search) ||
        ip.comment?.toLowerCase().includes(search)
      )
    }
    
    const page = params?.page || 1
    const perPage = params?.per_page || 50
    const start = (page - 1) * perPage
    const end = start + perPage
    const paged = filtered.slice(start, end)
    
    return {
      data: paged,
      meta: {
        current_page: page,
        from: start + 1,
        last_page: Math.ceil(filtered.length / perPage),
        per_page: perPage,
        to: Math.min(end, filtered.length),
        total: filtered.length,
      },
      links: {
        first: '',
        last: '',
        prev: null,
        next: null,
      },
    }
  },

  async createIPAddress(data: { ip_address: string; label: string; comment?: string | null }): Promise<IPAddress> {
    await delay(300)
    
    const newIP: IPAddress = {
      id: nextIPId++,
      ip_address: data.ip_address,
      label: data.label,
      comment: data.comment || null,
      created_by: mockUser.id,
      created_by_name: mockUser.name,
      created_at: new Date().toISOString(),
      updated_at: new Date().toISOString(),
    }
    
    mockIPAddresses.push(newIP)
    return newIP
  },

  async updateIPAddress(id: number, data: { label?: string; comment?: string | null }): Promise<IPAddress> {
    await delay(300)
    
    const index = mockIPAddresses.findIndex(ip => ip.id === id)
    if (index === -1) {
      throw new Error('IP address not found')
    }
    
    const existingIP = mockIPAddresses[index]!
    
    const updatedIP: IPAddress = {
      id: existingIP.id,
      ip_address: existingIP.ip_address,
      label: data.label ?? existingIP.label,
      comment: data.comment !== undefined ? data.comment : existingIP.comment,
      created_by: existingIP.created_by,
      created_by_name: existingIP.created_by_name,
      created_at: existingIP.created_at,
      updated_at: new Date().toISOString(),
    }
    
    mockIPAddresses[index] = updatedIP
    
    return updatedIP
  },

  async deleteIPAddress(id: number): Promise<void> {
    await delay(300)
    
    const index = mockIPAddresses.findIndex(ip => ip.id === id)
    if (index === -1) {
      throw new Error('IP address not found')
    }
    
    mockIPAddresses.splice(index, 1)
  },

  async getDashboardStats(): Promise<DashboardStats> {
    await delay(400)
    
    return {
      total_ips: mockIPAddresses.length,
      my_ips: mockIPAddresses.filter(ip => ip.created_by === mockUser.id).length,
      recent_activity: [
        {
          id: 1,
          action: 'create',
          ip_address: '2001:db8::1',
          label: 'IPv6 Gateway',
          user_name: 'Admin User',
          created_at: '2026-02-03T08:05:00Z',
        },
        {
          id: 2,
          action: 'update',
          ip_address: '192.168.1.100',
          label: 'Developer Workstation',
          user_name: 'John Doe',
          created_at: '2026-02-02T14:30:00Z',
        },
      ],
    }
  },

  async getAuditLogs(_params?: {
    page?: number
    per_page?: number
    action?: string
    user_id?: number
  }): Promise<PaginatedResponse<AuditLog>> {
    await delay(400)
    
    return {
      data: mockAuditLogs,
      meta: {
        current_page: 1,
        from: 1,
        last_page: 1,
        per_page: 50,
        to: mockAuditLogs.length,
        total: mockAuditLogs.length,
      },
      links: {
        first: '',
        last: '',
        prev: null,
        next: null,
      },
    }
  },
}

export default mockApi
