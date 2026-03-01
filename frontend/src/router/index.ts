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
    },
    {
      path: '/urgency-keywords',
      name: 'urgency-keywords',
      component: () => import('../views/UrgencyKeywordListView.vue')
    },
    {
      path: '/urgency-keywords/new',
      name: 'urgency-keyword-create',
      component: () => import('../views/UrgencyKeywordFormView.vue')
    },
    {
      path: '/urgency-keywords/:id/edit',
      name: 'urgency-keyword-edit',
      component: () => import('../views/UrgencyKeywordFormView.vue')
    },
    {
      path: '/urgency-keywords/tester',
      name: 'urgency-keyword-tester',
      component: () => import('../views/UrgencyKeywordTesterView.vue')
    }
  ]
})

export default router
