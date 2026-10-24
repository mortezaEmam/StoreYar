<template>
  <div>
    <div class="page-head"><h1>{{ t('payments.title') }}</h1></div>
    <p v-if="!session.businessId" class="warn">
      {{ t('common.selectBusiness') }}
      <NuxtLink to="/business">{{ t('common.chooseBusiness') }}</NuxtLink>
    </p>
    <template v-else>
      <div class="card">
        <div class="card-title">{{ t('payments.initiate') }}</div>
        <form class="form-row" @submit.prevent="initiate">
          <label style="margin:0">{{ t('payments.order') }}
            <select v-model="form.order_id" required>
              <option disabled value="">{{ t('payments.selectOrder') }}</option>
              <option v-for="o in payableOrders" :key="o.id" :value="o.id">
                {{ o.id.slice(0, 12) }}… — {{ o.status }}
                <template v-if="o.total_amount != null"> — {{ o.total_amount }}</template>
              </option>
            </select>
          </label>
          <label style="margin:0">{{ t('payments.amount') }}
            <input v-model.number="form.amount" type="number" min="1" required />
          </label>
          <label style="margin:0">{{ t('payments.currency') }}
            <input v-model="form.currency" />
          </label>
          <button type="submit" :disabled="pending">{{ t('payments.initiate') }}</button>
        </form>
        <p v-if="!payableOrders.length" class="hint">{{ t('payments.needOrder') }}</p>
      </div>
      <p v-if="error" class="err">{{ error }}</p>
      <div v-if="items.length" class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>ID</th>
              <th>{{ t('payments.order') }}</th>
              <th>{{ t('payments.amount') }}</th>
              <th>{{ t('payments.status') }}</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="p in items" :key="p.id">
              <td><code style="font-size:.8rem">{{ p.id.slice(0, 10) }}…</code></td>
              <td><code style="font-size:.8rem">{{ p.order_id.slice(0, 10) }}…</code></td>
              <td>{{ p.amount }} {{ p.currency }}</td>
              <td><span class="badge" :class="String(p.status).toLowerCase()">{{ p.status }}</span></td>
              <td>
                <button v-if="isPending(p.status)" type="button" class="secondary" @click="succeed(p.id)">
                  {{ t('payments.markSucceeded') }}
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <div v-else-if="!loading" class="empty">{{ t('payments.empty') }}</div>
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
const form = reactive({ order_id: '', amount: 1000, currency: 'IRR' })
const payableOrders = computed(() =>
  orders.value.filter((o: any) => ['confirmed', 'paid'].includes(String(o.status).toLowerCase())),
)
watch(() => form.order_id, (id) => {
  const o = orders.value.find((x: any) => x.id === id)
  if (o?.total_amount > 0) form.amount = o.total_amount
  if (o?.currency) form.currency = o.currency
})
function isPending(s: string) {
  const x = s.toLowerCase(); return x === 'pending' || x === 'initiated'
}
async function refresh() {
  if (!session.businessId) return
  loading.value = true; error.value = ''
  try {
    const [o, p] = await Promise.all([api.get<any>('/orders'), api.get<any>('/payments')])
    orders.value = Array.isArray(o.data) ? o.data : []
    items.value = Array.isArray(p.data) ? p.data : []
  } catch (e: unknown) {
    error.value = e instanceof Error ? e.message : t('common.error'); toast.err(error.value)
  } finally { loading.value = false }
}
async function initiate() {
  pending.value = true; error.value = ''
  try {
    await api.post('/payments', { ...form }, crypto.randomUUID())
    toast.ok(t('payments.initiated')); form.order_id = ''; await refresh()
  } catch (e: unknown) {
    error.value = e instanceof Error ? e.message : t('common.error'); toast.err(error.value)
  } finally { pending.value = false }
}
async function succeed(id: string) {
  pending.value = true; error.value = ''
  try {
    await api.post(`/payments/${id}/succeed`, {}, crypto.randomUUID())
    toast.ok(t('payments.succeeded')); await refresh()
  } catch (e: unknown) {
    error.value = e instanceof Error ? e.message : t('common.error'); toast.err(error.value)
  } finally { pending.value = false }
}
watch(() => session.businessId, refresh, { immediate: true })
</script>
