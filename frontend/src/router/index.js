import { createRouter, createWebHistory } from 'vue-router'
import { onUnauthorized } from '../lib/api'
import { auth, isLoggedIn } from '../lib/auth'
import ItemsView from '../views/ItemsView.vue'
import LoginView from '../views/LoginView.vue'

const router = createRouter({
  history: createWebHistory(),
  routes: [
    { path: '/', name: 'items', component: ItemsView, meta: { requiresAuth: true } },
    { path: '/login', name: 'login', component: LoginView, meta: { guestOnly: true } },
    { path: '/:pathMatch(.*)*', redirect: '/' },
  ],
})

router.beforeEach((to) => {
  if (to.meta.requiresAuth && !isLoggedIn()) {
    return { name: 'login' }
  }

  if (to.meta.guestOnly && isLoggedIn()) {
    return { name: 'items' }
  }
})

onUnauthorized(() => {
  auth.user = null
  router.push({ name: 'login', query: { expired: '1' } })
})

export default router
