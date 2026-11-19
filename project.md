# Barangay Problem Reporting and Management System (PROJECT.md)

AI Classification, Prioritization, Jurisdiction-Based Routing, and Map-Based Monitoring for Local Governance.

**Client**: Barangay Calarian, Zamboanga City  
**Development Team**: Group 3 (SynohDev), Western Mindanao State University (Software Engineering 1)  
**System Classification**: Municipal GovTech Civic Issue Management System  

---

## 1. Overview & Core Purpose

The **Barangay Problem Reporting and Management System** bridges communication between local residents and barangay officials in Barangay Calarian, Zamboanga City. It addresses inefficiencies in traditional manual incident reporting (in-person walk-ins, phone calls, scattered social media messages) by offering a transparent, structured digital platform.

### Primary Objectives
- **Frictionless Public Incident Submission**: Residents can submit issues (drainage, garbage, road damage, streetlights, flooding, etc.) with geo-tagging, landmark references, and photo evidence without requiring an account or complex verification steps.
- **AI-Assisted Staff Triage**: Integration with Google Gemini analyzes report text and photos in the background to suggest categories, urgency ratings, executive summaries, duplicate alerts, and photo-mismatch warnings.
- **Strict Human-in-the-Loop Governance**: AI outputs remain strictly advisory. Barangay officials and staff make all binding routing, merging, assignment, and resolution decisions.
- **Clear Jurisdiction Boundary Handling**: The system enforces jurisdiction boundaries through rules. Issues within barangay authority are routed to internal committees; matters beyond local jurisdiction (e.g., national highways, major water utilities) are routed via formal referral letters to external agencies.
- **Data Privacy & Emergency Compliance (RA 10173)**: Reporter identity is optional and isolated from public views and external AI prompts. Immediate emergencies trigger prominent hotline alerts (911) and are excluded from public maps to protect citizen safety.

---

## 2. System Architecture & Data Flow

### Architecture Style
The system is built as a **Modular Laravel Monolith** using the Model-View-Controller (MVC) pattern paired with a dedicated Service Layer for domain operations. Server-side Blade templates handle presentation, Vite bundles assets, and Leaflet.js / Chart.js deliver interactive mapping and analytics.

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
       |                       Service Layer                         |
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
       |                 Data Storage & External APIs                |
       |  - Database: SQLite (Dev/Test) / MySQL (Production Target)  |
       |  - External AI: Google Gemini REST API (gemini-2.5-flash)   |
       |  - Maps: OpenStreetMap Tile Servers (via Leaflet.js)         |
       |  - Mail / SMS: Laravel Mail & Notification Logs             |
       +-------------------------------------------------------------+
