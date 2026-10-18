<template>
  <div>
    <h1>Select business</h1>
    <p v-if="loading">Loading…</p>
    <p v-if="error" class="err">{{ error }}</p>
    <ul v-if="orgs.length">
      <li v-for="o in orgs" :key="o.id">
        <button type="button" @click="select(o.id)">{{ o.name }}</button>
        <span v-if="session.businessId === o.id"> (active)</span>
      </li>
    </ul>
    <hr />
    <h2>Create organization</h2>
    <form @submit.prevent="create">
      <input v-model="newName" placeholder="Name" required />
      <button type="submit">Create</button>
    </form>
  </div>
</template>
<script setup lang="ts">
import type { ApiData, Organization, Paginated } from '~/types/api'
definePageMeta({ middleware: 'auth' })
const api = useApi()
const session = useSessionStore()
const orgs = ref<Organization[]>([])
const loading = ref(true)
const error = ref('')
const newName = ref('')
async function load() {
  loading.value = true
  error.value = ''
  try {
    const res = await api.get<Paginated<Organization> | ApiData<Organization[]>>('/organizations')
    orgs.value = Array.isArray((res as Paginated<Organization>).data)
      ? (res as Paginated<Organization>).data
      : ((res as ApiData<Organization[]>).data || [])
  } catch (e: unknown) {
    error.value = e instanceof Error ? e.message : 'Failed to load'
  } finally {
    loading.value = false
  }
}
function select(id: string) { session.setBusinessId(id) }
async function create() {
  error.value = ''
  try {
    const res = await api.post<ApiData<Organization>>('/organizations', { name: newName.value })
    newName.value = ''
    session.setBusinessId(res.data.id)
    await load()
  } catch (e: unknown) {
    error.value = e instanceof Error ? e.message : 'Create failed'
  }
}
onMounted(load)
</script>
<style scoped>
.err { color: #b00; }
</style>
