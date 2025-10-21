import { defineStore } from 'pinia';
import http from '@/lib/http';

export const useProcessStore = defineStore('processes', {
  state: () => ({
    processes: [],
    pagination: {},
    currentProcess: null,
    currentVersion: null,
    autosaveStatus: 'idle',
    lastSavedAt: null,
  }),
  actions: {
    async fetchProcesses(params = {}) {
      const { data } = await http.get('/api/processes', { params });
      this.processes = data.data;
      this.pagination = data.meta;
    },
    async fetchProcess(id) {
      const { data } = await http.get(`/api/processes/${id}`);
      this.currentProcess = data.data;
      const versions = data.data?.versions ?? [];
      this.currentVersion = versions.find((v) => !v.is_published) ?? (versions.length ? versions[versions.length - 1] : null);
    },
    async saveDraft(id, payload) {
      this.autosaveStatus = 'saving';
      try {
        await http.patch(`/api/process-versions/${id}`, payload);
        this.lastSavedAt = new Date().toISOString();
        this.autosaveStatus = 'idle';
      } catch (error) {
        this.autosaveStatus = 'error';
        throw error;
      }
    },
  },
});
