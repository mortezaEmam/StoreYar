<template>
  <div>
    <div class="page-head"><h1>{{ t('home.title') }}</h1></div>
    <p class="hint">{{ t('home.welcome') }}</p>
    <div class="card" v-if="session.businessId">
      <p class="ok">{{ t('nav.business') }}: <code>{{ session.businessId }}</code></p>
      <p>
        <NuxtLink to="/products">{{ t('nav.products') }}</NuxtLink>
        ·
        <NuxtLink to="/orders">{{ t('nav.orders') }}</NuxtLink>
        ·
        <NuxtLink to="/stock">{{ t('nav.stock') }}</NuxtLink>
      </p>
    </div>
    <p v-else class="warn">
      {{ t('common.selectBusiness') }}
      <NuxtLink to="/business">{{ t('common.chooseBusiness') }}</NuxtLink>
    </p>
  </div>
</template>
<script setup lang="ts">
import { useSessionStore } from '~/stores/session'
definePageMeta({ middleware: 'auth' })
const { t } = useI18n()
const session = useSessionStore()
</script>
