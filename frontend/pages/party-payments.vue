<template>
  <div>
    <div class="page-head"><h1>{{ t('partyPay.title') }}</h1></div>
    <p v-if="!session.businessId" class="warn">
      {{ t('common.selectBusiness') }}
      <NuxtLink to="/business">{{ t('common.chooseBusiness') }}</NuxtLink>
    </p>
    <template v-else>
      <div class="grid-2">
        <div class="card">
          <div class="card-title">{{ t('partyPay.fromCustomer') }}</div>
          <form class="form-col" @submit.prevent="payCustomer">
            <label>{{ t('nav.customers') }}
              <select v-model="cust.customer_id" required>
                <option disabled value="">{{ t('common.select') }}</option>
                <option v-for="c in customers" :key="c.id" :value="c.id">
                  {{ c.name }} ({{ c.outstanding_balance_amount ?? 0 }})
                </option>
              </select>
            </label>
            <label>{{ t('partyPay.amount') }}
              <input v-model.number="cust.amount" type="number" min="1" required />
            </label>
            <label>{{ t('partyPay.method') }}
              <select v-model="cust.method">
                <option value="CASH">{{ t('pos.cash') }}</option>
                <option value="BANK">{{ t('partyPay.bank') }}</option>
                <option value="CARD">{{ t('pos.card') }}</option>
              </select>
            </label>
            <button type="submit" :disabled="pending">{{ t('partyPay.submit') }}</button>
          </form>
        </div>
        <div class="card">
          <div class="card-title">{{ t('partyPay.toSupplier') }}</div>
          <form class="form-col" @submit.prevent="paySupplier">
            <label>{{ t('nav.suppliers') }}
              <select v-model="sup.supplier_id" required>
                <option disabled value="">{{ t('common.select') }}</option>
                <option v-for="s in suppliers" :key="s.id" :value="s.id">
                  {{ s.name }} ({{ s.outstanding_balance_amount ?? 0 }})
                </option>
              </select>
            </label>
            <label>{{ t('partyPay.amount') }}
              <input v-model.number="sup.amount" type="number" min="1" required />
            </label>
            <label>{{ t('partyPay.method') }}
              <select v-model="sup.method">
                <option value="CASH">{{ t('pos.cash') }}</option>
                <option value="BANK">{{ t('partyPay.bank') }}</option>
                <option value="CARD">{{ t('pos.card') }}</option>
              </select>
            </label>
            <button type="submit" :disabled="pending">{{ t('partyPay.submit') }}</button>
          </form>
        </div>
      </div>
      <p v-if="error" class="err">{{ error }}</p>
      <div v-if="history.length" class="table-wrap" style="margin-top:1rem">
        <table>
          <thead>
            <tr>
              <th>{{ t('partyPay.doc') }}</th>
              <th>{{ t('partyPay.party') }}</th>
              <th>{{ t('partyPay.direction') }}</th>
              <th>{{ t('partyPay.amount') }}</th>
              <th>{{ t('partyPay.method') }}</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="p in history" :key="p.id">
              <td><code>{{ p.document_number || p.id?.slice(0, 8) }}</code></td>
              <td>{{ p.party_type }} · {{ p.party_id?.slice(0, 8) }}…</td>
              <td>{{ p.direction }}</td>
              <td>{{ p.amount }}</td>
              <td>{{ p.method }}</td>
            </tr>
          </tbody>
        </table>
      </div>
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
const customers = ref<any[]>([])
const suppliers = ref<any[]>([])
const history = ref<any[]>([])
const pending = ref(false)
const error = ref('')
const cust = reactive({ customer_id: '', amount: 0, method: 'CASH' })
const sup = reactive({ supplier_id: '', amount: 0, method: 'CASH' })

async function load() {
  if (!session.businessId) return
  error.value = ''
  try {
    const [c, s, h] = await Promise.all([
      api.get('/customers'),
      api.get('/suppliers'),
      api.get('/party-payments'),
    ])
    customers.value = api.unwrap(c) || []
    suppliers.value = api.unwrap(s) || []
    history.value = api.unwrap(h) || []
  } catch (e: any) {
    error.value = e.message || t('common.error')
  }
}
async function payCustomer() {
  pending.value = true
  error.value = ''
  try {
    await api.post('/party-payments/customers', { ...cust })
    toast.push(t('partyPay.ok'), 'ok')
    cust.amount = 0
    await load()
  } catch (e: any) {
    error.value = e.message || t('common.error')
  } finally {
    pending.value = false
  }
}
async function paySupplier() {
  pending.value = true
  error.value = ''
  try {
    await api.post('/party-payments/suppliers', { ...sup })
    toast.push(t('partyPay.ok'), 'ok')
    sup.amount = 0
    await load()
  } catch (e: any) {
    error.value = e.message || t('common.error')
  } finally {
    pending.value = false
  }
}
onMounted(load)
watch(() => session.businessId, load)
</script>
<style scoped>
.grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
@media (max-width: 800px) { .grid-2 { grid-template-columns: 1fr; } }
.form-col { display: flex; flex-direction: column; gap: .75rem; }
</style>
