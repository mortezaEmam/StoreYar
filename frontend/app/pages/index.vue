<template>
  <div>
    <h1>StoreYar</h1>

    <template v-if="!session.isAuthenticated">
      <p>
        <NuxtLink to="/login">Login</NuxtLink>
        or
        <NuxtLink to="/register">Register</NuxtLink>
      </p>
    </template>

    <template v-else-if="!session.businessId">
      <p>Select or create a business to continue.</p>
      <NuxtLink to="/business">Go to Business</NuxtLink>
    </template>

    <template v-else>
      <p class="biz">
        Active business: <code>{{ session.businessId }}</code>
      </p>

      <div class="cards">
        <NuxtLink class="card" to="/products">
          <strong>Products</strong>
          <span>{{ counts.products }}</span>
        </NuxtLink>
        <NuxtLink class="card" to="/stock">
          <strong>Stock rows</strong>
          <span>{{ counts.stock }}</span>
        </NuxtLink>
        <NuxtLink class="card" to="/orders">
          <strong>Orders</strong>
          <span>{{ counts.orders }}</span>
        </NuxtLink>
        <NuxtLink class="card" to="/payments">
          <strong>Payments</strong>
          <span>{{ counts.payments }}</span>
        </NuxtLink>
        <NuxtLink class="card" to="/shipments">
          <strong>Shipments</strong>
          <span>{{ counts.shipments }}</span>
        </NuxtLink>
        <NuxtLink class="card" to="/audit">
          <strong>Audit</strong>
          <span>view</span>
        </NuxtLink>
      </div>

      <p v-if="error" class="err">{{ error }}</p>
      <p v-if="loading" class="muted">Loading summary…</p>

      <ol class="flow">
        <li><NuxtLink to="/products">Add products</NuxtLink></li>
        <li><NuxtLink to="/stock">Initialize stock</NuxtLink></li>
        <li><NuxtLink to="/orders">Create & confirm order</NuxtLink></li>
        <li><NuxtLink to="/payments">Take payment</NuxtLink></li>
        <li><NuxtLink to="/shipments">Ship</NuxtLink></li>
      </ol>
    </template>
  </div>
</template>

<script setup lang="ts">
const session = useSessionStore()
const api = useApi()

const loading = ref(false)
const error = ref('')
const counts = reactive({
  products: '—',
  stock: '—',
  orders: '—',
  payments: '—',
  shipments: '—',
})

async function countOf(path: string): Promise<string> {
  try {
    const res = await api.get<any>(path)
    const data = res?.data
    if (Array.isArray(data)) return String(data.length)
    if (typeof res?.meta?.total === 'number') return String(res.meta.total)
    return '0'
  } catch {
    return '?'
  }
}

async function loadSummary() {
  if (!session.isAuthenticated || !session.businessId) return
  loading.value = true
  error.value = ''
  try {
    const [products, stock, orders, payments, shipments] = await Promise.all([
      countOf('/products'),
      countOf('/stock'),
      countOf('/orders'),
      countOf('/payments'),
      countOf('/shipments'),
    ])
    counts.products = products
    counts.stock = stock
    counts.orders = orders
    counts.payments = payments
    counts.shipments = shipments
  } catch (e: unknown) {
    error.value = e instanceof Error ? e.message : 'Summary failed'
  } finally {
    loading.value = false
  }
}

watch(
  () => [session.isAuthenticated, session.businessId],
  () => loadSummary(),
  { immediate: true },
)
</script>

<style scoped>
.biz { font-size: 0.9rem; color: #444; }
.cards {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(9rem, 1fr));
  gap: 0.75rem;
  margin: 1.25rem 0;
}
.card {
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
  padding: 0.9rem;
  border: 1px solid #e5e5e5;
  border-radius: 8px;
  text-decoration: none;
  color: inherit;
  background: #fafafa;
}
.card span { font-size: 1.4rem; font-weight: 600; }
.flow { line-height: 1.8; }
.err { color: #b00; }
.muted { color: #888; }
code { font-size: 0.8rem; word-break: break-all; }
</style>
