<template>
  <div>
    <div class="page-head"><h1>Shipments</h1></div>
    <p v-if="!session.businessId" class="warn">Select a business first. <NuxtLink to="/business">Choose business</NuxtLink></p>
    <template v-else>
      <div class="card">
        <div class="card-title">Create shipment</div>
        <form class="form-row" @submit.prevent="create">
          <label style="margin:0">Order
            <select v-model="form.order_id" required>
              <option disabled value="">Select order</option>
              <option v-for="o in shippableOrders" :key="o.id" :value="o.id">
                {{ o.id.slice(0, 12) }}… — {{ o.status }}
              </option>
            </select>
          </label>
          <button type="submit" :disabled="pending">Create</button>
        </form>
        <p v-if="!shippableOrders.length" class="hint">Paid/confirmed orders appear here.</p>
      </div>
      <p v-if="error" class="err">{{ error }}</p>
      <div v-if="items.length" class="table-wrap">
        <table>
          <thead><tr><th>ID</th><th>Order</th><th>Status</th><th>Tracking</th><th></th></tr></thead>
          <tbody>
            <tr v-for="s in items" :key="s.id">
              <td><code style="font-size:.8rem">{{ s.id.slice(0, 10) }}…</code></td>
              <td><code style="font-size:.8rem">{{ s.order_id.slice(0, 10) }}…</code></td>
              <td><span class="badge" :class="String(s.status).toLowerCase()">{{ s.status }}</span></td>
              <td>{{ s.tracking_number || '—' }}</td>
              <td>
                <button v-if="canShip(s.status)" type="button" class="secondary" @click="ship(s.id)">Ship</button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <div v-else-if="!loading" class="empty">No shipments yet.</div>
    </template>
  </div>
</template>
<script setup lang="ts">
import { useSessionStore } from '~/stores/session'
definePageMeta({ middleware: 'auth' })
const api = useApi(); const session = useSessionStore(); const toast = useToast()
const items = ref<any[]>([]); const orders = ref<any[]>([])
const loading = ref(false); const pending = ref(false); const error = ref('')
const form = reactive({ order_id: '' })
const shippableOrders = computed(() =>
  orders.value.filter((o: any) => ['paid', 'confirmed'].includes(String(o.status).toLowerCase())),
)
function canShip(status: string) {
  return !['shipped', 'delivered', 'cancelled'].includes(String(status).toLowerCase())
}
async function refresh() {
  if (!session.businessId) return
  loading.value = true; error.value = ''
  try {
    const [o, s] = await Promise.all([api.get<any>('/orders'), api.get<any>('/shipments')])
    orders.value = Array.isArray(o.data) ? o.data : []
    items.value = Array.isArray(s.data) ? s.data : []
  } catch (e: unknown) {
    error.value = e instanceof Error ? e.message : 'Load failed'; toast.err(error.value)
  } finally { loading.value = false }
}
async function create() {
  pending.value = true; error.value = ''
  try {
    await api.post('/shipments', { order_id: form.order_id }, crypto.randomUUID())
    toast.ok('Shipment created'); form.order_id = ''; await refresh()
  } catch (e: unknown) {
    error.value = e instanceof Error ? e.message : 'Failed'; toast.err(error.value)
  } finally { pending.value = false }
}
async function ship(id: string) {
  const tracking = window.prompt('Tracking number', 'TRACK-1')
  if (!tracking) return
  pending.value = true; error.value = ''
  try {
    await api.post(`/shipments/${id}/ship`, { tracking_number: tracking }, crypto.randomUUID())
    toast.ok('Marked as shipped'); await refresh()
  } catch (e: unknown) {
    error.value = e instanceof Error ? e.message : 'Failed'; toast.err(error.value)
  } finally { pending.value = false }
}
watch(() => session.businessId, refresh, { immediate: true })
</script>
