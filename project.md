# AI Coding Assistant Prompt: Phases 1–7 Implementation Checklist

## Context
- **Project:** Barangay Problem Reporting and Management System (Laravel 12, MySQL, Leaflet, Font Awesome)
- **Client:** Barangay Calarian, Zamboanga City
- **Current State:** Phase 1 built but unverified. Phases 2–7 not started.
- **Goal:** Verify Phase 1, then build Phases 2–7 sequentially. Use **Font Awesome** for all icons. Use **Mailtrap** or **log driver** for email testing.
- **Key Principle:** AI suggests, rules route, officials decide. No auto-merge, no auto-routing.

---

## Phase 1: Resident Reporting Form & Tracking (Layer 1 Baseline)
**Status:** Built. Needs verification and hardening before Phase 2.

- [ ] **1.1 Reports Table & Model**
  - Migration `create_reports_table`: `tracking_code` (unique, 8–10 chars), `description` (text), `photo_path` (nullable), `latitude`, `longitude` (nullable decimals), `purok` (nullable), `landmark` (nullable), `name` (nullable), `email` (nullable), `phone` (nullable), `status` (enum default `Received`), `is_emergency` (bool default false), `timestamps`.
  - Add AI placeholder columns now so Phase 3 needs no schema change: `problem_type`, `summary`, `ai_urgency`, `ai_status` (enum: `pending`, `completed`, `failed`, `unclassified`).
  - `Report` model: `$fillable` covers all above; cast `is_emergency` to boolean, lat/lng to float.
- [ ] **1.2 Tracking Code Generation**
  - Generate on create via model `booted()` or a `TrackingCode::generate()` helper.
  - Must be collision-safe: loop until unique; exclude ambiguous characters (`0/O`, `1/I/l`).
  - Display the code prominently on the success page with a copy button (Font Awesome `fa-regular fa-copy`).
- [ ] **1.3 Report Submission Controller**
  - `ReportController@store`: validate, store photo to `storage/app/public/reports`, save report, redirect to success page.
  - Validation rules:
    - `description`: required, min 10, max 2000.
    - `photo`: nullable, image, max 5MB, `mimes:jpg,jpeg,png,webp`.
    - `latitude`/`longitude`: nullable, numeric, between bounds for Zamboanga City (approx. lat 6.8–7.3, lng 121.9–122.3).
    - `email`: nullable, email format.
    - `purok`: nullable, exists in `puroks` lookup (or in a config array of purok names).
  - Rate limit the POST route (e.g. `throttle:10,60`) to curb spam (Feature 9 mentions rate limiting).
- [ ] **1.4 Resident Reporting Form (Feature 1)**
  - View: `resources/views/reports/create.blade.php`, mobile-first.
  - Fields: description textarea, photo upload with preview, name/email/phone (optional), purok dropdown, landmark text.
  - **Location capture:** "Use my location" button calling `navigator.geolocation.getCurrentPosition()`. On success, drop a Leaflet pin the resident can drag to correct. Fallback: manual pin placement by map click, or just pick purok/landmark.
  - Show loading/error states for geolocation denial (permission blocked, unavailable).
  - Store final lat/lng in hidden inputs on form submit.
- [ ] **1.5 Emergency Notice Before Submit (Feature 1, Feature 15 UI)**
  - Non-dismissible reminder block above the submit button: "This system is NOT monitored in real time. For emergencies call 911."
  - Render barangay emergency contacts from a config/DB list (police, fire, ambulance/health office, disaster response, barangay hotline). Mark placeholders `(add number)` until the barangay supplies real numbers.
  - Font Awesome `fa-solid fa-triangle-exclamation` icon in red.
- [ ] **1.6 Success Page**
  - View: `resources/views/reports/success.blade.php`.
  - Show tracking code large and centered, instructions to save it, link to status check, and repeat the emergency notice.
- [ ] **1.7 Status Tracking by Code (Feature 2)**
  - Route: `GET /track` (form) and `GET /track/{code}` (result). No auth.
  - Look up by `tracking_code`; show friendly not-found message if missing.
  - Timeline view of `StatusLog` entries: status label, date/time, public-facing note only (hide internal staff notes).
  - Throttle this route too (`throttle:30,1`) to prevent code guessing/enumeration.
