<template>
  <div>
    <h1>Stock</h1>
    <p v-if="!session.businessId" class="warn">Select a business first.</p>
    <template v-else>
      <form class="create" @submit.prevent="initStock">
        <input v-model="form.product_id" placeholder="Product ID" required />
        <input v-model.number="form.quantity" type="number" min="0" placeholder="Qty" required />
        <button type="submit">Initialize stock</button>
      </form>
      <form class="create" @submit.prevent="adjust">
        <input v-model="adjustForm.product_id" placeholder="Product ID" required />
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
            <td>{{ s.product_id.slice(0, 12) }}…</td>
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
import type { ApiData, Paginated, StockRow } from '~/types/api'

definePageMeta({ middleware: 'auth' })

const api = useApi()
const session = useSessionStore()
const items = ref<StockRow[]>([])
const loading = ref(false)
const error = ref('')
const form = reactive({ product_id: '', quantity: 10 })
const adjustForm = reactive({ product_id: '', direction: 'increase', amount: 1 })

async function load() {
  if (!session.businessId) return
  loading.value = true
  error.value = ''
  try {
    const res = await api.get<Paginated<StockRow> | ApiData<StockRow[]>>('/stock')
    items.value = Array.isArray((res as Paginated<StockRow>).data)
      ? (res as Paginated<StockRow>).data
      : ((res as ApiData<StockRow[]>).data || [])
  } catch (e: unknown) {
    error.value = e instanceof Error ? e.message : 'Load failed'
  } finally {
    loading.value = false
  }
}

async function initStock() {
  error.value = ''
  try {
    await api.post('/stock', {
      product_id: form.product_id,
      quantity: form.quantity,
    }, crypto.randomUUID())
    form.product_id = ''
    await load()
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
    await load()
  } catch (e: unknown) {
    error.value = e instanceof Error ? e.message : 'Adjust failed'
  }
}

watch(() => session.businessId, load, { immediate: true })
</script>

<style scoped>
.create { display: flex; flex-wrap: wrap; gap: 0.5rem; margin-bottom: 0.75rem; }
.err { color: #b00; } .warn { color: #a60; }
table { width: 100%; border-collapse: collapse; }
th, td { text-align: left; border-bottom: 1px solid #eee; padding: 0.4rem; }
</style>
