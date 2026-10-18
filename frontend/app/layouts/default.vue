<template>
  <div class="layout">
    <header v-if="session.isAuthenticated" class="header">
      <nav class="nav">
        <NuxtLink to="/">Home</NuxtLink>
        <NuxtLink to="/products">Products</NuxtLink>
        <NuxtLink to="/orders">Orders</NuxtLink>
        <NuxtLink to="/business">Business</NuxtLink>
      </nav>
      <div class="meta">
        <span v-if="session.businessId" class="biz">Biz: {{ session.businessId.slice(0, 8) }}…</span>
        <button type="button" @click="logout">Logout</button>
      </div>
    </header>
    <main class="main">
      <slot />
    </main>
  </div>
</template>

<script setup lang="ts">
const session = useSessionStore()
const api = useApi()

async function logout() {
  try {
    await api.post('/auth/logout')
  } catch {
    /* ignore */
  }
  session.clear()
  await navigateTo('/login')
}
</script>

<style scoped>
.layout { font-family: system-ui, sans-serif; min-height: 100vh; }
.header { display: flex; justify-content: space-between; padding: 0.75rem 1.25rem; border-bottom: 1px solid #ddd; }
.nav { display: flex; gap: 1rem; }
.main { padding: 1.25rem; max-width: 960px; margin: 0 auto; }
.meta { display: flex; gap: 0.75rem; align-items: center; }
.biz { font-size: 0.85rem; color: #555; }
a.router-link-active { font-weight: 600; }
</style>
