<template>
  <div>
    <div class="page-head"><h1>{{ t('stock.title') }}</h1></div>
    <p v-if="!session.businessId" class="warn">
      {{ t('common.selectBusiness') }}
      <NuxtLink to="/business">{{ t('common.chooseBusiness') }}</NuxtLink>
    </p>
    <template v-else>
      <div class="card">
        <div class="card-title">{{ t('stock.init') }}</div>
        <form class="form-row" @submit.prevent="initStock">
          <label style="margin:0">{{ t('orders.product') }}
            <select v-model="form.product_id" required>
              <option disabled value="">{{ t('stock.selectProduct') }}</option>
              <option v-for="p in products" :key="p.id" :value="p.id">{{ p.name }} ({{ p.sku }})</option>
            </select>
          </label>
          <label style="margin:0">{{ t('stock.quantity') }}
            <input v-model.number="form.quantity" type="number" min="0" required />
          </label>
          <button type="submit" :disabled="pending">{{ t('stock.init') }}</button>
        </form>
      </div>
      <div class="card">
        <div class="card-title">{{ t('stock.adjust') }}</div>
        <form class="form-row" @submit.prevent="adjust">
          <label style="margin:0">{{ t('orders.product') }}
            <select v-model="adjustForm.product_id" required>
              <option disabled value="">{{ t('stock.selectProduct') }}</option>
              <option v-for="p in products" :key="p.id" :value="p.id">{{ p.name }} ({{ p.sku }})</option>
            </select>
          </label>
          <label style="margin:0">{{ t('stock.direction') }}
            <select v-model="adjustForm.direction">
              <option value="increase">{{ t('stock.increase') }}</option>
              <option value="decrease">{{ t('stock.decrease') }}</option>
            </select>
          </label>
          <label style="margin:0">{{ t('stock.amount') }}
            <input v-model.number="adjustForm.amount" type="number" min="1" required />
          </label>
          <button type="submit" :disabled="pending">{{ t('stock.adjust') }}</button>
        </form>
      </div>
      <p v-if="error" class="err">{{ error }}</p>
      <div v-if="items.length" class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>{{ t('orders.product') }}</th>
              <th>{{ t('stock.onHand') }}</th>
              <th>{{ t('stock.reserved') }}</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="s in items" :key="s.product_id">
              <td>{{ label(s.product_id) }}</td>
              <td>{{ s.quantity }}</td>
              <td>{{ s.reserved_quantity ?? 0 }}</td>
            </tr>
          </tbody>
        </table>
      </div>
      <div v-else-if="!loading" class="empty">{{ t('stock.empty') }}</div>
    </template>
  </div>
</template>
<script setup lang="ts">
import { useSessionStore } from '~/stores/session'
definePageMeta({ middleware: 'auth' })
const { t } = useI18n()
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
    error.value = e instanceof Error ? e.message : t('common.error')
    toast.err(error.value)
  } finally { loading.value = false }
}
async function refresh() { await Promise.all([loadProducts(), loadStock()]) }
async function initStock() {
  pending.value = true; error.value = ''
  try {
    await api.post('/stock', { product_id: form.product_id, quantity: form.quantity }, crypto.randomUUID())
    toast.ok(t('stock.initialized')); form.quantity = 10; await loadStock()
  } catch (e: unknown) {
    error.value = e instanceof Error ? e.message : t('common.error'); toast.err(error.value)
  } finally { pending.value = false }
}
async function adjust() {
  pending.value = true; error.value = ''
  try {
    await api.post(`/stock/${adjustForm.product_id}/adjust`, {
      direction: adjustForm.direction, amount: adjustForm.amount,
    }, crypto.randomUUID())
    toast.ok(t('stock.adjusted')); await loadStock()
  } catch (e: unknown) {
    error.value = e instanceof Error ? e.message : t('common.error'); toast.err(error.value)
  } finally { pending.value = false }
}
watch(() => session.businessId, () => { if (session.businessId) refresh() }, { immediate: true })
</script>
