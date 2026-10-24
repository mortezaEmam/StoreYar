<template>
  <div>
    <div class="page-head"><h1>{{ t('products.title') }}</h1></div>
    <p v-if="!session.businessId" class="warn">
      {{ t('common.selectBusiness') }}
      <NuxtLink to="/business">{{ t('common.chooseBusiness') }}</NuxtLink>
    </p>
    <template v-else>
      <div class="card">
        <div class="card-title">{{ t('products.add') }}</div>
        <form class="form-row" @submit.prevent="create">
          <label style="margin:0">{{ t('products.name') }}
            <input v-model="form.name" required />
          </label>
          <label style="margin:0">{{ t('products.sku') }}
            <input v-model="form.sku" required />
          </label>
          <label style="margin:0">{{ t('products.price') }}
            <input v-model.number="form.unit_price_amount" type="number" min="0" required />
          </label>
          <button type="submit" :disabled="pending">{{ pending ? t('common.loading') : t('common.add') }}</button>
        </form>
        <p class="hint">
          {{ t('products.priceHint') }}
          · <NuxtLink to="/categories">{{ t('nav.categories') }}</NuxtLink>
        </p>
      </div>

      <p v-if="error" class="err">{{ error }}</p>

      <div v-if="items.length" class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>{{ t('products.name') }}</th>
              <th>{{ t('products.sku') }}</th>
              <th>{{ t('products.price') }}</th>
              <th>{{ t('categories.assign') }}</th>
              <th>{{ t('products.status') }}</th>
              <th>{{ t('products.actions') }}</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="p in items" :key="p.id">
              <td>{{ p.name }}</td>
              <td><code style="font-size:.8rem">{{ p.sku }}</code></td>
              <td>{{ p.unit_price_amount }} {{ p.currency || 'IRR' }}</td>
              <td>
                <select
                  :value="p.category_id || ''"
                  :disabled="pending"
                  @change="onCategoryChange(p, ($event.target as HTMLSelectElement).value)"
                >
                  <option value="">{{ t('categories.none') }}</option>
                  <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
                </select>
              </td>
              <td><span class="badge" :class="String(p.status || '').toLowerCase()">{{ p.status || '—' }}</span></td>
              <td>
                <div class="row-actions">
                  <button type="button" class="secondary" :disabled="pending" @click="openRename(p)">{{ t('products.rename') }}</button>
                  <button type="button" class="secondary" :disabled="pending" @click="openPrice(p)">{{ t('products.changePrice') }}</button>
                  <button v-if="!isActive(p)" type="button" class="secondary" :disabled="pending" @click="activate(p)">{{ t('products.activate') }}</button>
                  <button v-if="!isArchived(p)" type="button" class="danger" :disabled="pending" @click="archive(p)">{{ t('products.archive') }}</button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <div v-else-if="!loading" class="empty">{{ t('products.empty') }}</div>
      <p v-if="loading" class="hint">{{ t('common.loading') }}</p>

      <div v-if="dialog === 'rename'" class="modal-backdrop" @click.self="dialog = null">
        <div class="modal card">
          <div class="card-title">{{ t('products.rename') }}</div>
          <label>{{ t('products.newName') }}
            <input v-model="editName" required />
          </label>
          <div class="row-actions">
            <button type="button" :disabled="pending" @click="submitRename">{{ t('common.save') }}</button>
            <button type="button" class="secondary" @click="dialog = null">{{ t('common.cancel') }}</button>
          </div>
        </div>
      </div>

      <div v-if="dialog === 'price'" class="modal-backdrop" @click.self="dialog = null">
        <div class="modal card">
          <div class="card-title">{{ t('products.changePrice') }}</div>
          <label>{{ t('products.newPrice') }}
            <input v-model.number="editPrice" type="number" min="0" required />
          </label>
          <div class="row-actions">
            <button type="button" :disabled="pending" @click="submitPrice">{{ t('common.save') }}</button>
            <button type="button" class="secondary" @click="dialog = null">{{ t('common.cancel') }}</button>
          </div>
        </div>
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

const items = ref<any[]>([])
const categories = ref<any[]>([])
const loading = ref(false)
const pending = ref(false)
const error = ref('')
const form = reactive({ name: '', sku: '', unit_price_amount: 1000, currency: 'IRR' })
const dialog = ref<'rename' | 'price' | null>(null)
const active = ref<any>(null)
const editName = ref('')
const editPrice = ref(0)

