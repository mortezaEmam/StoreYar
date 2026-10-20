<template>
  <div>
    <h1>Payments</h1>
    <p v-if="!session.businessId" class="warn">Select a business first.</p>
    <template v-else>
      <form class="create" @submit.prevent="initiate">
        <input v-model="form.order_id" placeholder="Order ID" required />
        <input v-model.number="form.amount" type="number" min="1" placeholder="Amount (minor)" required />
        <input v-model="form.currency" placeholder="Currency" />
        <button type="submit">Initiate payment</button>
      </form>
      <p v-if="error" class="err">{{ error }}</p>
      <table v-if="items.length">
        <thead>
          <tr><th>ID</th><th>Order</th><th>Amount</th><th>Status</th><th></th></tr>
        </thead>
        <tbody>
          <tr v-for="p in items" :key="p.id">
            <td>{{ p.id.slice(0, 10) }}…</td>
            <td>{{ p.order_id.slice(0, 10) }}…</td>
            <td>{{ p.amount }} {{ p.currency }}</td>
            <td>{{ p.status }}</td>
            <td>
              <button
                v-if="p.status === 'pending' || p.status === 'initiated'"
                type="button"
                @click="succeed(p.id)"
              >
                Mark succeeded
              </button>
            </td>
          </tr>
        </tbody>
      </table>
      <p v-else-if="!loading">No payments.</p>
    </template>
  </div>
</template>

<script setup lang="ts">
import type { ApiData, Paginated, Payment } from '~/types/api'

definePageMeta({ middleware: 'auth' })

const api = useApi()
const session = useSessionStore()
const items = ref<Payment[]>([])
const loading = ref(false)
const error = ref('')
const form = reactive({ order_id: '', amount: 1000, currency: 'IRR' })

async function load() {
  if (!session.businessId) return
  loading.value = true
  error.value = ''
  try {
    const res = await api.get<Paginated<Payment> | ApiData<Payment[]>>('/payments')
    items.value = Array.isArray((res as Paginated<Payment>).data)
      ? (res as Paginated<Payment>).data
      : ((res as ApiData<Payment[]>).data || [])
  } catch (e: unknown) {
    error.value = e instanceof Error ? e.message : 'Load failed'
  } finally {
    loading.value = false
  }
}

async function initiate() {
  error.value = ''
  try {
    await api.post('/payments', { ...form }, crypto.randomUUID())
    form.order_id = ''
    await load()
  } catch (e: unknown) {
    error.value = e instanceof Error ? e.message : 'Initiate failed'
  }
}

async function succeed(id: string) {
  error.value = ''
  try {
    await api.post(`/payments/${id}/succeed`, {}, crypto.randomUUID())
    await load()
  } catch (e: unknown) {
    error.value = e instanceof Error ? e.message : 'Succeed failed'
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
