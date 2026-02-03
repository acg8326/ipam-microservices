// User & Authentication Types
export interface User {
  id: number
  name: string
  email: string
  role: 'admin' | 'user'
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
  session_id?: string
  user: User
}

// IP Address Management Types
export interface IPAddress {
  id: number
  ip_address: string
  label: string
  comment: string | null
  created_by: number
  created_by_name?: string
  created_at: string
  updated_at: string
}

export interface IPAddressForm {
  ip_address?: string
  label: string
  comment: string | null
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
  total_ips: number
  my_ips: number
  recent_activity: RecentActivity[]
}

export interface RecentActivity {
  id: number
  action: 'create' | 'update' | 'delete'
  ip_address: string
  label: string
  user_name: string
  created_at: string
}

// Audit Log Types
export interface AuditLog {
  id: number
  user_id: number
  user_name: string
  action: string
  entity_type: string
  entity_id: number | null
  ip_address: string | null
  old_values: Record<string, any> | null
  new_values: Record<string, any> | null
  created_at: string
}
