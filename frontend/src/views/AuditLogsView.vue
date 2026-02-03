<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { apiClient } from '@/api'

interface AuditLog {
  id: number
  action: string
  entity_type: string
  entity_id: number | null
  old_values: Record<string, unknown> | null
  new_values: Record<string, unknown> | null
  user_id: number | null
  user_email: string
  session_id: string | null
  ip_address: string | null
  created_at: string
}

interface AuditLogsResponse {
  data: AuditLog[]
  current_page: number
  last_page: number
  total: number
}

const logs = ref<AuditLog[]>([])
const loading = ref(true)
const error = ref('')
const currentPage = ref(1)
const totalPages = ref(1)
const totalLogs = ref(0)

// Filters
const filterAction = ref('')
const filterUser = ref('')

const filteredLogs = computed(() => {
  let result = logs.value
  
  if (filterAction.value) {
    result = result.filter(log => log.action.toLowerCase().includes(filterAction.value.toLowerCase()))
  }
  
  if (filterUser.value) {
    result = result.filter(log => log.user_email.toLowerCase().includes(filterUser.value.toLowerCase()))
  }
  
  return result
})

async function loadLogs(page = 1) {
  loading.value = true
  error.value = ''
  
  try {
    const response = await apiClient.get<AuditLogsResponse>(`/audit-logs?page=${page}`)
    logs.value = response.data
    currentPage.value = response.current_page
    totalPages.value = response.last_page
    totalLogs.value = response.total
  } catch (e: unknown) {
    const err = e as { message?: string }
    error.value = err.message || 'Failed to load audit logs'
  } finally {
    loading.value = false
  }
}

function getActionBadgeClass(action: string) {
  if (action.includes('login')) return 'badge--blue'
  if (action.includes('logout')) return 'badge--gray'
  if (action.includes('create') || action.includes('add')) return 'badge--green'
  if (action.includes('update') || action.includes('modify')) return 'badge--yellow'
  if (action.includes('delete')) return 'badge--red'
  return 'badge--gray'
}

function formatDate(dateString: string) {
  return new Date(dateString).toLocaleString()
}

function formatChanges(oldValues: Record<string, unknown> | null, newValues: Record<string, unknown> | null) {
  if (!oldValues && !newValues) return '-'
  if (!oldValues) return JSON.stringify(newValues, null, 2)
  if (!newValues) return 'Deleted'
  return `${JSON.stringify(oldValues)} → ${JSON.stringify(newValues)}`
}

onMounted(() => {
  loadLogs()
})
</script>

<template>
  <div class="audit-logs">
    <header class="page-header">
      <div class="page-header__content">
        <h1 class="page-header__title">Audit Logs</h1>
        <p class="page-header__subtitle">Track all system activity and changes</p>
      </div>
      <div class="page-header__actions">
        <span class="total-count">{{ totalLogs }} total logs</span>
      </div>
    </header>

    <!-- Filters -->
    <div class="filters">
      <div class="filter-group">
        <label class="filter-label">Filter by Action</label>
        <input
          v-model="filterAction"
          type="text"
          class="filter-input"
          placeholder="e.g. login, create, update..."
        >
      </div>
      <div class="filter-group">
        <label class="filter-label">Filter by User</label>
        <input
          v-model="filterUser"
          type="text"
          class="filter-input"
          placeholder="e.g. admin@example.com"
        >
      </div>
    </div>

    <div v-if="loading" class="loading-state">
      <div class="spinner"></div>
      <p>Loading audit logs...</p>
    </div>

    <div v-else-if="error" class="error-state">
      <p>{{ error }}</p>
      <button class="btn btn--primary" @click="loadLogs()">Retry</button>
    </div>

    <div v-else-if="filteredLogs.length === 0" class="empty-state">
      <svg class="empty-state__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
        <polyline points="14,2 14,8 20,8" />
        <line x1="16" y1="13" x2="8" y2="13" />
        <line x1="16" y1="17" x2="8" y2="17" />
      </svg>
      <p class="empty-state__title">No audit logs found</p>
      <p class="empty-state__description">System activity will appear here</p>
    </div>

    <template v-else>
      <div class="logs-table-container">
        <table class="logs-table">
          <thead>
            <tr>
              <th>Timestamp</th>
              <th>Action</th>
              <th>Entity</th>
              <th>User</th>
              <th>IP Address</th>
              <th>Details</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="log in filteredLogs" :key="log.id">
              <td class="timestamp">{{ formatDate(log.created_at) }}</td>
              <td>
                <span class="badge" :class="getActionBadgeClass(log.action)">
                  {{ log.action }}
                </span>
              </td>
              <td>
                <span v-if="log.entity_type">
                  {{ log.entity_type }}
                  <span v-if="log.entity_id" class="entity-id">#{{ log.entity_id }}</span>
                </span>
                <span v-else class="text-muted">-</span>
              </td>
              <td>{{ log.user_email }}</td>
              <td class="ip-address">{{ log.ip_address || '-' }}</td>
              <td class="details">
                <details v-if="log.old_values || log.new_values">
                  <summary>View changes</summary>
                  <pre class="changes-pre">{{ formatChanges(log.old_values, log.new_values) }}</pre>
                </details>
                <span v-else class="text-muted">-</span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div v-if="totalPages > 1" class="pagination">
        <button
          class="pagination__btn"
          :disabled="currentPage === 1"
          @click="loadLogs(currentPage - 1)"
        >
          Previous
        </button>
        <span class="pagination__info">
          Page {{ currentPage }} of {{ totalPages }}
        </span>
        <button
          class="pagination__btn"
          :disabled="currentPage === totalPages"
          @click="loadLogs(currentPage + 1)"
        >
          Next
        </button>
      </div>
    </template>
  </div>
