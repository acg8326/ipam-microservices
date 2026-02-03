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
      <p class="page-header__subtitle">User management and administration</p>
    </header>

    <!-- User Management -->
    <div class="content-card">
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
        <button class="btn btn--primary" @click="openCreateModal">
          <svg class="btn__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="12" y1="5" x2="12" y2="19" />
            <line x1="5" y1="12" x2="19" y2="12" />
          </svg>
          Add User
        </button>
      </div>

      <template v-else>
        <!-- Desktop Table -->
        <div class="table-responsive">
          <table class="users-table">
            <thead>
              <tr>
                <th>Name</th>
                <th>Email</th>
                <th>Role</th>
                <th>Created</th>
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
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Mobile Card View -->
        <div class="mobile-users">
          <div v-for="user in users" :key="'mobile-' + user.id" class="mobile-user-card">
            <div class="mobile-user-card__header">
              <span class="mobile-user-card__name">{{ user.name }}</span>
              <span class="badge" :class="getRoleBadgeClass(user.role)">{{ user.role }}</span>
            </div>
            <div class="mobile-user-card__email">{{ user.email }}</div>
            <div class="mobile-user-card__date">Joined {{ new Date(user.created_at).toLocaleDateString() }}</div>
          </div>
        </div>
      </template>
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
                <option value="admin">Admin - Full system access</option>
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
  margin-bottom: 2rem;
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

/* Content Card */
.content-card {
  background: #fff;
  border-radius: 16px;
  padding: 1.5rem;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05), 0 1px 2px rgba(0, 0, 0, 0.1);
  border: 1px solid #f1f5f9;
}

.content-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.5rem;
  gap: 1rem;
  flex-wrap: wrap;
}

