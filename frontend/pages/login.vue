<template>
  <div class="auth-wrap">
    <div class="auth-card">
      <div class="lang-bar">
        <button type="button" class="secondary" @click="setLocale(locale === 'en' ? 'fa' : 'en')">
          {{ locale === 'en' ? t('lang.fa') : t('lang.en') }}
        </button>
      </div>
      <div class="brand">{{ t('app.name') }}</div>
      <h1>{{ t('auth.login.title') }}</h1>
      <p class="sub">{{ t('auth.login.sub') }}</p>
      <form @submit.prevent="onSubmit">
        <label>{{ t('auth.email') }}
          <input v-model="email" type="email" required autocomplete="username" placeholder="you@example.com" />
        </label>
        <label>{{ t('auth.password') }}
          <input v-model="password" type="password" required autocomplete="current-password" placeholder="••••••••" />
        </label>
        <button type="submit" :disabled="pending">
          {{ pending ? t('auth.login.pending') : t('auth.login.submit') }}
        </button>
        <p v-if="error" class="err">{{ error }}</p>
      </form>
      <p class="footer">
        <NuxtLink to="/forgot-password">{{ t('auth.forgot.link') }}</NuxtLink>
        · {{ t('auth.noAccount') }}
        <NuxtLink to="/register">{{ t('auth.createOne') }}</NuxtLink>
      </p>
    </div>
  </div>
</template>
<script setup lang="ts">
import { useSessionStore } from '~/stores/session'
definePageMeta({ layout: false })
const { t, locale, setLocale } = useI18n()
const email = ref('')
const password = ref('')
const error = ref('')
const pending = ref(false)
const api = useApi()
const session = useSessionStore()
async function onSubmit() {
  pending.value = true
  error.value = ''
  try {
    const res = await api.post<any>('/auth/login', { email: email.value, password: password.value })
    session.setToken(res.data.token)
    session.setUser(res.data.user)
    await navigateTo('/business')
  } catch (e: unknown) {
    error.value = e instanceof Error ? e.message : t('auth.login.failed')
  } finally {
    pending.value = false
  }
}
</script>
<style scoped>
.lang-bar { display: flex; justify-content: flex-end; margin-bottom: 0.5rem; }
.lang-bar button { font-size: 0.8rem; padding: 0.35rem 0.65rem; }
</style>
