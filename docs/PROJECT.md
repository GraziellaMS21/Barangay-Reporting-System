# Project Specification: Barangay Problem Reporting System

Municipal GovTech Civic Issue Management System for local governance in **Barangay Calarian, Zamboanga City** (single pilot barangay).

Client: Barangay Calarian, Zamboanga City  
Team: Group 3 (SynohDev), Western Mindanao State University, Software Engineering 1  
Core Principle: *"The AI suggests, the rules route, the officials decide."*

---

## 1. Problem Statement & Purpose

Traditional incident reporting in barangays relies on physical walk-ins to the barangay hall, phone calls to personal mobile numbers, or unorganized group chats. This leads to lost requests, no accountability, lack of status visibility for residents, and difficulty triaging critical issues vs. routine maintenance.

The system provides a lightweight, mobile-responsive web platform where residents can submit civic concerns and dispute complaint requests without friction, while barangay officials receive AI-assisted triage with human-in-the-loop review, automated SLA tracking, structured external agency referrals via a dedicated Partner Agency Portal, and formal Katarungang Pambarangay case blotter/conciliation management.

### Modular Architecture: Two Modules Joined by Conversion ([DECIDED, D1])
The application operates as **two distinct operational modules joined by an explicit conversion bridge**, plus shared administration:
1. **Community Problem Reporting (Report Queue)**: Covers civic, municipal, sanitation, streetlight, drainage, flood, and infrastructure issues handled by internal committees or referred to external agencies, governed strictly by the canonical 12-status report lifecycle. Community reports reflect established barangay operational practice ([CONFIRMED, C6]) and can be submitted without an account or anonymously ([DECIDED, D7]).
2. **Katarungang Pambarangay Case Management (Cases Docket)**: Covers interpersonal disputes, peace-and-order offenses, and conciliation proceedings governed by statutory Katarungang Pambarangay rules and the canonical 8-status case lifecycle. Features master enable switch `cases.enabled` and is pending thesis adviser confirmation.
- **Conversion Bridge**: Civil community reports that turn out to be interpersonal disputes may be converted by authorized staff to formal cases via Report-to-Case Conversion (§ 8.8 in WORKFLOW.md). The two modules are never conflated or merged in management queues. Page renames (e.g. Report Queue -> "Community reports", Barangay Cases -> "Cases & Blotter") are Planned; route paths and permission slugs remain unchanged.

### Resident Entry Screen Architecture (Three Buttons, [DECIDED, D2])
Before any form is displayed, the resident entry screen (`resources/views/resident/create.blade.php`) presents three large, clear action buttons:
1. **Emergency**: Reuses the existing emergency path, immediate 911/hotline display, and safety alerts (`--red` accent).
2. **Report a Community Problem**: Opens the existing single-card community problem report form unchanged, supporting registered, guest, and anonymous submissions ([DECIDED, D7]). Features wrong-door notice directing civil disputes to the complaint form ([DECIDED, D6]).
3. **File a Complaint (Planned, [DECIDED, D4])**: Opens the resident complaint intake form. Features mandatory upfront screening excluding confidential matters (VAWC / child protection) ([DECIDED, D4a]), captures complainant and respondent details and the 5Ws, and creates a **pending complaint intake**. Staff review and docket this via the "Pending intake" tab on the Cases Docket.
*There is NO separate "File a Blotter" button* ([DECIDED, D2]): a complaint automatically establishes the blotter entry behind it as its logbook reference (two numbers, one record, [DECIDED, D3]).

> [!NOTE]
> **Active Scope**: Incident reporting (including optional voice reporting prototype; record-and-transcribe without telephony; guest/anonymous community problem reporting), external agency referrals with Partner Agency Portal, and Katarungang Pambarangay dispute conciliation cases are in scope; the case module and the resident complaint intake are pending adviser confirmation (`cases.enabled` switch; question: *"Should residents be able to start a dispute complaint online as a pending request, or should disputes stay walk-in only?"*), and importing the barangay's last 4 years of old records is a stretch item. Real telephony, softphone integration, live call recording, and cellular SMS broadcasts remain deferred.

---

## 2. User Roles & Permissions

Coarse access tier is governed strictly by `users.role` with three allowed values: `admin`, `staff`, and `agency` (`personnel` is NOT a role; it is an assignable directory of people).

