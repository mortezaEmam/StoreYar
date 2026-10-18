<template>
  <div class="p-8 max-w-md">
    <h1>Login</h1>
    <form @submit.prevent="onSubmit">
      <label>
        Email
        <input v-model="email" type="email" required />
      </label>
      <label>
        Password
        <input v-model="password" type="password" required />
      </label>
      <button type="submit" :disabled="pending">Sign in</button>
      <p v-if="error" class="text-red-600">{{ error }}</p>
    </form>
  </div>
</template>

<script setup lang="ts">
const email = ref('')
const password = ref('')
const error = ref('')
const pending = ref(false)
const config = useRuntimeConfig()

async function onSubmit() {
  pending.value = true
  error.value = ''
  try {
    const res = await $fetch<{ data: { token: string } }>(`${config.public.apiBase}/auth/login`, {
      method: 'POST',
      body: { email: email.value, password: password.value },
    })
    const token = res?.data?.token
    if (token) {
      localStorage.setItem('storeyar_token', token)
      await navigateTo('/')
    }
  } catch (e: unknown) {
    error.value = 'Login failed'
  } finally {
    pending.value = false
  }
}
</script>
