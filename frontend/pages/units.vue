<template>
  <div>
    <div class="page-head"><h1>{{ t('units.title') }}</h1></div>
    <p v-if="!session.businessId" class="warn">
      {{ t('common.selectBusiness') }}
      <NuxtLink to="/business">{{ t('common.chooseBusiness') }}</NuxtLink>
    </p>
    <template v-else>
      <div class="card">
        <div class="card-title">{{ t('units.add') }}</div>
        <form class="form-row" @submit.prevent="create">
          <label style="margin:0">{{ t('units.code') }}
            <input v-model="form.code" required placeholder="PCS" />
          </label>
          <label style="margin:0">{{ t('units.name') }}
            <input v-model="form.name" required placeholder="Piece" />
          </label>
          <label style="margin:0">{{ t('units.symbol') }}
            <input v-model="form.symbol" placeholder="عدد" />
          </label>
          <label style="margin:0">
            <input v-model="form.is_base" type="checkbox" /> {{ t('units.base') }}
          </label>
          <button type="submit" :disabled="pending">{{ t('common.add') }}</button>
        </form>
      </div>
      <p v-if="error" class="err">{{ error }}</p>
      <div v-if="items.length" class="table-wrap" style="margin-top:1rem">
        <table>
          <thead>
            <tr>
              <th>{{ t('units.code') }}</th>
              <th>{{ t('units.name') }}</th>
              <th>{{ t('units.symbol') }}</th>
              <th>{{ t('units.base') }}</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="u in items" :key="u.id">
              <td><code>{{ u.code }}</code></td>
              <td>{{ u.name }}</td>
              <td>{{ u.symbol }}</td>
              <td>{{ u.is_base ? '✓' : '' }}</td>
            </tr>
          </tbody>
        </table>
      </div>
      <div v-else-if="!loading" class="empty">{{ t('units.empty') }}</div>
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
const form = reactive({ code: '', name: '', symbol: '', is_base: false })

async function load() {
  if (!session.businessId) return
  loading.value = true
  error.value = ''
  try {
    items.value = api.unwrap(await api.get('/units')) || []
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
    await api.post('/units', { ...form })
    toast.ok(t('units.created'))
    form.code = ''; form.name = ''; form.symbol = ''; form.is_base = false
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