function isActive(p: any) {
  return String(p.status || '').toLowerCase() === 'active'
}
function isArchived(p: any) {
  return String(p.status || '').toLowerCase() === 'archived'
}

async function loadCategories() {
  try {
    const res = await api.get<any>('/categories')
    categories.value = Array.isArray(res.data) ? res.data : []
  } catch {
    categories.value = []
  }
}

async function load() {
  if (!session.businessId) return
  loading.value = true
  error.value = ''
  try {
    const res = await api.get<any>('/products')
    items.value = Array.isArray(res.data) ? res.data : []
  } catch (e: unknown) {
    error.value = e instanceof Error ? e.message : t('common.error')
    toast.err(error.value)
  } finally {
    loading.value = false
  }
}

async function refresh() {
  await Promise.all([loadCategories(), load()])
}

async function create() {
  pending.value = true
  error.value = ''
  try {
    await api.post('/products', { ...form }, crypto.randomUUID())
    toast.ok(t('products.created'))
    form.name = ''
    form.sku = ''
    await load()
  } catch (e: unknown) {
    error.value = e instanceof Error ? e.message : t('common.error')
    toast.err(error.value)
  } finally {
    pending.value = false
  }
}

async function onCategoryChange(p: any, categoryId: string) {
  pending.value = true
  error.value = ''
  try {
    await api.patch(
      `/products/${p.id}/category`,
      { category_id: categoryId || null },
      crypto.randomUUID(),
    )
    toast.ok(t('categories.assigned'))
    await load()
  } catch (e: unknown) {
    error.value = e instanceof Error ? e.message : t('common.error')
    toast.err(error.value)
    await load()
  } finally {
    pending.value = false
  }
}

function openRename(p: any) {
  active.value = p
  editName.value = p.name || ''
  dialog.value = 'rename'
}
function openPrice(p: any) {
  active.value = p
  editPrice.value = Number(p.unit_price_amount || 0)
  dialog.value = 'price'
}

async function submitRename() {
  if (!active.value?.id) return
  pending.value = true
  error.value = ''
  try {
    await api.patch(`/products/${active.value.id}`, { name: editName.value }, crypto.randomUUID())
    toast.ok(t('products.renamed'))
    dialog.value = null
    await load()
  } catch (e: unknown) {
    error.value = e instanceof Error ? e.message : t('common.error')
    toast.err(error.value)
  } finally {
    pending.value = false
  }
}

async function submitPrice() {
  if (!active.value?.id) return
  pending.value = true
  error.value = ''
  try {
    await api.patch(
      `/products/${active.value.id}/price`,
      { unit_price_amount: editPrice.value, currency: active.value.currency || 'IRR' },
      crypto.randomUUID(),
    )
    toast.ok(t('products.priceChanged'))
    dialog.value = null
    await load()
  } catch (e: unknown) {
    error.value = e instanceof Error ? e.message : t('common.error')
    toast.err(error.value)
  } finally {
    pending.value = false
  }
}

async function activate(p: any) {
  pending.value = true
  error.value = ''
  try {
    await api.post(`/products/${p.id}/activate`, {}, crypto.randomUUID())
    toast.ok(t('products.activated'))
    await load()
  } catch (e: unknown) {
    error.value = e instanceof Error ? e.message : t('common.error')
    toast.err(error.value)
  } finally {
    pending.value = false
  }
}

async function archive(p: any) {
  if (!confirm(t('products.confirmArchive'))) return
  pending.value = true
  error.value = ''
  try {
    await api.post(`/products/${p.id}/archive`, {}, crypto.randomUUID())
    toast.ok(t('products.archived'))
    await load()
  } catch (e: unknown) {
    error.value = e instanceof Error ? e.message : t('common.error')
    toast.err(error.value)
  } finally {
    pending.value = false
  }
}

watch(() => session.businessId, () => { if (session.businessId) refresh() }, { immediate: true })
</script>

<style scoped>
.row-actions { display: flex; flex-wrap: wrap; gap: 0.35rem; }
.modal-backdrop {
  position: fixed; inset: 0; background: rgba(16, 24, 40, 0.45);
  display: flex; align-items: center; justify-content: center; z-index: 40; padding: 1rem;
}
.modal { width: min(24rem, 100%); margin: 0; }
</style>
