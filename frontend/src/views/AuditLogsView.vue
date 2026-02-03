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
        <!-- Desktop Table -->
        <div class="table-responsive">
          <table class="logs-table">
            <thead>
              <tr>
                <th>Timestamp</th>
                <th>Action</th>
                <th>Entity</th>
                <th class="hide-tablet">User</th>
                <th class="hide-tablet">IP Address</th>
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
                <td class="hide-tablet">{{ log.user_email }}</td>
                <td class="hide-tablet"><span class="ip-address">{{ log.ip_address || '-' }}</span></td>
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

        <!-- Mobile Card View -->
        <div class="mobile-logs">
          <div v-for="log in filteredLogs" :key="'mobile-' + log.id" class="mobile-log-card">
            <div class="mobile-log-card__header">
              <span class="badge" :class="getActionBadgeClass(log.action)">{{ log.action }}</span>
              <span class="mobile-log-card__timestamp">{{ formatDate(log.created_at) }}</span>
            </div>
            <div class="mobile-log-card__body">
              <div class="mobile-log-card__row">
                <span class="mobile-log-card__label">User</span>
                <span class="mobile-log-card__value">{{ log.user_email }}</span>
              </div>
              <div v-if="log.entity_type" class="mobile-log-card__row">
                <span class="mobile-log-card__label">Entity</span>
                <span class="mobile-log-card__value">{{ log.entity_type }} <span v-if="log.entity_id" class="entity-id">#{{ log.entity_id }}</span></span>
              </div>
              <div v-if="log.ip_address" class="mobile-log-card__row">
                <span class="mobile-log-card__label">IP</span>
                <span class="mobile-log-card__value"><span class="ip-address">{{ log.ip_address }}</span></span>
              </div>
              <details v-if="log.old_values || log.new_values" style="margin-top: 0.75rem;">
                <summary style="font-size: 0.8125rem; color: #22c55e; font-weight: 600; cursor: pointer;">View changes</summary>
                <pre class="changes-pre">{{ formatChanges(log.old_values, log.new_values) }}</pre>
              </details>
            </div>
          </div>
        </div>
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
  margin-bottom: 2rem;
  gap: 1rem;
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

.total-count {
  background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
  padding: 0.625rem 1.25rem;
  border-radius: 10px;
  font-size: 0.875rem;
  color: #475569;
  font-weight: 600;
  border: 1px solid #e2e8f0;
}

/* Filters */
.filters {
  display: flex;
  gap: 1rem;
  margin-bottom: 1.5rem;
  background: #fff;
  padding: 1.25rem;
  border-radius: 16px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05), 0 1px 2px rgba(0, 0, 0, 0.1);
  border: 1px solid #f1f5f9;
  flex-wrap: wrap;
}

.filter-group {
  display: flex;
  flex-direction: column;
  gap: 0.375rem;
  flex: 1;
  min-width: 200px;
}

.filter-label {
  font-size: 0.75rem;
  font-weight: 600;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.filter-input {
  padding: 0.75rem 1rem;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  font-size: 0.9375rem;
  transition: all 0.2s ease;
}

.filter-input:focus {
  outline: none;
  border-color: #4ade80;
  box-shadow: 0 0 0 3px rgba(74, 222, 128, 0.15);
}

.filter-input::placeholder {
  color: #94a3b8;
}

/* Table */
.logs-table-container {
  background: #fff;
  border-radius: 16px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05), 0 1px 2px rgba(0, 0, 0, 0.1);
  border: 1px solid #f1f5f9;
  overflow: hidden;
}

.table-responsive {
  overflow-x: auto;
}

.logs-table {
  width: 100%;
  border-collapse: collapse;
}

.logs-table th,
.logs-table td {
  padding: 1rem 1.25rem;
  text-align: left;
  border-bottom: 1px solid #f1f5f9;
}

