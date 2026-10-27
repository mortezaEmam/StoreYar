<template>
  <div>
    <div class="page-head"><h1>{{ t('movements.title') }}</h1></div>
    <p v-if="!session.businessId" class="warn">
      {{ t('common.selectBusiness') }}
      <NuxtLink to="/business">{{ t('common.chooseBusiness') }}</NuxtLink>
    </p>
    <template v-else>
      <div class="card">
        <form class="form-row" @submit.prevent="load">
          <label style="margin:0">{{ t('pos.warehouse') }}
            <select v-model="filters.warehouse_id">
              <option value="">{{ t('common.all') || 'All' }}</option>
              <option v-for="w in warehouses" :key="w.id" :value="w.id">{{ w.name || w.code }}</option>
            </select>
          </label>
          <label style="margin:0">{{ t('orders.product') }}
            <select v-model="filters.product_id">
              <option value="">{{ t('common.all') || 'All' }}</option>
              <option v-for="p in products" :key="p.id" :value="p.id">{{ p.name }} ({{ p.sku }})</option>
            </select>
          </label>
          <label style="margin:0">From
            <input v-model="filters.from" type="date" />
          </label>
          <label style="margin:0">To
            <input v-model="filters.to" type="date" />
          </label>
          <button type="submit" :disabled="loading">{{ t('common.refresh') }}</button>
        </form>
      </div>

      <p v-if="error" class="err">{{ error }}</p>

      <div v-if="items.length" class="table-wrap" style="margin-top:1rem">
        <table>
          <thead>
            <tr>
              <th>{{ t('movements.when') }}</th>
              <th>{{ t('movements.type') }}</th>
              <th>{{ t('movements.dir') }}</th>
              <th>{{ t('nav.warehouses') }}</th>
              <th>{{ t('orders.product') }}</th>
              <th>{{ t('movements.delta') }}</th>
              <th>{{ t('movements.after') }}</th>
              <th>{{ t('movements.doc') }}</th>
              <th>{{ t('movements.reason') }}</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="m in items" :key="m.id">
              <td>{{ formatWhen(m.occurred_at) }}</td>
              <td>{{ m.movement_type }}</td>
              <td>
                <span :class="m.direction === 'IN' || m.quantity_delta > 0 ? 'ok' : 'err'">
                  {{ m.direction || (m.quantity_delta > 0 ? 'IN' : 'OUT') }}
                </span>
              </td>
              <td>{{ m.warehouse_code || shortId(m.warehouse_id) }}</td>
              <td>{{ m.product_name || productLabel(m.product_id) }} <span v-if="m.sku" class="hint">({{ m.sku }})</span></td>
              <td>{{ m.quantity_delta }}</td>
              <td>{{ m.quantity_after }}</td>
              <td>
                <span v-if="m.document_type">{{ m.document_type }}</span>
                <code v-if="m.document_id" style="font-size:.7rem">{{ shortId(m.document_id) }}</code>
              </td>
              <td>{{ m.reason }}</td>
            </tr>
          </tbody>
        </table>
      </div>
      <div v-else-if="!loading" class="empty">{{ t('movements.empty') }}</div>
      <p class="hint">{{ t('movements.hint') }}</p>
    </template>
  </div>
</template>
<script setup lang="ts">
import { useSessionStore } from '~/stores/session'
definePageMeta({ middleware: 'auth' })
const { t } = useI18n()
const api = useApi()
const session = useSessionStore()
const items = ref<any[]>([])
const warehouses = ref<any[]>([])
const products = ref<any[]>([])
const loading = ref(false)
const error = ref('')
const filters = reactive({
  warehouse_id: '',
  product_id: '',
  from: new Date(Date.now() - 7 * 864e5).toISOString().slice(0, 10),
  to: new Date().toISOString().slice(0, 10),
})

function shortId(id?: string) {
  return id ? id.slice(0, 10) + '…' : ''
}
function formatWhen(iso?: string) {
  if (!iso) return '—'
  try { return new Date(iso).toLocaleString() } catch { return iso }
}
function productLabel(id?: string) {
  const p = products.value.find((x: any) => x.id === id)
  return p ? `${p.name}` : shortId(id)
}

async function loadMeta() {
  try {
    const [w, p] = await Promise.all([
      api.get('/warehouses'),
      api.get('/products'),
    ])
    warehouses.value = api.unwrap(w) || []
    products.value = api.unwrap(p) || []
  } catch { /* ignore */ }
}

async function load() {
  if (!session.businessId) return
  loading.value = true
  error.value = ''
  const q = new URLSearchParams()
  if (filters.warehouse_id) q.set('warehouse_id', filters.warehouse_id)
  if (filters.product_id) q.set('product_id', filters.product_id)
  if (filters.from) q.set('from', filters.from)
  if (filters.to) q.set('to', filters.to)
  q.set('limit', '100')
  const qs = q.toString()

  try {
    // Prefer reporting endpoint (has product/warehouse names)
    let res: any
    try {
      res = await api.get(`/reports/stock-movements?${qs}`)
    } catch {
      res = await api.get(`/stock/movements?${qs}`)
    }
    items.value = api.unwrap(res) || []
  } catch (e: any) {
    error.value = e.message || t('common.error')
    items.value = []
  } finally {
    loading.value = false
  }
}

onMounted(async () => {
  await loadMeta()
  await load()
})
watch(() => session.businessId, async () => {
  await loadMeta()
  await load()
})
</script>