- [ ] **1.8 StatusLog Table**
  - Migration: `report_id`, `user_id` (nullable — Phase 1 changes have no user), `from_status`, `to_status`, `notes` (nullable), `is_public` (bool default true), `timestamps`.
  - Insert an initial `Received` row when a report is created so the timeline is never empty.
- [ ] **1.9 Purok Lookup**
  - Seed a `puroks` table (or `config/barangay.php` array) with Barangay Calarian purok names so the dropdown is data-driven instead of hardcoded in Blade.
- [ ] **1.10 Public Layout & Styling**
  - `resources/views/layouts/app.blade.php`: navy/gold/red theme, responsive nav, Font Awesome loaded locally via `npm install @fortawesome/fontawesome-free` (works offline during demo).
  - Flash messages component for validation errors and success alerts.
- [ ] **1.11 Seeder & Test Data**
  - `ReportSeeder` creating 5–8 realistic reports across different puroks, statuses, and problem types, each with a `StatusLog` trail.
  - Use coordinates spread across Zamboanga City so the Phase 6 map has visible clusters.
  - Include at least one report per category (road damage, garbage, drainage, flooding, streetlight) and one with no photo.
- [ ] **1.12 Verification Checklist**
  - [ ] Submit a report with photo + auto-detected location → tracking code returned.
  - [ ] Submit with geolocation denied → manual pin/purok still works.
  - [ ] Oversized photo (>5MB) rejected with clear error.
  - [ ] Duplicate tracking codes impossible after 50 rapid submissions.
  - [ ] `/track/{code}` shows correct timeline; wrong code shows friendly not-found.
  - [ ] Photo retrievable via `php artisan storage:link` at `/storage/reports/...`.
  - [ ] `php artisan route:list` shows only public routes (no admin routes yet).
  - [ ] Mobile viewport (375px) usable without horizontal scroll.

---

## Phase 2: Staff Login, Report Queue, Status Changes
**Status:** Not started. Requires Phase 1 verified.

- [ ] **2.1 User Roles & Migration**
  - Add `role` column to `users` table (`staff`, `admin`).
  - Update `User` model fillable and add `role` field.
  - Update seeder to create one `admin` user and two `staff` users.
  - Delete the default `User::factory()->create([...])` line in `DatabaseSeeder` to avoid duplicate-entry errors on re-seed.
- [ ] **2.2 Role Middleware**
  - Create `EnsureRole` middleware.
  - Register in `bootstrap/app.php`.
  - Protect `/admin/*` routes.
- [ ] **2.3 Staff Login**
  - Create `AuthController` for staff login (separate from resident flow).
  - View: `resources/views/auth/login.blade.php` (modern design, Font Awesome icons).
  - Route: `/login` (must be named `login`, since Laravel redirects guests there), throttle 5/min.
- [ ] **2.4 Report Model Relations**
  - `Report` model: `hasMany(StatusLog)`, `belongsTo(User)` (assigned_to).
  - `StatusLog` model: `belongsTo(User)` (changed_by), uses `notes` field from Phase 1.8.
- [ ] **2.5 Staff Review Queue (Feature 4)**
  - Controller: `Admin/ReportController@index`
  - View: `resources/views/admin/reports/index.blade.php`
  - Priority-sorted queue (newest first, emergency flagged first).
  - Show: description, photo thumbnail, AI status (pending), current status, tracking code.
  - Add Font Awesome icons for actions (edit, view, assign).
- [ ] **2.6 Report Detail & Status Change (Feature 4)**
  - View: `resources/views/admin/reports/show.blade.php`
  - Display original text, photo, AI output (if available), timeline of status logs.
  - Action buttons: Confirm, Reject, Assign, Merge (disabled until Phase 5), Refer.
  - Status dropdown: `Received` → `Under Review` → `Routed` → `In Progress` → `Resolved` → `Referred` → `Closed`.
  - On status change: create `StatusLog` entry with user, timestamp, optional notes, and set `is_public` appropriately.
- [ ] **2.7 Navigation Layout**
  - `resources/views/layouts/admin.blade.php` with Font Awesome sidebar/nav.
  - Add "Staff Login" link to resident layout.
- [ ] **2.8 Status Badge Component**
  - `resources/views/components/status-badge.blade.php` mapping each status to a color and Font Awesome icon.

---

## Phase 3: AI Extraction (Gemini)
**Status:** Not started.

