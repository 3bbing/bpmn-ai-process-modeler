<template>
  <div class="layout">
    <header class="layout__header">
      <h1>{{ title }}</h1>
      <nav>
        <RouterLink to="/capture">Aufnehmen</RouterLink>
        <RouterLink to="/processes">Prozesse</RouterLink>
        <RouterLink to="/review">Review</RouterLink>
        <RouterLink to="/search">Suche</RouterLink>
      </nav>
    </header>
    <main class="layout__main">
      <RouterView />
    </main>
  </div>
</template>

<script setup>
import { useTitle } from './composables/useTitle';

const { title } = useTitle('BPMN AI Process Modeler');
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
}
.layout__header nav {
  display: flex;
  gap: 1rem;
}
.layout__header a {
  color: white;
  font-weight: 600;
}
.layout__main {
  flex: 1;
  padding: 2rem;
  background: #f8fafc;
}
</style>
