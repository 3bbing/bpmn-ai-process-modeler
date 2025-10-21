# BPMN AI Process Modeler

Eine Laravel + Vue Anwendung zur sprach- oder textbasierten Erfassung von Prozessen inklusive automatischer BPMN-Generierung, Versionierung und Prozessbuch.

## Features (MVP)
- Chunk-basierte Audio-Uploads (≤ 15 MB) mit Recorder im Browser.
- OpenAI-gestützte Transkription und strukturierte Prozess-Extraktion.
- BPMN 2.0 Editor (bpmn.js) mit Autosave, L4-SOP Panels und Validierungen.
- Rollenmodell (Reader, Author, Reviewer, Owner, Admin) via Spatie Permissions.
- Review-Workflow (Draft → Review → Published) inkl. Kommentar-Threads.
- Prozessbuch mit Suche, Export (BPMN, PNG/SVG Placeholder, PDF/ZIP) und AI-Beschreibungen.

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
