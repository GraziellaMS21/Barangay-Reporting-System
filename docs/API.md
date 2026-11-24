# API Contracts & Routing Reference

Reference documentation for web endpoints, RESTful parameters, authentication middleware, and payload schemas in **Barangay-Reporting-System-1**.

> [!NOTE]
> All endpoints documented in Sections 1 through 13 reflect active routes registered in the application (273 routes total, snapshotted in `docs/ROUTES.snapshot.json`).
> Planned endpoints documented in Section 14 reflect upcoming features (WS3/WS4/Resident Complaint Intake) and are **not** yet registered in code or in the snapshot.
> To regenerate the route snapshot from the live application routing table when new routes are implemented, run:
> ```sh
> php artisan route:list --json > docs/ROUTES.snapshot.json
> ```

---

## 1. Route Partitions & Middleware Groups

Routing is modularized under `routes/` and registered via `routes/web.php`:
- `routes/resident.php`: Guest resident submissions, tracking, and resolution feedback.
- `routes/engine.php`: API utilities (e.g. nearby reports Haversine calculation).
- `routes/staff.php`: Authenticated staff queue, review cards, assignments, and updates.
- `routes/admin.php`: Administrative settings, user management, and security audit logs.
- `routes/setup.php`: Onboarding setup wizard (WS1).
- `routes/settings.php`: Dynamic settings, fiscal years, templates, backups, and search (WS1).
- `routes/people.php`: Personnel, positions, signatories, and user access (WS2).
- `routes/reports_center.php`: Reports catalog, parameter filters, and exports (WS2).
- `routes/cases.php`: Dispute intake, blotter entries, party profiles, and duplicates (WS3).
- `routes/case_actions.php`: Summons, conciliation hearings, deadlines, and CFA escalations (WS4).
- `routes/agency.php`: Outside agency registrations, portal inbox, and referral updates (WS5).
- `routes/imports.php`: Historical Excel/CSV bulk import wizard (WS6, Stretch).

| Middleware | Target Audience | Behavior on Unauthenticated Access |
| :--- | :--- | :--- |
| `web` (default) | Public / Resident | Open guest access; CSRF validation on POST. |
| `auth` | Staff / Admin / Agency | Redirects unauthenticated requests to `/login`. |
| `role:staff` | Staff / Admin | Permits both `staff` and `admin` roles; rejects others with HTTP 403. |
| `role:admin` | Admin Only | Restricts to `admin` role (Barangay Captain/System Admin); rejects staff with HTTP 403. |
| `role:agency` | Agency Portal | Restricts to `agency` role; requires `agency.active` and verified email. |
| `permission:{slug}` | Granular Access | Enforces specific permission slug from `permissions` table; rejects with HTTP 403. |

---

## 2. Public / Resident Routes (`routes/resident.php`)

### `GET /`
- **Controller**: `ReportController@create`
- **Status**: Built (Entry screen update Planned, [DECIDED, D2])
- **Description**: Renders the resident entry interface featuring three prominent action choices: Emergency, Report a Community Problem, and File a Complaint. Clicking "Report a Community Problem" reveals the single-card community report form with wrong-door guidance notice ([DECIDED, D6]).

### `POST /report`
- **Controller**: `ReportController@store`
- **Status**: Built (Guest & Anonymous additions Planned, [DECIDED, D7])
- **Middleware**: `throttle:10,1` (Planned guest mode: `throttle:5,1` + honeypot field)
- **Payload**:
  - `category_id` (integer, nullable per Decision 80, exists in `categories`)
  - `description` (string, required, min: 10, max: 2000)
  - `lat` (numeric, nullable)
  - `lng` (numeric, nullable)
  - `purok_id` (integer, nullable, exists in `puroks`)
  - `landmark` (string, nullable, max: 255)
  - `people_at_risk` (boolean, nullable) — Safety toggle
  - `is_ongoing` (boolean, nullable)
  - `is_recurring` (boolean, nullable)
  - `answers` (array, nullable) — Dynamic question responses
  - `photos[]` (array of image files, optional, max: 5 files, 5 MB total)
  - `reporter_name` (string, nullable; optional for guests, omitted if anonymous)
  - `reporter_contact` (string, nullable; optional for guests, omitted if anonymous)
  - `reporter_email` (email, nullable; optional for guests, omitted if anonymous)
  - `is_anonymous` (boolean, nullable; when true, discards contact details and stores no IP)
  - `consent` (accepted, required)
- **Response**: Redirects to `/report/success/{tracking_code}`.

### `GET /report/success/{tracking_code}`
- **Controller**: `ReportController@success`
- **Status**: Built
- **Description**: Displays confirmation card with the generated `BRG-XXXXXX` tracking code. For anonymous guest submissions, displays one-time tracking code with notice that status can be viewed via `/track/{code}`.

