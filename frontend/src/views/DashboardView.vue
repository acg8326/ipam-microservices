<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { apiClient, USE_MOCK_API, mockApi } from '@/api'
import type { DashboardStats } from '@/types'

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
</script>

<template>
  <div class="dashboard">
    <header class="dashboard__header">
      <h1 class="dashboard__title">Dashboard</h1>
      <p class="dashboard__subtitle">IP Address Management Overview</p>
    </header>

    <div v-if="loading" class="dashboard__loading">
      Loading dashboard...
    </div>

    <div v-else-if="error" class="dashboard__error">
      {{ error }}
    </div>

    <template v-else>
      <!-- Stats Cards -->
      <div class="stats-grid">
        <div class="stat-card">
          <div class="stat-card__icon stat-card__icon--blue">S</div>
          <div class="stat-card__content">
            <div class="stat-card__value">{{ stats?.total_subnets || 0 }}</div>
            <div class="stat-card__label">Total Subnets</div>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-card__icon stat-card__icon--green">IP</div>
          <div class="stat-card__content">
            <div class="stat-card__value">{{ stats?.total_ips || 0 }}</div>
            <div class="stat-card__label">Total IPs</div>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-card__icon stat-card__icon--yellow">A</div>
          <div class="stat-card__content">
            <div class="stat-card__value">{{ stats?.assigned_ips || 0 }}</div>
            <div class="stat-card__label">Assigned IPs</div>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-card__icon stat-card__icon--purple">%</div>
          <div class="stat-card__content">
            <div class="stat-card__value">{{ stats?.utilization_percent || 0 }}%</div>
            <div class="stat-card__label">Utilization</div>
          </div>
        </div>
      </div>

      <!-- Main Content Grid -->
      <div class="dashboard__grid">
        <!-- Recent Assignments -->
        <div class="card">
          <h2 class="card__title">Recent Assignments</h2>
          <div class="card__content">
            <div v-if="!stats?.recent_assignments?.length" class="card__empty">
              No recent assignments
            </div>
            <table v-else class="table">
              <thead>
                <tr>
                  <th>IP Address</th>
                  <th>Hostname</th>
                  <th>Assigned To</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="ip in stats?.recent_assignments" :key="ip.id">
                  <td>
                    <code>{{ ip.ip_address }}</code>
                  </td>
                  <td>{{ ip.hostname || '-' }}</td>
                  <td>{{ ip.assigned_to || '-' }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Subnet Utilization -->
        <div class="card">
          <h2 class="card__title">Subnet Utilization</h2>
          <div class="card__content">
            <div v-if="!stats?.subnet_utilization?.length" class="card__empty">
              No subnets configured
            </div>
            <div v-else class="utilization-list">
              <div
                v-for="subnet in stats?.subnet_utilization"
                :key="subnet.subnet_id"
                class="utilization-item"
              >
                <div class="utilization-item__header">
                  <span class="utilization-item__name">{{ subnet.subnet_name }}</span>
                  <span class="utilization-item__percent">{{ subnet.utilization_percent }}%</span>
                </div>
                <div class="utilization-item__bar">
                  <div
                    class="utilization-item__fill"
                    :style="{ width: `${subnet.utilization_percent}%` }"
                    :class="{
                      'utilization-item__fill--warning': subnet.utilization_percent > 70,
                      'utilization-item__fill--danger': subnet.utilization_percent > 90,
                    }"
                  ></div>
                </div>
                <div class="utilization-item__network">{{ subnet.network }}</div>
              </div>
            </div>
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

.utilization-list {
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
}

.utilization-item__header {
  display: flex;
  justify-content: space-between;
  margin-bottom: 0.5rem;
}

.utilization-item__name {
  font-weight: 500;
  color: #1f2937;
}

.utilization-item__percent {
  font-weight: 600;
  color: #4b5563;
}

.utilization-item__bar {
  height: 8px;
  background-color: #e5e7eb;
  border-radius: 4px;
  overflow: hidden;
}

.utilization-item__fill {
  height: 100%;
  background-color: #4ade80;
  border-radius: 4px;
  transition: width 0.3s ease;
}

.utilization-item__fill--warning {
  background-color: #fbbf24;
}

.utilization-item__fill--danger {
  background-color: #ef4444;
}

.utilization-item__network {
  font-size: 0.75rem;
  color: #9ca3af;
  margin-top: 0.25rem;
}
</style>
