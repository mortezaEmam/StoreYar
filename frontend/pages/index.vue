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

      <div class="stats" v-if="!loading">
        <div class="stat card">
          <div class="stat-label">{{ t('nav.products') }}</div>
          <div class="stat-value">{{ stats.products }}</div>
          <NuxtLink to="/products">{{ t('common.view') }}</NuxtLink>
        </div>
        <div class="stat card">
          <div class="stat-label">{{ t('nav.orders') }}</div>
          <div class="stat-value">{{ stats.orders }}</div>
          <NuxtLink to="/orders">{{ t('common.view') }}</NuxtLink>
        </div>
        <div class="stat card">
          <div class="stat-label">{{ t('nav.stock') }}</div>
          <div class="stat-value">{{ stats.stock }}</div>
          <NuxtLink to="/stock">{{ t('common.view') }}</NuxtLink>
        </div>
        <div class="stat card">
          <div class="stat-label">{{ t('nav.payments') }}</div>
          <div class="stat-value">{{ stats.payments }}</div>
          <NuxtLink to="/payments">{{ t('common.view') }}</NuxtLink>
        </div>
        <div class="stat card">
          <div class="stat-label">{{ t('nav.shipments') }}</div>
          <div class="stat-value">{{ stats.shipments }}</div>
          <NuxtLink to="/shipments">{{ t('common.view') }}</NuxtLink>
        </div>
        <div class="stat card">
          <div class="stat-label">{{ t('nav.categories') }}</div>
          <div class="stat-value">{{ stats.categories }}</div>
          <NuxtLink to="/categories">{{ t('common.view') }}</NuxtLink>
        </div>
      </div>

      <div class="card" style="margin-top:1rem">
        <div class="card-title">{{ t('home.quick') }}</div>
        <p>
          <NuxtLink to="/products">{{ t('nav.products') }}</NuxtLink>
          ·
          <NuxtLink to="/orders">{{ t('nav.orders') }}</NuxtLink>
          ·
          <NuxtLink to="/stock">{{ t('nav.stock') }}</NuxtLink>
          ·
          <NuxtLink to="/audit">{{ t('nav.audit') }}</NuxtLink>
        </p>
      </div>
    </template>
  </div>
</template>

<script setup lang="ts">
import { useSessionStore } from '~/stores/session'
definePageMeta({ middleware: 'auth' })
const { t } = useI18n()
const session = useSessionStore()
const api = useApi()
const loading = ref(false)
const error = ref('')
const stats = reactive({
  products: 0,
  orders: 0,
  stock: 0,
  payments: 0,
  shipments: 0,
  categories: 0,
})

async function count(path: string): Promise<number> {
  try {
    const res = await api.get<any>(path)
    const data = Array.isArray(res.data) ? res.data : (Array.isArray(res) ? res : [])
    return data.length
  } catch {
    return 0
  }
}

async function load() {
  if (!session.businessId) return
  loading.value = true
  error.value = ''
  try {
    const [products, orders, stock, payments, shipments, categories] = await Promise.all([
      count('/products'),
      count('/orders'),
      count('/stock'),
      count('/payments'),
      count('/shipments'),
      count('/categories'),
    ])
    Object.assign(stats, { products, orders, stock, payments, shipments, categories })
  } catch (e: unknown) {
    error.value = e instanceof Error ? e.message : t('common.error')
  } finally {
    loading.value = false
  }
}

watch(() => session.businessId, load, { immediate: true })
</script>

<style scoped>
.stats {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(9.5rem, 1fr));
  gap: 0.75rem;
}
.stat-label {
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  color: var(--muted, #667085);
}
.stat-value {
  font-size: 1.75rem;
  font-weight: 700;
  margin: 0.25rem 0 0.5rem;
}
</style>
