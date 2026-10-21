<template>
  <div>
    <div class="page-head"><h1>Products</h1></div>
    <p v-if="!session.businessId" class="warn">Select a business first. <NuxtLink to="/business">Choose business</NuxtLink></p>
    <template v-else>
      <div class="card">
        <div class="card-title">Add product</div>
        <form class="form-row" @submit.prevent="create">
          <label style="margin:0">Name
            <input v-model="form.name" required placeholder="Product name" />
          </label>
          <label style="margin:0">SKU
            <input v-model="form.sku" required placeholder="SKU-001" />
          </label>
          <label style="margin:0">Price (minor units)
            <input v-model.number="form.unit_price_amount" type="number" min="0" required />
          </label>
          <button type="submit" :disabled="pending">{{ pending ? 'Saving…' : 'Add' }}</button>
        </form>
        <p class="hint">Price is stored in minor units (e.g. 10000 IRR).</p>
      </div>
      <p v-if="error" class="err">{{ error }}</p>
      <div v-if="items.length" class="table-wrap">
        <table>
          <thead><tr><th>Name</th><th>SKU</th><th>Price</th><th>Status</th></tr></thead>
          <tbody>
            <tr v-for="p in items" :key="p.id">
              <td>{{ p.name }}</td>
              <td><code style="font-size:.8rem">{{ p.sku }}</code></td>
              <td>{{ p.unit_price_amount }} {{ p.currency || 'IRR' }}</td>
              <td><span class="badge" :class="p.status">{{ p.status || '—' }}</span></td>
            </tr>
          </tbody>
        </table>
      </div>
      <div v-else-if="!loading" class="empty">No products yet. Add your first product above.</div>
      <p v-if="loading" class="hint">Loading…</p>
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
const loading = ref(false)
const pending = ref(false)
const error = ref('')
const form = reactive({ name: '', sku: '', unit_price_amount: 1000, currency: 'IRR' })
async function load() {
  if (!session.businessId) return
  loading.value = true
  error.value = ''
  try {
    const res = await api.get<any>('/products')
    items.value = Array.isArray(res.data) ? res.data : []
  } catch (e: unknown) {
    error.value = e instanceof Error ? e.message : 'Load failed'
    toast.err(error.value)
  } finally {
    loading.value = false
  }
}
async function create() {
  pending.value = true
  error.value = ''
  try {
    await api.post('/products', { ...form }, crypto.randomUUID())
    toast.ok('Product created')
    form.name = ''
    form.sku = ''
    await load()
  } catch (e: unknown) {
    error.value = e instanceof Error ? e.message : 'Create failed'
    toast.err(error.value)
  } finally {
    pending.value = false
  }
}
watch(() => session.businessId, load, { immediate: true })
</script>
