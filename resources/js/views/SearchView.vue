<template>
  <section class="search">
    <h2>Prozessbuch</h2>
    <div class="search__filters">
      <input v-model="query" placeholder="Stichwort" />
      <select v-model="domain">
        <option value="">Domäne</option>
        <option value="Sales">Sales</option>
      </select>
      <select v-model="level">
        <option value="">Level</option>
        <option value="L1">L1</option>
        <option value="L2">L2</option>
        <option value="L3">L3</option>
        <option value="L4">L4</option>
      </select>
      <button @click="search">Suchen</button>
    </div>
    <div class="search__results">
      <article v-for="result in results" :key="result.id" class="result">
        <header>
          <h3>{{ result.title }}</h3>
          <span class="badge">{{ result.level }}</span>
        </header>
        <p>{{ result.summary }}</p>
        <footer>
          <button @click="exportProcess(result.id, 'pdf')">PDF exportieren</button>
        </footer>
      </article>
    </div>
  </section>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import http from '@/lib/http';

const query = ref('');
const domain = ref('');
const level = ref('');
const results = ref([]);

onMounted(() => {
  search();
});

async function search() {
  const { data } = await http.get('/api/search', {
    params: {
      query: query.value,
      domain: domain.value,
      level: level.value,
    },
  });
  results.value = data.data ?? data;
}

async function exportProcess(id, format) {
  await http.get(`/api/processes/${id}/versions/1/export`, {
    params: { fmt: format },
  });
  alert('Export erstellt.');
}
</script>

<style scoped>
.search {
  display: flex;
  flex-direction: column;
  gap: 2rem;
}
.search__filters {
  display: flex;
  flex-wrap: wrap;
  gap: 1rem;
}
.search__filters input,
.search__filters select {
  padding: 0.75rem 1rem;
  border-radius: 0.75rem;
  border: 1px solid #d1d5db;
}
.search__results {
  display: grid;
  gap: 1.5rem;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
}
.result {
  background: white;
  padding: 1.5rem;
  border-radius: 1rem;
  box-shadow: 0 4px 15px rgba(15, 23, 42, 0.08);
  display: flex;
  flex-direction: column;
  gap: 1rem;
}
.result header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}
.badge {
  background: #0ea5e9;
  color: white;
  padding: 0.25rem 0.75rem;
  border-radius: 9999px;
}
</style>
