<template>
  <div class="layout">
    <header v-if="session.isAuthenticated" class="header">
      <nav class="nav">
        <NuxtLink to="/">{{ t('nav.home') }}</NuxtLink>
        <NuxtLink to="/business">{{ t('nav.business') }}</NuxtLink>
        <NuxtLink to="/products">{{ t('nav.products') }}</NuxtLink>
        <NuxtLink to="/stock">{{ t('nav.stock') }}</NuxtLink>
        <NuxtLink to="/orders">{{ t('nav.orders') }}</NuxtLink>
        <NuxtLink to="/payments">{{ t('nav.payments') }}</NuxtLink>
        <NuxtLink to="/shipments">{{ t('nav.shipments') }}</NuxtLink>
        <NuxtLink to="/members">{{ t('nav.members') }}</NuxtLink>
        <NuxtLink to="/audit">{{ t('nav.audit') }}</NuxtLink>
      </nav>
      <div class="header-actions">
        <button type="button" class="secondary" @click="setLocale(locale === 'en' ? 'fa' : 'en')">
          {{ locale === 'en' ? 'فا' : 'EN' }}
        </button>
        <button type="button" class="secondary" @click="logout">{{ t('nav.logout') }}</button>
      </div>
    </header>
    <main class="main">
      <slot />
    </main>
    <div v-if="toast.message" class="toast" :class="toast.type">{{ toast.message }}</div>
  </div>
</template>
<script setup lang="ts">
import { useSessionStore } from '~/stores/session'
const session = useSessionStore()
const { t, locale, setLocale } = useI18n()
const { toast } = useToast()
async function logout() {
  try {
    const api = useApi()
    await api.post('/auth/logout')
  } catch { /* ignore */ }
  session.clear()
  await navigateTo('/login')
}
</script>
<style scoped>
.header-actions { display: flex; gap: 0.5rem; align-items: center; }
.toast {
  position: fixed;
  bottom: 1.25rem;
  inset-inline-end: 1.25rem;
  padding: 0.75rem 1rem;
  border-radius: 8px;
  background: #101828;
  color: #fff;
  z-index: 50;
  max-width: min(24rem, 90vw);
}
.toast.ok { background: #027a48; }
.toast.err { background: #b42318; }
</style>
