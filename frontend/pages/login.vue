<template>
  <div class="auth-wrap">
    <div class="auth-card">
      <div class="brand">StoreYar</div>
      <h1>Welcome back</h1>
      <p class="sub">Sign in to manage your business.</p>
      <form @submit.prevent="onSubmit">
        <label>Email
          <input v-model="email" type="email" required autocomplete="username" placeholder="you@example.com" />
        </label>
        <label>Password
          <input v-model="password" type="password" required autocomplete="current-password" placeholder="••••••••" />
        </label>
        <button type="submit" :disabled="pending">
          {{ pending ? 'Signing in…' : 'Sign in' }}
        </button>
        <p v-if="error" class="err">{{ error }}</p>
      </form>
      <p class="footer">Forgot password? <NuxtLink to="/forgot-password">Reset</NuxtLink> · No account? <NuxtLink to="/register">Create one</NuxtLink></p>
    </div>
  </div>
</template>
<script setup lang="ts">
import { useSessionStore } from '~/stores/session'
definePageMeta({ layout: false })
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
    error.value = e instanceof Error ? e.message : 'Login failed'
  } finally {
    pending.value = false
  }
}
</script>
