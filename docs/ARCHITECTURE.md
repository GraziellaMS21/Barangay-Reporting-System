# System Architecture & Technical Design

Architectural specification, service layer boundaries, data flow pipelines, and security trust models for the **Barangay Problem Reporting and Management System**.

---

## 1. Architectural Overview

The application follows a **Modular Monolithic MVC Pattern** built on Laravel 13, augmented with an explicit Domain Service Layer. Controller actions remain thin, delegating domain decisions, state transitions, external AI queries, and routing logic to dedicated service classes.

```
       +-------------------------------------------------------------+
       |                     Resident / Public UI                   |
       |  (Report Form, Tracking Status, Confirmation, Public Map)   |
       +------------------------------+------------------------------+
                                      | HTTP Requests / CSRF
                                      v
       +-------------------------------------------------------------+
       |                  Laravel Routing & Middleware               |
       |  (routes/resident.php, routes/staff.php, routes/admin.php)  |
       +------------------------------+------------------------------+
                                      |
                                      v
       +-------------------------------------------------------------+
       |                 Controller Presentation Layer               |
       |        (ReportController, TrackController, Admin/*)         |
       +------------------------------+------------------------------+
                                      | Calls Domain Services
                                      v
       +-------------------------------------------------------------+
       |                       Domain Service Layer                  |
       |  - GeminiService (Async AI Classification & Photo Auditing) |
       |  - ReportStatusService (Finite State Machine Transitions)    |
       |  - RoutingService (Jurisdiction Rules & Office Assignment)  |
       |  - PriorityService (Risk Weights, SLA & Score Calculation)  |
       |  - ReferralService (External Agency Transmission & Letters) |
       |  - ProofPhotoService (Multi-image Resolution Evidence)      |
       |  - DashboardAnalyticsService & HotspotService               |
       +------------------------------+------------------------------+
                                      | Eloquent ORM
                                      v
       +-------------------------------------------------------------+
       |                 Data Storage & External Services            |
       |  - Database: SQLite (Dev/Test) / MySQL (Production Target)  |
       |  - AI API: Google Gemini REST API (gemini-2.5-flash)        |
       |  - Mapping: OpenStreetMap Tile Servers (via Leaflet.js)     |
       |  - Mail: Laravel Mail (Log Driver / SMTP) & NotificationLog |
       +-------------------------------------------------------------+
```

---

## 2. Service Layer Responsibility Map

