import { createRouter, createWebHistory } from 'vue-router'
import HomeView from '../views/HomeView.vue'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/',
      name: 'home',
      component: HomeView
    },
    {
      path: '/login',
      name: 'login',
      component: () => import('../views/LoginView.vue')
    },
    {
      path: '/dashboard',
      name: 'dashboard',
      component: () => import('../views/DashboardView.vue')
    },
    {
      path: '/companies',
      name: 'companies',
      component: () => import('../views/CompanyListView.vue')
    },
    {
      path: '/companies/new',
      name: 'company-create',
      component: () => import('../views/CompanyFormView.vue')
    },
    {
      path: '/companies/:id/edit',
      name: 'company-edit',
      component: () => import('../views/CompanyFormView.vue')
    }
  ]
})

export default router
