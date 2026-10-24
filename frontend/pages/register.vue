<template>
  <div class="auth-wrap">
    <div class="auth-card">
      <div class="lang-bar">
        <button type="button" class="secondary" @click="setLocale(locale === 'en' ? 'fa' : 'en')">
          {{ locale === 'en' ? t('lang.fa') : t('lang.en') }}
        </button>
      </div>
      <div class="brand">{{ t('app.name') }}</div>
      <h1>{{ t('auth.register.title') }}</h1>
      <p class="sub">{{ t('auth.register.sub') }}</p>
      <p class="hint" style="font-size:0.75rem;opacity:.7">API: {{ apiBase }}</p>
      <form @submit.prevent="onSubmit">
        <label>{{ t('auth.name') }}
          <input v-model="name" required autocomplete="name" />
        </label>
        <label>{{ t('auth.email') }}
          <input v-model="email" type="email" required autocomplete="username" />
        </label>
        <label>{{ t('auth.password') }}
          <input v-model="password" type="password" required minlength="8" autocomplete="new-password" />
        </label>
        <button type="submit" :disabled="pending">
          {{ pending ? t('auth.register.pending') : t('auth.register.submit') }}
        </button>
        <p v-if="error" class="err">{{ error }}</p>
      </form>
      <p class="footer">
        {{ t('auth.register.hasAccount') }}
        <NuxtLink to="/login">{{ t('nav.login') }}</NuxtLink>
      </p>
    </div>
  </div>
</template>
<script setup lang="ts">
import { useSessionStore } from '~/stores/session'
definePageMeta({ layout: false })
const { t, locale, setLocale } = useI18n()
const name = ref('')
const email = ref('')
const password = ref('')
const error = ref('')
const pending = ref(false)
const api = useApi()
const session = useSessionStore()
const apiBase = api.base

async function onSubmit() {
  pending.value = true
  error.value = ''
  try {
    const res = await api.post<any>('/auth/register', {
      name: name.value.trim(),
      email: email.value.trim(),
      password: password.value,
    })
    const body = api.unwrap<{ token?: string; user?: any }>(res)
    const token = body?.token
    if (!token || typeof token !== 'string') {
      throw new Error('Register response had no token. Check API response in Network tab.')
    }
    session.setToken(token)
    if (body.user) session.setUser(body.user)
    await navigateTo('/business')
  } catch (e: unknown) {
    error.value = e instanceof Error ? e.message : t('auth.register.failed')
  } finally {
    pending.value = false
  }
}
</script>
<style scoped>
.lang-bar { display: flex; justify-content: flex-end; margin-bottom: 0.5rem; }
.lang-bar button { font-size: 0.8rem; padding: 0.35rem 0.65rem; }
</style>
