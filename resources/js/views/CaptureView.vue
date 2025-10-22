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
          <button :disabled="chunks.length === 0 || isUploading" @click="upload">
            {{ isUploading ? 'Verarbeitung läuft…' : 'Verarbeiten' }}
          </button>
          <button type="button" class="secondary" :disabled="!currentBlob" @click="downloadRecording">
            Download
          </button>
        </div>
        <div class="recorder-card__meta">
          <p>Aufnahmedauer: {{ durationLabel }}</p>
          <p>Datei: <strong>{{ recordingName }}</strong></p>
          <p>Dateigröße: {{ sizeLabel }}</p>
        </div>
        <label class="file-upload">
          <span>Eigenes Audio hochladen</span>
          <input type="file" accept="audio/*" @change="handleFileUpload" />
        </label>
        <ul>
          <li v-for="chunk in chunks" :key="chunk.id">Chunk {{ chunk.index + 1 }} – {{ chunk.sizeLabel }}</li>
        </ul>
        <div v-if="transcriptionSegments.length" class="segments">
          <h4>Transkript-Abschnitte</h4>
          <ul>
            <li v-for="(segment, index) in transcriptionSegments" :key="`${segment.start}-${index}`">
              {{ formatTime(segment.start) }} – {{ formatTime(segment.end) }} · {{ segment.text }}
            </li>
          </ul>
        </div>
      </div>
      <div class="recorder-card">
        <h3>Text-Notizen</h3>
        <label class="field">
          <span>Prozesstitel</span>
          <input v-model="processTitle" type="text" placeholder="Titel für den Entwurf" />
        </label>
        <label class="field">
          <span>Abstraktionsebene</span>
          <select v-model="level">
            <option value="L1">L1 – Prozesslandschaft</option>
            <option value="L2">L2 – Wertschöpfung</option>
            <option value="L3">L3 – Fachprozess</option>
            <option value="L4">L4 – Arbeitsanweisung</option>
          </select>
        </label>
        <textarea v-model="notes" rows="10" placeholder="Schreibe hier deine Schritte..." />
        <div class="button-row">
          <button @click="generateFromText" :disabled="!notes.trim() || isGenerating">
            {{ isGenerating ? 'Erzeuge Entwurf…' : 'BPMN-Entwurf erzeugen' }}
          </button>
          <button type="button" class="secondary" :disabled="!bpmnXml" @click="downloadBpmn">
            BPMN herunterladen
          </button>
        </div>
        <p v-if="info" class="info">{{ info }}</p>
        <div v-if="extractionSummary" class="extraction-summary">
          <h4>Extraktionsübersicht</h4>
          <ul>
            <li>Swimlanes: {{ extractionSummary.lanes }}</li>
            <li>Aufgaben: {{ extractionSummary.tasks }}</li>
            <li>Ereignisse: {{ extractionSummary.events }}</li>
            <li>Gateways: {{ extractionSummary.gateways }}</li>
          </ul>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
import { computed, onBeforeUnmount, reactive, ref, watch } from 'vue';
import http from '@/lib/http';

const MAX_CHUNK_BYTES =
  Number(import.meta.env.VITE_UPLOAD_MAX_CHUNK_BYTES ?? 15 * 1024 * 1024) || 15 * 1024 * 1024;

const mediaRecorder = ref(null);
const mediaStream = ref(null);
const isRecording = ref(false);
const error = ref('');
const notes = ref('');
const chunks = reactive([]);
const duration = ref(0);
const timer = ref(null);
const recordedSegments = ref([]);
const transcriptionSegments = ref([]);
const currentBlob = ref(null);
const recordingName = ref('capture.ogg');
const currentMime = ref('audio/ogg');
const downloadUrl = ref('');
const isUploading = ref(false);
const info = ref('');
const extractionResult = ref(null);
const bpmnXml = ref('');
const bpmnDownloadUrl = ref('');
const isGenerating = ref(false);
const processTitle = ref('');
const level = ref('L4');

const extractionSummary = computed(() => {
  if (!extractionResult.value) {
    return null;
  }
  return {
    lanes: extractionResult.value.lanes?.length ?? 0,
    tasks: extractionResult.value.tasks?.length ?? 0,
    events: extractionResult.value.events?.length ?? 0,
    gateways: extractionResult.value.gateways?.length ?? 0,
  };
});

