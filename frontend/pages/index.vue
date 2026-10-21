<template>
  <div>
    <template v-if="!session.isAuthenticated">
      <div class="card" style="text-align:center;padding:2.5rem">
        <div class="brand" style="color:var(--primary);font-weight:700;letter-spacing:.06em;text-transform:uppercase;font-size:.8rem">StoreYar</div>
        <h1>Commerce for your business</h1>
        <p class="hint">Products, stock, orders, payments, and shipping in one place.</p>
        <div style="display:flex;gap:.75rem;justify-content:center;margin-top:1.25rem">
          <NuxtLink to="/login"><button type="button">Sign in</button></NuxtLink>
          <NuxtLink to="/register"><button type="button" class="secondary">Register</button></NuxtLink>
        </div>
      </div>
    </template>

    <template v-else-if="!session.businessId">
      <div class="warn">Select or create a business to continue.</div>
      <NuxtLink to="/business"><button type="button">Choose business</button></NuxtLink>
    </template>

    <template v-else>
      <div class="page-head">
        <div>
          <h1>Dashboard</h1>
          <p class="hint" style="margin:0">Active business <code style="font-size:.75rem">{{ session.businessId }}</code></p>
        </div>
        <button type="button" class="secondary" :disabled="loading" @click="loadSummary">Refresh</button>
      </div>

      <div class="cards-grid">
        <NuxtLink to="/products"><strong>Products</strong><span>{{ counts.products }}</span></NuxtLink>
        <NuxtLink to="/stock"><strong>Stock</strong><span>{{ counts.stock }}</span></NuxtLink>
        <NuxtLink to="/orders"><strong>Orders</strong><span>{{ counts.orders }}</span></NuxtLink>
        <NuxtLink to="/payments"><strong>Payments</strong><span>{{ counts.payments }}</span></NuxtLink>
        <NuxtLink to="/shipments"><strong>Shipments</strong><span>{{ counts.shipments }}</span></NuxtLink>
        <NuxtLink to="/audit"><strong>Audit</strong><span>→</span></NuxtLink>
      </div>

      <p v-if="error" class="err">{{ error }}</p>
      <p v-if="loading" class="hint">Loading summary…</p>

      <h2>Suggested flow</h2>
      <ol class="flow">
        <li><NuxtLink to="/products">Add products</NuxtLink></li>
        <li><NuxtLink to="/stock">Initialize stock</NuxtLink></li>
        <li><NuxtLink to="/orders">Create & confirm order</NuxtLink></li>
        <li><NuxtLink to="/payments">Take payment</NuxtLink></li>
        <li><NuxtLink to="/shipments">Ship the order</NuxtLink></li>
      </ol>
    </template>
  </div>
</template>
<script setup lang="ts">
import { useSessionStore } from '~/stores/session'
const session = useSessionStore()
const api = useApi()
const loading = ref(false)
const error = ref('')
const counts = reactive({ products: '—', stock: '—', orders: '—', payments: '—', shipments: '—' })
async function countOf(path: string) {
  try {
    const res = await api.get<any>(path)
    if (Array.isArray(res?.data)) return String(res.data.length)
    if (typeof res?.meta?.total === 'number') return String(res.meta.total)
    return '0'
  } catch { return '?' }
}
async function loadSummary() {
  if (!session.isAuthenticated || !session.businessId) return
  loading.value = true
  error.value = ''
  try {
    const [products, stock, orders, payments, shipments] = await Promise.all([
      countOf('/products'), countOf('/stock'), countOf('/orders'),
      countOf('/payments'), countOf('/shipments'),
    ])
    Object.assign(counts, { products, stock, orders, payments, shipments })
  } catch (e: unknown) {
    error.value = e instanceof Error ? e.message : 'Summary failed'
  } finally {
    loading.value = false
  }
}
watch(() => [session.isAuthenticated, session.businessId], () => loadSummary(), { immediate: true })
</script>
