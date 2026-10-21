<template>
  <div>
    <h1>Orders</h1>
    <p v-if="!session.businessId" class="warn">Select a business first.</p>
    <template v-else>
      <section class="panel">
        <h2>New order</h2>
        <button type="button" :disabled="pending" @click="createDraft">Create draft</button>
        <p v-if="draftId" class="ok">Draft: {{ draftId.slice(0, 12) }}…</p>
        <form v-if="draftId" class="create" @submit.prevent="addLine">
          <select v-model="line.product_id" required>
            <option disabled value="">Select product</option>
            <option v-for="p in products" :key="p.id" :value="p.id">
              {{ p.name }} — {{ p.unit_price_amount }} {{ p.currency || 'IRR' }}
            </option>
          </select>
          <input v-model.number="line.quantity" type="number" min="1" required />
          <input v-model.number="line.unit_price_amount" type="number" min="0" required />
          <button type="submit">Add line</button>
        </form>
        <button v-if="draftId" type="button" :disabled="pending" @click="confirmDraft">
          Confirm order
        </button>
      </section>

      <p v-if="error" class="err">{{ error }}</p>

      <table v-if="items.length">
        <thead><tr><th>ID</th><th>Status</th><th></th></tr></thead>
        <tbody>
          <tr v-for="o in items" :key="o.id">
            <td>{{ o.id.slice(0, 12) }}…</td>
            <td>{{ o.status }}</td>
            <td>
              <button v-if="o.status === 'draft'" type="button" @click="confirmExisting(o.id)">
                Confirm
              </button>
            </td>
          </tr>
        </tbody>
      </table>
      <p v-else-if="!loading">No orders.</p>
    </template>
  </div>
</template>

<script setup lang="ts">
definePageMeta({ middleware: 'auth' })

const api = useApi()
const session = useSessionStore()
const items = ref<{ id: string; status: string }[]>([])
const products = ref<{ id: string; name: string; unit_price_amount: number; currency?: string }[]>([])
const loading = ref(false)
const pending = ref(false)
const error = ref('')
const draftId = ref('')
const line = reactive({ product_id: '', quantity: 1, unit_price_amount: 1000 })

watch(
  () => line.product_id,
  (id) => {
    const p = products.value.find(x => x.id === id)
    if (p) line.unit_price_amount = p.unit_price_amount
  },
)

async function loadProducts() {
  if (!session.businessId) return
  try {
    const res = await api.get<any>('/products')
    products.value = Array.isArray(res.data) ? res.data : []
  } catch {
    products.value = []
  }
}

async function load() {
  if (!session.businessId) return
  loading.value = true
  error.value = ''
  try {
    const res = await api.get<any>('/orders')
    items.value = Array.isArray(res.data) ? res.data : []
  } catch (e: unknown) {
    error.value = e instanceof Error ? e.message : 'Load failed'
  } finally {
    loading.value = false
  }
}

async function createDraft() {
  pending.value = true
  error.value = ''
  try {
    const res = await api.post<any>('/orders', undefined, crypto.randomUUID())
    draftId.value = res.data?.id || res.id
    await load()
  } catch (e: unknown) {
    error.value = e instanceof Error ? e.message : 'Create failed'
  } finally {
    pending.value = false
  }
}

async function addLine() {
  if (!draftId.value) return
  pending.value = true
  error.value = ''
  try {
    await api.post(
      `/orders/${draftId.value}/lines`,
      {
        product_id: line.product_id,
        quantity: line.quantity,
        unit_price_amount: line.unit_price_amount,
      },
      crypto.randomUUID(),
    )
    line.product_id = ''
    await load()
  } catch (e: unknown) {
    error.value = e instanceof Error ? e.message : 'Add line failed'
  } finally {
    pending.value = false
  }
}

async function confirmDraft() {
  if (!draftId.value) return
  await confirmExisting(draftId.value)
  draftId.value = ''
}

async function confirmExisting(id: string) {
  pending.value = true
  error.value = ''
  try {
    await api.post(`/orders/${id}/confirm`, {}, crypto.randomUUID())
    await load()
  } catch (e: unknown) {
    error.value = e instanceof Error ? e.message : 'Confirm failed'
  } finally {
    pending.value = false
  }
}

watch(
  () => session.businessId,
  async () => {
    await loadProducts()
    await load()
  },
  { immediate: true },
)
</script>

<style scoped>
.panel { margin-bottom: 1.25rem; padding-bottom: 1rem; border-bottom: 1px solid #eee; }
.create { display: flex; flex-wrap: wrap; gap: 0.5rem; margin: 0.75rem 0; }
.err { color: #b00; } .warn { color: #a60; } .ok { color: #060; font-size: 0.9rem; }
table { width: 100%; border-collapse: collapse; }
th, td { text-align: left; border-bottom: 1px solid #eee; padding: 0.4rem; }
</style>
