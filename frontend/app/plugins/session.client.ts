export default defineNuxtPlugin(() => {
  useSessionStore().hydrate()
})
