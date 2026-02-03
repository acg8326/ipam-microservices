<script setup lang="ts">
import { ref } from 'vue'
import { ipAddressesApi } from '@/api'
import type { IPAddress } from '@/types'

const searchQuery = ref('')
const searchResults = ref<IPAddress[]>([])
const loading = ref(false)
const searched = ref(false)

async function search() {
  if (!searchQuery.value.trim()) return
  
  loading.value = true
  searched.value = true
  
  try {
    const response = await ipAddressesApi.list({ search: searchQuery.value, per_page: 50 })
    searchResults.value = response.data
  } catch {
    searchResults.value = []
  } finally {
    loading.value = false
  }
}

function getStatusClass(status: string) {
  switch (status) {
    case 'assigned': return 'status--assigned'
    case 'reserved': return 'status--reserved'
    case 'dhcp': return 'status--dhcp'
    default: return 'status--available'
  }
}
</script>

<template>
  <div class="search-view">
    <header class="page-header">
      <h1 class="page-header__title">Search</h1>
      <p class="page-header__subtitle">Search for IP addresses, hostnames, or MAC addresses</p>
    </header>

    <div class="search-box">
      <input
        v-model="searchQuery"
        type="search"
        class="search-box__input"
        placeholder="Enter IP address, hostname, or MAC address..."
        @keyup.enter="search"
      >
      <button class="btn btn--primary" :disabled="loading" @click="search">
        {{ loading ? 'Searching...' : 'Search' }}
      </button>
    </div>

    <div v-if="loading" class="loading">Searching...</div>

    <div v-else-if="searched && !searchResults.length" class="no-results">
      No results found for "{{ searchQuery }}"
    </div>

    <div v-else-if="searchResults.length" class="results">
      <h2 class="results__title">{{ searchResults.length }} results found</h2>
      
      <div class="table-container">
        <table class="table">
          <thead>
            <tr>
              <th>IP Address</th>
              <th>Hostname</th>
              <th>Status</th>
              <th>Assigned To</th>
              <th>MAC Address</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="ip in searchResults" :key="ip.id">
              <td><code>{{ ip.ip_address }}</code></td>
              <td>{{ ip.hostname || '-' }}</td>
              <td>
                <span class="status" :class="getStatusClass(ip.status)">
                  {{ ip.status }}
                </span>
              </td>
              <td>{{ ip.assigned_to || '-' }}</td>
              <td>
                <code v-if="ip.mac_address">{{ ip.mac_address }}</code>
                <span v-else>-</span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<style scoped>
.search-view {
  max-width: 1200px;
  margin: 0 auto;
}

.page-header {
  margin-bottom: 2rem;
}

.page-header__title {
  font-size: 1.875rem;
  font-weight: bold;
  color: #1f2937;
  margin: 0 0 0.25rem;
}

.page-header__subtitle {
  color: #6b7280;
  margin: 0;
}

.search-box {
  display: flex;
  gap: 1rem;
  margin-bottom: 2rem;
}

.search-box__input {
  flex: 1;
  padding: 1rem 1.25rem;
  border: 2px solid #e5e7eb;
  border-radius: 12px;
  font-size: 1.125rem;
  transition: border-color 0.2s ease;
}

.search-box__input:focus {
  outline: none;
  border-color: #4ade80;
}

.loading,
.no-results {
  text-align: center;
  padding: 3rem;
  background: #fff;
  border-radius: 12px;
  color: #6b7280;
}

.results__title {
  font-size: 1rem;
  color: #6b7280;
  margin: 0 0 1rem;
}

.table-container {
  background: #fff;
  border-radius: 12px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  overflow: hidden;
}

.table {
  width: 100%;
  border-collapse: collapse;
}

.table th,
.table td {
  text-align: left;
  padding: 0.875rem 1rem;
  border-bottom: 1px solid #f3f4f6;
}

.table th {
  background-color: #f9fafb;
  font-weight: 500;
  color: #6b7280;
  font-size: 0.75rem;
  text-transform: uppercase;
}

.table code {
  background-color: #f3f4f6;
  padding: 0.25rem 0.5rem;
  border-radius: 4px;
  font-size: 0.875rem;
}

.status {
  display: inline-block;
  padding: 0.25rem 0.625rem;
  border-radius: 9999px;
  font-size: 0.75rem;
  font-weight: 500;
  text-transform: capitalize;
}

.status--available {
  background-color: #dcfce7;
  color: #166534;
}

.status--assigned {
  background-color: #dbeafe;
  color: #1e40af;
}

.status--reserved {
  background-color: #fef3c7;
  color: #92400e;
}

.status--dhcp {
  background-color: #f3e8ff;
  color: #7c3aed;
}

.btn {
  padding: 1rem 2rem;
  border-radius: 12px;
  font-weight: 600;
  cursor: pointer;
  border: none;
  font-size: 1rem;
}

.btn--primary {
  background-color: #4ade80;
  color: #fff;
}

.btn--primary:hover:not(:disabled) {
  background-color: #22c55e;
}

.btn--primary:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}
</style>
