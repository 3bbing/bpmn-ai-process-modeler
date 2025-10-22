import { createRouter, createWebHistory } from 'vue-router';
import CaptureView from '@/views/CaptureView.vue';
import ProcessListView from '@/views/ProcessListView.vue';
import ReviewQueueView from '@/views/ReviewQueueView.vue';
import SearchView from '@/views/SearchView.vue';
import ProcessEditorView from '@/views/ProcessEditorView.vue';
import ProfileView from '@/views/ProfileView.vue';
import UsersView from '@/views/UsersView.vue';
import { useAuthStore } from '@/stores/useAuthStore';

const routes = [
  {
    path: '/',
    redirect: '/capture',
  },
  {
    path: '/capture',
    component: CaptureView,
    meta: { title: 'Aufnahme' },
  },
  {
    path: '/profile',
    component: ProfileView,
    meta: { title: 'Profil' },
  },
  {
    path: '/processes',
    component: ProcessListView,
    meta: { title: 'Prozesse' },
  },
  {
    path: '/processes/:id/edit',
    component: ProcessEditorView,
    meta: { title: 'BPMN-Editor' },
  },
  {
    path: '/review',
    component: ReviewQueueView,
    meta: { title: 'Review' },
  },
  {
    path: '/search',
    component: SearchView,
    meta: { title: 'Suche' },
  },
  {
    path: '/admin/users',
    component: UsersView,
    meta: { title: 'Benutzer', requiresAdmin: true },
  },
];

const router = createRouter({
  history: createWebHistory(),
  routes,
});

router.beforeEach(async (to, from, next) => {
  const auth = useAuthStore();

  await auth.fetchCurrentUser();

  if (to.meta?.requiresAdmin && !auth.isAdmin) {
    return next('/capture');
  }

  return next();
});

export default router;
