import { describe, it, expect, beforeEach } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'
import { useIPAddressesStore } from './ipAddresses'

describe('IP Addresses Store', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
  })

  it('should initialize with empty state', () => {
    const store = useIPAddressesStore()
    expect(store.ipAddresses).toEqual([])
    expect(store.loading).toBe(false)
    expect(store.error).toBeNull()
  })

  it('should track loading state', () => {
    const store = useIPAddressesStore()
    store.loading = true
    expect(store.loading).toBe(true)
  })

  it('should store error messages', () => {
    const store = useIPAddressesStore()
    store.error = 'Network error'
    expect(store.error).toBe('Network error')
  })

  it('should store IP addresses', () => {
    const store = useIPAddressesStore()
    const mockIPs = [
      {
        id: 1,
        ip_address: '192.168.1.1',
        label: 'Server 1',
        comment: 'Main server',
        created_by: 1,
        created_at: '2024-01-01T00:00:00Z',
        updated_at: '2024-01-01T00:00:00Z'
      },
      {
        id: 2,
        ip_address: '192.168.1.2',
        label: 'Server 2',
        comment: null,
        created_by: 1,
        created_at: '2024-01-01T00:00:00Z',
        updated_at: '2024-01-01T00:00:00Z'
      }
    ]

    store.ipAddresses = mockIPs
    expect(store.ipAddresses).toHaveLength(2)
    expect(store.ipAddresses[0]?.ip_address).toBe('192.168.1.1')
  })
})
