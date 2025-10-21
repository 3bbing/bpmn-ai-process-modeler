<template>
  <span class="badge" :class="status">
    <slot />
  </span>
</template>

<script setup>
defineProps({
  status: {
    type: String,
    default: 'idle',
  },
});
</script>

<style scoped>
.badge {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.35rem 0.75rem;
  border-radius: 9999px;
  font-size: 0.85rem;
}
.badge.idle {
  background: rgba(34, 197, 94, 0.1);
  color: #166534;
}
.badge.saving {
  background: rgba(245, 158, 11, 0.15);
  color: #b45309;
}
.badge.error {
  background: rgba(248, 113, 113, 0.15);
  color: #b91c1c;
}
</style>