.logs-table th {
  background: #f8fafc;
  font-weight: 600;
  color: #64748b;
  font-size: 0.75rem;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.logs-table td {
  font-size: 0.875rem;
  color: #0f172a;
}

.logs-table tbody tr {
  transition: background-color 0.15s ease;
}

.logs-table tbody tr:hover {
  background-color: #f8fafc;
}

.logs-table tbody tr:last-child td {
  border-bottom: none;
}

.timestamp {
  white-space: nowrap;
  font-family: 'SF Mono', 'Menlo', 'Monaco', 'Courier New', monospace;
  font-size: 0.8125rem;
  color: #64748b;
}

.ip-address {
  font-family: 'SF Mono', 'Menlo', 'Monaco', 'Courier New', monospace;
  font-size: 0.8125rem;
  background: #f1f5f9;
  padding: 0.25rem 0.5rem;
  border-radius: 4px;
}

.entity-id {
  color: #64748b;
  font-size: 0.8125rem;
}

.text-muted {
  color: #94a3b8;
}

/* Badges */
.badge {
  display: inline-flex;
  align-items: center;
  padding: 0.25rem 0.625rem;
  border-radius: 9999px;
  font-size: 0.75rem;
  font-weight: 600;
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
  background-color: #f1f5f9;
  color: #475569;
}

/* Details */
.details details {
  cursor: pointer;
}

.details summary {
  font-size: 0.8125rem;
  color: #22c55e;
  font-weight: 600;
  transition: color 0.15s ease;
}

.details summary:hover {
  color: #16a34a;
}

.changes-pre {
  margin-top: 0.5rem;
  padding: 0.75rem;
  background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
  border-radius: 8px;
  font-size: 0.75rem;
  max-width: 300px;
  overflow-x: auto;
  white-space: pre-wrap;
  word-break: break-all;
  font-family: 'SF Mono', 'Menlo', 'Monaco', 'Courier New', monospace;
  border: 1px solid #e2e8f0;
}

/* Pagination */
.pagination {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 1rem;
  margin-top: 1.5rem;
  padding: 1rem;
  background: #fff;
  border-radius: 12px;
  border: 1px solid #f1f5f9;
}

.pagination__btn {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.625rem 1rem;
  background: #f1f5f9;
  border: none;
  border-radius: 8px;
  font-size: 0.875rem;
  font-weight: 600;
  color: #475569;
  cursor: pointer;
  transition: all 0.2s ease;
}

.pagination__btn:hover:not(:disabled) {
  background: #e2e8f0;
}

.pagination__btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.pagination__info {
  font-size: 0.875rem;
  color: #64748b;
}

/* States */
.loading-state,
.empty-state,
.error-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 4rem 2rem;
  text-align: center;
  background: #fff;
  border-radius: 16px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05), 0 1px 2px rgba(0, 0, 0, 0.1);
  border: 1px solid #f1f5f9;
  gap: 0.5rem;
}

.loading-state {
  gap: 1rem;
}

.spinner {
  width: 48px;
  height: 48px;
  border: 3px solid #f1f5f9;
  border-top-color: #4ade80;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.empty-state__icon {
  width: 56px;
  height: 56px;
  color: #cbd5e1;
  margin-bottom: 0.5rem;
}

.empty-state__title {
  font-size: 1.125rem;
  font-weight: 700;
  color: #0f172a;
  margin: 0;
}

.empty-state__description {
  color: #64748b;
  margin: 0;
  font-size: 0.9375rem;
}

.error-state {
  background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
  color: #dc2626;
}

.btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  padding: 0.75rem 1.25rem;
  border-radius: 10px;
  font-weight: 600;
  cursor: pointer;
  border: none;
  font-size: 0.875rem;
  margin-top: 1rem;
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

/* Mobile Cards */
.mobile-logs {
  display: none;
}

.mobile-log-card {
  background: #fff;
  border: 1px solid #f1f5f9;
  border-radius: 16px;
  padding: 1.25rem;
  margin-bottom: 1rem;
}

.mobile-log-card__header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 0.75rem;
  margin-bottom: 0.75rem;
}

.mobile-log-card__timestamp {
  font-size: 0.75rem;
  color: #64748b;
  font-family: 'SF Mono', 'Menlo', monospace;
}

.mobile-log-card__body {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.mobile-log-card__row {
  display: flex;
  justify-content: space-between;
  font-size: 0.875rem;
}

.mobile-log-card__label {
  color: #64748b;
  font-weight: 500;
}

.mobile-log-card__value {
  color: #0f172a;
  text-align: right;
}

/* Responsive */
@media (max-width: 1024px) {
  .hide-tablet {
    display: none;
  }
}

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

  .filter-group {
    min-width: auto;
  }

  /* Hide table, show mobile cards */
  .table-responsive {
    display: none;
  }

  .mobile-logs {
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
}

@media (max-width: 480px) {
  .page-header__title {
    font-size: 1.25rem;
  }

  .total-count {
    width: 100%;
    text-align: center;
  }
}
</style>
