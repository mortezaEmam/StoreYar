<template>
  <div>
    <div class="page-head"><h1>{{ t('business.title') }}</h1></div>
    <p class="hint">{{ t('business.hint') }}</p>
    <div class="card">
      <div class="card-title">{{ t('business.yours') }}</div>
      <p v-if="loading" class="hint">{{ t('common.loading') }}</p>
      <p v-if="error" class="err">{{ error }}</p>
      <div v-if="orgs.length" class="table-wrap">
        <table>
          <thead><tr><th>{{ t('business.name') }}</th><th>ID</th><th></th></tr></thead>
          <tbody>
            <tr v-for="o in orgs" :key="o.id">
              <td>{{ o.name }}</td>
              <td><code style="font-size:.75rem">{{ o.id }}</code></td>
              <td>
                <button type="button" :class="session.businessId === o.id ? '' : 'secondary'" @click="select(o.id)">
                  {{ session.businessId === o.id ? t('business.active') : t('business.select') }}
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <div v-else-if="!loading" class="empty">{{ t('business.none') }}</div>
    </div>
    <div class="card">
      <div class="card-title">{{ t('business.create') }}</div>
      <form class="form-row" @submit.prevent="create">
        <label style="margin:0">{{ t('business.name') }}
          <input v-model="newName" required />
        </label>
        <button type="submit" :disabled="pending">{{ pending ? t('common.loading') : t('business.create') }}</button>
      </form>
    </div>
    <p v-if="session.businessId" class="ok">
      {{ t('business.continue') }}
      <NuxtLink to="/products">{{ t('nav.products') }}</NuxtLink>
    </p>
  </div>
</template>
<script setup lang="ts">
import { useSessionStore } from '~/stores/session'
definePageMeta({ middleware: 'auth' })
const { t } = useI18n()
const api = useApi()
const session = useSessionStore()
const toast = useToast()
const orgs = ref<{ id: string; name: string }[]>([])
const loading = ref(true)
const pending = ref(false)
const error = ref('')
const newName = ref('')
async function load() {
  loading.value = true
  error.value = ''
  try {
    const res = await api.get<any>('/organizations')
    orgs.value = Array.isArray(res.data) ? res.data : []
  } catch (e: unknown) {
    error.value = e instanceof Error ? e.message : t('common.error')
    toast.err(error.value)
  } finally {
    loading.value = false
  }
}
function select(id: string) {
  session.setBusinessId(id)
  toast.ok(t('business.selected'))
}
async function create() {
  pending.value = true
  error.value = ''
  try {
    const res = await api.post<any>('/organizations', { name: newName.value })
    newName.value = ''
    session.setBusinessId(res.data.id)
    toast.ok(t('business.created'))
    await load()
  } catch (e: unknown) {
    error.value = e instanceof Error ? e.message : t('common.error')
    toast.err(error.value)
  } finally {
    pending.value = false
  }
}
onMounted(load)
</script>
