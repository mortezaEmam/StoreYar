<template>
  <div>
    <h1>Members</h1>
    <p v-if="!session.businessId" class="warn">Select a business first.</p>
    <template v-else>
      <form class="create" @submit.prevent="grant">
        <input v-model="form.user_id" placeholder="User ID" required />
        <select v-model="form.role">
          <option value="member">member</option>
          <option value="admin">admin</option>
          <option value="owner">owner</option>
        </select>
        <button type="submit">Grant membership</button>
      </form>
      <p v-if="error" class="err">{{ error }}</p>
      <table v-if="items.length">
        <thead><tr><th>User</th><th>Role</th><th></th></tr></thead>
        <tbody>
          <tr v-for="m in items" :key="m.user_id">
            <td>{{ m.user_id.slice(0, 12) }}… {{ m.email || '' }}</td>
            <td>{{ m.role }}</td>
            <td><button type="button" @click="revoke(m.user_id)">Revoke</button></td>
          </tr>
        </tbody>
      </table>
      <p v-else-if="!loading">No members loaded.</p>
    </template>
  </div>
</template>
<script setup lang="ts">
definePageMeta({ middleware: 'auth' })
const api = useApi()
const session = useSessionStore()
const items = ref<{ user_id: string; role: string; email?: string }[]>([])
const loading = ref(false)
const error = ref('')
const form = reactive({ user_id: '', role: 'member' })
async function load() {
  if (!session.businessId) return
  loading.value = true
  error.value = ''
  try {
    const res = await api.get<any>(`/organizations/${session.businessId}/members`)
    items.value = Array.isArray(res.data) ? res.data : (Array.isArray(res) ? res : [])
  } catch (e: unknown) {
    error.value = e instanceof Error ? e.message : 'Load failed'
  } finally {
    loading.value = false
  }
}
async function grant() {
  try {
    await api.post(`/organizations/${session.businessId}/members`, { ...form })
    form.user_id = ''
    await load()
  } catch (e: unknown) {
    error.value = e instanceof Error ? e.message : 'Grant failed'
  }
}
async function revoke(userId: string) {
  try {
    await api.del(`/organizations/${session.businessId}/members/${userId}`)
    await load()
  } catch (e: unknown) {
    error.value = e instanceof Error ? e.message : 'Revoke failed'
  }
}
watch(() => session.businessId, load, { immediate: true })
</script>
<style scoped>
.create { display: flex; flex-wrap: wrap; gap: .5rem; margin-bottom: 1rem; }
.err { color: #b00; } .warn { color: #a60; }
table { width: 100%; border-collapse: collapse; }
th, td { text-align: left; border-bottom: 1px solid #eee; padding: .4rem; }
</style>
