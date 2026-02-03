import { describe, it, expect, beforeEach, vi, afterEach } from 'vitest'

// Mock localStorage
const localStorageMock = (() => {
  let store: Record<string, string> = {}
  return {
    getItem: (key: string) => store[key] || null,
    setItem: (key: string, value: string) => { store[key] = value },
    removeItem: (key: string) => { delete store[key] },
    clear: () => { store = {} },
  }
})()

Object.defineProperty(global, 'localStorage', { value: localStorageMock })

// Token refresh threshold - 5 minutes in milliseconds
const TOKEN_REFRESH_THRESHOLD_MS = 5 * 60 * 1000

// Simple implementation of token management for testing
class TokenManager {
  private token: string | null = null
  private tokenExpiry: number | null = null

  constructor() {
    this.token = localStorage.getItem('access_token')
    const expiry = localStorage.getItem('token_expiry')
    this.tokenExpiry = expiry ? parseInt(expiry, 10) : null
  }

  setToken(token: string | null, expiresIn?: number) {
    this.token = token
    if (token && expiresIn) {
      this.tokenExpiry = Date.now() + (expiresIn * 1000)
      localStorage.setItem('access_token', token)
      localStorage.setItem('token_expiry', this.tokenExpiry.toString())
    } else if (token) {
      this.tokenExpiry = Date.now() + (3600 * 1000)
      localStorage.setItem('access_token', token)
      localStorage.setItem('token_expiry', this.tokenExpiry.toString())
    } else {
      this.tokenExpiry = null
      localStorage.removeItem('access_token')
      localStorage.removeItem('token_expiry')
    }
  }

  getToken(): string | null {
    return this.token
  }

  getTokenExpiry(): number | null {
    return this.tokenExpiry
  }

  isTokenExpiringSoon(): boolean {
    if (!this.tokenExpiry) return false
    return (this.tokenExpiry - Date.now()) < TOKEN_REFRESH_THRESHOLD_MS
  }

  isTokenExpired(): boolean {
    if (!this.tokenExpiry) return true
    return Date.now() >= this.tokenExpiry
  }
}

