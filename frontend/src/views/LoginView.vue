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
const showPassword = ref(false)

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
    error.value = err.message || 'Invalid email or password. Please try again.'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="login">
    <!-- Background decoration -->
    <div class="login__bg-pattern"></div>
    
    <div class="login__card">
      <!-- Logo/Icon -->
      <div class="login__logo">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="login__logo-icon">
          <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 17.93c-3.95-.49-7-3.85-7-7.93 0-.62.08-1.21.21-1.79L9 15v1c0 1.1.9 2 2 2v1.93zm6.9-2.54c-.26-.81-1-1.39-1.9-1.39h-1v-3c0-.55-.45-1-1-1H8v-2h2c.55 0 1-.45 1-1V7h2c1.1 0 2-.9 2-2v-.41c2.93 1.19 5 4.06 5 7.41 0 2.08-.8 3.97-2.1 5.39z"/>
        </svg>
      </div>

      <div class="login__header">
        <h1 class="login__title">Welcome to IPAM</h1>
        <p class="login__subtitle">Sign in to manage your IP addresses</p>
      </div>

      <form class="login__form" @submit.prevent="handleLogin">
        <div v-if="error" class="login__error">
          <svg xmlns="http://www.w3.org/2000/svg" class="login__error-icon" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
          </svg>
          <span>{{ error }}</span>
        </div>

        <div class="login__field">
          <label for="email" class="login__label">
            <svg xmlns="http://www.w3.org/2000/svg" class="login__field-icon" viewBox="0 0 20 20" fill="currentColor">
              <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z" />
              <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z" />
            </svg>
            Email Address
          </label>
          <input
            id="email"
            v-model="email"
            type="email"
            class="login__input"
            placeholder="you@company.com"
            required
            autocomplete="username"
          >
        </div>

        <div class="login__field">
          <label for="password" class="login__label">
            <svg xmlns="http://www.w3.org/2000/svg" class="login__field-icon" viewBox="0 0 20 20" fill="currentColor">
              <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd" />
            </svg>
            Password
          </label>
          <div class="login__input-wrapper">
            <input
              id="password"
              v-model="password"
              :type="showPassword ? 'text' : 'password'"
              class="login__input login__input--password"
              placeholder="Enter your password"
              required
              autocomplete="current-password"
            >
            <button 
              type="button" 
              class="login__toggle-password"
              @click="showPassword = !showPassword"
              :title="showPassword ? 'Hide password' : 'Show password'"
            >
              <svg v-if="!showPassword" xmlns="http://www.w3.org/2000/svg" class="login__eye-icon" viewBox="0 0 20 20" fill="currentColor">
                <path d="M10 12a2 2 0 100-4 2 2 0 000 4z" />
                <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd" />
              </svg>
              <svg v-else xmlns="http://www.w3.org/2000/svg" class="login__eye-icon" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M3.707 2.293a1 1 0 00-1.414 1.414l14 14a1 1 0 001.414-1.414l-1.473-1.473A10.014 10.014 0 0019.542 10C18.268 5.943 14.478 3 10 3a9.958 9.958 0 00-4.512 1.074l-1.78-1.781zm4.261 4.26l1.514 1.515a2.003 2.003 0 012.45 2.45l1.514 1.514a4 4 0 00-5.478-5.478z" clip-rule="evenodd" />
                <path d="M12.454 16.697L9.75 13.992a4 4 0 01-3.742-3.741L2.335 6.578A9.98 9.98 0 00.458 10c1.274 4.057 5.065 7 9.542 7 .847 0 1.669-.105 2.454-.303z" />
              </svg>
            </button>
          </div>
        </div>

        <button
          type="submit"
          class="login__button"
          :disabled="loading"
        >
          <svg v-if="loading" class="login__spinner" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
          <span v-if="loading">Signing in...</span>
          <span v-else>Sign In</span>
        </button>
      </form>

      <div class="login__footer">
        <div class="login__divider">
          <span>Need access?</span>
        </div>
        <p class="login__help-text">
          Contact your system administrator to request an account.
        </p>
      </div>
    </div>

    <!-- Version/Copyright -->
    <p class="login__copyright">© {{ new Date().getFullYear() }} IPAM System</p>
  </div>
</template>

<style scoped>
.login {
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #0f172a 100%);
  padding: 1rem;
  position: relative;
  overflow: hidden;
}

