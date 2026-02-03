<script setup lang="ts">
import { onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useSubnetsStore, useIPAddressesStore } from '@/stores'
import { subnetsApi } from '@/api'

const route = useRoute()
const router = useRouter()
const subnetsStore = useSubnetsStore()
const ipStore = useIPAddressesStore()

const subnetId = Number(route.params.id)

onMounted(async () => {
  await subnetsStore.fetchSubnet(subnetId)
  await ipStore.fetchIPAddresses({ subnet_id: subnetId })
})

function goBack() {
  router.push('/subnets')
}

async function scanSubnet() {
  try {
    await subnetsApi.scan(subnetId)
    alert('Scan initiated. Results will appear shortly.')
  } catch {
    alert('Failed to start scan')
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
  <div class="subnet-detail">
    <header class="page-header">
      <div class="page-header__back">
        <button class="btn btn--icon" @click="goBack">←</button>
        <div>
          <h1 class="page-header__title">{{ subnetsStore.currentSubnet?.name || 'Loading...' }}</h1>
          <p class="page-header__subtitle">
            <code>{{ subnetsStore.currentSubnet?.network }}/{{ subnetsStore.currentSubnet?.cidr }}</code>
          </p>
        </div>
      </div>
      <div class="page-header__actions">
        <button class="btn btn--secondary" @click="scanSubnet">Scan Network</button>
        <button class="btn btn--primary">+ Assign IP</button>
      </div>
    </header>

    <!-- Subnet Info -->
    <div v-if="subnetsStore.currentSubnet" class="info-grid">
      <div class="info-card">
        <div class="info-card__label">Total Addresses</div>
        <div class="info-card__value">{{ subnetsStore.currentSubnet.total_addresses }}</div>
      </div>
      <div class="info-card">
        <div class="info-card__label">Used</div>
        <div class="info-card__value">{{ subnetsStore.currentSubnet.used_addresses }}</div>
      </div>
      <div class="info-card">
        <div class="info-card__label">Available</div>
        <div class="info-card__value">{{ subnetsStore.currentSubnet.available_addresses }}</div>
      </div>
      <div class="info-card">
        <div class="info-card__label">Utilization</div>
        <div class="info-card__value">{{ subnetsStore.currentSubnet.utilization_percent }}%</div>
      </div>
      <div class="info-card">
        <div class="info-card__label">Gateway</div>
        <div class="info-card__value">
          <code>{{ subnetsStore.currentSubnet.gateway || 'Not set' }}</code>
        </div>
      </div>
      <div class="info-card">
        <div class="info-card__label">VLAN</div>
        <div class="info-card__value">{{ subnetsStore.currentSubnet.vlan_id || 'Not set' }}</div>
      </div>
    </div>

    <!-- IP Addresses Table -->
    <div class="section">
      <h2 class="section__title">IP Addresses</h2>
      
      <div v-if="ipStore.loading" class="loading">Loading IP addresses...</div>
      
      <div v-else class="table-container">
        <table class="table">
          <thead>
            <tr>
              <th>IP Address</th>
              <th>Hostname</th>
              <th>Status</th>
              <th>Assigned To</th>
              <th>MAC Address</th>
              <th>Actions</th>
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
              <td>
                <button v-if="ip.status === 'available'" class="btn btn--small btn--primary">
                  Assign
                </button>
                <button v-else-if="ip.status === 'assigned'" class="btn btn--small btn--secondary">
                  Release
                </button>
              </td>
            </tr>
          </tbody>
        </table>

        <div v-if="!ipStore.ipAddresses.length" class="empty-state">
          No IP addresses found in this subnet
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.subnet-detail {
  max-width: 1400px;
  margin: 0 auto;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 1.5rem;
}

.page-header__back {
  display: flex;
  align-items: flex-start;
  gap: 1rem;
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

.page-header__actions {
  display: flex;
  gap: 0.5rem;
}

.info-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
  gap: 1rem;
  margin-bottom: 2rem;
}

.info-card {
  background: #fff;
  border-radius: 8px;
  padding: 1rem;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.info-card__label {
  font-size: 0.75rem;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  color: #6b7280;
  margin-bottom: 0.25rem;
}

.info-card__value {
  font-size: 1.25rem;
  font-weight: 600;
  color: #1f2937;
}

.section {
  margin-top: 2rem;
}

.section__title {
  font-size: 1.25rem;
  font-weight: 600;
  color: #1f2937;
  margin: 0 0 1rem;
}

.loading {
  text-align: center;
  padding: 2rem;
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

.btn {
  padding: 0.625rem 1.25rem;
  border-radius: 8px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.15s ease;
  border: none;
}

.btn--icon {
  padding: 0.5rem 0.75rem;
  background-color: #f3f4f6;
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

.btn--small {
  padding: 0.375rem 0.75rem;
  font-size: 0.875rem;
}
</style>
