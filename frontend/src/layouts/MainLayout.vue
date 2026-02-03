<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores'

const router = useRouter()
const authStore = useAuthStore()

const sidebarOpen = ref(true)
const mobileMenuOpen = ref(false)
const userMenuOpen = ref(false)
const isMobile = ref(false)

const user = computed(() => authStore.user)

const navigation = [
  { name: 'Dashboard', path: '/', icon: 'dashboard' },
  { name: 'IP Addresses', path: '/ip-addresses', icon: 'ip' },
]

function checkMobile() {
  isMobile.value = window.innerWidth < 1024
  if (isMobile.value) {
    sidebarOpen.value = false
  }
}

onMounted(() => {
  checkMobile()
  window.addEventListener('resize', checkMobile)
})

onUnmounted(() => {
  window.removeEventListener('resize', checkMobile)
})

async function logout() {
  await authStore.logout()
  router.push('/login')
}

function toggleSidebar() {
  if (isMobile.value) {
    mobileMenuOpen.value = !mobileMenuOpen.value
  } else {
    sidebarOpen.value = !sidebarOpen.value
  }
}

function closeMobileMenu() {
  mobileMenuOpen.value = false
}

function handleNavClick() {
  if (isMobile.value) {
    mobileMenuOpen.value = false
  }
}
</script>

