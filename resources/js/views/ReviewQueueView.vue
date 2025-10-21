<template>
  <section>
    <h2>Review-Queue</h2>
    <p>Hier siehst du Prozesse, die dein Review benötigen.</p>
    <ul class="review-list">
      <li v-for="item in reviewItems" :key="item.id">
        <div>
          <h3>{{ item.process.title }} (v{{ item.version }})</h3>
          <p>Status: {{ item.status }}</p>
        </div>
        <div class="actions">
          <button @click="decide(item.id, 'approve')">Freigeben</button>
          <button class="secondary" @click="decide(item.id, 'request_changes')">Änderungen anfordern</button>
        </div>
      </li>
    </ul>
  </section>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import http from '@/lib/http';

const reviewItems = ref([]);

onMounted(async () => {
  const { data } = await http.get('/api/processes', {
    params: { status: 'in_review' },
  });
  reviewItems.value = (data.data ?? []).flatMap((process) =>
    (process.versions ?? [])
      .filter((version) => version.status === 'in_review')
      .map((version) => ({
        id: version.id,
        process,
        version: version.version,
        status: version.status,
      }))
  );
});

async function decide(versionId, decision) {
  await http.post('/api/reviews', { version_id: versionId, decision });
  reviewItems.value = reviewItems.value.filter((item) => item.id !== versionId);
}
</script>

<style scoped>
.review-list {
  display: grid;
  gap: 1.5rem;
  margin-top: 1.5rem;
}
.review-list li {
  background: white;
  padding: 1.5rem;
  border-radius: 1rem;
  display: flex;
  align-items: center;
  justify-content: space-between;
  box-shadow: 0 4px 25px rgba(15, 23, 42, 0.08);
}
.actions {
  display: flex;
  gap: 0.75rem;
}
button {
  padding: 0.75rem 1.25rem;
  border-radius: 9999px;
  border: none;
  background: #22c55e;
  color: white;
}
button.secondary {
  background: #f97316;
}
</style>
