<template>
  <div>
    <h1>Shipments</h1>
    <p v-if="!session.businessId" class="warn">Select a business first.</p>
    <template v-else>
      <form class="create" @submit.prevent="create">
        <select v-model="form.order_id" required>
          <option disabled value="">Select order</option>
          <option v-for="o in shippableOrders" :key="o.id" :value="o.id">
            {{ o.id.slice(0, 12) }}… — {{ o.status }}
          </option>
        </select>
        <button type="submit">Create shipment</button>
      </form>
      <p v-if="!shippableOrders.length && !loadingOrders" class="hint">
        No paid orders available. Complete a payment first.
      </p>
      <p v-if="error" class="err">{{ error }}</p>
      <table v-if="items.length">
        <thead>
          <tr><th>ID</th><th>Order</th><th>Status</th><th>Tracking</th><th></th></tr>
        </thead>
        <tbody>
          <tr v-for="s in items" :key="s.id">
            <td>{{ s.id.slice(0, 10) }}…</td>
            <td>{{ s.order_id.slice(0, 10) }}…</td>
            <td>{{ s.status }}</td>
            <td>{{ s.tracking_number || '—' }}</td>
            <td>
              <button
                v-if="canShip(s.status)"
                type="button"
                @click="ship(s.id)"
              >
                Ship
              </button>
            </td>
          </tr>
        </tbody>
      </table>
      <p v-else-if="!loading">No shipments.</p>
    </template>
  </div>
</template>

<script setup lang="ts">
definePageMeta({ middleware: 'auth' })

const api = useApi()
const session = useSessionStore()

const items = ref<{ id: string; order_id: string; status: string; tracking_number?: string | null }[]>([])
const orders = ref<{ id: string; status: string }[]>([])
const loading = ref(false)
const loadingOrders = ref(false)
const error = ref('')
const form = reactive({ order_id: '' })

const shippableOrders = computed(() =>
  orders.value.filter(o => ['paid', 'confirmed'].includes(String(o.status).toLowerCase())),
)

function canShip(status: string) {
  const s = status.toLowerCase()
  return !['shipped', 'delivered', 'cancelled'].includes(s)
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

async function loadShipments() {
  if (!session.businessId) return
  loading.value = true
  error.value = ''
  try {
    const res = await api.get<any>('/shipments')
    items.value = Array.isArray(res.data) ? res.data : []
  } catch (e: unknown) {
    error.value = e instanceof Error ? e.message : 'Load failed'
  } finally {
    loading.value = false
  }
}

async function refresh() {
  await Promise.all([loadOrders(), loadShipments()])
}

async function create() {
  error.value = ''
  try {
    await api.post('/shipments', { order_id: form.order_id }, crypto.randomUUID())
    form.order_id = ''
    await loadShipments()
  } catch (e: unknown) {
    error.value = e instanceof Error ? e.message : 'Create failed'
  }
}

async function ship(id: string) {
  error.value = ''
  const tracking = window.prompt('Tracking number', 'TRACK-1')
  if (!tracking) return
  try {
    await api.post(`/shipments/${id}/ship`, { tracking_number: tracking }, crypto.randomUUID())
    await loadShipments()
  } catch (e: unknown) {
    error.value = e instanceof Error ? e.message : 'Ship failed'
  }
}

watch(() => session.businessId, refresh, { immediate: true })
</script>

<style scoped>
.create { display: flex; flex-wrap: wrap; gap: 0.5rem; margin-bottom: 1rem; }
.err { color: #b00; } .warn { color: #a60; } .hint { color: #666; font-size: 0.9rem; }
table { width: 100%; border-collapse: collapse; }
th, td { text-align: left; border-bottom: 1px solid #eee; padding: 0.4rem; }
select { padding: 0.35rem; min-width: 16rem; }
</style>
