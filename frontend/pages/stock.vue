<template>
  <div>
    <div class="page-head"><h1>{{ t('stock.title') }}</h1></div>
    <p v-if="!session.businessId" class="warn">
      {{ t('common.selectBusiness') }}
      <NuxtLink to="/business">{{ t('common.chooseBusiness') }}</NuxtLink>
    </p>
    <template v-else>
      <div class="card filters">
        <label>{{ t('pos.warehouse') }}
          <select v-model="warehouseId" @change="loadStock">
            <option disabled value="">{{ t('common.select') }}</option>
            <option v-for="w in warehouses" :key="w.id" :value="w.id">{{ w.name || w.code }}</option>
          </select>
        </label>
        <input v-model="search" :placeholder="t('stock.search')" class="search" />
        <button type="button" class="secondary" :disabled="loading" @click="loadStock">{{ t('common.refresh') }}</button>
      </div>

      <div class="tabs">
        <button type="button" :class="{ active: tab === 'receive' }" @click="tab = 'receive'">{{ t('stock.receive') }}</button>
        <button type="button" :class="{ active: tab === 'issue' }" @click="tab = 'issue'">{{ t('stock.issue') }}</button>
        <NuxtLink to="/stock-transfers" class="tab-link">{{ t('nav.stockTransfers') }}</NuxtLink>
        <NuxtLink to="/stock-movements" class="tab-link">{{ t('nav.stockMovements') }}</NuxtLink>
      </div>

      <div class="card" v-if="tab === 'receive'">
        <form class="form-row mobile-stack" @submit.prevent="receive">
          <label style="margin:0">{{ t('orders.product') }}
            <select v-model="recv.product_id" required>
              <option disabled value="">{{ t('common.select') }}</option>
              <option v-for="p in products" :key="p.id" :value="p.id">{{ p.name }} ({{ p.sku }})</option>
            </select>
          </label>
          <label style="margin:0">{{ t('stock.qty') }}
            <input v-model.number="recv.quantity" type="number" min="1" required style="width:5rem" />
          </label>
          <button type="submit" :disabled="pending || !warehouseId">{{ t('stock.receiveBtn') }}</button>
        </form>
      </div>

      <div class="card" v-else>
        <form class="form-row mobile-stack" @submit.prevent="issue">
          <label style="margin:0">{{ t('orders.product') }}
            <select v-model="iss.product_id" required>
              <option disabled value="">{{ t('common.select') }}</option>
              <option v-for="p in products" :key="p.id" :value="p.id">{{ p.name }} ({{ p.sku }})</option>
            </select>
          </label>
          <label style="margin:0">{{ t('stock.qty') }}
            <input v-model.number="iss.quantity" type="number" min="1" required style="width:5rem" />
          </label>
          <label style="margin:0">{{ t('stock.reason') }}
            <input v-model="iss.reason" :placeholder="t('stock.reasonPh')" />
          </label>
          <button type="submit" :disabled="pending || !warehouseId">{{ t('stock.issueBtn') }}</button>
        </form>
      </div>

      <p v-if="error" class="err">{{ error }}</p>

      <div v-if="filteredItems.length" class="table-wrap" style="margin-top:1rem">
        <table>
          <thead>
            <tr>
              <th>{{ t('products.name') }}</th>
              <th>{{ t('products.sku') }}</th>
              <th>{{ t('stock.onHand') }}</th>
              <th>{{ t('stock.reserved') }}</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="s in filteredItems" :key="s.id || s.product_id">
              <td>{{ productName(s.product_id) }}</td>
              <td><code>{{ productSku(s.product_id) }}</code></td>
              <td><strong>{{ s.quantity }}</strong></td>
              <td>{{ s.reserved_quantity ?? 0 }}</td>
              <td>
                <button type="button" class="secondary" @click="quickIssue(s)">−1</button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <div v-else-if="!loading && warehouseId" class="empty">{{ t('stock.empty') }}</div>

      <div class="mobile-cards" v-if="filteredItems.length">
        <div v-for="s in filteredItems" :key="'m-' + (s.id || s.product_id)" class="card stock-card">
          <div class="stock-card-name">{{ productName(s.product_id) }}</div>
          <div class="hint">{{ productSku(s.product_id) }}</div>
          <div class="stock-card-qty">{{ s.quantity }}</div>
          <div class="stock-card-actions">
            <button type="button" class="secondary" @click="prepReceive(s)">+</button>
            <button type="button" class="secondary" @click="quickIssue(s)">−</button>
          </div>
        </div>
      </div>
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

