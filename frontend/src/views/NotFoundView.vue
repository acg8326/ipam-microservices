<script setup lang="ts">
import { useRouter } from 'vue-router'

const router = useRouter()

function goHome() {
  router.push('/')
}
</script>

<template>
  <div class="not-found">
    <div class="not-found__content">
      <h1 class="not-found__code">404</h1>
      <h2 class="not-found__title">Page Not Found</h2>
      <p class="not-found__message">
        The page you're looking for doesn't exist or has been moved.
      </p>
      <button class="btn btn--primary" @click="goHome">
        Go to Dashboard
      </button>
    </div>
  </div>
</template>

<style scoped>
.not-found {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background-color: #f5f7fa;
}

.not-found__content {
  text-align: center;
  padding: 2rem;
}

.not-found__code {
  font-size: 8rem;
  font-weight: bold;
  color: #e5e7eb;
  margin: 0;
  line-height: 1;
}

.not-found__title {
  font-size: 1.5rem;
  color: #1f2937;
  margin: 1rem 0 0.5rem;
}

.not-found__message {
  color: #6b7280;
  margin: 0 0 2rem;
}

.btn {
  padding: 0.875rem 2rem;
  border-radius: 8px;
  font-weight: 600;
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
