<script setup lang="ts">
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores'

const router = useRouter()
const authStore = useAuthStore()

const sidebarOpen = ref(true)
const userMenuOpen = ref(false)

const user = computed(() => authStore.user)

const navigation = [
  { name: 'Dashboard', path: '/', icon: 'dashboard' },
  { name: 'Subnets', path: '/subnets', icon: 'network' },
  { name: 'IP Addresses', path: '/ip-addresses', icon: 'ip' },
  { name: 'VLANs', path: '/vlans', icon: 'vlan' },
  { name: 'Locations', path: '/locations', icon: 'location' },
  { name: 'Search', path: '/search', icon: 'search' },
]

async function logout() {
  await authStore.logout()
  router.push('/login')
}

function toggleSidebar() {
  sidebarOpen.value = !sidebarOpen.value
}
</script>

<template>
  <div class="layout">
    <!-- Sidebar -->
    <aside class="sidebar" :class="{ 'sidebar--collapsed': !sidebarOpen }">
      <div class="sidebar__header">
        <h1 class="sidebar__logo">
          <span v-if="sidebarOpen">IPAM</span>
          <span v-else>IP</span>
        </h1>
      </div>
      
      <nav class="sidebar__nav">
        <router-link
          v-for="item in navigation"
          :key="item.path"
          :to="item.path"
          class="sidebar__link"
          :title="item.name"
        >
          <span class="sidebar__icon">{{ item.icon.charAt(0).toUpperCase() }}</span>
          <span v-if="sidebarOpen" class="sidebar__text">{{ item.name }}</span>
        </router-link>
      </nav>
      
      <div class="sidebar__footer">
        <router-link
          v-if="authStore.isAdmin"
          to="/settings"
          class="sidebar__link"
          title="Settings"
        >
          <span class="sidebar__icon">S</span>
          <span v-if="sidebarOpen" class="sidebar__text">Settings</span>
        </router-link>
      </div>
    </aside>

    <!-- Main Content -->
    <div class="main">
      <!-- Top Header -->
      <header class="header">
        <button class="header__toggle" @click="toggleSidebar">
          ☰
        </button>
        
        <div class="header__spacer"></div>
        
        <div class="header__user">
          <button class="header__user-button" @click="userMenuOpen = !userMenuOpen">
            <span class="header__avatar">{{ user?.name?.charAt(0) || 'U' }}</span>
            <span class="header__username">{{ user?.name || 'User' }}</span>
          </button>
          
          <div v-if="userMenuOpen" class="header__dropdown">
            <div class="header__dropdown-item">
              <strong>{{ user?.name }}</strong>
              <small>{{ user?.email }}</small>
            </div>
            <hr>
            <button class="header__dropdown-item header__dropdown-item--button" @click="logout">
              Logout
            </button>
          </div>
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
  background-color: #f5f7fa;
}

.sidebar {
  width: 250px;
  background-color: #1a1a2e;
  color: #fff;
  display: flex;
  flex-direction: column;
  transition: width 0.3s ease;
}

.sidebar--collapsed {
  width: 60px;
}

.sidebar__header {
  padding: 1rem;
  border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.sidebar__logo {
  font-size: 1.5rem;
  font-weight: bold;
  color: #4ade80;
  margin: 0;
}

.sidebar__nav {
  flex: 1;
  padding: 1rem 0;
}

.sidebar__link {
  display: flex;
  align-items: center;
  padding: 0.75rem 1rem;
  color: #a0a0a0;
  text-decoration: none;
  transition: all 0.2s ease;
}

.sidebar__link:hover,
.sidebar__link.router-link-active {
  background-color: rgba(74, 222, 128, 0.1);
  color: #4ade80;
}

.sidebar__icon {
  width: 28px;
  height: 28px;
  display: flex;
  align-items: center;
  justify-content: center;
  background-color: rgba(255, 255, 255, 0.1);
  border-radius: 6px;
  font-size: 0.875rem;
  font-weight: bold;
}

.sidebar__text {
  margin-left: 0.75rem;
}

.sidebar__footer {
  padding: 1rem 0;
  border-top: 1px solid rgba(255, 255, 255, 0.1);
}

.main {
  flex: 1;
  display: flex;
  flex-direction: column;
  min-width: 0;
}

.header {
  height: 60px;
  background-color: #fff;
  border-bottom: 1px solid #e5e7eb;
  display: flex;
  align-items: center;
  padding: 0 1rem;
  gap: 1rem;
}

.header__toggle {
  background: none;
  border: none;
  font-size: 1.25rem;
  cursor: pointer;
  padding: 0.5rem;
  border-radius: 6px;
}

.header__toggle:hover {
  background-color: #f3f4f6;
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
  gap: 0.5rem;
  background: none;
  border: none;
  cursor: pointer;
  padding: 0.5rem;
  border-radius: 6px;
}

.header__user-button:hover {
  background-color: #f3f4f6;
}

.header__avatar {
  width: 32px;
  height: 32px;
  background-color: #4ade80;
  color: #fff;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: bold;
}

.header__username {
  font-weight: 500;
}

.header__dropdown {
  position: absolute;
  top: 100%;
  right: 0;
  background-color: #fff;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
  min-width: 200px;
  z-index: 50;
}

.header__dropdown-item {
  padding: 0.75rem 1rem;
  display: flex;
  flex-direction: column;
}

.header__dropdown-item--button {
  width: 100%;
  background: none;
  border: none;
  text-align: left;
  cursor: pointer;
  color: #ef4444;
}

.header__dropdown-item--button:hover {
  background-color: #fef2f2;
}

.content {
  flex: 1;
  padding: 1.5rem;
  overflow-y: auto;
}
</style>