const warehouses = ref<any[]>([])
const products = ref<any[]>([])
const items = ref<any[]>([])
const warehouseId = ref('')
const search = ref('')
const tab = ref<'receive' | 'issue'>('receive')
const loading = ref(false)
const pending = ref(false)
const error = ref('')
const recv = reactive({ product_id: '', quantity: 1 })
const iss = reactive({ product_id: '', quantity: 1, reason: '' })

const filteredItems = computed(() => {
  const q = search.value.trim().toLowerCase()
  if (!q) return items.value
  return items.value.filter((s: any) => {
    const name = productName(s.product_id).toLowerCase()
    const sku = productSku(s.product_id).toLowerCase()
    return name.includes(q) || sku.includes(q)
  })
})

function productName(id: string) {
  return products.value.find((x: any) => x.id === id)?.name || id?.slice(0, 10) || '—'
}
function productSku(id: string) {
  return products.value.find((x: any) => x.id === id)?.sku || ''
}
function prepReceive(s: any) {
  tab.value = 'receive'
  recv.product_id = s.product_id
  recv.quantity = 1
}
async function quickIssue(s: any) {
  iss.product_id = s.product_id
  iss.quantity = 1
  iss.reason = 'Quick issue'
  tab.value = 'issue'
  await issue()
}

async function loadMeta() {
  if (!session.businessId) return
  try {
    const [w, p] = await Promise.all([api.get('/warehouses'), api.get('/products')])
    warehouses.value = api.unwrap(w) || []
    products.value = api.unwrap(p) || []
    if (!warehouseId.value && warehouses.value.length) {
      const def = warehouses.value.find((x: any) => x.is_default) || warehouses.value[0]
      warehouseId.value = def.id
    }
  } catch (e: any) {
    error.value = e.message || t('common.error')
  }
}

async function loadStock() {
  if (!session.businessId || !warehouseId.value) return
  loading.value = true
  error.value = ''
  try {
    try {
      items.value = api.unwrap(await api.get(`/stock/by-warehouse?warehouse_id=${warehouseId.value}`)) || []
    } catch {
      items.value = api.unwrap(await api.get('/stock')) || []
    }
  } catch (e: any) {
    error.value = e.message || t('common.error')
    items.value = []
  } finally {
    loading.value = false
  }
}

async function receive() {
  pending.value = true
  error.value = ''
  try {
    await api.post('/stock/receive', {
      warehouse_id: warehouseId.value,
      product_id: recv.product_id,
      quantity: recv.quantity,
    })
    toast.ok(t('stock.received'))
    recv.quantity = 1
    await loadStock()
  } catch (e: any) {
    error.value = e.message || t('common.error')
    toast.err(error.value)
  } finally {
    pending.value = false
  }
}

async function issue() {
  if (!iss.product_id) return
  pending.value = true
  error.value = ''
  try {
    await api.post('/stock/issue', {
      warehouse_id: warehouseId.value,
      product_id: iss.product_id,
      quantity: iss.quantity,
      reason: iss.reason || 'Manual issue',
    })
    toast.ok(t('stock.issued'))
    iss.quantity = 1
    await loadStock()
  } catch (e: any) {
    error.value = e.message || t('common.error')
    toast.err(error.value)
  } finally {
    pending.value = false
  }
}

onMounted(async () => {
  await loadMeta()
  await loadStock()
})
watch(() => session.businessId, async () => {
  await loadMeta()
  await loadStock()
})
</script>

<style scoped>
.filters { display: flex; flex-wrap: wrap; gap: .75rem; align-items: flex-end; }
.search { flex: 1; min-width: 8rem; }
.tabs { display: flex; flex-wrap: wrap; gap: .5rem; margin: 1rem 0 .5rem; align-items: center; }
.tabs button.active { font-weight: 700; }
.tab-link { font-size: .9rem; }
.mobile-cards { display: none; }
.stock-card-name { font-weight: 600; }
.stock-card-qty { font-size: 1.25rem; margin: .25rem 0; }
.stock-card-actions { display: flex; gap: .35rem; }
@media (max-width: 700px) {
  .table-wrap { display: none; }
  .mobile-cards {
    display: grid; grid-template-columns: 1fr 1fr; gap: .5rem; margin-top: 1rem;
  }
  .mobile-stack { flex-direction: column !important; align-items: stretch !important; }
  .filters { flex-direction: column; align-items: stretch; }
}
</style>
