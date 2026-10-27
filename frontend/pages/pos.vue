<template>
  <div class="pos-page">
    <div class="page-head">
      <h1>{{ t('pos.title') }}</h1>
      <NuxtLink to="/pos-sales" class="hint">{{ t('nav.posSales') }} →</NuxtLink>
    </div>

    <p v-if="!session.businessId" class="warn">
      {{ t('common.selectBusiness') }}
      <NuxtLink to="/business">{{ t('common.chooseBusiness') }}</NuxtLink>
    </p>

    <template v-else>
      <!-- Session bar -->
      <div class="card session-bar">
        <template v-if="sessionRow?.status === 'OPEN'">
          <div>
            <strong class="ok">{{ t('pos.openSession') }}</strong>
            <code class="hint">{{ sessionRow.id }}</code>
            <span class="hint">· {{ warehouseLabel(sessionRow.warehouse_id) }}</span>
          </div>
          <div class="session-stats">
            <span>{{ t('pos.sessionSales') }}: <strong>{{ sessionSalesCount }}</strong></span>
            <span>{{ t('pos.sessionTotal') }}: <strong>{{ sessionSalesTotal }}</strong></span>
          </div>
          <div class="close-row">
            <label>{{ t('pos.countedCash') }}
              <input v-model.number="closingCount" type="number" min="0" style="width:8rem" />
            </label>
            <button type="button" class="secondary" :disabled="pending" @click="closeSession">
              {{ t('pos.close') }}
            </button>
          </div>
        </template>
        <form v-else class="form-row" @submit.prevent="openSession">
          <label style="margin:0">{{ t('pos.warehouse') }}
            <select v-model="openForm.warehouse_id" required>
              <option disabled value="">{{ t('common.select') }}</option>
              <option v-for="w in warehouses" :key="w.id" :value="w.id">{{ w.name || w.code }}</option>
            </select>
          </label>
          <label style="margin:0">{{ t('pos.openingFloat') }}
            <input v-model.number="openForm.opening_float_amount" type="number" min="0" />
          </label>
          <button type="submit" :disabled="pending">{{ t('pos.open') }}</button>
        </form>
      </div>

      <div v-if="sessionRow?.status === 'OPEN'" class="pos-main">
        <!-- Left: scan / pick -->
        <div class="pos-left">
          <div class="card">
            <div class="card-title">{{ t('pos.scan') }}</div>
            <form class="form-row" @submit.prevent="addByBarcode">
              <label style="margin:0;flex:1">{{ t('pos.barcode') }}
                <input ref="barcodeInput" v-model="barcode" autofocus autocomplete="off" />
              </label>
              <label style="margin:0">{{ t('pos.qty') }}
                <input v-model.number="qty" type="number" min="1" style="width:4.5rem" />
              </label>
              <button type="submit" :disabled="pending">{{ t('common.add') }}</button>
            </form>
            <p class="hint">{{ t('pos.scanHint') }}</p>
          </div>

          <div class="card">
            <div class="card-title">{{ t('pos.pickProduct') }}</div>
            <input v-model="productFilter" :placeholder="t('pos.searchProduct')" class="search-input" />
            <div class="product-list">
              <button
                v-for="p in filteredProducts"
                :key="p.id"
                type="button"
                class="product-item"
                @click="addProduct(p)"
              >
                <span>{{ p.name }}</span>
                <span class="hint">{{ p.sku }}</span>
                <strong>{{ p.unit_price_amount }}</strong>
              </button>
              <div v-if="!filteredProducts.length" class="empty">{{ t('pos.noProducts') }}</div>
            </div>
          </div>
        </div>

        <!-- Right: cart + pay -->
        <div class="pos-right">
          <div class="card cart-card">
            <div class="card-title">{{ t('pos.cart') }}</div>
            <div v-if="!cart.length" class="empty">{{ t('pos.cartEmpty') }}</div>
            <div v-else class="cart-lines">
              <div v-for="(line, i) in cart" :key="i" class="cart-line">
                <div class="cart-name">{{ line.name || line.product_id }}</div>
                <div class="cart-controls">
                  <button type="button" class="secondary qty-btn" @click="changeQty(i, -1)">−</button>
                  <span>{{ line.quantity }}</span>
                  <button type="button" class="secondary qty-btn" @click="changeQty(i, 1)">+</button>
                </div>
                <div class="cart-price">{{ line.quantity * line.unit_price_amount }}</div>
                <button type="button" class="secondary" @click="cart.splice(i, 1)">×</button>
              </div>
            </div>

            <div class="cart-footer">
              <div class="total-row">
                <span>{{ t('pos.total') }}</span>
                <strong class="total-value">{{ cartTotal }}</strong>
              </div>
              <label>{{ t('pos.payment') }}
                <select v-model="paymentMethod">
                  <option value="CASH">{{ t('pos.cash') }}</option>
                  <option value="CARD">{{ t('pos.card') }}</option>
                  <option value="CREDIT">{{ t('pos.credit') }}</option>
                  <option value="MIXED">{{ t('pos.mixed') }}</option>
                </select>
              </label>
              <label v-if="paymentMethod === 'CREDIT'">{{ t('nav.customers') }}
                <select v-model="customerId">
                  <option value="">{{ t('common.select') }}</option>
                  <option v-for="c in customers" :key="c.id" :value="c.id">
                    {{ c.name }} ({{ c.outstanding_balance_amount ?? 0 }})
                  </option>
                </select>
              </label>
              <button
                type="button"
                class="checkout-btn"
                :disabled="pending || !cart.length || (paymentMethod === 'CREDIT' && !customerId)"
                @click="checkout"
              >
                {{ t('pos.checkout') }}
              </button>
            </div>
          </div>

          <div v-if="lastReceipt" class="card receipt">
            <div class="card-title">{{ t('pos.receipt') }}</div>
            <p><strong>{{ lastReceipt.document_number || '—' }}</strong></p>
            <p class="hint">ID: <code>{{ lastReceipt.id }}</code></p>
            <p>{{ t('pos.total') }}: <strong>{{ lastReceipt.total_amount }}</strong> {{ lastReceipt.currency }}</p>
            <p>{{ t('pos.payment') }}: {{ lastReceipt.payment_method }}</p>
            <NuxtLink
              :to="{ path: '/pos-returns', query: { sale_id: lastReceipt.id } }"
              class="hint"
            >{{ t('pos.gotoReturn') }}</NuxtLink>
          </div>
        </div>
      </div>

      <p v-if="error" class="err">{{ error }}</p>
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
const customers = ref<any[]>([])
const sessionRow = ref<any>(null)
const cart = ref<any[]>([])
const barcode = ref('')
const qty = ref(1)
const paymentMethod = ref('CASH')
const customerId = ref('')
const productFilter = ref('')
const pending = ref(false)
const error = ref('')
const lastReceipt = ref<any>(null)
const closingCount = ref(0)
const sessionSales = ref<any[]>([])
const barcodeInput = ref<HTMLInputElement | null>(null)
const openForm = reactive({ warehouse_id: '', opening_float_amount: 0 })

