<template>
  <div>
    <div class="page-head"><h1>{{ t('reports.title') }}</h1></div>
    <p v-if="!session.businessId" class="warn">
      {{ t('common.selectBusiness') }}
      <NuxtLink to="/business">{{ t('common.chooseBusiness') }}</NuxtLink>
    </p>
    <template v-else>
      <button type="button" class="secondary" :disabled="loading" @click="load">{{ t('common.refresh') }}</button>
      <p v-if="error" class="err">{{ error }}</p>
      <div v-if="dash" class="stats" style="margin-top:1rem">
        <div class="stat card">
          <div class="stat-label">{{ t('nav.products') }}</div>
          <div class="stat-value">{{ dash.products }}</div>
        </div>
        <div class="stat card">
          <div class="stat-label">{{ t('reports.stockUnits') }}</div>
          <div class="stat-value">{{ dash.stock_units }}</div>
        </div>
        <div class="stat card">
          <div class="stat-label">{{ t('reports.salesToday') }}</div>
          <div class="stat-value">{{ dash.pos_sales_today_count }}</div>
        </div>
        <div class="stat card">
          <div class="stat-label">{{ t('reports.salesTodayAmt') }}</div>
          <div class="stat-value">{{ dash.pos_sales_today_amount }}</div>
        </div>
        <div class="stat card">
          <div class="stat-label">{{ t('reports.lowStock') }}</div>
          <div class="stat-value">{{ dash.low_stock_lines }}</div>
        </div>
        <div class="stat card">
          <div class="stat-label">{{ t('reports.openPo') }}</div>
          <div class="stat-value">{{ dash.open_purchase_orders }}</div>
        </div>
      </div>

      <div class="card" style="margin-top:1rem">
        <div class="card-title">{{ t('reports.lowStockList') }}</div>
        <div v-if="lowStock.length" class="table-wrap">
          <table>
            <thead>
              <tr>
                <th>{{ t('products.sku') }}</th>
                <th>{{ t('products.name') }}</th>
                <th>{{ t('stock.onHand') }}</th>
                <th>{{ t('reports.min') }}</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="r in lowStock" :key="r.product_id + r.warehouse_id">
                <td>{{ r.sku }}</td>
                <td>{{ r.product_name }}</td>
                <td>{{ r.quantity }}</td>
                <td>{{ r.min_stock ?? r.reorder_point }}</td>
              </tr>
            </tbody>
          </table>
        </div>
        <div v-else class="empty">{{ t('reports.noLowStock') }}</div>
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

const dash = ref<any>(null)
const lowStock = ref<any[]>([])
const loading = ref(false)
const error = ref('')

async function load() {
  if (!session.businessId) return
  loading.value = true
  error.value = ''
  try {
    const [d, low] = await Promise.all([
      api.get('/reports/dashboard'),
      api.get('/reports/low-stock'),
    ])
    dash.value = api.unwrap(d)
    lowStock.value = api.unwrap(low) || []
  } catch (e: any) {
    error.value = e.message || t('common.error')
  } finally {
    loading.value = false
  }
}

onMounted(load)
watch(() => session.businessId, load)
</script>
