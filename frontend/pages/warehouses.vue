<template>
  <div>
    <div class="page-head"><h1>{{ t('warehouses.title') }}</h1></div>
    <p v-if="!session.businessId" class="warn">
      {{ t('common.selectBusiness') }}
      <NuxtLink to="/business">{{ t('common.chooseBusiness') }}</NuxtLink>
    </p>
    <template v-else>
      <div class="card">
        <div class="card-title">{{ t('warehouses.add') }}</div>
        <form class="form-row" @submit.prevent="create">
          <label style="margin:0">{{ t('warehouses.code') }}
            <input v-model="form.code" required />
          </label>
          <label style="margin:0">{{ t('warehouses.name') }}
            <input v-model="form.name" required />
          </label>
          <button type="submit" :disabled="pending">{{ t('common.add') }}</button>
        </form>
      </div>
      <p v-if="error" class="err">{{ error }}</p>
      <div v-if="items.length" class="table-wrap" style="margin-top:1rem">
        <table>
          <thead>
            <tr>
              <th>{{ t('warehouses.code') }}</th>
              <th>{{ t('warehouses.name') }}</th>
              <th>{{ t('warehouses.default') }}</th>
              <th>{{ t('warehouses.active') }}</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="w in items" :key="w.id">
              <td><code>{{ w.code }}</code></td>
              <td>{{ w.name }}</td>
              <td>{{ w.is_default ? '✓' : '' }}</td>
              <td>{{ w.is_active !== false ? '✓' : '—' }}</td>
            </tr>
          </tbody>
        </table>
      </div>
      <div v-else-if="!loading" class="empty">{{ t('warehouses.empty') }}</div>
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
const pending = ref(false)
const error = ref('')
const form = reactive({ code: '', name: '' })

async function load() {
  if (!session.businessId) return
  loading.value = true
  try {
    items.value = api.unwrap(await api.get('/warehouses')) || []
  } catch (e: any) {
    error.value = e.message || t('common.error')
  } finally {
    loading.value = false
  }
}
async function create() {
  pending.value = true
  error.value = ''
  try {
    await api.post('/warehouses', { ...form })
    toast.push(t('warehouses.created'), 'ok')
    form.code = ''; form.name = ''
    await load()
  } catch (e: any) {
    error.value = e.message || t('common.error')
  } finally {
    pending.value = false
  }
}
onMounted(load)
watch(() => session.businessId, load)
</script>