.login__bg-pattern {
  position: absolute;
  inset: 0;
  background-image: 
    radial-gradient(circle at 25% 25%, rgba(74, 222, 128, 0.05) 0%, transparent 50%),
    radial-gradient(circle at 75% 75%, rgba(59, 130, 246, 0.05) 0%, transparent 50%);
  pointer-events: none;
}

.login__card {
  background: #fff;
  border-radius: 16px;
  box-shadow: 
    0 25px 50px -12px rgba(0, 0, 0, 0.4),
    0 0 0 1px rgba(255, 255, 255, 0.1);
  padding: 2.5rem;
  width: 100%;
  max-width: 420px;
  position: relative;
  z-index: 1;
}

.login__logo {
  display: flex;
  justify-content: center;
  margin-bottom: 1.5rem;
}

.login__logo-icon {
  width: 56px;
  height: 56px;
  color: #4ade80;
  background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%);
  padding: 12px;
  border-radius: 16px;
}

.login__header {
  text-align: center;
  margin-bottom: 2rem;
}

.login__title {
  font-size: 1.75rem;
  font-weight: 700;
  color: #0f172a;
  margin: 0 0 0.5rem;
  letter-spacing: -0.025em;
}

.login__subtitle {
  color: #64748b;
  margin: 0;
  font-size: 0.9375rem;
}

.login__form {
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
}

.login__error {
  display: flex;
  align-items: center;
  gap: 0.625rem;
  background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
  border: 1px solid #fecaca;
  color: #dc2626;
  padding: 0.875rem 1rem;
  border-radius: 10px;
  font-size: 0.875rem;
  font-weight: 500;
}

.login__error-icon {
  width: 20px;
  height: 20px;
  flex-shrink: 0;
}

.login__field {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.login__label {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.875rem;
  font-weight: 600;
  color: #334155;
}

.login__field-icon {
  width: 16px;
  height: 16px;
  color: #64748b;
}

.login__input-wrapper {
  position: relative;
  display: flex;
  align-items: center;
}

.login__input {
  width: 100%;
  padding: 0.875rem 1rem;
  border: 2px solid #e2e8f0;
  border-radius: 10px;
  font-size: 1rem;
  color: #0f172a;
  background: #f8fafc;
  transition: all 0.2s ease;
}

.login__input::placeholder {
  color: #94a3b8;
}

.login__input:hover {
  border-color: #cbd5e1;
  background: #fff;
}

.login__input:focus {
  outline: none;
  border-color: #4ade80;
  background: #fff;
  box-shadow: 0 0 0 4px rgba(74, 222, 128, 0.15);
}

.login__input--password {
  padding-right: 3rem;
}

.login__toggle-password {
  position: absolute;
  right: 0.75rem;
  background: none;
  border: none;
  padding: 0.5rem;
  cursor: pointer;
  color: #64748b;
  border-radius: 6px;
  transition: all 0.2s ease;
}

.login__toggle-password:hover {
  color: #334155;
  background: #f1f5f9;
}

.login__eye-icon {
  width: 20px;
  height: 20px;
}

.login__button {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  background: linear-gradient(135deg, #4ade80 0%, #22c55e 100%);
  color: #fff;
  padding: 1rem 1.5rem;
  border: none;
  border-radius: 10px;
  font-size: 1rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s ease;
  box-shadow: 0 4px 6px -1px rgba(34, 197, 94, 0.25);
  margin-top: 0.5rem;
}

.login__button:hover:not(:disabled) {
  background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
  transform: translateY(-1px);
  box-shadow: 0 6px 10px -1px rgba(34, 197, 94, 0.3);
}

.login__button:active:not(:disabled) {
  transform: translateY(0);
}

.login__button:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}

.login__spinner {
  width: 20px;
  height: 20px;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

.login__footer {
  margin-top: 2rem;
  padding-top: 1.5rem;
  border-top: 1px solid #e2e8f0;
}

.login__divider {
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 0.75rem;
}

.login__divider span {
  font-size: 0.8125rem;
  font-weight: 600;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.login__help-text {
  text-align: center;
  color: #94a3b8;
  font-size: 0.875rem;
  margin: 0;
}

.login__copyright {
  position: absolute;
  bottom: 1.5rem;
  color: rgba(255, 255, 255, 0.4);
  font-size: 0.75rem;
  margin: 0;
}

/* Responsive */
@media (max-width: 480px) {
  .login__card {
    padding: 2rem 1.5rem;
  }
  
  .login__title {
    font-size: 1.5rem;
  }
}
</style>
