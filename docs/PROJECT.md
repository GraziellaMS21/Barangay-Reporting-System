# Project Specification: Barangay Problem Reporting System

Municipal GovTech Civic Issue Management System for local governance in **Barangay Calarian, Zamboanga City** (single pilot barangay).

Client: Barangay Calarian, Zamboanga City  
Team: Group 3 (SynohDev), Western Mindanao State University, Software Engineering 1  
Core Principle: *"The AI suggests, the rules route, the officials decide."*

---

## 1. Problem Statement & Purpose

Traditional incident reporting in barangays relies on physical walk-ins to the barangay hall, phone calls to personal mobile numbers, or unorganized group chats. This leads to lost requests, no accountability, lack of status visibility for residents, and difficulty triaging critical issues vs. routine maintenance.

The system provides a lightweight, mobile-responsive web platform where residents can submit civic concerns without friction, while barangay officials receive AI-assisted triage with human-in-the-loop review, automated SLA tracking, structured external agency referrals via a dedicated Partner Agency Portal, and formal Katarungang Pambarangay case blotter/conciliation management.

> [!NOTE]
> **Active Scope**: Incident reporting, external agency referrals with Partner Agency Portal, and Katarungang Pambarangay dispute conciliation cases are in scope; the case module is pending the adviser's confirmation (it can be switched off with `cases.enabled`), and importing the barangay's last 4 years of old records is a stretch item. Voice calls, softphone, and cellular SMS broadcasts remain deferred.

---

## 2. User Roles & Permissions

Coarse access tier is governed strictly by `users.role` with three allowed values: `admin`, `staff`, and `agency` (`personnel` is NOT a role; it is an assignable directory of people).