```

### Request & Data Lifecycle
1. **Submission**: Resident fills out the report card at `/`. JavaScript compresses attached images client-side before sending. The report is saved with a unique tracking code (e.g., `BRG-2026-XXXXXX`) in status `submitted`.
2. **Background AI Processing**: `ReportSubmitted` event dispatches `GeminiService::analyze()`. Gemini extracts key facts, checks for photo/text mismatches, suggests priority/category, and writes to `report_ai_results`. (If Gemini is offline, the report remains fully functional in status `submitted`).
3. **Staff Review & Decision**: Staff view the report in the queue (`/admin/reports`). The review card presents a 3-way comparison: Resident Input vs. AI Suggestion vs. Staff Final Decision.
4. **Routing**:
   - **Mode A (Within Authority)**: Assigned to an internal barangay committee/staff member with an SLA deadline.
   - **Mode B (Beyond Authority)**: Generates a formal referral letter with a `REF-YYYY-NNNN` number and schedules follow-up checks.
5. **Resolution & Proof**: Personnel document work by uploading up to 5 proof photos and a resolution note (`ResolutionProof`). Status transitions to `awaiting_confirmation`.
6. **Resident Verification**: The resident tracks the code, reviews the proof photo, submits a 1–5 star rating, and either **Confirms Fixed** (transitions to `resolved`) or **Reopens Case** (re-enters staff queue with explanation).

---

## 3. Key Third-Party Integrations

- **Google Gemini API (`gemini-2.5-flash`)**: Powers automated categorization, urgency scoring, emergency flag detection, and photo mismatch analysis. Invoked strictly via Laravel's `Http` facade without sending reporter PII.
- **Leaflet.js (`^1.9.4`) & OpenStreetMap**: Enables interactive map pin dropping, purok boundary clamping to Barangay Calarian (`6.905, 122.060` to `6.940, 122.098`), duplicate radius checking (`300m`), and public map transparency.
- **Chart.js (`^4.6.1`)**: Drives administrative analytics dashboards for volume trends, category breakdowns, and SLA response rates.
- **Tailwind CSS (`^4.0.0`) via `@tailwindcss/vite`**: Custom utility styling coupled with design tokens (`--ink`, `--blue`, `--gold`, `--red`).
- **FontAwesome Free (`^7.3.1`)**: Iconography across resident and staff views.
- **Laravel Mail & Notifications**: Dispatches status updates, emergency Tanod/Captain alerts, and agency referral tracking notices.

---

## 4. Core Domain Models & Database Entities

| Model | Table | Purpose & Key Relationships |
|---|---|---|
| `Report` | `reports` | Primary domain aggregate. Stores tracking code, coordinates, status, urgency, priority score, reporter contact (staff-only), and answers JSON. Belongs to `Category`, `Purok`, `Office`, and `User` (assignee). Has many `StatusLog`, `ReportPhoto`, `ReportProofPhoto`, `InfoRequest`, `InternalNote`. |
| `Category` | `categories` | Report classification (Drainage, Garbage, Road Damage, Streetlight, Flooding, Other). Contains `risk_weight`, `default_office_id`, `sla_hours`, and dynamic `question_schema` JSON. |
| `Office` | `offices` | Target entity for assignments. Distinguishes internal barangay committees from external agencies (`is_internal` boolean). |
| `RoutingRule` | `routing_rules` | Rules engine mapping `category_id` and `office_id` to `within_authority` boolean and legal jurisdiction notes. |
| `Referral` | `referrals` | External agency referrals (`REF-YYYY-NNNN`). Tracks sent dates, follow-up dates (sent + 7 days), transmission channel, and outcome. Has many `ReferralUpdate`. |
| `ReportAiResult` | `report_ai_results` | Advisory AI audit trail. Stores suggested category, suggested urgency, key facts, photo mismatch flags, confidence score, raw Gemini JSON, and error logs. |
| `StatusLog` | `status_logs` | Immutable audit trail for every status transition. Records `user_id`, `from_status`, `to_status`, notes, attachments, and `visible_to_resident` flag. |
| `InfoRequest` | `info_requests` | Two-way communication thread between barangay staff and resident for clarifications without exposing staff identity. |
| `ResolutionProof` | `resolution_proofs` | Proof of completion containing staff notes and image proofs. |
| `Feedback` | `feedback` | Post-resolution resident satisfaction ratings (1–5 stars) and comments. |
| `EmergencyContact` | `emergency_contacts` | Directory of local emergency hotlines (Police, Fire, CDRRMO, Barangay Tanod). |
| `AuditLog` | `audit_logs` | System security audit trail capturing user ID, action, model, old values, and new values. |

### Report Lifecycle State Machine
```
[submitted] ──► [under_review] ──┬──► [emergency_flagged] (Hidden from public map)
                                 ├──► [needs_info] (Awaiting resident response)
                                 ├──► [out_of_scope] / [merged]
                                 ├──► [referred] (Mode B: External Agency)
                                 └──► [routed] (Mode A: Internal Office)
                                         │
                                         ▼
                                   [in_progress]
                                         │
                                         ▼
                             [awaiting_confirmation]
                                   │         │
                 Resident Confirms │         │ Resident Reopens
                                   ▼         ▼
                              [resolved]   [reopened] ──► [under_review]
