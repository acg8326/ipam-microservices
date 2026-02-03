// Mock API for frontend development without backend
import type { AuthResponse, User, DashboardStats, Subnet, IPAddress, PaginatedResponse } from '@/types'

const mockUser: User = {
  id: 1,
  name: 'Admin User',
  email: 'admin@example.com',
  role: 'admin',
  created_at: '2026-01-01T00:00:00Z',
  updated_at: '2026-01-01T00:00:00Z',
}

const mockSubnets: Subnet[] = [
  {
    id: 1,
    name: 'Production Network',
    network: '10.0.0.0',
    cidr: 24,
    gateway: '10.0.0.1',
    vlan_id: 100,
    description: 'Main production subnet',
    parent_id: null,
    location_id: 1,
    total_addresses: 254,
    used_addresses: 45,
    available_addresses: 209,
    utilization_percent: 18,
    created_at: '2026-01-01T00:00:00Z',
    updated_at: '2026-01-01T00:00:00Z',
  },
  {
    id: 2,
    name: 'Development Network',
    network: '10.1.0.0',
    cidr: 24,
    gateway: '10.1.0.1',
    vlan_id: 200,
    description: 'Development environment',
    parent_id: null,
    location_id: 1,
    total_addresses: 254,
    used_addresses: 120,
    available_addresses: 134,
    utilization_percent: 47,
    created_at: '2026-01-01T00:00:00Z',
    updated_at: '2026-01-01T00:00:00Z',
  },
  {
    id: 3,
    name: 'DMZ',
    network: '192.168.1.0',
    cidr: 24,
    gateway: '192.168.1.1',
    vlan_id: 300,
    description: 'Demilitarized zone for public services',
    parent_id: null,
    location_id: 2,
    total_addresses: 254,
    used_addresses: 230,
    available_addresses: 24,
    utilization_percent: 91,
    created_at: '2026-01-01T00:00:00Z',
    updated_at: '2026-01-01T00:00:00Z',
  },
]

const mockIPAddresses: IPAddress[] = [
  {
    id: 1,
    subnet_id: 1,
    ip_address: '10.0.0.10',
    hostname: 'web-server-01',
    mac_address: '00:1A:2B:3C:4D:5E',
    status: 'assigned',
    device_type: 'Server',
    description: 'Primary web server',
    assigned_to: 'Web Team',
    last_seen: '2026-02-03T08:00:00Z',
    created_at: '2026-01-01T00:00:00Z',
    updated_at: '2026-01-01T00:00:00Z',
  },
  {
    id: 2,
    subnet_id: 1,
    ip_address: '10.0.0.11',
    hostname: 'db-server-01',
    mac_address: '00:1A:2B:3C:4D:5F',
    status: 'assigned',
    device_type: 'Server',
    description: 'Database server',
    assigned_to: 'DBA Team',
    last_seen: '2026-02-03T08:00:00Z',
    created_at: '2026-01-01T00:00:00Z',
    updated_at: '2026-01-01T00:00:00Z',
  },
  {
    id: 3,
    subnet_id: 1,
    ip_address: '10.0.0.12',
    hostname: null,
    mac_address: null,
    status: 'available',
    device_type: null,
    description: null,
    assigned_to: null,
    last_seen: null,
    created_at: '2026-01-01T00:00:00Z',
    updated_at: '2026-01-01T00:00:00Z',
  },
  {
    id: 4,
    subnet_id: 1,
    ip_address: '10.0.0.20',
    hostname: 'reserved-future',
    mac_address: null,
    status: 'reserved',
    device_type: null,
    description: 'Reserved for future expansion',
    assigned_to: 'Infrastructure',
    last_seen: null,
    created_at: '2026-01-01T00:00:00Z',
    updated_at: '2026-01-01T00:00:00Z',
  },
  {
    id: 5,
    subnet_id: 1,
    ip_address: '10.0.0.100',
    hostname: 'dhcp-client-01',
    mac_address: '00:1A:2B:3C:4D:60',
    status: 'dhcp',
    device_type: 'Workstation',
    description: 'DHCP assigned',
    assigned_to: null,
    last_seen: '2026-02-03T07:30:00Z',
    created_at: '2026-01-01T00:00:00Z',
    updated_at: '2026-01-01T00:00:00Z',
  },
]

// Simulate network delay
const delay = (ms: number) => new Promise(resolve => setTimeout(resolve, ms))

export const mockApi = {
  async login(email: string, _password: string): Promise<AuthResponse> {
    await delay(500)
    
    if (email === 'admin@example.com') {
      return {
        access_token: 'mock-jwt-token-12345',
        token_type: 'Bearer',
        expires_in: 3600,
        user: mockUser,
      }
    }
    
    throw { message: 'Invalid credentials' }
  },

  async me(): Promise<User> {
    await delay(200)
    return mockUser
  },

  async getDashboardStats(): Promise<DashboardStats> {
    await delay(300)
    return {
      total_subnets: mockSubnets.length,
      total_ips: 762,
      assigned_ips: 395,
      available_ips: 367,
      utilization_percent: 52,
      recent_assignments: mockIPAddresses.filter(ip => ip.status === 'assigned').slice(0, 5),
      subnet_utilization: mockSubnets.map(s => ({
        subnet_id: s.id,
        subnet_name: s.name,
        network: `${s.network}/${s.cidr}`,
        utilization_percent: s.utilization_percent,
      })),
    }
  },

  async getSubnets(params?: { page?: number; search?: string }): Promise<PaginatedResponse<Subnet>> {
    await delay(300)
    
    let filtered = [...mockSubnets]
    if (params?.search) {
      const search = params.search.toLowerCase()
      filtered = filtered.filter(s => 
        s.name.toLowerCase().includes(search) ||
        s.network.includes(search)
      )
    }

    return {
      data: filtered,
      meta: {
        current_page: params?.page || 1,
        from: 1,
        last_page: 1,
        per_page: 25,
        to: filtered.length,
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

  async getSubnet(id: number): Promise<Subnet | undefined> {
    await delay(200)
    return mockSubnets.find(s => s.id === id)
  },

  async getIPAddresses(params?: { 
    subnet_id?: number
    status?: string
    search?: string 
  }): Promise<PaginatedResponse<IPAddress>> {
    await delay(300)
    
    let filtered = [...mockIPAddresses]
    
    if (params?.subnet_id) {
      filtered = filtered.filter(ip => ip.subnet_id === params.subnet_id)
    }
    if (params?.status) {
      filtered = filtered.filter(ip => ip.status === params.status)
    }
    if (params?.search) {
      const search = params.search.toLowerCase()
      filtered = filtered.filter(ip => 
        ip.ip_address.includes(search) ||
        ip.hostname?.toLowerCase().includes(search) ||
        ip.mac_address?.toLowerCase().includes(search)
      )
    }

    return {
      data: filtered,
      meta: {
        current_page: 1,
        from: 1,
        last_page: 1,
        per_page: 50,
        to: filtered.length,
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
}

export default mockApi
