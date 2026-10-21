export default defineNuxtPlugin(() => {
  const session = useSessionStore()
  session.hydrate()
})
