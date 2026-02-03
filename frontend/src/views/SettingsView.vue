<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { authApi } from '@/api'
import type { User } from '@/types'

// Users state
const users = ref<User[]>([])
const loadingUsers = ref(false)
const usersError = ref('')

// Create user modal state
const showCreateModal = ref(false)
const createForm = ref({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
  role: 'user' as 'admin' | 'user',
})
const createLoading = ref(false)
const createError = ref('')
const createErrors = ref<Record<string, string[]>>({})

// Active tab
const activeTab = ref<'users' | 'api-keys' | 'audit-logs' | 'import-export'>('users')

async function loadUsers() {
  loadingUsers.value = true
  usersError.value = ''
  try {
    users.value = await authApi.getUsers()
  } catch (e: unknown) {
    const err = e as { message?: string }
    usersError.value = err.message || 'Failed to load users'
  } finally {
    loadingUsers.value = false
  }
}

function openCreateModal() {
  createForm.value = {
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    role: 'user',
  }
  createError.value = ''
  createErrors.value = {}
  showCreateModal.value = true
}

function closeCreateModal() {
  showCreateModal.value = false
}

async function handleCreateUser() {
  if (!createForm.value.name || !createForm.value.email || !createForm.value.password) {
    createError.value = 'Please fill in all required fields'
    return
  }

  if (createForm.value.password !== createForm.value.password_confirmation) {
    createError.value = 'Passwords do not match'
    return
  }

  createLoading.value = true
  createError.value = ''
  createErrors.value = {}

  try {
    await authApi.createUser(createForm.value)
    closeCreateModal()
    await loadUsers()
  } catch (e: unknown) {
    const err = e as { message?: string; errors?: Record<string, string[]> }
    createError.value = err.message || 'Failed to create user'
    createErrors.value = err.errors || {}
  } finally {
    createLoading.value = false
  }
}

function getRoleBadgeClass(role: string) {
  switch (role) {
    case 'admin':
      return 'badge--admin'
    default:
      return 'badge--user'
  }
}

onMounted(() => {
  loadUsers()
})
</script>