.content-header__title {
  font-size: 1.25rem;
  font-weight: 700;
  color: #0f172a;
  margin: 0;
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.content-header__title::before {
  content: '';
  display: block;
  width: 4px;
  height: 24px;
  background: linear-gradient(135deg, #4ade80 0%, #22c55e 100%);
  border-radius: 2px;
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
  font-size: 0.875rem;
  cursor: pointer;
  border: none;
  transition: all 0.2s ease;
}

.btn__icon {
  width: 18px;
  height: 18px;
}

.btn--primary {
  background: linear-gradient(135deg, #4ade80 0%, #22c55e 100%);
  color: #fff;
  box-shadow: 0 2px 8px rgba(74, 222, 128, 0.3);
}

.btn--primary:hover:not(:disabled) {
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(74, 222, 128, 0.4);
}

.btn--primary:active:not(:disabled) {
  transform: translateY(0);
}

.btn--primary:disabled {
  opacity: 0.6;
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

/* Users Table */
.table-responsive {
  overflow-x: auto;
}

.users-table {
  width: 100%;
  border-collapse: collapse;
}

.users-table th,
.users-table td {
  padding: 1rem 1.25rem;
  text-align: left;
  border-bottom: 1px solid #f1f5f9;
}

.users-table th {
  font-weight: 600;
  color: #64748b;
  font-size: 0.75rem;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  background: #f8fafc;
}

.users-table td {
  color: #0f172a;
  font-size: 0.875rem;
}

.users-table tbody tr {
  transition: background-color 0.15s ease;
}

.users-table tbody tr:hover {
  background-color: #f8fafc;
}

.users-table tbody tr:last-child td {
  border-bottom: none;
}

/* Mobile Card View */
.mobile-users {
  display: none;
}

.mobile-user-card {
  background: #fff;
  border: 1px solid #f1f5f9;
  border-radius: 12px;
  padding: 1.25rem;
  margin-bottom: 1rem;
}

.mobile-user-card__header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 0.75rem;
}

.mobile-user-card__name {
  font-weight: 600;
  color: #0f172a;
  font-size: 1rem;
}

.mobile-user-card__email {
  color: #64748b;
  font-size: 0.875rem;
  margin-bottom: 0.5rem;
}

.mobile-user-card__date {
  color: #94a3b8;
  font-size: 0.75rem;
}

/* Badges */
.badge {
  display: inline-flex;
  align-items: center;
  padding: 0.25rem 0.75rem;
  border-radius: 9999px;
  font-size: 0.75rem;
  font-weight: 600;
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

/* Loading & Empty States */
.loading-state,
.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 4rem 2rem;
  text-align: center;
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
  to {
    transform: rotate(360deg);
  }
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
  margin: 0 0 1rem;
  font-size: 0.9375rem;
}

.error-state {
  color: #dc2626;
  background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
  padding: 2rem;
  border-radius: 12px;
}

/* Modal */
.modal-overlay {
  position: fixed;
  inset: 0;
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
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
  width: 100%;
  max-width: 480px;
  max-height: calc(100vh - 2rem);
  overflow-y: auto;
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
  display: flex;
  align-items: center;
  justify-content: center;
  width: 36px;
  height: 36px;
  border: none;
  background: #f1f5f9;
  border-radius: 10px;
  color: #64748b;
  cursor: pointer;
  transition: all 0.2s ease;
}

.modal__close:hover {
  background-color: #e2e8f0;
  color: #0f172a;
}

.modal__close svg {
  width: 20px;
  height: 20px;
}

.modal__body {
  padding: 1.5rem;
}

.modal__footer {
  display: flex;
  justify-content: flex-end;
  gap: 0.75rem;
  margin-top: 1.5rem;
  padding-top: 1.25rem;
  border-top: 1px solid #f1f5f9;
}

/* Form */
.form-group {
  margin-bottom: 1.25rem;
}

.form-group:last-of-type {
  margin-bottom: 0;
}

.form-label {
  display: block;
  font-size: 0.875rem;
  font-weight: 600;
  color: #0f172a;
  margin-bottom: 0.5rem;
}

.form-input {
  width: 100%;
  padding: 0.875rem 1rem;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  font-size: 0.9375rem;
  transition: all 0.2s ease;
  box-sizing: border-box;
}

.form-input:focus {
  outline: none;
  border-color: #4ade80;
  box-shadow: 0 0 0 3px rgba(74, 222, 128, 0.15);
}

.form-input--error {
  border-color: #ef4444;
}

.form-input--error:focus {
  border-color: #ef4444;
  box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.15);
}

.form-error {
  background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
  border: 1px solid #fecaca;
  color: #dc2626;
  padding: 0.875rem 1rem;
  border-radius: 10px;
  font-size: 0.875rem;
  margin-bottom: 1.25rem;
  font-weight: 500;
}

.form-field-error {
  display: block;
  font-size: 0.75rem;
  color: #ef4444;
  margin-top: 0.375rem;
  font-weight: 500;
}

select.form-input {
  cursor: pointer;
  appearance: none;
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%2364748b'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'%3E%3C/path%3E%3C/svg%3E");
  background-repeat: no-repeat;
  background-position: right 0.75rem center;
  background-size: 1.25rem;
  padding-right: 2.5rem;
}

/* Responsive */
@media (max-width: 768px) {
  .page-header__title {
    font-size: 1.5rem;
  }

  .content-header {
    flex-direction: column;
    align-items: stretch;
  }

  .content-header__title {
    justify-content: center;
  }

  .btn--primary {
    width: 100%;
    justify-content: center;
  }

  /* Hide table, show mobile cards */
  .table-responsive {
    display: none;
  }

  .mobile-users {
    display: block;
  }

  .modal__footer {
    flex-direction: column;
  }

  .modal__footer .btn {
    width: 100%;
  }
}

@media (max-width: 480px) {
  .page-header__title {
    font-size: 1.25rem;
  }

  .content-card {
    padding: 1rem;
  }

  .mobile-user-card {
    padding: 1rem;
  }
}
</style>