const cartTotal = computed(() =>
  cart.value.reduce((s, l) => s + l.quantity * l.unit_price_amount, 0),
)
const sessionSalesCount = computed(() => sessionSales.value.length)
const sessionSalesTotal = computed(() =>
  sessionSales.value.reduce((s, x) => s + (Number(x.total_amount) || 0), 0),
)
const filteredProducts = computed(() => {
  const q = productFilter.value.trim().toLowerCase()
  const list = products.value.slice(0, 80)
  if (!q) return list
  return products.value
    .filter((p: any) =>
      (p.name || '').toLowerCase().includes(q) ||
      (p.sku || '').toLowerCase().includes(q),
    )
    .slice(0, 40)
})

function warehouseLabel(id?: string) {
  const w = warehouses.value.find((x: any) => x.id === id)
  return w ? (w.name || w.code) : (id?.slice(0, 8) || '')
}

function addProduct(p: any) {
  const existing = cart.value.find((l) => l.product_id === p.id)
  if (existing) existing.quantity += qty.value
  else {
    cart.value.push({
      product_id: p.id,
      name: p.name,
      quantity: qty.value,
      unit_price_amount: p.unit_price_amount || 0,
    })
  }
  qty.value = 1
  barcodeInput.value?.focus()
}

function changeQty(i: number, d: number) {
  const line = cart.value[i]
  if (!line) return
  line.quantity = Math.max(1, line.quantity + d)
}

async function loadWarehouses() {
  try {
    warehouses.value = api.unwrap(await api.get('/warehouses')) || []
    if (warehouses.value.length && !openForm.warehouse_id) {
      openForm.warehouse_id = warehouses.value[0].id
    }
  } catch { warehouses.value = [] }
}

async function loadProducts() {
  try {
    products.value = api.unwrap(await api.get('/products')) || []
  } catch { products.value = [] }
}

async function loadCustomers() {
  try {
    customers.value = api.unwrap(await api.get('/customers')) || []
  } catch { customers.value = [] }
}

async function loadCurrentSession() {
  try {
    const res = await api.get('/pos/sessions/current')
    const data = api.unwrap(res)
    if (data && data.status === 'OPEN') {
      sessionRow.value = data
      await loadSessionSales()
    }
  } catch { /* no open session */ }
}

async function loadSessionSales() {
  if (!sessionRow.value?.id) {
    sessionSales.value = []
    return
  }
  try {
    const res = await api.get(`/pos/sales?session_id=${sessionRow.value.id}&limit=100`)
    sessionSales.value = api.unwrap(res) || []
    const expected =
      (Number(sessionRow.value.opening_float_amount) || 0) +
      sessionSales.value
        .filter((s: any) => s.payment_method === 'CASH')
        .reduce((sum: number, s: any) => sum + (Number(s.total_amount) || 0), 0)
    closingCount.value = expected
  } catch {
    sessionSales.value = []
  }
}