<template>
  <div class="settings-view">
    <header class="page-header">
      <h1 class="page-header__title">Settings</h1>
      <p class="page-header__subtitle">System configuration and administration</p>
    </header>

    <!-- Tab Navigation -->
    <div class="tabs">
      <button
        class="tab"
        :class="{ 'tab--active': activeTab === 'users' }"
        @click="activeTab = 'users'"
      >
        <svg class="tab__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
          <circle cx="9" cy="7" r="4" />
          <path d="M23 21v-2a4 4 0 0 0-3-3.87" />
          <path d="M16 3.13a4 4 0 0 1 0 7.75" />
        </svg>
        User Management
      </button>
      <button
        class="tab"
        :class="{ 'tab--active': activeTab === 'api-keys' }"
        @click="activeTab = 'api-keys'"
      >
        <svg class="tab__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M21 2l-2 2m-7.61 7.61a5.5 5.5 0 1 1-7.778 7.778 5.5 5.5 0 0 1 7.777-7.777zm0 0L15.5 7.5m0 0l3 3L22 7l-3-3m-3.5 3.5L19 4" />
        </svg>
        API Keys
      </button>
      <button
        class="tab"
        :class="{ 'tab--active': activeTab === 'audit-logs' }"
        @click="activeTab = 'audit-logs'"
      >
        <svg class="tab__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
          <polyline points="14,2 14,8 20,8" />
          <line x1="16" y1="13" x2="8" y2="13" />
          <line x1="16" y1="17" x2="8" y2="17" />
          <polyline points="10,9 9,9 8,9" />
        </svg>
        Audit Logs
      </button>
      <button
        class="tab"
        :class="{ 'tab--active': activeTab === 'import-export' }"
        @click="activeTab = 'import-export'"
      >
        <svg class="tab__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
          <polyline points="17,8 12,3 7,8" />
          <line x1="12" y1="3" x2="12" y2="15" />
        </svg>
        Import/Export
      </button>
    </div>

    <!-- User Management Tab -->
    <div v-if="activeTab === 'users'" class="tab-content">
      <div class="content-header">
        <h2 class="content-header__title">Users</h2>
        <button class="btn btn--primary" @click="openCreateModal">
          <svg class="btn__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="12" y1="5" x2="12" y2="19" />
            <line x1="5" y1="12" x2="19" y2="12" />
          </svg>
          Add User
        </button>
      </div>

      <div v-if="loadingUsers" class="loading-state">
        <div class="spinner"></div>
        <p>Loading users...</p>
      </div>

      <div v-else-if="usersError" class="error-state">
        <p>{{ usersError }}</p>
        <button class="btn btn--secondary" @click="loadUsers">Retry</button>
      </div>

      <div v-else-if="users.length === 0" class="empty-state">
        <svg class="empty-state__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
          <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
          <circle cx="9" cy="7" r="4" />
          <path d="M23 21v-2a4 4 0 0 0-3-3.87" />
          <path d="M16 3.13a4 4 0 0 1 0 7.75" />
        </svg>
        <p class="empty-state__title">No users found</p>
        <p class="empty-state__description">Create the first user to get started</p>
        <button class="btn btn--primary" @click="openCreateModal">Add User</button>
      </div>

      <table v-else class="users-table">
        <thead>
          <tr>
            <th>Name</th>
            <th>Email</th>
            <th>Role</th>
            <th>Created</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="user in users" :key="user.id">
            <td>{{ user.name }}</td>
            <td>{{ user.email }}</td>
            <td>
              <span class="badge" :class="getRoleBadgeClass(user.role)">
                {{ user.role }}
              </span>
            </td>
            <td>{{ new Date(user.created_at).toLocaleDateString() }}</td>
            <td>
              <button class="action-btn" title="Edit user">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" />
                  <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z" />
                </svg>
              </button>
              <button class="action-btn action-btn--danger" title="Delete user">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <polyline points="3,6 5,6 21,6" />
                  <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2" />
                </svg>
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- API Keys Tab -->
    <div v-if="activeTab === 'api-keys'" class="tab-content">
      <div class="placeholder-content">
        <svg class="placeholder-content__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
          <path d="M21 2l-2 2m-7.61 7.61a5.5 5.5 0 1 1-7.778 7.778 5.5 5.5 0 0 1 7.777-7.777zm0 0L15.5 7.5m0 0l3 3L22 7l-3-3m-3.5 3.5L19 4" />
        </svg>
        <h3>API Keys</h3>
        <p>Generate and manage API keys for programmatic access.</p>
        <p class="text-muted">Coming soon...</p>
      </div>
    </div>

    <!-- Audit Logs Tab -->
    <div v-if="activeTab === 'audit-logs'" class="tab-content">
      <div class="placeholder-content">
        <svg class="placeholder-content__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
          <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
          <polyline points="14,2 14,8 20,8" />
          <line x1="16" y1="13" x2="8" y2="13" />
          <line x1="16" y1="17" x2="8" y2="17" />
        </svg>
        <h3>Audit Logs</h3>
        <p>View system activity and change history.</p>
        <p class="text-muted">Coming soon...</p>
      </div>
    </div>

    <!-- Import/Export Tab -->
    <div v-if="activeTab === 'import-export'" class="tab-content">
      <div class="placeholder-content">
        <svg class="placeholder-content__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
          <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
          <polyline points="17,8 12,3 7,8" />
          <line x1="12" y1="3" x2="12" y2="15" />
        </svg>
        <h3>Import / Export</h3>
        <p>Import or export IP address data in CSV or JSON format.</p>
        <p class="text-muted">Coming soon...</p>
      </div>
    </div>

    <!-- Create User Modal -->
    <Teleport to="body">
      <div v-if="showCreateModal" class="modal-overlay" @click.self="closeCreateModal">
        <div class="modal">
          <div class="modal__header">
            <h2 class="modal__title">Create New User</h2>
            <button class="modal__close" @click="closeCreateModal">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="18" y1="6" x2="6" y2="18" />
                <line x1="6" y1="6" x2="18" y2="18" />
              </svg>
            </button>
          </div>

          <form class="modal__body" @submit.prevent="handleCreateUser">
            <div v-if="createError" class="form-error">
              {{ createError }}
            </div>

            <div class="form-group">
              <label for="user-name" class="form-label">Full Name *</label>
              <input
                id="user-name"
                v-model="createForm.name"
                type="text"
                class="form-input"
                :class="{ 'form-input--error': createErrors.name }"
                placeholder="John Doe"
                required
              >
              <span v-if="createErrors.name" class="form-field-error">{{ createErrors.name[0] }}</span>
            </div>

            <div class="form-group">
              <label for="user-email" class="form-label">Email *</label>
              <input
                id="user-email"
                v-model="createForm.email"
                type="email"
                class="form-input"
                :class="{ 'form-input--error': createErrors.email }"
                placeholder="john@example.com"
                required
              >
              <span v-if="createErrors.email" class="form-field-error">{{ createErrors.email[0] }}</span>
            </div>

            <div class="form-group">
              <label for="user-role" class="form-label">Role *</label>
              <select
                id="user-role"
                v-model="createForm.role"
                class="form-input"
                required
              >
                <option value="user">User - Can manage own IP addresses</option>
                <option value="admin">Admin - Full system access (super-admin)</option>
              </select>
            </div>

            <div class="form-group">
              <label for="user-password" class="form-label">Password *</label>
              <input
                id="user-password"
                v-model="createForm.password"
                type="password"
                class="form-input"
                :class="{ 'form-input--error': createErrors.password }"
                placeholder="Minimum 8 characters"
                required
                minlength="8"
              >
              <span v-if="createErrors.password" class="form-field-error">{{ createErrors.password[0] }}</span>
            </div>

            <div class="form-group">
              <label for="user-password-confirm" class="form-label">Confirm Password *</label>
              <input
                id="user-password-confirm"
                v-model="createForm.password_confirmation"
                type="password"
                class="form-input"
                placeholder="Re-enter password"
                required
              >
            </div>

            <div class="modal__footer">
              <button type="button" class="btn btn--secondary" @click="closeCreateModal">
                Cancel
              </button>
              <button type="submit" class="btn btn--primary" :disabled="createLoading">
                {{ createLoading ? 'Creating...' : 'Create User' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<style scoped>
.settings-view {
  max-width: 1200px;
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

/* Tabs */
.tabs {
  display: flex;
  gap: 0.25rem;
  border-bottom: 1px solid #e5e7eb;
  margin-bottom: 1.5rem;
}

.tab {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.75rem 1rem;
  background: none;
  border: none;
  border-bottom: 2px solid transparent;
  color: #6b7280;
  font-size: 0.875rem;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s ease;
}

.tab:hover {
  color: #374151;
}

.tab--active {
  color: #4ade80;
  border-bottom-color: #4ade80;
}

.tab__icon {
  width: 1.125rem;
  height: 1.125rem;
}

/* Tab Content */
.tab-content {
  background: #fff;
  border-radius: 12px;
  padding: 1.5rem;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.content-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.5rem;
}

.content-header__title {
  font-size: 1.25rem;
  font-weight: 600;
  color: #1f2937;
  margin: 0;
}

/* Buttons */
.btn {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.625rem 1.25rem;
  border-radius: 8px;
  font-weight: 500;
  font-size: 0.875rem;
  cursor: pointer;
  border: none;
  transition: all 0.2s ease;
}

.btn__icon {
  width: 1rem;
  height: 1rem;
}

.btn--primary {
  background-color: #4ade80;
  color: #fff;
}

.btn--primary:hover:not(:disabled) {
  background-color: #22c55e;
}

.btn--primary:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.btn--secondary {
  background-color: #f3f4f6;
  color: #374151;
}

.btn--secondary:hover {
  background-color: #e5e7eb;
}

/* Users Table */
.users-table {
  width: 100%;
  border-collapse: collapse;
}

.users-table th,
.users-table td {
  padding: 0.875rem 1rem;
  text-align: left;
  border-bottom: 1px solid #e5e7eb;
}

.users-table th {
  font-weight: 600;
  color: #374151;
  font-size: 0.75rem;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.users-table td {
  color: #1f2937;
  font-size: 0.875rem;
}

.users-table tbody tr:hover {
  background-color: #f9fafb;
}

/* Badges */
.badge {
  display: inline-block;
  padding: 0.25rem 0.75rem;
  border-radius: 9999px;
  font-size: 0.75rem;
  font-weight: 500;
  text-transform: capitalize;
}

.badge--admin {
  background-color: #fef3c7;
  color: #92400e;
}

.badge--user {
  background-color: #dbeafe;
  color: #1e40af;
}

/* Action Buttons */
.action-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 2rem;
  height: 2rem;
  border: none;
  background: none;
  border-radius: 6px;
  color: #6b7280;
  cursor: pointer;
  transition: all 0.2s ease;
}

.action-btn:hover {
  background-color: #f3f4f6;
  color: #374151;
}

.action-btn--danger:hover {
  background-color: #fef2f2;
  color: #dc2626;
}

.action-btn svg {
  width: 1rem;
  height: 1rem;
}

/* Loading & Empty States */
.loading-state,
.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 3rem;
  text-align: center;
  color: #6b7280;
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
  to {
    transform: rotate(360deg);
  }
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
  margin: 0 0 1rem;
}

.error-state {
  color: #dc2626;
}

/* Placeholder Content */
.placeholder-content {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 4rem;
  text-align: center;
}

.placeholder-content__icon {
  width: 4rem;
  height: 4rem;
  color: #d1d5db;
  margin-bottom: 1rem;
}

.placeholder-content h3 {
  font-size: 1.25rem;
  font-weight: 600;
  color: #374151;
  margin: 0 0 0.5rem;
}

.placeholder-content p {
  color: #6b7280;
  margin: 0;
}

.text-muted {
  color: #9ca3af !important;
  margin-top: 0.5rem !important;
}

/* Modal */
.modal-overlay {
  position: fixed;
  inset: 0;
  background-color: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  padding: 1rem;
}

.modal {
  background: #fff;
  border-radius: 12px;
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
  width: 100%;
  max-width: 480px;
  max-height: 90vh;
  overflow-y: auto;
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
  display: flex;
  align-items: center;
  justify-content: center;
  width: 2rem;
  height: 2rem;
  border: none;
  background: none;
  border-radius: 6px;
  color: #6b7280;
  cursor: pointer;
  transition: all 0.2s ease;
}

.modal__close:hover {
  background-color: #f3f4f6;
  color: #374151;
}

.modal__close svg {
  width: 1.25rem;
  height: 1.25rem;
}

.modal__body {
  padding: 1.5rem;
}

.modal__footer {
  display: flex;
  justify-content: flex-end;
  gap: 0.75rem;
  margin-top: 1.5rem;
  padding-top: 1rem;
  border-top: 1px solid #e5e7eb;
}

/* Form */
.form-group {
  margin-bottom: 1rem;
}

.form-label {
  display: block;
  font-size: 0.875rem;
  font-weight: 500;
  color: #374151;
  margin-bottom: 0.375rem;
}

.form-input {
  width: 100%;
  padding: 0.625rem 0.875rem;
  border: 1px solid #d1d5db;
  border-radius: 8px;
  font-size: 0.875rem;
  transition: all 0.2s ease;
}

.form-input:focus {
  outline: none;
  border-color: #4ade80;
  box-shadow: 0 0 0 3px rgba(74, 222, 128, 0.1);
}

.form-input--error {
  border-color: #ef4444;
}

.form-input--error:focus {
  border-color: #ef4444;
  box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1);
}

.form-error {
  background-color: #fef2f2;
  border: 1px solid #fecaca;
  color: #dc2626;
  padding: 0.75rem 1rem;
  border-radius: 8px;
  font-size: 0.875rem;
  margin-bottom: 1rem;
}

.form-field-error {
  display: block;
  font-size: 0.75rem;
  color: #ef4444;
  margin-top: 0.25rem;
}

select.form-input {
  cursor: pointer;
}
</style>
