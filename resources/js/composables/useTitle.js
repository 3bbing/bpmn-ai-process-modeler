import { ref, watch } from 'vue';
import { useRoute } from 'vue-router';

export function useTitle(baseTitle) {
  const route = useRoute();
  const title = ref(baseTitle);

  watch(
    () => route.meta?.title,
    (metaTitle) => {
      title.value = metaTitle ? `${metaTitle} – ${baseTitle}` : baseTitle;
      document.title = title.value;
    },
    { immediate: true }
  );

  return { title };
}
