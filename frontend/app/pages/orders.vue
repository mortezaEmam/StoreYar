<template>
  <div>
    <h1>Orders</h1>
    <p v-if="!session.businessId" class="warn">Select a business first.</p>
    <template v-else>
      <p v-if="error" class="err">{{ error }}</p>
      <table v-if="items.length">
        <thead><tr><th>ID</th><th>Status</th><th></th></tr></thead>
        <tbody>
          <tr v-for="o in items" :key="o.id">
            <td>{{ o.id.slice(0, 10) }}…</td>
            <td>{{ o.status }}</td>
            <td>
              <button v-if="o.status === 'draft'" type="button" @click="confirm(o.id)">Confirm</button>
            </td>
          </tr>
        </tbody>
      </table>
      <p v-else-if="!loading">No orders.</p>
    </template>
  </div>
</template>
<script setup lang="ts">
import type { ApiData, Order, Paginated } from '~/types/api'
definePageMeta({ middleware: 'auth' })
const api = useApi()
const session = useSessionStore()
const items = ref<Order[]>([])
const loading = ref(false)
const error = ref('')
async function load() {
  if (!session.businessId) return
  loading.value = true
  error.value = ''
  try {
    const res = await api.get<Paginated<Order> | ApiData<Order[]>>('/orders')
    items.value = Array.isArray((res as Paginated<Order>).data)
      ? (res as Paginated<Order>).data
      : ((res as ApiData<Order[]>).data || [])
  } catch (e: unknown) {
    error.value = e instanceof Error ? e.message : 'Load failed'
  } finally {
    loading.value = false
  }
}
async function confirm(id: string) {
  error.value = ''
  try {
    await api.post(`/orders/${id}/confirm`, {}, crypto.randomUUID())
    await load()
  } catch (e: unknown) {
    error.value = e instanceof Error ? e.message : 'Confirm failed'
  }
}
watch(() => session.businessId, load, { immediate: true })
</script>
<style scoped>
.err { color: #b00; } .warn { color: #a60; }
table { width: 100%; border-collapse: collapse; }
th, td { text-align: left; border-bottom: 1px solid #eee; padding: 0.4rem; }
</style>
