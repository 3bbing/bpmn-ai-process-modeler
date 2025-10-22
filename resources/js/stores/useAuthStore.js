import { defineStore } from 'pinia';
import http from '@/lib/http';

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: null,
    loading: false,
    initialized: false,
  }),
  getters: {
    isAdmin: (state) => state.user?.roles?.includes('admin') ?? false,
  },
  actions: {
    async fetchCurrentUser(force = false) {
      if (this.initialized && !force) {
        return this.user;
      }

      this.loading = true;

      try {
        const { data } = await http.get('/api/me');
        this.user = data.data ?? data;
      } catch (error) {
        this.user = null;
      } finally {
        this.loading = false;
        this.initialized = true;
      }

      return this.user;
    },
    async refresh() {
      this.initialized = false;
      return this.fetchCurrentUser(true);
    },
    async logout() {
      await http.post('/logout');
      this.user = null;
      window.location.href = '/login';
    },
  },
});
