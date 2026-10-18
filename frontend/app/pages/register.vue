<template>
  <div class="form">
    <h1>Register</h1>
    <form @submit.prevent="onSubmit">
      <label>Name <input v-model="name" required /></label>
      <label>Email <input v-model="email" type="email" required /></label>
      <label>Password <input v-model="password" type="password" required minlength="8" /></label>
      <label>Confirm <input v-model="passwordConfirmation" type="password" required /></label>
      <button type="submit" :disabled="pending">Create account</button>
      <p v-if="error" class="err">{{ error }}</p>
    </form>
  </div>
</template>
<script setup lang="ts">
import type { ApiData, TokenData } from '~/types/api'
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
    const res = await api.post<ApiData<TokenData>>('/auth/register', {
      name: name.value, email: email.value, password: password.value,
      password_confirmation: passwordConfirmation.value,
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
<style scoped>
.form { max-width: 360px; margin: 3rem auto; font-family: system-ui, sans-serif; }
label { display: block; margin-bottom: 0.75rem; }
input { width: 100%; padding: 0.4rem; }
.err { color: #b00; }
</style>