### `GET /track/{tracking_code}`
- **Controller**: `TrackController@show`
- **Status**: Built
- **Description**: Displays report status timeline, public staff notes, and proof gallery. Never reveals reporter PII, internal notes, or emergency reports. Shows status only.

### `GET /track/{tracking_code?}`
- **Controller**: `TrackController@show`
- **Status**: Built
- **Description**: Public incident progression tracker, official timeline, and feedback forms.

### `POST /track/{tracking_code}/clarification`
- **Controller**: `TrackController@replyClarification`
- **Status**: Built
- **Payload**: `response` (string, required, max: 2000).
- **Description**: Resident response to a staff `needs_info` inquiry.

### `POST /track/{tracking_code}/confirm`
- **Controller**: `TrackController@confirmResolution`
- **Status**: Built
- **Payload**: `rating` (integer, 1–5, optional), `comment` (string, optional).
- **Description**: Resident verifies work is completed; transitions report from `awaiting_confirmation` $\rightarrow$ `resolved`.

### `POST /track/{tracking_code}/reopen`
- **Controller**: `TrackController@reopen`
- **Status**: Built
- **Payload**: `reason` (string, required, min: 10, max: 1000).
- **Description**: Resident disputes resolution; transitions report to `reopened`.

### `GET /map`
- **Controller**: `MapController@index`
- **Status**: Built
- **Description**: Public transparency map showing active public reports (emergencies strictly excluded).

---

## 3. Engine & Utility Routes (`routes/engine.php`)

### `GET /api/nearby-reports`
- **Controller**: `NearbyReportController@index`
- **Status**: Built
- **Parameters**: `lat` (float, required), `lng` (float, required), `category_id` or `category` (optional).
- **Description**: Calculates Haversine distance and returns open incidents within $300\text{m}$.

---

## 4. Staff Routes (`routes/staff.php`)

Protected by `auth` and `role:staff`.

| Method | URI | Controller Action | Purpose | Status | Owner |
| :--- | :--- | :--- | :--- | :--- | :--- |
| `GET` | `/admin/reports` | `Admin\ReportController@index` | Main priority triage queue with filters and pagination. | Built | Baseline / WS1 (Year filter) |
| `GET` | `/admin/my-cases` | `Admin\ReportController@myCases` | Legacy alias issuing 302 redirect to `/admin/reports?my_cases=1`. | Built | Baseline |
| `GET` | `/admin/reports/{id}` | `Admin\ReportController@show` | 3-way comparative review page (Resident vs AI vs Final). | Built | Baseline |
| `POST` | `/admin/reports/{id}/review` | `Admin\ReportReviewController@saveReview` | Confirms review values and routes Mode A / Mode B. | Built | Baseline |
| `POST` | `/admin/reports/{id}/status` | `Admin\ReportController@updateStatus` | Manual status transition with mandatory note and reason code for `no_action`. | Built | Baseline |
| `POST` | `/admin/reports/{id}/unmerge` | `Admin\ReportController@unmerge` | Unlinks accidental duplicate and returns report to `under_review`. | Built | Baseline |
| `POST` | `/admin/reports/{id}/info-request` | `Admin\ReportController@requestInfo` | Posts clarification inquiry (`needs_info`). | Built | Baseline |
| `POST` | `/admin/reports/{id}/proof-photos` | `Admin\ReportController@storeProofPhotos` | Uploads up to 5 multi-image resolution proofs. | Built | Baseline |
| `POST` | `/admin/reports/{id}/notes` | `Admin\ReportController@storeInternalNote` | Appends staff-only internal discussion note. | Built | Baseline |
| `GET` | `/admin/referrals` | `Admin\ReferralController@index` | Unified directory for internal dispatch and external agency referrals. | Built | WS5 |
| `GET` | `/admin/referrals/{id}` | `Admin\ReferralController@show` | Referral details, letter, and external agency tracking. | Built | WS5 |
| `GET` | `/admin/referrals/{id}/letter` | `Admin\ReferralController@letter` | Printable formal agency endorsement letter. | Built | WS5 |

---

## 5. Admin Routes (`routes/admin.php`)

Protected by `auth` and `role:admin`.

| Method | URI | Controller Action | Purpose | Status | Owner |
| :--- | :--- | :--- | :--- | :--- | :--- |
| `GET` | `/admin/dashboard` | `Admin\DashboardController@index` | KPI metrics, category breakdowns, and AI insights. | Built | Baseline / WS1 |
| `GET` | `/admin/settings/categories` | `Admin\CategoryController@index` | Manage categories, question builder, and SLA hours. | Built | Baseline |
| `GET` | `/admin/settings/offices` | `Admin\OfficeController@index` | Manage internal committees and external agencies. | Built | Baseline |
| `GET` | `/admin/settings/routing-rules` | `Admin\RoutingRuleController@index` | Jurisdiction routing matrix and Mode A/B rules. | Built | Baseline |
| `GET` | `/admin/settings/priority-weights`| `Admin\SettingController@priorityWeights` | Adjust numerical weights for priority scoring. | Built | Baseline |
| `GET` | `/admin/audit-log` | `Admin\AuditLogController@index` | Immutable security audit trail of staff actions and settings. | Built | Baseline |
| `GET` | `/admin/reports/export` | `Admin\ReportController@exportCsv` | Streamed CSV download of reports matching queue filters. | Built | Baseline / WS1 |

