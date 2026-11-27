# Barangay Problem Reporting and Management System

A municipal civic issue management web platform for local governance in **Barangay Calarian, Zamboanga City**. The system enables residents to submit general concerns—covering both community problems (drainage, garbage, streetlights, road damage, flooding) and localized incidents—through a unified **Report a Concern** entry point without an account (separate from formal **File a Complaint** dispute intake), provides Google Gemini AI-assisted triage with human-in-the-loop review, enforces jurisdiction-based internal dispatch and external agency referrals, and provides real-time tracking, resolution verification, and analytics.

Core principle: *"The AI suggests, the rules route, the officials decide."*

## Tech Stack

- **Backend**: PHP 8.4+, Laravel Framework 13, Eloquent ORM
- **Database**: SQLite (local development & testing) / MySQL (production prototype target)
- **Frontend**: Blade templates, Tailwind CSS v4 via `@tailwindcss/vite`, FontAwesome Free 7
- **Mapping & Charts**: Leaflet.js 1.9 (OpenStreetMap), Chart.js 4.6
- **AI Integration**: Google Gemini API (`gemini-2.5-flash`) via Laravel `Http` client
- **Testing & Tooling**: PHPUnit 12, Laravel Pint 1.27

## Quick-Start Setup

```sh
# Setup & Installation
composer run setup                   # Install dependencies, copy .env, generate key, migrate, build assets
composer install                     # PHP backend dependencies
npm install                          # Frontend dependencies
php artisan storage:link             # Link public storage disk for photos

# Development Servers
composer run dev                     # Starts both Artisan serve and Vite bundler
php artisan serve                    # Laravel backend server (http://localhost:8000)
npm run dev                          # Vite development server
npm run build                        # Compile and minify production assets

# Testing & Code Quality
php artisan test                     # Run entire PHPUnit test suite
./vendor/bin/pint                    # Apply Laravel Pint code style fixes
./vendor/bin/pint --test             # Check code style dry-run
php artisan route:list               # Inspect routing table
php artisan config:clear             # Clear cached configuration
```

## Local Queue Worker & Email Testing

By default (`.env.example`), the application uses `QUEUE_CONNECTION=database` (storing pending jobs in the `jobs` table) and `MAIL_MAILER=log`.

### 1. Running the Queue Worker

`composer run dev` starts the Laravel web server and Vite bundler, **not** the queue worker. Because report notifications and AI screening run asynchronously on the `database` queue, you must run the queue worker in a **second terminal** while developing locally:

```sh
php artisan queue:work
```

To process just one pending job manually during testing:

```sh
php artisan queue:work --once
```

### 2. Stopping and Restarting the Worker

- **Stop safely:** Press `Ctrl+C` in the worker terminal.
- **Restart after code changes:** `queue:work` keeps the application loaded in memory. After editing listeners, Mailables, or services, run `php artisan queue:restart` (or stop with `Ctrl+C` and start `php artisan queue:work` again).

### 3. Inspecting Locally Logged Emails

With `MAIL_MAILER=log`, Laravel **does not send real emails** over SMTP. Instead, all rendered email subjects, recipients, and HTML bodies are written to:

- `storage/logs/laravel.log`

### 4. Important Queue Behavior

- **After-commit safety:** Notification listeners implement `ShouldHandleEventsAfterCommit`, so jobs are only queued after the related database transaction commits.
- **Worker requirement:** Pending jobs remain in the `jobs` table until `php artisan queue:work` is running.
- **Retry behavior:** Notification listeners are configured with `$tries = 1` (preventing duplicate emails on failure; failed attempts are recorded in `notification_logs` and `failed_jobs`). AI screening (`ProcessNewReport`) uses `$tries = 2` with a 10-second backoff.
- **PHPUnit vs. local dev:** `phpunit.xml` uses `QUEUE_CONNECTION=sync` and `MAIL_MAILER=array` by default. Passing automated tests does **not** test or prove real SMTP delivery.

### 5. Troubleshooting

```sh
php artisan queue:monitor database:default   # Check pending job count on the default queue
php artisan queue:failed                     # List jobs that failed and moved to failed_jobs
```

If an expected email does not appear in `storage/logs/laravel.log`, verify that `php artisan queue:work` is running, check `php artisan queue:failed`, and inspect `storage/logs/laravel.log` for errors.

## Documentation

Full project documentation is organized in [`docs/`](file:///C:/githubFiles/Barangay-Reporting-System-1/docs):

- [**`docs/PROJECT.md`**](file:///C:/githubFiles/Barangay-Reporting-System-1/docs/PROJECT.md): Overview, problem statement, roles, scope, privacy, and deferred features.
- [**`docs/ARCHITECTURE.md`**](file:///C:/githubFiles/Barangay-Reporting-System-1/docs/ARCHITECTURE.md): System architecture, service layer map, data flow, trust boundaries, and external APIs.
- [**`docs/WORKFLOW.md`**](file:///C:/githubFiles/Barangay-Reporting-System-1/docs/WORKFLOW.md): **Canonical source of truth** for lifecycle stages, 12 statuses, transition table, reason codes, and SLA rules.
- [**`docs/CURRENT-STATE.md`**](file:///C:/githubFiles/Barangay-Reporting-System-1/docs/CURRENT-STATE.md): Implementation status, verified features, and code gap analysis.
- [**`docs/DECISIONS.md`**](file:///C:/githubFiles/Barangay-Reporting-System-1/docs/DECISIONS.md): Architectural Decision Records (ADRs 1–50 plus new decisions 1–8).
- [**`docs/CONVENTIONS.md`**](file:///C:/githubFiles/Barangay-Reporting-System-1/docs/CONVENTIONS.md): Coding standards, strict typing, naming, and design tokens.
- [**`docs/TASKS.md`**](file:///C:/githubFiles/Barangay-Reporting-System-1/docs/TASKS.md): Ordered implementation roadmap for pending code updates.
- [**`docs/DATABASE.md`**](file:///C:/githubFiles/Barangay-Reporting-System-1/docs/DATABASE.md): Entities, tables, foreign keys, cascade rules, and JSON schemas.
- [**`docs/API.md`**](file:///C:/githubFiles/Barangay-Reporting-System-1/docs/API.md): Route table, parameters, payload schemas, and middleware.
- [**`docs/TESTING.md`**](file:///C:/githubFiles/Barangay-Reporting-System-1/docs/TESTING.md): Test commands, testing conventions, and 10-step client demo script.
- [**`AGENTS.md`**](file:///C:/githubFiles/Barangay-Reporting-System-1/AGENTS.md): Operational manual and execution protocol for AI coding agents.
