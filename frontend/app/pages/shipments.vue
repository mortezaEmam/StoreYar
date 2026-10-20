<template>
  <div>
    <h1>Shipments</h1>
    <p v-if="!session.businessId" class="warn">Select a business first.</p>
    <template v-else>
      <form class="create" @submit.prevent="create">
        <input v-model="form.order_id" placeholder="Order ID" required />
        <button type="submit">Create shipment</button>
      </form>
      <p v-if="error" class="err">{{ error }}</p>
      <table v-if="items.length">
        <thead>
          <tr><th>ID</th><th>Order</th><th>Status</th><th>Tracking</th><th></th></tr>
        </thead>
        <tbody>
          <tr v-for="s in items" :key="s.id">
            <td>{{ s.id.slice(0, 10) }}…</td>
            <td>{{ s.order_id.slice(0, 10) }}…</td>
            <td>{{ s.status }}</td>
            <td>{{ s.tracking_number || '—' }}</td>
            <td>
              <button
                v-if="s.status !== 'shipped' && s.status !== 'delivered' && s.status !== 'cancelled'"
                type="button"
                @click="ship(s.id)"
              >
                Ship
              </button>
            </td>
          </tr>
        </tbody>
      </table>
      <p v-else-if="!loading">No shipments.</p>
    </template>
  </div>
</template>

<script setup lang="ts">
import type { ApiData, Paginated, Shipment } from '~/types/api'

definePageMeta({ middleware: 'auth' })

const api = useApi()
const session = useSessionStore()
const items = ref<Shipment[]>([])
const loading = ref(false)
const error = ref('')
const form = reactive({ order_id: '' })

async function load() {
  if (!session.businessId) return
  loading.value = true
  error.value = ''
  try {
    const res = await api.get<Paginated<Shipment> | ApiData<Shipment[]>>('/shipments')
    items.value = Array.isArray((res as Paginated<Shipment>).data)
      ? (res as Paginated<Shipment>).data
      : ((res as ApiData<Shipment[]>).data || [])
  } catch (e: unknown) {
    error.value = e instanceof Error ? e.message : 'Load failed'
  } finally {
    loading.value = false
  }
}

async function create() {
  error.value = ''
  try {
    await api.post('/shipments', { order_id: form.order_id }, crypto.randomUUID())
    form.order_id = ''
    await load()
  } catch (e: unknown) {
    error.value = e instanceof Error ? e.message : 'Create failed'
  }
}

async function ship(id: string) {
  error.value = ''
  const tracking = window.prompt('Tracking number', 'TRACK-1')
  if (!tracking) return
  try {
    await api.post(`/shipments/${id}/ship`, { tracking_number: tracking }, crypto.randomUUID())
    await load()
  } catch (e: unknown) {
    error.value = e instanceof Error ? e.message : 'Ship failed'
  }
}

watch(() => session.businessId, load, { immediate: true })
</script>

<style scoped>
.create { display: flex; flex-wrap: wrap; gap: 0.5rem; margin-bottom: 1rem; }
.err { color: #b00; } .warn { color: #a60; }
table { width: 100%; border-collapse: collapse; }
th, td { text-align: left; border-bottom: 1px solid #eee; padding: 0.4rem; }
</style>