- [ ] **3.1 Gemini API Integration**
  - Install `google/apiclient` via Composer.
  - Add `GEMINI_API_KEY` to `.env`.
  - Create `Services/GeminiService.php` with methods: `extract()`, `analyzePhoto()`, `summarize()`.
- [ ] **3.2 AI Report Understanding (Feature 10)**
  - Prompt: Extract `problem_type`, `purok/landmark`, `key_facts` from text/transcript.
  - Accept English, Filipino, or a mix of both.
  - Validate against allowed categories. If unclear, mark `ai_status = 'unclassified'`.
- [ ] **3.3 AI Photo Analysis (Feature 11)**
  - Send photo to Gemini Vision API along with the description.
  - Return: `description`, `suggested_type`, `mismatch_flag` (if text/photo conflict).
  - Handle missing photo or API failure gracefully (continue without analysis).
- [ ] **3.4 Report Summarization (Feature 12)**
  - Generate 1–2 sentence summary for staff queue scanning.
  - Already stored in `reports.summary` (column added in Phase 1.1).
- [ ] **3.5 Urgency Estimation (Feature 13)**
  - AI suggests `urgency` (Low, Medium, High, Critical) into `reports.ai_urgency`.
  - **Note:** Final priority score is calculated in Phase 4. Staff can override anytime.
- [ ] **3.6 Trigger AI on Submission**
  - After report creation (Phase 1), dispatch a job to run Gemini extraction asynchronously.
  - Update `reports.ai_status`: `pending` → `completed` or `failed`.
  - Run with `php artisan queue:work`; queue driver already set up in E.1.
- [ ] **3.7 Show AI Output in Queue**
  - In the detail view, place AI output beside the original text and photo, clearly labeled as a suggestion.

---

## Phase 4: Routing & Jurisdiction
**Status:** Not started.

- [ ] **4.1 Rule-Based Routing Engine (Feature 5)**
  - Create `RoutingRule` model/table: `problem_type`, `purok`, `jurisdiction`, `assigned_office`, `weight`.
  - Create `RoutingService.php` to match rules and return recommended office.
  - In Report Detail view, show "Recommended Office" with option to confirm or change.
- [ ] **4.2 Priority Scoring (Feature 13)**
  - Combine `ai_urgency` with rule-based factors: nearby sensitive places, problem risk, number of supporting reports, report age.
  - Administrator sets factor weights in a settings table; score must be transparent (show the breakdown).
  - Staff can change final urgency at any time; log the override.
- [ ] **4.3 Jurisdiction & Referral Handling (Feature 6)**
  - Create `Jurisdiction` model/table: `office_name`, `scope` (barangay/city/province), `contact_info`.
  - If report falls outside barangay authority, flag as `referral_needed`.
  - Generate referral text; log in `reports.referral_notes`.
  - Status changes to `Referred` when confirmed by staff; referral logged and tracked so residents need not file again.
- [ ] **4.4 Admin UI for Rules & Jurisdictions**
  - CRUD views for `RoutingRule` and `Jurisdiction` (Admin only).
  - Font Awesome icons for add/edit/delete actions.

---

## Phase 5: Duplicate Detection & Emergency Handling
**Status:** Not started.

- [ ] **5.1 Duplicate Detection (Feature 14)**
  - Compare new reports with open reports using:
    - Text similarity (cosine similarity or simple Levenshtein).
    - Location distance (Haversine formula).
    - Problem type match.
    - Time window (e.g., last 7 days).
  - If similarity > threshold, show "Possible Duplicate" warning with merge button.
  - **Staff must confirm merge.** No auto-merge.
  - On merge: combine notes, keep earliest creation date, update tracking code, log the merge in `StatusLog`.
- [ ] **5.2 Emergency & Sensitive Handling (Feature 15)**
  - Gemini prompt includes emergency/crime detection (murder, shooting, dead body, etc.) in English, Filipino, or Taglish.
  - If flagged:
    - Set `reports.is_emergency = true`.
    - Stop normal routing and assign no priority score.
    - Hide from public map; limit visibility to authorized staff.
    - Alert Barangay Captain/tanod (dashboard + email).
    - Show resident warning: "Call 911 or the local police. System not monitored in real time," plus the barangay emergency contact list.
    - Place report at top of staff queue.
    - Staff confirm flag, advise resident, or refer to police; close as `Out of Scope` with recorded explanation.
  - The AI only flags; it never contacts police or handles the case.
