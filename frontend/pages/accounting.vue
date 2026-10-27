<template>
  <div>
    <div class="page-head"><h1>{{ t('accounting.title') }}</h1></div>
    <p v-if="!session.businessId" class="warn">
      {{ t('common.selectBusiness') }}
      <NuxtLink to="/business">{{ t('common.chooseBusiness') }}</NuxtLink>
    </p>
    <template v-else>
      <div class="tabs">
        <button type="button" :class="{ active: tab === 'accounts' }" @click="tab = 'accounts'">{{ t('accounting.accounts') }}</button>
        <button type="button" :class="{ active: tab === 'journals' }" @click="tab = 'journals'">{{ t('accounting.journals') }}</button>
      </div>
      <p v-if="error" class="err">{{ error }}</p>

      <template v-if="tab === 'accounts'">
        <div class="card">
          <div class="card-title">{{ t('accounting.addAccount') }}</div>
          <form class="form-row" @submit.prevent="createAccount">
            <label style="margin:0">{{ t('accounting.code') }}
              <input v-model="acc.code" required />
            </label>
            <label style="margin:0">{{ t('accounting.name') }}
              <input v-model="acc.name" required />
            </label>
            <label style="margin:0">{{ t('accounting.type') }}
              <select v-model="acc.type">
                <option value="ASSET">ASSET</option>
                <option value="LIABILITY">LIABILITY</option>
                <option value="EQUITY">EQUITY</option>
                <option value="REVENUE">REVENUE</option>
                <option value="EXPENSE">EXPENSE</option>
              </select>
            </label>
            <label style="margin:0">{{ t('accounting.subtype') }}
              <input v-model="acc.subtype" placeholder="CASH, AR, SALES..." />
            </label>
            <button type="submit" :disabled="pending">{{ t('common.add') }}</button>
          </form>
        </div>
        <div v-if="accounts.length" class="table-wrap" style="margin-top:1rem">
          <table>
            <thead>
              <tr>
                <th>{{ t('accounting.code') }}</th>
                <th>{{ t('accounting.name') }}</th>
                <th>{{ t('accounting.type') }}</th>
                <th>{{ t('accounting.subtype') }}</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="a in accounts" :key="a.id">
                <td><code>{{ a.code }}</code></td>
                <td>{{ a.name }}</td>
                <td>{{ a.type }}</td>
                <td>{{ a.subtype }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </template>

      <template v-else>
        <div class="card">
          <div class="card-title">{{ t('accounting.newJournal') }}</div>
          <form class="form-col" @submit.prevent="createJournal">
            <label>{{ t('accounting.date') }}
              <input v-model="je.entry_date" type="date" required />
            </label>
            <label>{{ t('accounting.memo') }}
              <input v-model="je.memo" />
            </label>
            <div v-for="(line, i) in je.lines" :key="i" class="form-row">
              <label style="margin:0">{{ t('accounting.account') }}
                <select v-model="line.account_id" required>
                  <option disabled value="">{{ t('common.select') }}</option>
                  <option v-for="a in accounts" :key="a.id" :value="a.id">{{ a.code }} — {{ a.name }}</option>
                </select>
              </label>
              <label style="margin:0">Debit
                <input v-model.number="line.debit_amount" type="number" min="0" />
              </label>
              <label style="margin:0">Credit
                <input v-model.number="line.credit_amount" type="number" min="0" />
              </label>
            </div>
            <button type="button" class="secondary" @click="je.lines.push({ account_id: '', debit_amount: 0, credit_amount: 0 })">+ line</button>
            <label><input v-model="je.post" type="checkbox" /> {{ t('accounting.postNow') }}</label>
            <button type="submit" :disabled="pending">{{ t('accounting.saveJournal') }}</button>
          </form>
        </div>
        <div v-if="journals.length" class="table-wrap" style="margin-top:1rem">
          <table>
            <thead>
              <tr>
                <th>{{ t('accounting.doc') }}</th>
                <th>{{ t('accounting.date') }}</th>
                <th>{{ t('accounting.status') }}</th>
                <th>Debit</th>
                <th>Credit</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="j in journals" :key="j.id">
                <td><code>{{ j.document_number || j.id?.slice(0, 8) }}</code></td>
                <td>{{ j.entry_date }}</td>
                <td>{{ j.status }}</td>
                <td>{{ j.total_debit }}</td>
                <td>{{ j.total_credit }}</td>
                <td>
                  <button
                    v-if="j.status === 'DRAFT'"
                    type="button"
                    class="secondary"
                    :disabled="pending"
                    @click="postJournal(j.id)"
                  >{{ t('accounting.post') }}</button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </template>
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
const tab = ref<'accounts' | 'journals'>('accounts')
const accounts = ref<any[]>([])
const journals = ref<any[]>([])
const pending = ref(false)
const error = ref('')
const acc = reactive({ code: '', name: '', type: 'ASSET', subtype: '' })
const je = reactive({
  entry_date: new Date().toISOString().slice(0, 10),
  memo: '',
  post: true,
  lines: [
    { account_id: '', debit_amount: 0, credit_amount: 0 },
    { account_id: '', debit_amount: 0, credit_amount: 0 },
  ],
})

async function load() {
  if (!session.businessId) return
  error.value = ''
  try {
    const [a, j] = await Promise.all([
      api.get('/accounting/accounts'),
      api.get('/accounting/journals'),
    ])
    accounts.value = api.unwrap(a) || []
    journals.value = api.unwrap(j) || []
  } catch (e: any) {
    error.value = e.message || t('common.error')
  }
}
async function createAccount() {
  pending.value = true
  error.value = ''
  try {
    await api.post('/accounting/accounts', {
      code: acc.code,
      name: acc.name,
      type: acc.type,
      subtype: acc.subtype || null,
    })
    toast.push(t('accounting.accountCreated'), 'ok')
    acc.code = ''; acc.name = ''; acc.subtype = ''
    await load()
  } catch (e: any) {
    error.value = e.message || t('common.error')
  } finally {
    pending.value = false
  }
}
async function createJournal() {
  pending.value = true
  error.value = ''
  try {
    await api.post('/accounting/journals', {
      entry_date: je.entry_date,
      memo: je.memo || null,
      post: je.post,
      lines: je.lines.map((l) => ({
        account_id: l.account_id,
        debit_amount: l.debit_amount || 0,
        credit_amount: l.credit_amount || 0,
      })),
    })
    toast.push(t('accounting.journalSaved'), 'ok')
    await load()
  } catch (e: any) {
    error.value = e.message || t('common.error')
  } finally {
    pending.value = false
  }
}
async function postJournal(id: string) {
  pending.value = true
  try {
    await api.post(`/accounting/journals/${id}/post`)
    toast.push(t('accounting.posted'), 'ok')
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
.tabs { display: flex; gap: .5rem; margin-bottom: 1rem; }
.tabs button.active { font-weight: 700; }
.form-col { display: flex; flex-direction: column; gap: .75rem; max-width: 40rem; }
</style>
