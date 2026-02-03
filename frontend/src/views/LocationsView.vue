<script setup lang="ts">
// Locations view - placeholder
</script>

<template>
  <div class="locations-view">
    <header class="page-header">
      <div>
        <h1 class="page-header__title">Locations</h1>
        <p class="page-header__subtitle">Manage data centers and locations</p>
      </div>
      <button class="btn btn--primary">+ Add Location</button>
    </header>

    <div class="placeholder">
      <p>Location management coming soon...</p>
    </div>
  </div>
</template>

<style scoped>
.locations-view {
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

.placeholder {
  background: #fff;
  border-radius: 12px;
  padding: 4rem;
  text-align: center;
  color: #6b7280;
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
</style>
