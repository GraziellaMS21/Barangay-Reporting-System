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

## 8. Acceptance Criteria

1. Resident submits a report in under 2 minutes on a 375px mobile device without creating an account and receives a unique tracking code (`BRG-XXXXXX`).
2. Gemini AI processes report in background; if the API times out (30s) or fails, the report is saved safely in status `submitted` with `ai_status = failed`.
3. Emergency reports immediately surface a 911 hotline prompt, hide from public maps, alert officials, and pin to the top of the queue.
4. Staff can review AI recommendations, compare side-by-side with resident inputs, and make final decisions before routing.
5. Issues outside barangay authority generate structured external referrals (`REF-YYYY-NNNN`) with printable letters and tracking.
6. Resolution requires proof photos and staff notes, transitioning to `awaiting_confirmation` until the resident confirms or reopens.
