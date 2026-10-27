<template>
  <div>
    <div class="page-head"><h1>{{ t('home.title') }}</h1></div>
    <p class="hint">{{ t('home.welcome') }}</p>

    <p v-if="!session.businessId" class="warn">
      {{ t('common.selectBusiness') }}
      <NuxtLink to="/business">{{ t('common.chooseBusiness') }}</NuxtLink>
    </p>

    <template v-else>
      <p class="ok">{{ t('nav.business') }}: <code>{{ session.businessId }}</code></p>

      <div v-if="loading" class="hint">{{ t('common.loading') }}</div>
      <p v-if="error" class="err">{{ error }}</p>

      <div class="stats" v-if="dash && !loading">
        <div class="stat card">
          <div class="stat-label">{{ t('nav.products') }}</div>
          <div class="stat-value">{{ dash.products ?? '—' }}</div>
          <NuxtLink to="/products">{{ t('common.view') }}</NuxtLink>
        </div>
        <div class="stat card">
          <div class="stat-label">{{ t('reports.salesToday') }}</div>
          <div class="stat-value">{{ dash.pos_sales_today_count ?? '—' }}</div>
          <NuxtLink to="/pos">{{ t('nav.pos') }}</NuxtLink>
        </div>
        <div class="stat card">
          <div class="stat-label">{{ t('reports.salesTodayAmt') }}</div>
          <div class="stat-value">{{ dash.pos_sales_today_amount ?? '—' }}</div>
        </div>
        <div class="stat card">
          <div class="stat-label">{{ t('reports.lowStock') }}</div>
          <div class="stat-value">{{ dash.low_stock_lines ?? '—' }}</div>
          <NuxtLink to="/reports">{{ t('nav.reports') }}</NuxtLink>
        </div>
      </div>

      <h2 class="section-title">{{ t('home.modules') }}</h2>
      <div class="module-grid">
        <NuxtLink v-for="m in modules" :key="m.to" :to="m.to" class="module-card card">
          <div class="module-title">{{ t(m.titleKey) }}</div>
          <div class="module-desc">{{ t(m.descKey) }}</div>
        </NuxtLink>
      </div>
    </template>
  </div>
</template>

<script setup lang="ts">
import { useSessionStore } from '~/stores/session'
definePageMeta({ middleware: 'auth' })
const { t } = useI18n()
const api = useApi()
const session = useSessionStore()

const dash = ref<any>(null)
const loading = ref(false)
const error = ref('')

const modules = [
  { to: '/pos', titleKey: 'nav.pos', descKey: 'home.desc.pos' },
  { to: '/products', titleKey: 'nav.products', descKey: 'home.desc.products' },
  { to: '/barcodes', titleKey: 'nav.barcodes', descKey: 'home.desc.barcodes' },
  { to: '/warehouses', titleKey: 'nav.warehouses', descKey: 'home.desc.warehouses' },
  { to: '/stock', titleKey: 'nav.stock', descKey: 'home.desc.stock' },
  { to: '/opening-stock', titleKey: 'nav.openingStock', descKey: 'home.desc.openingStock' },
  { to: '/stock-transfers', titleKey: 'nav.stockTransfers', descKey: 'home.desc.transfers' },
  { to: '/stock-counts', titleKey: 'nav.stockCounts', descKey: 'home.desc.counts' },
  { to: '/purchase-orders', titleKey: 'nav.purchaseOrders', descKey: 'home.desc.po' },
  { to: '/suppliers', titleKey: 'nav.suppliers', descKey: 'home.desc.suppliers' },
  { to: '/customers', titleKey: 'nav.customers', descKey: 'home.desc.customers' },
  { to: '/party-payments', titleKey: 'nav.partyPayments', descKey: 'home.desc.partyPay' },
  { to: '/pos-returns', titleKey: 'nav.posReturns', descKey: 'home.desc.posReturns' },
  { to: '/accounting', titleKey: 'nav.accounting', descKey: 'home.desc.accounting' },
  { to: '/reports', titleKey: 'nav.reports', descKey: 'home.desc.reports' },
]

async function load() {
  if (!session.businessId) return
  loading.value = true
  error.value = ''
  try {
    dash.value = api.unwrap(await api.get('/reports/dashboard'))
  } catch (e: any) {
    // dashboard optional if reports route missing
    dash.value = null
    error.value = ''
  } finally {
    loading.value = false
  }
}

onMounted(load)
watch(() => session.businessId, load)
</script>

<style scoped>
.section-title { margin: 1.5rem 0 .75rem; font-size: 1.1rem; }
.module-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(11rem, 1fr));
  gap: .75rem;
}
.module-card {
  text-decoration: none;
  color: inherit;
  padding: 1rem;
  transition: box-shadow .15s, transform .15s;
}
.module-card:hover {
  box-shadow: 0 4px 14px rgba(0,0,0,.08);
  transform: translateY(-1px);
}
.module-title { font-weight: 600; margin-bottom: .25rem; }
.module-desc { font-size: .85rem; opacity: .7; }
.stats {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(10rem, 1fr));
  gap: .75rem;
  margin: 1rem 0;
}
.stat-label { font-size: .8rem; opacity: .7; }
.stat-value { font-size: 1.4rem; font-weight: 700; margin: .25rem 0; }
</style>
