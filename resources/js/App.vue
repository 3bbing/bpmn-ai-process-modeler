<template>
  <div class="layout">
    <header class="layout__header">
      <div class="layout__brand">
        <h1>{{ title }}</h1>
        <nav>
          <RouterLink to="/capture">Aufnehmen</RouterLink>
          <RouterLink to="/processes">Prozesse</RouterLink>
          <RouterLink to="/review">Review</RouterLink>
          <RouterLink to="/search">Suche</RouterLink>
          <RouterLink to="/profile">Profil</RouterLink>
          <RouterLink v-if="isAdmin" to="/admin/users">Benutzer</RouterLink>
        </nav>
      </div>
      <div class="layout__account" v-if="auth.user">
        <span class="layout__username">{{ auth.user.name }}</span>
        <button type="button" @click="logout">Abmelden</button>
      </div>
    </header>
    <main class="layout__main">
      <RouterView />
    </main>
  </div>
</template>

<script setup>
import { computed, onMounted } from 'vue';
import { useTitle } from './composables/useTitle';
import { useAuthStore } from '@/stores/useAuthStore';

const { title } = useTitle('BPMN AI Process Modeler');
const auth = useAuthStore();

const isAdmin = computed(() => auth.isAdmin);

onMounted(async () => {
  await auth.fetchCurrentUser();
});

async function logout() {
  await auth.logout();
}
</script>

<style scoped>
.layout {
  min-height: 100vh;
  display: flex;
  flex-direction: column;
}
.layout__header {
  background: #0f172a;
  color: white;
  padding: 1rem 2rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 1rem;
}
.layout__brand {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 1.5rem;
}
.layout__header nav {
  display: flex;
  gap: 1rem;
  flex-wrap: wrap;
}
.layout__header a {
  color: white;
  font-weight: 600;
}
.layout__account {
  display: flex;
  align-items: center;
  gap: 1rem;
}
.layout__account button {
  padding: 0.5rem 1rem;
  border-radius: 999px;
  border: 1px solid rgba(255, 255, 255, 0.4);
  background: transparent;
  color: white;
  cursor: pointer;
  font-weight: 600;
}
.layout__account button:hover {
  background: rgba(255, 255, 255, 0.1);
}
.layout__username {
  font-weight: 600;
}
.layout__main {
  flex: 1;
  padding: 2rem;
  background: #f8fafc;
}
</style>