watch([notes, level, processTitle], () => {
  if (isGenerating.value) {
    return;
  }
  const hadBpmn = Boolean(bpmnXml.value);
  extractionResult.value = null;
  bpmnXml.value = '';
  revokeBpmnDownloadUrl();
  if (hadBpmn) {
    info.value = 'Inhalt wurde geändert. Bitte den BPMN-Entwurf erneut erzeugen.';
  }
});

const durationLabel = computed(() => new Date(duration.value * 1000).toISOString().substring(14, 19));
const sizeLabel = computed(() => formatSize(currentBlob.value?.size ?? 0));

async function toggleRecording() {
  if (isRecording.value) {
    mediaRecorder.value?.stop();
    stopStream();
    isRecording.value = false;
    clearInterval(timer.value);
    return;
  }

  clearDerivedData(true);
  resetRecordingState();
  recordingName.value = 'capture.ogg';
  currentMime.value = 'audio/ogg';

  try {
    const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
    mediaStream.value = stream;
    mediaRecorder.value = new MediaRecorder(stream, { mimeType: 'audio/ogg' });
    mediaRecorder.value.ondataavailable = (event) => {
      if (event.data.size > 0) {
        recordedSegments.value.push(event.data);
      }
    };
    mediaRecorder.value.onstop = buildChunksFromSegments;
    mediaRecorder.value.start();
    isRecording.value = true;
    duration.value = 0;
    timer.value = setInterval(() => (duration.value += 1), 1000);
  } catch (err) {
    error.value = 'Recorder konnte nicht gestartet werden.';
    stopStream();
  }
}

