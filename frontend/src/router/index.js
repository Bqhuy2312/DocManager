import { createRouter, createWebHistory } from "vue-router";
import Login from "../pages/Login.vue";
import Dashboard from "../pages/Dashboard.vue";
import AllDocuments from "../pages/AllDocuments.vue";
import Favorites from "../pages/Favorites.vue";
import Categories from "../pages/Categories.vue";
import Folders from "../pages/Folders.vue";
import Upload from "../pages/Upload.vue";
import Members from "../pages/Members.vue";
import MemberDetail from "../pages/MemberDetail.vue";
import Departments from "../pages/Departments.vue";
import Backups from "../pages/Backups.vue";
import Settings from "../pages/Settings.vue";
import DocumentDetail from "../pages/DocumentDetail.vue";
import Approvals from "../pages/Approvals.vue";

const routes = [
  {
    path: '/login',
    name: 'Login',
    component: Login
  },
  {
    path: '/',
    redirect: '/dashboard'
  },
  {
    path: '/dashboard',
    name: 'Dashboard',
    component: Dashboard
  },
  {
    path: '/documents',
    name: 'AllDocuments',
    component: AllDocuments
  },
  {
    path: '/documents/:id',
    name: 'DocumentDetail',
    component: DocumentDetail
  },
  {
    path: '/favorites',
    name: 'Favorites',
    component: Favorites
  },
  {
    path: '/categories',
    name: 'Categories',
    component: Categories
  },
  {
    path: '/folders',
    name: 'Folders',
    component: Folders,
    meta: { roles: ['admin', 'editor'] }
  },
  {
    path: '/approvals',
    name: 'Approvals',
    component: Approvals,
    meta: { requiresAdmin: true }
  },
  {
    path: '/upload',
    name: 'Upload',
    component: Upload,
    meta: { roles: ['admin', 'editor'] }
  },
  {
    path: '/members',
    name: 'Members',
    component: Members,
    meta: { requiresAdmin: true }
  },
  {
    path: '/members/:id',
    name: 'MemberDetail',
    component: MemberDetail,
    meta: { requiresAdmin: true }
  },
  {
    path: '/departments',
    name: 'Departments',
    component: Departments,
    meta: { requiresAdmin: true }
  },
  {
    path: '/backups',
    name: 'Backups',
    component: Backups,
    meta: { requiresAdmin: true }
  },
  {
    path: '/settings',
    name: 'Settings',
    component: Settings
  }
];

const router = createRouter({
  history: createWebHistory(),
  routes,
});

// Route guard
router.beforeEach((to, from, next) => {
  const token = localStorage.getItem('token')
  const storedUser = localStorage.getItem('user')
  let user = null

  try {
    user = JSON.parse(storedUser)
  } catch {
    user = null
  }

  if (!token && to.path !== '/login') {
    next('/login')
  } else if (token && to.path === '/login') {
    next('/dashboard')
  } else if (to.meta.requiresAdmin && user?.role !== 'admin') {
    next('/dashboard')
  } else if (to.meta.roles && !to.meta.roles.includes(user?.role)) {
    next('/dashboard')
  } else {
    next()
  }
})

export default router
