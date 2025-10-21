<template>
  <section class="capture">
    <div class="capture__intro">
      <h2>Prozessaufnahme</h2>
      <p>Starte eine Sprachaufnahme oder füge Text hinzu, um deinen Prozess zu beschreiben.</p>
    </div>
    <div class="capture__recorder">
      <div class="recorder-card">
        <h3>Audio Recorder</h3>
        <p v-if="error" class="error">{{ error }}</p>
        <div class="recorder-card__controls">
          <button @click="toggleRecording">{{ isRecording ? 'Stop' : 'Aufnahme starten' }}</button>
          <button :disabled="chunks.length === 0" @click="upload">Upload</button>
        </div>
        <p>Aufnahmedauer: {{ durationLabel }}</p>
        <p>Dateigröße: {{ sizeLabel }}</p>
        <ul>
          <li v-for="chunk in chunks" :key="chunk.id">Chunk {{ chunk.index + 1 }} – {{ chunk.sizeLabel }}</li>
        </ul>
      </div>
      <div class="recorder-card">
        <h3>Text-Notizen</h3>
        <textarea v-model="notes" rows="10" placeholder="Schreibe hier deine Schritte..." />
        <button @click="generateFromText" :disabled="!notes">BPMN-Entwurf erzeugen</button>
      </div>
    </div>
  </section>
</template>

<script setup>
import { computed, onBeforeUnmount, reactive, ref } from 'vue';
import http from '@/lib/http';

const mediaRecorder = ref(null);
const isRecording = ref(false);
const error = ref('');
const notes = ref('');
const chunks = reactive([]);
const duration = ref(0);
const timer = ref(null);

const durationLabel = computed(() => new Date(duration.value * 1000).toISOString().substring(14, 19));
const sizeLabel = computed(() => {
  const total = chunks.reduce((sum, chunk) => sum + chunk.size, 0);
  return `${(total / 1024 / 1024).toFixed(2)} MB`;
});

async function toggleRecording() {
  if (isRecording.value) {
    mediaRecorder.value.stop();
    isRecording.value = false;
    clearInterval(timer.value);
    return;
  }

  try {
    const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
    mediaRecorder.value = new MediaRecorder(stream, { mimeType: 'audio/ogg' });
    mediaRecorder.value.ondataavailable = (event) => {
      if (event.data.size > 0) {
        if (event.data.size > 15 * 1024 * 1024) {
          error.value = 'Chunk größer als 15 MB – Aufnahme gestoppt.';
          mediaRecorder.value.stop();
          return;
        }
        chunks.push({
          id: crypto.randomUUID(),
          index: chunks.length,
          blob: event.data,
          size: event.data.size,
          sizeLabel: `${(event.data.size / 1024 / 1024).toFixed(2)} MB`,
        });
      }
    };
    mediaRecorder.value.start(5000);
    isRecording.value = true;
    duration.value = 0;
    timer.value = setInterval(() => (duration.value += 1), 1000);
  } catch (err) {
    error.value = 'Recorder konnte nicht gestartet werden.';
  }
}

async function upload() {
  try {
    const { data } = await http.post('/api/uploads', {
      filename: 'capture.ogg',
      mime_type: 'audio/ogg',
      size: chunks[0].size,
    });

    for (const chunk of chunks) {
      const formData = new FormData();
      formData.append('chunk', chunk.blob, `chunk-${chunk.index}.ogg`);
      formData.append('idx', chunk.index);
      formData.append('checksum', await checksum(chunk.blob));
      await http.post(`/api/uploads/${data.upload_id}/chunks`, formData);
    }

    const finalize = await http.post(`/api/uploads/${data.upload_id}/finalize`, { concat: true });
    await http.post('/api/transcriptions', { file_refs: finalize.data.file_refs });
    chunks.splice(0, chunks.length);
    duration.value = 0;
    error.value = '';
  } catch (err) {
    error.value = 'Upload fehlgeschlagen.';
  }
}

async function checksum(blob) {
  const buffer = await blob.arrayBuffer();
  let crc = 0 ^ -1;
  const view = new Uint8Array(buffer);
  for (let i = 0; i < view.length; i += 1) {
    let byte = view[i];
    crc = (crc >>> 8) ^ crcTable[(crc ^ byte) & 0xff];
  }
  return ((crc ^ -1) >>> 0).toString(16).padStart(8, '0');
}

const crcTable = (() => {
  const table = new Uint32Array(256);
  for (let n = 0; n < 256; n += 1) {
    let c = n;
    for (let k = 0; k < 8; k += 1) {
      c = (c & 1) ? (0xedb88320 ^ (c >>> 1)) : (c >>> 1);
    }
    table[n] = c >>> 0;
  }
  return table;
})();

function generateFromText() {
  // placeholder - actual navigation happens in follow-up flows
  alert('Text wird für BPMN-Extraktion vorbereitet.');
}

onBeforeUnmount(() => {
  clearInterval(timer.value);
});
</script>

<style scoped>
.capture {
  display: flex;
  flex-direction: column;
  gap: 2rem;
}
.capture__recorder {
  display: grid;
  gap: 2rem;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
}
.recorder-card {
  background: white;
  padding: 1.5rem;
  border-radius: 1rem;
  box-shadow: 0 10px 30px rgba(15, 23, 42, 0.1);
  display: flex;
  flex-direction: column;
  gap: 1rem;
}
.error {
  color: #dc2626;
  font-weight: 600;
}
textarea {
  width: 100%;
  border-radius: 0.75rem;
  border: 1px solid #cbd5f5;
  padding: 0.75rem;
}
button {
  padding: 0.75rem 1.25rem;
  border-radius: 9999px;
  border: none;
  background: #2563eb;
  color: white;
  cursor: pointer;
}
button:disabled {
  background: #cbd5f5;
  cursor: not-allowed;
}
</style>
