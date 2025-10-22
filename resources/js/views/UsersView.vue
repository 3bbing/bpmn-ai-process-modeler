<template>
  <section class="users">
    <header class="users__header">
      <h2>Benutzerverwaltung</h2>
      <button class="primary" @click="startCreate">Neuen Benutzer anlegen</button>
    </header>

    <div class="layout-grid">
      <div class="card">
        <h3>Benutzer</h3>
        <ul class="user-list">
          <li
            v-for="user in users"
            :key="user.id"
            :class="{ active: selectedUser?.id === user.id }"
            @click="selectUser(user)"
          >
            <span class="user-list__name">{{ user.name }}</span>
            <span class="user-list__email">{{ user.email }}</span>
            <span class="user-list__roles">{{ user.roles.join(', ') || '–' }}</span>
          </li>
        </ul>
      </div>

      <div class="card">
        <h3>{{ isCreating ? 'Benutzer anlegen' : 'Benutzer bearbeiten' }}</h3>

        <form @submit.prevent="submit">
          <label>
            Name
            <input v-model="form.name" type="text" required />
          </label>
          <label>
            E-Mail
            <input v-model="form.email" type="email" required />
          </label>
          <div class="split">
            <label>
              Passwort
              <input v-model="form.password" type="password" :required="isCreating" autocomplete="new-password" />
            </label>
            <label>
              Passwort bestätigen
              <input
                v-model="form.password_confirmation"
                type="password"
                :required="isCreating"
                autocomplete="new-password"
              />
            </label>
          </div>

          <fieldset>
            <legend>Rollen</legend>
            <label v-for="role in roles" :key="role" class="checkbox">
              <input type="checkbox" :value="role" v-model="form.roles" />
              <span>{{ role }}</span>
            </label>
          </fieldset>

          <div v-if="formErrors.length" class="error-list">
            <p v-for="(error, idx) in formErrors" :key="idx">{{ error }}</p>
          </div>
          <p v-if="formSuccess" class="success">{{ formSuccess }}</p>

          <div class="form-actions">
            <button class="primary" type="submit" :disabled="submitting">
              {{ submitting ? 'Speicherung...' : isCreating ? 'Benutzer erstellen' : 'Änderungen speichern' }}
            </button>
            <button v-if="!isCreating" class="danger" type="button" @click="removeUser" :disabled="submitting">
              Benutzer löschen
            </button>
          </div>
        </form>
      </div>
    </div>
  </section>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import http from '@/lib/http';
import { useAuthStore } from '@/stores/useAuthStore';

const authStore = useAuthStore();

const users = ref([]);
const roles = ref([]);
const selectedUser = ref(null);
const submitting = ref(false);
const formErrors = ref([]);
const formSuccess = ref('');

const form = reactive({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
  roles: [],
});

const isCreating = computed(() => !selectedUser.value || selectedUser.value.id === null);

onMounted(async () => {
  await authStore.fetchCurrentUser();
  await fetchUsers();
});

function startCreate() {
  selectedUser.value = { id: null };
  Object.assign(form, {
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    roles: [],
  });
  formErrors.value = [];
  formSuccess.value = '';
}

function selectUser(user) {
  selectedUser.value = user;
  Object.assign(form, {
    name: user.name,
    email: user.email,
    password: '',
    password_confirmation: '',
    roles: [...user.roles],
  });
  formErrors.value = [];
  formSuccess.value = '';
}

async function fetchUsers() {
  const { data } = await http.get('/api/users');
  users.value = data.data ?? [];
  roles.value = data.meta?.roles ?? [];

  if (!selectedUser.value && users.value.length) {
    selectUser(users.value[0]);
  }
}

