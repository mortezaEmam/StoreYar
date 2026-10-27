<template>
  <div>
    <div class="page-head"><h1>{{ t('pos.title') }}</h1></div>
    <p v-if="!session.businessId" class="warn">
      {{ t('common.selectBusiness') }}
      <NuxtLink to="/business">{{ t('common.chooseBusiness') }}</NuxtLink>
    </p>
    <template v-else>
      <div class="pos-grid">
        <div class="card">
          <div class="card-title">{{ t('pos.session') }}</div>
          <div v-if="sessionRow" class="ok">
            {{ t('pos.openSession') }}: <code>{{ sessionRow.id?.slice(0, 10) }}…</code>
            · {{ sessionRow.status }}
          </div>
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
          <button
            v-if="sessionRow && sessionRow.status === 'OPEN'"
            type="button"
            class="secondary"
            style="margin-top:.5rem"
            :disabled="pending"
            @click="closeSession"
          >{{ t('pos.close') }}</button>
        </div>

        <div class="card" v-if="sessionRow?.status === 'OPEN'">
          <div class="card-title">{{ t('pos.scan') }}</div>
          <form class="form-row" @submit.prevent="addByBarcode">
            <label style="margin:0">{{ t('pos.barcode') }}
              <input ref="barcodeInput" v-model="barcode" autofocus />
            </label>
            <label style="margin:0">{{ t('pos.qty') }}
              <input v-model.number="qty" type="number" min="1" style="width:5rem" />
            </label>
            <button type="submit" :disabled="pending">{{ t('common.add') }}</button>
          </form>
          <p class="hint">{{ t('pos.scanHint') }}</p>
        </div>
      </div>

      <div class="card" v-if="cart.length" style="margin-top:1rem">
        <div class="card-title">{{ t('pos.cart') }}</div>
        <div class="table-wrap">
          <table>
            <thead>
              <tr>
                <th>{{ t('products.name') }}</th>
                <th>{{ t('pos.qty') }}</th>
                <th>{{ t('products.price') }}</th>
                <th>{{ t('pos.lineTotal') }}</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(line, i) in cart" :key="i">
                <td>{{ line.name || line.product_id }}</td>
                <td>{{ line.quantity }}</td>
                <td>{{ line.unit_price_amount }}</td>
                <td>{{ line.quantity * line.unit_price_amount }}</td>
                <td><button type="button" class="secondary" @click="cart.splice(i, 1)">×</button></td>
              </tr>
            </tbody>
          </table>
        </div>
        <div class="pos-checkout">
          <strong>{{ t('pos.total') }}: {{ cartTotal }}</strong>
          <label>{{ t('pos.payment') }}
            <select v-model="paymentMethod">
              <option value="CASH">{{ t('pos.cash') }}</option>
              <option value="CARD">{{ t('pos.card') }}</option>
              <option value="CREDIT">{{ t('pos.credit') }}</option>
            </select>
          </label>
          <button type="button" :disabled="pending || !cart.length" @click="checkout">
            {{ t('pos.checkout') }}
          </button>
        </div>
      </div>

      <p v-if="error" class="err">{{ error }}</p>
      <p v-if="lastSale" class="ok">{{ t('pos.saleOk') }}: {{ lastSale }}</p>
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
const sessionRow = ref<any>(null)
const cart = ref<any[]>([])
const barcode = ref('')
const qty = ref(1)
const paymentMethod = ref('CASH')
const pending = ref(false)
const error = ref('')
const lastSale = ref('')
const barcodeInput = ref<HTMLInputElement | null>(null)
const openForm = reactive({ warehouse_id: '', opening_float_amount: 0 })

const cartTotal = computed(() =>
  cart.value.reduce((s, l) => s + l.quantity * l.unit_price_amount, 0),
)

async function loadWarehouses() {
  try {
    const res = await api.get('/warehouses')
    warehouses.value = api.unwrap(res) || []
    if (warehouses.value.length && !openForm.warehouse_id) {
      openForm.warehouse_id = warehouses.value[0].id
    }
  } catch {
    warehouses.value = []
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
    toast.push(t('pos.opened'), 'ok')
  } catch (e: any) {
    error.value = e.message || t('common.error')
  } finally {
    pending.value = false
  }
}

async function closeSession() {
  if (!sessionRow.value) return
  pending.value = true
  try {
    await api.post(`/pos/sessions/${sessionRow.value.id}/close`, {
      closing_count_amount: cartTotal.value,
    })
    sessionRow.value = null
    cart.value = []
    toast.push(t('pos.closed'), 'ok')
  } catch (e: any) {
    error.value = e.message || t('common.error')
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
    const existing = cart.value.find((l) => l.product_id === product.id)
    if (existing) {
      existing.quantity += qty.value
    } else {
      cart.value.push({
        product_id: product.id,
        barcode: code,
        name: product.name,
        quantity: qty.value,
        unit_price_amount: product.unit_price_amount || 0,
      })
    }
    barcode.value = ''
    qty.value = 1
    barcodeInput.value?.focus()
  } catch (e: any) {
    error.value = e.message || t('pos.barcodeNotFound')
  } finally {
    pending.value = false
  }
}

async function checkout() {
  if (!sessionRow.value || !cart.value.length) return
  pending.value = true
  error.value = ''
  try {
    const res = await api.post('/pos/sale', {
      pos_session_id: sessionRow.value.id,
      payment_method: paymentMethod.value,
      lines: cart.value.map((l) => ({
        product_id: l.product_id,
        quantity: l.quantity,
        unit_price_amount: l.unit_price_amount,
      })),
    })
    const sale = api.unwrap(res)
    lastSale.value = sale?.document_number || sale?.id || 'OK'
    cart.value = []
    toast.push(t('pos.saleOk'), 'ok')
  } catch (e: any) {
    error.value = e.message || t('common.error')
  } finally {
    pending.value = false
  }
}

onMounted(() => {
  if (session.businessId) loadWarehouses()
})
watch(() => session.businessId, (v) => { if (v) loadWarehouses() })
</script>

<style scoped>
.pos-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
@media (max-width: 800px) { .pos-grid { grid-template-columns: 1fr; } }
.pos-checkout {
  display: flex; flex-wrap: wrap; gap: 1rem; align-items: center;
  margin-top: 1rem; padding-top: .75rem; border-top: 1px solid #eee;
}
</style>
