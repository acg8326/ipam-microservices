<script setup lang="ts">
// Settings view - admin only
</script>

<template>
  <div class="settings-view">
    <header class="page-header">
      <h1 class="page-header__title">Settings</h1>
      <p class="page-header__subtitle">System configuration and administration</p>
    </header>

    <div class="settings-grid">
      <div class="settings-card">
        <h2 class="settings-card__title">User Management</h2>
        <p class="settings-card__description">Manage users, roles, and permissions</p>
        <button class="btn btn--secondary">Manage Users</button>
      </div>

      <div class="settings-card">
        <h2 class="settings-card__title">API Keys</h2>
        <p class="settings-card__description">Generate and manage API keys</p>
        <button class="btn btn--secondary">Manage API Keys</button>
      </div>

      <div class="settings-card">
        <h2 class="settings-card__title">Audit Logs</h2>
        <p class="settings-card__description">View system activity and changes</p>
        <button class="btn btn--secondary">View Logs</button>
      </div>

      <div class="settings-card">
        <h2 class="settings-card__title">Import/Export</h2>
        <p class="settings-card__description">Import or export IP data</p>
        <button class="btn btn--secondary">Import/Export</button>
      </div>
    </div>
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
  font-size: 1.875rem;
  font-weight: bold;
  color: #1f2937;
  margin: 0 0 0.25rem;
}

.page-header__subtitle {
  color: #6b7280;
  margin: 0;
}

.settings-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 1.5rem;
}

.settings-card {
  background: #fff;
  border-radius: 12px;
  padding: 1.5rem;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.settings-card__title {
  font-size: 1.125rem;
  font-weight: 600;
  color: #1f2937;
  margin: 0 0 0.5rem;
}

.settings-card__description {
  color: #6b7280;
  margin: 0 0 1rem;
  font-size: 0.875rem;
}

.btn {
  padding: 0.625rem 1.25rem;
  border-radius: 8px;
  font-weight: 500;
  cursor: pointer;
  border: none;
}

.btn--secondary {
  background-color: #f3f4f6;
  color: #374151;
}

.btn--secondary:hover {
  background-color: #e5e7eb;
}
</style>
