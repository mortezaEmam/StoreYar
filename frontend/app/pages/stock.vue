<template>
  <div>
    <h1>Stock</h1>
    <p v-if="!session.businessId" class="warn">Select a business first.</p>
    <template v-else>
      <form class="create" @submit.prevent="initStock">
        <select v-model="form.product_id" required>
          <option disabled value="">Select product</option>
          <option v-for="p in products" :key="p.id" :value="p.id">
            {{ p.name }} ({{ p.sku }})
          </option>
        </select>
        <input v-model.number="form.quantity" type="number" min="0" required />
        <button type="submit">Initialize stock</button>
      </form>

      <form class="create" @submit.prevent="adjust">
        <select v-model="adjustForm.product_id" required>
          <option disabled value="">Select product</option>
          <option v-for="p in products" :key="p.id" :value="p.id">
            {{ p.name }} ({{ p.sku }})
          </option>
        </select>
        <select v-model="adjustForm.direction">
          <option value="increase">increase</option>
          <option value="decrease">decrease</option>
        </select>
        <input v-model.number="adjustForm.amount" type="number" min="1" required />
        <button type="submit">Adjust</button>
      </form>

      <p v-if="error" class="err">{{ error }}</p>

      <table v-if="items.length">
        <thead>
          <tr><th>Product</th><th>On hand</th><th>Reserved</th></tr>
        </thead>
        <tbody>
          <tr v-for="s in items" :key="s.product_id">
            <td>{{ label(s.product_id) }}</td>
            <td>{{ s.quantity }}</td>
            <td>{{ s.reserved_quantity ?? 0 }}</td>
          </tr>
        </tbody>
      </table>
      <p v-else-if="!loading">No stock rows.</p>
    </template>
  </div>
</template>

<script setup lang="ts">
definePageMeta({ middleware: 'auth' })

const api = useApi()
const session = useSessionStore()

const products = ref<{ id: string; name: string; sku: string }[]>([])
const items = ref<{ product_id: string; quantity: number; reserved_quantity?: number }[]>([])
const loading = ref(false)
const error = ref('')
const form = reactive({ product_id: '', quantity: 10 })
const adjustForm = reactive({ product_id: '', direction: 'increase', amount: 1 })

function label(productId: string) {
  const p = products.value.find(x => x.id === productId)
  return p ? `${p.name} (${p.sku})` : productId.slice(0, 12) + '…'
}

async function loadProducts() {
  if (!session.businessId) return
  try {
    const res = await api.get<any>('/products')
    products.value = Array.isArray(res.data) ? res.data : []
  } catch {
    products.value = []
  }
}

async function loadStock() {
  if (!session.businessId) return
  loading.value = true
  error.value = ''
  try {
    const res = await api.get<any>('/stock')
    items.value = Array.isArray(res.data) ? res.data : []
  } catch (e: unknown) {
    error.value = e instanceof Error ? e.message : 'Load failed'
  } finally {
    loading.value = false
  }
}

async function refresh() {
  await Promise.all([loadProducts(), loadStock()])
}

async function initStock() {
  error.value = ''
  try {
    await api.post(
      '/stock',
      { product_id: form.product_id, quantity: form.quantity },
      crypto.randomUUID(),
    )
    form.quantity = 10
    await loadStock()
  } catch (e: unknown) {
    error.value = e instanceof Error ? e.message : 'Init failed'
  }
}

async function adjust() {
  error.value = ''
  try {
    await api.post(
      `/stock/${adjustForm.product_id}/adjust`,
      { direction: adjustForm.direction, amount: adjustForm.amount },
      crypto.randomUUID(),
    )
    await loadStock()
  } catch (e: unknown) {
    error.value = e instanceof Error ? e.message : 'Adjust failed'
  }
}

watch(() => session.businessId, refresh, { immediate: true })
</script>

<style scoped>
.create { display: flex; flex-wrap: wrap; gap: 0.5rem; margin-bottom: 0.75rem; }
.err { color: #b00; } .warn { color: #a60; }
table { width: 100%; border-collapse: collapse; }
th, td { text-align: left; border-bottom: 1px solid #eee; padding: 0.4rem; }
select, input { padding: 0.35rem; }
</style>
