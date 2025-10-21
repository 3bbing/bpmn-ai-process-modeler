import { createRouter, createWebHistory } from 'vue-router';
import CaptureView from '@/views/CaptureView.vue';
import ProcessListView from '@/views/ProcessListView.vue';
import ReviewQueueView from '@/views/ReviewQueueView.vue';
import SearchView from '@/views/SearchView.vue';
import ProcessEditorView from '@/views/ProcessEditorView.vue';

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
];

const router = createRouter({
  history: createWebHistory(),
  routes,
});

export default router;
