<template>
  <div>
    <div class="page-head"><h1>{{ t('products.title') }}</h1></div>
    <p v-if="!session.businessId" class="warn">
      {{ t('common.selectBusiness') }}
      <NuxtLink to="/business">{{ t('common.chooseBusiness') }}</NuxtLink>
    </p>
    <template v-else>
      <div class="card">
        <div class="card-title">{{ t('products.add') }}</div>
        <form class="form-row" @submit.prevent="create">
          <label style="margin:0">{{ t('products.name') }}
            <input v-model="form.name" required />
          </label>
          <label style="margin:0">{{ t('products.sku') }}
            <input v-model="form.sku" required />
          </label>
          <label style="margin:0">{{ t('products.price') }}
            <input v-model.number="form.unit_price_amount" type="number" min="0" required />
          </label>
          <button type="submit" :disabled="pending">{{ pending ? t('common.loading') : t('common.add') }}</button>
        </form>
        <p class="hint">{{ t('products.priceHint') }}</p>
      </div>
      <p v-if="error" class="err">{{ error }}</p>
      <div v-if="items.length" class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>{{ t('products.name') }}</th>
              <th>{{ t('products.sku') }}</th>
              <th>{{ t('products.price') }}</th>
              <th>{{ t('products.status') }}</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="p in items" :key="p.id">
              <td>{{ p.name }}</td>
              <td><code style="font-size:.8rem">{{ p.sku }}</code></td>
              <td>{{ p.unit_price_amount }} {{ p.currency || 'IRR' }}</td>
              <td><span class="badge" :class="p.status">{{ p.status || '—' }}</span></td>
            </tr>
          </tbody>
        </table>
      </div>
      <div v-else-if="!loading" class="empty">{{ t('products.empty') }}</div>
      <p v-if="loading" class="hint">{{ t('common.loading') }}</p>
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
const loading = ref(false)
const pending = ref(false)
const error = ref('')
const form = reactive({ name: '', sku: '', unit_price_amount: 1000, currency: 'IRR' })
async function load() {
  if (!session.businessId) return
  loading.value = true
  error.value = ''
  try {
    const res = await api.get<any>('/products')
    items.value = Array.isArray(res.data) ? res.data : []
  } catch (e: unknown) {
    error.value = e instanceof Error ? e.message : t('common.error')
    toast.err(error.value)
  } finally {
    loading.value = false
  }
}
async function create() {
  pending.value = true
  error.value = ''
  try {
    await api.post('/products', { ...form }, crypto.randomUUID())
    toast.ok(t('products.created'))
    form.name = ''
    form.sku = ''
    await load()
  } catch (e: unknown) {
    error.value = e instanceof Error ? e.message : t('common.error')
    toast.err(error.value)
  } finally {
    pending.value = false
  }
}
watch(() => session.businessId, load, { immediate: true })
</script>
