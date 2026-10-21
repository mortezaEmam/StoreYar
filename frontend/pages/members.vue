<template>
  <div>
    <div class="page-head"><h1>Members</h1></div>
    <p v-if="!session.businessId" class="warn">Select a business first. <NuxtLink to="/business">Choose business</NuxtLink></p>
    <template v-else>
      <div class="card">
        <div class="card-title">Grant membership</div>
        <form class="form-row" @submit.prevent="grant">
          <label style="margin:0">User ID
            <input v-model="form.user_id" required placeholder="ULID of user" />
          </label>
          <label style="margin:0">Role
            <select v-model="form.role">
              <option value="member">member</option>
              <option value="admin">admin</option>
              <option value="owner">owner</option>
            </select>
          </label>
          <button type="submit" :disabled="pending">Grant</button>
        </form>
      </div>
      <p v-if="error" class="err">{{ error }}</p>
      <p v-if="success" class="ok">{{ success }}</p>
      <div v-if="items.length" class="table-wrap">
        <table>
          <thead><tr><th>User</th><th>Role</th><th></th></tr></thead>
          <tbody>
            <tr v-for="m in items" :key="m.user_id">
              <td>
                <code style="font-size:.8rem">{{ m.user_id.slice(0, 12) }}…</code>
                <span v-if="m.email" class="hint" style="margin-left:.35rem">{{ m.email }}</span>
              </td>
              <td><span class="badge">{{ m.role }}</span></td>
              <td>
                <button type="button" class="danger" @click="revoke(m.user_id)">Revoke</button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <div v-else-if="!loading" class="empty">No members loaded.</div>
    </template>
  </div>
</template>
<script setup lang="ts">
import { useSessionStore } from '~/stores/session'
definePageMeta({ middleware: 'auth' })
const api = useApi(); const session = useSessionStore()
const items = ref<any[]>([]); const loading = ref(false); const pending = ref(false)
const error = ref(''); const success = ref('')
const form = reactive({ user_id: '', role: 'member' })
async function load() {
  if (!session.businessId) return
  loading.value = true; error.value = ''
  try {
    const res = await api.get<any>(`/organizations/${session.businessId}/members`)
    items.value = Array.isArray(res.data) ? res.data : (Array.isArray(res) ? res : [])
  } catch (e: unknown) {
    error.value = e instanceof Error ? e.message : 'Load failed'
  } finally { loading.value = false }
}
async function grant() {
  pending.value = true; error.value = ''; success.value = ''
  try {
    await api.post(`/organizations/${session.businessId}/members`, { ...form })
    success.value = 'Membership granted.'; form.user_id = ''; await load()
  } catch (e: unknown) {
    error.value = e instanceof Error ? e.message : 'Grant failed'
  } finally { pending.value = false }
}
async function revoke(userId: string) {
  if (!confirm('Revoke this member?')) return
  pending.value = true; error.value = ''; success.value = ''
  try {
    await api.del(`/organizations/${session.businessId}/members/${userId}`)
    success.value = 'Membership revoked.'; await load()
  } catch (e: unknown) {
    error.value = e instanceof Error ? e.message : 'Revoke failed'
  } finally { pending.value = false }
}
watch(() => session.businessId, load, { immediate: true })
</script>
