import { route } from 'quasar/wrappers'
import { createRouter, createWebHistory } from 'vue-router'
import routes from './routes'
import { authService } from '../services/authService'

export default route(function (/* { store, ssrContext } */) {
  const Router = createRouter({
    scrollBehavior: () => ({ left: 0, top: 0 }),
    routes,
    history: createWebHistory(process.env.VUE_ROUTER_BASE)
  })

  Router.beforeEach(async (to, from, next) => {
    const isAuthenticated = authService.isAuthenticated()
    const isLoginPage = to.path === '/login'
    if (!isAuthenticated && !isLoginPage) {
      next({ name: 'login' })
    } else if (isAuthenticated && isLoginPage) {
      next({ path: '/' })
    } else {
      next()
    }
  })

  return Router
})