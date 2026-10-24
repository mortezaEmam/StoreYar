<template>
  <div class="auth-wrap">
    <div class="auth-card">
      <div class="brand">{{ t('app.name') }}</div>
      <h1>{{ t('auth.reset.title') }}</h1>
      <form @submit.prevent="onSubmit">
        <label>{{ t('auth.email') }}
          <input v-model="email" type="email" required />
        </label>
        <label>{{ t('auth.token') }}
          <input v-model="token" required />
        </label>
        <label>{{ t('auth.password') }}
          <input v-model="password" type="password" required minlength="8" />
        </label>
        <label>{{ t('auth.passwordConfirm') }}
          <input v-model="passwordConfirmation" type="password" required />
        </label>
        <button type="submit" :disabled="pending" style="width:100%">
          {{ pending ? t('common.loading') : t('auth.reset.submit') }}
        </button>
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
const route = useRoute()
const email = ref(String(route.query.email || ''))
const token = ref(String(route.query.token || ''))
const password = ref('')
const passwordConfirmation = ref('')
const pending = ref(false)
const error = ref('')
async function onSubmit() {
  pending.value = true
  error.value = ''
  try {
    await api.post('/auth/reset-password', {
      email: email.value,
      token: token.value,
      password: password.value,
      password_confirmation: passwordConfirmation.value,
    })
    await navigateTo('/login')
  } catch (e: unknown) {
    error.value = e instanceof Error ? e.message : t('common.error')
  } finally {
    pending.value = false
  }
}
</script>
