<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useIPAddressesStore, useAuthStore, useToastStore } from '@/stores'

interface IPAddress {
  id: number
  ip_address: string
  label: string
  comment: string | null
  created_by: number
  created_by_name?: string
  created_at: string
  updated_at: string
}

const ipStore = useIPAddressesStore()
const authStore = useAuthStore()
const toast = useToastStore()

const searchQuery = ref('')
const showAddModal = ref(false)
const showEditModal = ref(false)
const showDeleteModal = ref(false)
const selectedIP = ref<IPAddress | null>(null)

// Form fields
const form = ref({
  ip_address: '',
  label: '',
  comment: ''
})

const formErrors = ref<Record<string, string>>({})

const isAdmin = computed(() => authStore.user?.role === 'admin')

// Can edit IP address field if admin OR owner of the IP
const canEditIpField = computed(() => {
  if (!selectedIP.value) return false
  return isAdmin.value || Number(selectedIP.value.created_by) === Number(authStore.user?.id)
})

onMounted(() => {
  ipStore.fetchIPAddresses()
})

function search() {
  ipStore.fetchIPAddresses({ search: searchQuery.value })
}

function canEdit(ip: IPAddress) {
  return isAdmin.value || ip.created_by === authStore.user?.id
}

function canDelete(_ip: IPAddress) {
  return isAdmin.value // Only admins can delete
}

function openAddModal() {
  form.value = { ip_address: '', label: '', comment: '' }
  formErrors.value = {}
  showAddModal.value = true
}

function openEditModal(ip: IPAddress) {
  selectedIP.value = ip
  form.value = {
    ip_address: ip.ip_address,
    label: ip.label,
    comment: ip.comment || ''
  }
  formErrors.value = {}
  showEditModal.value = true
}

function openDeleteModal(ip: IPAddress) {
  selectedIP.value = ip
  showDeleteModal.value = true
}

function validateForm() {
  formErrors.value = {}
  
  if (!form.value.ip_address.trim()) {
    formErrors.value.ip_address = 'IP address is required'
  } else {
    // Basic IP validation (IPv4 or IPv6)
    const ipv4Regex = /^(\d{1,3}\.){3}\d{1,3}$/
    const ipv6Regex = /^([0-9a-fA-F]{1,4}:){7}[0-9a-fA-F]{1,4}$|^::$|^([0-9a-fA-F]{1,4}:)*::([0-9a-fA-F]{1,4}:)*[0-9a-fA-F]{1,4}$/
    if (!ipv4Regex.test(form.value.ip_address) && !ipv6Regex.test(form.value.ip_address)) {
      formErrors.value.ip_address = 'Invalid IP address format'
    }
  }
  
  if (!form.value.label.trim()) {
    formErrors.value.label = 'Label is required'
  }
  
  return Object.keys(formErrors.value).length === 0
}

async function createIP() {
  if (!validateForm()) return
  
  try {
    await ipStore.createIPAddress({
      ip_address: form.value.ip_address,
      label: form.value.label,
      comment: form.value.comment || null
    })
    showAddModal.value = false
    toast.success('IP address created successfully')
    ipStore.fetchIPAddresses()
  } catch (error: any) {
    if (error.errors) {
      // Handle validation errors from API
      const errorMessages = Object.values(error.errors).flat()
      formErrors.value = error.errors
      toast.error(errorMessages[0] as string || 'Failed to create IP address')
    } else {
      toast.error(error.message || 'Failed to create IP address')
    }
  }
}

