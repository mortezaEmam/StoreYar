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
      <p v-if="error" class="err">{{ error }}</p>
      <p v-if="loading" class="hint">{{ t('common.loading') }}</p>
      <div v-if="items.length" class="table-wrap">
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
            <tr v-for="(row, i) in items" :key="row.id || i">
              <td>{{ row.created_at || row.occurred_at || '—' }}</td>
              <td>{{ row.action || row.event || '—' }}</td>
              <td><code style="font-size:.75rem">{{ row.actor_id || row.user_id || '—' }}</code></td>
              <td>{{ row.entity_type || '' }} {{ row.entity_id ? String(row.entity_id).slice(0, 12) + '…' : '' }}</td>
            </tr>
          </tbody>
        </table>
      </div>
      <div v-else-if="!loading" class="empty">{{ t('audit.empty') }}</div>
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