<template>
  <div class="layout">
    <!-- Mobile Overlay -->
    <div 
      v-if="mobileMenuOpen" 
      class="layout__overlay"
      @click="closeMobileMenu"
    ></div>

    <!-- Sidebar -->
    <aside 
      class="sidebar" 
      :class="{ 
        'sidebar--collapsed': !sidebarOpen && !isMobile,
        'sidebar--mobile-open': mobileMenuOpen && isMobile
      }"
    >
      <div class="sidebar__header">
        <div class="sidebar__logo">
          <svg class="sidebar__logo-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 17.93c-3.95-.49-7-3.85-7-7.93 0-.62.08-1.21.21-1.79L9 15v1c0 1.1.9 2 2 2v1.93zm6.9-2.54c-.26-.81-1-1.39-1.9-1.39h-1v-3c0-.55-.45-1-1-1H8v-2h2c.55 0 1-.45 1-1V7h2c1.1 0 2-.9 2-2v-.41c2.93 1.19 5 4.06 5 7.41 0 2.08-.8 3.97-2.1 5.39z"/>
          </svg>
          <span v-if="sidebarOpen || isMobile" class="sidebar__logo-text">IPAM</span>
        </div>
        <button v-if="isMobile" class="sidebar__close" @click="closeMobileMenu">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
            <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/>
          </svg>
        </button>
      </div>
      
      <nav class="sidebar__nav">
        <router-link
          v-for="item in navigation"
          :key="item.path"
          :to="item.path"
          class="sidebar__link"
          :title="item.name"
          @click="handleNavClick"
        >
          <span class="sidebar__icon">
            <!-- Dashboard Icon -->
            <svg v-if="item.icon === 'dashboard'" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
              <path d="M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z"/>
            </svg>
            <!-- IP Icon -->
            <svg v-if="item.icon === 'ip'" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
              <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 17.93c-3.95-.49-7-3.85-7-7.93 0-.62.08-1.21.21-1.79L9 15v1c0 1.1.9 2 2 2v1.93zm6.9-2.54c-.26-.81-1-1.39-1.9-1.39h-1v-3c0-.55-.45-1-1-1H8v-2h2c.55 0 1-.45 1-1V7h2c1.1 0 2-.9 2-2v-.41c2.93 1.19 5 4.06 5 7.41 0 2.08-.8 3.97-2.1 5.39z"/>
            </svg>
          </span>
          <span v-if="sidebarOpen || isMobile" class="sidebar__text">{{ item.name }}</span>
        </router-link>
        
        <!-- Admin-only: Audit Logs -->
        <router-link
          v-if="authStore.isAdmin"
          to="/audit-logs"
          class="sidebar__link"
          title="Audit Logs"
          @click="handleNavClick"
        >
          <span class="sidebar__icon">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
              <path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-5 14H7v-2h7v2zm3-4H7v-2h10v2zm0-4H7V7h10v2z"/>
            </svg>
          </span>
          <span v-if="sidebarOpen || isMobile" class="sidebar__text">Audit Logs</span>
        </router-link>
      </nav>
      
      <div class="sidebar__footer">
        <router-link
          v-if="authStore.isAdmin"
          to="/settings"
          class="sidebar__link"
          title="Settings"
          @click="handleNavClick"
        >
          <span class="sidebar__icon">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
              <path d="M19.14 12.94c.04-.31.06-.63.06-.94 0-.31-.02-.63-.06-.94l2.03-1.58c.18-.14.23-.41.12-.61l-1.92-3.32c-.12-.22-.37-.29-.59-.22l-2.39.96c-.5-.38-1.03-.7-1.62-.94l-.36-2.54c-.04-.24-.24-.41-.48-.41h-3.84c-.24 0-.43.17-.47.41l-.36 2.54c-.59.24-1.13.57-1.62.94l-2.39-.96c-.22-.08-.47 0-.59.22L2.74 8.87c-.12.21-.08.47.12.61l2.03 1.58c-.04.31-.06.63-.06.94s.02.63.06.94l-2.03 1.58c-.18.14-.23.41-.12.61l1.92 3.32c.12.22.37.29.59.22l2.39-.96c.5.38 1.03.7 1.62.94l.36 2.54c.05.24.24.41.48.41h3.84c.24 0 .44-.17.47-.41l.36-2.54c.59-.24 1.13-.56 1.62-.94l2.39.96c.22.08.47 0 .59-.22l1.92-3.32c.12-.22.07-.47-.12-.61l-2.01-1.58zM12 15.6c-1.98 0-3.6-1.62-3.6-3.6s1.62-3.6 3.6-3.6 3.6 1.62 3.6 3.6-1.62 3.6-3.6 3.6z"/>
            </svg>
          </span>
          <span v-if="sidebarOpen || isMobile" class="sidebar__text">Settings</span>
        </router-link>

        <!-- User info in sidebar for mobile -->
        <div v-if="isMobile" class="sidebar__user">
          <div class="sidebar__user-info">
            <span class="sidebar__user-avatar">{{ user?.name?.charAt(0) || 'U' }}</span>
            <div v-if="sidebarOpen || isMobile" class="sidebar__user-details">
              <span class="sidebar__user-name">{{ user?.name }}</span>
              <span class="sidebar__user-role">{{ user?.role }}</span>
            </div>
          </div>
          <button class="sidebar__logout" @click="logout">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
              <path d="M17 7l-1.41 1.41L18.17 11H8v2h10.17l-2.58 2.58L17 17l5-5zM4 5h8V3H4c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h8v-2H4V5z"/>
            </svg>
          </button>
        </div>
      </div>
    </aside>

    <!-- Main Content -->
    <div class="main">
      <!-- Top Header -->
      <header class="header">
        <button class="header__toggle" @click="toggleSidebar" aria-label="Toggle menu">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
            <path d="M3 18h18v-2H3v2zm0-5h18v-2H3v2zm0-7v2h18V6H3z"/>
          </svg>
        </button>

        <!-- Mobile Logo -->
        <div v-if="isMobile" class="header__brand">
          <svg class="header__brand-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 17.93c-3.95-.49-7-3.85-7-7.93 0-.62.08-1.21.21-1.79L9 15v1c0 1.1.9 2 2 2v1.93zm6.9-2.54c-.26-.81-1-1.39-1.9-1.39h-1v-3c0-.55-.45-1-1-1H8v-2h2c.55 0 1-.45 1-1V7h2c1.1 0 2-.9 2-2v-.41c2.93 1.19 5 4.06 5 7.41 0 2.08-.8 3.97-2.1 5.39z"/>
          </svg>
          <span class="header__brand-text">IPAM</span>
        </div>
        
        <div class="header__spacer"></div>
        
        <!-- Desktop User Menu -->
        <div class="header__user" v-if="!isMobile">
          <button class="header__user-button" @click="userMenuOpen = !userMenuOpen">
            <span class="header__avatar">{{ user?.name?.charAt(0) || 'U' }}</span>
            <span class="header__user-info">
              <span class="header__username">{{ user?.name || 'User' }}</span>
              <span class="header__role">{{ user?.role }}</span>
            </span>
            <svg class="header__chevron" :class="{ 'header__chevron--open': userMenuOpen }" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
              <path d="M7.41 8.59L12 13.17l4.59-4.58L18 10l-6 6-6-6 1.41-1.41z"/>
            </svg>
          </button>
          
          <Transition name="dropdown">
            <div v-if="userMenuOpen" class="header__dropdown">
              <div class="header__dropdown-header">
                <span class="header__dropdown-avatar">{{ user?.name?.charAt(0) || 'U' }}</span>
                <div class="header__dropdown-info">
                  <strong>{{ user?.name }}</strong>
                  <small>{{ user?.email }}</small>
                </div>
              </div>
              <div class="header__dropdown-divider"></div>
              <button class="header__dropdown-item" @click="logout">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                  <path d="M17 7l-1.41 1.41L18.17 11H8v2h10.17l-2.58 2.58L17 17l5-5zM4 5h8V3H4c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h8v-2H4V5z"/>
                </svg>
                Sign out
              </button>
            </div>
          </Transition>
        </div>
      </header>

      <!-- Page Content -->
      <main class="content">
        <router-view />
      </main>
    </div>
  </div>
