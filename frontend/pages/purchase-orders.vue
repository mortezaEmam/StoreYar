<template>
  <div>
    <div class="page-head"><h1>{{ t('po.title') }}</h1></div>
    <p v-if="!session.businessId" class="warn">
      {{ t('common.selectBusiness') }}
      <NuxtLink to="/business">{{ t('common.chooseBusiness') }}</NuxtLink>
    </p>
    <template v-else>
      <div class="card">
        <div class="card-title">{{ t('po.create') }}</div>
        <form class="form-col" @submit.prevent="create">
          <label>{{ t('po.supplier') }}
            <select v-model="form.supplier_id" required>
              <option disabled value="">{{ t('common.select') }}</option>
              <option v-for="s in suppliers" :key="s.id" :value="s.id">{{ s.name }}</option>
            </select>
          </label>
          <label>{{ t('po.warehouse') }}
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
          <label>{{ t('po.qty') }}
            <input v-model.number="form.quantity" type="number" min="1" required />
          </label>
          <label>{{ t('po.unitCost') }}
            <input v-model.number="form.unit_cost_amount" type="number" min="0" />
          </label>
          <button type="submit" :disabled="pending">{{ pending ? t('common.loading') : t('common.add') }}</button>
        </form>
      </div>

      <p v-if="error" class="err">{{ error }}</p>

      <div v-if="items.length" class="table-wrap" style="margin-top:1rem">
        <table>
          <thead>
            <tr>
              <th>{{ t('po.doc') }}</th>
              <th>{{ t('po.status') }}</th>
              <th>{{ t('po.total') }}</th>
              <th>{{ t('po.actions') }}</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="po in items" :key="po.id">
              <td><code>{{ po.document_number || po.id?.slice(0, 8) }}</code></td>
              <td>{{ po.status }}</td>
              <td>{{ po.total_amount }}</td>
              <td>
                <button
                  v-if="po.status === 'ORDERED' || po.status === 'PARTIAL'"
                  type="button"
                  class="secondary"
                  :disabled="pending"
                  @click="receiveAll(po)"
                >{{ t('po.receive') }}</button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <div v-else-if="!loading" class="empty">{{ t('po.empty') }}</div>
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
const suppliers = ref<any[]>([])
const warehouses = ref<any[]>([])
const products = ref<any[]>([])
const loading = ref(false)
const pending = ref(false)
const error = ref('')
const form = reactive({
  supplier_id: '',
  warehouse_id: '',
  product_id: '',
  quantity: 1,
  unit_cost_amount: 0,
})

async function load() {
  if (!session.businessId) return
  loading.value = true
  error.value = ''
  try {
    const [po, sup, wh, pr] = await Promise.all([
      api.get('/purchase-orders'),
      api.get('/suppliers').catch(() => ({ data: [] })),
      api.get('/warehouses').catch(() => ({ data: [] })),
      api.get('/products').catch(() => ({ data: [] })),
    ])
    items.value = api.unwrap(po) || []
    suppliers.value = api.unwrap(sup) || []
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
    await api.post('/purchase-orders', {
      supplier_id: form.supplier_id,
      warehouse_id: form.warehouse_id,
      submit: true,
      lines: [{
        product_id: form.product_id,
        quantity: form.quantity,
        unit_cost_amount: form.unit_cost_amount || undefined,
      }],
    })
    toast.push(t('po.created'), 'ok')
    form.quantity = 1
    await load()
  } catch (e: any) {
    error.value = e.message || t('common.error')
  } finally {
    pending.value = false
  }
}

async function receiveAll(po: any) {
  pending.value = true
  error.value = ''
  try {
    const detail = api.unwrap(await api.get(`/purchase-orders/${po.id}`))
    const lines = (detail?.lines || []).map((l: any) => ({
      purchase_order_line_id: l.id,
      quantity: Math.max(0, (l.quantity_ordered || 0) - (l.quantity_received || 0)),
    })).filter((l: any) => l.quantity > 0)

    if (!lines.length) {
      toast.push(t('po.nothingToReceive'), 'hint')
      return
    }
    await api.post(`/purchase-orders/${po.id}/receive`, { lines })
    toast.push(t('po.received'), 'ok')
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