async function updateIP() {
  if (!selectedIP.value) return
  
  formErrors.value = {}
  if (!form.value.label.trim()) {
    formErrors.value.label = 'Label is required'
    return
  }

  // Validate IP address if user is changing it
  if (canEditIpField.value && form.value.ip_address !== selectedIP.value.ip_address) {
    const ipv4Regex = /^(\d{1,3}\.){3}\d{1,3}$/
    const ipv6Regex = /^([0-9a-fA-F]{1,4}:){7}[0-9a-fA-F]{1,4}$|^::$|^([0-9a-fA-F]{1,4}:)*::([0-9a-fA-F]{1,4}:)*[0-9a-fA-F]{1,4}$/
    if (!ipv4Regex.test(form.value.ip_address) && !ipv6Regex.test(form.value.ip_address)) {
      formErrors.value.ip_address = 'Invalid IP address format'
      return
    }
  }
  
  try {
    const updateData: { label: string; comment: string | null; ip_address?: string } = {
      label: form.value.label,
      comment: form.value.comment || null
    }

    // Include IP address if user can edit and changed it
    if (canEditIpField.value && form.value.ip_address !== selectedIP.value.ip_address) {
      updateData.ip_address = form.value.ip_address
    }

    await ipStore.updateIPAddress(selectedIP.value.id, updateData)
    showEditModal.value = false
    toast.success('IP address updated successfully')
    ipStore.fetchIPAddresses()
  } catch (error: any) {
    if (error.errors) {
      formErrors.value = error.errors
      toast.error('Failed to update IP address')
    } else {
      toast.error(error.message || 'Failed to update IP address')
    }
  }
}

async function deleteIP() {
  if (!selectedIP.value) return
  
  try {
    await ipStore.deleteIPAddress(selectedIP.value.id)
    showDeleteModal.value = false
    toast.success('IP address deleted successfully')
    ipStore.fetchIPAddresses()
  } catch (error: any) {
    toast.error(error.message || 'Failed to delete IP address')
  }
}

function formatDate(dateString: string) {
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}
</script>

