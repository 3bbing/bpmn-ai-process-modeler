# BPMN AI Process Modeler

Eine Laravel + Vue Anwendung zur sprach- oder textbasierten Erfassung von Prozessen inklusive automatischer BPMN-Generierung, Versionierung und Prozessbuch.

## Features (MVP)
- Chunk-basierte Audio-Uploads (≤ 15 MB) mit Recorder im Browser.
- Recorder-Workflow mit manuellen Uploads, Transkriptvorschau und BPMN-Entwurf per Klick.
- OpenAI-gestützte Transkription und strukturierte Prozess-Extraktion.
- BPMN 2.0 Editor (bpmn.js) mit Autosave, L4-SOP Panels und Validierungen.
- Rollenmodell (Reader, Author, Reviewer, Owner, Admin) via Spatie Permissions.
- Review-Workflow (Draft → Review → Published) inkl. Kommentar-Threads.
- Prozessbuch mit Suche, Export (BPMN, PNG/SVG Placeholder, PDF/ZIP) und AI-Beschreibungen.
- Self-service Profilverwaltung sowie Admin-Konsole für Benutzer, Rollen & Passwort-Resets.

## Tech Stack
- **Backend:** Laravel 11, PHP 8.2+, MySQL 8, Sanctum, Spatie Permissions & Activitylog.
- **Frontend:** Vue 3, Vite, Pinia, vue-router, bpmn.js, @bpmn-io/properties-panel.
- **Services:** OpenAI Whisper/GPT (Transkription & LLM), DB Queue, Storage Disk.

## Lokales Setup
```bash
cp .env.example .env
# Datenbank Zugangsdaten anpassen
# Composer Abhängigkeiten (benötigt Internet)
composer install
php artisan key:generate
php artisan migrate --seed

# Node Pakete installieren
npm install
npm run dev
```

Backend unter `php artisan serve`, Frontend via `npm run dev` (Vite, Port 5173).
Setze optional `VITE_API_BASE_URL` in `.env` und `frontend/.env` für abweichende API-Hosts.

### Default admin bootstrap
- Set `APP_DEFAULT_ADMIN_EMAIL` and `APP_DEFAULT_ADMIN_PASSWORD` in `.env` before running `php artisan migrate --seed`.
- The seeder creates or updates that user with the `admin` role on every deploy. Change the password immediately after first login or remove the env vars to prevent reuse.
- After signing in, profile settings are under `/profile`; the admin-only user management lives at `/admin/users`.
- OpenAI-Transkription: passe bei Bedarf `OPENAI_TRANSCRIPTION_RESPONSE_FORMAT` an (`json` oder `text`, abhängig vom Modell).
- Für Extraktionen kannst du zwischen `OPENAI_LLM_MODEL=gpt-5.1` (präziser, langsamer) und z.B. `gpt-4o-mini` (schneller, günstiger) wechseln. Nutze `OPENAI_TIMEOUT`/`OPENAI_CONNECT_TIMEOUT` falls komplexe Prompts länger dauern.
- `OPENAI_CHAT_TEMPERATURE` bleibt bei gpt-5-Modellen automatisch auf 1.0 (modellspezifische Vorgabe); leisere Werte greifen für gpt-4o Varianten. Die Extraktion versucht, JSON-Blöcke aus Antworten zu extrahieren – bei Mischformaten oder abgeschnittenen Antworten findest du die Rohdaten im Log.
- Fehlschlagende Extraktionen protokollieren das Roh-JSON in der Fehlermeldung – prüfe ggf. das Log, falls die OpenAI-Antwort nicht dem Schema entspricht.

### Writable directories
When deploying to a host ensure Laravel’s writable folders exist before running artisan commands:

```bash
mkdir -p storage/app/public \
         storage/framework/cache/data \
         storage/framework/sessions \
         storage/framework/testing \
         storage/framework/views
chmod -R 775 storage bootstrap/cache
```

### Security notice (dev dependences)
`npm audit` may flag a moderate vulnerability in `esbuild` via Vite 5.x. The issue only affects the local dev server; keep it bound to `localhost` when possible. Updating to Vite ≥6.4.1 resolves it but is a breaking upgrade, so plan the migration separately.

## Tests
```bash
php artisan test
npm run build
```

## Weitere Dokumentation
- [docs/ARCHITECTURE.md](docs/ARCHITECTURE.md)

## Geplante Erweiterungen
- BPMN-Linting & Live-Guidance Checks.
- Workflow für Kommentare/Threads pro Prozessschritt.
- PDF-Export mit Template und Assets.
- E2E-Tests und Screenshot-Regressionen.
