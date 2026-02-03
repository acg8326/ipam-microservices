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
    case 'create': return 'badge--green'
    case 'update': return 'badge--yellow'
    case 'delete': return 'badge--red'
    default: return 'badge--blue'
  }
}
</script>

<template>
  <div class="dashboard">
    <header class="dashboard__header">
      <h1 class="dashboard__title">Dashboard</h1>
      <p class="dashboard__subtitle">Welcome back, {{ authStore.user?.name }}</p>
    </header>

    <div v-if="loading" class="dashboard__loading">
      <div class="spinner"></div>
      <p>Loading dashboard...</p>
    </div>

    <div v-else-if="error" class="dashboard__error">
      {{ error }}
    </div>

    <template v-else>
      <!-- Stats Cards -->
      <div class="stats-grid">
        <div class="stat-card">
          <div class="stat-card__icon stat-card__icon--green">IP</div>
          <div class="stat-card__content">
            <div class="stat-card__value">{{ stats?.total_ips || 0 }}</div>
            <div class="stat-card__label">Total IP Addresses</div>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-card__icon stat-card__icon--blue">My</div>
          <div class="stat-card__content">
            <div class="stat-card__value">{{ stats?.my_ips || 0 }}</div>
            <div class="stat-card__label">My IP Addresses</div>
          </div>
        </div>
      </div>

      <!-- Recent Activity -->
      <div class="card">
        <h2 class="card__title">Recent Activity</h2>
        <div class="card__content">
          <div v-if="!stats?.recent_activity?.length" class="card__empty">
            No recent activity
          </div>
          <table v-else class="table">
            <thead>
              <tr>
                <th>Action</th>
                <th>IP Address</th>
                <th>Label</th>
                <th>User</th>
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
                <td>{{ activity.label }}</td>
                <td>{{ activity.user_name }}</td>
                <td class="date">{{ formatDate(activity.created_at) }}</td>
              </tr>
            </tbody>
          </table>
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

.dashboard__title {
  font-size: 1.875rem;
  font-weight: bold;
  color: #1f2937;
  margin: 0 0 0.25rem;
}

.dashboard__subtitle {
  color: #6b7280;
  margin: 0;
}

.dashboard__loading,
.dashboard__error {
  text-align: center;
  padding: 3rem;
  background: #fff;
  border-radius: 12px;
  color: #6b7280;
}

.dashboard__error {
  background-color: #fef2f2;
  color: #dc2626;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
  gap: 1.5rem;
  margin-bottom: 2rem;
}

.stat-card {
  background: #fff;
  border-radius: 12px;
  padding: 1.5rem;
  display: flex;
  align-items: center;
  gap: 1rem;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.stat-card__icon {
  width: 48px;
  height: 48px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: bold;
  font-size: 1rem;
}

.stat-card__icon--blue {
  background-color: #dbeafe;
  color: #2563eb;
}

.stat-card__icon--green {
  background-color: #dcfce7;
  color: #16a34a;
}

.stat-card__icon--yellow {
  background-color: #fef3c7;
  color: #d97706;
}

.stat-card__icon--purple {
  background-color: #f3e8ff;
  color: #9333ea;
}

.stat-card__value {
  font-size: 1.5rem;
  font-weight: bold;
  color: #1f2937;
}

.stat-card__label {
  font-size: 0.875rem;
  color: #6b7280;
}

.dashboard__grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
  gap: 1.5rem;
}

.card {
  background: #fff;
  border-radius: 12px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.card__title {
  font-size: 1.125rem;
  font-weight: 600;
  color: #1f2937;
  padding: 1.25rem 1.5rem;
  border-bottom: 1px solid #e5e7eb;
  margin: 0;
}

.card__content {
  padding: 1.5rem;
}

.card__empty {
  text-align: center;
  color: #9ca3af;
  padding: 2rem;
}

.table {
  width: 100%;
  border-collapse: collapse;
}

.table th,
.table td {
  text-align: left;
  padding: 0.75rem;
  border-bottom: 1px solid #f3f4f6;
}

.table th {
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

.badge {
  padding: 0.25rem 0.5rem;
  border-radius: 9999px;
  font-size: 0.75rem;
  font-weight: 500;
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

.badge--blue {
  background-color: #dbeafe;
  color: #1e40af;
}
</style>