<template>
  <div class="ip-addresses-view">
    <header class="page-header">
      <div class="page-header__content">
        <h1 class="page-header__title">IP Addresses</h1>
        <p class="page-header__subtitle">Manage your IP address inventory</p>
      </div>
      <button class="btn btn--primary" @click="openAddModal">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
          <path d="M12 4a1 1 0 0 1 1 1v6h6a1 1 0 1 1 0 2h-6v6a1 1 0 1 1-2 0v-6H5a1 1 0 1 1 0-2h6V5a1 1 0 0 1 1-1z"/>
        </svg>
        Add IP Address
      </button>
    </header>

    <!-- Filters -->
    <div class="filters">
      <div class="filters__search-wrapper">
        <svg class="filters__search-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
          <path d="M15.5 14h-.79l-.28-.27A6.471 6.471 0 0 0 16 9.5 6.5 6.5 0 1 0 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/>
        </svg>
        <input
          v-model="searchQuery"
          type="search"
          class="filters__search"
          placeholder="Search by IP address or label..."
          @keyup.enter="search"
        >
      </div>
      <button class="btn btn--secondary" @click="search">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="18" height="18">
          <path d="M15.5 14h-.79l-.28-.27A6.471 6.471 0 0 0 16 9.5 6.5 6.5 0 1 0 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/>
        </svg>
        Search
      </button>
    </div>

    <!-- Loading -->
    <div v-if="ipStore.loading" class="loading">
      <div class="loading-spinner">
        <svg class="loading-spinner__svg" viewBox="0 0 50 50">
          <circle class="loading-spinner__circle" cx="25" cy="25" r="20" fill="none" stroke-width="4"></circle>
        </svg>
      </div>
      <p>Loading IP addresses...</p>
    </div>

    <!-- Table -->
    <div v-else class="table-container">
      <!-- Desktop Table -->
      <div class="table-responsive">
        <table class="table">
          <thead>
            <tr>
              <th>IP Address</th>
              <th>Label</th>
              <th class="hide-tablet">Comment</th>
              <th class="hide-tablet">Added By</th>
              <th>Created</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="ip in ipStore.ipAddresses" :key="ip.id">
              <td><code>{{ ip.ip_address }}</code></td>
              <td class="label-cell">{{ ip.label }}</td>
              <td class="comment-cell hide-tablet">{{ ip.comment || '-' }}</td>
              <td class="hide-tablet">{{ ip.created_by_name || 'Unknown' }}</td>
              <td class="date-cell">{{ formatDate(ip.created_at) }}</td>
              <td class="actions-cell">
                <button
                  v-if="canEdit(ip)"
                  class="btn btn--small btn--secondary"
                  @click="openEditModal(ip)"
                >
                  <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04a.996.996 0 0 0 0-1.41l-2.34-2.34a.996.996 0 0 0-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/>
                  </svg>
                  Edit
                </button>
                <button
                  v-if="canDelete(ip)"
                  class="btn btn--small btn--danger"
                  @click="openDeleteModal(ip)"
                >
                  <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/>
                  </svg>
                  Delete
                </button>
                <span v-if="!canEdit(ip) && !canDelete(ip)" class="text-muted">-</span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Mobile Card View -->
      <div class="mobile-cards">
        <div v-for="ip in ipStore.ipAddresses" :key="'mobile-' + ip.id" class="mobile-card">
          <div class="mobile-card__header">
            <code class="mobile-card__ip">{{ ip.ip_address }}</code>
          </div>
          <div class="mobile-card__label">{{ ip.label }}</div>
          <div v-if="ip.comment" class="mobile-card__comment">{{ ip.comment }}</div>
          <div class="mobile-card__meta">
            <span>Added by {{ ip.created_by_name || 'Unknown' }}</span>
            <span>{{ formatDate(ip.created_at) }}</span>
          </div>
          <div class="mobile-card__actions">
            <button
              v-if="canEdit(ip)"
              class="btn btn--small btn--secondary"
              @click="openEditModal(ip)"
            >
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                <path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04a.996.996 0 0 0 0-1.41l-2.34-2.34a.996.996 0 0 0-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/>
              </svg>
              Edit
            </button>
            <button
              v-if="canDelete(ip)"
              class="btn btn--small btn--danger"
              @click="openDeleteModal(ip)"
            >
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                <path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/>
              </svg>
              Delete
            </button>
          </div>
        </div>
      </div>

      <div v-if="!ipStore.ipAddresses.length" class="empty-state">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
          <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 17.93c-3.95-.49-7-3.85-7-7.93 0-.62.08-1.21.21-1.79L9 15v1c0 1.1.9 2 2 2v1.93zm6.9-2.54c-.26-.81-1-1.39-1.9-1.39h-1v-3c0-.55-.45-1-1-1H8v-2h2c.55 0 1-.45 1-1V7h2c1.1 0 2-.9 2-2v-.41c2.93 1.19 5 4.06 5 7.41 0 2.08-.8 3.97-2.1 5.39z"/>
        </svg>
        <p>No IP addresses found</p>
        <p style="font-size: 0.875rem;">Click "Add IP Address" to create one.</p>
      </div>
    </div>

    <!-- Pagination -->
    <div v-if="ipStore.pagination.lastPage > 1" class="pagination">
      <button
        class="btn btn--secondary"
        :disabled="ipStore.pagination.currentPage === 1"
        @click="ipStore.fetchIPAddresses({ page: ipStore.pagination.currentPage - 1, search: searchQuery })"
      >
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="18" height="18">
          <path d="M15.41 7.41L14 6l-6 6 6 6 1.41-1.41L10.83 12z"/>
        </svg>
        Previous
      </button>
      <span class="pagination__info">
        Page {{ ipStore.pagination.currentPage }} of {{ ipStore.pagination.lastPage }}
        ({{ ipStore.pagination.total }} total)
      </span>
      <button
        class="btn btn--secondary"
        :disabled="ipStore.pagination.currentPage === ipStore.pagination.lastPage"
        @click="ipStore.fetchIPAddresses({ page: ipStore.pagination.currentPage + 1, search: searchQuery })"
      >
        Next
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="18" height="18">
          <path d="M10 6L8.59 7.41 13.17 12l-4.58 4.59L10 18l6-6z"/>
        </svg>
      </button>
    </div>

    <!-- Add IP Modal -->
    <div v-if="showAddModal" class="modal-overlay" @click.self="showAddModal = false">
      <div class="modal">
        <div class="modal__header">
          <h2 class="modal__title">Add IP Address</h2>
          <button class="modal__close" @click="showAddModal = false">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
              <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/>
            </svg>
          </button>
        </div>
        <form class="modal__body" @submit.prevent="createIP">
          <div class="form-group">
            <label class="form-label">IP Address *</label>
            <input
              v-model="form.ip_address"
              type="text"
              class="form-input"
              placeholder="e.g., 192.168.1.100 or 2001:db8::1"
            >
            <span v-if="formErrors.ip_address" class="form-error">{{ formErrors.ip_address }}</span>
          </div>
          <div class="form-group">
            <label class="form-label">Label *</label>
            <input
              v-model="form.label"
              type="text"
              class="form-input"
              placeholder="e.g., Web Server, Database, etc."
            >
            <span v-if="formErrors.label" class="form-error">{{ formErrors.label }}</span>
          </div>
          <div class="form-group">
            <label class="form-label">Comment (optional)</label>
            <textarea
              v-model="form.comment"
              class="form-input form-textarea"
              rows="3"
              placeholder="Add any additional notes..."
            ></textarea>
          </div>
          <div class="modal__actions">
            <button type="button" class="btn btn--secondary" @click="showAddModal = false">
              Cancel
            </button>
            <button type="submit" class="btn btn--primary" :disabled="ipStore.loading">
              {{ ipStore.loading ? 'Creating...' : 'Create IP Address' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Edit IP Modal -->
    <div v-if="showEditModal" class="modal-overlay" @click.self="showEditModal = false">
      <div class="modal">
        <div class="modal__header">
          <h2 class="modal__title">Edit IP Address</h2>
          <button class="modal__close" @click="showEditModal = false">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
              <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/>
            </svg>
          </button>
        </div>
        <form class="modal__body" @submit.prevent="updateIP">
          <div class="form-group">
            <label class="form-label">IP Address</label>
            <input
              v-model="form.ip_address"
              type="text"
              class="form-input"
              :disabled="!canEditIpField"
              :class="{ 'form-input--disabled': !canEditIpField }"
            >
            <span v-if="formErrors.ip_address" class="form-error">{{ formErrors.ip_address }}</span>
            <span v-else-if="!canEditIpField" class="form-hint">IP address cannot be changed (not owner)</span>
            <span v-else class="form-hint form-hint--admin">You can modify the IP address</span>
          </div>
          <div class="form-group">
            <label class="form-label">Label *</label>
            <input
              v-model="form.label"
              type="text"
              class="form-input"
              placeholder="e.g., Web Server, Database, etc."
            >
            <span v-if="formErrors.label" class="form-error">{{ formErrors.label }}</span>
          </div>
          <div class="form-group">
            <label class="form-label">Comment (optional)</label>
            <textarea
              v-model="form.comment"
              class="form-input form-textarea"
              rows="3"
              placeholder="Add any additional notes..."
            ></textarea>
          </div>
          <div class="modal__actions">
            <button type="button" class="btn btn--secondary" @click="showEditModal = false">
              Cancel
            </button>
            <button type="submit" class="btn btn--primary" :disabled="ipStore.loading">
              {{ ipStore.loading ? 'Saving...' : 'Save Changes' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div v-if="showDeleteModal" class="modal-overlay" @click.self="showDeleteModal = false">
      <div class="modal modal--small">
        <div class="modal__header">
          <h2 class="modal__title">Delete IP Address</h2>
          <button class="modal__close" @click="showDeleteModal = false">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
              <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/>
            </svg>
          </button>
        </div>
        <div class="modal__body">
          <p>Are you sure you want to delete this IP address?</p>
          <div class="delete-info">
            <p style="margin: 0 0 0.5rem;"><strong>IP:</strong> <code>{{ selectedIP?.ip_address }}</code></p>
            <p style="margin: 0;"><strong>Label:</strong> {{ selectedIP?.label }}</p>
          </div>
          <p class="text-danger" style="margin: 0;">⚠️ This action cannot be undone.</p>
        </div>
        <div class="modal__actions">
          <button class="btn btn--secondary" @click="showDeleteModal = false">
            Cancel
          </button>
          <button class="btn btn--danger" @click="deleteIP" :disabled="ipStore.loading">
            <svg v-if="!ipStore.loading" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
              <path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/>
            </svg>
            {{ ipStore.loading ? 'Deleting...' : 'Delete' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.ip-addresses-view {
  max-width: 1400px;
  margin: 0 auto;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 2rem;
  gap: 1rem;
}

.page-header__content {
  flex: 1;
}

.page-header__title {
  font-size: 1.75rem;
  font-weight: 700;
  color: #0f172a;
  margin: 0 0 0.25rem;
  letter-spacing: -0.025em;
}

.page-header__subtitle {
  color: #64748b;
  margin: 0;
  font-size: 0.9375rem;
}

/* Search & Filters */
.filters {
  display: flex;
  gap: 1rem;
  margin-bottom: 1.5rem;
  flex-wrap: wrap;
}

.filters__search-wrapper {
  flex: 1;
  min-width: 280px;
  position: relative;
}

.filters__search-icon {
  position: absolute;
  left: 1rem;
  top: 50%;
  transform: translateY(-50%);
  width: 20px;
  height: 20px;
  color: #94a3b8;
  pointer-events: none;
}

.filters__search {
  width: 100%;
  padding: 0.875rem 1rem 0.875rem 2.75rem;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  font-size: 0.9375rem;
  background: #fff;
  transition: all 0.2s ease;
}

.filters__search:focus {
  outline: none;
  border-color: #4ade80;
  box-shadow: 0 0 0 3px rgba(74, 222, 128, 0.15);
}

.filters__search::placeholder {
  color: #94a3b8;
}

/* Loading */
.loading {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 4rem 2rem;
  background: #fff;
  border-radius: 16px;
  color: #64748b;
  gap: 1rem;
  border: 1px solid #f1f5f9;
}

.loading-spinner {
  width: 48px;
  height: 48px;
}

.loading-spinner__svg {
  animation: rotate 2s linear infinite;
  width: 100%;
  height: 100%;
}

.loading-spinner__circle {
  stroke: #4ade80;
  stroke-linecap: round;
  animation: dash 1.5s ease-in-out infinite;
}

@keyframes rotate {
  100% { transform: rotate(360deg); }
}

@keyframes dash {
  0% {
    stroke-dasharray: 1, 150;
    stroke-dashoffset: 0;
  }
  50% {
    stroke-dasharray: 90, 150;
    stroke-dashoffset: -35;
  }
  100% {
    stroke-dasharray: 90, 150;
    stroke-dashoffset: -124;
  }
}

/* Table Container */
.table-container {
  background: #fff;
  border-radius: 16px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05), 0 1px 2px rgba(0, 0, 0, 0.1);
  border: 1px solid #f1f5f9;
  overflow: hidden;
}

.table-responsive {
  overflow-x: auto;
}

.table {
  width: 100%;
  border-collapse: collapse;
}

.table th,
.table td {
  text-align: left;
  padding: 1rem 1.5rem;
  border-bottom: 1px solid #f1f5f9;
}

.table th {
  background-color: #f8fafc;
  font-weight: 600;
  color: #64748b;
  font-size: 0.75rem;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.table tbody tr {
  transition: background-color 0.15s ease;
}

.table tbody tr:hover {
  background-color: #f8fafc;
}

.table tbody tr:last-child td {
  border-bottom: none;
}

.table code {
  background-color: #f1f5f9;
  padding: 0.375rem 0.625rem;
  border-radius: 6px;
  font-size: 0.8125rem;
  font-family: 'SF Mono', 'Menlo', 'Monaco', 'Courier New', monospace;
  color: #0f172a;
}

.label-cell {
  font-weight: 500;
  color: #0f172a;
}

.comment-cell {
  color: #64748b;
  max-width: 250px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.date-cell {
  color: #64748b;
  white-space: nowrap;
  font-size: 0.875rem;
}

.actions-cell {
  display: flex;
  gap: 0.5rem;
}

.text-muted {
  color: #94a3b8;
}

.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 4rem 2rem;
  color: #64748b;
  gap: 0.75rem;
}

.empty-state svg {
  width: 56px;
  height: 56px;
  color: #cbd5e1;
  margin-bottom: 0.5rem;
}

/* Pagination */
.pagination {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 1rem;
  margin-top: 1.5rem;
  padding: 1rem;
  background: #fff;
  border-radius: 12px;
  border: 1px solid #f1f5f9;
}

.pagination__info {
  color: #64748b;
  font-size: 0.875rem;
}

/* Buttons */
.btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  padding: 0.75rem 1.25rem;
  border-radius: 10px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s ease;
  border: none;
  font-size: 0.875rem;
}

.btn svg {
  width: 18px;
  height: 18px;
}

.btn--primary {
  background: linear-gradient(135deg, #4ade80 0%, #22c55e 100%);
  color: #fff;
  box-shadow: 0 2px 8px rgba(74, 222, 128, 0.3);
}

.btn--primary:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(74, 222, 128, 0.4);
}

.btn--primary:active {
  transform: translateY(0);
}

.btn--primary:disabled {
  background: #86efac;
  cursor: not-allowed;
  box-shadow: none;
  transform: none;
}

.btn--secondary {
  background-color: #f1f5f9;
  color: #475569;
}

.btn--secondary:hover {
  background-color: #e2e8f0;
}

.btn--secondary:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.btn--danger {
  background: linear-gradient(135deg, #f87171 0%, #ef4444 100%);
  color: #fff;
  box-shadow: 0 2px 8px rgba(239, 68, 68, 0.3);
}

.btn--danger:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(239, 68, 68, 0.4);
}

.btn--small {
  padding: 0.5rem 0.75rem;
  font-size: 0.75rem;
  border-radius: 8px;
}

.btn--small svg {
  width: 14px;
  height: 14px;
}

/* Modal */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: rgba(15, 23, 42, 0.6);
  backdrop-filter: blur(4px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  padding: 1rem;
  animation: fadeIn 0.2s ease;
}

@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}

.modal {
  background: #fff;
  border-radius: 20px;
  width: 100%;
  max-width: 500px;
  max-height: calc(100vh - 2rem);
  overflow: auto;
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
  animation: slideUp 0.3s ease;
}

@keyframes slideUp {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.modal--small {
  max-width: 420px;
}

.modal__header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.5rem;
  border-bottom: 1px solid #f1f5f9;
}

.modal__title {
  font-size: 1.125rem;
  font-weight: 700;
  color: #0f172a;
  margin: 0;
}

.modal__close {
  width: 36px;
  height: 36px;
  border-radius: 10px;
  background: #f1f5f9;
  border: none;
  color: #64748b;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s ease;
}

.modal__close:hover {
  background: #e2e8f0;
  color: #0f172a;
}

.modal__close svg {
  width: 20px;
  height: 20px;
}

.modal__body {
  padding: 1.5rem;
}

.modal__actions {
  display: flex;
  justify-content: flex-end;
  gap: 0.75rem;
  padding: 1.25rem 1.5rem;
  border-top: 1px solid #f1f5f9;
  background-color: #f8fafc;
  border-radius: 0 0 20px 20px;
}

/* Form */
.form-group {
  margin-bottom: 1.25rem;
}

.form-group:last-child {
  margin-bottom: 0;
}

.form-label {
  display: block;
  font-weight: 600;
  color: #0f172a;
  margin-bottom: 0.5rem;
  font-size: 0.875rem;
}

.form-input {
  width: 100%;
  padding: 0.875rem 1rem;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  font-size: 0.9375rem;
  box-sizing: border-box;
  transition: all 0.2s ease;
}

.form-input:focus {
  outline: none;
  border-color: #4ade80;
  box-shadow: 0 0 0 3px rgba(74, 222, 128, 0.15);
}

.form-input:disabled {
  background-color: #f8fafc;
  color: #64748b;
  cursor: not-allowed;
}

.form-textarea {
  resize: vertical;
  min-height: 100px;
  font-family: inherit;
}

.form-error {
  color: #ef4444;
  font-size: 0.75rem;
  margin-top: 0.375rem;
  display: block;
  font-weight: 500;
}

.form-hint {
  color: #64748b;
  font-size: 0.75rem;
  margin-top: 0.375rem;
  display: block;
}

.form-hint--admin {
  color: #22c55e;
  font-weight: 500;
}

.form-input--disabled {
  background-color: #f1f5f9;
  cursor: not-allowed;
  color: #64748b;
}

.delete-info {
  background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
  padding: 1.25rem;
  border-radius: 12px;
  margin: 1rem 0;
  border: 1px solid #e2e8f0;
}

.delete-info code {
  background-color: #fff;
  padding: 0.25rem 0.5rem;
  border-radius: 4px;
  font-size: 0.875rem;
  font-family: 'SF Mono', 'Menlo', monospace;
}

.text-danger {
  color: #ef4444;
  font-weight: 600;
}

/* Mobile Cards (shown on small screens) */
.mobile-cards {
  display: none;
}

.mobile-card {
  background: #fff;
  border: 1px solid #f1f5f9;
  border-radius: 16px;
  padding: 1.25rem;
  margin-bottom: 1rem;
}

.mobile-card__header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 1rem;
}

.mobile-card__ip {
  background-color: #f1f5f9;
  padding: 0.375rem 0.625rem;
  border-radius: 6px;
  font-size: 0.875rem;
  font-family: 'SF Mono', 'Menlo', monospace;
  color: #0f172a;
}

.mobile-card__label {
  font-weight: 600;
  color: #0f172a;
  font-size: 1rem;
  margin-bottom: 0.25rem;
}

.mobile-card__comment {
  color: #64748b;
  font-size: 0.875rem;
  margin-bottom: 1rem;
}

.mobile-card__meta {
  display: flex;
  flex-wrap: wrap;
  gap: 1rem;
  font-size: 0.75rem;
  color: #94a3b8;
  margin-bottom: 1rem;
  padding-top: 0.75rem;
  border-top: 1px solid #f1f5f9;
}

.mobile-card__actions {
  display: flex;
  gap: 0.5rem;
}

/* Hide columns on tablet */
@media (max-width: 1024px) {
  .hide-tablet {
    display: none;
  }

  .comment-cell {
    max-width: 150px;
  }
}

/* Mobile Responsive */
@media (max-width: 768px) {
  .page-header {
    flex-direction: column;
    gap: 1rem;
  }

  .page-header__title {
    font-size: 1.5rem;
  }

  .filters {
    flex-direction: column;
  }

  .filters__search-wrapper {
    min-width: auto;
  }

  /* Hide desktop table, show mobile cards */
  .table-responsive {
    display: none;
  }

  .mobile-cards {
    display: block;
    padding: 1rem;
  }

  .pagination {
    flex-wrap: wrap;
    gap: 0.75rem;
  }

  .pagination__info {
    width: 100%;
    text-align: center;
    order: -1;
  }

  .modal {
    max-width: 100%;
    margin: 1rem;
    border-radius: 16px;
  }

  .modal__actions {
    flex-direction: column;
  }

  .modal__actions .btn {
    width: 100%;
  }
}

@media (max-width: 480px) {
  .page-header__title {
    font-size: 1.25rem;
  }

  .btn--primary:not(.btn--small) {
    width: 100%;
  }

  .mobile-card {
    padding: 1rem;
  }
}
</style>