| Service Class | Location | Primary Responsibilities |
| :--- | :--- | :--- |
| **`GeminiService`** | `app/Services/GeminiService.php` | Handles prompt serialization, HTTP timeouts (30s), fallback model failover, photo description, and response sanitization. Planned: `transcribeAudio()` for audio transcription (audio in, text out; strictly untrusted data, no PII). |
| **`VoiceService`** *(Planned)* | `app/Services/Voice/VoiceService.php` | Manages voice recording ingestion, audio format/duration validation, private disk storage, unverified draft tracking, and staff verification audit logging. |
| **`ReportStatusService`** | `app/Services/ReportStatusService.php` | Enforces state transition matrix integrity, database transactions, and dispatches `ReportStatusChanged` events. |
| **`RoutingService`** | `app/Services/RoutingService.php` | Evaluates category rules against jurisdiction authority (Mode A internal dispatch vs. Mode B external referral). |
| **`PriorityService`** | `app/Services/PriorityService.php` | Computes dynamic numerical priority score based on risk weights, sensitivity proximity, and situational flags. |
| **`ReferralService`** | `app/Services/ReferralService.php` | Generates unique `REF-YYYY-NNNN` referral records, tracks follow-up dates, formats printable letters, and manages agency communication logs. |
| **`ProofPhotoService`** | `app/Services/ProofPhotoService.php` | Validates, optimizes, and stores multiple resolution photos (up to 5 images, max 5 MB each). |
| **`DuplicateService`** | `app/Services/DuplicateService.php` | Performs Haversine distance calculations (100–150m) and text similarity comparisons against open reports. |
| **`HotspotService`** | `app/Services/HotspotService.php` | Groups coordinates into geospatial grid clusters to identify high-density incident locations. |
| **`NotificationService`** | `app/Services/NotificationService.php` | Wraps mail dispatches in robust exception handling and logs delivery status to `notification_logs`. |
| **`SettingsService`** | `app/Services/SettingsService.php` | Dynamic typed configuration store, cached key lookups, and setup placeholder confirmation auditing. |
| **`FiscalYearService`** | `app/Services/FiscalYearService.php` | Fiscal year lifecycle, active year resolution, boundary enforcement, and annual transition management. |
| **`FiscalYearContext`** | `app/Services/FiscalYearContext.php` | Session-scoped viewing year context for multi-year review, archival auditing, and global filters. |
| **`FiscalYearGuard`** | `app/Services/FiscalYearGuard.php` | Security gate preventing mutations on records belonging to locked/closed fiscal years. |
| **`NumberingService`** | `app/Services/NumberingService.php` | Format mask compiler (tokens `{YEAR}`, `{YY}`, `{MM}`, `{SEQ:n}`; default case format `{MM}-{SEQ:3}-{YEAR}`) and atomic database-locked sequence generator. |
| **`PermissionService`** | `app/Services/PermissionService.php` | Granular permission registry, user syncing, and role preset application (`staff_full`, `case_encoder`, etc.). |
| **`AuditLogger`** | `app/Services/AuditLogger.php` | Centralized audit logger recording administrative overrides, setting adjustments, and security events. |
| **`PersonnelService`** | `app/Services/PersonnelService.php` | Official roster management, term tracking, signatory eligibility, and deactivation safeguards. |
| **`SignatoryResolver`** | `app/Services/SignatoryResolver.php` | Document signatory resolution mapping document types to active position holders. |
| **`CaseStatusService`** | `app/Services/CaseStatusService.php` | State transition machine for Katarungang Pambarangay dispute conciliation cases. |
| **`SummonsCounterService`** | `app/Services/SummonsCounterService.php` | Counts non-voided summons per case for display only. No threshold is enforced: the barangay counts days, not summons (Secretary Q20). Deadlines are handled by `OverdueService`. |
| **`OverdueService`** | `app/Services/OverdueService.php` | SLA calculation identifying overdue reports and pending external referrals, plus case mediation and conciliation deadlines in working days (`App\Support\WorkingDays`). |
| **`GlobalSearchService`** | `app/Services/GlobalSearchService.php` | Cross-entity universal search indexing reports, cases, parties, and referrals. |
| **`PartyMatchingService`** | `app/Services/Cases/PartyMatchingService.php` | Duplicate party detection and merge assistance. |
| **`LegacyImportService`** | `app/Services/Imports/LegacyImportService.php` | Chunked CSV/Excel legacy file ingestion with validation and rollback. |

---

## 3. Fiscal Year Context, Guard & Numbering Pipeline

### Fiscal Year Context & Guard
The application enforces strict annual fiscal year isolation:
- **`FiscalYearService::forNewRecord()`**: Attaches the currently active fiscal year to every new report, case, or referral.
- **`FiscalYearContext`**: Allows staff to switch viewing context via the `<x-year-switcher />` component without altering the default active year.
- **`FiscalYearGuard::assertWritable()`**: Rejects edits to finalized records in locked fiscal years, throwing `FiscalYearLocked`.

### Atomic Numbering Pipeline
Numbering sequences are tracked in `number_sequences` with a composite unique key `['fiscal_year_id', 'type']`:
1. `NumberingService::next($type, $fiscalYear)` parses format masks (e.g. `{MM}-{SEQ:3}-{YEAR}` for cases and blotter entries, `REF-{YEAR}-{SEQ:4}`, `SUM-{YEAR}-{SEQ:4}`).
2. Uses `lockForUpdate()` within an atomic database transaction.
3. Automatically rolls sequence numbering back to 1 when a new fiscal year starts.

---

## 4. Role, Permission & Preset Architecture

