<template>
  <div>
    <div class="page-head"><h1>{{ t('posSales.title') }}</h1></div>
    <p v-if="!session.businessId" class="warn">
      {{ t('common.selectBusiness') }}
      <NuxtLink to="/business">{{ t('common.chooseBusiness') }}</NuxtLink>
    </p>
    <template v-else>
      <button type="button" class="secondary" :disabled="loading" @click="load">{{ t('common.refresh') }}</button>
      <p v-if="error" class="err">{{ error }}</p>

      <div v-if="items.length" class="table-wrap" style="margin-top:1rem">
        <table>
          <thead>
            <tr>
              <th>{{ t('posSales.doc') }}</th>
              <th>{{ t('posSales.when') }}</th>
              <th>{{ t('posSales.total') }}</th>
              <th>{{ t('posSales.pay') }}</th>
              <th>{{ t('posSales.status') }}</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="s in items" :key="s.id">
              <td>
                <code>{{ s.document_number || s.id?.slice(0, 10) }}</code>
                <div class="hint" style="font-size:.75rem">{{ s.id }}</div>
              </td>
              <td>{{ formatWhen(s.sold_at) }}</td>
              <td>{{ s.total_amount }} {{ s.currency }}</td>
              <td>{{ s.payment_method }}</td>
              <td>{{ s.status }}</td>
              <td>
                <button type="button" class="secondary" @click="open(s.id)">{{ t('common.view') }}</button>
                <NuxtLink
                  v-if="s.status === 'COMPLETED'"
                  class="secondary"
                  style="margin-inline-start:.35rem;display:inline-block;padding:.25rem .5rem;text-decoration:none"
                  :to="{ path: '/pos-returns', query: { sale_id: s.id } }"
                >{{ t('posSales.return') }}</NuxtLink>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <div v-else-if="!loading" class="empty">{{ t('posSales.empty') }}</div>

      <div v-if="detail" class="card" style="margin-top:1rem">
        <div class="card-title">{{ t('posSales.detail') }} — {{ detail.document_number || detail.id }}</div>
        <p class="hint">ID: <code>{{ detail.id }}</code></p>
        <div class="table-wrap">
          <table>
            <thead>
              <tr>
                <th>{{ t('products.name') }}</th>
                <th>{{ t('pos.qty') }}</th>
                <th>{{ t('products.price') }}</th>
                <th>{{ t('pos.lineTotal') }}</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="l in detail.lines || []" :key="l.id">
                <td>{{ l.product_name || l.product_id }} <span v-if="l.sku" class="hint">({{ l.sku }})</span></td>
                <td>{{ l.quantity }}</td>
                <td>{{ l.unit_price_amount }}</td>
                <td>{{ l.line_total_amount }}</td>
              </tr>
            </tbody>
          </table>
        </div>
        <NuxtLink
          class="secondary"
          style="display:inline-block;margin-top:.75rem;padding:.35rem .75rem;text-decoration:none"
          :to="{ path: '/pos-returns', query: { sale_id: detail.id } }"
        >{{ t('posSales.return') }}</NuxtLink>
      </div>
    </template>
  </div>
</template>
<script setup lang="ts">
import { useSessionStore } from '~/stores/session'
definePageMeta({ middleware: 'auth' })
const { t } = useI18n()
const api = useApi()
const session = useSessionStore()
const items = ref<any[]>([])
const detail = ref<any>(null)
const loading = ref(false)
const error = ref('')

function formatWhen(iso?: string) {
  if (!iso) return '—'
  try { return new Date(iso).toLocaleString() } catch { return iso }
}

async function load() {
  if (!session.businessId) return
  loading.value = true
  error.value = ''
  try {
    items.value = api.unwrap(await api.get('/pos/sales?limit=50')) || []
  } catch (e: any) {
    error.value = e.message || t('common.error')
  } finally {
    loading.value = false
  }
}

async function open(id: string) {
  error.value = ''
  try {
    detail.value = api.unwrap(await api.get(`/pos/sales/${id}`))
  } catch (e: any) {
    error.value = e.message || t('common.error')
  }
}

onMounted(load)
watch(() => session.businessId, load)
</script>