---

## 6. Workstream Route Partitions

### A. Setup & System Wizard (`routes/setup.php` — WS1)
Guest / Initial Admin setup route group with `web` middleware.

| Method | URI | Route Name | Middleware / Permission | Purpose | Status |
| :--- | :--- | :--- | :--- | :--- | :--- |
| `GET` | `/setup` | `setup.show` | `web` | 5-step interactive onboarding wizard (Verification, Admin Account, Profile, Fiscal Year, Personnel Review). | Built |
| `POST` | `/setup/step/{step}` | `setup.step` | `web` | Validate & save specific setup step. | Built |
| `POST` | `/setup/complete` | `setup.complete` | `web` | Finalize setup wizard & redirect to login. | Built |

### B. Settings, Fiscal Years, Calendar & Backups (`routes/settings.php` — WS1)
Protected by `auth`, `role:staff`, and granular permissions.

| Method | URI | Route Name | Middleware / Permission | Purpose | Status |
| :--- | :--- | :--- | :--- | :--- | :--- |
| `GET` | `/admin/settings/general` | `admin.settings.general.edit` | `permission:settings.manage` | Core barangay profile & switches. | Built |
| `PUT` | `/admin/settings/general` | `admin.settings.general.update` | `permission:settings.manage` | Update general barangay settings. | Built |
| `GET` | `/admin/fiscal-years` | `admin.fiscal-years.index` | `permission:fiscal_year.manage` | Fiscal year management list. | Built |
| `POST` | `/admin/fiscal-years` | `admin.fiscal-years.store` | `permission:fiscal_year.manage` | Create new fiscal year. | Built |
| `POST` | `/admin/fiscal-years/{id}/activate` | `admin.fiscal-years.activate` | `permission:fiscal_year.manage` | Set active fiscal year. | Built |
| `POST` | `/admin/fiscal-years/{id}/lock` | `admin.fiscal-years.lock` | `permission:fiscal_year.manage` | Lock closed fiscal year (immutable). | Built |
| `POST` | `/admin/fiscal-years/switch-view` | `admin.fiscal-years.switch-view` | `auth` | Set session viewing fiscal year. | Built |
| `GET` | `/admin/settings/numbering` | `admin.numbering.edit` | `permission:settings.manage` | Numbering scheme formats ({MM}-{SEQ:3}-{YEAR}). | Built |
| `PUT` | `/admin/settings/numbering` | `admin.numbering.update` | `permission:settings.manage` | Update numbering formats. | Built |
| `GET` | `/admin/settings/calendar` | `admin.settings.calendar.index` | `permission:settings.manage` | Manage holidays for working days engine. | Built |
| `GET` | `/admin/settings/pending` | `admin.pending.index` | `permission:settings.manage` | Unconfirmed assumptions register. | Built |
| `POST` | `/admin/settings/pending/{id}/confirm` | `admin.pending.confirm` | `permission:settings.manage` | Confirm placeholder value. | Built |
| `GET` | `/admin/settings/templates` | `admin.templates.index` | `permission:templates.manage` | Response templates catalog. | Built |
| `GET` | `/admin/settings/case-types` | `admin.case-types.index` | `permission:settings.manage` | KP case categories and timeline flags. | Built |
| `GET` | `/admin/settings/case-fields` | `admin.case-fields.index` | `permission:settings.manage` | Configure dynamic form fields. | Built |
| `GET` | `/admin/settings/escalation-destinations` | `admin.escalation-destinations.index` | `permission:settings.manage` | Manage court/prosecutor destinations. | Built |
| `GET` | `/admin/settings/backups` | `admin.backups.index` | `permission:backups.manage` | Database backup management & export. | Built |
| `POST` | `/admin/settings/backups/run` | `admin.backups.run` | `permission:backups.manage` | Trigger immediate system backup. | Built |
| `GET` | `/admin/settings/backups/{file}` | `admin.backups.download` | `permission:backups.manage` | Download private backup archive. | Built |
| `GET` | `/admin/search` | `admin.search` | `auth`, `role:staff`, `throttle:30,1` | Multi-year global search. | Built |

### C. People, Personnel & Reports Center (`routes/people.php`, `routes/reports_center.php` — WS2)
Protected by `auth`, `role:staff`, and granular permissions.