| Role | Account Tier | Position / Preset | Can Do | Must Not |
| :--- | :--- | :--- | :--- | :--- |
| **Resident** | **None** (guest, anonymous, or registered; tracking code `BRG-XXXXXX`) | None | Submit a community problem report (optionally recording audio, as a guest or anonymously without an account [DECIDED, D7; Planned]); submit an online dispute complaint request (Planned, [DECIDED, D4]); see emergency contacts; track community report status and public timeline; answer staff clarification questions; confirm or reopen resolution and rate 1–5 stars; view the limited public transparency map. | See personal identification, full details, or audio recordings of other residents' reports; see respondent details or dispute tracking on complaint acknowledgement ([DECIDED, D4]). |
| **Staff (Desk Officer / Tanod)** | Yes (`staff`) | Position `barangay_tanod`, Preset `staff_full` | Review the priority queue; record hotline phone reports (`reports.log_phone`); listen to and verify audio recordings requiring verification; view 3-way comparative triage (Resident vs. AI vs. Final); edit and confirm values; route internally or refer upward; handle emergencies and duplicates; assign personnel; request clarifications; propose resolution with proof photos. | Let AI output become final without manual staff review; auto-close or auto-resolve cases. |
| **Lupon Member / Case Encoder** | Yes (`staff`) | Position `lupon_member`, Preset `case_encoder` | Record walk-in complaints and blotter entries; record party profiles; schedule hearings; generate summonses; view non-confidential cases and confidential cases they encoded themselves. | View other confidential cases (for example minors' or VAWC cases) or modify system settings and backups. |
| **Case Reviewer (Kagawad Peace & Order)** | Yes (`staff`) | Position `kagawad_peace_order`, Preset `case_reviewer` | View all cases including confidential disputes; view reports queue and Reports Center exports. | Modify system settings, user accounts, or database backups. |
| **Barangay Captain** | Yes (`admin`) | Position `punong_barangay` | Everything Staff can do, plus manage users, categories, offices, routing rules, emergency contacts, sensitive places, priority weights, response templates; view dashboard analytics and security audit log; export CSV. Holds full admin privileges. | Bypass audit logging for overrides or setting changes. |
| **Barangay Secretary** | Yes (`admin`) | Position `barangay_secretary` | Holds full administrative authority per Secretary Q6 (approves partner agencies, manages settings, signs notices, manages case docket). Holds `role = 'admin'` with position `barangay_secretary` (NOT a separate role). | Bypass audit logging for overrides or setting changes. |
| **Agency User** | Yes (`agency`) | None (External Office link via `users.office_id`) | View assigned external referrals in Partner Portal; accept referrals; download endorsement packets; post progress and resolution updates; manage agency team. Requires verified email and approved registration. | Access internal barangay reports, public submission queues, or confidential blotter cases. |
| **AI Service (Google Gemini)** | N/A (external API) | N/A | Transcribe speech audio to text strictly as data (never executes spoken commands or repeats personal names); suggest category, urgency rating, executive summary, photo description, photo mismatch alert, emergency flags, duplicate candidates; generate aggregate dashboard insights. | Route, merge, close, resolve, modify final records, or contact police directly. |

### Permissions & Presets Architecture
- **Granular Permissions**: Exactly **21 permission slugs** exist in code (seeded in `RequiredDataSeeder.php`): `settings.manage`, `fiscal_year.manage`, `templates.manage`, `backups.manage`, `personnel.manage`, `users.manage`, `reports.view`, `reports.log_phone`, `reports_center.view`, `reports_center.export`, `cases.view`, `cases.view_confidential`, `cases.create`, `cases.edit`, `cases.summons`, `cases.escalate`, `agencies.manage`, `referrals.manage`, `imports.manage`, `dashboard.view`, `audit.view`.
- **Permission Presets**: Defined in `config/permission_presets.php`:
  1. `staff_full` (18 permissions): Standard full operational rights across reports, cases, personnel, and referrals without confidential case access or system backups.
  2. `case_encoder` (5 permissions): Dispute case intake, editing, hearings, and summons generation for non-confidential cases.
  3. `reports_analyst` (3 permissions): Read-only reporting and dashboard analytics export access.
  4. `case_reviewer` (5 permissions): Review of all dispute cases including confidential cases, reports queue, and Reports Center (`cases.view`, `cases.view_confidential`, `reports.view`, `reports_center.view`, `dashboard.view`).
- **Summons vs. Working Days Escalation Policy**: Case escalation is strictly governed by the **15-working-day statutory period** (confirmed by the Barangay Secretary, Q20/Q21) rather than a summons count. Summons counts (`sequence_no`) are maintained purely for administrative record-keeping; `cases.summons_limit` in `SummonsCounterService` is display-only/legacy. No automatic escalation or status change ever occurs on summons count or deadline expiration ("The AI suggests, the rules route, the officials decide").
- **Admin Exemption**: Users holding `role = 'admin'` automatically bypass explicit permission checks via `User::hasPermission()`. Positions do not grant permissions on their own.

---

## 3. Information Collected & Data Privacy

### Information Flow
- **Resident Submits (Required)**: Description (minimum 10 characters; English, Filipino, Bisaya, or Chavacano), at least one location method (device GPS, tapped pin, draggable pin, or purok/landmark), and consent checkbox (`consent_at`).
- **Resident Submits (Optional)**: Category ("Choose a category (optional)", none preselected; optional on citizen form), up to 5 photos (JPG/PNG, combined max 5 MB, compressed client-side), reporter name, mobile number, email address, anonymous toggle (which discards and hides contact fields), a single safety checkbox (*"Someone is in danger right now"*), and an optional **voice recording** ("Quick report by voice" strip at the top with audio file upload fallback, max `voice.max_seconds` [default 120s]).
- **Staff Submits (Hotline Phone Reports)**: Desk officers log incoming telephone complaints via the "Record phone report" form (`reports.log_phone` permission required), capturing optional audio, caller details, and mandatory verbal consent confirmation (`consent_recorded_by`).
- **Voice Intake & Form Fill Flow ("Record First, AI Fills the Form")**:
  - The recording comes FIRST and drives the form. A dedicated single Gemini call analyzes the speech audio and returns the verbatim transcript plus structured candidate fields (`caller_name`, `caller_phone`, `caller_email`, `wants_anonymous`, `purok`, `landmark`, `category`, `urgency`, `emergency`, `not_stated`).
  - The form is then pre-filled; every AI-filled field is labeled "From recording" and remains fully editable; every field not stated by the speaker displays muted italic "Not provided" (stored as `NULL`, never the literal string).
  - The speech model returns BOTH raw `transcript` (what was said, lightly cleaned, same language, not translated) AND `description` (the problem statement in the speaker's language with the caller's name, phone numbers, email, and personal contact details completely REMOVED). The form description textarea receives `description`, never the raw transcript.
  - Raw `transcript` is staff-only, stored in `report_audios.transcript`, and returned ONLY by the staff analyze endpoint (`/admin/reports/phone/analyze`). The resident analyze endpoint (`/report/voice/analyze`) never returns the raw transcript or urgency. If the speaker clearly described an active problem type, `category` is returned as an editable suggestion labeled "From recording".
  - Recorded or uploaded audio is stored strictly on the private disk with randomized UUID filenames.
  - `reports.source` records report origin (`web`, `voice`, or `phone_log`).
  - Audio records live in `report_audios` with status tracking (`pending`, `done`, `needs_verification`, `failed`, `mock`), `extracted_data` (non-contact extraction for unattached audio), `ai_description`, and `provenance` JSON (recording field flags and `edited` booleans).
  - **The Key Uncertainty Rule**: **If the audio is uncertain or the AI cannot understand it, nothing is guessed and the decision goes to staff.** When `clarity = 'unclear'`, empty transcript, invalid JSON, or API timeout/error occurs, `transcript_status` is set to `needs_verification` (or `failed`); NO fields are pre-filled (all remain "Not provided"), partial text is kept only as an unverified draft, and forward routing remains blocked until authorized staff listen to the recording and verify the content.
- **AI Output (Stored Separately in `report_ai_results`)**: Suggested category, urgency (Low/Medium/High/Critical), summary, photo description, photo-text mismatch flag, emergency flag, key facts.
- **Staff Add**: Final category and urgency, assigned office and staff member, internal notes (staff-only), resolution proofs (up to 5 images), audio verification notes/timestamps, and mandatory explanation notes for referrals, merges, or `no_action`.
- **System Logs**: Every status transition, every staff override of an AI-suggested value, and every audio access/verification action (who, when, old value, new value, note). Transcripts and extracted contact details are never logged in application or audit logs.

### Data Privacy (Republic Act 10173 - Data Privacy Act of 2012)
1. **PII Isolation & Voice Intake Rule**:
   - For standard web reporting, typed reporter name, phone number, and email are staff-only and are **never** transmitted to Google Gemini or third-party APIs.
   - For voice intake (Decision 79), spoken audio is sent to Google Gemini under explicit user consent to extract spoken contact information and incident details. Spoken PII is strictly stripped from the returned `description`, raw transcripts are staff-only and never reused in downstream text triage prompts or public views, anonymous submissions drop contact details on the server, and no transcripts or PII are logged in application or audit logs.
2. **Audio Data Privacy (Planned Prototype)**:
   - Voice recordings are stored strictly on the private storage disk (`storage/app/private/audio/`) with cryptographically random filenames.
   - Audio is streamed strictly via an authenticated, permission-checked endpoint (`GET /admin/reports/{report}/audio` with HTTP Range support and `Cache-Control: private, no-store`).
   - Voice audio is **never** exposed on the public map (`/map`), nearby reports API (`/api/nearby-reports`), resident tracking page (`/track/{code}`), or notification emails.
   - The transcription prompt treats audio strictly as untrusted data (never executes spoken instructions) and directs the model not to repeat personal names.
   - The consent checkbox and privacy notice (English and Filipino) explicitly inform residents that voice recordings are processed by AI to produce text and are accessible to authorized barangay personnel only.
   - Because biometric voice characteristics are identifiable, the anonymous toggle displays an informative warning banner if a voice recording is attached.
   - Desk-officer phone intake requires a mandatory checkbox certifying verbal consent from the caller.
   - **Audio Retention & Purging**: Setting `voice.audio_retention_days` (default 90 days, unconfirmed placeholder) controls lifecycle retention. A scheduled command (`app:purge-audio`) purges expired audio files and orphaned uploads (> 24h old) while permanently retaining the text transcript, verification metadata, and report record. Audio playback, download, verification, and deletion events are immutably logged to `audit_logs`. Audio assets are included in system backup archives (`app:backup`).
3. **Resident Communications**: Automated emails to residents include only the tracking code, status change, staff public note, and tracking URL—never reporter PII, photos, audio clips, or raw report descriptions.
4. **Public Transparency Guardrails**: The public map (`/map`) and nearby reports API (`/api/nearby-reports`) display only category, purok, and status. Descriptions, photos, audio attachments, reporter details, and all emergency-flagged incidents are strictly hidden.

---

## 4. Geographic Scope & Bounding

The pilot deployment is clamped to the administrative territory of **Barangay Calarian, Zamboanga City**:
- **Geographic Bounding Box**: Latitude `6.905` to `6.940`, Longitude `122.060` to `122.098`.
- **Map Center**: Centered on Barangay Calarian proper (bounded in Leaflet via tile layer clamping).
- **Puroks Covered**: Seeded with local Calarian puroks (e.g., Purok 1 through Purok 7, Calarian proper).

---

## 5. Languages Supported

- **Resident Text & AI Understanding**: English, Filipino (Tagalog), Bisaya (Cebuano), and Chavacano. Google Gemini parses mixed dialects; unclear reports default to `unclassified` for manual staff review.
- **Resident Form Interface**: Bimodal UI labels available in English and Filipino (one toggle per page).
- **Spoken Audio Transcription & Dialect Limits (Planned Prototype)**:
  - English and Filipino are expected to transcribe reliably.
  - Bisaya (Cebuano) transcribes partially.
  - Chavacano is expected to be least reliably transcribed by generalized cloud models.
  - Code-switching (Taglish, Chavacano-Tagalog), low-bitrate phone quality audio, background street/ambient noise, and local Calarian sitio or landmark names reduce automated transcription accuracy.
  - **The Core Uncertainty Rule**: **If the audio is uncertain or the AI cannot understand it, nothing is guessed and the decision goes to staff.**
  - Dialect-specific speech recognition models remain deferred. A manual transcription accuracy check (10 to 15 real recordings per language, evaluated on semantic intent and location capture rather than verbatim literal parity) is planned prior to production rollout.

---

## 6. Deferred Features (Do NOT Build)

The following items from preliminary academic requirements or legacy proposals are officially **Deferred**:
- **Real Telephony & Softphone Integration**: PBX, SIP trunking, Twilio/cellular voice gateway integration, softphone dialers, and automated inbound call dispatching. (Replaced in the prototype by simple desk-officer browser recording form for hotline intake).
- **Live Call Recording**: Recording of active two-way cellular or landline telephone calls during speech.
- **Real-Time (Streaming) Transcription**: Streaming WebSocket/audio transcription during recording.
- **SMS Gateway Broadcasts**: Direct cellular SMS delivery for tracking codes or alert broadcasts (system relies on on-screen code display, copy buttons, and transactional email).
- **Voice-Language Dialect Audio Processing**: Specialized custom acoustic speech models for Chavacano/Bisaya dialects.
- **Resident Registration/Portal**: Native mobile app builds, password-protected citizen accounts, or citizen identity verification tiers.

---

## 7. Tech Stack & Constraints

- **Backend**: PHP 8.4+, Laravel Framework 13, Eloquent ORM.
- **Database**: SQLite (local development and automated PHPUnit testing), MySQL (production target).
- **Frontend**: Blade templates, Tailwind CSS v4 with `@tailwindcss/vite`, FontAwesome Free 7 icons.
- **Mapping**: Leaflet.js 1.9 with OpenStreetMap tiles (bundled locally via Vite; no external script CDNs in production).
- **Analytics**: Chart.js 4.6 for dashboard visualizations.
- **AI Service**: Google Gemini REST API (`gemini-2.5-flash`) via Laravel `Http` facade.
- **Hosting / Environment**: Local environment (`php artisan serve`, `npm run dev`) or containerized standard LAMP stack.

---

## 8. Unconfirmed Values & Settings Register

Every value below lives in the database (`settings` table or reference seed tables) and displays an **"Unconfirmed" / "Pending confirmation"** badge in the UI until an authorized administrator confirms it at `/admin/settings/pending`. 

> **Seeding Guarantee**: All placeholder records are seeded via `RequiredDataSeeder` using `firstOrCreate` to prevent clobbering administrator edits during redeployment or migrations.

### 8.1 Settings Keys (`settings` table)

| Key | Type | Default Value | Group | Source Question | Operational Purpose |
| :--- | :--- | :--- | :--- | :--- | :--- |
| `barangay.name` | `string` | `Barangay Calarian` | `general` | Q1 | Official barangay name on headers & summonses |
| `barangay.city` | `string` | `Zamboanga City` | `general` | Q2 | Territorial jurisdiction city/municipality |
| `barangay.province` | `string` | `Zamboanga del Sur` | `general` | Q3 | Province of the barangay |
| `barangay.region` | `string` | `Region IX (Zamboanga Peninsula)` | `general` | Q4 | Administrative region |
| `barangay.hall_address` | `string` | `Calarian, Zamboanga City, 7000` | `general` | Q5 | Hearing notice and summons venue address |
| `barangay.contact_number` | `string` | `0917-123-4567` | `general` | Q6 | Official contact number |
| `barangay.email` | `string` | `info@calarian.gov.ph` | `general` | Q7 | Official communication email |
| `numbering.case.format` | `string` | `{MM}-{SEQ:3}-{YEAR}` | `numbering` | Q9, C5 | Conciliation case docket format ([CONFIRMED, C5]; flagged mismatch: code/UI `CASE-{YEAR}-{SEQ:5}` and `CASE-2026-00001` require correction) |
| `numbering.blotter.format` | `string` | `{MM}-{SEQ:3}-{YEAR}` | `numbering` | Q9, C5 | Incident blotter numbering format ([CONFIRMED, C5]; flagged mismatch: UI `BLOT-2026-00001` requires correction) |
| `numbering.referral.format` | `string` | `{MM}-{SEQ:3}-{YEAR}` | `numbering` | Q9, C5 | External referral numbering scheme per Secretary answer ([CONFIRMED, C5]; referral prefix `REF-YYYY-NNNN` remains unconfirmed) |
| `numbering.summons.format` | `string` | `SUM-YYYY-NNNN` | `numbering` | Q10 | Summons notice numbering format ([PROPOSED, unconfirmed]) |
| `referral.follow_up_days` | `integer` | `3` | `referrals` | Q12, C6 | Agency follow-up reminder interval (days; referral letters only) |
| `records.lookback_years` | `integer` | `4` | `general` | Q8, C4 | Years of legacy records maintained |
| `cases.enabled` | `boolean` | `true` | `cases` | Adviser | Master switch for dispute case module |
| `cases.mediation_deadline_days` | `integer` | `15` | `cases` | Q20, Q25, C11 | Mediation period limit (working days) |
| `cases.conciliation_deadline_days` | `integer` | `15` | `cases` | Q25, C11, P2 | Conciliation period limit (working days, [PROPOSED unit]) |
| `cases.conciliation_extension_days` | `integer` | `15` | `cases` | Q25, C11, P2 | Maximum extension for conciliation (working days, [PROPOSED unit]) |
| `cases.deadline_day_type` | `string` | `working` | `cases` | Q20, Q25, C11 | Working days vs calendar days computation |
| `cases.deadline_warning_days` | `integer` | `3` | `cases` | Q27 | Days before deadline to flag warning |
| `calendar.holidays` | `json` | `[]` | `cases` | Q25 | Specific non-working holidays excluded from clock |
| `cases.default_venue` | `string` | `Barangay Hall` | `cases` | None | Default hearing venue |
| `cases.cfa_signer_position` | `string` | `pangkat_chairman` | `cases` | Q5, C3 | Signatory position for CFA document |
| `agencies.registration_open` | `boolean` | `true` | `agencies` | Q28, C15 | Public partner agency registration switch |
| `agencies.require_id_document` | `boolean` | `true` | `agencies` | Q31, C15 | Mandatory proof upload for agency registration |
| `privacy.case_retention_years` | `integer` | `5` | `privacy` | Q36 | Case record retention threshold |
| `reports.default_format` | `string` | `print` | `reports` | Q33 | Default report rendering (`print`, `pdf`, `excel`) |
| `reports.paper_size` | `string` | `a4` | `reports` | None | Default paper size (`a4`, `letter`, `legal`) |
| `hotline.vawc.national` | `string` | `911` | `hotlines` | P9 | National emergency hotline for screening screen (Planned) |
| `hotline.vawc.pnp` | `string` | `0919-777-7377` | `hotlines` | P9 | PNP Aleng Pulis hotline (Planned) |
| `hotline.bcpc.national` | `string` | `163` | `hotlines` | P9 | Bantay Bata hotline (Planned) |
| `hotline.vawc.barangay` | `string` | `null` | `hotlines` | P9 | Local Barangay Calarian VAW Desk number (Planned) |
| `hotline.bcpc.cswdo` | `string` | `null` | `hotlines` | P9 | Zamboanga City CSWDO hotline (Planned) |
| `reminders.settlement_compliance` | `boolean` | `false` | `cases` | P10 | Optional reminder when settlement terms fall due (Planned) |
| `reminders.pangkat_convening` | `boolean` | `false` | `cases` | P10 | Optional reminder 3 days after Pangkat constitution (Planned) |
| `reminders.kp_accomplishment_yearly`| `boolean` | `false` | `cases` | P10, C16 | Optional reminder for annual KP accomplishment report (Planned) |

#### Unconfirmed Numbering Formats Table
| Entity | Confirmed Standard Format | Prototype / Legacy Format (Flagged Mismatch) | Confirmation Status |
| :--- | :--- | :--- | :--- |
| **Case Docket Number** | `{MM}-{SEQ:3}-{YEAR}` (e.g. `11-001-2026`) | `CASE-{YEAR}-{SEQ:5}` / `CASE-2026-00001` | **CONFIRMED [C5]**; UI/Code needs update |
| **Blotter Number** | `{MM}-{SEQ:3}-{YEAR}` (e.g. `11-001-2026`) | `BLOT-2026-00001` | **CONFIRMED [C5]**; UI/Code needs update |
| **Referral Number** | `{MM}-{SEQ:3}-{YEAR}` (e.g. `11-001-2026`) | `REF-YYYY-NNNN` / `REF-{YEAR}-{SEQ:4}` | Confirmed format, but `REF-` prefix unconfirmed |
| **Summons Number** | `SUM-YYYY-NNNN` (proposed) | `SUM-{YEAR}-{SEQ:4}` | **[PROPOSED]**, Unconfirmed |
| **Certificate to File Action (CFA)**| Unconfirmed format | `CFA-{YEAR}-{SEQ:4}` | **[PROPOSED]**, Unconfirmed |

| `priority.urgency` | `integer` | `20` | `priority` | Settings | Priority score weight for urgency level |
| `priority.category` | `integer` | `10` | `priority` | Settings | Priority score weight for category risk |
| `priority.sensitive_place` | `integer` | `25` | `priority` | Settings | Priority score weight for sensitive place proximity |
| `priority.supporting_reports` | `integer` | `8` | `priority` | Settings | Priority score weight for supporting reports count |
| `priority.age_days` | `integer` | `2` | `priority` | Settings | Priority score weight for age in days |
| `voice.enabled` | `boolean` | `true` | `voice` | Unconfirmed | Master toggle for voice recording & intake |
| `voice.max_seconds` | `integer` | `120` | `voice` | Unconfirmed | Maximum voice recording duration in seconds |
| `voice.audio_retention_days` | `integer` | `90` | `voice` | Unconfirmed | Audio file retention days (counted from report closure for attached audio) |
| `voice.demo_sample_enabled` | `boolean` | `false` | `voice` | Unconfirmed | Enables bundled sample audio button for offline demo |
| `voice.extract_contact_from_audio` | `boolean` | `true` | `voice` | Unconfirmed | Allows AI to extract spoken caller contact on staff phone reports |
| `voice.resident_extract_contact` | `boolean` | `false` | `voice` | Unconfirmed | Allows AI to extract contact on resident recordings (default false; residents type contact) |
| `voice.resident_recording_enabled` | `boolean` | `true` | `voice` | Unconfirmed | Enables voice recorder on resident report form |
| `voice.daily_analyze_limit` | `integer` | `100` | `voice` | Unconfirmed | Daily limit for AI voice analyses before falling back to manual entry |

### 8.2 Seed-Table Placeholders (`is_confirmed = false`)

#### Positions (`positions` table)
- `punong_barangay` (Punong Barangay / Captain) — `is_signatory_default = true`
- `barangay_secretary` (Barangay Secretary) — `is_signatory_default = true`
- `lupon_member` (Lupon Member / Conciliator) — `is_signatory_default = false`
- `barangay_tanod` (Barangay Tanod / Peace Officer) — `is_signatory_default = false`
- `kagawad_peace_order` (Barangay Kagawad - Peace & Order) — `is_signatory_default = false`

#### Signatory Assignments (`signatory_assignments` table)
- `summons`: Prepared by `barangay_secretary`, Presided by `punong_barangay`
- `case_endorsement`: Prepared by `barangay_secretary`, Approved by `punong_barangay`
- `referral_letter`: Prepared by `barangay_secretary`, Approved by `punong_barangay`
- `settlement_agreement`: Attested by `punong_barangay`, Approved by `punong_barangay`
*(For `certificate_to_file_action`, `SignatoryResolver` resolves Pangkat Chairman or case-specific personnel)*.
*(Note: Current codebase contains 2 fallback views—`resources/views/admin/reports-center/print.blade.php:137,142` and `show.blade.php:269,274`—that hardcode "Barangay Secretary" and "Punong Barangay" inside `@empty` fallback blocks when no signatories are configured. Remediation to blank signature lines is tracked in TASKS.md Task 14)*.

#### Case Types (`case_types` table)
- Non-Confidential by default: Physical injury, Noise disturbance, Boundary or property dispute, Theft, Domestic or family dispute, Threats or verbal abuse, Unpaid debt, Damage to property, Vehicular accident, Other.
- Confidential by default (`is_confidential_default = true`): BCPC case (child protection), VAWC case (violence against women and children).

#### Escalation Destinations (`escalation_destinations` table)
- Municipal Trial Court in Cities (MTCC)
- City Prosecutor Office
- PNP Station 8 (Ayala/Calarian)
- City Social Welfare and Development Office (CSWDO)

#### Report Templates (`report_templates` table)
- `kp_accomplishment`: Katarungang Pambarangay annual accomplishment report (DILG)
- `case_summary`: Monthly case summary by type and status (DILG)
- `purok_category_matrix`: Cross-tabulation of incidents and disputes by purok and category
- `problem_reports_summary`: Public incident reports summary
- `referral_register`: Log of referrals dispatched to external agencies
- `summons_log`: Sequential summons and service record
- `personnel_list`: Active directory of barangay officials and terms
- `year_over_year`: Comparative annual metrics

---

## 9. Open Questions & Clarifications with Barangay Officials

The following operational questions were surfaced during the Barangay Secretary questionnaire review or prompt design updates and remain pending confirmation by barangay leadership and the academic adviser:

### Group A: Working Answers Adopted for Now [PROPOSED, pending confirmation]
1. **Mediation Clock Start Date**: Does the 15-working-day mediation clock start on the date of the first meeting of parties before the Punong Barangay (`first_meeting_on`) ([PROPOSED, P2]), or from docketing (`filed_on`) / summons service? [Provisionally resolved to first-meeting date].
2. **Conciliation Unit and Extension**: The Secretary stated "another 15 to 30 days" for conciliation ([CONFIRMED, C11]). Is conciliation counted in working days ([PROPOSED, P2]), and who decides the extension (Punong Barangay or Pangkat Chairman)?
3. **Non-Appearance Procedures**:
   - *Respondent absent*: Does the barangay record `failed_to_appear`, bar connected counterclaims, and constitute the Pangkat rather than issue an immediate CFA ([PROPOSED, P3])? What does the barangay actually record and do within the 15 working days?
   - *Complainant absent*: Does the barangay dismiss the case upon unexcused failure to appear after due notice ([PROPOSED, P3])?
4. **Physical Logbook Form & Extracts**: Does the physical blotter logbook use series, page, and entry numbers ([PROPOSED, P4])? Can the team inspect a sample filled-in page? Is the logbook entry the primary intake record, with the "Barangay Blotter" document serving as a certified extract issued on request ([PROPOSED, P5])?
5. **Numbering Reset and Prefixes**: Do blotter entries and formal complaint cases share a single numerical sequence counter, or run separate counters ([PROPOSED, P4a])? Do summons (`SUM-`), CFA, and referral numbers use `{MM}-{SEQ:3}-{YEAR}` or standard prefixes like `REF-YYYY-NNNN`?
6. **Pre-Summons Eligibility Screening**: Before issuing summons, does staff verify legal eligibility items (parties are natural individuals residing in the same barangay, dispute not excluded by law, no parties in police custody) alongside classification by case type ([PROPOSED, P1])?
7. **Statutory Conciliation Sequence**: Confirm the complete sequence ([PROPOSED, P7]): complaint intake & classification -> summons -> mediation (15 working days from first meeting) -> Pangkat constitution -> conciliation (15 working days) -> amicable settlement OR CFA upon complainant request. (Secretary Q18 omitted the Pangkat step).
8. **Document Signatories Verification**: Confirm proposed signatory mapping ([PROPOSED, P8]): Notice of hearing (Pangkat Chairman), Amicable settlement (parties + PB or Pangkat Chair), Blotter extract (Secretary prepares, PB certifies), Complaint intake receipt (Secretary or Lupon member), CFA with Pangkat (Pangkat Chairman) vs CFA without Pangkat (Secretary prepares, PB signs).
9. **Confidentiality Screening & Guidance**: Review draft VAWC and BCPC guidance wording in English and Filipino ([PROPOSED, P9]), and provide official contact numbers for the Barangay Calarian VAW Desk and City CSWDO.
10. **Automated Reminders Scope**: Is the 3-day follow-up interval applicable strictly to external referral letters (Mode B) ([PROPOSED, P10; CONFIRMED, C6])? Does the Secretary wish to activate optional reminders for settlement compliance dates, 3-day Pangkat convening deadlines, or annual KP accomplishment reports?
11. **Academic Adviser Question**: Should residents be allowed to submit dispute complaints online as a pending intake request only ([PROPOSED, P6; DECIDED, D4]), or should dispute intake strictly remain walk-in only at the barangay hall?

### Group B: Decided by the Team (To Inform the Secretary)
12. **Guest and Anonymous Community Reporting ([DECIDED, D7])**: The team has decided that community problem reports (Report Queue) may be submitted without creating an account and anonymously, with optional tracking codes ([PROPOSED, P11]). (The Secretary answered "Not sure" in Q14). Inform the Secretary of this decision, confirm if the barangay has any operational objections, and confirm whether tracking codes are desired. (Complaints remain strictly identified and in-person; anonymity is not offered for disputes).

---

## 10. Acceptance Criteria

1. Resident submits a report in under 2 minutes on a 375px mobile device without creating an account and receives a unique tracking code (`BRG-XXXXXX`).
2. Gemini AI processes report in background; if the API times out (30s) or fails, the report is saved safely in status `submitted` with `ai_status = failed`.
3. Emergency reports immediately surface a 911 hotline prompt, hide from public maps, alert officials, and pin to the top of the queue.
4. Staff can review AI recommendations, compare side-by-side with resident inputs, and make final decisions before routing.
5. Issues outside barangay authority generate structured external referrals (`REF-YYYY-NNNN`) with printable letters and tracking.
6. Resolution requires proof photos and staff notes, transitioning to `awaiting_confirmation` until the resident confirms or reopens.
7. Voice reporting provides "record first, AI fills the form" intake for resident reporting and staff hotline intake; a recording pre-fills the form with "From recording" labels; unstated fields show "Not provided"; unclear audio is never guessed (`needs_verification`), keeping fields empty and blocking routing until staff verify; resident anonymous reports drop and never store spoken contact details; raw transcripts are staff-only.
