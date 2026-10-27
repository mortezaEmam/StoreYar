<template>
  <div>
    <div class="page-head"><h1>{{ t('suppliers.title') }}</h1></div>
    <p v-if="!session.businessId" class="warn">
      {{ t('common.selectBusiness') }}
      <NuxtLink to="/business">{{ t('common.chooseBusiness') }}</NuxtLink>
    </p>
    <template v-else>
      <div class="card">
        <div class="card-title">{{ t('suppliers.add') }}</div>
        <form class="form-row" @submit.prevent="create">
          <label style="margin:0">{{ t('suppliers.code') }}
            <input v-model="form.code" required />
          </label>
          <label style="margin:0">{{ t('suppliers.name') }}
            <input v-model="form.name" required />
          </label>
          <label style="margin:0">{{ t('suppliers.phone') }}
            <input v-model="form.phone" />
          </label>
          <button type="submit" :disabled="pending">{{ t('common.add') }}</button>
        </form>
      </div>
      <p v-if="error" class="err">{{ error }}</p>
      <div v-if="items.length" class="table-wrap" style="margin-top:1rem">
        <table>
          <thead>
            <tr>
              <th>{{ t('suppliers.code') }}</th>
              <th>{{ t('suppliers.name') }}</th>
              <th>{{ t('suppliers.phone') }}</th>
              <th>{{ t('suppliers.balance') }}</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="s in items" :key="s.id">
              <td>{{ s.code }}</td>
              <td>{{ s.name }}</td>
              <td>{{ s.phone }}</td>
              <td>{{ s.outstanding_balance_amount ?? 0 }}</td>
            </tr>
          </tbody>
        </table>
      </div>
      <div v-else-if="!loading" class="empty">{{ t('suppliers.empty') }}</div>
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
const form = reactive({ code: '', name: '', phone: '' })

async function load() {
  if (!session.businessId) return
  loading.value = true
  error.value = ''
  try {
    items.value = api.unwrap(await api.get('/suppliers')) || []
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
    await api.post('/suppliers', { ...form })
    toast.push(t('suppliers.created'), 'ok')
    form.code = ''; form.name = ''; form.phone = ''
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
