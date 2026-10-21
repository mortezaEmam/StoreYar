<template>
  <div>
    <div class="page-head">
      <h1>Audit log</h1>
      <button type="button" class="secondary" :disabled="loading" @click="load">Refresh</button>
    </div>
    <p v-if="!session.businessId" class="warn">Select a business first. <NuxtLink to="/business">Choose business</NuxtLink></p>
    <template v-else>
      <p v-if="error" class="err">{{ error }}</p>
      <p v-if="loading" class="hint">Loading…</p>
      <div v-if="items.length" class="table-wrap">
        <table>
          <thead><tr><th>When</th><th>Action</th><th>Resource</th><th>Actor</th></tr></thead>
          <tbody>
            <tr v-for="(a, i) in items" :key="a.id || i">
              <td style="white-space:nowrap;font-size:.85rem">{{ a.occurred_at || '—' }}</td>
              <td><span class="badge">{{ a.action || '—' }}</span></td>
              <td>
                {{ a.resource_type || '—' }}
                <code v-if="a.resource_id" style="font-size:.75rem;margin-left:.25rem">
                  {{ String(a.resource_id).slice(0, 10) }}…
                </code>
              </td>
              <td>
                <code v-if="a.actor_user_id" style="font-size:.75rem">{{ String(a.actor_user_id).slice(0, 10) }}…</code>
                <span v-else>—</span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <div v-else-if="!loading" class="empty">No audit entries for this business.</div>
    </template>
  </div>
</template>
<script setup lang="ts">
import { useSessionStore } from '~/stores/session'
definePageMeta({ middleware: 'auth' })
const api = useApi(); const session = useSessionStore()
const items = ref<any[]>([]); const loading = ref(false); const error = ref('')
async function load() {
  if (!session.businessId) return
  loading.value = true; error.value = ''
  try {
    const res = await api.get<any>('/audit-log')
    items.value = Array.isArray(res.data) ? res.data : []
  } catch (e: unknown) {
    error.value = e instanceof Error ? e.message : 'Load failed'
  } finally { loading.value = false }
}
watch(() => session.businessId, load, { immediate: true })
</script>