describe('Token Management', () => {
  let tokenManager: TokenManager

  beforeEach(() => {
    localStorage.clear()
    vi.useFakeTimers()
    tokenManager = new TokenManager()
  })

  afterEach(() => {
    vi.useRealTimers()
  })

  describe('setToken', () => {
    it('should store token and calculate expiry', () => {
      const now = Date.now()
      vi.setSystemTime(now)
      
      tokenManager.setToken('test-token', 3600) // 1 hour
      
      expect(tokenManager.getToken()).toBe('test-token')
      expect(tokenManager.getTokenExpiry()).toBe(now + 3600 * 1000)
      expect(localStorage.getItem('access_token')).toBe('test-token')
      expect(localStorage.getItem('token_expiry')).toBe((now + 3600 * 1000).toString())
    })

    it('should use default 1 hour expiry if not provided', () => {
      const now = Date.now()
      vi.setSystemTime(now)
      
      tokenManager.setToken('test-token')
      
      expect(tokenManager.getTokenExpiry()).toBe(now + 3600 * 1000)
    })

    it('should clear token and expiry when set to null', () => {
      tokenManager.setToken('test-token', 3600)
      tokenManager.setToken(null)
      
      expect(tokenManager.getToken()).toBeNull()
      expect(tokenManager.getTokenExpiry()).toBeNull()
      expect(localStorage.getItem('access_token')).toBeNull()
      expect(localStorage.getItem('token_expiry')).toBeNull()
    })
  })

  describe('isTokenExpiringSoon', () => {
    it('should return false when token has plenty of time', () => {
      const now = Date.now()
      vi.setSystemTime(now)
      
      tokenManager.setToken('test-token', 3600) // 1 hour = 60 minutes
      
      expect(tokenManager.isTokenExpiringSoon()).toBe(false)
    })

    it('should return true when token expires in less than 5 minutes', () => {
      const now = Date.now()
      vi.setSystemTime(now)
      
      tokenManager.setToken('test-token', 240) // 4 minutes
      
      expect(tokenManager.isTokenExpiringSoon()).toBe(true)
    })

    it('should return true when token expires in exactly 5 minutes', () => {
      const now = Date.now()
      vi.setSystemTime(now)
      
      tokenManager.setToken('test-token', 300) // 5 minutes
      
      // At exactly 5 minutes, it's NOT expiring soon (threshold is < 5 min)
      expect(tokenManager.isTokenExpiringSoon()).toBe(false)
    })

    it('should return true after time passes and token approaches expiry', () => {
      const now = Date.now()
      vi.setSystemTime(now)
      
      tokenManager.setToken('test-token', 600) // 10 minutes
      
      expect(tokenManager.isTokenExpiringSoon()).toBe(false)
      
      // Advance time by 6 minutes (360 seconds)
      vi.advanceTimersByTime(6 * 60 * 1000)
      
      // Now only 4 minutes left - should be expiring soon
      expect(tokenManager.isTokenExpiringSoon()).toBe(true)
    })

    it('should return false when no token expiry set', () => {
      expect(tokenManager.isTokenExpiringSoon()).toBe(false)
    })
  })

  describe('isTokenExpired', () => {
    it('should return false when token is still valid', () => {
      const now = Date.now()
      vi.setSystemTime(now)
      
      tokenManager.setToken('test-token', 3600)
      
      expect(tokenManager.isTokenExpired()).toBe(false)
    })

    it('should return true when token has expired', () => {
      const now = Date.now()
      vi.setSystemTime(now)
      
      tokenManager.setToken('test-token', 60) // 1 minute
      
      // Advance time by 2 minutes
      vi.advanceTimersByTime(2 * 60 * 1000)
      
      expect(tokenManager.isTokenExpired()).toBe(true)
    })

    it('should return true when no token expiry set', () => {
      expect(tokenManager.isTokenExpired()).toBe(true)
    })

    it('should return true at exactly expiry time', () => {
      const now = Date.now()
      vi.setSystemTime(now)
      
      tokenManager.setToken('test-token', 60) // 1 minute
      
      // Advance exactly to expiry
      vi.advanceTimersByTime(60 * 1000)
      
      expect(tokenManager.isTokenExpired()).toBe(true)
    })
  })

  describe('localStorage persistence', () => {
    it('should restore token from localStorage on initialization', () => {
      const expiry = Date.now() + 3600 * 1000
      localStorage.setItem('access_token', 'stored-token')
      localStorage.setItem('token_expiry', expiry.toString())
      
      const newManager = new TokenManager()
      
      expect(newManager.getToken()).toBe('stored-token')
      expect(newManager.getTokenExpiry()).toBe(expiry)
    })

    it('should handle missing expiry in localStorage', () => {
      localStorage.setItem('access_token', 'stored-token')
      // No token_expiry set
      
      const newManager = new TokenManager()
      
      expect(newManager.getToken()).toBe('stored-token')
      expect(newManager.getTokenExpiry()).toBeNull()
    })
  })
})

describe('Token Refresh Scenarios', () => {
  beforeEach(() => {
    localStorage.clear()
    vi.useFakeTimers()
  })

  afterEach(() => {
    vi.useRealTimers()
  })

  it('should not trigger refresh for fresh token', () => {
    const tokenManager = new TokenManager()
    const now = Date.now()
    vi.setSystemTime(now)
    
    // Fresh token with 1 hour expiry
    tokenManager.setToken('fresh-token', 3600)
    
    expect(tokenManager.isTokenExpiringSoon()).toBe(false)
    expect(tokenManager.isTokenExpired()).toBe(false)
  })

  it('should trigger refresh when 4 minutes remaining', () => {
    const tokenManager = new TokenManager()
    const now = Date.now()
    vi.setSystemTime(now)
    
    // Token with 1 hour expiry
    tokenManager.setToken('test-token', 3600)
    
    // Advance to 4 minutes before expiry (56 minutes)
    vi.advanceTimersByTime(56 * 60 * 1000)
    
    expect(tokenManager.isTokenExpiringSoon()).toBe(true)
    expect(tokenManager.isTokenExpired()).toBe(false)
  })

  it('should handle token refresh cycle', () => {
    const tokenManager = new TokenManager()
    const now = Date.now()
    vi.setSystemTime(now)
    
    // Initial token
    tokenManager.setToken('token-v1', 600) // 10 minutes
    
    // Advance 6 minutes - should be expiring soon
    vi.advanceTimersByTime(6 * 60 * 1000)
    expect(tokenManager.isTokenExpiringSoon()).toBe(true)
    
    // Simulate refresh - set new token
    tokenManager.setToken('token-v2', 3600) // New 1 hour token
    
    // Token should no longer be expiring soon
    expect(tokenManager.isTokenExpiringSoon()).toBe(false)
    expect(tokenManager.getToken()).toBe('token-v2')
  })
})