async function upload() {
  error.value = '';
  clearDerivedData(false);

  if (!currentBlob.value || chunks.length === 0) {
    error.value = 'Keine Aufnahme vorhanden.';
    return;
  }

  isUploading.value = true;

  try {
    const filename = recordingName.value || 'recording.ogg';
    const mimeType = currentMime.value || 'audio/ogg';
    const totalSize = currentBlob.value.size;

    const { data } = await http.post('/api/uploads', {
      filename,
      mime_type: mimeType,
      size: totalSize,
    });

    for (const chunk of chunks) {
      const formData = new FormData();
      formData.append('chunk', chunk.blob, `chunk-${chunk.index}.ogg`);
      formData.append('idx', chunk.index);
      formData.append('checksum', await checksum(chunk.blob));
      await http.post(`/api/uploads/${data.upload_id}/chunks`, formData);
    }

    const finalize = await http.post(`/api/uploads/${data.upload_id}/finalize`, { concat: true });
    const transcription = await http.post('/api/transcriptions', { file_refs: finalize.data.file_refs });
    handleTranscriptionResponse(transcription.data);
  } catch (err) {
    const message = err?.response?.data?.message ?? 'Upload fehlgeschlagen.';
    error.value = message;
  } finally {
    isUploading.value = false;
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

async function generateFromText() {
  const transcriptionText = notes.value.trim();
  if (!transcriptionText || isGenerating.value) {
    return;
  }

  error.value = '';
  info.value = '';
  extractionResult.value = null;
  bpmnXml.value = '';
  revokeBpmnDownloadUrl();

  if (!processTitle.value) {
    processTitle.value = deriveTitleFromText(transcriptionText);
  }

  isGenerating.value = true;

  try {
    const { data: extraction } = await http.post('/api/extract', {
      text: transcriptionText,
      level: level.value,
    });

    extractionResult.value = extraction;

    const { data: bpmnResult } = await http.post('/api/bpmn', {
      model: extraction,
      title: processTitle.value || 'Prozessentwurf',
    });

    bpmnXml.value = bpmnResult.bpmn_xml;
    updateBpmnDownloadUrl(bpmnXml.value);
    info.value = 'BPMN-Entwurf erstellt. Du kannst ihn herunterladen oder im Editor weiterverarbeiten.';
  } catch (err) {
    const responseErrors = err?.response?.data?.errors;
    if (responseErrors) {
      error.value = Object.values(responseErrors).flat().join(' ');
    } else {
      error.value = err?.response?.data?.message ?? 'Erstellung des BPMN-Entwurfs fehlgeschlagen.';
    }
  } finally {
    isGenerating.value = false;
  }
}

onBeforeUnmount(() => {
  clearInterval(timer.value);
  if (isRecording.value && mediaRecorder.value) {
    mediaRecorder.value.stop();
  }
  stopStream();
  revokeDownloadUrl();
  revokeBpmnDownloadUrl();
});

function stopStream() {
  if (mediaStream.value) {
    mediaStream.value.getTracks().forEach((track) => track.stop());
    mediaStream.value = null;
  }
  mediaRecorder.value = null;
}

function buildChunksFromSegments() {
  const segments = recordedSegments.value.slice();
  if (!segments.length) {
    resetRecordingState();
    return;
  }

  const fullBlob = new Blob(segments, { type: 'audio/ogg' });
  prepareChunksFromBlob(fullBlob, 'capture.ogg');
  recordedSegments.value = [];
}

function prepareChunksFromBlob(blob, name = 'recording.ogg') {
  if (!blob) {
    return;
  }

  chunks.splice(0, chunks.length);
  currentBlob.value = blob;
  recordingName.value = name || 'recording.ogg';
  if (blob.type) {
    currentMime.value = blob.type;
  }

  if (blob.size <= MAX_CHUNK_BYTES) {
    chunks.push(buildChunk(blob, 0));
  } else {
    let offset = 0;
    let index = 0;
    while (offset < blob.size) {
      const slice = blob.slice(offset, offset + MAX_CHUNK_BYTES, blob.type || 'audio/ogg');
      chunks.push(buildChunk(slice, index));
      offset += MAX_CHUNK_BYTES;
      index += 1;
    }
  }

  updateDownloadUrl(blob);
}

function buildChunk(blob, index) {
  return {
    id: crypto.randomUUID(),
    index,
    blob,
    size: blob.size,
    sizeLabel: formatSize(blob.size),
  };
}

function handleFileUpload(event) {
  const [file] = event.target.files ?? [];
  if (!file) {
    return;
  }

  if (isRecording.value) {
    mediaRecorder.value?.stop();
    isRecording.value = false;
    clearInterval(timer.value);
  }

  stopStream();
  recordedSegments.value = [];
  error.value = '';
  clearDerivedData(true);

  prepareChunksFromBlob(file, file.name);
  event.target.value = '';
}

function downloadRecording() {
  if (!currentBlob.value) {
    return;
  }

  if (!downloadUrl.value) {
    updateDownloadUrl(currentBlob.value);
  }

  const link = document.createElement('a');
  link.href = downloadUrl.value;
  link.download = recordingName.value || 'recording.ogg';
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
}

function downloadBpmn() {
  if (!bpmnXml.value) {
    return;
  }
  if (!bpmnDownloadUrl.value) {
    updateBpmnDownloadUrl(bpmnXml.value);
  }
  const link = document.createElement('a');
  link.href = bpmnDownloadUrl.value;
  const safeTitle = (processTitle.value || 'prozess-entwurf').trim().replace(/\s+/g, '_');
  link.download = `${safeTitle}.bpmn`;
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
}

function handleTranscriptionResponse(payload) {
  if (!payload) {
    return;
  }
  const text = payload.text ?? '';
  const trimmed = text.trim();
  if (trimmed) {
    notes.value = trimmed;
    if (!processTitle.value) {
      processTitle.value = deriveTitleFromText(trimmed);
    }
    info.value = 'Transkript übernommen. Prüfe den Text und erstelle anschließend den BPMN-Entwurf.';
  } else {
    info.value = 'Das Transkript ist leer. Bitte erneut aufnehmen oder Text manuell ergänzen.';
  }
  const segments = Array.isArray(payload.segments) ? payload.segments : [];
  transcriptionSegments.value = segments.map((segment) => ({
    start: Number(segment.start ?? 0),
    end: Number(segment.end ?? 0),
    text: (segment.text ?? '').trim(),
  }));
}

function clearDerivedData(resetNotes = false) {
  transcriptionSegments.value = [];
  extractionResult.value = null;
  bpmnXml.value = '';
  info.value = '';
  isGenerating.value = false;
  if (resetNotes) {
    notes.value = '';
    processTitle.value = '';
  }
  revokeBpmnDownloadUrl();
}

function resetRecordingState(name = 'capture.ogg', mime = 'audio/ogg') {
  chunks.splice(0, chunks.length);
  recordedSegments.value = [];
  currentBlob.value = null;
  recordingName.value = name;
  currentMime.value = mime;
  error.value = '';
  revokeDownloadUrl();
}

function updateBpmnDownloadUrl(xml) {
  revokeBpmnDownloadUrl();
  if (!xml) {
    return;
  }
  const blob = new Blob([xml], { type: 'application/xml' });
  bpmnDownloadUrl.value = URL.createObjectURL(blob);
}

function updateDownloadUrl(blob) {
  revokeDownloadUrl();
  if (!blob) {
    return;
  }
  downloadUrl.value = URL.createObjectURL(blob);
}

function revokeDownloadUrl() {
  if (downloadUrl.value) {
    URL.revokeObjectURL(downloadUrl.value);
    downloadUrl.value = '';
  }
}

function revokeBpmnDownloadUrl() {
  if (bpmnDownloadUrl.value) {
    URL.revokeObjectURL(bpmnDownloadUrl.value);
    bpmnDownloadUrl.value = '';
  }
}

function formatSize(bytes) {
  if (!bytes) {
    return '0.00 MB';
  }
  return `${(bytes / 1024 / 1024).toFixed(2)} MB`;
}

function formatTime(value) {
  if (typeof value !== 'number' || Number.isNaN(value)) {
    return '0:00';
  }
  const totalSeconds = Math.max(0, Math.floor(value));
  const minutes = Math.floor(totalSeconds / 60)
    .toString()
    .padStart(1, '0');
  const seconds = (totalSeconds % 60).toString().padStart(2, '0');
  return `${minutes}:${seconds}`;
}

function deriveTitleFromText(text) {
  const clean = text.replace(/\s+/g, ' ').trim();
  if (!clean) {
    return '';
  }
  const firstSentence = clean.split(/[\.\n!?]/).find((part) => part.trim().length > 5) ?? clean;
  return firstSentence.trim().slice(0, 80);
}
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
.recorder-card__controls {
  display: flex;
  gap: 0.75rem;
  flex-wrap: wrap;
}
.recorder-card__meta {
  display: grid;
  gap: 0.25rem;
  color: #475569;
  font-size: 0.95rem;
}
.field {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  font-weight: 600;
  color: #0f172a;
}
.field input,
.field select {
  padding: 0.65rem 1rem;
  border-radius: 0.75rem;
  border: 1px solid #cbd5f5;
  font-size: 1rem;
}
.button-row {
  display: flex;
  gap: 0.75rem;
  flex-wrap: wrap;
}
.error {
  color: #dc2626;
  font-weight: 600;
}
.info {
  background: rgba(4, 120, 87, 0.12);
  color: #047857;
  padding: 0.75rem 1rem;
  border-radius: 0.75rem;
  font-size: 0.95rem;
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
button.secondary {
  background: transparent;
  color: #0f172a;
  border: 1px solid #cbd5f5;
}
button.secondary:hover:not(:disabled) {
  background: rgba(37, 99, 235, 0.08);
}
button.secondary:disabled {
  background: rgba(203, 213, 245, 0.4);
  color: #94a3b8;
  border-color: #cbd5f5;
}
.extraction-summary {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 1rem;
  padding: 1rem;
  display: grid;
  gap: 0.5rem;
}
.extraction-summary ul {
  margin: 0;
  padding-left: 1.25rem;
  display: grid;
  gap: 0.25rem;
}
.file-upload {
  display: inline-flex;
  align-items: center;
  gap: 0.75rem;
  font-weight: 600;
  color: #2563eb;
  cursor: pointer;
}
.file-upload input {
  display: none;
}
.segments {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 1rem;
  padding: 1rem;
  display: grid;
  gap: 0.75rem;
  max-height: 220px;
  overflow-y: auto;
}
.segments h4 {
  margin: 0;
  font-size: 0.95rem;
  font-weight: 700;
  color: #0f172a;
}
.segments ul {
  margin: 0;
  padding-left: 1.25rem;
  display: grid;
  gap: 0.4rem;
  color: #475569;
  font-size: 0.95rem;
}
</style>
