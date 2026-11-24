# API Contracts & Routing Reference

Reference documentation for web endpoints, RESTful parameters, authentication middleware, and payload schemas in **Barangay-Reporting-System-1**.

> [!NOTE]
> All endpoints documented below reflect active routes registered in the application (273 routes total, snapshotted in `docs/ROUTES.snapshot.json`).
> To regenerate the route snapshot from the live application routing table, run:
> ```sh
> php artisan route:list --json > docs/ROUTES.snapshot.json
> ```
> Endpoints across all workstreams (WS1 through WS6) are fully implemented and verified against the PHPUnit test suite.

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
- **Status**: Built
- **Description**: Renders the resident single-card incident reporting interface.

### `POST /report`
- **Controller**: `ReportController@store`
- **Status**: Built
- **Middleware**: `throttle:10,1`
- **Payload**:
  - `category_id` (integer, required, exists in `categories`)
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
  - `reporter_name` (string, nullable)
  - `reporter_contact` (string, nullable)
  - `reporter_email` (email, nullable)
  - `is_anonymous` (boolean, nullable)
  - `consent` (accepted, required)
- **Response**: Redirects to `/report/success/{tracking_code}`.

### `GET /report/success/{tracking_code}`
- **Controller**: `ReportController@success`
- **Status**: Built
- **Description**: Displays confirmation card with the generated `BRG-XXXXXX` tracking code.

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
| `GET` | `/setup` | `setup.show` | `web` | 7-step interactive onboarding wizard. | Built |
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

## 7. Planned Routes (Voice Prototype — WS7)

> [!NOTE]
> All routes below are **Planned (not yet implemented)**. Route names and URLs are tentative and will be finalized in `docs/ROUTES.snapshot.json` upon implementation via `php artisan route:list --json > docs/ROUTES.snapshot.json`.

| Method | Tentative URI | Tentative Route Name | Middleware / Permission | Purpose | Status |
| :--- | :--- | :--- | :--- | :--- | :--- |
| `POST` | `/report/voice/transcribe` | `reports.voice.transcribe` | `public`, `throttle:5,1` | Uploads raw audio recording or audio file; returns transcript token and state. | Planned |
| `GET` | `/admin/reports/phone` | `admin.reports.phone.create` | `auth`, `role:staff`, `permission:reports.log_phone` | Renders the hotline phone complaint intake form. | Planned |
| `POST` | `/admin/reports/phone` | `admin.reports.phone.store` | `auth`, `role:staff`, `permission:reports.log_phone` | Submits telephone report with verbal consent and audio token. | Planned |
| `GET` | `/admin/reports/{report}/audio` | `admin.reports.audio` | `auth`, `role:staff`, `permission:reports.view` | Streams private audio with HTTP Range support (`Cache-Control: private, no-store`). | Planned |
| `POST` | `/admin/reports/{report}/audio/verify` | `admin.reports.audio.verify` | `auth`, `role:staff`, `permission:reports.view` | Staff listens to uncertain audio, confirms/inputs text, and clears review gate. | Planned |

### Request & Response Specifications

#### 1. Public Transcription Endpoint (`POST /report/voice/transcribe`)
- **Request**: `multipart/form-data`
  - `audio`: Required audio file (max 10 MB, duration $\le 60\text{s}$, MIME `audio/webm`, `audio/mp4`, `audio/ogg`, or `audio/wav`).
- **Response - Success (`transcript_status = done`)**:
  ```json
  {
    "token": "3fa85f64-5717-4562-b3fc-2c963f66afa6",
    "status": "done",
    "transcript": "May sirang kalsada at baradong kanal sa tapat ng purok hall...",
    "language": "fil",
    "duration_seconds": 28
  }
  ```
  *(Text is automatically inserted into description textarea for reporter review)*.
- **Response - Uncertain (`transcript_status = needs_verification`)**:
  ```json
  {
    "token": "3fa85f64-5717-4562-b3fc-2c963f66afa6",
    "status": "needs_verification",
    "unverified_draft": "May sirang... [unintelligible]",
    "language": "fil",
    "message": "We couldn't understand this recording clearly. Type a short description, or send the recording and barangay staff will listen to it."
  }
  ```
  *(Guessed text is NEVER inserted as description; report is accepted with placeholder if empty, and staff must verify)*.
- **Response - Error (`transcript_status = failed`)**:
  ```json
  {
    "token": "3fa85f64-5717-4562-b3fc-2c963f66afa6",
    "status": "failed",
    "error": "Transcription timeout or unreadable audio format.",
    "message": "We couldn't understand this recording clearly. Type a short description, or send the recording and barangay staff will listen to it."
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