Security is layered across three tiers:
1. **Roles**: Coarse access tier governed strictly by `users.role` with three allowed values: `admin`, `staff`, and `agency` (`personnel` is NOT a role; it is an assignable directory of people).
2. **Granular Permissions**: Exactly **20 permission slugs** are registered and seeded in current code (with **1 planned slug**, `reports.log_phone`, bringing the planned total to **21 permission slugs**):
   - `settings.manage`, `fiscal_year.manage`, `templates.manage`, `backups.manage`
   - `personnel.manage`, `users.manage`
   - `reports.view`, `reports_center.view`, `reports_center.export`
   - `cases.view`, `cases.view_confidential`, `cases.create`, `cases.edit`, `cases.summons`, `cases.escalate`
   - `agencies.manage`, `referrals.manage`, `imports.manage`
   - `dashboard.view`, `audit.view`
   - *(Planned)* `reports.log_phone`: Allows desk officers to record and transcribe telephone complaints at the barangay hotline.
3. **Role Presets**: Pre-configured permission bundles defined in `config/permission_presets.php`:
   - `staff_full`: Complete staff operational rights across cases, reports, and referrals without confidential case access or system backups (current: 16 permissions; planned: 17 permissions with `reports.log_phone`).
   - `case_encoder`: Lupon clerk role restricted to dispute case docketing and summons issuance for non-confidential cases (5 permissions).
   - `reports_analyst`: Read-only reporting and dashboard analytics access (3 permissions).
   - `case_reviewer`: Review of all dispute cases including confidential cases, reports queue, and Reports Center (6 permissions).
4. **Admin Exemption**: Users with role `admin` implicitly satisfy all permission checks via `User::hasPermission()`. Positions do not grant permissions on their own.

---

## 5. Storage Disks & Trust Boundaries

The system enforces strict disk isolation to protect sensitive citizen data and official documents:

| Storage Disk | Physical Path | Access Rules | Stored Assets |
| :--- | :--- | :--- | :--- |
| **`public`** | `storage/app/public` | Public web access via symlink | Non-emergency resident incident photos, resolution proofs. |
| **`private`** | `storage/app/private` | Authenticated & authorized controller streaming only | Partner agency ID/letter proofs, referral case dossier attachments, official digital signatures (`personnel.signature_path`), database backup archives (`backups/`), and voice report audio recordings (`audio/`, planned). |

### Private Audio Flow & Retention (Planned Prototype)
- **Audio Storage**: Voice recordings are saved to `storage/app/private/audio/` with randomized UUID tokens (`report_audios.token`).
- **Streaming Controller**: Authorized staff stream audio via `GET /admin/reports/{report}/audio` (supports HTTP Range requests for playback scrubbing; responds with `Cache-Control: private, no-store`). Voice audio is strictly omitted from public views, APIs, emails, and resident tracking pages.
- **Scheduled Retention Purge**: Scheduled command `app:purge-audio` executes on a daily cron:
  - Deletes audio files whose `created_at` exceeds `voice.audio_retention_days` (default 90 days).
  - Deletes unattached audio recordings older than 24 hours (orphaned uploads where the resident abandoned the form).
  - Retains the text transcript, language metadata, verification history, and report record permanently.
  - Logs all purge and deletion events to `audit_logs`.
- **Backup Ingestion**: Private audio assets are included in scheduled archive snapshots (`app:backup`).

---

## 6. Navigation, Seeder & Audit Architecture

### Config-Driven Navigation
Navigation is declared centrally in `config/navigation.php`. The administrative layout (`layouts/admin.blade.php`) dynamically filters menu items and badge counts against:
- User role (`admin` vs `staff`)
- User permissions via `hasPermission()`
- Master feature flags (`config('features.cases_enabled')`)
- Partner agencies use an isolated minimal layout (`layouts/agency.blade.php`) with access restricted strictly to their assigned referrals and account profile.

### Isolated Seeder Architecture
The seeder pipeline is cleanly partitioned:
- **`RequiredDataSeeder` (Production Reference)**: Idempotent seeder run by `DatabaseSeeder` and `php artisan app:empty`. Seeds baseline reference records (Puroks 1–7, Categories, Offices, Routing Rules, Emergency Contacts, Response Templates, Settings Placeholders, Priority Weights, Case Types, Escalation Destinations). It creates **0 users**, **0 reports**, and **0 cases**.
- **`DemoDataSeeder` (Development & Evaluation)**: Executed via `php artisan app:demo`. Sets up active fiscal year 2026, advances `number_sequences` to prevent collision, seeds canonical demo accounts (`admin@`, `secretary@`, `staff@`, `clerk@`, `reviewer@`, `cswdo@`), and invokes `DemoReportSeeder`.