async function submit() {
  submitting.value = true;
  formErrors.value = [];
  formSuccess.value = '';

  try {
    const payload = {
      name: form.name,
      email: form.email,
      roles: form.roles,
    };

    if (form.password) {
      payload.password = form.password;
      payload.password_confirmation = form.password_confirmation;
    }

    if (isCreating.value) {
      if (!form.password) {
        formErrors.value = ['Passwort wird benötigt.'];
        return;
      }

      const { data } = await http.post('/api/users', payload);
      const user = data.data ?? data;
      users.value.push(user);
      selectUser(user);
      formSuccess.value = 'Benutzer erstellt.';
    } else {
      const { data } = await http.patch(`/api/users/${selectedUser.value.id}`, payload);
      const updated = data.data ?? data;
      const idx = users.value.findIndex((u) => u.id === updated.id);
      if (idx !== -1) {
        users.value[idx] = updated;
      }
      selectUser(updated);
      if (authStore.user?.id === updated.id) {
        await authStore.refresh();
      }
      formSuccess.value = 'Benutzer aktualisiert.';
    }
  } catch (error) {
    if (error.response?.data?.errors) {
      formErrors.value = flattenErrors(error.response.data.errors);
    } else if (error.response?.data?.message) {
      formErrors.value = [error.response.data.message];
    }
  } finally {
    submitting.value = false;
  }
}

async function removeUser() {
  if (!selectedUser.value || !selectedUser.value.id) return;
  if (!confirm('Benutzer wirklich löschen? Dies kann nicht rückgängig gemacht werden.')) return;

  submitting.value = true;
  formErrors.value = [];
  formSuccess.value = '';

  try {
    await http.delete(`/api/users/${selectedUser.value.id}`);
    users.value = users.value.filter((u) => u.id !== selectedUser.value.id);
    selectedUser.value = null;
    startCreate();
    formSuccess.value = 'Benutzer gelöscht.';
  } catch (error) {
    if (error.response?.data?.message) {
      formErrors.value = [error.response.data.message];
    }
  } finally {
    submitting.value = false;
  }
}

function flattenErrors(errors) {
  return Object.values(errors).flat();
}
</script>

<style scoped>
.users {
  display: grid;
  gap: 2rem;
}
.users__header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 1rem;
}
.layout-grid {
  display: grid;
  gap: 2rem;
  grid-template-columns: minmax(260px, 320px) 1fr;
}
.card {
  background: white;
  padding: 2rem;
  border-radius: 1.25rem;
  box-shadow: 0 15px 35px rgba(15, 23, 42, 0.12);
  display: grid;
  gap: 1.5rem;
}
.user-list {
  list-style: none;
  margin: 0;
  padding: 0;
  display: grid;
  gap: 0.75rem;
}
.user-list li {
  padding: 1rem;
  border-radius: 1rem;
  border: 1px solid transparent;
  background: #f1f5f9;
  cursor: pointer;
  display: grid;
  gap: 0.25rem;
}
.user-list li:hover {
  border-color: #2563eb;
}
.user-list li.active {
  border-color: #2563eb;
  background: rgba(37, 99, 235, 0.08);
}
.user-list__name {
  font-weight: 600;
  color: #0f172a;
}
.user-list__email {
  color: #475569;
  font-size: 0.95rem;
}
.user-list__roles {
  font-size: 0.8rem;
  color: #1d4ed8;
}
form {
  display: grid;
  gap: 1rem;
}
label {
  display: grid;
  gap: 0.5rem;
  font-weight: 600;
  color: #0f172a;
}
input {
  padding: 0.75rem 1rem;
  border-radius: 0.75rem;
  border: 1px solid #cbd5f5;
  font-size: 1rem;
  width: 100%;
}
fieldset {
  border: 1px solid #cbd5f5;
  border-radius: 1rem;
  padding: 1rem;
  display: grid;
  gap: 0.5rem;
}
legend {
  padding: 0 0.5rem;
  color: #475569;
  font-size: 0.9rem;
}
.checkbox {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  font-weight: 500;
}
.primary {
  padding: 0.75rem 1.5rem;
  border-radius: 999px;
  border: none;
  background: #2563eb;
  color: white;
  font-weight: 600;
  cursor: pointer;
}
.danger {
  padding: 0.75rem 1.5rem;
  border-radius: 999px;
  border: none;
  background: #dc2626;
  color: white;
  font-weight: 600;
  cursor: pointer;
}
.primary:disabled,
.danger:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}
.split {
  display: grid;
  gap: 1rem;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
}
.form-actions {
  display: flex;
  gap: 1rem;
}
.error-list {
  background: rgba(220, 38, 38, 0.1);
  color: #b91c1c;
  padding: 0.75rem 1rem;
  border-radius: 0.75rem;
  display: grid;
  gap: 0.25rem;
}
.success {
  color: #047857;
  font-size: 0.95rem;
}

@media (max-width: 960px) {
  .layout-grid {
    grid-template-columns: 1fr;
  }
}
</style>
