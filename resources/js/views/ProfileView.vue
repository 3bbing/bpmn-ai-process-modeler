<template>
  <section class="profile">
    <h2>Mein Profil</h2>

    <div class="card">
      <h3>Persönliche Daten</h3>
      <form @submit.prevent="updateProfile">
        <label>
          Name
          <input v-model="profileForm.name" type="text" required />
        </label>
        <label>
          E-Mail
          <input v-model="profileForm.email" type="email" required />
        </label>
        <p v-if="profileErrors.email" class="error">{{ profileErrors.email }}</p>
        <p v-if="profileErrors.name" class="error">{{ profileErrors.name }}</p>
        <p v-if="profileSuccess" class="success">{{ profileSuccess }}</p>
        <button type="submit" :disabled="profileSaving">
          {{ profileSaving ? 'Speicherung...' : 'Änderungen speichern' }}
        </button>
      </form>
    </div>

    <div class="card">
      <h3>Passwort ändern</h3>
      <form @submit.prevent="updatePassword">
        <label>
          Aktuelles Passwort
          <input v-model="passwordForm.current_password" type="password" required autocomplete="current-password" />
        </label>
        <label>
          Neues Passwort
          <input v-model="passwordForm.password" type="password" required autocomplete="new-password" />
        </label>
        <label>
          Neues Passwort bestätigen
          <input v-model="passwordForm.password_confirmation" type="password" required autocomplete="new-password" />
        </label>
        <p v-if="passwordErrors.current_password" class="error">{{ passwordErrors.current_password }}</p>
        <p v-if="passwordErrors.password" class="error">{{ passwordErrors.password }}</p>
        <p v-if="passwordSuccess" class="success">{{ passwordSuccess }}</p>
        <button type="submit" :disabled="passwordSaving">
          {{ passwordSaving ? 'Speicherung...' : 'Passwort aktualisieren' }}
        </button>
      </form>
    </div>
  </section>
</template>

<script setup>
import { computed, reactive, ref, watch } from 'vue';
import http from '@/lib/http';
import { useAuthStore } from '@/stores/useAuthStore';

const authStore = useAuthStore();

const profileForm = reactive({
  name: '',
  email: '',
});

const passwordForm = reactive({
  current_password: '',
  password: '',
  password_confirmation: '',
});

const profileSaving = ref(false);
const passwordSaving = ref(false);
const profileSuccess = ref('');
const passwordSuccess = ref('');
const profileErrors = reactive({});
const passwordErrors = reactive({});

const user = computed(() => authStore.user);

watch(
  user,
  (next) => {
    if (!next) return;
    profileForm.name = next.name ?? '';
    profileForm.email = next.email ?? '';
  },
  { immediate: true }
);

async function updateProfile() {
  profileSaving.value = true;
  profileSuccess.value = '';
  Object.keys(profileErrors).forEach((key) => delete profileErrors[key]);

  try {
    const { data } = await http.patch('/api/me', profileForm);
    authStore.user = data.data ?? data;
    profileSuccess.value = 'Profil gespeichert.';
  } catch (error) {
    if (error.response?.data?.errors) {
      Object.assign(profileErrors, formatErrors(error.response.data.errors));
    }
  } finally {
    profileSaving.value = false;
  }
}

async function updatePassword() {
  passwordSaving.value = true;
  passwordSuccess.value = '';
  Object.keys(passwordErrors).forEach((key) => delete passwordErrors[key]);

  try {
    await http.patch('/api/me/password', passwordForm);
    passwordSuccess.value = 'Passwort aktualisiert.';
    passwordForm.current_password = '';
    passwordForm.password = '';
    passwordForm.password_confirmation = '';
  } catch (error) {
    if (error.response?.data?.errors) {
      Object.assign(passwordErrors, formatErrors(error.response.data.errors));
    } else if (error.response?.data?.message) {
      passwordErrors.current_password = error.response.data.message;
    }
  } finally {
    passwordSaving.value = false;
  }
}

function formatErrors(errors) {
  return Object.fromEntries(
    Object.entries(errors).map(([key, messages]) => [key, [].concat(messages).join(' ')])
  );
}
</script>

<style scoped>
.profile {
  display: grid;
  gap: 2rem;
  max-width: 720px;
}
.card {
  background: white;
  padding: 2rem;
  border-radius: 1.25rem;
  box-shadow: 0 15px 35px rgba(15, 23, 42, 0.12);
  display: grid;
  gap: 1.5rem;
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
}
button {
  justify-self: start;
  padding: 0.75rem 1.5rem;
  border-radius: 999px;
  border: none;
  background: #2563eb;
  color: white;
  font-weight: 600;
  cursor: pointer;
}
button:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}
.error {
  color: #b91c1c;
  font-size: 0.95rem;
}
.success {
  color: #047857;
  font-size: 0.95rem;
}
</style>
