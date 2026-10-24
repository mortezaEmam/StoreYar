<template>
  <div class="auth-wrap">
    <div class="auth-card">
      <div class="brand">{{ t('app.name') }}</div>
      <h1>{{ t('auth.forgot.title') }}</h1>
      <form @submit.prevent="onSubmit">
        <label>{{ t('auth.email') }}
          <input v-model="email" type="email" required />
        </label>
        <button type="submit" :disabled="pending" style="width:100%">
          {{ pending ? t('common.loading') : t('auth.forgot.submit') }}
        </button>
        <p v-if="message" class="ok">{{ message }}</p>
        <p v-if="error" class="err">{{ error }}</p>
      </form>
      <p class="footer"><NuxtLink to="/login">{{ t('nav.login') }}</NuxtLink></p>
    </div>
  </div>
</template>
<script setup lang="ts">
definePageMeta({ layout: false })
const { t } = useI18n()
const api = useApi()
const email = ref('')
const pending = ref(false)
const error = ref('')
const message = ref('')
async function onSubmit() {
  pending.value = true
  error.value = ''
  message.value = ''
  try {
    await api.post('/auth/forgot-password', { email: email.value })
    message.value = t('auth.forgot.sent')
  } catch (e: unknown) {
    error.value = e instanceof Error ? e.message : t('common.error')
  } finally {
    pending.value = false
  }
}
</script>
