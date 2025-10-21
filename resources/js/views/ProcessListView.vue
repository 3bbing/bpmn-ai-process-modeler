<template>
  <section>
    <header class="section-header">
      <h2>Deine Prozesse</h2>
      <button @click="createProcess">Neuer Prozess</button>
    </header>
    <table class="process-table">
      <thead>
        <tr>
          <th>Code</th>
          <th>Titel</th>
          <th>Level</th>
          <th>Status</th>
          <th>Owner</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="process in store.processes" :key="process.id">
          <td>{{ process.code }}</td>
          <td>{{ process.title }}</td>
          <td>{{ process.level }}</td>
          <td>{{ process.status }}</td>
          <td>{{ process.owner?.name }}</td>
          <td>
            <RouterLink :to="`/processes/${process.id}/edit`">Bearbeiten</RouterLink>
          </td>
        </tr>
      </tbody>
    </table>
  </section>
</template>

<script setup>
import { onMounted } from 'vue';
import http from '@/lib/http';
import { useProcessStore } from '@/stores/useProcessStore';

const store = useProcessStore();

onMounted(() => {
  store.fetchProcesses();
});

async function createProcess() {
  const { data } = await http.post('/api/processes', {
    domain_id: 1,
    code: `P-${Date.now()}`,
    title: 'Neuer Prozess',
    level: 'L3',
    owner_user_id: 1,
    status: 'draft',
  });
  store.processes.unshift(data.data);
}
</script>

<style scoped>
.section-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.5rem;
}
.process-table {
  width: 100%;
  border-collapse: collapse;
  background: white;
  border-radius: 1rem;
  overflow: hidden;
  box-shadow: 0 4px 20px rgba(15, 23, 42, 0.05);
}
.process-table th,
.process-table td {
  padding: 1rem;
  text-align: left;
  border-bottom: 1px solid #e2e8f0;
}
.process-table tbody tr:hover {
  background: #f8fafc;
}
button {
  padding: 0.6rem 1rem;
  border-radius: 9999px;
  border: none;
  background: #22c55e;
  color: white;
  cursor: pointer;
}
</style>
