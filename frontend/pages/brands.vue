<template>
  <div>
    <div class="page-head"><h1>{{ t('brands.title') }}</h1></div>
    <p v-if="!session.businessId" class="warn">
      {{ t('common.selectBusiness') }}
      <NuxtLink to="/business">{{ t('common.chooseBusiness') }}</NuxtLink>
    </p>
    <template v-else>
      <div class="card">
        <div class="card-title">{{ t('brands.add') }}</div>
        <form class="form-row" @submit.prevent="create">
          <label style="margin:0">{{ t('brands.code') }}
            <input v-model="form.code" required />
          </label>
          <label style="margin:0">{{ t('brands.name') }}
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
              <th>{{ t('brands.code') }}</th>
              <th>{{ t('brands.name') }}</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="b in items" :key="b.id">
              <td><code>{{ b.code }}</code></td>
              <td>{{ b.name }}</td>
            </tr>
          </tbody>
        </table>
      </div>
      <div v-else-if="!loading" class="empty">{{ t('brands.empty') }}</div>
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
  error.value = ''
  try {
    items.value = api.unwrap(await api.get('/brands')) || []
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
    await api.post('/brands', { ...form })
    toast.ok(t('brands.created'))
    form.code = ''; form.name = ''
    await load()
  } catch (e: any) {
    error.value = e.message || t('common.error')
    toast.err(error.value)
  } finally {
    pending.value = false
  }
}
onMounted(load)
watch(() => session.businessId, load)
</script>
