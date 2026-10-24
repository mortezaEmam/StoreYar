<template>
  <div>
    <div class="page-head"><h1>{{ t('members.title') }}</h1></div>
    <p v-if="!session.businessId" class="warn">
      {{ t('common.selectBusiness') }}
      <NuxtLink to="/business">{{ t('common.chooseBusiness') }}</NuxtLink>
    </p>
    <template v-else>
      <div class="card">
        <div class="card-title">{{ t('members.invite') }}</div>
        <form class="form-row" @submit.prevent="invite">
          <label style="margin:0">{{ t('members.email') }}
            <input v-model="form.email" type="email" required />
          </label>
          <label style="margin:0">{{ t('members.role') }}
            <select v-model="form.role">
              <option value="member">{{ t('members.roleMember') }}</option>
              <option value="admin">{{ t('members.roleAdmin') }}</option>
            </select>
          </label>
          <button type="submit" :disabled="pending">{{ t('members.invite') }}</button>
        </form>
      </div>
      <p v-if="error" class="err">{{ error }}</p>
      <div v-if="items.length" class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>{{ t('members.email') }}</th>
              <th>{{ t('members.role') }}</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(m, i) in items" :key="m.user_id || m.id || i">
              <td>{{ m.email || m.user_email || m.user_id || '—' }}</td>
              <td><span class="badge">{{ m.role || '—' }}</span></td>
            </tr>
          </tbody>
        </table>
      </div>
      <div v-else-if="!loading" class="empty">{{ t('members.empty') }}</div>
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
const form = reactive({ email: '', role: 'member' })
async function load() {
  if (!session.businessId) return
  loading.value = true
  error.value = ''
  try {
    const res = await api.get<any>(`/organizations/${session.businessId}/members`)
    items.value = Array.isArray(res.data) ? res.data : (Array.isArray(res) ? res : [])
  } catch (e: unknown) {
    error.value = e instanceof Error ? e.message : t('common.error')
    toast.err(error.value)
  } finally {
    loading.value = false
  }
}
async function invite() {
  pending.value = true
  error.value = ''
  try {
    await api.post(`/organizations/${session.businessId}/members`, {
      email: form.email,
      role: form.role,
    }, crypto.randomUUID())
    toast.ok(t('members.invited'))
    form.email = ''
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