```

---

## 5. Key Features & Entry Points

| Feature | Entry Point Route | Controller & View Path | Description |
|---|---|---|---|
| **Resident Report Form** | `GET /` <br> `POST /report` | `ReportController@create`, `@store` <br> `resources/views/resident/create.blade.php` | Single-card resident submission form with dynamic category questions, Leaflet map pinning, image upload/compression, and emergency safety trigger. |
| **Public Status Tracker** | `GET /track/{code}` <br> `POST /track` | `TrackController@show`, `@search` <br> `resources/views/resident/show.blade.php` | Live status progression timeline, staff clarification reply form, resolution confirmation, and 5-star rating. |
| **Public Transparency Map** | `GET /map` | `MapController@index` <br> `resources/views/resident/map.blade.php` | OpenStreetMap showing active public incidents (excluding emergency cases) to prevent duplicate reports. |
| **Nearby Reports API** | `GET /api/nearby-reports` | `routes/engine.php` | Haversine distance calculator returning existing issues within 300 meters of given coordinates. |
| **Staff Queue & Triage** | `GET /admin/reports` | `Admin\ReportController@index` <br> `resources/views/admin/reports/index.blade.php` | Queue organized by SLA urgency, category, status, and assignment. Supports bulk confirmation and CSV export. |
| **AI Comparison & Review** | `GET /admin/reports/{id}` <br> `MATCH /admin/reports/{id}/review` | `Admin\ReportController@show`, `@saveReview` <br> `resources/views/admin/reports/show.blade.php` | 3-column comparative view (Resident vs. AI vs. Final). Enforces jurisdiction Mode A / Mode B selection. |
| **Proof of Work Upload** | `POST /admin/reports/{id}/proof-photos` | `Admin\ReportController@storeProofPhotos` | Multi-photo upload for field teams to document completed work before proposing resolution. |
| **Agency Referral Letters** | `GET /admin/referrals/{id}/letter` | `Admin\ReferralController@letter` <br> `resources/views/admin/referrals/letter.blade.php` | Printable, formal endorsement letters for outside agencies with automated follow-up tracking. |
| **Admin Insights & Metrics** | `GET /admin/` (or `/admin/dashboard`) | `Admin\DashboardController@index` <br> `resources/views/admin/dashboard.blade.php` | High-level metrics for case volume, category distribution, SLA compliance, and Purok issue frequency. |
| **System Settings** | `GET /admin/settings` | `Admin\SettingsController@index` | Priority weight sliders, category schemas, office management, and emergency email configurations. |

---

## 6. Current State, Technical Debt & Roadmap

### What is Fully Implemented
- Complete end-to-end resident reporting lifecycle with dynamic questions and category sorting.
- Client-side image compression (HTML5 Canvas to JPEG) to respect upload thresholds.
- Dual-mode jurisdiction routing (Mode A internal dispatch vs. Mode B external agency referrals).
- Comprehensive status audit logging with resident-visible vs. internal-only segregation.
- Interactive resident resolution verification with 5-star feedback ratings.
- Fallback resiliency: if the Google Gemini API fails or times out, the manual review pipeline proceeds without interruption.

### Known Limitations & Technical Debt
1. **SMS Gateway Driver**: SMS notifications for Tanod alerts currently log or use dummy transports; full Twilio/Semaphore credentials must be hooked up for live cellular broadcasts.
2. **PostGIS Migration**: Geospatial queries currently use the PHP Haversine formula (`/api/nearby-reports`). As report volume expands, migrating from SQLite/MySQL to PostgreSQL + PostGIS will allow native spatial indexing (`ST_DWithin`).
3. **Queue Worker in Production**: Queue defaults to `sync` in development; production deployment requires running `php artisan queue:work` under Supervisor for asynchronous Gemini calls.
4. **Offline PWA Support**: Residents in remote Purok pockets with weak mobile data would benefit from a ServiceWorker to cache draft reports until an internet connection is re-established.

### Logical Next Steps
- Implement Push Notifications / Webhooks for real-time staff browser alert pings.
- Add multi-language localization dictionary expansions (Chavacano, Bisaya) in `resources/lang`.
- Add automated SLA escalation jobs triggered via Laravel Scheduler (`php artisan schedule:run`) for overdue reports.
