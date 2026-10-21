export default defineNuxtRouteMiddleware((to) => {
  const session = useSessionStore()
  if (!session.isAuthenticated && to.path !== '/login' && to.path !== '/register') {
    return navigateTo('/login')
  }
})
