<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { apiClient, USE_MOCK_API, mockApi } from '@/api'
import { useAuthStore } from '@/stores'
import type { DashboardStats } from '@/types'

const authStore = useAuthStore()
const stats = ref<DashboardStats | null>(null)
const loading = ref(true)
const error = ref('')

onMounted(async () => {
  try {
    if (USE_MOCK_API) {
      stats.value = await mockApi.getDashboardStats()
    } else {
      stats.value = await apiClient.get<DashboardStats>('/dashboard/stats')
    }
  } catch (e: unknown) {
    const err = e as { message?: string }
    error.value = err.message || 'Failed to load dashboard stats'
  } finally {
    loading.value = false
  }
})

function formatDate(dateString: string) {
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

function getActionBadgeClass(action: string) {
  switch (action) {
    case 'create': return 'badge--success'
    case 'update': return 'badge--warning'
    case 'delete': return 'badge--danger'
    default: return 'badge--info'
  }
}

function getGreeting() {
  const hour = new Date().getHours()
  if (hour < 12) return 'Good morning'
  if (hour < 18) return 'Good afternoon'
  return 'Good evening'
}
</script>

<template>
  <div class="dashboard">
    <!-- Header -->
    <header class="dashboard__header">
      <div class="dashboard__welcome">
        <h1 class="dashboard__title">{{ getGreeting() }}, {{ authStore.user?.name?.split(' ')[0] }}!</h1>
        <p class="dashboard__subtitle">Here's what's happening with your IP addresses today.</p>
      </div>
    </header>

    <!-- Loading State -->
    <div v-if="loading" class="dashboard__loading">
      <div class="loading-spinner">
        <svg class="loading-spinner__svg" viewBox="0 0 50 50">
          <circle class="loading-spinner__circle" cx="25" cy="25" r="20" fill="none" stroke-width="4"></circle>
        </svg>
      </div>
      <p>Loading dashboard...</p>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="dashboard__error">
      <svg class="dashboard__error-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/>
      </svg>
      <p>{{ error }}</p>
    </div>

    <template v-else>
      <!-- Stats Cards -->
      <div class="stats-grid">
        <div class="stat-card stat-card--primary">
          <div class="stat-card__icon">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
              <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 17.93c-3.95-.49-7-3.85-7-7.93 0-.62.08-1.21.21-1.79L9 15v1c0 1.1.9 2 2 2v1.93zm6.9-2.54c-.26-.81-1-1.39-1.9-1.39h-1v-3c0-.55-.45-1-1-1H8v-2h2c.55 0 1-.45 1-1V7h2c1.1 0 2-.9 2-2v-.41c2.93 1.19 5 4.06 5 7.41 0 2.08-.8 3.97-2.1 5.39z"/>
            </svg>
          </div>
          <div class="stat-card__content">
            <div class="stat-card__value">{{ stats?.total_ips || 0 }}</div>
            <div class="stat-card__label">Total IP Addresses</div>
          </div>
        </div>

        <div class="stat-card stat-card--secondary">
          <div class="stat-card__icon">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
              <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
            </svg>
          </div>
          <div class="stat-card__content">
            <div class="stat-card__value">{{ stats?.my_ips || 0 }}</div>
            <div class="stat-card__label">My IP Addresses</div>
          </div>
        </div>
      </div>

      <!-- Recent Activity -->
      <div class="card">
        <div class="card__header">
          <h2 class="card__title">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
              <path d="M13 3c-4.97 0-9 4.03-9 9H1l3.89 3.89.07.14L9 12H6c0-3.87 3.13-7 7-7s7 3.13 7 7-3.13 7-7 7c-1.93 0-3.68-.79-4.94-2.06l-1.42 1.42C8.27 19.99 10.51 21 13 21c4.97 0 9-4.03 9-9s-4.03-9-9-9zm-1 5v5l4.28 2.54.72-1.21-3.5-2.08V8H12z"/>
            </svg>
            Recent Activity
          </h2>
        </div>
        <div class="card__content">
          <div v-if="!stats?.recent_activity?.length" class="card__empty">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
              <path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V5h14v14z"/>
            </svg>
            <p>No recent activity</p>
          </div>
          
          <!-- Desktop Table -->
          <div v-else class="table-responsive">
            <table class="table">
              <thead>
                <tr>
                  <th>Action</th>
                  <th>IP Address</th>
                  <th>Label</th>
                  <th class="hide-mobile">User</th>
                  <th>Date</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="activity in stats?.recent_activity" :key="activity.id">
                  <td>
                    <span class="badge" :class="getActionBadgeClass(activity.action)">
                      {{ activity.action }}
                    </span>
                  </td>
                  <td>
                    <code class="ip-code">{{ activity.ip_address }}</code>
                  </td>
                  <td class="text-truncate">{{ activity.label }}</td>
                  <td class="hide-mobile text-muted">{{ activity.user_name }}</td>
                  <td class="text-muted text-nowrap">{{ formatDate(activity.created_at) }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </template>
  </div>
</template>

<style scoped>
.dashboard {
  max-width: 1400px;
  margin: 0 auto;
}

.dashboard__header {
  margin-bottom: 2rem;
}

.dashboard__welcome {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.dashboard__title {
  font-size: 1.75rem;
  font-weight: 700;
  color: #0f172a;
  margin: 0;
  letter-spacing: -0.025em;
}

.dashboard__subtitle {
  color: #64748b;
  margin: 0;
  font-size: 0.9375rem;
}

/* Loading State */
.dashboard__loading {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 4rem 2rem;
  background: #fff;
  border-radius: 16px;
  color: #64748b;
  gap: 1rem;
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

/* Error State */
.dashboard__error {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 4rem 2rem;
  background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
  border-radius: 16px;
  color: #dc2626;
  gap: 1rem;
  text-align: center;
}

.dashboard__error-icon {
  width: 48px;
  height: 48px;
  opacity: 0.8;
}

/* Stats Grid */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 1.5rem;
  margin-bottom: 2rem;
}

.stat-card {
  background: #fff;
  border-radius: 16px;
  padding: 1.5rem;
  display: flex;
  align-items: center;
  gap: 1.25rem;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05), 0 1px 2px rgba(0, 0, 0, 0.1);
  transition: all 0.2s ease;
  border: 1px solid #f1f5f9;
}

.stat-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
}

.stat-card__icon {
  width: 56px;
  height: 56px;
  border-radius: 14px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.stat-card__icon svg {
  width: 28px;
  height: 28px;
}

.stat-card--primary .stat-card__icon {
  background: linear-gradient(135deg, #dcfce7 0%, #bbf7d0 100%);
  color: #16a34a;
}

.stat-card--secondary .stat-card__icon {
  background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
  color: #2563eb;
}

.stat-card__content {
  flex: 1;
}

.stat-card__value {
  font-size: 2rem;
  font-weight: 700;
  color: #0f172a;
  line-height: 1.2;
}

.stat-card__label {
  font-size: 0.875rem;
  color: #64748b;
  margin-top: 0.25rem;
}

/* Card */
.card {
  background: #fff;
  border-radius: 16px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05), 0 1px 2px rgba(0, 0, 0, 0.1);
  border: 1px solid #f1f5f9;
  overflow: hidden;
}

.card__header {
  padding: 1.25rem 1.5rem;
  border-bottom: 1px solid #f1f5f9;
}

.card__title {
  font-size: 1rem;
  font-weight: 600;
  color: #0f172a;
  margin: 0;
  display: flex;
  align-items: center;
  gap: 0.625rem;
}

.card__title svg {
  width: 20px;
  height: 20px;
  color: #64748b;
}

.card__content {
  padding: 0;
}

.card__empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 3rem;
  color: #94a3b8;
  gap: 0.75rem;
}

.card__empty svg {
  width: 48px;
  height: 48px;
  opacity: 0.5;
}

/* Table */
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
  font-weight: 600;
  color: #64748b;
  font-size: 0.75rem;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  background: #f8fafc;
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

.ip-code {
  background-color: #f1f5f9;
  padding: 0.375rem 0.625rem;
  border-radius: 6px;
  font-size: 0.8125rem;
  font-family: 'SF Mono', 'Menlo', 'Monaco', 'Courier New', monospace;
  color: #0f172a;
}

/* Badges */
.badge {
  display: inline-flex;
  align-items: center;
  padding: 0.25rem 0.625rem;
  border-radius: 9999px;
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: capitalize;
}

.badge--success {
  background-color: #dcfce7;
  color: #166534;
}

.badge--warning {
  background-color: #fef3c7;
  color: #92400e;
}

.badge--danger {
  background-color: #fee2e2;
  color: #991b1b;
}

.badge--info {
  background-color: #dbeafe;
  color: #1e40af;
}

/* Utility Classes */
.text-truncate {
  max-width: 200px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.text-muted {
  color: #64748b;
}

.text-nowrap {
  white-space: nowrap;
}

/* Responsive */
@media (max-width: 768px) {
  .dashboard__title {
    font-size: 1.5rem;
  }

  .stats-grid {
    grid-template-columns: 1fr;
    gap: 1rem;
  }

  .stat-card {
    padding: 1.25rem;
  }

  .stat-card__icon {
    width: 48px;
    height: 48px;
  }

  .stat-card__icon svg {
    width: 24px;
    height: 24px;
  }

  .stat-card__value {
    font-size: 1.5rem;
  }

  .table th,
  .table td {
    padding: 0.875rem 1rem;
  }

  .hide-mobile {
    display: none;
  }

  .text-truncate {
    max-width: 120px;
  }
}

@media (max-width: 480px) {
  .dashboard__title {
    font-size: 1.25rem;
  }

  .dashboard__subtitle {
    font-size: 0.875rem;
  }

  .card__header {
    padding: 1rem;
  }

  .table th,
  .table td {
    padding: 0.75rem;
    font-size: 0.8125rem;
  }

  .ip-code {
    font-size: 0.75rem;
    padding: 0.25rem 0.5rem;
  }
}
</style>
