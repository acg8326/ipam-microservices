// User & Authentication Types
export interface User {
  id: number
  name: string
  email: string
  role: 'admin' | 'operator' | 'viewer'
  created_at: string
  updated_at: string
}

export interface LoginCredentials {
  email: string
  password: string
}

export interface AuthResponse {
  access_token: string
  token_type: string
  expires_in: number
  user: User
}

// IP Address Management Types
export interface Subnet {
  id: number
  name: string
  network: string
  cidr: number
  gateway: string | null
  vlan_id: number | null
  description: string | null
  parent_id: number | null
  location_id: number | null
  total_addresses: number
  used_addresses: number
  available_addresses: number
  utilization_percent: number
  children?: Subnet[]
  created_at: string
  updated_at: string
}

export interface IPAddress {
  id: number
  subnet_id: number
  ip_address: string
  hostname: string | null
  mac_address: string | null
  status: 'available' | 'reserved' | 'assigned' | 'dhcp'
  device_type: string | null
  description: string | null
  assigned_to: string | null
  last_seen: string | null
  created_at: string
  updated_at: string
}

export interface VLAN {
  id: number
  vlan_number: number
  name: string
  description: string | null
  location_id: number | null
  created_at: string
  updated_at: string
}

export interface Location {
  id: number
  name: string
  code: string
  address: string | null
  description: string | null
  parent_id: number | null
  children?: Location[]
  created_at: string
  updated_at: string
}

// API Response Types
export interface PaginatedResponse<T> {
  data: T[]
  meta: {
    current_page: number
    from: number
    last_page: number
    per_page: number
    to: number
    total: number
  }
  links: {
    first: string
    last: string
    prev: string | null
    next: string | null
  }
}

export interface ApiError {
  message: string
  errors?: Record<string, string[]>
}

// Dashboard Types
export interface DashboardStats {
  total_subnets: number
  total_ips: number
  assigned_ips: number
  available_ips: number
  utilization_percent: number
  recent_assignments: IPAddress[]
  subnet_utilization: SubnetUtilization[]
}

export interface SubnetUtilization {
  subnet_id: number
  subnet_name: string
  network: string
  utilization_percent: number
}

// Form Types
export interface SubnetForm {
  name: string
  network: string
  cidr: number
  gateway?: string
  vlan_id?: number
  description?: string
  parent_id?: number
  location_id?: number
}

export interface IPAddressForm {
  subnet_id: number
  ip_address: string
  hostname?: string
  mac_address?: string
  status: 'available' | 'reserved' | 'assigned' | 'dhcp'
  device_type?: string
  description?: string
  assigned_to?: string
}
