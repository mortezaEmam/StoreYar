<template>
  <div class="auth-wrap">
    <div class="auth-card">
      <div class="brand">StoreYar</div>
      <h1>Create account</h1>
      <p class="sub">Start selling in a few minutes.</p>
      <form @submit.prevent="onSubmit">
        <label>Name <input v-model="name" required placeholder="Your name" /></label>
        <label>Email <input v-model="email" type="email" required placeholder="you@example.com" /></label>
        <label>Password <input v-model="password" type="password" required minlength="8" placeholder="At least 8 characters" /></label>
        <label>Confirm password <input v-model="passwordConfirmation" type="password" required placeholder="Repeat password" /></label>
        <button type="submit" :disabled="pending">
          {{ pending ? 'Creating…' : 'Create account' }}
        </button>
        <p v-if="error" class="err">{{ error }}</p>
      </form>
      <p class="footer">Already registered? <NuxtLink to="/login">Sign in</NuxtLink></p>
    </div>
  </div>
</template>
<script setup lang="ts">
import { useSessionStore } from '~/stores/session'
definePageMeta({ layout: false })
const name = ref('')
const email = ref('')
const password = ref('')
const passwordConfirmation = ref('')
const error = ref('')
const pending = ref(false)
const api = useApi()
const session = useSessionStore()
async function onSubmit() {
  pending.value = true
  error.value = ''
  try {
    const res = await api.post<any>('/auth/register', {
      name: name.value, email: email.value,
      password: password.value, password_confirmation: passwordConfirmation.value,
    })
    session.setToken(res.data.token)
    session.setUser(res.data.user)
    await navigateTo('/business')
  } catch (e: unknown) {
    error.value = e instanceof Error ? e.message : 'Register failed'
  } finally {
    pending.value = false
  }
}
</script>
