import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { subnetsApi } from '@/api'
import type { Subnet, SubnetForm, PaginatedResponse } from '@/types'

export const useSubnetsStore = defineStore('subnets', () => {
  const subnets = ref<Subnet[]>([])
  const currentSubnet = ref<Subnet | null>(null)
  const subnetTree = ref<Subnet[]>([])
  const loading = ref(false)
  const error = ref<string | null>(null)
  const pagination = ref({
    currentPage: 1,
    lastPage: 1,
    perPage: 25,
    total: 0,
  })

  const rootSubnets = computed(() => 
    subnets.value.filter(s => !s.parent_id)
  )

  async function fetchSubnets(params?: {
    page?: number
    per_page?: number
    search?: string
    parent_id?: number | null
  }) {
    loading.value = true
    error.value = null
    
    try {
      const response: PaginatedResponse<Subnet> = await subnetsApi.list(params)
      subnets.value = response.data
      pagination.value = {
        currentPage: response.meta.current_page,
        lastPage: response.meta.last_page,
        perPage: response.meta.per_page,
        total: response.meta.total,
      }
    } catch (e: unknown) {
      const err = e as { message?: string }
      error.value = err.message || 'Failed to fetch subnets'
    } finally {
      loading.value = false
    }
  }

  async function fetchSubnet(id: number) {
    loading.value = true
    error.value = null
    
    try {
      currentSubnet.value = await subnetsApi.get(id)
    } catch (e: unknown) {
      const err = e as { message?: string }
      error.value = err.message || 'Failed to fetch subnet'
      currentSubnet.value = null
    } finally {
      loading.value = false
    }
  }

  async function fetchSubnetTree() {
    loading.value = true
    error.value = null
    
    try {
      subnetTree.value = await subnetsApi.getTree()
    } catch (e: unknown) {
      const err = e as { message?: string }
      error.value = err.message || 'Failed to fetch subnet tree'
    } finally {
      loading.value = false
    }
  }

  async function createSubnet(data: SubnetForm) {
    loading.value = true
    error.value = null
    
    try {
      const subnet = await subnetsApi.create(data)
      subnets.value.push(subnet)
      return subnet
    } catch (e: unknown) {
      const err = e as { message?: string }
      error.value = err.message || 'Failed to create subnet'
      throw e
    } finally {
      loading.value = false
    }
  }

  async function updateSubnet(id: number, data: Partial<SubnetForm>) {
    loading.value = true
    error.value = null
    
    try {
      const subnet = await subnetsApi.update(id, data)
      const index = subnets.value.findIndex(s => s.id === id)
      if (index !== -1) {
        subnets.value[index] = subnet
      }
      if (currentSubnet.value?.id === id) {
        currentSubnet.value = subnet
      }
      return subnet
    } catch (e: unknown) {
      const err = e as { message?: string }
      error.value = err.message || 'Failed to update subnet'
      throw e
    } finally {
      loading.value = false
    }
  }

  async function deleteSubnet(id: number) {
    loading.value = true
    error.value = null
    
    try {
      await subnetsApi.delete(id)
      subnets.value = subnets.value.filter(s => s.id !== id)
      if (currentSubnet.value?.id === id) {
        currentSubnet.value = null
      }
    } catch (e: unknown) {
      const err = e as { message?: string }
      error.value = err.message || 'Failed to delete subnet'
      throw e
    } finally {
      loading.value = false
    }
  }

  function $reset() {
    subnets.value = []
    currentSubnet.value = null
    subnetTree.value = []
    loading.value = false
    error.value = null
    pagination.value = {
      currentPage: 1,
      lastPage: 1,
      perPage: 25,
      total: 0,
    }
  }

  return {
    subnets,
    currentSubnet,
    subnetTree,
    loading,
    error,
    pagination,
    rootSubnets,
    fetchSubnets,
    fetchSubnet,
    fetchSubnetTree,
    createSubnet,
    updateSubnet,
    deleteSubnet,
    $reset,
  }
})