| Method | URI | Route Name | Middleware / Permission | Purpose | Status |
| :--- | :--- | :--- | :--- | :--- | :--- |
| `GET` | `/admin/personnel` | `admin.personnel.index` | `permission:personnel.manage` | Directory of officials, tanods & Lupon. | Built |
| `GET` | `/admin/positions` | `admin.positions.index` | `permission:personnel.manage` | Manage barangay positions & codes. | Built |
| `GET` | `/admin/signatories` | `admin.signatories.edit` | `permission:personnel.manage` | Document signatory matrix. | Built |
| `PUT` | `/admin/signatories` | `admin.signatories.update` | `permission:personnel.manage` | Update document signatory roles. | Built |
| `GET` | `/admin/users` | `admin.users.index` | `permission:users.manage` | Manage staff accounts and presets. | Built |
| `GET` | `/admin/users/{id}/permissions` | `admin.users.permissions.edit` | `permission:users.manage` | Edit granular user permission checkboxes. | Built |
| `PUT` | `/admin/users/{id}/permissions` | `admin.users.permissions.update` | `permission:users.manage` | Apply permission preset or custom list. | Built |
| `GET` | `/admin/reports-center` | `admin.reports-center.index` | `permission:reports_center.view` | Report templates catalog (DILG/KP). | Built |
| `GET` | `/admin/reports-center/{key}` | `admin.reports-center.show` | `permission:reports_center.view` | Report parameter form and table preview. | Built |
| `POST` | `/admin/reports-center/{key}/export` | `admin.reports-center.export` | `permission:reports_center.export` | Export report to PDF, Excel, or Print. | Built |
| `GET` | `/admin/reports-center/generated/{id}` | `admin.reports-center.download` | `permission:reports_center.view` | Download previously generated report. | Built |

### D. Case Intake & Party Management (`routes/cases.php` — WS3)
Protected by `auth`, `role:staff`, and `permission:cases.view`.

| Method | URI | Route Name | Middleware / Permission | Purpose | Status |
| :--- | :--- | :--- | :--- | :--- | :--- |
| `GET` | `/admin/cases` | `admin.cases.index` | `permission:cases.view` | Case list with deadline badges and filters. | Built |
| `GET` | `/admin/cases/create` | `admin.cases.create` | `permission:cases.create` | Blotter vs Complaint intake form. | Built |
| `POST` | `/admin/cases` | `admin.cases.store` | `permission:cases.create` | Persist case with duplicate warnings. | Built |
| `GET` | `/admin/cases/{id}` | `admin.cases.show` | `permission:cases.view` | Case dossier, parties, notes, timeline. | Built |
| `GET` | `/admin/cases/{id}/edit` | `admin.cases.edit` | `permission:cases.edit` | Edit complaint narrative and metadata. | Built |
| `PUT` | `/admin/cases/{id}` | `admin.cases.update` | `permission:cases.edit` | Update case details. | Built |
| `GET` | `/admin/parties` | `admin.parties.index` | `permission:cases.view` | Master party directory. | Built |
| `GET` | `/admin/parties/{id}` | `admin.parties.show` | `permission:cases.view` | Party profile and case history. | Built |
| `GET` | `/admin/parties/{party}/merge` | `admin.parties.merge.create` | `permission:cases.edit` | Side-by-side party merge preview. | Built |
| `POST` | `/admin/parties/{party}/merge` | `admin.parties.merge.store` | `permission:cases.edit` | Commit party record merge. | Built |
| `POST` | `/admin/reports/{id}/convert-to-case`| `admin.reports.convert-to-case` | `permission:cases.create` | Prefill dispute intake from report. | Built |

### E. Case Procedural Actions & Deadlines (`routes/case_actions.php` — WS4)
Protected by `auth`, `role:staff`, and procedural permissions.