- [ ] **5.3 Emergency Dashboard Alert**
  - Add red alert banner on admin dashboard for emergency reports.

---

## Phase 6: GIS Map & Trend Analysis
**Status:** Not started.

- [ ] **6.1 GIS Problem Map (Feature 7)**
  - Install `leaflet` via NPM.
  - Create `resources/views/admin/map.blade.php`.
  - Load reports as markers colored by urgency or problem type.
  - Filters: problem type, purok, status, date range.
  - Residents see limited public map (no personal details, no emergency-flagged reports).
- [ ] **6.2 Backend Geo Endpoint**
  - Route returning filtered reports as GeoJSON.
  - Exclude emergency reports from the public endpoint entirely.
- [ ] **6.3 Problem Area & Trend Analysis (Feature 16)**
  - Use Leaflet.markercluster or simple clustering to identify problem areas.
  - Add chart (Chart.js) showing problems over time (bar/line chart).
  - Group by purok and problem type.

---

## Phase 7: Dashboard, Reports & Insights
**Status:** Not started.

- [ ] **7.1 Operations Dashboard (Feature 8)**
  - View: `resources/views/admin/dashboard.blade.php`.
  - Key figures: Total reports, Pending, In Progress, Resolved, Referred.
  - Chart: Problems by type, by purok, by status. Embed the problem map.
  - Recent activity feed from `StatusLog`.
- [ ] **7.2 Dashboard Insights (Feature 17)**
  - Send aggregated, non-personal data to Gemini for short insights.
  - Display in dashboard sidebar. Never send names, emails, or phone numbers.
- [ ] **7.3 Reports & Analytics (Feature 8)**
  - Generate PDF/CSV reports: cases by type, purok, status, period.
  - Average time to resolution.
  - Count of reports awaiting action.
- [ ] **7.4 User Management (Feature 9)**
  - Admin can create/edit/delete staff and admin users and assign roles.
  - Replace the demo-only passwords from Phase 2.
- [ ] **7.5 Audit Trail Completion (Feature 9)**
  - Ensure every status change and staff override is queryable and exportable.

---

## Email Notifications (Add-on for Phases 2–7)
**Status:** Not started.

- [ ] **E.1 Email Configuration**
  - Set `MAIL_MAILER=log` for local testing or `MAIL_MAILER=smtp` with Mailtrap credentials.
  - Set `QUEUE_CONNECTION=database` in `.env`.
  - Create the queue tables: `php artisan queue:table` then `php artisan migrate`.
- [ ] **E.2 Mailable Classes**
  - Create `StatusChangedNotification` mailable:
    - Pass `report` and `newStatus` via the constructor.
    - Implement `ShouldQueue` interface for background sending.
    - Use `afterCommit()` to ensure the status has been persisted before the mailer fires.
  - Create view: `resources/views/emails/status-changed.blade.php` with Calarian branding, navy header, and a prominent tracking code.
- [ ] **E.3 Trigger on Status Change**
  - In the `ReportController`, after the `StatusLog` is successfully created, dispatch the email:
    ```php
    if ($report->email) {
        Mail::to($report->email)->queue(new StatusChangedNotification($report, $newStatus));
    }
    ```
  - For phone reports (where no email exists), the tracking code is given verbally by staff; email is skipped.
- [ ] **E.4 Testing**
  - Set `MAIL_MAILER=log` and verify `storage/logs/laravel.log` contains the full HTML email body.
  - Set `MAIL_MAILER=smtp` with Mailtrap credentials and verify the email lands in the Mailtrap inbox.
  - Confirm `php artisan queue:work` processes the email jobs without errors.

---

## General Requirements for All Phases
- Use **Font Awesome** for all icons (import via CDN or `@fortawesome/fontawesome-free` installed via NPM).
- Modern, clean UI with Calarian colors (navy, gold, red).
- Responsive mobile-first designs.
- All AI outputs are suggestions; staff must confirm every action (no auto-merge, no auto-routing).
- Audit trail: every status change, override, and merge is logged in `StatusLog` with `changed_by` user and timestamp.
- Rate limiting on staff login (5 attempts/min) and public submission (10 attempts/min).
- Data Privacy: call recordings (if added later) kept only for a defined retention period; personal data hidden on public map.