### Audit Logging & Security Tracing
Every administrative override of an AI recommendation, setting alteration, fiscal year status change, and confidential case view is recorded in `audit_logs` via `App\Services\AuditLogger`. View access to confidential cases is throttled in cache (max 1 audit event per user/case per hour) to prevent audit log flooding while maintaining complete security traceability.

---

## 5. End-to-End Data Flow

```mermaid
sequenceDiagram
    autonumber
    actor Resident
    participant App as Laravel Application
    participant Gemini as Google Gemini API
    actor Staff
    actor ExternalAgency as External Agency

    Resident->>App: Submits report (Category, Description, GPS/Pin, Photos)
    App-->>Resident: Returns unique tracking code (BRG-XXXXXX)
    Note over App: Status = submitted

    App->>Gemini: Async triage request (No reporter PII sent)
    alt Gemini Responds Successfully
        Gemini-->>App: Category, urgency, summary, emergency flag, photo audit
        App->>App: Save to report_ai_results (ai_status = done)
    else Gemini Fails or Times Out (30s)
        App->>App: Mark ai_status = failed (report remains intact for manual triage)
    end

    Staff->>App: Opens review page (/admin/reports/{id})
    Note over Staff: Reviews 3-way comparison (Resident vs AI vs Final)
    
    alt Mode A: Within Barangay Authority
        Staff->>App: Assigns internal office & SLA, clicks "Confirm & Route"
        App->>App: Transition to routed -> in_progress
    else Mode B: Beyond Barangay Authority
        Staff->>App: Prepares referral letter (REF-YYYY-NNNN)
        App->>ExternalAgency: Logs referral and tracks follow-up date
        App->>App: Transition to referred
    else Non-Actionable / False Alarm
        Staff->>App: Selects reason code (e.g. invalid_report)
        App->>App: Transition to no_action (Mandatory explanation)
    end

    Staff->>App: Uploads proof photos & notes when work complete
    App->>App: Transition to awaiting_confirmation
    Resident->>App: Views proof on tracking page, clicks "Confirm Fixed"
    App->>App: Transition to resolved (Resident submits 1-5 rating)
```

---

## 6. Trust Boundaries & AI Operational Limits

1. **Strict Advisory Mandate**: The AI is completely isolated from binding state modifications. It has zero authority to execute database writes to final columns on `reports`, transition statuses, merge duplicates, or close reports.
2. **Human-in-the-Loop Review Gate**: Staff must explicitly review and confirm category and urgency before routing an incident. AI values are rendered with clear visual badges (`AI Suggested`, `Matches`, `Differs`).
3. **Emergency Containment**: The AI cannot contact emergency services (police, fire, CDRRMO). It only sets an advisory `emergency_flagged` status, which triggers on-screen 911 dialing guidance for the resident and sends priority alerts to local officials.
4. **Voice Transcription Boundaries (Planned Prototype)**:
   - Voice audio is transmitted to Gemini strictly as untrusted data. The prompt instructs the model never to execute spoken instructions, change routing commands, or repeat personal identifiable names.
   - **Key Rule**: **If the audio is uncertain or the AI cannot understand it, nothing is guessed and the decision goes to staff.**
   - When transcription produces `needs_verification` or `failed`, no guessed text is inserted into the report description. Guessed/partial text is held strictly as an unverified draft, and forward state transitions ("Confirm and route") are blocked until an authorized staff member listens to the recording and confirms or inputs the verified text.

---

## 7. Security & Network Architecture

- **Session Authentication**: Staff and Administrator access is guarded by Laravel's built-in session authentication and `role` middleware (`EnsureRole:staff` or `EnsureRole:admin`).
- **Throttling & Abuse Prevention**:
  - Public submission routes are rate-limited (`throttle:10,1`).
  - Staff login endpoint is throttled (`throttle:5,1`).
- **CSRF & Input Sanitization**: Every HTML form includes `@csrf` verification tokens. All rich text and descriptions are escaped before rendering in Blade templates.
- **File Upload Hardening**: Uploaded photos are verified by MIME type (`image/jpeg`, `image/png`, `image/webp`), sanitized on write, and stored in isolated storage disks (`storage/app/public/reports/`).
