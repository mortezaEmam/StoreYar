<template>
  <div>
    <div class="page-head"><h1>{{ t('export.title') }}</h1></div>
    <p v-if="!session.businessId" class="warn">
      {{ t('common.selectBusiness') }}
      <NuxtLink to="/business">{{ t('common.chooseBusiness') }}</NuxtLink>
    </p>
    <template v-else>
      <div class="card">
        <div class="card-title">{{ t('export.manifest') }}</div>
        <button type="button" class="secondary" :disabled="pending" @click="loadManifest">{{ t('common.refresh') }}</button>
        <div v-if="manifest" class="table-wrap" style="margin-top:.75rem">
          <table>
            <thead>
              <tr>
                <th>{{ t('export.resource') }}</th>
                <th>{{ t('export.count') }}</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(n, key) in manifest.resources" :key="key">
                <td>{{ key }}</td>
                <td>{{ n }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <div class="card" style="margin-top:1rem">
        <div class="card-title">{{ t('export.download') }}</div>
        <div class="export-list">
          <div v-for="r in resources" :key="r.path" class="export-row">
            <span>{{ t(r.labelKey) }}</span>
            <div class="export-actions">
              <button type="button" class="secondary" :disabled="pending" @click="download(r.path, 'json')">JSON</button>
              <button type="button" class="secondary" :disabled="pending" @click="download(r.path, 'csv')">CSV</button>
            </div>
          </div>
        </div>
      </div>
      <p v-if="error" class="err">{{ error }}</p>
      <p v-if="okMsg" class="ok">{{ okMsg }}</p>
    </template>
  </div>
</template>
<script setup lang="ts">
import { useSessionStore } from '~/stores/session'
definePageMeta({ middleware: 'auth' })
const { t } = useI18n()
const api = useApi()
const session = useSessionStore()
const config = useRuntimeConfig()
const pending = ref(false)
const error = ref('')
const okMsg = ref('')
const manifest = ref<any>(null)

const resources = [
  { path: 'products', labelKey: 'nav.products' },
  { path: 'barcodes', labelKey: 'nav.barcodes' },
  { path: 'stock', labelKey: 'nav.stock' },
  { path: 'customers', labelKey: 'nav.customers' },
  { path: 'suppliers', labelKey: 'nav.suppliers' },
  { path: 'sales', labelKey: 'export.sales' },
  { path: 'movements', labelKey: 'export.movements' },
  { path: 'journals', labelKey: 'export.journals' },
]

async function loadManifest() {
  pending.value = true
  error.value = ''
  try {
    manifest.value = api.unwrap(await api.get('/export/manifest'))
  } catch (e: any) {
    error.value = e.message || t('common.error')
  } finally {
    pending.value = false
  }
}

async function download(resource: string, format: 'json' | 'csv') {
  pending.value = true
  error.value = ''
  okMsg.value = ''
  try {
    const base = String(config.public.apiBase || 'http://localhost:8000/api').replace(/\/$/, '')
    const url = `${base}/export/${resource}?format=${format}`
    const headers: Record<string, string> = { Accept: format === 'csv' ? 'text/csv' : 'application/json' }
    if (session.token) headers.Authorization = `Bearer ${session.token}`
    if (session.businessId) headers['X-Business-Id'] = session.businessId

    const res = await fetch(url, { headers })
    if (!res.ok) throw new Error(`HTTP ${res.status}`)

    if (format === 'csv') {
      const blob = await res.blob()
      const a = document.createElement('a')
      a.href = URL.createObjectURL(blob)
      a.download = `${resource}-${Date.now()}.csv`
      a.click()
      URL.revokeObjectURL(a.href)
    } else {
      const data = await res.json()
      const blob = new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' })
      const a = document.createElement('a')
      a.href = URL.createObjectURL(blob)
      a.download = `${resource}-${Date.now()}.json`
      a.click()
      URL.revokeObjectURL(a.href)
    }
    okMsg.value = t('export.done')
  } catch (e: any) {
    error.value = e.message || t('common.error')
  } finally {
    pending.value = false
  }
}

onMounted(loadManifest)
watch(() => session.businessId, loadManifest)
</script>
<style scoped>
.export-list { display: flex; flex-direction: column; gap: .5rem; }
.export-row {
  display: flex; justify-content: space-between; align-items: center;
  gap: 1rem; padding: .35rem 0; border-bottom: 1px solid rgba(0,0,0,.06);
}
.export-actions { display: flex; gap: .5rem; }
</style>
