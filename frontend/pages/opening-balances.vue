<template>
  <div>
    <div class="page-head"><h1>{{ t('openingBal.title') }}</h1></div>
    <p v-if="!session.businessId" class="warn">
      {{ t('common.selectBusiness') }}
      <NuxtLink to="/business">{{ t('common.chooseBusiness') }}</NuxtLink>
    </p>
    <template v-else>
      <p class="hint">{{ t('openingBal.hint') }}</p>
      <div class="card">
        <form class="form-col" @submit.prevent="submit">
          <label>{{ t('openingBal.date') }}
            <input v-model="form.as_of_date" type="date" required />
          </label>
          <label>{{ t('openingBal.notes') }}
            <input v-model="form.notes" />
          </label>

          <div v-for="(line, i) in form.lines" :key="i" class="form-row line">
            <label style="margin:0;flex:2">{{ t('accounting.account') }}
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
            <button type="button" class="secondary" @click="form.lines.splice(i, 1)" :disabled="form.lines.length <= 2">×</button>
          </div>

          <p :class="balanced ? 'ok' : 'err'">
            Debit {{ totalDebit }} / Credit {{ totalCredit }}
            {{ balanced ? t('openingBal.balanced') : t('openingBal.unbalanced') }}
          </p>

          <button type="button" class="secondary" @click="form.lines.push({ account_id: '', debit_amount: 0, credit_amount: 0 })">
            + {{ t('openingBal.addLine') }}
          </button>
          <button type="submit" :disabled="pending || !balanced">{{ t('openingBal.submit') }}</button>
        </form>
      </div>
      <p v-if="error" class="err">{{ error }}</p>
      <p v-if="lastDoc" class="ok">{{ t('openingBal.ok') }}: {{ lastDoc }}</p>
      <p class="hint">
        <NuxtLink to="/accounting">{{ t('nav.accounting') }}</NuxtLink>
      </p>
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
const accounts = ref<any[]>([])
const pending = ref(false)
const error = ref('')
const lastDoc = ref('')
const form = reactive({
  as_of_date: new Date().toISOString().slice(0, 10),
  notes: '',
  lines: [
    { account_id: '', debit_amount: 0, credit_amount: 0 },
    { account_id: '', debit_amount: 0, credit_amount: 0 },
  ],
})

const totalDebit = computed(() => form.lines.reduce((s, l) => s + (Number(l.debit_amount) || 0), 0))
const totalCredit = computed(() => form.lines.reduce((s, l) => s + (Number(l.credit_amount) || 0), 0))
const balanced = computed(() => totalDebit.value > 0 && totalDebit.value === totalCredit.value)

async function loadAccounts() {
  if (!session.businessId) return
  try {
    accounts.value = api.unwrap(await api.get('/accounting/accounts')) || []
  } catch (e: any) {
    error.value = e.message || t('common.error')
  }
}

async function submit() {
  if (!balanced.value) return
  pending.value = true
  error.value = ''
  try {
    const res = await api.post('/opening-balances', {
      as_of_date: form.as_of_date,
      notes: form.notes || null,
      lines: form.lines.map((l) => ({
        account_id: l.account_id,
        debit_amount: Number(l.debit_amount) || 0,
        credit_amount: Number(l.credit_amount) || 0,
      })),
    })
    const data = api.unwrap(res)
    lastDoc.value = data?.document_number || data?.journal_entry_id || data?.id || 'OK'
    toast.ok(t('openingBal.ok'))
  } catch (e: any) {
    error.value = e.message || t('common.error')
    toast.err(error.value)
  } finally {
    pending.value = false
  }
}

onMounted(loadAccounts)
watch(() => session.businessId, loadAccounts)
</script>
<style scoped>
.form-col { display: flex; flex-direction: column; gap: .75rem; max-width: 48rem; }
.line { align-items: flex-end; }
</style>
