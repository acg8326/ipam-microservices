<script setup lang="ts">
import { ref } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '@/stores'

const router = useRouter()
const route = useRoute()
const authStore = useAuthStore()

const email = ref('')
const password = ref('')
const error = ref('')
const loading = ref(false)

async function handleLogin() {
  if (!email.value || !password.value) {
    error.value = 'Please enter email and password'
    return
  }

  loading.value = true
  error.value = ''

  try {
    await authStore.login({
      email: email.value,
      password: password.value,
    })
    
    const redirect = route.query.redirect as string
    router.push(redirect || '/')
  } catch (e: unknown) {
    const err = e as { message?: string }
    error.value = err.message || 'Invalid credentials'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="login">
    <div class="login__card">
      <div class="login__header">
        <h1 class="login__title">IPAM</h1>
        <p class="login__subtitle">IP Address Management System</p>
      </div>

      <form class="login__form" @submit.prevent="handleLogin">
        <div v-if="error" class="login__error">
          {{ error }}
        </div>

        <div class="login__field">
          <label for="email" class="login__label">Email</label>
          <input
            id="email"
            v-model="email"
            type="email"
            class="login__input"
            placeholder="admin@example.com"
            required
            autocomplete="username"
          >
        </div>

        <div class="login__field">
          <label for="password" class="login__label">Password</label>
          <input
            id="password"
            v-model="password"
            type="password"
            class="login__input"
            placeholder="••••••••"
            required
            autocomplete="current-password"
          >
        </div>

        <button
          type="submit"
          class="login__button"
          :disabled="loading"
        >
          {{ loading ? 'Signing in...' : 'Sign In' }}
        </button>

        <p class="login__help-text">
          Contact your administrator if you need an account.
        </p>
      </form>
    </div>
  </div>
</template>

<style scoped>
.login {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
  padding: 1rem;
}

.login__card {
  background: #fff;
  border-radius: 12px;
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
  padding: 2.5rem;
  width: 100%;
  max-width: 400px;
}

.login__header {
  text-align: center;
  margin-bottom: 2rem;
}

.login__title {
  font-size: 2rem;
  font-weight: bold;
  color: #1a1a2e;
  margin: 0 0 0.5rem;
}

.login__subtitle {
  color: #6b7280;
  margin: 0;
}

.login__form {
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
}

.login__error {
  background-color: #fef2f2;
  border: 1px solid #fecaca;
  color: #dc2626;
  padding: 0.75rem 1rem;
  border-radius: 8px;
  font-size: 0.875rem;
}

.login__field {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.login__label {
  font-size: 0.875rem;
  font-weight: 500;
  color: #374151;
}

.login__input {
  padding: 0.75rem 1rem;
  border: 1px solid #d1d5db;
  border-radius: 8px;
  font-size: 1rem;
  transition: all 0.2s ease;
}

.login__input:focus {
  outline: none;
  border-color: #4ade80;
  box-shadow: 0 0 0 3px rgba(74, 222, 128, 0.1);
}

.login__button {
  background-color: #4ade80;
  color: #fff;
  padding: 0.875rem 1rem;
  border: none;
  border-radius: 8px;
  font-size: 1rem;
  font-weight: 600;
  cursor: pointer;
  transition: background-color 0.2s ease;
}

.login__button:hover:not(:disabled) {
  background-color: #22c55e;
}

.login__button:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.login__help-text {
  text-align: center;
  color: #9ca3af;
  font-size: 0.8125rem;
  margin: 0.5rem 0 0;
}
</style>
