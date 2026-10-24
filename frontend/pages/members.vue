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
      <p v-if="loading" class="hint">{{ t('common.loading') }}</p>

      <div v-if="items.length" class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>{{ t('members.email') }}</th>
              <th>{{ t('members.role') }}</th>
              <th>{{ t('products.actions') }}</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(m, i) in items" :key="memberKey(m, i)">
              <td>{{ displayEmail(m) }}</td>
              <td>
                <select
                  v-if="!isOwner(m)"
                  :value="normalizeRole(m.role)"
                  :disabled="pending"
                  @change="onRoleChange(m, ($event.target as HTMLSelectElement).value)"
                >
                  <option value="member">{{ t('members.roleMember') }}</option>
                  <option value="admin">{{ t('members.roleAdmin') }}</option>
                </select>
                <span v-else class="badge">{{ t('members.roleOwner') }}</span>
              </td>
              <td>
                <button
                  v-if="!isOwner(m)"
                  type="button"
                  class="danger"
                  :disabled="pending"
                  @click="remove(m)"
                >
                  {{ t('members.remove') }}
                </button>
                <span v-else class="hint">{{ t('members.cannotRemoveOwner') }}</span>
              </td>
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

function memberKey(m: any, i: number) {
  return m.user_id || m.id || m.email || i
}
function displayEmail(m: any) {
  return m.email || m.user_email || m.user_id || '—'
}
function userId(m: any): string {
  return String(m.user_id || m.id || '')
}
function normalizeRole(role: unknown) {
  const r = String(role || 'member').toLowerCase()
  if (r === 'admin' || r === 'owner' || r === 'member') return r
  return 'member'
}
function isOwner(m: any) {
  return normalizeRole(m.role) === 'owner'
}

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
    await api.post(
      `/organizations/${session.businessId}/members`,
      { email: form.email, role: form.role },
      crypto.randomUUID(),
    )
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

async function onRoleChange(m: any, role: string) {
  const id = userId(m)
  if (!id || isOwner(m)) return
  pending.value = true
  error.value = ''
  try {
    await api.patch(
      `/organizations/${session.businessId}/members/${id}`,
      { role },
      crypto.randomUUID(),
    )
    toast.ok(t('members.updated'))
    await load()
  } catch (e: unknown) {
    error.value = e instanceof Error ? e.message : t('common.error')
    toast.err(error.value)
    await load()
  } finally {
    pending.value = false
  }
}

async function remove(m: any) {
  if (isOwner(m)) return
  if (!confirm(t('members.confirmRemove'))) return
  const id = userId(m)
  if (!id) return
  pending.value = true
  error.value = ''
  try {
    if (typeof (api as any).del === 'function') {
      await (api as any).del(`/organizations/${session.businessId}/members/${id}`, crypto.randomUUID())
    } else {
      await api.post(
        `/organizations/${session.businessId}/members/${id}`,
        { _method: 'DELETE' },
        crypto.randomUUID(),
      )
    }
    toast.ok(t('members.removed'))
    await load()
  } catch (e: unknown) {
    // fallback fetch DELETE if del missing
    try {
      const config = useRuntimeConfig()
      const base = (config.public.apiBase as string) || 'http://localhost:8000/api'
      const headers: Record<string, string> = {
        Accept: 'application/json',
        Authorization: `Bearer ${session.token}`,
        'X-Business-Id': session.businessId!,
        'Idempotency-Key': crypto.randomUUID(),
      }
      const res = await fetch(`${base}/organizations/${session.businessId}/members/${id}`, {
        method: 'DELETE',
        headers,
      })
      if (!res.ok) throw new Error(await res.text())
      toast.ok(t('members.removed'))
      await load()
    } catch (e2: unknown) {
      error.value = e2 instanceof Error ? e2.message : (e instanceof Error ? e.message : t('common.error'))
      toast.err(error.value)
    }
  } finally {
    pending.value = false
  }
}

watch(() => session.businessId, load, { immediate: true })
</script>
