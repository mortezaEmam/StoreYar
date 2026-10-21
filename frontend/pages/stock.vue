<template>
  <div>
    <div class="page-head"><h1>Stock</h1></div>
    <p v-if="!session.businessId" class="warn">Select a business first. <NuxtLink to="/business">Choose business</NuxtLink></p>
    <template v-else>
      <div class="card">
        <div class="card-title">Initialize stock</div>
        <form class="form-row" @submit.prevent="initStock">
          <label style="margin:0">Product
            <select v-model="form.product_id" required>
              <option disabled value="">Select product</option>
              <option v-for="p in products" :key="p.id" :value="p.id">{{ p.name }} ({{ p.sku }})</option>
            </select>
          </label>
          <label style="margin:0">Quantity
            <input v-model.number="form.quantity" type="number" min="0" required />
          </label>
          <button type="submit" :disabled="pending">Initialize</button>
        </form>
      </div>
      <div class="card">
        <div class="card-title">Adjust stock</div>
        <form class="form-row" @submit.prevent="adjust">
          <label style="margin:0">Product
            <select v-model="adjustForm.product_id" required>
              <option disabled value="">Select product</option>
              <option v-for="p in products" :key="p.id" :value="p.id">{{ p.name }} ({{ p.sku }})</option>
            </select>
          </label>
          <label style="margin:0">Direction
            <select v-model="adjustForm.direction">
              <option value="increase">Increase</option>
              <option value="decrease">Decrease</option>
            </select>
          </label>
          <label style="margin:0">Amount
            <input v-model.number="adjustForm.amount" type="number" min="1" required />
          </label>
          <button type="submit" :disabled="pending">Adjust</button>
        </form>
      </div>
      <p v-if="error" class="err">{{ error }}</p>
      <div v-if="items.length" class="table-wrap">
        <table>
          <thead><tr><th>Product</th><th>On hand</th><th>Reserved</th></tr></thead>
          <tbody>
            <tr v-for="s in items" :key="s.product_id">
              <td>{{ label(s.product_id) }}</td>
              <td>{{ s.quantity }}</td>
              <td>{{ s.reserved_quantity ?? 0 }}</td>
            </tr>
          </tbody>
        </table>
      </div>
      <div v-else-if="!loading" class="empty">No stock rows. Initialize stock for a product.</div>
    </template>
  </div>
</template>
<script setup lang="ts">
import { useSessionStore } from '~/stores/session'
definePageMeta({ middleware: 'auth' })
const api = useApi(); const session = useSessionStore(); const toast = useToast()
const products = ref<any[]>([]); const items = ref<any[]>([])
const loading = ref(false); const pending = ref(false); const error = ref('')
const form = reactive({ product_id: '', quantity: 10 })
const adjustForm = reactive({ product_id: '', direction: 'increase', amount: 1 })
function label(id: string) {
  const p = products.value.find((x: any) => x.id === id)
  return p ? `${p.name} (${p.sku})` : id.slice(0, 12) + '…'
}
async function loadProducts() {
  try {
    const res = await api.get<any>('/products')
    products.value = Array.isArray(res.data) ? res.data : []
  } catch { products.value = [] }
}
async function loadStock() {
  loading.value = true; error.value = ''
  try {
    const res = await api.get<any>('/stock')
    items.value = Array.isArray(res.data) ? res.data : []
  } catch (e: unknown) {
    error.value = e instanceof Error ? e.message : 'Load failed'
    toast.err(error.value)
  } finally { loading.value = false }
}
async function refresh() { await Promise.all([loadProducts(), loadStock()]) }
async function initStock() {
  pending.value = true; error.value = ''
  try {
    await api.post('/stock', { product_id: form.product_id, quantity: form.quantity }, crypto.randomUUID())
    toast.ok('Stock initialized'); form.quantity = 10; await loadStock()
  } catch (e: unknown) {
    error.value = e instanceof Error ? e.message : 'Init failed'; toast.err(error.value)
  } finally { pending.value = false }
}
async function adjust() {
  pending.value = true; error.value = ''
  try {
    await api.post(`/stock/${adjustForm.product_id}/adjust`, {
      direction: adjustForm.direction, amount: adjustForm.amount,
    }, crypto.randomUUID())
    toast.ok('Stock adjusted'); await loadStock()
  } catch (e: unknown) {
    error.value = e instanceof Error ? e.message : 'Adjust failed'; toast.err(error.value)
  } finally { pending.value = false }
}
watch(() => session.businessId, () => { if (session.businessId) refresh() }, { immediate: true })
</script>