| Role | Account Tier | Position / Preset | Can Do | Must Not |
| :--- | :--- | :--- | :--- | :--- |
| **Resident** | **None** (guest, tracking code `BRG-XXXXXX`) | None | Submit a report; see emergency contacts; track status and public timeline; answer staff clarification questions; confirm or reopen resolution and rate 1–5 stars; view the limited public transparency map. | See personal identification or full details of other residents' reports. |
| **Staff (Desk Officer / Tanod)** | Yes (`staff`) | Position `barangay_tanod`, Preset `staff_full` | Review the priority queue; view 3-way comparative triage (Resident vs. AI vs. Final); edit and confirm values; route internally or refer upward; handle emergencies and duplicates; assign personnel; request clarifications; propose resolution with proof photos. | Let AI output become final without manual staff review; auto-close or auto-resolve cases. |
| **Lupon Member / Case Encoder** | Yes (`staff`) | Position `lupon_member`, Preset `case_encoder` | Record walk-in complaints and blotter entries; record party profiles; schedule hearings; generate summonses; view non-confidential cases and confidential cases they encoded themselves. | View other confidential cases (for example minors' or VAWC cases) or modify system settings and backups. |
| **Case Reviewer (Kagawad Peace & Order)** | Yes (`staff`) | Position `kagawad_peace_order`, Preset `case_reviewer` | View all cases including confidential disputes; view reports queue and Reports Center exports. | Modify system settings, user accounts, or database backups. |
| **Barangay Captain** | Yes (`admin`) | Position `punong_barangay` | Everything Staff can do, plus manage users, categories, offices, routing rules, emergency contacts, sensitive places, priority weights, response templates; view dashboard analytics and security audit log; export CSV. Holds full admin privileges. | Bypass audit logging for overrides or setting changes. |
| **Barangay Secretary** | Yes (`admin`) | Position `barangay_secretary` | Holds full administrative authority per Secretary Q6 (approves partner agencies, manages settings, signs notices, manages case docket). Holds `role = 'admin'` with position `barangay_secretary` (NOT a separate role). | Bypass audit logging for overrides or setting changes. |
| **Agency User** | Yes (`agency`) | None (External Office link via `users.office_id`) | View assigned external referrals in Partner Portal; accept referrals; download endorsement packets; post progress and resolution updates; manage agency team. Requires verified email and approved registration. | Access internal barangay reports, public submission queues, or confidential blotter cases. |
| **AI Service (Google Gemini)** | N/A (external API) | N/A | Suggest category, urgency rating, executive summary, photo description, photo mismatch alert, emergency flags, duplicate candidates; generate aggregate dashboard insights. | Route, merge, close, resolve, modify final records, or contact police directly. |

### Permissions & Presets Architecture
- **Granular Permissions**: Exactly **20 permission slugs** exist in code: `settings.manage`, `fiscal_year.manage`, `templates.manage`, `backups.manage`, `personnel.manage`, `users.manage`, `reports.view`, `reports_center.view`, `reports_center.export`, `cases.view`, `cases.view_confidential`, `cases.create`, `cases.edit`, `cases.summons`, `cases.escalate`, `agencies.manage`, `referrals.manage`, `imports.manage`, `dashboard.view`, `audit.view`.
- **Permission Presets**: Defined in `config/permission_presets.php`:
  1. `staff_full` (16 permissions): Standard full operational rights across reports, cases, personnel, and referrals without confidential case access or system backups.
  2. `case_encoder` (5 permissions): Dispute case intake, editing, hearings, and summons generation for non-confidential cases.
  3. `reports_analyst` (3 permissions): Read-only reporting and dashboard analytics export access.
  4. `case_reviewer` (6 permissions): Review of all dispute cases including confidential cases, reports queue, and Reports Center.
- **Admin Exemption**: Users holding `role = 'admin'` automatically bypass explicit permission checks via `User::hasPermission()`. Positions do not grant permissions on their own.

---

## 3. Information Collected & Data Privacy

### Information Flow
- **Resident Submits (Required)**: Category (none preselected), description (minimum 10 characters; English, Filipino, Bisaya, or Chavacano), at least one location method (device GPS, tapped pin, draggable pin, or purok/landmark), and consent checkbox (`consent_at`).
- **Resident Submits (Optional)**: Up to 5 photos (JPG/PNG, combined max 5 MB, compressed client-side), reporter name, mobile number, email address, anonymous toggle (which discards and hides contact fields), and a single safety checkbox: *"Someone is in danger right now"*.
- **AI Output (Stored Separately in `report_ai_results`)**: Suggested category, urgency (Low/Medium/High/Critical), summary, photo description, photo-text mismatch flag, emergency flag, key facts.
- **Staff Add**: Final category and urgency, assigned office and staff member, internal notes (staff-only), resolution proofs (up to 5 images), and mandatory explanation notes for referrals, merges, or `no_action`.
- **System Logs**: Every status transition and every staff override of an AI-suggested value (who, when, old value, new value, note).

### Data Privacy (Republic Act 10173 - Data Privacy Act of 2012)
1. **PII Isolation**: Reporter name, phone number, and email are staff-only. They are **never** transmitted to Google Gemini or third-party APIs.
2. **Resident Communications**: Automated emails to residents include only the tracking code, status change, staff public note, and tracking URL—never reporter PII, photos, or raw report descriptions.
3. **Public Transparency Guardrails**: The public map (`/map`) and nearby reports API (`/api/nearby-reports`) display only category, purok, and status. Descriptions, photos, reporter details, and all emergency-flagged incidents are strictly hidden.

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

---

## 6. Deferred Features (Do NOT Build)

The following items from preliminary academic requirements or legacy proposals are officially **Deferred**:
- **Voice Call Reporting**: UR-04, FR-22, proposal feature 18, "Report by Phone" use case, softphone integration, call recording, retention settings, and audio speech-to-text.
- **SMS Gateway Broadcasts**: Direct cellular SMS delivery for tracking codes or alert broadcasts (system relies on on-screen code display, copy buttons, and transactional email).
- **Voice-Language Dialect Audio Processing**: Chavacano/Bisaya audio speech models.
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
| `numbering.referral.format` | `string` | `REF-{YEAR}-{SEQ:4}` | `numbering` | Q8 | External referral numbering scheme |
| `numbering.case.format` | `string` | `CASE-{YEAR}-{SEQ:5}` | `numbering` | Q9 | Conciliation case docket format |
| `numbering.blotter.format` | `string` | `{MM}-{SEQ:3}-{YEAR}` | `numbering` | Q9 | Incident blotter numbering format |
| `numbering.summons.format` | `string` | `SUM-{YEAR}-{SEQ:4}` | `numbering` | Q10 | Summons notice numbering format |
| `referral.follow_up_days` | `integer` | `3` | `referrals` | Q12 | Agency follow-up reminder interval (days) |
| `records.lookback_years` | `integer` | `4` | `general` | Q8 | Years of legacy records maintained |
| `cases.enabled` | `boolean` | `true` | `cases` | Adviser | Master switch for dispute case module |
| `cases.mediation_deadline_days` | `integer` | `15` | `cases` | Q20, Q25 | Mediation period limit (working days) |
| `cases.conciliation_deadline_days` | `integer` | `15` | `cases` | Q25 | Conciliation period limit (working days) |
| `cases.conciliation_extension_days` | `integer` | `15` | `cases` | Q25 | Maximum extension for conciliation (working days) |
| `cases.deadline_day_type` | `string` | `working` | `cases` | Q20, Q25 | Working days vs calendar days computation |
| `cases.deadline_warning_days` | `integer` | `3` | `cases` | Q27 | Days before deadline to flag warning |
| `calendar.holidays` | `json` | `[]` | `cases` | Q25 | Specific non-working holidays excluded from clock |
| `cases.default_venue` | `string` | `Barangay Hall` | `cases` | None | Default hearing venue |
| `cases.cfa_signer_position` | `string` | `pangkat_chairman` | `cases` | Q5 | Signatory position for CFA document |
| `agencies.registration_open` | `boolean` | `true` | `agencies` | Q28 | Public partner agency registration switch |
| `agencies.require_id_document` | `boolean` | `true` | `agencies` | Q31 | Mandatory proof upload for agency registration |
| `privacy.case_retention_years` | `integer` | `5` | `privacy` | Q36 | Case record retention threshold |
| `reports.default_format` | `string` | `print` | `reports` | Q33 | Default report rendering (`print`, `pdf`, `excel`) |
| `reports.paper_size` | `string` | `a4` | `reports` | None | Default paper size (`a4`, `letter`, `legal`) |
| `priority.urgency` | `integer` | `20` | `priority` | Settings | Priority score weight for urgency level |
| `priority.category` | `integer` | `10` | `priority` | Settings | Priority score weight for category risk |
| `priority.sensitive_place` | `integer` | `25` | `priority` | Settings | Priority score weight for sensitive place proximity |
| `priority.supporting_reports` | `integer` | `8` | `priority` | Settings | Priority score weight for supporting reports count |
| `priority.age_days` | `integer` | `2` | `priority` | Settings | Priority score weight for age in days |

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

The following operational questions were surfaced during the Barangay Secretary questionnaire review and remain pending confirmation by barangay leadership:

1. **Mediation Clock Start Date**: Does the 15-working-day mediation clock start on `filed_on` (docketing), the date the first summons is served, or the first hearing date?
2. **Conciliation Calendar**: Are conciliation period extensions granted by the Punong Barangay or by the Pangkat Chairman?
3. **Pangkat Roster**: Is the Pangkat Chairman designated as a standing role or elected per-dispute from the 3-member Pangkat panel?
4. **Numbering Reset Specifics**: Do incident blotter entries and formal conciliation complaints share the same numerical sequence counter or separate sequence counters?
5. **BCPC & VAWC Timeline**: Do BCPC and VAWC disputes follow standard Katarungang Pambarangay 15-day conciliation or immediate referral to specialized agencies without conciliation?
6. **Confidential Case Viewing**: May the specific Lupon member who encoded a confidential case continue viewing it after initial docketing?
7. **Agency Action Authority**: May an external partner agency directly mark a referral as "declined" or "closed", or does final resolution require barangay staff confirmation?
8. **Statutory Privacy Notice**: Does the barangay have a pre-approved Data Privacy Act (RA 10173) consent notice for disputed parties, or will the system's generated draft be confirmed?
9. **Physical Paper Size**: Which paper size is officially required for printed DILG compliance reports (`A4`, `Letter`, or `Legal/Long Bond`)?
10. **Blotter Summary Submission**: Is the blotter incident summary submitted to any city agency, or retained strictly internal?
11. **VAWC Committee Kagawads**: Which specific Kagawads are designated to review minor and child protection cases?
12. **Historical Logbook Ingestion**: For the last 4 years of paper records, who will be responsible for typing non-digitized handwritten blotters into CSV format for WS6 import?

---

## 10. Acceptance Criteria

1. Resident submits a report in under 2 minutes on a 375px mobile device without creating an account and receives a unique tracking code (`BRG-XXXXXX`).
2. Gemini AI processes report in background; if the API times out (30s) or fails, the report is saved safely in status `submitted` with `ai_status = failed`.
3. Emergency reports immediately surface a 911 hotline prompt, hide from public maps, alert officials, and pin to the top of the queue.
4. Staff can review AI recommendations, compare side-by-side with resident inputs, and make final decisions before routing.
5. Issues outside barangay authority generate structured external referrals (`REF-YYYY-NNNN`) with printable letters and tracking.
6. Resolution requires proof photos and staff notes, transitioning to `awaiting_confirmation` until the resident confirms or reopens.
