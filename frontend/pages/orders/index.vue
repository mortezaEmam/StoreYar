<template>
  <div>
    <div class="page-head"><h1>Orders</h1></div>
    <p v-if="!session.businessId" class="warn">Select a business first. <NuxtLink to="/business">Choose business</NuxtLink></p>
    <template v-else>
      <div class="card">
        <div class="card-title">New order</div>
        <p class="hint">1) Create draft → 2) Add line → 3) Confirm (reserves stock)</p>
        <div class="form-row">
          <button type="button" :disabled="pending" @click="createDraft">
            {{ pending && !draftId ? 'Creating…' : 'Create draft' }}
          </button>
        </div>
        <p v-if="draftId" class="ok">Draft ready: <code>{{ draftId.slice(0, 14) }}…</code></p>
        <form v-if="draftId" class="form-row" @submit.prevent="addLine">
          <label style="margin:0">Product
            <select v-model="line.product_id" required>
              <option disabled value="">Select product</option>
              <option v-for="p in products" :key="p.id" :value="p.id">
                {{ p.name }} — {{ p.unit_price_amount }} {{ p.currency || 'IRR' }}
              </option>
            </select>
          </label>
          <label style="margin:0">Qty
            <input v-model.number="line.quantity" type="number" min="1" required />
          </label>
          <label style="margin:0">Unit price
            <input v-model.number="line.unit_price_amount" type="number" min="0" required />
          </label>
          <button type="submit" :disabled="pending">Add line</button>
        </form>
        <button v-if="draftId" type="button" :disabled="pending" @click="confirmDraft">Confirm order</button>
      </div>
      <p v-if="error" class="err">{{ error }}</p>
      <div v-if="items.length" class="table-wrap">
        <table>
          <thead><tr><th>ID</th><th>Status</th><th>Total</th><th></th></tr></thead>
          <tbody>
            <tr v-for="o in items" :key="o.id">
              <td>
                <NuxtLink :to="`/orders/${o.id}`">
                  <code style="font-size:.8rem">{{ o.id.slice(0, 12) }}…</code>
                </NuxtLink>
              </td>
              <td><span class="badge" :class="String(o.status).toLowerCase()">{{ o.status }}</span></td>
              <td>{{ o.total_amount != null ? o.total_amount : '—' }}</td>
              <td style="display:flex;gap:.35rem;flex-wrap:wrap">
                <NuxtLink :to="`/orders/${o.id}`">
                  <button type="button" class="secondary">View</button>
                </NuxtLink>
                <button
                  v-if="String(o.status).toLowerCase() === 'draft'"
                  type="button"
                  class="secondary"
                  @click="confirmExisting(o.id)"
                >Confirm</button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <div v-else-if="!loading" class="empty">No orders yet.</div>
    </template>
  </div>
</template>
<script setup lang="ts">
import { useSessionStore } from '~/stores/session'
definePageMeta({ middleware: 'auth' })
const api = useApi()
const session = useSessionStore()
const toast = useToast()
const items = ref<any[]>([])
const products = ref<any[]>([])
const loading = ref(false)
const pending = ref(false)
const error = ref('')
const draftId = ref('')
const line = reactive({ product_id: '', quantity: 1, unit_price_amount: 1000 })
watch(() => line.product_id, (id) => {
  const p = products.value.find((x: any) => x.id === id)
  if (p) line.unit_price_amount = p.unit_price_amount
})
async function loadProducts() {
  if (!session.businessId) return
  try {
    const res = await api.get<any>('/products')
    products.value = Array.isArray(res.data) ? res.data : []
  } catch { products.value = [] }
}
async function load() {
  if (!session.businessId) return
  loading.value = true
  error.value = ''
  try {
    const res = await api.get<any>('/orders')
    items.value = Array.isArray(res.data) ? res.data : []
  } catch (e: unknown) {
    error.value = e instanceof Error ? e.message : 'Load failed'
    toast.err(error.value)
  } finally { loading.value = false }
}
async function createDraft() {
  pending.value = true; error.value = ''
  try {
    const res = await api.post<any>('/orders', undefined, crypto.randomUUID())
    draftId.value = res.data?.id || res.id
    toast.ok('Draft created — add a line, then confirm')
    await load()
  } catch (e: unknown) {
    error.value = e instanceof Error ? e.message : 'Create failed'
    toast.err(error.value)
  } finally { pending.value = false }
}
async function addLine() {
  if (!draftId.value) return
  pending.value = true; error.value = ''
  try {
    await api.post(`/orders/${draftId.value}/lines`, {
      product_id: line.product_id, quantity: line.quantity, unit_price_amount: line.unit_price_amount,
    }, crypto.randomUUID())
    toast.ok('Line added')
    line.product_id = ''
    await load()
  } catch (e: unknown) {
    error.value = e instanceof Error ? e.message : 'Add line failed'
    toast.err(error.value)
  } finally { pending.value = false }
}
async function confirmDraft() {
  if (!draftId.value) return
  await confirmExisting(draftId.value)
  draftId.value = ''
}
async function confirmExisting(id: string) {
  pending.value = true; error.value = ''
  try {
    await api.post(`/orders/${id}/confirm`, {}, crypto.randomUUID())
    toast.ok('Order confirmed')
    await load()
  } catch (e: unknown) {
    error.value = e instanceof Error ? e.message : 'Confirm failed'
    toast.err(error.value)
  } finally { pending.value = false }
}
watch(() => session.businessId, async () => { await loadProducts(); await load() }, { immediate: true })
</script>