async function openSession() {
  pending.value = true
  error.value = ''
  try {
    const res = await api.post('/pos/sessions/open', {
      warehouse_id: openForm.warehouse_id,
      opening_float_amount: openForm.opening_float_amount || 0,
    })
    sessionRow.value = api.unwrap(res)
    cart.value = []
    lastReceipt.value = null
    await loadSessionSales()
    toast.ok(t('pos.opened'))
    nextTick(() => barcodeInput.value?.focus())
  } catch (e: any) {
    error.value = e.message || t('common.error')
    toast.err(error.value)
  } finally {
    pending.value = false
  }
}

async function closeSession() {
  if (!sessionRow.value) return
  pending.value = true
  error.value = ''
  try {
    await api.post(`/pos/sessions/${sessionRow.value.id}/close`, {
      closing_count_amount: closingCount.value || 0,
    })
    toast.ok(t('pos.closed'))
    sessionRow.value = null
    cart.value = []
    sessionSales.value = []
    lastReceipt.value = null
  } catch (e: any) {
    error.value = e.message || t('common.error')
    toast.err(error.value)
  } finally {
    pending.value = false
  }
}

async function addByBarcode() {
  const code = barcode.value.trim()
  if (!code) return
  pending.value = true
  error.value = ''
  try {
    const res = await api.get(`/barcodes/lookup?barcode=${encodeURIComponent(code)}`)
    const data = api.unwrap(res)
    const product = data?.product || data
    if (!product?.id) throw new Error(t('pos.barcodeNotFound'))
    addProduct({
      id: product.id,
      name: product.name,
      unit_price_amount: product.unit_price_amount || 0,
    })
    barcode.value = ''
  } catch (e: any) {
    error.value = e.message || t('pos.barcodeNotFound')
    toast.err(error.value)
  } finally {
    pending.value = false
    nextTick(() => barcodeInput.value?.focus())
  }
}

async function checkout() {
  if (!sessionRow.value || !cart.value.length) return
  pending.value = true
  error.value = ''
  try {
    const body: any = {
      pos_session_id: sessionRow.value.id,
      payment_method: paymentMethod.value,
      lines: cart.value.map((l) => ({
        product_id: l.product_id,
        quantity: l.quantity,
        unit_price_amount: l.unit_price_amount,
      })),
    }
    if (paymentMethod.value === 'CREDIT' && customerId.value) {
      body.customer_id = customerId.value
    }
    const res = await api.post('/pos/sale', body)
    lastReceipt.value = api.unwrap(res)
    cart.value = []
    toast.ok(t('pos.saleOk'))
    await loadSessionSales()
    nextTick(() => barcodeInput.value?.focus())
  } catch (e: any) {
    error.value = e.message || t('common.error')
    toast.err(error.value)
  } finally {
    pending.value = false
  }
}

async function boot() {
  if (!session.businessId) return
  await Promise.all([loadWarehouses(), loadProducts(), loadCustomers()])
  await loadCurrentSession()
}

onMounted(boot)
watch(() => session.businessId, boot)
</script>

<style scoped>
.page-head { display: flex; align-items: baseline; gap: 1rem; }
.session-bar {
  display: flex; flex-wrap: wrap; gap: 1rem; align-items: center;
  justify-content: space-between; margin-bottom: 1rem;
}
.session-stats { display: flex; gap: 1.25rem; }
.close-row { display: flex; gap: .5rem; align-items: flex-end; }
.pos-main {
  display: grid;
  grid-template-columns: 1.1fr 0.9fr;
  gap: 1rem;
  align-items: start;
}
@media (max-width: 900px) {
  .pos-main { grid-template-columns: 1fr; }
}
.pos-left, .pos-right { display: flex; flex-direction: column; gap: 1rem; }
.search-input { width: 100%; margin-bottom: .5rem; }
.product-list {
  max-height: 18rem; overflow: auto;
  display: flex; flex-direction: column; gap: .25rem;
}
.product-item {
  display: grid;
  grid-template-columns: 1fr auto auto;
  gap: .5rem;
  text-align: start;
  padding: .45rem .6rem;
  background: transparent;
  border: 1px solid rgba(0,0,0,.08);
  border-radius: 6px;
  cursor: pointer;
}
.product-item:hover { background: rgba(0,0,0,.03); }
.cart-lines { display: flex; flex-direction: column; gap: .35rem; max-height: 16rem; overflow: auto; }
.cart-line {
  display: grid;
  grid-template-columns: 1fr auto auto auto;
  gap: .5rem;
  align-items: center;
  padding: .35rem 0;
  border-bottom: 1px solid rgba(0,0,0,.06);
}
.cart-controls { display: flex; align-items: center; gap: .35rem; }
.qty-btn { min-width: 1.75rem; padding: .15rem .4rem; }
.cart-footer {
  margin-top: .75rem; padding-top: .75rem;
  border-top: 1px solid rgba(0,0,0,.08);
  display: flex; flex-direction: column; gap: .65rem;
}
.total-row { display: flex; justify-content: space-between; font-size: 1.15rem; }
.total-value { font-size: 1.4rem; }
.checkout-btn {
  padding: .75rem 1rem;
  font-size: 1.05rem;
  font-weight: 600;
}
.receipt { background: rgba(2, 122, 72, .06); }
</style>
