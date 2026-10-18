<template>
  <div>
    <h1>Products</h1>
    <p v-if="!session.businessId" class="warn">Select a business first.</p>
    <template v-else>
      <form class="create" @submit.prevent="create">
        <input v-model="form.name" placeholder="Name" required />
        <input v-model="form.sku" placeholder="SKU" required />
        <input v-model.number="form.unit_price_amount" type="number" min="0" required />
        <button type="submit">Add product</button>
      </form>
      <p v-if="error" class="err">{{ error }}</p>
      <table v-if="items.length">
        <thead><tr><th>Name</th><th>SKU</th><th>Price</th></tr></thead>
        <tbody>
          <tr v-for="p in items" :key="p.id">
            <td>{{ p.name }}</td><td>{{ p.sku }}</td>
            <td>{{ p.unit_price_amount }} {{ p.currency }}</td>
          </tr>
        </tbody>
      </table>
      <p v-else-if="!loading">No products.</p>
    </template>
  </div>
</template>
<script setup lang="ts">
import type { ApiData, Paginated, Product } from '~/types/api'
definePageMeta({ middleware: 'auth' })
const api = useApi()
const session = useSessionStore()
const items = ref<Product[]>([])
const loading = ref(false)
const error = ref('')
const form = reactive({ name: '', sku: '', unit_price_amount: 1000, currency: 'IRR' })
async function load() {
  if (!session.businessId) return
  loading.value = true
  error.value = ''
  try {
    const res = await api.get<Paginated<Product> | ApiData<Product[]>>('/products')
    items.value = Array.isArray((res as Paginated<Product>).data)
      ? (res as Paginated<Product>).data
      : ((res as ApiData<Product[]>).data || [])
  } catch (e: unknown) {
    error.value = e instanceof Error ? e.message : 'Load failed'
  } finally {
    loading.value = false
  }
}
async function create() {
  error.value = ''
  try {
    await api.post('/products', { ...form }, crypto.randomUUID())
    form.name = ''
    form.sku = ''
    await load()
  } catch (e: unknown) {
    error.value = e instanceof Error ? e.message : 'Create failed'
  }
}
watch(() => session.businessId, load, { immediate: true })
</script>
<style scoped>
.create { display: flex; flex-wrap: wrap; gap: 0.5rem; margin-bottom: 1rem; }
.err { color: #b00; } .warn { color: #a60; }
table { width: 100%; border-collapse: collapse; }
th, td { text-align: left; border-bottom: 1px solid #eee; padding: 0.4rem; }
</style>