</template>

<style scoped>
.layout {
  display: flex;
  min-height: 100vh;
  background-color: #f8fafc;
}

.layout__overlay {
  position: fixed;
  inset: 0;
  background-color: rgba(0, 0, 0, 0.5);
  z-index: 40;
  backdrop-filter: blur(2px);
}

/* Sidebar Styles */
.sidebar {
  width: 260px;
  background: linear-gradient(180deg, #0f172a 0%, #1e293b 100%);
  color: #fff;
  display: flex;
  flex-direction: column;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  position: relative;
  z-index: 50;
}

.sidebar--collapsed {
  width: 72px;
}

.sidebar--collapsed .sidebar__logo-text,
.sidebar--collapsed .sidebar__text {
  display: none;
}

.sidebar__header {
  padding: 1.25rem;
  display: flex;
  align-items: center;
  justify-content: space-between;
  border-bottom: 1px solid rgba(255, 255, 255, 0.08);
  min-height: 72px;
}

.sidebar__logo {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.sidebar__logo-icon {
  width: 36px;
  height: 36px;
  color: #4ade80;
  flex-shrink: 0;
}

.sidebar__logo-text {
  font-size: 1.375rem;
  font-weight: 700;
  color: #fff;
  letter-spacing: -0.025em;
}

.sidebar__close {
  display: none;
  background: none;
  border: none;
  color: #94a3b8;
  padding: 0.5rem;
  cursor: pointer;
  border-radius: 8px;
}

.sidebar__close:hover {
  background: rgba(255, 255, 255, 0.1);
  color: #fff;
}

.sidebar__close svg {
  width: 24px;
  height: 24px;
}

.sidebar__nav {
  flex: 1;
  padding: 1rem 0.75rem;
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.sidebar__link {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.75rem 1rem;
  color: #94a3b8;
  text-decoration: none;
  border-radius: 10px;
  transition: all 0.2s ease;
  font-weight: 500;
}

.sidebar__link:hover {
  background-color: rgba(255, 255, 255, 0.05);
  color: #e2e8f0;
}

.sidebar__link.router-link-active {
  color: #fff;
}

.sidebar__icon {
  width: 40px;
  height: 40px;
  min-width: 40px;
  display: flex;
  align-items: center;
  justify-content: center;
  background-color: rgba(255, 255, 255, 0.06);
  border-radius: 10px;
  transition: all 0.2s ease;
}

.sidebar__link:hover .sidebar__icon {
  background-color: rgba(255, 255, 255, 0.1);
}

.sidebar__link.router-link-active .sidebar__icon {
  background: linear-gradient(135deg, #4ade80 0%, #22c55e 100%);
  color: #0f172a;
}

.sidebar__icon svg {
  width: 20px;
  height: 20px;
}

.sidebar__text {
  font-size: 0.9375rem;
}

.sidebar__footer {
  padding: 0.75rem;
  border-top: 1px solid rgba(255, 255, 255, 0.08);
}

.sidebar__user {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0.75rem;
  margin-top: 0.5rem;
  background: rgba(255, 255, 255, 0.05);
  border-radius: 10px;
}

.sidebar__user-info {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.sidebar__user-avatar {
  width: 36px;
  height: 36px;
  background: linear-gradient(135deg, #4ade80 0%, #22c55e 100%);
  color: #fff;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 600;
  font-size: 0.875rem;
}

.sidebar__user-details {
  display: flex;
  flex-direction: column;
}

.sidebar__user-name {
  font-size: 0.875rem;
  font-weight: 600;
  color: #fff;
}

.sidebar__user-role {
  font-size: 0.75rem;
  color: #64748b;
  text-transform: capitalize;
}

.sidebar__logout {
  background: none;
  border: none;
  color: #94a3b8;
  padding: 0.5rem;
  cursor: pointer;
  border-radius: 8px;
  transition: all 0.2s ease;
}

.sidebar__logout:hover {
  background: rgba(239, 68, 68, 0.1);
  color: #ef4444;
}

.sidebar__logout svg {
  width: 20px;
  height: 20px;
}

/* Main Content */
.main {
  flex: 1;
  display: flex;
  flex-direction: column;
  min-width: 0;
  background-color: #f8fafc;
}

/* Header */
.header {
  height: 64px;
  background-color: #fff;
  border-bottom: 1px solid #e2e8f0;
  display: flex;
  align-items: center;
  padding: 0 1.5rem;
  gap: 1rem;
  position: sticky;
  top: 0;
  z-index: 30;
}

.header__toggle {
  background: none;
  border: none;
  padding: 0.625rem;
  cursor: pointer;
  border-radius: 10px;
  color: #64748b;
  transition: all 0.2s ease;
}

.header__toggle:hover {
  background-color: #f1f5f9;
  color: #0f172a;
}

.header__brand {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.header__brand-icon {
  width: 28px;
  height: 28px;
  color: #4ade80;
}

.header__brand-text {
  font-size: 1.125rem;
  font-weight: 700;
  color: #0f172a;
  letter-spacing: -0.025em;
}

.header__toggle svg {
  width: 24px;
  height: 24px;
}

.header__spacer {
  flex: 1;
}

.header__user {
  position: relative;
}

.header__user-button {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  background: none;
  border: 1px solid #e2e8f0;
  cursor: pointer;
  padding: 0.5rem 0.75rem 0.5rem 0.5rem;
  border-radius: 12px;
  transition: all 0.2s ease;
}

.header__user-button:hover {
  background-color: #f8fafc;
  border-color: #cbd5e1;
}

.header__avatar {
  width: 36px;
  height: 36px;
  background: linear-gradient(135deg, #4ade80 0%, #22c55e 100%);
  color: #fff;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 600;
  font-size: 0.875rem;
}

.header__user-info {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  text-align: left;
}

.header__username {
  font-weight: 600;
  font-size: 0.875rem;
  color: #0f172a;
}

.header__role {
  font-size: 0.75rem;
  color: #64748b;
  text-transform: capitalize;
}

.header__chevron {
  width: 20px;
  height: 20px;
  color: #94a3b8;
  transition: transform 0.2s ease;
}

.header__chevron--open {
  transform: rotate(180deg);
}

.header__dropdown {
  position: absolute;
  top: calc(100% + 8px);
  right: 0;
  background-color: #fff;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  box-shadow: 0 10px 40px -10px rgba(0, 0, 0, 0.2);
  min-width: 240px;
  z-index: 50;
  overflow: hidden;
}

.header__dropdown-header {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 1rem;
  background: #f8fafc;
}

.header__dropdown-avatar {
  width: 44px;
  height: 44px;
  background: linear-gradient(135deg, #4ade80 0%, #22c55e 100%);
  color: #fff;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 600;
  font-size: 1rem;
}

.header__dropdown-info {
  display: flex;
  flex-direction: column;
}

.header__dropdown-info strong {
  font-size: 0.9375rem;
  color: #0f172a;
}

.header__dropdown-info small {
  font-size: 0.8125rem;
  color: #64748b;
}

.header__dropdown-divider {
  height: 1px;
  background: #e2e8f0;
}

.header__dropdown-item {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  width: 100%;
  padding: 0.875rem 1rem;
  background: none;
  border: none;
  text-align: left;
  cursor: pointer;
  color: #64748b;
  font-size: 0.875rem;
  font-weight: 500;
  transition: all 0.2s ease;
}

.header__dropdown-item:hover {
  background-color: #fef2f2;
  color: #ef4444;
}

.header__dropdown-item svg {
  width: 20px;
  height: 20px;
}

/* Page Content */
.content {
  flex: 1;
  padding: 1.5rem;
  overflow-y: auto;
}

/* Dropdown Animation */
.dropdown-enter-active,
.dropdown-leave-active {
  transition: all 0.2s ease;
}

.dropdown-enter-from,
.dropdown-leave-to {
  opacity: 0;
  transform: translateY(-8px);
}

/* Mobile Styles */
@media (max-width: 1023px) {
  .sidebar {
    position: fixed;
    left: 0;
    top: 0;
    bottom: 0;
    transform: translateX(-100%);
    width: 280px;
  }

  .sidebar--mobile-open {
    transform: translateX(0);
  }

  .sidebar__close {
    display: block;
  }

  .header {
    padding: 0 1rem;
  }

  .content {
    padding: 1rem;
  }
}

@media (max-width: 640px) {
  .header__user-info {
    display: none;
  }

  .header__user-button {
    padding: 0.375rem;
    border: none;
  }
}
</style>
