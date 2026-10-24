<template>
  <div>
    <div class="page-head">
      <div>
        <NuxtLink to="/orders" class="back">← {{ t('orders.back') }}</NuxtLink>
        <h1>{{ t('orders.detail') }}</h1>
      </div>
      <button type="button" class="secondary" :disabled="loading" @click="load">{{ t('common.refresh') }}</button>
    </div>

    <p v-if="!session.isAuthenticated" class="warn">
      <NuxtLink to="/login">{{ t('nav.login') }}</NuxtLink>
    </p>
    <p v-else-if="!session.businessId" class="warn">
      {{ t('common.selectBusiness') }}
      <NuxtLink to="/business">{{ t('common.chooseBusiness') }}</NuxtLink>
    </p>

    <template v-else>
      <p v-if="loading" class="hint">{{ t('common.loading') }}</p>
      <p v-if="error" class="err">{{ error }}</p>

      <template v-if="order && !loading">
        <div class="card">
          <div class="meta-grid">
            <div>
              <div class="label">ID</div>
              <code class="mono">{{ order.id }}</code>
            </div>
            <div>
              <div class="label">{{ t('orders.status') }}</div>
              <span class="badge" :class="statusClass">{{ order.status }}</span>
            </div>
            <div>
              <div class="label">{{ t('orders.total') }}</div>
              <strong>{{ totalLabel }}</strong>
            </div>
            <div v-if="order.customer_user_id">
              <div class="label">{{ t('orders.customer') }}</div>
              <code class="mono">{{ String(order.customer_user_id).slice(0, 14) }}…</code>
            </div>
          </div>

          <div class="actions">
            <button
              v-if="statusClass === 'draft'"
              type="button"
              :disabled="pending"
              @click="confirmOrder"
            >
              {{ t('orders.confirm') }}
            </button>
            <button
              v-if="canCancel"
              type="button"
              class="danger"
              :disabled="pending"
              @click="cancelOrder"
            >
              {{ t('orders.cancel') }}
            </button>
            <NuxtLink v-if="statusClass === 'confirmed' || statusClass === 'paid'" to="/payments">
              <button type="button" class="secondary">{{ t('orders.goPayments') }}</button>
            </NuxtLink>
            <NuxtLink v-if="statusClass === 'paid' || statusClass === 'confirmed'" to="/shipments">
              <button type="button" class="secondary">{{ t('orders.goShipments') }}</button>
            </NuxtLink>
          </div>
        </div>

        <div class="card">
          <div class="card-title">{{ t('orders.lines') }}</div>
          <div v-if="lines.length" class="table-wrap">
            <table>
              <thead>
                <tr>
                  <th>{{ t('orders.product') }}</th>
                  <th>{{ t('orders.qty') }}</th>
                  <th>{{ t('orders.unitPrice') }}</th>
                  <th>{{ t('orders.lineTotal') }}</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(line, i) in lines" :key="line.id || i">
                  <td><code class="mono">{{ (line.product_id || '').slice(0, 12) }}…</code></td>
                  <td>{{ line.quantity }}</td>
                  <td>{{ line.unit_price_amount ?? line.unit_price ?? '—' }}</td>
                  <td>{{ lineTotal(line) }}</td>
                </tr>
              </tbody>
            </table>
          </div>
          <div v-else class="empty">{{ t('orders.noLines') }}</div>
        </div>
      </template>

      <div v-else-if="!loading && !error" class="empty">{{ t('orders.notFound') }}</div>
    </template>
  </div>
</template>

<script setup lang="ts">
import { useSessionStore } from '~/stores/session'
definePageMeta({ middleware: 'auth' })
const { t } = useI18n()
const route = useRoute()
const api = useApi()
const session = useSessionStore()
const toast = useToast()
const order = ref<any>(null)
const loading = ref(false)
const pending = ref(false)
const error = ref('')
const orderId = computed(() => String(route.params.id || ''))
const lines = computed(() => Array.isArray(order.value?.lines) ? order.value.lines : [])
const statusClass = computed(() => String(order.value?.status || '').toLowerCase())
const canCancel = computed(() => ['draft', 'confirmed'].includes(statusClass.value))
const totalLabel = computed(() => {
  if (order.value?.total_amount != null) {
    return `${order.value.total_amount} ${order.value.currency || 'IRR'}`
  }
  const sum = lines.value.reduce((acc: number, line: any) => {
    const q = Number(line.quantity || 0)
    const p = Number(line.unit_price_amount ?? line.unit_price ?? 0)
    return acc + q * p
  }, 0)
  return sum ? `${sum} IRR` : '—'
})
function lineTotal(line: any) {
  const q = Number(line.quantity || 0)
  const p = Number(line.unit_price_amount ?? line.unit_price ?? 0)
  if (!q || !p) return '—'
  return q * p
}
async function load() {
  if (!session.businessId || !orderId.value) return
  loading.value = true
  error.value = ''
  order.value = null
  try {
    const res = await api.get<any>(`/orders/${orderId.value}`)
    order.value = res?.data ?? res
    if (!order.value?.id) error.value = t('orders.notFound')
  } catch (e: unknown) {
    error.value = e instanceof Error ? e.message : t('common.error')
    order.value = null
    toast.err(error.value)
  } finally {
    loading.value = false
  }
}
async function confirmOrder() {
  if (!orderId.value) return
  pending.value = true
  error.value = ''
  try {
    await api.post(`/orders/${orderId.value}/confirm`, {}, crypto.randomUUID())
    toast.ok(t('orders.confirmed'))
    await load()
  } catch (e: unknown) {
    error.value = e instanceof Error ? e.message : t('common.error')
    toast.err(error.value)
  } finally {
    pending.value = false
  }
}
async function cancelOrder() {
  if (!orderId.value) return
  if (!confirm(t('orders.cancelConfirm'))) return
  pending.value = true
  error.value = ''
  try {
    await api.post(`/orders/${orderId.value}/cancel`, {}, crypto.randomUUID())
    toast.ok(t('orders.cancelled'))
    await load()
  } catch (e: unknown) {
    error.value = e instanceof Error ? e.message : t('common.error')
    toast.err(error.value)
  } finally {
    pending.value = false
  }
}
watch(() => [session.businessId, orderId.value], () => load(), { immediate: true })
</script>
<style scoped>
.back { display: inline-block; font-size: 0.875rem; color: var(--muted, #667085); margin-bottom: 0.35rem; }
.meta-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(11rem, 1fr)); gap: 1rem; margin-bottom: 1.25rem; }
.label { font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.04em; color: var(--muted, #667085); margin-bottom: 0.25rem; }
.mono { font-size: 0.8rem; word-break: break-all; }
.actions { display: flex; flex-wrap: wrap; gap: 0.5rem; }
</style>
