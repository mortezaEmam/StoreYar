<template>
  <div>
    <div class="page-head"><h1>{{ t('shipments.title') }}</h1></div>
    <p v-if="!session.businessId" class="warn">
      {{ t('common.selectBusiness') }}
      <NuxtLink to="/business">{{ t('common.chooseBusiness') }}</NuxtLink>
    </p>
    <template v-else>
      <div class="card">
        <div class="card-title">{{ t('shipments.create') }}</div>
        <form class="form-row" @submit.prevent="create">
          <label style="margin:0">{{ t('shipments.order') }}
            <select v-model="form.order_id" required>
              <option disabled value="">{{ t('shipments.selectOrder') }}</option>
              <option v-for="o in shippableOrders" :key="o.id" :value="o.id">
                {{ o.id.slice(0, 12) }}… — {{ o.status }}
              </option>
            </select>
          </label>
          <button type="submit" :disabled="pending">{{ t('shipments.create') }}</button>
        </form>
        <p v-if="!shippableOrders.length" class="hint">{{ t('shipments.needOrder') }}</p>
      </div>
      <p v-if="error" class="err">{{ error }}</p>
      <div v-if="items.length" class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>ID</th>
              <th>{{ t('shipments.order') }}</th>
              <th>{{ t('shipments.status') }}</th>
              <th>{{ t('shipments.tracking') }}</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="s in items" :key="s.id">
              <td><code style="font-size:.8rem">{{ s.id.slice(0, 10) }}…</code></td>
              <td><code style="font-size:.8rem">{{ s.order_id.slice(0, 10) }}…</code></td>
              <td><span class="badge" :class="String(s.status).toLowerCase()">{{ s.status }}</span></td>
              <td>{{ s.tracking_number || '—' }}</td>
              <td>
                <button v-if="canShip(s.status)" type="button" class="secondary" @click="ship(s.id)">
                  {{ t('shipments.ship') }}
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <div v-else-if="!loading" class="empty">{{ t('shipments.empty') }}</div>
    </template>
  </div>
</template>
<script setup lang="ts">
import { useSessionStore } from '~/stores/session'
definePageMeta({ middleware: 'auth' })
const { t } = useI18n()
const api = useApi(); const session = useSessionStore(); const toast = useToast()
const items = ref<any[]>([]); const orders = ref<any[]>([])
const loading = ref(false); const pending = ref(false); const error = ref('')
const form = reactive({ order_id: '' })
const shippableOrders = computed(() =>
  orders.value.filter((o: any) => ['paid', 'confirmed'].includes(String(o.status).toLowerCase())),
)
function canShip(status: string) {
  return !['shipped', 'delivered', 'cancelled'].includes(String(status).toLowerCase())
}
async function refresh() {
  if (!session.businessId) return
  loading.value = true; error.value = ''
  try {
    const [o, s] = await Promise.all([api.get<any>('/orders'), api.get<any>('/shipments')])
    orders.value = Array.isArray(o.data) ? o.data : []
    items.value = Array.isArray(s.data) ? s.data : []
  } catch (e: unknown) {
    error.value = e instanceof Error ? e.message : t('common.error'); toast.err(error.value)
  } finally { loading.value = false }
}
async function create() {
  pending.value = true; error.value = ''
  try {
    await api.post('/shipments', { order_id: form.order_id }, crypto.randomUUID())
    toast.ok(t('shipments.created')); form.order_id = ''; await refresh()
  } catch (e: unknown) {
    error.value = e instanceof Error ? e.message : t('common.error'); toast.err(error.value)
  } finally { pending.value = false }
}
async function ship(id: string) {
  const tracking = window.prompt(t('shipments.trackingPrompt'), 'TRACK-1')
  if (!tracking) return
  pending.value = true; error.value = ''
  try {
    await api.post(`/shipments/${id}/ship`, { tracking_number: tracking }, crypto.randomUUID())
    toast.ok(t('shipments.shipped')); await refresh()
  } catch (e: unknown) {
    error.value = e instanceof Error ? e.message : t('common.error'); toast.err(error.value)
  } finally { pending.value = false }
}
watch(() => session.businessId, refresh, { immediate: true })
</script>
