import { describe, it, expect } from 'vitest'

// IP Address validation utilities
function isValidIPv4(ip: string): boolean {
  const ipv4Regex = /^(\d{1,3}\.){3}\d{1,3}$/
  if (!ipv4Regex.test(ip)) return false
  
  const parts = ip.split('.')
  return parts.every(part => {
    const num = parseInt(part, 10)
    return num >= 0 && num <= 255
  })
}

function isValidIPv6(ip: string): boolean {
  const ipv6Regex = /^([0-9a-fA-F]{1,4}:){7}[0-9a-fA-F]{1,4}$|^::$|^([0-9a-fA-F]{1,4}:)*::([0-9a-fA-F]{1,4}:)*[0-9a-fA-F]{1,4}$|^::1$/
  return ipv6Regex.test(ip)
}

function isValidIP(ip: string): boolean {
  return isValidIPv4(ip) || isValidIPv6(ip)
}

describe('IP Address Validation', () => {
  describe('IPv4 validation', () => {
    it('should accept valid IPv4 addresses', () => {
      expect(isValidIPv4('192.168.1.1')).toBe(true)
      expect(isValidIPv4('10.0.0.1')).toBe(true)
      expect(isValidIPv4('255.255.255.255')).toBe(true)
      expect(isValidIPv4('0.0.0.0')).toBe(true)
    })

    it('should reject invalid IPv4 addresses', () => {
      expect(isValidIPv4('256.1.1.1')).toBe(false)
      expect(isValidIPv4('192.168.1')).toBe(false)
      expect(isValidIPv4('192.168.1.1.1')).toBe(false)
      expect(isValidIPv4('abc.def.ghi.jkl')).toBe(false)
      expect(isValidIPv4('')).toBe(false)
    })
  })

  describe('IPv6 validation', () => {
    it('should accept valid IPv6 addresses', () => {
      expect(isValidIPv6('2001:0db8:85a3:0000:0000:8a2e:0370:7334')).toBe(true)
      expect(isValidIPv6('::1')).toBe(true)
      expect(isValidIPv6('::')).toBe(true)
    })

    it('should reject invalid IPv6 addresses', () => {
      expect(isValidIPv6('192.168.1.1')).toBe(false)
      expect(isValidIPv6('invalid')).toBe(false)
    })
  })

  describe('Combined IP validation', () => {
    it('should accept both IPv4 and IPv6', () => {
      expect(isValidIP('192.168.1.1')).toBe(true)
      expect(isValidIP('::1')).toBe(true)
    })

    it('should reject invalid IPs', () => {
      expect(isValidIP('not-an-ip')).toBe(false)
      expect(isValidIP('')).toBe(false)
    })
  })
})

describe('Label validation', () => {
  function isValidLabel(label: string): boolean {
    return label.trim().length > 0 && label.length <= 255
  }

  it('should accept valid labels', () => {
    expect(isValidLabel('Server 1')).toBe(true)
    expect(isValidLabel('Production DB')).toBe(true)
    expect(isValidLabel('a')).toBe(true)
  })

  it('should reject empty labels', () => {
    expect(isValidLabel('')).toBe(false)
    expect(isValidLabel('   ')).toBe(false)
  })
})
