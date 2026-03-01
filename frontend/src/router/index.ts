import { createRouter, createWebHistory } from 'vue-router'
import HomeView from '../views/HomeView.vue'
import { useAuthStore } from '../stores/authStore'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/',
      name: 'home',
      component: HomeView,
      meta: { requiresAuth: false }
    },
    {
      path: '/login',
      name: 'login',
      component: () => import('../views/LoginView.vue'),
      meta: { requiresAuth: false, hideNav: true }
    },
    {
      path: '/dashboard',
      name: 'dashboard',
      component: () => import('../views/DashboardView.vue'),
      meta: { requiresAuth: true }
    },
    {
      path: '/companies',
      name: 'companies',
      component: () => import('../views/CompanyListView.vue'),
      meta: { requiresAuth: true, requiresAdmin: true }
    },
    {
      path: '/companies/new',
      name: 'company-create',
      component: () => import('../views/CompanyFormView.vue'),
      meta: { requiresAuth: true, requiresAdmin: true }
    },
    {
      path: '/companies/:id/edit',
      name: 'company-edit',
      component: () => import('../views/CompanyFormView.vue'),
      meta: { requiresAuth: true, requiresAdmin: true }
    },
    {
      path: '/urgency-keywords',
      name: 'urgency-keywords',
      component: () => import('../views/UrgencyKeywordListView.vue'),
      meta: { requiresAuth: true, requiresAdmin: true }
    },
    {
      path: '/urgency-keywords/new',
      name: 'urgency-keyword-create',
      component: () => import('../views/UrgencyKeywordFormView.vue'),
      meta: { requiresAuth: true, requiresAdmin: true }
    },
    {
      path: '/urgency-keywords/:id/edit',
      name: 'urgency-keyword-edit',
      component: () => import('../views/UrgencyKeywordFormView.vue'),
      meta: { requiresAuth: true, requiresAdmin: true }
    },
    {
      path: '/urgency-keywords/tester',
      name: 'urgency-keyword-tester',
      component: () => import('../views/UrgencyKeywordTesterView.vue'),
      meta: { requiresAuth: true, requiresAdmin: true }
    },
    // ── Gestão de Usuários (admin only) ────────────────────────────────────────
    {
      path: '/users',
      name: 'users',
      component: () => import('../views/UserListView.vue'),
      meta: { requiresAuth: true, requiresAdmin: true }
    },
    {
      path: '/users/new',
      name: 'user-create',
      component: () => import('../views/UserFormView.vue'),
      meta: { requiresAuth: true, requiresAdmin: true }
    },
    {
      path: '/users/:id/edit',
      name: 'user-edit',
      component: () => import('../views/UserFormView.vue'),
      meta: { requiresAuth: true, requiresAdmin: true }
    },
    {
      path: '/:pathMatch(.*)*',
      name: 'not-found',
      component: () => import('../views/NotFoundView.vue'),
      meta: { hideNav: true }
    }
  ]
})

// Navigation guard para proteger rotas
router.beforeEach(async (to, from, next) => {
  const authStore = useAuthStore()

  // Se a rota requer autenticação e usuário não está autenticado
  if (to.meta.requiresAuth && !authStore.isAuthenticated) {
    next({ name: 'login', query: { redirect: to.fullPath } })
    return
  }

  // Se está tentando acessar login e já está autenticado
  if (to.name === 'login' && authStore.isAuthenticated) {
    next({ name: 'dashboard' })
    return
  }

  // Se a rota requer admin e usuário não é admin
  if (to.meta.requiresAdmin && !authStore.isAdmin) {
    next({ name: 'dashboard' })
    return
  }

  next()
})

export default router

