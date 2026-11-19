# AGENTS.md — Operational Rules & Execution Guide

Imperative operational manual for AI coding agents working in **Barangay-Reporting-System-1**.

## 1. Project Commands (Actual CLI Scripts)

```sh
# Setup & Installation
composer run setup                   # Full setup (composer, .env, key, migrate, npm install, build)
composer install                     # Install PHP backend dependencies
npm install                          # Install frontend JS/CSS dependencies

# Development Servers
composer run dev                     # Starts full dev environment (Artisan serve + Vite bundler)
php artisan serve                    # Start Laravel backend server (http://localhost:8000)
npm run dev                          # Start Vite asset development server
npm run build                        # Compile and minify production assets (Vite + Tailwind v4)

# Testing (Executes in SQLite in-memory / testing environment)
php artisan test                     # Run entire PHPUnit test suite (clears cached config first)
php artisan test path/to/Test.php    # Run single test file (e.g., tests/Feature/StaffCaseManagementTest.php)
php artisan test --filter=test_name  # Run a specific test method

# Code Style & Diagnostics
./vendor/bin/pint                    # Apply Laravel Pint coding style fixes
./vendor/bin/pint --test             # Dry-run check for code style issues without altering files
php artisan route:list               # Validate routing table and parameter bindings
php artisan config:clear             # Clear cached configuration
```

## 2. Tech Stack & Key Versions

- **Backend**: PHP 8.4+ (`^8.3`), Laravel Framework `^13.17`, Laravel Tinker `^3.0`
- **Database/ORM**: Eloquent ORM with SQLite (`database/database.sqlite` / `:memory:` for testing), MySQL prototype target
- **Frontend / Bundler**: Vite `^8.0.0`, Tailwind CSS `^4.0.0` with `@tailwindcss/vite` `^4.0.0`
- **Libraries**: Leaflet `^1.9.4` (maps), Chart.js `^4.6.1` (analytics), FontAwesome Free `^7.3.1`
- **AI Integration**: Google Gemini API (`gemini-2.5-flash`) via Laravel `Http` facade
- **Testing & Tooling**: PHPUnit `^12.5.12`, Laravel Pint `^1.27`, Laravel Boost `^2.10`, Laravel Pail `^1.2.5`

## 3. Directory & File Placement Rules

- `app/Models/`: Eloquent entities with typed casts, relations, and constants (`Report.php`, `Category.php`, `Referral.php`, etc.).
- `app/Http/Controllers/`:
  - Resident/Public controllers: `ReportController.php`, `TrackController.php`, `MapController.php`.
  - Staff/Admin controllers in `Admin/`: `ReportController.php`, `DashboardController.php`, `SettingsController.php`, `ReferralController.php`.
- `app/Services/`: Domain logic services (`GeminiService.php`, `ReportStatusService.php`, `RoutingService.php`, `PriorityService.php`, `ProofPhotoService.php`, `ReferralService.php`).
- `routes/`: Modular route files required in `routes/web.php`:
  - `routes/resident.php`: Resident submission, tracking, feedback, public map.
  - `routes/staff.php`: Staff queue, review cards, proof photos, status updates, info requests.
  - `routes/admin.php`: Admin metrics, settings, user management, audit logs.
  - `routes/engine.php`: API utilities (e.g. `/api/nearby-reports`).
- `resources/views/`: Blade templates partitioned into `resident/`, `admin/`, `layouts/`, and `components/`.
- `database/migrations/`: Sequential migration files.
- `database/seeders/`: Seeders for puroks, emergency contacts, categories, and demo reports.
- `tests/Feature/` & `tests/Unit/`: Test suites extending `Tests\TestCase`.

## 4. Code Style & Architectural Conventions

- **Strict Typing**: Enforce explicit parameter and return types on all PHP methods. Use constructor property promotion (`public function __construct(public GeminiService $gemini) {}`).
- **Core Mandate**: *The AI suggests, the rules route, the officials decide.* Never auto-merge, auto-route, or auto-resolve cases through AI alone.
- **Layered Architecture**: Keep controllers thin. Route transitions through `ReportStatusService::updateStatus()`, AI tasks to `GeminiService`, and jurisdiction routing to `RoutingService`.
- **State Machine Integrity**: Always check `ReportStatusService::canTransition($from, $to)` before modifying report status.
- **Styling Tokens**: Adhere to core CSS design tokens (`var(--ink)`, `var(--blue)`, `var(--gold)`, `var(--red)`, `var(--line)`). Preserve hero and background markup structure in public views.
- **Linting**: Run `./vendor/bin/pint` on modified PHP files before committing.

## 5. Strict Guardrails & Boundaries

- **Never Edit Manually**: `composer.lock`, `package-lock.json`, `public/build/`, `vendor/`, or `node_modules/`.
- **Immutable Migrations**: Never modify committed migrations that have already run. Create new migration files for schema alterations.
- **Environment Handling**: Never commit `.env` or leak credentials. Refer only to `.env.example` keys (`GEMINI_API_KEY`, `DB_CONNECTION`, etc.).
- **Resident Privacy (RA 10173)**: Never transmit reporter personal identification (name, contact number, email) to external AI APIs. Hide emergency reports from public maps and unauthenticated API responses.
- **Mandatory Verification**: Always verify changes by running targeted tests (`php artisan test <path>`) and linting (`./vendor/bin/pint --test`) prior to concluding tasks.
