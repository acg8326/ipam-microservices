import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { authApi } from '@/api'
import type { User, LoginCredentials } from '@/types'

export const useAuthStore = defineStore('auth', () => {
  const user = ref<User | null>(null)
  const loading = ref(false)
  const error = ref<string | null>(null)

  const isAuthenticated = computed(() => !!user.value)
  const isAdmin = computed(() => user.value?.role === 'admin')
  const isOperator = computed(() => ['admin', 'operator'].includes(user.value?.role || ''))

  async function login(credentials: LoginCredentials) {
    loading.value = true
    error.value = null
    
    try {
      const response = await authApi.login(credentials)
      user.value = response.user
      return response
    } catch (e: unknown) {
      const err = e as { message?: string }
      error.value = err.message || 'Login failed'
      throw e
    } finally {
      loading.value = false
    }
  }

  async function logout() {
    loading.value = true
    
    try {
      await authApi.logout()
    } finally {
      user.value = null
      loading.value = false
    }
  }

  async function fetchUser() {
    loading.value = true
    error.value = null
    
    try {
      user.value = await authApi.me()
    } catch (e: unknown) {
      const err = e as { message?: string }
      user.value = null
      error.value = err.message || 'Failed to fetch user'
    } finally {
      loading.value = false
    }
  }

  async function initialize() {
    const token = localStorage.getItem('access_token')
    if (token) {
      await fetchUser()
    }
  }

  function $reset() {
    user.value = null
    loading.value = false
    error.value = null
  }

  return {
    user,
    loading,
    error,
    isAuthenticated,
    isAdmin,
    isOperator,
    login,
    logout,
    fetchUser,
    initialize,
    $reset,
  }
})