| Method | URI | Route Name | Middleware / Permission | Purpose | Status |
| :--- | :--- | :--- | :--- | :--- | :--- |
| `GET` | `/admin/secretary` | `admin.secretary` | `permission:cases.view` | Secretary operational reminders dashboard. | Built |
| `GET` | `/admin/hearings` | `admin.hearings.index` | `permission:cases.summons` | Hearing schedule calendar & upcoming roster.| Built |
| `GET` | `/admin/cases/{id}/summons/create` | `admin.cases.summons.create` | `permission:cases.summons` | Form to generate sequential summons. | Built |
| `POST` | `/admin/cases/{id}/summons` | `admin.cases.summons.store` | `permission:cases.summons` | Issue sequential summons. | Built |
| `GET` | `/admin/cases/{id}/summons/{s}/print` | `admin.cases.summons.print` | `permission:cases.summons` | Printable summons with signature block. | Built |
| `POST` | `/admin/cases/{id}/summons/{s}/void` | `admin.cases.summons.void` | `permission:cases.summons` | Void summons with mandatory note. | Built |
| `POST` | `/admin/cases/{id}/summons/{s}/service` | `admin.cases.summons.service` | `permission:cases.summons` | Record summons service result. | Built |
| `POST` | `/admin/cases/{id}/summons/{s}/attendance`| `admin.cases.summons.attendance`| `permission:cases.summons` | Record party attendance at hearing. | Built |
| `POST` | `/admin/cases/{id}/hearings` | `admin.cases.hearings.store` | `permission:cases.summons` | Schedule conciliation hearing. | Built |
| `POST` | `/admin/hearings/{h}/outcome` | `admin.cases.hearings.outcome` | `permission:cases.summons` | Record hearing outcome proceedings. | Built |
| `POST` | `/admin/cases/{id}/settle` | `admin.cases.settle` | `permission:cases.edit` | Record settlement terms & agreement upload. | Built |
| `GET` | `/admin/cases/{id}/escalate` | `admin.cases.escalate.create` | `permission:cases.escalate` | CFA / Court escalation form. | Built |
| `POST` | `/admin/cases/{id}/escalate` | `admin.cases.escalate.store` | `permission:cases.escalate` | Certify case & issue CFA. | Built |
| `GET` | `/admin/cases/{id}/escalate/letter` | `admin.cases.escalate.letter` | `permission:cases.escalate` | Printable CFA & endorsement letter. | Built |
| `POST` | `/admin/cases/{id}/notes` | `admin.cases.notes.store` | `permission:cases.view` | Append investigation or hearing notes. | Built |

### F. External Agency Portal & Referrals (`routes/agency.php` — WS5)
Protected by `auth`, `role:agency`, `agency.active` (or `agencies.manage` on admin side).

| Method | URI | Route Name | Middleware / Permission | Purpose | Status |
| :--- | :--- | :--- | :--- | :--- | :--- |
| `GET` | `/agency/register` | `agency.register` | `guest`, `throttle:5,1` | Agency registration with ID/letter upload. | Built |
| `POST` | `/agency/register` | `agency.register.store` | `guest`, `throttle:5,1` | Submit credentials and official agency proof. | Built |
| `GET` | `/agency/pending` | `agency.pending` | `auth` | Awaiting admin account approval landing page.| Built |
| `GET` | `/agency/referrals` | `agency.referrals.index` | `role:agency, agency.active` | Outside agency referral inbox. | Built |
| `GET` | `/agency/referrals/{id}` | `agency.referrals.show` | `role:agency, agency.active` | Referral case dossier & paper attachments. | Built |
| `POST` | `/agency/referrals/{id}/accept` | `agency.referrals.accept` | `role:agency, agency.active` | Agency acknowledgment of referred case. | Built |
| `POST` | `/agency/referrals/{id}/updates` | `agency.referrals.updates.store`| `role:agency, agency.active` | Agency progress milestone or resolution update. | Built |
| `POST` | `/agency/referrals/{id}/message` | `agency.referrals.message` | `role:agency, agency.active` | Send direct message to barangay staff. | Built |
| `GET` | `/agency/team` | `agency.team.index` | `role:agency, agency.active` | Agency office team roster (Office Admin). | Built |
| `POST` | `/agency/team/invite` | `agency.team.invite` | `role:agency, agency.active` | Send invite link to agency colleague. | Built |
| `GET` | `/agency/invite/{token}` | `agency.invite.show` | `public`, `throttle:5,1` | Invited agency colleague registration form. | Built |
| `POST` | `/agency/invite/{token}` | `agency.invite.accept` | `public`, `throttle:5,1` | Complete invited registration. | Built |
| `GET` | `/admin/agencies` | `admin.agencies.index` | `permission:agencies.manage` | Admin review list of partner agency requests.| Built |
| `GET` | `/admin/agencies/{id}/document` | `admin.agencies.document` | `permission:agencies.manage` | Stream private ID/letter proof document. | Built |
| `POST` | `/admin/referrals/{id}/send` | `admin.referrals.send` | `permission:referrals.manage` | Transmit prepared referral to agency portal. | Built |

### G. Legacy File Import (`routes/imports.php` — WS6, Stretch)
Protected by `auth`, `role:staff`, `permission:imports.manage`.

| Method | URI | Route Name | Middleware / Permission | Purpose | Status |
| :--- | :--- | :--- | :--- | :--- | :--- |
| `GET` | `/admin/imports` | `admin.imports.index` | `permission:imports.manage` | Batch history of Excel/CSV imports. | Built |
| `GET` | `/admin/imports/create` | `admin.imports.create` | `permission:imports.manage` | Upload file and download blank template. | Built |
| `POST` | `/admin/imports` | `admin.imports.store` | `permission:imports.manage` | Store file on private disk & create batch. | Built |
| `GET` | `/admin/imports/{id}/map` | `admin.imports.map` | `permission:imports.manage` | Interactive column mapping screen. | Built |
| `POST` | `/admin/imports/{id}/map` | `admin.imports.save-map` | `permission:imports.manage` | Save column mapping & validate sample. | Built |
| `GET` | `/admin/imports/{id}/preview` | `admin.imports.preview` | `permission:imports.manage` | Preview parsed rows and duplicate warnings. | Built |
| `POST` | `/admin/imports/{id}/commit` | `admin.imports.commit` | `permission:imports.manage` | Commit rows in chunks (original case numbers). | Built |
| `POST` | `/admin/imports/{id}/rollback` | `admin.imports.rollback` | `permission:imports.manage` | Roll back committed batch completely. | Built |

