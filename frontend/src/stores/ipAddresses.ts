import { defineStore } from 'pinia'
import { ref } from 'vue'
import { ipAddressesApi } from '@/api'
import type { IPAddress, IPAddressForm, PaginatedResponse } from '@/types'

export const useIPAddressesStore = defineStore('ipAddresses', () => {
  const ipAddresses = ref<IPAddress[]>([])
  const currentIP = ref<IPAddress | null>(null)
  const loading = ref(false)
  const error = ref<string | null>(null)
  const pagination = ref({
    currentPage: 1,
    lastPage: 1,
    perPage: 50,
    total: 0,
  })

  async function fetchIPAddresses(params?: {
    page?: number
    per_page?: number
    search?: string
  }) {
    loading.value = true
    error.value = null
    
    try {
      const response: PaginatedResponse<IPAddress> = await ipAddressesApi.list(params)
      ipAddresses.value = response.data
      pagination.value = {
        currentPage: response.meta.current_page,
        lastPage: response.meta.last_page,
        perPage: response.meta.per_page,
        total: response.meta.total,
      }
    } catch (e: unknown) {
      const err = e as { message?: string }
      error.value = err.message || 'Failed to fetch IP addresses'
    } finally {
      loading.value = false
    }
  }

  async function fetchIPAddress(id: number) {
    loading.value = true
    error.value = null
    
    try {
      currentIP.value = await ipAddressesApi.get(id)
    } catch (e: unknown) {
      const err = e as { message?: string }
      error.value = err.message || 'Failed to fetch IP address'
      currentIP.value = null
    } finally {
      loading.value = false
    }
  }

  async function createIPAddress(data: IPAddressForm) {
    loading.value = true
    error.value = null
    
    try {
      const ip = await ipAddressesApi.create(data)
      ipAddresses.value.push(ip)
      return ip
    } catch (e: unknown) {
      const err = e as { message?: string }
      error.value = err.message || 'Failed to create IP address'
      throw e
    } finally {
      loading.value = false
    }
  }

  async function updateIPAddress(id: number, data: Partial<IPAddressForm>) {
    loading.value = true
    error.value = null
    
    try {
      const ip = await ipAddressesApi.update(id, data)
      const index = ipAddresses.value.findIndex(i => i.id === id)
      if (index !== -1) {
        ipAddresses.value[index] = ip
      }
      if (currentIP.value?.id === id) {
        currentIP.value = ip
      }
      return ip
    } catch (e: unknown) {
      const err = e as { message?: string }
      error.value = err.message || 'Failed to update IP address'
      throw e
    } finally {
      loading.value = false
    }
  }

  async function deleteIPAddress(id: number) {
    loading.value = true
    error.value = null
    
    try {
      await ipAddressesApi.delete(id)
      ipAddresses.value = ipAddresses.value.filter(ip => ip.id !== id)
      if (currentIP.value?.id === id) {
        currentIP.value = null
      }
    } catch (e: unknown) {
      const err = e as { message?: string }
      error.value = err.message || 'Failed to delete IP address'
      throw e
    } finally {
      loading.value = false
    }
  }

  function clearError() {
    error.value = null
  }

  return {
    ipAddresses,
    currentIP,
    loading,
    error,
    pagination,
    fetchIPAddresses,
    fetchIPAddress,
    createIPAddress,
    updateIPAddress,
    deleteIPAddress,
    clearError,
  }
})
