<template>
  <div>
    <div class="page-head"><h1>{{ t('customers.title') }}</h1></div>
    <p v-if="!session.businessId" class="warn">
      {{ t('common.selectBusiness') }}
      <NuxtLink to="/business">{{ t('common.chooseBusiness') }}</NuxtLink>
    </p>
    <template v-else>
      <div class="card">
        <div class="card-title">{{ t('customers.add') }}</div>
        <form class="form-row" @submit.prevent="create">
          <label style="margin:0">{{ t('customers.code') }}
            <input v-model="form.code" required />
          </label>
          <label style="margin:0">{{ t('customers.name') }}
            <input v-model="form.name" required />
          </label>
          <label style="margin:0">{{ t('customers.phone') }}
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
              <th>{{ t('customers.code') }}</th>
              <th>{{ t('customers.name') }}</th>
              <th>{{ t('customers.phone') }}</th>
              <th>{{ t('customers.balance') }}</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="c in items" :key="c.id">
              <td>{{ c.code }}</td>
              <td>{{ c.name }}</td>
              <td>{{ c.phone }}</td>
              <td>{{ c.outstanding_balance_amount ?? 0 }}</td>
            </tr>
          </tbody>
        </table>
      </div>
      <div v-else-if="!loading" class="empty">{{ t('customers.empty') }}</div>
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
  try {
    const res = await api.get('/customers')
    items.value = api.unwrap(res) || []
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
    await api.post('/customers', { ...form })
    toast.push(t('customers.created'), 'ok')
    form.code = ''
    form.name = ''
    form.phone = ''
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
