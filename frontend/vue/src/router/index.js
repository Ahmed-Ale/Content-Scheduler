import { createRouter, createWebHistory } from 'vue-router';
import Dashboard from '../pages/Dashboard.vue';
import Login from '../pages/Login.vue';
import Register from '../pages/Register.vue';
import Profile from '../pages/Profile.vue';
import Settings from '../pages/Settings.vue';
import PostCreator from '../pages/PostCreator.vue';
import PostEditor from '../pages/PostEditor.vue';
import Analytics from '../pages/Analytics.vue';
import { useAuthStore } from '../stores/auth';

const routes = [
  { path: '/', redirect: '/dashboard' },
  { path: '/dashboard', name: 'Dashboard', component: Dashboard },
  { path: '/login', name: 'Login', component: Login },
  { path: '/register', name: 'Register', component: Register },
  { path: '/profile', name: 'Profile', component: Profile },
  { path: '/settings', name: 'Settings', component: Settings },
  { path: '/create', name: 'CreatePost', component: PostCreator },
  { path: '/edit/:id', name: 'EditPost', component: PostEditor },
  { path: '/analytics', name: 'Analytics', component: Analytics },
];

const router = createRouter({
  history: createWebHistory(),
  routes,
});

router.beforeEach((to, from, next) => {
  const authStore = useAuthStore();
  const isAuthenticated = authStore.isAuthenticated();
  const isAuthRoute = ['/login', '/register'].includes(to.path);

  if (!isAuthenticated && !isAuthRoute) {
    next('/login');
  } else if (isAuthenticated && isAuthRoute) {
    next('/dashboard');
  } else {
    next();
  }
});

export default router;