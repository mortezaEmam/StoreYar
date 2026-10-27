<template>
  <div>
    <div class="page-head"><h1>{{ t('fiscal.title') }}</h1></div>
    <p v-if="!session.businessId" class="warn">
      {{ t('common.selectBusiness') }}
      <NuxtLink to="/business">{{ t('common.chooseBusiness') }}</NuxtLink>
    </p>
    <template v-else>
      <div class="card">
        <div class="card-title">{{ t('fiscal.add') }}</div>
        <form class="form-row" @submit.prevent="create">
          <label style="margin:0">{{ t('fiscal.name') }}
            <input v-model="form.name" required placeholder="1404" />
          </label>
          <label style="margin:0">{{ t('fiscal.start') }}
            <input v-model="form.starts_on" type="date" required />
          </label>
          <label style="margin:0">{{ t('fiscal.end') }}
            <input v-model="form.ends_on" type="date" required />
          </label>
          <button type="submit" :disabled="pending">{{ t('common.add') }}</button>
        </form>
      </div>
      <p v-if="error" class="err">{{ error }}</p>
      <div v-if="items.length" class="table-wrap" style="margin-top:1rem">
        <table>
          <thead>
            <tr>
              <th>{{ t('fiscal.name') }}</th>
              <th>{{ t('fiscal.start') }}</th>
              <th>{{ t('fiscal.end') }}</th>
              <th>{{ t('fiscal.status') }}</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="y in items" :key="y.id">
              <td>{{ y.name }}</td>
              <td>{{ y.starts_on || y.start_date }}</td>
              <td>{{ y.ends_on || y.end_date }}</td>
              <td>{{ y.status || (y.is_closed ? 'CLOSED' : 'OPEN') }}</td>
            </tr>
          </tbody>
        </table>
      </div>
      <div v-else-if="!loading" class="empty">{{ t('fiscal.empty') }}</div>
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
const form = reactive({
  name: '',
  starts_on: '',
  ends_on: '',
})

async function load() {
  if (!session.businessId) return
  loading.value = true
  error.value = ''
  try {
    items.value = api.unwrap(await api.get('/accounting/fiscal-years')) || []
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
    // support both field naming variants
    await api.post('/accounting/fiscal-years', {
      name: form.name,
      starts_on: form.starts_on,
      ends_on: form.ends_on,
    })
    toast.ok(t('fiscal.created'))
    form.name = ''; form.starts_on = ''; form.ends_on = ''
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
