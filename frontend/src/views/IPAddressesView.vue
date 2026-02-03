<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useIPAddressesStore, useAuthStore } from '@/stores'

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
    ipStore.fetchIPAddresses()
  } catch (error: any) {
    if (error.response?.data?.errors) {
      formErrors.value = error.response.data.errors
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
  
  try {
    await ipStore.updateIPAddress(selectedIP.value.id, {
      label: form.value.label,
      comment: form.value.comment || null
    })
    showEditModal.value = false
    ipStore.fetchIPAddresses()
  } catch (error: any) {
    if (error.response?.data?.errors) {
      formErrors.value = error.response.data.errors
    }
  }
}

async function deleteIP() {
  if (!selectedIP.value) return
  
  try {
    await ipStore.deleteIPAddress(selectedIP.value.id)
    showDeleteModal.value = false
    ipStore.fetchIPAddresses()
  } catch (error: any) {
    console.error('Failed to delete IP:', error)
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
      <div>
        <h1 class="page-header__title">IP Addresses</h1>
        <p class="page-header__subtitle">Manage your IP address inventory</p>
      </div>
      <button class="btn btn--primary" @click="openAddModal">
        + Add IP Address
      </button>
    </header>

    <!-- Filters -->
    <div class="filters">
      <input
        v-model="searchQuery"
        type="search"
        class="filters__search"
        placeholder="Search by IP address or label..."
        @keyup.enter="search"
      >
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
            <th>Label</th>
            <th>Comment</th>
            <th>Added By</th>
            <th>Created</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="ip in ipStore.ipAddresses" :key="ip.id">
            <td><code>{{ ip.ip_address }}</code></td>
            <td class="label-cell">{{ ip.label }}</td>
            <td class="comment-cell">{{ ip.comment || '-' }}</td>
            <td>{{ ip.created_by_name || 'Unknown' }}</td>
            <td>{{ formatDate(ip.created_at) }}</td>
            <td class="actions-cell">
              <button
                v-if="canEdit(ip)"
                class="btn btn--small btn--secondary"
                @click="openEditModal(ip)"
              >
                Edit
              </button>
              <button
                v-if="canDelete(ip)"
                class="btn btn--small btn--danger"
                @click="openDeleteModal(ip)"
              >
                Delete
              </button>
              <span v-if="!canEdit(ip) && !canDelete(ip)" class="text-muted">-</span>
            </td>
          </tr>
        </tbody>
      </table>

      <div v-if="!ipStore.ipAddresses.length" class="empty-state">
        No IP addresses found. Click "Add IP Address" to create one.
      </div>
    </div>

    <!-- Pagination -->
    <div v-if="ipStore.pagination.lastPage > 1" class="pagination">
      <button
        class="btn btn--secondary"
        :disabled="ipStore.pagination.currentPage === 1"
        @click="ipStore.fetchIPAddresses({ page: ipStore.pagination.currentPage - 1, search: searchQuery })"
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
        @click="ipStore.fetchIPAddresses({ page: ipStore.pagination.currentPage + 1, search: searchQuery })"
      >
        Next
      </button>
    </div>

    <!-- Add IP Modal -->
    <div v-if="showAddModal" class="modal-overlay" @click.self="showAddModal = false">
      <div class="modal">
        <div class="modal__header">
          <h2 class="modal__title">Add IP Address</h2>
          <button class="modal__close" @click="showAddModal = false">&times;</button>
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
          <button class="modal__close" @click="showEditModal = false">&times;</button>
        </div>
        <form class="modal__body" @submit.prevent="updateIP">
          <div class="form-group">
            <label class="form-label">IP Address</label>
            <input
              :value="form.ip_address"
              type="text"
              class="form-input"
              disabled
            >
            <span class="form-hint">IP address cannot be changed</span>
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
          <button class="modal__close" @click="showDeleteModal = false">&times;</button>
        </div>
        <div class="modal__body">
          <p>Are you sure you want to delete this IP address?</p>
          <p class="delete-info">
            <strong>IP:</strong> {{ selectedIP?.ip_address }}<br>
            <strong>Label:</strong> {{ selectedIP?.label }}
          </p>
          <p class="text-danger">This action cannot be undone.</p>
        </div>
        <div class="modal__actions">
          <button class="btn btn--secondary" @click="showDeleteModal = false">
            Cancel
          </button>
          <button class="btn btn--danger" @click="deleteIP" :disabled="ipStore.loading">
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
  align-items: center;
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
  font-family: 'Monaco', 'Menlo', monospace;
}

.label-cell {
  font-weight: 500;
  color: #1f2937;
}

.comment-cell {
  color: #6b7280;
  max-width: 300px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.actions-cell {
  display: flex;
  gap: 0.5rem;
}

.text-muted {
  color: #9ca3af;
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

/* Buttons */
.btn {
  padding: 0.625rem 1.25rem;
  border-radius: 8px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.15s ease;
  border: none;
  font-size: 0.875rem;
}

.btn--primary {
  background-color: #4ade80;
  color: #fff;
}

.btn--primary:hover {
  background-color: #22c55e;
}

.btn--primary:disabled {
  background-color: #86efac;
  cursor: not-allowed;
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

.btn--danger {
  background-color: #ef4444;
  color: #fff;
}

.btn--danger:hover {
  background-color: #dc2626;
}

.btn--small {
  padding: 0.375rem 0.75rem;
  font-size: 0.75rem;
}

/* Modal */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}

.modal {
  background: #fff;
  border-radius: 12px;
  width: 100%;
  max-width: 500px;
  max-height: 90vh;
  overflow: auto;
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
}

.modal--small {
  max-width: 400px;
}

.modal__header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.25rem 1.5rem;
  border-bottom: 1px solid #e5e7eb;
}

.modal__title {
  font-size: 1.125rem;
  font-weight: 600;
  color: #1f2937;
  margin: 0;
}

.modal__close {
  background: none;
  border: none;
  font-size: 1.5rem;
  color: #6b7280;
  cursor: pointer;
  line-height: 1;
}

.modal__close:hover {
  color: #1f2937;
}

.modal__body {
  padding: 1.5rem;
}

.modal__actions {
  display: flex;
  justify-content: flex-end;
  gap: 0.75rem;
  padding: 1rem 1.5rem;
  border-top: 1px solid #e5e7eb;
  background-color: #f9fafb;
}

/* Form */
.form-group {
  margin-bottom: 1.25rem;
}

.form-label {
  display: block;
  font-weight: 500;
  color: #374151;
  margin-bottom: 0.5rem;
  font-size: 0.875rem;
}

.form-input {
  width: 100%;
  padding: 0.75rem 1rem;
  border: 1px solid #d1d5db;
  border-radius: 8px;
  font-size: 1rem;
  box-sizing: border-box;
}

.form-input:focus {
  outline: none;
  border-color: #4ade80;
  box-shadow: 0 0 0 3px rgba(74, 222, 128, 0.1);
}

.form-input:disabled {
  background-color: #f3f4f6;
  color: #6b7280;
}

.form-textarea {
  resize: vertical;
  min-height: 80px;
}

.form-error {
  color: #ef4444;
  font-size: 0.75rem;
  margin-top: 0.25rem;
  display: block;
}

.form-hint {
  color: #6b7280;
  font-size: 0.75rem;
  margin-top: 0.25rem;
  display: block;
}

.delete-info {
  background-color: #f3f4f6;
  padding: 1rem;
  border-radius: 8px;
  margin: 1rem 0;
}

.text-danger {
  color: #ef4444;
  font-weight: 500;
}
</style>
