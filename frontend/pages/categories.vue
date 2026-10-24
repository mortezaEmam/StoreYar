<template>
  <div>
    <div class="page-head"><h1>{{ t('categories.title') }}</h1></div>
    <p v-if="!session.businessId" class="warn">
      {{ t('common.selectBusiness') }}
      <NuxtLink to="/business">{{ t('common.chooseBusiness') }}</NuxtLink>
    </p>
    <template v-else>
      <div class="card">
        <div class="card-title">{{ t('categories.add') }}</div>
        <form class="form-row" @submit.prevent="create">
          <label style="margin:0">{{ t('categories.name') }}
            <input v-model="name" required maxlength="120" />
          </label>
          <button type="submit" :disabled="pending">{{ t('common.add') }}</button>
        </form>
      </div>
      <p v-if="error" class="err">{{ error }}</p>
      <div v-if="items.length" class="table-wrap">
        <table>
          <thead><tr><th>{{ t('categories.name') }}</th><th>ID</th></tr></thead>
          <tbody>
            <tr v-for="c in items" :key="c.id">
              <td>{{ c.name }}</td>
              <td><code style="font-size:.75rem">{{ c.id }}</code></td>
            </tr>
          </tbody>
        </table>
      </div>
      <div v-else-if="!loading" class="empty">{{ t('categories.empty') }}</div>
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
const name = ref('')
const loading = ref(false)
const pending = ref(false)
const error = ref('')
async function load() {
  if (!session.businessId) return
  loading.value = true
  error.value = ''
  try {
    const res = await api.get<any>('/categories')
    items.value = Array.isArray(res.data) ? res.data : []
  } catch (e: unknown) {
    error.value = e instanceof Error ? e.message : t('common.error')
    toast.err(error.value)
  } finally {
    loading.value = false
  }
}
async function create() {
  pending.value = true
  error.value = ''
  try {
    await api.post('/categories', { name: name.value }, crypto.randomUUID())
    toast.ok(t('categories.created'))
    name.value = ''
    await load()
  } catch (e: unknown) {
    error.value = e instanceof Error ? e.message : t('common.error')
    toast.err(error.value)
  } finally {
    pending.value = false
  }
}
watch(() => session.businessId, load, { immediate: true })
</script>
