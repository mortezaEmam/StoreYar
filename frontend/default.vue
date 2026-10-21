<template>
  <div class="layout">
    <header v-if="session.isAuthenticated" class="header">
      <nav class="nav">
        <NuxtLink to="/">Home</NuxtLink>
        <NuxtLink to="/products">Products</NuxtLink>
        <NuxtLink to="/stock">Stock</NuxtLink>
        <NuxtLink to="/orders">Orders</NuxtLink>
        <NuxtLink to="/payments">Payments</NuxtLink>
        <NuxtLink to="/shipments">Shipments</NuxtLink>
        <NuxtLink to="/members">Members</NuxtLink>
        <NuxtLink to="/audit">Audit</NuxtLink>
        <NuxtLink to="/business">Business</NuxtLink>
      </nav>
      <div class="meta">
        <span v-if="session.businessId" class="biz" :title="session.businessId">
          {{ session.businessId.slice(0, 8) }}…
        </span>
        <button type="button" class="secondary" @click="logout">Logout</button>
      </div>
    </header>

    <main class="main">
      <slot />
    </main>

    <div class="toast-stack" aria-live="polite">
      <div
        v-for="t in toast.toasts.value"
        :key="t.id"
        class="toast"
        :class="t.kind"
        @click="toast.dismiss(t.id)"
      >
        {{ t.message }}
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { useSessionStore } from '~/stores/session'

const session = useSessionStore()
const api = useApi()
const toast = useToast()

async function logout() {
  try {
    await api.post('/auth/logout')
  } catch {
    /* ignore */
  }
  session.clear()
  toast.hint('Signed out')
  await navigateTo('/login')
}
</script>

<style scoped>
.toast-stack {
  position: fixed;
  right: 1rem;
  bottom: 1rem;
  z-index: 50;
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  max-width: min(22rem, calc(100vw - 2rem));
}
.toast {
  padding: 0.75rem 1rem;
  border-radius: 10px;
  font-size: 0.9rem;
  box-shadow: 0 8px 24px rgba(16, 24, 40, 0.12);
  cursor: pointer;
  border: 1px solid transparent;
  background: #fff;
}
.toast.ok {
  background: var(--success-soft, #ecfdf3);
  color: var(--success, #027a48);
  border-color: #a6f4c5;
}
.toast.err {
  background: var(--danger-soft, #fef3f2);
  color: var(--danger, #b42318);
  border-color: #fecdca;
}
.toast.hint {
  background: #fff;
  color: var(--text, #101828);
  border-color: var(--border, #e4e7ec);
}
</style>
