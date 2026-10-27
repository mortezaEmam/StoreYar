<template>
  <div>
    <div class="page-head"><h1>{{ t('purchaseReturn.title') }}</h1></div>
    <p v-if="!session.businessId" class="warn">
      {{ t('common.selectBusiness') }}
      <NuxtLink to="/business">{{ t('common.chooseBusiness') }}</NuxtLink>
    </p>
    <template v-else>
      <div class="card">
        <div class="card-title">{{ t('purchaseReturn.form') }}</div>
        <form class="form-col" @submit.prevent="submit">
          <label>{{ t('po.supplier') }}
            <select v-model="form.supplier_id" required>
              <option disabled value="">{{ t('common.select') }}</option>
              <option v-for="s in suppliers" :key="s.id" :value="s.id">{{ s.name }}</option>
            </select>
          </label>
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
          <label>{{ t('purchaseReturn.qty') }}
            <input v-model.number="form.quantity" type="number" min="1" required />
          </label>
          <label>{{ t('po.unitCost') }}
            <input v-model.number="form.unit_cost_amount" type="number" min="0" />
          </label>
          <label>{{ t('purchaseReturn.reason') }}
            <input v-model="form.reason" />
          </label>
          <button type="submit" :disabled="pending">{{ t('purchaseReturn.submit') }}</button>
        </form>
      </div>
      <p v-if="error" class="err">{{ error }}</p>
      <p v-if="lastDoc" class="ok">{{ t('purchaseReturn.ok') }}: {{ lastDoc }}</p>
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
const suppliers = ref<any[]>([])
const warehouses = ref<any[]>([])
const products = ref<any[]>([])
const pending = ref(false)
const error = ref('')
const lastDoc = ref('')
const form = reactive({
  supplier_id: '',
  warehouse_id: '',
  product_id: '',
  quantity: 1,
  unit_cost_amount: 0,
  reason: '',
})

async function load() {
  if (!session.businessId) return
  try {
    const [s, w, p] = await Promise.all([
      api.get('/suppliers'),
      api.get('/warehouses'),
      api.get('/products'),
    ])
    suppliers.value = api.unwrap(s) || []
    warehouses.value = api.unwrap(w) || []
    products.value = api.unwrap(p) || []
  } catch (e: any) {
    error.value = e.message || t('common.error')
  }
}
async function submit() {
  pending.value = true
  error.value = ''
  try {
    const res = await api.post('/purchase-returns', {
      supplier_id: form.supplier_id,
      warehouse_id: form.warehouse_id,
      reason: form.reason || null,
      lines: [{
        product_id: form.product_id,
        quantity: form.quantity,
        unit_cost_amount: form.unit_cost_amount || undefined,
      }],
    })
    const data = api.unwrap(res)
    lastDoc.value = data?.document_number || data?.id || 'OK'
    toast.push(t('purchaseReturn.ok'), 'ok')
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
