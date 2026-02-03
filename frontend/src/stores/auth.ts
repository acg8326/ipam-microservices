import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { authApi } from '@/api'
import type { User, LoginCredentials } from '@/types'

export const useAuthStore = defineStore('auth', () => {
  const user = ref<User | null>(null)
  const loading = ref(false)
  const error = ref<string | null>(null)
  const initialized = ref(false)

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
      console.log('[Auth] fetchUser: calling authApi.me()')
      user.value = await authApi.me()
      console.log('[Auth] fetchUser: success, user =', user.value)
    } catch (e: unknown) {
      const err = e as { message?: string }
      console.error('[Auth] fetchUser: error =', err)
      user.value = null
      // Only set error for non-auth errors - 401s are handled by the client
      if (err.message !== 'Unauthorized') {
        error.value = err.message || 'Failed to fetch user'
      }
    } finally {
      loading.value = false
    }
  }

  async function initialize() {
    if (initialized.value) return
    
    const token = localStorage.getItem('access_token')
    console.log('[Auth] initialize: token from localStorage =', token ? 'EXISTS' : 'NULL')
    if (token) {
      try {
        await fetchUser()
      } catch (e) {
        // If fetching user fails, clear the invalid token
        console.error('[Auth] initialize: fetchUser threw, clearing token', e)
        localStorage.removeItem('access_token')
        localStorage.removeItem('token_expiry')
      }
    }
    console.log('[Auth] initialize: done, user =', user.value, 'initialized =', true)
    initialized.value = true
  }

  function $reset() {
    user.value = null
    loading.value = false
    error.value = null
    initialized.value = false
  }

  return {
    user,
    loading,
    error,
    initialized,
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
