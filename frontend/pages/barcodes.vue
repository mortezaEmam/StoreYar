<template>
  <div>
    <div class="page-head"><h1>{{ t('barcodes.title') }}</h1></div>
    <p v-if="!session.businessId" class="warn">
      {{ t('common.selectBusiness') }}
      <NuxtLink to="/business">{{ t('common.chooseBusiness') }}</NuxtLink>
    </p>
    <template v-else>
      <div class="card">
        <div class="card-title">{{ t('barcodes.add') }}</div>
        <form class="form-row" @submit.prevent="create">
          <label style="margin:0">{{ t('orders.product') }}
            <select v-model="form.product_id" required>
              <option disabled value="">{{ t('common.select') }}</option>
              <option v-for="p in products" :key="p.id" :value="p.id">{{ p.name }} ({{ p.sku }})</option>
            </select>
          </label>
          <label style="margin:0">{{ t('barcodes.code') }}
            <input v-model="form.barcode" required />
          </label>
          <label style="margin:0">
            <input v-model="form.is_primary" type="checkbox" /> {{ t('barcodes.primary') }}
          </label>
          <button type="submit" :disabled="pending">{{ t('common.add') }}</button>
        </form>
        <p class="hint">{{ t('barcodes.hint') }}</p>
      </div>
      <div class="card" style="margin-top:1rem">
        <div class="card-title">{{ t('barcodes.lookup') }}</div>
        <form class="form-row" @submit.prevent="lookup">
          <label style="margin:0">{{ t('barcodes.code') }}
            <input v-model="lookupCode" />
          </label>
          <button type="submit" :disabled="pending">{{ t('barcodes.find') }}</button>
        </form>
        <p v-if="lookupResult" class="ok">
          {{ lookupResult.name || lookupResult.product?.name }} · {{ lookupResult.sku || lookupResult.product?.sku }}
        </p>
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
const products = ref<any[]>([])
const pending = ref(false)
const error = ref('')
const lookupCode = ref('')
const lookupResult = ref<any>(null)
const form = reactive({ product_id: '', barcode: '', is_primary: true })

async function loadProducts() {
  if (!session.businessId) return
  try {
    products.value = api.unwrap(await api.get('/products')) || []
  } catch (e: any) {
    error.value = e.message || t('common.error')
  }
}
async function create() {
  pending.value = true
  error.value = ''
  try {
    await api.post('/barcodes', {
      product_id: form.product_id,
      barcode: form.barcode,
      is_primary: form.is_primary,
    })
    toast.push(t('barcodes.created'), 'ok')
    form.barcode = ''
  } catch (e: any) {
    error.value = e.message || t('common.error')
  } finally {
    pending.value = false
  }
}
async function lookup() {
  pending.value = true
  error.value = ''
  lookupResult.value = null
  try {
    const res = await api.get(`/barcodes/lookup?barcode=${encodeURIComponent(lookupCode.value)}`)
    const data = api.unwrap(res)
    lookupResult.value = data?.product || data
  } catch (e: any) {
    error.value = e.message || t('barcodes.notFound')
  } finally {
    pending.value = false
  }
}
onMounted(loadProducts)
watch(() => session.businessId, loadProducts)
</script>