---

## 7. Voice Intake & Analysis Routes (Record-First Pipeline)

| Method | URI | Route Name | Middleware / Permission | Purpose | Status |
| :--- | :--- | :--- | :--- | :--- | :--- |
| `POST` | `/report/voice/analyze` | `reports.voice.analyze` | `public`, `throttle:3,10` | Resident voice intake: analyzes audio, returns candidate pre-fill fields (includes category suggestion if clearly described; strictly omits raw transcript and urgency; drops contact on anonymous). | Implemented |
| `POST` | `/admin/reports/phone/analyze` | `admin.reports.phone.analyze` | `auth`, `role:staff`, `permission:reports.log_phone` | Staff phone intake: analyzes audio, returns transcript, extracted contact details, category & urgency suggestions. | Implemented |
| `POST` | `/report/voice/transcribe` | `reports.voice.transcribe` | `public`, `throttle:5,1` | Legacy raw transcription endpoint (backward compatibility). | Implemented |
| `GET` | `/admin/reports/phone` | `admin.reports.phone.create` | `auth`, `role:staff`, `permission:reports.log_phone` | Renders the hotline phone complaint intake form. | Implemented |
| `POST` | `/admin/reports/phone` | `admin.reports.phone.store` | `auth`, `role:staff`, `permission:reports.log_phone` | Submits telephone report with verbal consent (`consent_recorded_by`) and audio token. | Implemented |
| `GET` | `/admin/reports/{report}/audio` | `admin.reports.audio` | `auth`, `role:staff`, `permission:reports.view` | Streams private audio with HTTP Range support (`Cache-Control: private, no-store`). | Implemented |
| `POST` | `/admin/reports/{report}/audio/verify` | `admin.reports.audio.verify` | `auth`, `role:staff`, `permission:reports.view` | Staff listens to uncertain audio, confirms/inputs text, and clears review gate. | Implemented |

### Request & Response Specifications

#### 1. Voice Analyze Endpoints (`POST /report/voice/analyze` and `POST /admin/reports/phone/analyze`)
- **Request**: `multipart/form-data`
  - `audio`: Required audio file (max 10 MB, verified audio MIME: `audio/webm`, `audio/ogg`, `audio/wav`, `audio/mp4`, `audio/mpeg`, `video/webm`).
  - `duration_seconds`: Optional integer ($\le 120\text{s}$).
  - `anonymous`: Optional boolean (resident endpoint; when true, server drops caller contact).
  - `mock_scenario`: Optional string (`full`, `no_contact`, `partial`, `unclear`, `emergency`) for testing and offline evaluation.
- **Limits & Abuse Prevention**:
  - Per-IP rate limiting: 3 requests per 10 minutes on resident endpoint (`throttle:3,10`).
  - Daily analysis limit: `voice.daily_analyze_limit` (default 100); when reached, returns fallback without calling Gemini.
  - Resident toggle: `voice.resident_recording_enabled`; when false, returns disabled fallback.

- **Response - Staff Success (`clarity = clear` or `partial`)**:
  ```json
  {
    "ok": true,
    "token": "3fa85f64-5717-4562-b3fc-2c963f66afa6",
    "status": "done",
    "clarity": "clear",
    "is_partial": false,
    "is_unclear": false,
    "notice": null,
    "language": "fil",
    "transcript": "Magandang umaga, ako si Maria Santos, 09171234567. May tambak na basura sa Purok 2 malapit sa school...",
    "description": "May tambak na basura sa Purok 2 malapit sa school na hindi nahahakot.",
    "caller_name": "Maria Santos",
    "caller_phone": "09171234567",
    "caller_email": "maria@example.com",
    "purok": "Purok 2",
    "purok_id": 2,
    "landmark": "Near school",
    "category": "garbage",
    "category_id": 3,
    "urgency": "High",
    "emergency": false,
    "wants_anonymous": false,
    "not_stated": []
  }
  ```