</template>

<style scoped>
.audit-logs {
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

.total-count {
  background: #f3f4f6;
  padding: 0.5rem 1rem;
  border-radius: 8px;
  font-size: 0.875rem;
  color: #4b5563;
}

/* Filters */
.filters {
  display: flex;
  gap: 1rem;
  margin-bottom: 1.5rem;
  background: #fff;
  padding: 1rem;
  border-radius: 12px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.filter-group {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.filter-label {
  font-size: 0.75rem;
  font-weight: 500;
  color: #6b7280;
  text-transform: uppercase;
}

.filter-input {
  padding: 0.5rem 0.75rem;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  font-size: 0.875rem;
  min-width: 200px;
}

.filter-input:focus {
  outline: none;
  border-color: #4ade80;
}

/* Table */
.logs-table-container {
  background: #fff;
  border-radius: 12px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  overflow: hidden;
}

.logs-table {
  width: 100%;
  border-collapse: collapse;
}

.logs-table th,
.logs-table td {
  padding: 0.875rem 1rem;
  text-align: left;
  border-bottom: 1px solid #e5e7eb;
}

.logs-table th {
  background: #f9fafb;
  font-weight: 600;
  color: #374151;
  font-size: 0.75rem;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.logs-table td {
  font-size: 0.875rem;
  color: #1f2937;
}

.logs-table tbody tr:hover {
  background-color: #f9fafb;
}

.timestamp {
  white-space: nowrap;
  font-family: monospace;
  font-size: 0.8125rem;
  color: #6b7280;
}

.ip-address {
  font-family: monospace;
  font-size: 0.8125rem;
}

.entity-id {
  color: #6b7280;
  font-size: 0.8125rem;
}

.text-muted {
  color: #9ca3af;
}

/* Badges */
.badge {
  display: inline-block;
  padding: 0.25rem 0.625rem;
  border-radius: 9999px;
  font-size: 0.75rem;
  font-weight: 500;
}

.badge--blue {
  background-color: #dbeafe;
  color: #1e40af;
}

.badge--green {
  background-color: #dcfce7;
  color: #166534;
}

.badge--yellow {
  background-color: #fef3c7;
  color: #92400e;
}

.badge--red {
  background-color: #fee2e2;
  color: #991b1b;
}

.badge--gray {
  background-color: #f3f4f6;
  color: #4b5563;
}

/* Details */
.details details {
  cursor: pointer;
}

.details summary {
  font-size: 0.8125rem;
  color: #4ade80;
  font-weight: 500;
}

.details summary:hover {
  color: #22c55e;
}

.changes-pre {
  margin-top: 0.5rem;
  padding: 0.5rem;
  background: #f9fafb;
  border-radius: 4px;
  font-size: 0.75rem;
  max-width: 300px;
  overflow-x: auto;
  white-space: pre-wrap;
  word-break: break-all;
}

/* Pagination */
.pagination {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 1rem;
  margin-top: 1.5rem;
}

.pagination__btn {
  padding: 0.5rem 1rem;
  background: #fff;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  font-size: 0.875rem;
  cursor: pointer;
  transition: all 0.2s ease;
}

.pagination__btn:hover:not(:disabled) {
  background: #f3f4f6;
}

.pagination__btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.pagination__info {
  font-size: 0.875rem;
  color: #6b7280;
}

/* States */
.loading-state,
.empty-state,
.error-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 4rem;
  text-align: center;
  background: #fff;
  border-radius: 12px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.spinner {
  width: 2rem;
  height: 2rem;
  border: 3px solid #e5e7eb;
  border-top-color: #4ade80;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
  margin-bottom: 1rem;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.empty-state__icon {
  width: 4rem;
  height: 4rem;
  color: #d1d5db;
  margin-bottom: 1rem;
}

.empty-state__title {
  font-size: 1.125rem;
  font-weight: 600;
  color: #374151;
  margin: 0 0 0.25rem;
}

.empty-state__description {
  color: #6b7280;
  margin: 0;
}

.error-state {
  color: #dc2626;
}

.btn {
  padding: 0.625rem 1.25rem;
  border-radius: 8px;
  font-weight: 500;
  cursor: pointer;
  border: none;
}

.btn--primary {
  background-color: #4ade80;
  color: #fff;
}

.btn--primary:hover {
  background-color: #22c55e;
}
</style>
