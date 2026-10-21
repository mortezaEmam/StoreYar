<template>
  <div>
    <div class="page-head"><h1>Business</h1></div>
    <p class="hint">Choose the organization context for all product, stock, and order actions.</p>
    <div class="card">
      <div class="card-title">Your organizations</div>
      <p v-if="loading" class="hint">Loading…</p>
      <p v-if="error" class="err">{{ error }}</p>
      <div v-if="orgs.length" class="table-wrap">
        <table>
          <thead><tr><th>Name</th><th>ID</th><th></th></tr></thead>
          <tbody>
            <tr v-for="o in orgs" :key="o.id">
              <td>{{ o.name }}</td>
              <td><code style="font-size:.75rem">{{ o.id }}</code></td>
              <td>
                <button type="button" :class="session.businessId === o.id ? '' : 'secondary'" @click="select(o.id)">
                  {{ session.businessId === o.id ? 'Active' : 'Select' }}
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <div v-else-if="!loading" class="empty">No organizations yet. Create one below.</div>
    </div>
    <div class="card">
      <div class="card-title">Create organization</div>
      <form class="form-row" @submit.prevent="create">
        <label style="margin:0">Name
          <input v-model="newName" required placeholder="My shop" />
        </label>
        <button type="submit" :disabled="pending">{{ pending ? 'Creating…' : 'Create' }}</button>
      </form>
    </div>
    <p v-if="session.businessId" class="ok">
      Active business set. Continue to <NuxtLink to="/products">Products</NuxtLink>.
    </p>
  </div>
</template>
<script setup lang="ts">
import { useSessionStore } from '~/stores/session'
definePageMeta({ middleware: 'auth' })
const api = useApi(); const session = useSessionStore(); const toast = useToast()
const orgs = ref<{ id: string; name: string }[]>([])
const loading = ref(true); const pending = ref(false); const error = ref(''); const newName = ref('')
async function load() {
  loading.value = true; error.value = ''
  try {
    const res = await api.get<any>('/organizations')
    orgs.value = Array.isArray(res.data) ? res.data : []
  } catch (e: unknown) {
    error.value = e instanceof Error ? e.message : 'Failed to load'; toast.err(error.value)
  } finally { loading.value = false }
}
function select(id: string) {
  session.setBusinessId(id)
  toast.ok('Business selected')
}
async function create() {
  pending.value = true; error.value = ''
  try {
    const res = await api.post<any>('/organizations', { name: newName.value })
    newName.value = ''
    session.setBusinessId(res.data.id)
    toast.ok('Organization created')
    await load()
  } catch (e: unknown) {
    error.value = e instanceof Error ? e.message : 'Create failed'; toast.err(error.value)
  } finally { pending.value = false }
}
onMounted(load)
</script>
