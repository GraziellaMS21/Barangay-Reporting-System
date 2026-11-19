# Barangay Problem Reporting and Management System

A municipal civic issue management web platform for local governance in **Barangay Calarian, Zamboanga City**. The system enables residents to report community incidents (drainage, garbage, streetlights, road damage, flooding) without an account, provides Google Gemini AI-assisted triage with human-in-the-loop review, enforces jurisdiction-based internal dispatch and external agency referrals, and provides real-time tracking, resolution verification, and analytics.

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
