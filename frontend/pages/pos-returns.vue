<template>
  <div>
    <div class="page-head"><h1>{{ t('posReturn.title') }}</h1></div>
    <p v-if="!session.businessId" class="warn">
      {{ t('common.selectBusiness') }}
      <NuxtLink to="/business">{{ t('common.chooseBusiness') }}</NuxtLink>
    </p>
    <template v-else>
      <div class="card">
        <div class="card-title">{{ t('posReturn.form') }}</div>
        <form class="form-col" @submit.prevent="submit">
          <label>{{ t('posReturn.saleId') }}
            <input v-model="form.pos_sale_id" required placeholder="01H..." />
          </label>
          <label>{{ t('orders.product') }}
            <select v-model="form.product_id" required>
              <option disabled value="">{{ t('common.select') }}</option>
              <option v-for="p in products" :key="p.id" :value="p.id">{{ p.name }} ({{ p.sku }})</option>
            </select>
          </label>
          <label>{{ t('posReturn.qty') }}
            <input v-model.number="form.quantity" type="number" min="1" required />
          </label>
          <label>{{ t('posReturn.reason') }}
            <input v-model="form.reason" />
          </label>
          <button type="submit" :disabled="pending">{{ t('posReturn.submit') }}</button>
        </form>
        <p class="hint">{{ t('posReturn.hint') }}</p>
      </div>
      <p v-if="error" class="err">{{ error }}</p>
      <p v-if="lastDoc" class="ok">{{ t('posReturn.ok') }}: {{ lastDoc }}</p>
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
const lastDoc = ref('')
const form = reactive({ pos_sale_id: '', product_id: '', quantity: 1, reason: '' })

async function load() {
  if (!session.businessId) return
  try {
    products.value = api.unwrap(await api.get('/products')) || []
  } catch (e: any) {
    error.value = e.message || t('common.error')
  }
}
async function submit() {
  pending.value = true
  error.value = ''
  try {
    const res = await api.post('/pos-returns', {
      pos_sale_id: form.pos_sale_id,
      reason: form.reason || null,
      lines: [{ product_id: form.product_id, quantity: form.quantity }],
    })
    const data = api.unwrap(res)
    lastDoc.value = data?.document_number || data?.id || 'OK'
    toast.push(t('posReturn.ok'), 'ok')
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
