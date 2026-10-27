<template>
  <div>
    <div class="page-head"><h1>{{ t('stockCount.title') }}</h1></div>
    <p v-if="!session.businessId" class="warn">
      {{ t('common.selectBusiness') }}
      <NuxtLink to="/business">{{ t('common.chooseBusiness') }}</NuxtLink>
    </p>
    <template v-else>
      <div class="card">
        <div class="card-title">{{ t('stockCount.create') }}</div>
        <form class="form-col" @submit.prevent="create">
          <label>{{ t('pos.warehouse') }}
            <select v-model="form.warehouse_id" required>
              <option disabled value="">{{ t('common.select') }}</option>
              <option v-for="w in warehouses" :key="w.id" :value="w.id">{{ w.name || w.code }}</option>
            </select>
          </label>
          <label>{{ t('orders.product') }}
            <select v-model="form.product_id" required>
              <option disabled value="">{{ t('common.select') }}</option>
              <option v-for="p in products" :key="p.id" :value="p.id">{{ p.name }} ({{ p.sku }})</option>
            </select>
          </label>
          <label>{{ t('stockCount.counted') }}
            <input v-model.number="form.counted_quantity" type="number" min="0" required />
          </label>
          <button type="submit" :disabled="pending">{{ t('stockCount.saveDraft') }}</button>
        </form>
      </div>
      <p v-if="error" class="err">{{ error }}</p>
      <div v-if="items.length" class="table-wrap" style="margin-top:1rem">
        <table>
          <thead>
            <tr>
              <th>{{ t('stockCount.doc') }}</th>
              <th>{{ t('stockCount.status') }}</th>
              <th>{{ t('stockCount.actions') }}</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="c in items" :key="c.id">
              <td><code>{{ c.document_number || c.id?.slice(0, 8) }}</code></td>
              <td>{{ c.status }}</td>
              <td>
                <button
                  v-if="c.status === 'DRAFT'"
                  type="button"
                  class="secondary"
                  :disabled="pending"
                  @click="post(c.id)"
                >{{ t('stockCount.post') }}</button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <div v-else-if="!loading" class="empty">{{ t('stockCount.empty') }}</div>
    </template>
  </div>
</template>
<script setup lang="ts">
import { useSessionStore } from '~/stores/session'
definePageMeta({ middleware: 'auth' })
const { t } = useI18n()
const api = useApi()
const session = useSessionStore()
const toast = useToast()
const items = ref<any[]>([])
const warehouses = ref<any[]>([])
const products = ref<any[]>([])
const loading = ref(false)
const pending = ref(false)
const error = ref('')
const form = reactive({ warehouse_id: '', product_id: '', counted_quantity: 0 })

async function load() {
  if (!session.businessId) return
  loading.value = true
  error.value = ''
  try {
    const [sc, wh, pr] = await Promise.all([
      api.get('/stock-counts'),
      api.get('/warehouses'),
      api.get('/products'),
    ])
    items.value = api.unwrap(sc) || []
    warehouses.value = api.unwrap(wh) || []
    products.value = api.unwrap(pr) || []
  } catch (e: any) {
    error.value = e.message || t('common.error')
  } finally {
    loading.value = false
  }
}
async function create() {
  pending.value = true
  error.value = ''
  try {
    await api.post('/stock-counts', {
      warehouse_id: form.warehouse_id,
      lines: [{ product_id: form.product_id, counted_quantity: form.counted_quantity }],
    })
    toast.push(t('stockCount.created'), 'ok')
    await load()
  } catch (e: any) {
    error.value = e.message || t('common.error')
  } finally {
    pending.value = false
  }
}
async function post(id: string) {
  pending.value = true
  error.value = ''
  try {
    await api.post(`/stock-counts/${id}/post`)
    toast.push(t('stockCount.posted'), 'ok')
    await load()
  } catch (e: any) {
    error.value = e.message || t('common.error')
  } finally {
    pending.value = false
  }
}
onMounted(load)
watch(() => session.businessId, load)
</script>
<style scoped>
.form-col { display: flex; flex-direction: column; gap: .75rem; max-width: 28rem; }
</style>
