<template>
  <div>
    <div class="page-head">
      <h1>{{ t('audit.title') }}</h1>
      <button type="button" class="secondary" :disabled="loading" @click="load">{{ t('common.refresh') }}</button>
    </div>
    <p v-if="!session.businessId" class="warn">
      {{ t('common.selectBusiness') }}
      <NuxtLink to="/business">{{ t('common.chooseBusiness') }}</NuxtLink>
    </p>
    <template v-else>
      <div class="card filter-bar">
        <label style="margin:0">{{ t('audit.filterAction') }}
          <select v-model="filterAction">
            <option value="">{{ t('audit.filterAll') }}</option>
            <option v-for="a in actionOptions" :key="a" :value="a">{{ a }}</option>
          </select>
        </label>
        <label style="margin:0">{{ t('audit.filterEntity') }}
          <select v-model="filterEntity">
            <option value="">{{ t('audit.filterAll') }}</option>
            <option v-for="e in entityOptions" :key="e" :value="e">{{ e }}</option>
          </select>
        </label>
        <label style="margin:0">{{ t('audit.filterSearch') }}
          <input v-model="filterSearch" type="search" :placeholder="t('audit.filterSearchPlaceholder')" />
        </label>
        <span class="hint">{{ filteredItems.length }} / {{ items.length }}</span>
      </div>

      <p v-if="error" class="err">{{ error }}</p>
      <p v-if="loading" class="hint">{{ t('common.loading') }}</p>

      <div v-if="filteredItems.length" class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>{{ t('audit.when') }}</th>
              <th>{{ t('audit.action') }}</th>
              <th>{{ t('audit.actor') }}</th>
              <th>{{ t('audit.entity') }}</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(row, i) in filteredItems" :key="row.id || i">
              <td>{{ formatWhen(row) }}</td>
              <td>{{ actionOf(row) }}</td>
              <td><code style="font-size:.75rem">{{ actorOf(row) }}</code></td>
              <td>{{ entityOf(row) }}</td>
            </tr>
          </tbody>
        </table>
      </div>
      <div v-else-if="!loading" class="empty">
        {{ items.length ? t('audit.filterEmpty') : t('audit.empty') }}
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

const items = ref<any[]>([])
const loading = ref(false)
const error = ref('')
const filterAction = ref('')
const filterEntity = ref('')
const filterSearch = ref('')

function actionOf(row: any) {
  return String(row.action || row.event || row.event_type || '—')
}
function actorOf(row: any) {
  const id = row.actor_id || row.user_id || row.actor_user_id || ''
  return id ? String(id).slice(0, 16) + (String(id).length > 16 ? '…' : '') : '—'
}
function entityTypeOf(row: any) {
  return String(row.entity_type || row.aggregate_type || row.resource_type || '')
}
function entityOf(row: any) {
  const type = entityTypeOf(row)
  const id = row.entity_id || row.aggregate_id || row.resource_id || ''
  const short = id ? String(id).slice(0, 12) + '…' : ''
  return [type, short].filter(Boolean).join(' ') || '—'
}
function formatWhen(row: any) {
  const raw = row.created_at || row.occurred_at || row.recorded_at || ''
  if (!raw) return '—'
  try {
    return new Date(raw).toLocaleString()
  } catch {
    return String(raw)
  }
}

const actionOptions = computed(() => {
  const set = new Set<string>()
  for (const row of items.value) {
    const a = actionOf(row)
    if (a && a !== '—') set.add(a)
  }
  return Array.from(set).sort()
})

const entityOptions = computed(() => {
  const set = new Set<string>()
  for (const row of items.value) {
    const e = entityTypeOf(row)
    if (e) set.add(e)
  }
  return Array.from(set).sort()
})

const filteredItems = computed(() => {
  const q = filterSearch.value.trim().toLowerCase()
  return items.value.filter((row) => {
    if (filterAction.value && actionOf(row) !== filterAction.value) return false
    if (filterEntity.value && entityTypeOf(row) !== filterEntity.value) return false
    if (q) {
      const blob = [
        actionOf(row),
        actorOf(row),
        entityOf(row),
        formatWhen(row),
        JSON.stringify(row),
      ].join(' ').toLowerCase()
      if (!blob.includes(q)) return false
    }
    return true
  })
})

async function load() {
  if (!session.businessId) return
  loading.value = true
  error.value = ''
  try {
    const res = await api.get<any>('/audit-log')
    items.value = Array.isArray(res.data) ? res.data : (Array.isArray(res) ? res : [])
  } catch (e: unknown) {
    error.value = e instanceof Error ? e.message : t('common.error')
    toast.err(error.value)
  } finally {
    loading.value = false
  }
}

watch(() => session.businessId, load, { immediate: true })
</script>

<style scoped>
.filter-bar {
  display: flex;
  flex-wrap: wrap;
  align-items: flex-end;
  gap: 1rem;
}
.filter-bar input[type="search"] {
  min-width: 12rem;
}
</style>
