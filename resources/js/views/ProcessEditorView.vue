<template>
  <section class="editor">
    <header class="editor__header">
      <div>
        <h2>{{ process?.title }}</h2>
        <p>Letzte Speicherung: {{ lastSavedLabel }}</p>
      </div>
      <AutosaveBadge :status="store.autosaveStatus">
        <span v-if="store.autosaveStatus === 'saving'">Speichere...</span>
        <span v-else>Alle Änderungen gespeichert</span>
      </AutosaveBadge>
    </header>
    <div class="editor__layout">
      <div class="editor__canvas" ref="canvasRef"></div>
      <aside class="editor__sidebar">
        <h3>SOP (L4)</h3>
        <div v-for="task in sopTasks" :key="task.id" class="sop-panel">
          <header>{{ task.label }}</header>
          <label>
            Ziel
            <input v-model="task.sop.goal" />
          </label>
          <label>
            Schritte
            <textarea v-model="task.sop.steps"></textarea>
          </label>
          <label>
            Ergebnis
            <input v-model="task.sop.outcome" />
          </label>
        </div>
      </aside>
    </div>
  </section>
</template>

<script setup>
import { computed, onMounted, onUnmounted, reactive, ref, watch } from 'vue';
import BpmnModeler from 'bpmn-js/lib/Modeler';
import { useRoute } from 'vue-router';
import { useProcessStore } from '@/stores/useProcessStore';
import AutosaveBadge from '@/components/AutosaveBadge.vue';
import 'bpmn-js/dist/assets/diagram-js.css';
import 'bpmn-js/dist/assets/bpmn-font/css/bpmn-embedded.css';

const route = useRoute();
const canvasRef = ref(null);
const modeler = ref(null);
const store = useProcessStore();
const sopTasks = reactive([]);
const autosaveTimer = ref(null);

const process = computed(() => store.currentProcess);
const lastSavedLabel = computed(() => store.lastSavedAt ? new Date(store.lastSavedAt).toLocaleTimeString() : '–');

onMounted(async () => {
  await store.fetchProcess(route.params.id);
  modeler.value = new BpmnModeler({ container: canvasRef.value });
  if (store.currentVersion?.bpmn_xml) {
    await modeler.value.importXML(store.currentVersion.bpmn_xml);
  }

  modeler.value.on('commandStack.changed', queueAutosave);

  const tasks = store.currentVersion?.meta?.tasks ?? [];
  sopTasks.splice(0, sopTasks.length);
  tasks.forEach((task) => {
    sopTasks.push({
      ...task,
      sop: {
        goal: task?.sop?.goal ?? '',
        steps: task?.sop?.steps ?? '',
        outcome: task?.sop?.outcome ?? '',
      },
    });
  });
});

watch(
  () => sopTasks.map((task) => ({ ...task, sop: { ...task.sop } })),
  () => queueAutosave(),
  { deep: true }
);

onUnmounted(() => {
  if (autosaveTimer.value) {
    clearTimeout(autosaveTimer.value);
  }
  modeler.value?.destroy();
});

function queueAutosave() {
  if (autosaveTimer.value) {
    clearTimeout(autosaveTimer.value);
  }
  autosaveTimer.value = setTimeout(handleAutosave, 5000);
}

async function handleAutosave() {
  if (!store.currentVersion) return;
  if (autosaveTimer.value) {
    clearTimeout(autosaveTimer.value);
    autosaveTimer.value = null;
  }
  try {
    const { xml } = await modeler.value.saveXML({ format: true });
    await store.saveDraft(store.currentVersion.id, {
      bpmn_xml: xml,
      meta: {
        tasks: sopTasks,
      },
    });
  } catch (error) {
    console.error('Autosave failed', error);
  }
}
</script>

<style scoped>
.editor {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}
.editor__header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
}
.editor__layout {
  display: grid;
  grid-template-columns: 2fr 1fr;
  gap: 1.5rem;
}
.editor__canvas {
  background: white;
  border-radius: 1rem;
  box-shadow: 0 4px 20px rgba(15, 23, 42, 0.1);
  min-height: 600px;
}
.editor__sidebar {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}
.sop-panel {
  background: white;
  border-radius: 1rem;
  padding: 1rem;
  box-shadow: 0 2px 12px rgba(15, 23, 42, 0.08);
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}
.status.saving {
  color: #f59e0b;
}
.status.idle {
  color: #22c55e;
}
</style>