- **Response - Resident Success (PII Isolated & Suggestions Hidden)**:
  ```json
  {
    "ok": true,
    "token": "3fa85f64-5717-4562-b3fc-2c963f66afa6",
    "status": "done",
    "clarity": "clear",
    "is_partial": false,
    "is_unclear": false,
    "notice": null,
    "language": "fil",
    "description": "May tambak na basura sa Purok 2 malapit sa school na hindi nahahakot.",
    "caller_name": "Maria Santos",
    "caller_phone": "09171234567",
    "caller_email": null,
    "purok": "Purok 2",
    "purok_id": 2,
    "landmark": "Near school",
    "category": "garbage",
    "category_id": 1,
    "urgency": null,
    "emergency": false,
    "wants_anonymous": false,
    "not_stated": ["caller_email"]
  }
  ```
  *(Note: Raw `transcript` is omitted entirely; `urgency` is strictly `null` as resident form has no urgency field; `category` may be returned as an editable suggestion labeled "From recording" if clearly described in audio; if anonymous mode is requested, all contact details are returned as `null`)*.

- **Response - Unclear Audio (`transcript_status = needs_verification`)**:
  ```json
  {
    "ok": true,
    "token": "3fa85f64-5717-4562-b3fc-2c963f66afa6",
    "status": "needs_verification",
    "clarity": "unclear",
    "is_partial": false,
    "is_unclear": true,
    "notice": "We couldn't clearly transcribe parts of your voice note. Barangay staff will listen and review it before processing.",
    "language": null,
    "description": "",
    "caller_name": null,
    "caller_phone": null,
    "caller_email": null,
    "purok": null,
    "purok_id": null,
    "landmark": null,
    "category": null,
    "category_id": null,
    "urgency": null,
    "emergency": false,
    "wants_anonymous": false,
    "not_stated": ["caller_name", "caller_phone", "caller_email", "purok", "landmark", "category", "urgency", "description"]
  }
  ```
  *(No candidate fields are pre-filled; unstated fields show "Not provided"; report is accepted into `submitted` with placeholder if empty, and staff must verify before routing)*.

- **Response - Fallback (Daily Limit or Feature Disabled)**:
  ```json
  {
    "ok": false,
    "status": "limit_reached",
    "fallback": true,
    "message": "The daily voice analysis limit has been reached. Please fill in the report details manually."
  }
  ```

#### 2. Hotline Phone Report Submission (`POST /admin/reports/phone`)
- **Request**:
  - `audio_token`: UUID token (optional if officer typed notes directly).
  - `caller_name`: String (optional).
  - `caller_contact`: String (optional).
  - `purok_id`: Foreign key (required).
  - `description`: Text (required; officer notes or verified transcript).
  - `verbal_consent`: Boolean `true` (required checkbox certifying verbal consent).

#### 3. Audio Streaming Endpoint (`GET /admin/reports/{report}/audio`)
- **Headers**:
  - `Range: bytes=0-` (supported for seeking/scrubbing).
  - Returns `206 Partial Content` with `Content-Range`, `Content-Type: audio/webm` (or matching mime), `Cache-Control: private, no-store`.
  - Responds `403 Forbidden` if user lacks `reports.view` or report has no audio.
  - Never accessible on unauthenticated public routes.

#### 4. Staff Audio Verification (`POST /admin/reports/{report}/audio/verify`)
- **Request**:
  - `verified_description`: Text (required; confirmed transcript or corrected text).
  - `verification_note`: Text (optional).
- **Response**: Redirect back to report review page with success toast. Unblocks "Confirm and route" action.

---

## 14. Planned Endpoints (Resident Complaint Intake & Case Enhancements)

> [!IMPORTANT]
> The endpoints in this section are **Planned**. They are not yet present in the application routing table or in `docs/ROUTES.snapshot.json`. Once implemented in code, the snapshot must be regenerated with `php artisan route:list --json > docs/ROUTES.snapshot.json`.

### 14.1 Public Resident Complaint Intake (`routes/resident.php`, Planned)

#### `GET /complaint`
- **Controller**: `Resident\ComplaintController@create` (Planned)
- **Status**: Planned ([DECIDED, D2, D4, D4a])
- **Middleware**: `web` (Turbo Drive disabled, 375px mobile first)
- **Description**: Displays the resident dispute filing interface with an upfront confidential screening question (VAWC / child protection). If answered "Yes", the form is hidden, no data is stored, and the user is shown emergency hotlines (911, PNP Aleng Pulis, Bantay Bata) with a "Leave this page" quick-exit button. If answered "No", displays the compact single-card complaint form.

#### `POST /complaint`
- **Controller**: `Resident\ComplaintController@store` (Planned)
- **Status**: Planned ([DECIDED, D4])
- **Middleware**: `web`, `throttle:3,10` (rate limited; spam protection via honeypot)
- **Payload**:
  - `complainant_name` (string, required)
  - `complainant_alias` (string, nullable)
  - `complainant_age` (integer, nullable)
  - `complainant_contact` (string, required)
  - `complainant_address` (string, required)
  - `complainant_residency_claim` (string, required)
  - `respondent_name` (string, required; stored as unlinked free text)
  - `respondent_alias` (string, nullable)
  - `respondent_address` (string, required)
  - `respondent_contact` (string, nullable)
  - `what_happened` (string, required) — 5Ws
  - `who_involved` (string, required) — 5Ws
  - `when_happened` (datetime, required) — 5Ws
  - `where_happened` (string, required) — 5Ws
  - `why_how_happened` (string, required) — 5Ws
  - `rights_violated` (string, required)
  - `relief_requested` (string, required)
  - `case_type_suggestion` (string, nullable)
  - `consent` (accepted, required; RA 10173 data privacy consent)
