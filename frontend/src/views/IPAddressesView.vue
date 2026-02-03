<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useIPAddressesStore } from '@/stores'

const ipStore = useIPAddressesStore()
const searchQuery = ref('')
const statusFilter = ref('')

onMounted(() => {
  ipStore.fetchIPAddresses()
})

function search() {
  ipStore.fetchIPAddresses({
    search: searchQuery.value,
    status: statusFilter.value || undefined,
  })
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
  <div class="ip-addresses-view">
    <header class="page-header">
      <div>
        <h1 class="page-header__title">IP Addresses</h1>
        <p class="page-header__subtitle">View and manage all IP addresses</p>
      </div>
    </header>

    <!-- Filters -->
    <div class="filters">
      <input
        v-model="searchQuery"
        type="search"
        class="filters__search"
        placeholder="Search by IP, hostname, or MAC..."
        @keyup.enter="search"
      >
      <select v-model="statusFilter" class="filters__select" @change="search">
        <option value="">All Status</option>
        <option value="available">Available</option>
        <option value="assigned">Assigned</option>
        <option value="reserved">Reserved</option>
        <option value="dhcp">DHCP</option>
      </select>
      <button class="btn btn--secondary" @click="search">Search</button>
    </div>

    <!-- Loading -->
    <div v-if="ipStore.loading" class="loading">Loading IP addresses...</div>

    <!-- Table -->
    <div v-else class="table-container">
      <table class="table">
        <thead>
          <tr>
            <th>IP Address</th>
            <th>Hostname</th>
            <th>Status</th>
            <th>Assigned To</th>
            <th>MAC Address</th>
            <th>Device Type</th>
            <th>Last Seen</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="ip in ipStore.ipAddresses" :key="ip.id">
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
            <td>{{ ip.device_type || '-' }}</td>
            <td>{{ ip.last_seen || '-' }}</td>
          </tr>
        </tbody>
      </table>

      <div v-if="!ipStore.ipAddresses.length" class="empty-state">
        No IP addresses found
      </div>
    </div>

    <!-- Pagination -->
    <div v-if="ipStore.pagination.lastPage > 1" class="pagination">
      <button
        class="btn btn--secondary"
        :disabled="ipStore.pagination.currentPage === 1"
        @click="ipStore.fetchIPAddresses({ page: ipStore.pagination.currentPage - 1 })"
      >
        Previous
      </button>
      <span class="pagination__info">
        Page {{ ipStore.pagination.currentPage }} of {{ ipStore.pagination.lastPage }}
        ({{ ipStore.pagination.total }} total)
      </span>
      <button
        class="btn btn--secondary"
        :disabled="ipStore.pagination.currentPage === ipStore.pagination.lastPage"
        @click="ipStore.fetchIPAddresses({ page: ipStore.pagination.currentPage + 1 })"
      >
        Next
      </button>
    </div>
  </div>
</template>

<style scoped>
.ip-addresses-view {
  max-width: 1400px;
  margin: 0 auto;
}

.page-header {
  margin-bottom: 1.5rem;
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

.filters {
  display: flex;
  gap: 1rem;
  margin-bottom: 1.5rem;
  flex-wrap: wrap;
}

.filters__search {
  flex: 1;
  min-width: 250px;
  padding: 0.75rem 1rem;
  border: 1px solid #d1d5db;
  border-radius: 8px;
  font-size: 1rem;
}

.filters__search:focus {
  outline: none;
  border-color: #4ade80;
  box-shadow: 0 0 0 3px rgba(74, 222, 128, 0.1);
}

.filters__select {
  padding: 0.75rem 1rem;
  border: 1px solid #d1d5db;
  border-radius: 8px;
  font-size: 1rem;
  background-color: #fff;
}

.loading {
  text-align: center;
  padding: 3rem;
  background: #fff;
  border-radius: 12px;
  color: #6b7280;
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
  letter-spacing: 0.05em;
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

.empty-state {
  text-align: center;
  padding: 3rem;
  color: #6b7280;
}

.pagination {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 1rem;
  margin-top: 1.5rem;
}

.pagination__info {
  color: #6b7280;
}

.btn {
  padding: 0.625rem 1.25rem;
  border-radius: 8px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.15s ease;
  border: none;
}

.btn--secondary {
  background-color: #f3f4f6;
  color: #374151;
}

.btn--secondary:hover {
  background-color: #e5e7eb;
}

.btn--secondary:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}
</style>
