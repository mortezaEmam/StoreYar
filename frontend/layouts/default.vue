<template>
  <div class="layout">
    <header v-if="session.isAuthenticated" class="header">
      <nav class="nav nav-primary">
        <NuxtLink to="/">{{ t('nav.home') }}</NuxtLink>
        <NuxtLink to="/pos">{{ t('nav.pos') }}</NuxtLink>
        <NuxtLink to="/products">{{ t('nav.products') }}</NuxtLink>
        <NuxtLink to="/stock">{{ t('nav.stock') }}</NuxtLink>
        <NuxtLink to="/purchase-orders">{{ t('nav.purchaseOrders') }}</NuxtLink>
        <NuxtLink to="/customers">{{ t('nav.customers') }}</NuxtLink>
        <NuxtLink to="/reports">{{ t('nav.reports') }}</NuxtLink>
        <NuxtLink to="/business">{{ t('nav.business') }}</NuxtLink>
        <button type="button" class="nav-more secondary" @click="showMore = !showMore">
          {{ showMore ? t('nav.less') : t('nav.more') }}
        </button>
      </nav>
      <nav v-if="showMore" class="nav nav-secondary">
        <NuxtLink to="/barcodes">{{ t('nav.barcodes') }}</NuxtLink>
        <NuxtLink to="/categories">{{ t('nav.categories') }}</NuxtLink>
        <NuxtLink to="/warehouses">{{ t('nav.warehouses') }}</NuxtLink>
        <NuxtLink to="/opening-stock">{{ t('nav.openingStock') }}</NuxtLink>
        <NuxtLink to="/stock-transfers">{{ t('nav.stockTransfers') }}</NuxtLink>
        <NuxtLink to="/stock-counts">{{ t('nav.stockCounts') }}</NuxtLink>
        <NuxtLink to="/pos-returns">{{ t('nav.posReturns') }}</NuxtLink>
        <NuxtLink to="/suppliers">{{ t('nav.suppliers') }}</NuxtLink>
        <NuxtLink to="/party-payments">{{ t('nav.partyPayments') }}</NuxtLink>
        <NuxtLink to="/accounting">{{ t('nav.accounting') }}</NuxtLink>
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
    <main class="main"><slot /></main>
    <div class="toast-stack">
      <div v-for="item in toasts" :key="item.id" class="toast" :class="item.kind">{{ item.message }}</div>
    </div>
  </div>
</template>
<script setup lang="ts">
import { useSessionStore } from '~/stores/session'
const session = useSessionStore()
const { t, locale, setLocale } = useI18n()
const { toasts } = useToast()
const showMore = ref(false)
async function logout() {
  try { await useApi().post('/auth/logout') } catch { /* */ }
  session.clear()
  await navigateTo('/login')
}
</script>
<style scoped>
.header {
  display: flex;
  flex-wrap: wrap;
  align-items: flex-start;
  gap: .5rem 1rem;
  justify-content: space-between;
}
.nav { display: flex; flex-wrap: wrap; gap: .35rem .75rem; align-items: center; }
.nav-secondary {
  width: 100%;
  padding: .35rem 0;
  border-top: 1px solid rgba(0,0,0,.06);
  font-size: .9rem;
}
.nav-more { font-size: .85rem; padding: .2rem .5rem; }
.header-actions { display: flex; gap: 0.5rem; align-items: center; margin-inline-start: auto; }
.toast-stack {
  position: fixed; bottom: 1.25rem; inset-inline-end: 1.25rem; z-index: 50;
  display: flex; flex-direction: column; gap: 0.5rem; max-width: min(24rem, 90vw);
}
.toast { padding: 0.75rem 1rem; border-radius: 8px; background: #101828; color: #fff; }
.toast.ok { background: #027a48; }
.toast.err { background: #b42318; }
.toast.hint { background: #344054; }
</style>
