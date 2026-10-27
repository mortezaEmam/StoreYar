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

      <div class="card" style="margin-top:1rem">
        <div class="card-title">{{ t('stock.receive') }}</div>
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

      <p v-if="error" class="err">{{ error }}</p>

      <div v-if="filteredItems.length" class="table-wrap" style="margin-top:1rem">
        <table>
          <thead>
            <tr>
              <th>{{ t('products.name') }}</th>
              <th>{{ t('products.sku') }}</th>
              <th>{{ t('stock.onHand') }}</th>
              <th>{{ t('stock.reserved') }}</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="s in filteredItems" :key="s.id || s.product_id">
              <td>{{ productName(s.product_id) }}</td>
              <td><code>{{ productSku(s.product_id) }}</code></td>
              <td><strong>{{ s.quantity }}</strong></td>
              <td>{{ s.reserved_quantity ?? 0 }}</td>
            </tr>
          </tbody>
        </table>
      </div>
      <div v-else-if="!loading && warehouseId" class="empty">{{ t('stock.empty') }}</div>

      <!-- Mobile cards -->
      <div class="mobile-cards" v-if="filteredItems.length">
        <div v-for="s in filteredItems" :key="'m-' + (s.id || s.product_id)" class="card stock-card">
          <div class="stock-card-name">{{ productName(s.product_id) }}</div>
          <div class="hint">{{ productSku(s.product_id) }}</div>
          <div class="stock-card-qty">{{ s.quantity }} <span class="hint">{{ t('stock.onHand') }}</span></div>
        </div>
      </div>

      <p class="hint links">
        <NuxtLink to="/stock-movements">{{ t('nav.stockMovements') }}</NuxtLink>
        ·
        <NuxtLink to="/opening-stock">{{ t('nav.openingStock') }}</NuxtLink>
        ·
        <NuxtLink to="/stock-transfers">{{ t('nav.stockTransfers') }}</NuxtLink>
        ·
        <NuxtLink to="/stock-counts">{{ t('nav.stockCounts') }}</NuxtLink>
      </p>
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
const loading = ref(false)
const pending = ref(false)
const error = ref('')
const recv = reactive({ product_id: '', quantity: 1 })

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
  const p = products.value.find((x: any) => x.id === id)
  return p?.name || id?.slice(0, 10) || '—'
}
function productSku(id: string) {
  const p = products.value.find((x: any) => x.id === id)
  return p?.sku || ''
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
    // Prefer warehouse-aware API (Phase 6)
    try {
      const res = await api.get(`/stock/by-warehouse?warehouse_id=${warehouseId.value}`)
      items.value = api.unwrap(res) || []
    } catch {
      // fallback legacy
      const res = await api.get('/stock')
      items.value = api.unwrap(res) || []
    }
  } catch (e: any) {
    error.value = e.message || t('common.error')
    items.value = []
  } finally {
    loading.value = false
  }
}

async function receive() {
  if (!warehouseId.value) return
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
.filters {
  display: flex; flex-wrap: wrap; gap: .75rem; align-items: flex-end;
}
.search { flex: 1; min-width: 8rem; }
.links { margin-top: 1rem; }
.mobile-cards { display: none; }
.stock-card-name { font-weight: 600; }
.stock-card-qty { font-size: 1.25rem; margin-top: .35rem; }

@media (max-width: 700px) {
  .table-wrap { display: none; }
  .mobile-cards {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: .5rem;
    margin-top: 1rem;
  }
  .mobile-stack {
    flex-direction: column !important;
    align-items: stretch !important;
  }
  .filters { flex-direction: column; align-items: stretch; }
}
</style>
