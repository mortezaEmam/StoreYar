<template>
  <div>
    <div class="page-head"><h1>{{ t('transfer.title') }}</h1></div>
    <p v-if="!session.businessId" class="warn">
      {{ t('common.selectBusiness') }}
      <NuxtLink to="/business">{{ t('common.chooseBusiness') }}</NuxtLink>
    </p>
    <template v-else>
      <div class="card">
        <div class="card-title">{{ t('transfer.form') }}</div>
        <form class="form-col" @submit.prevent="submit">
          <label>{{ t('transfer.from') }}
            <select v-model="form.from_warehouse_id" required>
              <option disabled value="">{{ t('common.select') }}</option>
              <option v-for="w in warehouses" :key="w.id" :value="w.id">{{ w.name || w.code }}</option>
            </select>
          </label>
          <label>{{ t('transfer.to') }}
            <select v-model="form.to_warehouse_id" required>
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
          <label>{{ t('transfer.qty') }}
            <input v-model.number="form.quantity" type="number" min="1" required />
          </label>
          <button type="submit" :disabled="pending">{{ t('transfer.submit') }}</button>
        </form>
      </div>
      <p v-if="error" class="err">{{ error }}</p>
      <p v-if="lastDoc" class="ok">{{ t('transfer.ok') }}: {{ lastDoc }}</p>
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
const pending = ref(false)
const error = ref('')
const lastDoc = ref('')
const form = reactive({ from_warehouse_id: '', to_warehouse_id: '', product_id: '', quantity: 1 })

async function load() {
  if (!session.businessId) return
  try {
    const [w, p] = await Promise.all([api.get('/warehouses'), api.get('/products')])
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
    const res = await api.post('/stock-transfers', {
      from_warehouse_id: form.from_warehouse_id,
      to_warehouse_id: form.to_warehouse_id,
      lines: [{ product_id: form.product_id, quantity: form.quantity }],
    })
    const data = api.unwrap(res)
    lastDoc.value = data?.document_number || data?.id || 'OK'
    toast.push(t('transfer.ok'), 'ok')
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