- **Behavior**: Persists record into `complaint_intakes` in status `pending`. Does NOT create a case or any `parties` records.
- **Response**: Redirects to `/complaint/received`.

#### `GET /complaint/received`
- **Controller**: `Resident\ComplaintController@received` (Planned)
- **Status**: Planned ([DECIDED, D4])
- **Description**: Displays acknowledgement screen only: *"Your complaint request has been received. Please visit the Barangay Calarian Hall to sign and complete filing."* Strictly reveals NO case status, NO respondent information, and NO dispute tracking.

### 14.2 Staff Pending Intakes Review (`routes/cases.php`, Planned)

#### `GET /admin/cases/intakes`
- **Controller**: `Admin\CaseIntakeController@index` (Planned)
- **Permission**: `cases.view`
- **Description**: Renders the "Pending intake" tab on the Cases Docket. Fixed table layout with columns totaling 100%, mobile card stack below 768px.

#### `GET /admin/cases/intakes/{intake}`
- **Controller**: `Admin\CaseIntakeController@show` (Planned)
- **Permission**: `cases.view`
- **Description**: Displays complete details of the resident complaint intake, showing complainant details, respondent free-text details, 5Ws, rights violated, relief requested, and duplicate warnings.

#### `POST /admin/cases/intakes/{intake}/docket`
- **Controller**: `Admin\CaseIntakeController@docket` (Planned)
- **Permission**: `cases.create`
- **Description**: Executes the "Docket this" action. Opens the existing WS3 intake form prefilled with the intake data. Upon staff confirmation, creates the case in status `filed`, confirms parties, logs the blotter entry reference (two numbers, one record), and transitions intake to `docketed`.

#### `POST /admin/cases/intakes/{intake}/reject`
- **Controller**: `Admin\CaseIntakeController@reject` (Planned)
- **Permission**: `cases.edit`
- **Payload**:
  - `outcome_reason` (string, required): Standard reason code.
  - `explanation_note` (string, required): Written justification.
- **Description**: Marks the intake as `redirected` or `rejected`.

### 14.3 Case Procedures & Printables (`routes/case_actions.php`, Planned)

#### `POST /admin/cases/{case}/first-meeting`
- **Controller**: `Admin\CaseActionController@recordFirstMeeting` (Planned)
- **Permission**: `cases.edit`
- **Payload**: `first_meeting_on` (date, required)
- **Description**: Records the first meeting before the Punong Barangay to start the 15-working-day mediation clock ([PROPOSED, P2]).

#### `POST /admin/cases/{case}/eligibility`
- **Controller**: `Admin\CaseActionController@updateEligibility` (Planned)
- **Permission**: `cases.edit`
- **Payload**: `checklist` (array of booleans: `parties_same_barangay`, `kp_matter`, `non_confidential`, `residency_verified`)
- **Description**: Saves the staff pre-summons eligibility screening checklist ([PROPOSED, P1]).

#### `POST /admin/cases/{case}/upload-scan`
- **Controller**: `Admin\CaseActionController@uploadScan` (Planned)
- **Permission**: `cases.edit`
- **Payload**: `signed_document` (file, PDF/image, max 10MB)
- **Description**: Uploads a scanned copy of the signed blotter or complaint sheet to private storage disk.

#### Printables Endpoints
All printable endpoints resolve signatories dynamically via `SignatoryResolver::forDocument()` and display `<x-ui.draft-watermark>`:
- `GET /admin/cases/{case}/print/blotter`: Printable blotter extract (Secretary prepares, PB certifies).
- `GET /admin/cases/{case}/print/complaint`: Printable formal complaint form (Complainant signs, PB acknowledges).
- `GET /admin/cases/{case}/summons/{summons}/print`: Printable summons notice (PB signed).
- `GET /admin/cases/{case}/hearings/{hearing}/notice`: Printable conciliation notice of hearing (Pangkat Chairman signed).
- `GET /admin/cases/{case}/print/settlement`: Printable amicable settlement agreement (Parties + PB / Pangkat Chair).
- `GET /admin/cases/{case}/print/cfa`: Printable Certification to File Action (Pangkat Chairman signed per C3).

> [!NOTE]
> **Permission Slugs Stability**: All planned endpoints strictly reuse the existing **21 permission slugs** seeded in `RequiredDataSeeder.php` (`cases.view`, `cases.create`, `cases.edit`, `cases.summons`, `cases.escalate`, etc.). No new permission slugs are required.
