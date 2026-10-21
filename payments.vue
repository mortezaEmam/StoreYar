<template>
  <div>
    <h1>Payments</h1>
    <p v-if="!session.businessId" class="warn">Select a business first.</p>
    <template v-else>
      <form class="create" @submit.prevent="initiate">
        <select v-model="form.order_id" required>
          <option disabled value="">Select order</option>
          <option v-for="o in payableOrders" :key="o.id" :value="o.id">
            {{ o.id.slice(0, 12) }}… — {{ o.status }}
            <template v-if="o.total_amount != null"> — {{ o.total_amount }}</template>
          </option>
        </select>
        <input v-model.number="form.amount" type="number" min="1" required />
        <input v-model="form.currency" placeholder="Currency" />
        <button type="submit">Initiate payment</button>
      </form>
      <p v-if="!payableOrders.length && !loadingOrders" class="hint">
        No confirmed/paid orders available. Confirm an order first.
      </p>
      <p v-if="error" class="err">{{ error }}</p>
      <table v-if="items.length">
        <thead>
          <tr><th>ID</th><th>Order</th><th>Amount</th><th>Status</th><th></th></tr>
        </thead>
        <tbody>
          <tr v-for="p in items" :key="p.id">
            <td>{{ p.id.slice(0, 10) }}…</td>
            <td>{{ p.order_id.slice(0, 10) }}…</td>
            <td>{{ p.amount }} {{ p.currency }}</td>
            <td>{{ p.status }}</td>
            <td>
              <button
                v-if="isPending(p.status)"
                type="button"
                @click="succeed(p.id)"
              >
                Mark succeeded
              </button>
            </td>
          </tr>
        </tbody>
      </table>
      <p v-else-if="!loading">No payments.</p>
    </template>
  </div>
</template>

<script setup lang="ts">
definePageMeta({ middleware: 'auth' })

const api = useApi()
const session = useSessionStore()

type OrderRow = { id: string; status: string; total_amount?: number; currency?: string }
type PaymentRow = { id: string; order_id: string; amount: number; currency: string; status: string }

const items = ref<PaymentRow[]>([])
const orders = ref<OrderRow[]>([])
const loading = ref(false)
const loadingOrders = ref(false)
const error = ref('')
const form = reactive({ order_id: '', amount: 1000, currency: 'IRR' })

const payableOrders = computed(() =>
  orders.value.filter(o => ['confirmed', 'paid'].includes(String(o.status).toLowerCase())),
)

watch(
  () => form.order_id,
  (id) => {
    const o = orders.value.find(x => x.id === id)
    if (o?.total_amount != null && o.total_amount > 0) {
      form.amount = o.total_amount
    }
    if (o?.currency) form.currency = o.currency
  },
)

function isPending(status: string) {
  const s = status.toLowerCase()
  return s === 'pending' || s === 'initiated'
}

async function loadOrders() {
  if (!session.businessId) return
  loadingOrders.value = true
  try {
    const res = await api.get<any>('/orders')
    orders.value = Array.isArray(res.data) ? res.data : []
  } catch {
    orders.value = []
  } finally {
    loadingOrders.value = false
  }
}

async function loadPayments() {
  if (!session.businessId) return
  loading.value = true
  error.value = ''
  try {
    const res = await api.get<any>('/payments')
    items.value = Array.isArray(res.data) ? res.data : []
  } catch (e: unknown) {
    error.value = e instanceof Error ? e.message : 'Load failed'
  } finally {
    loading.value = false
  }
}

async function refresh() {
  await Promise.all([loadOrders(), loadPayments()])
}

async function initiate() {
  error.value = ''
  try {
    await api.post('/payments', { ...form }, crypto.randomUUID())
    form.order_id = ''
    await loadPayments()
  } catch (e: unknown) {
    error.value = e instanceof Error ? e.message : 'Initiate failed'
  }
}

async function succeed(id: string) {
  error.value = ''
  try {
    await api.post(`/payments/${id}/succeed`, {}, crypto.randomUUID())
    await loadPayments()
  } catch (e: unknown) {
    error.value = e instanceof Error ? e.message : 'Succeed failed'
  }
}

watch(() => session.businessId, refresh, { immediate: true })
</script>

<style scoped>
.create { display: flex; flex-wrap: wrap; gap: 0.5rem; margin-bottom: 1rem; }
.err { color: #b00; } .warn { color: #a60; } .hint { color: #666; font-size: 0.9rem; }
table { width: 100%; border-collapse: collapse; }
th, td { text-align: left; border-bottom: 1px solid #eee; padding: 0.4rem; }
select, input { padding: 0.35rem; }
</style>
