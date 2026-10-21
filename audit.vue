<template>
  <div>
    <h1>Audit log</h1>
    <p v-if="!session.businessId" class="warn">Select a business first.</p>
    <template v-else>
      <p v-if="error" class="err">{{ error }}</p>
      <table v-if="items.length">
        <thead>
          <tr><th>When</th><th>Action</th><th>Resource</th><th>Actor</th></tr>
        </thead>
        <tbody>
          <tr v-for="(a, i) in items" :key="a.id || i">
            <td>{{ a.occurred_at || '—' }}</td>
            <td>{{ a.action || '—' }}</td>
            <td>{{ a.resource_type }} {{ a.resource_id ? String(a.resource_id).slice(0, 10) + '…' : '' }}</td>
            <td>{{ a.actor_user_id ? String(a.actor_user_id).slice(0, 10) + '…' : '—' }}</td>
          </tr>
        </tbody>
      </table>
      <p v-else-if="!loading">No audit entries.</p>
    </template>
  </div>
</template>
<script setup lang="ts">
definePageMeta({ middleware: 'auth' })
const api = useApi()
const session = useSessionStore()
const items = ref<any[]>([])
const loading = ref(false)
const error = ref('')
async function load() {
  if (!session.businessId) return
  loading.value = true
  error.value = ''
  try {
    const res = await api.get<any>('/audit-log')
    items.value = Array.isArray(res.data) ? res.data : []
  } catch (e: unknown) {
    error.value = e instanceof Error ? e.message : 'Load failed'
  } finally {
    loading.value = false
  }
}
watch(() => session.businessId, load, { immediate: true })
</script>
<style scoped>
.err { color: #b00; } .warn { color: #a60; }
table { width: 100%; border-collapse: collapse; }
th, td { text-align: left; border-bottom: 1px solid #eee; padding: .4rem; font-size: .9rem; }
</style>
