<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useSubnetsStore } from '@/stores'

const router = useRouter()
const subnetsStore = useSubnetsStore()

const showCreateModal = ref(false)
const searchQuery = ref('')

onMounted(() => {
  subnetsStore.fetchSubnets()
})

function viewSubnet(id: number) {
  router.push(`/subnets/${id}`)
}

function getUtilizationClass(percent: number) {
  if (percent >= 90) return 'utilization--danger'
  if (percent >= 70) return 'utilization--warning'
  return 'utilization--ok'
}
</script>

<template>
  <div class="subnets-view">
    <header class="page-header">
      <div>
        <h1 class="page-header__title">Subnets</h1>
        <p class="page-header__subtitle">Manage IP subnets and address space</p>
      </div>
      <button class="btn btn--primary" @click="showCreateModal = true">
        + Add Subnet
      </button>
    </header>

    <!-- Search & Filters -->
    <div class="filters">
      <input
        v-model="searchQuery"
        type="search"
        class="filters__search"
        placeholder="Search subnets..."
        @input="subnetsStore.fetchSubnets({ search: searchQuery })"
      >
    </div>

    <!-- Loading State -->
    <div v-if="subnetsStore.loading" class="loading">
      Loading subnets...
    </div>

    <!-- Error State -->
    <div v-else-if="subnetsStore.error" class="error">
      {{ subnetsStore.error }}
    </div>

    <!-- Subnets Table -->
    <div v-else class="table-container">
      <table class="table">
        <thead>
          <tr>
            <th>Name</th>
            <th>Network</th>
            <th>Gateway</th>
            <th>VLAN</th>
            <th>Used / Total</th>
            <th>Utilization</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="subnet in subnetsStore.subnets"
            :key="subnet.id"
            class="table__row--clickable"
            @click="viewSubnet(subnet.id)"
          >
            <td>
              <strong>{{ subnet.name }}</strong>
              <small v-if="subnet.description" class="text-muted">
                {{ subnet.description }}
              </small>
            </td>
            <td>
              <code>{{ subnet.network }}/{{ subnet.cidr }}</code>
            </td>
            <td>
              <code v-if="subnet.gateway">{{ subnet.gateway }}</code>
              <span v-else class="text-muted">-</span>
            </td>
            <td>
              <span v-if="subnet.vlan_id" class="badge">VLAN {{ subnet.vlan_id }}</span>
              <span v-else class="text-muted">-</span>
            </td>
            <td>
              {{ subnet.used_addresses }} / {{ subnet.total_addresses }}
            </td>
            <td>
              <div class="utilization" :class="getUtilizationClass(subnet.utilization_percent)">
                <div class="utilization__bar">
                  <div
                    class="utilization__fill"
                    :style="{ width: `${subnet.utilization_percent}%` }"
                  ></div>
                </div>
                <span class="utilization__percent">{{ subnet.utilization_percent }}%</span>
              </div>
            </td>
            <td @click.stop>
              <button class="btn btn--small btn--secondary">
                Edit
              </button>
            </td>
          </tr>
        </tbody>
      </table>

      <!-- Empty State -->
      <div v-if="!subnetsStore.subnets.length" class="empty-state">
        <p>No subnets found</p>
        <button class="btn btn--primary" @click="showCreateModal = true">
          Create your first subnet
        </button>
      </div>
    </div>

    <!-- Pagination -->
    <div v-if="subnetsStore.pagination.lastPage > 1" class="pagination">
      <button
        class="btn btn--secondary"
        :disabled="subnetsStore.pagination.currentPage === 1"
        @click="subnetsStore.fetchSubnets({ page: subnetsStore.pagination.currentPage - 1 })"
      >
        Previous
      </button>
      <span class="pagination__info">
        Page {{ subnetsStore.pagination.currentPage }} of {{ subnetsStore.pagination.lastPage }}
      </span>
      <button
        class="btn btn--secondary"
        :disabled="subnetsStore.pagination.currentPage === subnetsStore.pagination.lastPage"
        @click="subnetsStore.fetchSubnets({ page: subnetsStore.pagination.currentPage + 1 })"
      >
        Next
      </button>
    </div>

    <!-- Create Modal (placeholder) -->
    <div v-if="showCreateModal" class="modal-overlay" @click.self="showCreateModal = false">
      <div class="modal">
        <h2>Create Subnet</h2>
        <p>Subnet creation form will go here...</p>
        <button class="btn btn--secondary" @click="showCreateModal = false">Close</button>
      </div>
    </div>
  </div>
</template>

<style scoped>
.subnets-view {
  max-width: 1400px;
  margin: 0 auto;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
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
  margin-bottom: 1.5rem;
}

.filters__search {
  width: 100%;
  max-width: 400px;
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

.loading,
.error {
  text-align: center;
  padding: 3rem;
  background: #fff;
  border-radius: 12px;
}

.error {
  background-color: #fef2f2;
  color: #dc2626;
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
  padding: 1rem;
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

.table__row--clickable {
  cursor: pointer;
  transition: background-color 0.15s ease;
}

.table__row--clickable:hover {
  background-color: #f9fafb;
}

.table code {
  background-color: #f3f4f6;
  padding: 0.25rem 0.5rem;
  border-radius: 4px;
  font-size: 0.875rem;
}

.table small {
  display: block;
  font-size: 0.75rem;
  color: #9ca3af;
  margin-top: 0.25rem;
}

.text-muted {
  color: #9ca3af;
}

.badge {
  display: inline-block;
  background-color: #dbeafe;
  color: #1d4ed8;
  padding: 0.25rem 0.5rem;
  border-radius: 4px;
  font-size: 0.75rem;
  font-weight: 500;
}

.utilization {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.utilization__bar {
  width: 60px;
  height: 6px;
  background-color: #e5e7eb;
  border-radius: 3px;
  overflow: hidden;
}

.utilization__fill {
  height: 100%;
  background-color: #4ade80;
  border-radius: 3px;
}

.utilization--warning .utilization__fill {
  background-color: #fbbf24;
}

.utilization--danger .utilization__fill {
  background-color: #ef4444;
}

.utilization__percent {
  font-size: 0.875rem;
  font-weight: 500;
  min-width: 40px;
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

.btn--primary {
  background-color: #4ade80;
  color: #fff;
}

.btn--primary:hover {
  background-color: #22c55e;
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

.btn--small {
  padding: 0.375rem 0.75rem;
  font-size: 0.875rem;
}

.modal-overlay {
  position: fixed;
  inset: 0;
  background-color: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 100;
}

.modal {
  background: #fff;
  border-radius: 12px;
  padding: 2rem;
  max-width: 500px;
  width: 90%;
}
</style>
