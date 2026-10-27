<template>
  <div>
    <div class="page-head"><h1>{{ t('sync.title') }}</h1></div>
    <p v-if="!session.businessId" class="warn">
      {{ t('common.selectBusiness') }}
      <NuxtLink to="/business">{{ t('common.chooseBusiness') }}</NuxtLink>
    </p>
    <template v-else>
      <p class="hint">{{ t('sync.intro') }}</p>

      <div class="grid-2">
        <div class="card">
          <div class="card-title">{{ t('sync.register') }}</div>
          <form class="form-col" @submit.prevent="registerDevice">
            <label>{{ t('sync.deviceKey') }}
              <input v-model="reg.device_key" required />
            </label>
            <label>{{ t('sync.deviceName') }}
              <input v-model="reg.name" />
            </label>
            <label>{{ t('sync.platform') }}
              <select v-model="reg.platform">
                <option value="WEB">WEB</option>
                <option value="ANDROID">ANDROID</option>
                <option value="IOS">IOS</option>
                <option value="POS">POS</option>
              </select>
            </label>
            <button type="submit" :disabled="pending">{{ t('sync.registerBtn') }}</button>
          </form>
          <p v-if="device" class="ok">
            {{ t('sync.registered') }}:
            <code>{{ device.id }}</code>
            · {{ device.device_key }}
          </p>
        </div>

        <div class="card">
          <div class="card-title">{{ t('sync.pull') }}</div>
          <div class="form-col">
            <button type="button" class="secondary" :disabled="pending" @click="pullCatalog">{{ t('sync.pullCatalog') }}</button>
            <button type="button" class="secondary" :disabled="pending" @click="pullStock">{{ t('sync.pullStock') }}</button>
            <button type="button" class="secondary" :disabled="pending" @click="loadEvents">{{ t('sync.loadEvents') }}</button>
          </div>
          <p v-if="pullSummary" class="ok">{{ pullSummary }}</p>
        </div>
      </div>

      <div class="card" style="margin-top:1rem">
        <div class="card-title">{{ t('sync.testPush') }}</div>
        <p class="hint">{{ t('sync.testPushHint') }}</p>
        <button type="button" :disabled="pending || !device" @click="testPush">{{ t('sync.pushBtn') }}</button>
      </div>

      <p v-if="error" class="err">{{ error }}</p>

      <div v-if="events.length" class="table-wrap" style="margin-top:1rem">
        <table>
          <thead>
            <tr>
              <th>{{ t('sync.eventId') }}</th>
              <th>{{ t('sync.eventType') }}</th>
              <th>{{ t('sync.eventStatus') }}</th>
              <th>{{ t('sync.when') }}</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="e in events" :key="e.id || e.client_event_id">
              <td><code>{{ e.client_event_id || e.id?.slice(0, 12) }}</code></td>
              <td>{{ e.event_type }}</td>
              <td>{{ e.status }}</td>
              <td>{{ e.created_at || e.client_occurred_at }}</td>
            </tr>
          </tbody>
        </table>
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
const toast = useToast()

const pending = ref(false)
const error = ref('')
const device = ref<any>(null)
const events = ref<any[]>([])
const pullSummary = ref('')
const reg = reactive({
  device_key: 'web-' + Math.random().toString(36).slice(2, 10),
  name: 'Browser POS',
  platform: 'WEB',
})

// restore device id from localStorage
onMounted(() => {
  if (import.meta.client) {
    try {
      const raw = localStorage.getItem('storeyar_sync_device')
      if (raw) device.value = JSON.parse(raw)
    } catch { /* */ }
  }
})

async function registerDevice() {
  pending.value = true
  error.value = ''
  try {
    const res = await api.post('/sync/devices', { ...reg })
    device.value = api.unwrap(res)
    if (import.meta.client && device.value) {
      localStorage.setItem('storeyar_sync_device', JSON.stringify(device.value))
    }
    toast.ok(t('sync.registered'))
  } catch (e: any) {
    error.value = e.message || t('common.error')
    toast.err(error.value)
  } finally {
    pending.value = false
  }
}

async function pullCatalog() {
  pending.value = true
  error.value = ''
  try {
    const res = await api.get('/sync/pull/catalog')
    const data = api.unwrap(res)
    const n = Array.isArray(data) ? data.length : (data?.items?.length ?? data?.count ?? '?')
    pullSummary.value = `${t('sync.pullCatalog')}: ${n}`
    toast.ok(pullSummary.value)
  } catch (e: any) {
    error.value = e.message || t('common.error')
  } finally {
    pending.value = false
  }
}

async function pullStock() {
  pending.value = true
  error.value = ''
  try {
    const res = await api.get('/sync/pull/stock')
    const data = api.unwrap(res)
    const n = Array.isArray(data) ? data.length : (data?.items?.length ?? data?.count ?? '?')
    pullSummary.value = `${t('sync.pullStock')}: ${n}`
    toast.ok(pullSummary.value)
  } catch (e: any) {
    error.value = e.message || t('common.error')
  } finally {
    pending.value = false
  }
}

async function loadEvents() {
  pending.value = true
  error.value = ''
  try {
    const res = await api.get('/sync/events')
    events.value = api.unwrap(res) || []
  } catch (e: any) {
    error.value = e.message || t('common.error')
  } finally {
    pending.value = false
  }
}

async function testPush() {
  if (!device.value?.id) return
  pending.value = true
  error.value = ''
  try {
    const clientEventId = 'evt-' + Date.now()
    const res = await api.post('/sync/push', {
      device_id: device.value.id,
      events: [{
        client_event_id: clientEventId,
        event_type: 'PING',
        payload: { source: 'web-ui', at: new Date().toISOString() },
        client_occurred_at: new Date().toISOString(),
      }],
    })
    const data = api.unwrap(res)
    toast.ok(t('sync.pushOk'))
    pullSummary.value = JSON.stringify(data)
    await loadEvents()
  } catch (e: any) {
    error.value = e.message || t('common.error')
    toast.err(error.value)
  } finally {
    pending.value = false
  }
}
</script>
<style scoped>
.grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
@media (max-width: 800px) { .grid-2 { grid-template-columns: 1fr; } }
.form-col { display: flex; flex-direction: column; gap: .75rem; }
</style>
