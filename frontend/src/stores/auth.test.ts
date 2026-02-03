import { describe, it, expect, beforeEach } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'
import { useAuthStore } from './auth'

describe('Auth Store', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    localStorage.clear()
  })

  it('should initialize with no user', () => {
    const store = useAuthStore()
    expect(store.user).toBeNull()
    expect(store.isAuthenticated).toBe(false)
    expect(store.isAdmin).toBe(false)
  })

  it('should set user correctly', () => {
    const store = useAuthStore()
    const mockUser = {
      id: 1,
      name: 'Test User',
      email: 'test@example.com',
      role: 'user' as const,
      created_at: '2024-01-01T00:00:00Z',
      updated_at: '2024-01-01T00:00:00Z'
    }

    store.user = mockUser
    expect(store.user).toEqual(mockUser)
    expect(store.isAuthenticated).toBe(true)
    expect(store.isAdmin).toBe(false)
  })

  it('should detect admin role', () => {
    const store = useAuthStore()
    const mockAdmin = {
      id: 1,
      name: 'Admin User',
      email: 'admin@example.com',
      role: 'admin' as const,
      created_at: '2024-01-01T00:00:00Z',
      updated_at: '2024-01-01T00:00:00Z'
    }

    store.user = mockAdmin
    expect(store.isAdmin).toBe(true)
  })

  it('should clear user on logout', () => {
    const store = useAuthStore()
    store.user = {
      id: 1,
      name: 'Test User',
      email: 'test@example.com',
      role: 'user' as const,
      created_at: '2024-01-01T00:00:00Z',
      updated_at: '2024-01-01T00:00:00Z'
    }

    store.$reset()
    expect(store.user).toBeNull()
    expect(store.isAuthenticated).toBe(false)
  })
})
