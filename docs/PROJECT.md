# Project Specification & System Reference: Barangay Problem Reporting & Management System

Municipal GovTech Civic Issue Management and Dispute Conciliation Platform for local governance in **Barangay Calarian, Zamboanga City** (pilot deployment).

- **Client**: Barangay Calarian, Zamboanga City
- **Team**: Group 3 (SynohDev), Western Mindanao State University, Software Engineering 1
- **Core Operating Mandate**: *"The AI suggests, the rules route, the officials decide."*
- **Authoritative Status**: This document serves as the master business, operational, and architectural specification for the system.
- **Status Labels**:
  - `[IMPLEMENTED]`: Verified in current codebase.
  - `[PARTIALLY IMPLEMENTED]`: Only part of the documented behavior exists in current code.
  - `[APPROVED DESIGN]`: Agreed target behavior not yet fully implemented in code.
  - `[PENDING VALIDATION]`: Requires Barangay Captain, Secretary, Lupon, or adviser confirmation.
  - `[DEFERRED]`: Intentionally outside the current implementation scope.

---

## 1. Project Overview and Scope

### 1.1 Problem Statement & Purpose
Traditional incident reporting and case management in Philippine barangays rely on physical walk-ins to the barangay hall, phone calls to personal mobile numbers, paper logbooks, or unorganized group chats. This leads to lost requests, lack of accountability, no status visibility for residents, and difficulty triaging critical public safety hazards versus routine municipal maintenance.

The system provides a lightweight, mobile-responsive web platform where:
1. **Residents** can submit public civic concerns (and dispute complaint requests) without friction or mandatory account creation, tracking resolution progress via a secure tracking code.
2. **Barangay Officials** receive AI-assisted triage with human-in-the-loop verification, automated priority scoring, structured internal dispatch, and tracked external agency referrals.
3. **Lupon Tagapamayapa Members** manage formal dispute conciliation proceedings under statutory Katarungang Pambarangay rules (Republic Act 7160).

### 1.2 Geographic Territory & Administrative Scope
- **Pilot Location**: Exclusively clamped to the administrative territory of **Barangay Calarian, Zamboanga City** `[IMPLEMENTED]`.
- **Geographic Bounding Box**: Latitude `6.905` to `6.940`, Longitude `122.060` to `122.098` `[IMPLEMENTED]`.
- **Map Center**: Centered on Barangay Calarian proper (bounded in Leaflet via tile layer clamping) `[IMPLEMENTED]`.
- **Territorial Subdivisions**: Seeded with local Calarian puroks (Purok 1 through Purok 7, Calarian proper, with stable `code` slugs and nullable GeoJSON `boundary_geometry` column) `[IMPLEMENTED]`.
- **Provisional Outer Boundary (`resources/gis/calarian_simplified.geojson`)**: Git-tracked barangay-level Polygon (`psgc_status: fuzzy`) configured via `reports.gis.barangay_boundary_path` (overridable via `GIS_BARANGAY_BOUNDARY_PATH`) for outer-boundary checks `[PROVISIONAL — NOT OFFICIALLY VERIFIED]`.
- **Development & Prototype Mock Puroks (`MockPurokBoundarySeeder`)**: Seeds 7 temporary non-overlapping `MOCK PUROK 1`–`MOCK PUROK 7` polygons (`code`: `mock-purok-*`). For the current thesis prototype, these 7 mock boundaries are automatically provisioned in `local` and `testing` environments via `DatabaseSeeder` / `MockPurokBoundarySeeder` (`updateOrCreate` on `code`) and scoped via `Purok::scopeVisibleInCurrentEnvironment()`, serving as the working geographic dataset to demonstrate automated Purok detection. They are visibly disclosed in the UI as simulated/provisional data and are never represented as official boundaries.
- **GIS Boundary Detection Engine, Resident Location Display & Purok Map Visualization (`BoundaryService`, `POST /api/detect-location`, `GET /api/purok-boundaries`, `resident/create.blade.php`)**: Pure-PHP Ray-Casting Point-in-Polygon engine integrated into Step 2 with:
  1. The **Detected Location Information** card showing Barangay ("Calarian" / "Outside provisional boundary"), City ("Zamboanga City"), Purok (e.g., "MOCK PUROK 3 (Simulated)", "Not yet identified", or "Needs manual selection"), and Coordinates (lat/lng to 6 decimal places).
  2. Direct visual rendering on the Step 2 Leaflet map of the provisional Barangay Calarian boundary (dashed outline) and all seven mock Purok polygons with visually distinguishable palette colors and transparent fills.
  3. Interactive features including polygon click-to-pin placement, permanent centered labels, dynamic Purok polygon highlighting upon detection or selection, and an embedded responsive Map Boundaries & Puroks legend with interactive chips.
  4. Full bilingual English/Filipino language support and prototype disclosure notices `[IMPLEMENTED — TR2.4.2, TR2.4.3, TR2.4.3-FIX & TR2.4.3-FIX-2]`. **Official Purok boundaries and official jurisdiction enforcement remain pending** until verified data is supplied by Barangay Calarian.

### 1.3 Active Scope vs. Deferred Features
- **Active Scope (`[IMPLEMENTED]`, `[PARTIALLY IMPLEMENTED]`, or `[APPROVED DESIGN]`)**:
  - Frictionless community problem reporting via web form `[IMPLEMENTED]` and desk-officer telephone intake `[IMPLEMENTED]`.
  - Multi-modal Google Gemini integration for advisory classification, summarization, and photo inspection (`gemini-2.5-flash` executed synchronously via listener) `[IMPLEMENTED]`.
  - Asynchronous background queue architecture for AI processing (`ShouldQueue`) `[APPROVED DESIGN]`.
  - Mode A (Internal Dispatch) and Mode B (External Agency Referrals with `REF-YYYY-NNNN` tracking) `[IMPLEMENTED]`.
  - Resident before-and-after photo resolution verification with satisfaction rating `[PARTIALLY IMPLEMENTED]`.
  - Record-and-transcribe voice reporting prototype (resident audio upload and hotline dictation without telephony) `[IMPLEMENTED]`.
  - Katarungang Pambarangay dispute conciliation docket with mediation tracking (switchable via `cases.enabled`) `[IMPLEMENTED]`.
  - Partner Agency Portal for external referral updates (`/agency/referrals`) `[IMPLEMENTED]`.
  - Annual fiscal year lifecycle, immutable historical records, automated daily database backups, and multi-year global search `[IMPLEMENTED]`.
- **Explicitly Deferred Features (`[DEFERRED]` — Do NOT Build)**:
  - *Real Telephony & Softphone Integration*: No PBX, SIP trunking, Twilio voice gateway, or live call recording.
  - *Real-Time Streaming Transcription*: No live streaming WebSocket speech-to-text during active phone calls.
  - *SMS Gateway Broadcasts*: No cellular SMS gateway broadcasts or automated SMS delivery (relies on web tracking codes and transactional email).
  - *Dialect-Specific Acoustic Models*: No custom speech models for Bisaya or Chavacano.
  - *Mandatory Citizen Accounts*: No native mobile app store builds, password gates, or national ID identity verification tiers for basic community problem reporting.

---

## 2. System Modules and Their Boundaries

The application is structured into **two distinct operational modules joined by an explicit conversion bridge**, backed by shared administration:

```
+---------------------------------------------------------------------------------------------------+
|                                  Resident / Public Interface                                      |
|  [Emergency / 911]  |  [Report a Concern Form]   |  [File a Complaint Intake]   |  [Track / Map]  |
+---------------------------------------------------------------------------------------------------+
                                            │
                                            ▼
+----------------------------------+  [Conversion Bridge]  +---------------------------------------+
|   MODULE 1: Community Reports    | ────────────────────> |      MODULE 2: Cases & Blotter        |
| - 12-Status Incident Lifecycle   | (Converts civil issue | - 8-Status Katarungang Pambarangay    |
| - Gemini AI Triage & Priority    |  to formal dispute)   | - Blotter Logbook vs Formal Complaint |
| - 3-Way Comparative Staff Review |                       | - Mediation & Conciliation Clocks     |
| - SLA & Internal Office Dispatch |                       | - Sequential Summons & Hearings       |
| - External Agency Referral Letters                       | - Amicable Settlement / CFA Generation|
+----------------------------------+                       +---------------------------------------+
                                            │
                                            ▼
+---------------------------------------------------------------------------------------------------+
|                               Shared Administration & Governance                                  |
| - 5-Step Setup Wizard  - Fiscal Year Scoping & Immutability  - Personnel & Signatory Directory    |
| - 21 Granular Permissions & 4 Presets  - Partner Agency Portal  - DILG Reports  - Daily Backups   |
+---------------------------------------------------------------------------------------------------+
```

### 2.1 Module 1: Community Concern Reporting (Report Queue)
- **Covers**: Both community problems (public infrastructure, streetlights, clogged drainage, uncollected garbage, road potholes, stray animals, flooding) and localized incidents. Residents submit through a single **Report a Concern** entry point and do not need to determine the official classification beforehand.
- **Lifecycle**: Governed by the canonical **12-status incident lifecycle** `[IMPLEMENTED]`.
- **User Access**: Open to public residents (guest, anonymous, or registered) without login `[IMPLEMENTED]`.
- **AI Integration**:
  - *Post-Submission Triage (`ProcessNewReport`)*: Multi-modal Gemini AI extracts suggested category, urgency, executive summary, photo description, photo mismatch alert, and emergency keywords asynchronously after report submission `[IMPLEMENTED]`.
  - *Pre-Submission Concern Analysis (`POST /report/analyze-concern`)*: Manual, advisory Step 1 AI assistance providing classification (`community_problem`, `incident`, `possible_formal_complaint`, `uncertain`), summary, optional clarification questions, and explicit category suggestion acceptance `[IMPLEMENTED — TR2.2 & TR2.3]`.
  - *Voice Intake (`POST /report/voice/analyze`)*: Synchronous audio transcription and field prefill `[IMPLEMENTED]`.

### 2.2 Module 2: Katarungang Pambarangay Case Management (Cases Docket)
- **Covers**: Interpersonal civil disputes, neighborhood altercations, minor offenses, and landlord-tenant disagreements governed by statutory Katarungang Pambarangay rules (RA 7160).
- **Lifecycle**: Governed strictly by the canonical **8-status case conciliation lifecycle** (`filed`, `in_mediation`, `in_conciliation`, `settled`, `unsettled`, `escalated`, `dismissed`, `withdrawn`) `[IMPLEMENTED]`.
- **Mediation & Conciliation Clocks**:
  - *Lupon Chairperson Mediation Period*: Provisionally defaulted to **15 calendar days** from the first meeting of parties before the Punong Barangay (following RA 7160 Section 410(b)), pending official/legal validation `[APPROVED DESIGN; PENDING VALIDATION]`.
  - *Pangkat Conciliation Period*: Tracked separately as **15 calendar days** from the date the Pangkat ng Tagapagkasundo convenes, extendable by the Pangkat for another period not exceeding **15 calendar days** in clearly meritorious cases (RA 7160 Section 410(c)) `[APPROVED DESIGN; PENDING VALIDATION]`.
  - *Counting Rules*: Whether deadlines strictly exclude weekends and holidays (working days) or follow statutory calendar days remains an open legal interpretation item marked `[PENDING VALIDATION]`.
- **User Access**: Restricted to authorized barangay personnel (Punong Barangay, Barangay Secretary, Lupon members, records clerks, and Peace & Order Kagawad) `[IMPLEMENTED]`.
- **Master Switch**: Governed by configuration key `cases.enabled` (defaults to `true`; pending formal confirmation by academic adviser) `[IMPLEMENTED]`.

### 2.3 The Conversion Bridge
- **Operational Rule**: Community problem reports and dispute conciliation cases are **never conflated or merged** in the same queue `[IMPLEMENTED]`.
- **Conversion Trigger**: When an open civil community report (e.g., reported noise or drainage obstruction) is determined upon investigation to stem from an active interpersonal neighborhood dispute, authorized staff (`permission: cases.create`) converts the report into a formal Katarungang Pambarangay case `[IMPLEMENTED]`.
- **Reciprocal Relationship**: The civil report transitions to `no_action` with `reason: converted_to_case` (or stays in `in_progress` if municipal field work continues), while a linked case record is created at `filed` `[IMPLEMENTED]`. Bidirectional foreign keys (`reports.case_id` and `cases.source_report_id`) link the entities.
- **Privacy Preservation**: Reporter personal details from an anonymous or guest community report are never automatically copied into dispute party records without explicit human staff verification and citizen consent `[IMPLEMENTED]`.

---

## 3. Community Problem Reporting Workflow

The Community Problem Reporting module follows a structured 7-stage operational lifecycle ensuring that every report is captured, prioritized, verified by human officials, and tracked to audited resolution.

```mermaid
flowchart TD
    classDef startEnd fill:#1e293b,stroke:#0f172a,stroke-width:2px,color:#fff;
    classDef process fill:#f8fafc,stroke:#64748b,stroke-width:1.5px,color:#0f172a;
    classDef gate fill:#fef3c7,stroke:#d97706,stroke-width:2px,color:#78350f;
    classDef internal fill:#eff6ff,stroke:#2563eb,stroke-width:2px,color:#1e3a8a;
    classDef external fill:#f5f3ff,stroke:#7c3aed,stroke-width:2px,color:#4c1d95;
    classDef danger fill:#fef2f2,stroke:#dc2626,stroke-width:2px,color:#991b1b;
    classDef success fill:#f0fdf4,stroke:#16a34a,stroke-width:2px,color:#14532d;

    Start([Resident Web or Phone Intake]):::startEnd --> Validate[Validation & Deduplication]:::process
    Validate --> Persist[Persist Report: Status 'submitted']:::process
    Persist --> AIProcess{AI Triage: Gemini Flash<br/>[APPROVED DESIGN: Async / Current: Sync]}:::gate

    %% AI Branches
    AIProcess -- Success --> CheckEmergency{AI Emergency Detected?}:::gate
    AIProcess -- Failed / Timed Out --> AIFailedQueue[Status 'submitted', ai_status='failed'<br/>Actionable; Queue Badge & Screening: APPROVED DESIGN]:::process
    AIFailedQueue --> HumanReview

    %% Emergency Branches
    CheckEmergency -- Yes --> EmgFlag[Status: 'emergency_flagged' & Surface 911 Card]:::danger
    EmgFlag --> EmgHandle{Emergency Action Type?}:::gate
    EmgHandle -- 911 Call Guidance Only --> EmgExit[Status: 'no_action' emergency_redirected]:::danger
    EmgHandle -- Tanod Perimeter/Safety Response --> ResponseMonitoring[Status: 'in_progress' - Response/Remediation Monitoring]:::internal
    EmgHandle -- External Responder Handled --> ExtEmgReview[Staff Documentation & Photo Verification]:::gate
    ExtEmgReview --> ResponseMonitoring
    EmgFlag -. Downgrade / False Alarm .-> HumanReview

    CheckEmergency -- No --> TriageQueue[Report Queue: Priority Sorted]:::process
    TriageQueue --> HumanReview[Staff 3-Way Review<br/>Mandatory Manual Emergency Screening: APPROVED DESIGN]:::gate
    HumanReview -- Staff Emergency Flag [IMPLEMENTED] --> EmgFlag

    HumanReview --> CheckNeedsInfo{Clarification Needed?}:::gate
    CheckNeedsInfo -- Yes --> AskInfo[Status: 'needs_info' - Send Question]:::process
    AskInfo --> ResidentReply[Resident Submits Answer on Track Page]:::process
    ResidentReply --> ResumeWorkflow[Auto-Resume Previous Active State]:::process
    ResumeWorkflow --> HumanReview

    CheckNeedsInfo -- No --> CheckJurisdiction{Jurisdiction Assessment}:::gate

    %% PATH A: INTERNAL BARANGAY ACTION
    CheckJurisdiction -- Mode A: Internal Authority --> RouteInternal[Status: 'routed' - Assign Office/Crew]:::internal
    RouteInternal --> ResponseMonitoring
    ResponseMonitoring --> Milestone[Log Progress Updates e.g. waiting_materials]:::internal
    Milestone --> ResponseMonitoring
    ResponseMonitoring --> UploadProof[Upload Resolution Proof Photos & Notes]:::internal
    UploadProof --> ProposeFix[Status: 'awaiting_confirmation']:::gate

    %% PATH B: EXTERNAL AGENCY REFERRAL
    CheckJurisdiction -- Mode B: External Authority --> PrepareRef[Generate REF-YYYY-NNNN & Endorsement Letter]:::external
    PrepareRef --> TransmitRef[Status: 'referred' - Transmit to Agency]:::external
    TransmitRef --> FollowUpWait[Provisional 3-Day Follow-Up Tracking<br/>Configurable Default - BR-05]:::external
    FollowUpWait --> AgencyResponse{Agency Feedback?}:::gate
    AgencyResponse -- Remediation Completed [Current Code] --> DirectRefClose[ReferralService::close -> 'resolved']:::external
    AgencyResponse -- Remediation Completed [APPROVED DESIGN] --> AgencyResolved[Staff Moves to 'in_progress' to Log Proof]:::external
    AgencyResolved --> UploadProof
    AgencyResponse -- Declined / Inaction --> RefDeclinedReview[Staff Review Decision Point]:::gate
    RefDeclinedReview -- Re-route Internally --> RouteInternal
    RefDeclinedReview -- Re-refer to Other Agency --> PrepareRef
    RefDeclinedReview -- Mark Non-Actionable --> NonAction

    %% PATH C: NON-ACTIONABLE
    CheckJurisdiction -- Out of Territory / Spam / Unreachable --> NonAction[Select Reason Code & Enter Explanation]:::process
    NonAction --> CloseNoAction[Closed Operational State: 'no_action']:::startEnd
    CloseNoAction -. Authorized Staff Reopening .-> HumanReview

    %% PATH D: RESIDENT CONFIRMATION & CLOSURE
    ProposeFix --> ResidentChoice{Resident Review on Track Page}:::gate
    ResidentChoice -- 'Yes, Issue is Fixed' --> FixConfirmed[Status: 'resolved' - 1-5 Star Rating]:::success
    ResidentChoice -- 'No, Still Needs Work' --> ReopenIssue[Status: 'reopened' - Re-enter Queue]:::danger
    ReopenIssue --> HumanReview

    ResidentChoice -- Citizen Does Not Confirm --> StaffManualCheck{Staff Observational Period [BR-10]}:::gate
    StaffManualCheck -- Verified Fixed on Site --> ManualClose[Staff/Admin Manual Close]:::process
    ManualClose --> FixConfirmed

    FixConfirmed --> Archive([Archived Record]):::startEnd
    FixConfirmed -. Latent Failure / Re-inspection .-> ResponseMonitoring
```

### Detailed Operational Steps
1. **Intake & Persist**: Resident submits online (`/report`) or desk officer logs phone intake (`/admin/reports/phone`). Record is stored immediately in status `submitted`, stamped with active fiscal year and unique tracking code (`BRG-XXXXXX`). Server protects against rapid double-clicks ($\le 20\text{s}$) `[IMPLEMENTED]`.
2. **Advisory AI Triage & Failure Resilience**:
   - *Successful AI Processing*: AI extracts suggested category, urgency, executive summary, photo mismatch flag, and emergency keywords, storing output in `report_ai_results`. If emergency keywords are detected, status moves to `emergency_flagged` `[IMPLEMENTED]`.
   - *AI Failure / Timeout Path*: If Gemini times out (30s) or fails, the report is **never discarded or blocked**. The record remains safely saved in status `submitted` with `ai_status = 'failed'` `[IMPLEMENTED]`. The report detail page (`review-workflow.blade.php`) displays an `"AI suggestions are not available..."` notice and a `"Retry AI analysis"` button `[IMPLEMENTED]`.
   - *Queue Identification & Mandatory Screening*: In the current codebase, the staff queue table (`index.blade.php`) **does not display an explicit AI failure badge or column**, and the system **does not enforce a mandatory emergency-screening gate** when AI fails. Adding visible queue indicators for `ai_status = 'failed'` and enforcing a mandatory manual emergency-screening control are `[APPROVED DESIGN]` / required operational controls. Staff can manually flag any report as an emergency via the emergency review control (`PATCH /admin/reports/{report}/emergency` -> `ReportReviewService::resolveEmergency`) `[IMPLEMENTED]`.
   - *Execution Mode*: Currently executes synchronously via `ProcessNewReport` listener `[IMPLEMENTED]`; decoupling via asynchronous background jobs (`ShouldQueue`) is an `[APPROVED DESIGN]`.
3. **Comparative Staff Review**: Desk officer inspects 3-way card: Resident Input vs. Gemini AI Suggestion vs. Final Decision. Staff must manually verify Category and Urgency before the routing action unlocks `[IMPLEMENTED]`.
4. **Jurisdiction Routing**:
   - **Mode A (Internal)**: Assigned to internal barangay committee or tanod crew. Status moves to `routed` `[IMPLEMENTED]`.
   - **Mode B (External)**: Dispatched to external utility/city agency (ZCWD, ZAMCELCO, PNP, City Engineer). Generates `REF-YYYY-NNNN` endorsement letter. Status moves to `referred`. Follow-up is provisionally tracked every **3 calendar days** (configurable default based on recorded staff preference; formal policy adoption `[PENDING VALIDATION]`) `[IMPLEMENTED]`.
   - **Non-Actionable**: Moved to `no_action` with 1 of 6 mandatory reason codes and an explanation note `[IMPLEMENTED]`.
5. **Field Work & Response/Remediation Monitoring**: Assigned crew logs updates under `in_progress`. Delays (e.g. waiting for replacement materials) are logged via `waiting_materials` self-transitions without disrupting the active status `[IMPLEMENTED]`. For external referrals completed by partner agencies, `ReferralService::close` currently transitions `referred -> resolved` directly when `$outcome === 'resolved'` `[IMPLEMENTED]`; routing external completions through `referred -> in_progress -> awaiting_confirmation -> resolved` with proof is `[APPROVED DESIGN]`.
6. **Resolution Proposal**: Staff invokes `proposeResolution` to move a report from `in_progress` to `awaiting_confirmation` `[IMPLEMENTED]`. The system strictly requires at least one canonical `ReportProofPhoto` record belonging to the report before the transition can proceed: staff must either upload new proof photos via `proof_photos[]` in the proposal request or have previously attached proof photos via `storeProofPhotos` (a legacy single `photo` record alone is rejected). If proof photos are missing or unreadable, a validation exception is thrown and the report status remains unchanged. Upon successful proposal with proof, `ResolutionProof` and `ReportProofPhoto` records are stored, the report transitions to `awaiting_confirmation` via `ReportStatusService::transition()`, and the `ResolutionProposed` event is dispatched.
7. **Citizen Verification & Closure**: Resident inspects Before & After photos on `/track/{code}`:
   - *Confirmed Fixed*: `TrackController@confirmResolution` moves status from `awaiting_confirmation` to `resolved` through `ReportStatusService::transition()`, updates `resolution_confirmed_at`, saves optional 1–5 star rating and comments in `Feedback`, and dispatches the `ResolutionConfirmed` event `[IMPLEMENTED]`.
   - *Still Needs Work*: Resident enters dispute explanation ($\ge 10$ chars); `TrackController@reopen` moves status to `reopened` through `ReportStatusService::transition()`, clears `resolution_confirmed_at`, and dispatches `ResolutionConfirmed` ($confirmed = false$) `[IMPLEMENTED]`.
   - *Manual Close*: If citizen does not confirm, `Admin\ReportController@closeManually` allows administrative resolution `[IMPLEMENTED]`. Manual closure is strictly restricted to `role: admin` (staff users receive HTTP 403), requires the report to be in `awaiting_confirmation`, requires at least one canonical `ReportProofPhoto` record (legacy `ResolutionProof` alone is rejected), enforces a mandatory justification of 10–1000 characters, transitions status to `resolved` via `ReportStatusService::transition()`, and transactionally records an `AuditLog` entry (`action: 'manual_close_report'`). The waiting window prior to manual closure remains subject to BR-10 `[PENDING VALIDATION]`.

---

## 4. Actors and Responsibilities

Operational roles in the system distinguish between **technical software account configuration** (`users.role IN ('admin', 'staff', 'agency')`) and **governance authority** under local municipal administration (RA 7160):

| Actor | Primary Operational Responsibilities | Permitted Actions in System | Strict Boundaries & Prohibitions |
| :--- | :--- | :--- | :--- |
| **Resident (Public)** | Report local civic issues; monitor progress; answer staff inquiries; verify completed fixes. | Submit reports (guest/anonymous/identified); upload photos and audio; track via `BRG-XXXXXX`; reply to inquiries; confirm resolution; dispute/reopen; submit 1–5 star rating. | Must NOT view PII of other citizens; must NOT view internal staff notes; must NOT access dispute blotters or administrative queues. |
| **Desk Officer / Tanod** | Frontline intake; hotline logging; triage verification; field execution; resolution proof capture. | Log phone calls (`reports.log_phone`); verify audio; accept/edit AI suggestions; assign office/personnel; log progress notes; upload proof photos; request citizen info (`needs_info`). Governed by technical `role = 'staff'`. | Must NOT allow AI suggestions to become final without review; must NOT auto-close reports without human review. |
| **Barangay Secretary** | Administrative management; partner agency referrals; official documents; queue oversight. | Assigned technical `role = 'admin'`; signs endorsement letters; approves agency accounts; reviews overdue follow-ups; manages fiscal years and settings. | Must NOT bypass audit logging for administrative overrides. Technical `admin` privileges do NOT grant executive powers reserved by law for the Punong Barangay. |
| **Barangay Captain (PB)** | Executive oversight; emergency response coordination; municipal policy; appeals. | Assigned technical `role = 'admin'`; executive head of the barangay; resolves emergencies; manages priority weights; views security audit logs; authorizes non-action reopenings. | Must NOT bypass immutable audit logging or statutory reporting standards. |
| **Partner Agency User** | External utility/city agency remediation (ZCWD, ZAMCELCO, City Engineer). | Access isolated Agency Portal (`/agency/referrals`); view assigned referrals; download endorsement packets; post remediation milestones. Bound to `role = 'agency'`. | Must NOT access internal barangay queues; must NOT view reporter identities; must NOT access Katarungang Pambarangay cases. |
| **AI Service (Google Gemini)** | Advisory multi-modal classification, summarization, and transcription. | Transcribe spoken audio; suggest category and urgency; detect photo mismatches; flag emergency keywords; draft executive summaries. | Must NOT independently classify, route, assign, resolve, merge, close reports, or contact emergency dispatchers directly. |

> [!IMPORTANT]
> **Technical Role vs. Position-Specific Authorization**: In the current codebase, `User::hasPermission()` unconditionally returns `true` for `role = 'admin'` (`[IMPLEMENTED]`). Consequently, both the Barangay Captain and Barangay Secretary accounts currently possess **equivalent technical access** across all system features and permission gates. This technical configuration does **not** establish equivalent legal authority under the Local Government Code, nor does the codebase currently enforce position-specific separation of duties between these two roles. Implementing distinct position-based authorization (e.g., restricting specific executive approvals exclusively to the Punong Barangay) would require additional design and permission-gate development (`[APPROVED DESIGN]` / `[PENDING VALIDATION]`).

---

## 5. Report Status Lifecycle and Transition Rules

The Community Problem Reporting module operates on **12 canonical statuses**. The state machine is governed centrally via `app/Services/ReportStatusService.php`.

### 5.1 Status Directory & Transition Matrix

The table below contrasts the **canonical target transitions** (`[APPROVED DESIGN]`) against the transitions accepted by `ReportStatusService::ALLOWED` (`[IMPLEMENTED]`). Note that `ReportStatusService::ALLOWED` documents only what that service permits; it does not represent every status-changing path currently available across all controllers and services (for example, `TrackController@confirmResolution` and `TrackController@reopen` update status directly outside this service, while `Admin\ReportController@updateStatus` restricts manual selections via `Report::MANUAL_STATUSES`).

| Status Code | Display Label | Operational Meaning | Responsible Actor | Target Canonical Transitions (`[APPROVED DESIGN]`) | Actual Code `ALLOWED` Array & Notes (`[IMPLEMENTED]`) |
| :--- | :--- | :--- | :--- | :--- | :--- |
| **`submitted`** | Received | New intake persisted in database. Untouched by human staff. | Desk Officer | `under_review`, `emergency_flagged` | `under_review`, `routed`, `needs_info`, `no_action`, `referred`, `merged`, `emergency_flagged` `[IMPLEMENTED]`. |
| **`under_review`** | Under Review | Staff has opened review workspace and is triaging evidence. | Desk Officer | `routed`, `referred`, `needs_info`, `no_action`, `merged`, `emergency_flagged` | `routed`, `needs_info`, `no_action`, `referred`, `merged`, `emergency_flagged` `[IMPLEMENTED]`. |
| **`routed`** | Assigned | Internal barangay office or maintenance crew assigned; work pending. | Assigned Office / Crew | `in_progress`, `needs_info`, `referred`, `no_action`, `emergency_flagged` | `in_progress`, `needs_info`, `referred`, `no_action`, `awaiting_confirmation`, `resolved`, `emergency_flagged` `[IMPLEMENTED]`. Note: `routed -> awaiting_confirmation` and `routed -> resolved` bypass `in_progress` tracking and are targeted for removal `[APPROVED DESIGN]`. |
| **`in_progress`** | In Progress | Active field work, technical repairs, or site monitoring underway. | Assigned Crew / Tanods | `awaiting_confirmation`, `in_progress` (milestones), `needs_info`, `referred`, `no_action`, `emergency_flagged` | `in_progress`, `needs_info`, `referred`, `no_action`, `awaiting_confirmation`, `resolved`, `emergency_flagged` `[IMPLEMENTED]`. Direct `in_progress -> resolved` will be removed in target design so all remediation-based closures pass through `awaiting_confirmation` `[APPROVED DESIGN]`. |
| **`needs_info`** | Awaiting Citizen Reply | Staff has asked the citizen a clarification question. | Reporting Resident | Previous active status (`under_review` or `in_progress`), `no_action` | `under_review`, `routed`, `in_progress`, `needs_info`, `referred`, `no_action`, `awaiting_confirmation`, `resolved`, `emergency_flagged` `[IMPLEMENTED]`. Auto-resuming previous active status on `/track` reply is `[APPROVED DESIGN]`. |
| **`awaiting_confirmation`** | For Citizen Verification | Work complete; staff proposed resolution; awaiting resident review. | Reporting Resident | `resolved`, `reopened`, `in_progress`, `no_action`, `emergency_flagged` | `resolved`, `no_action`, `in_progress`, `reopened`, `emergency_flagged` `[IMPLEMENTED]`. Target canonical gateway to `resolved` for all remediation-based closures `[APPROVED DESIGN]`. |
| **`resolved`** | Resolved | Verified fixed by resident or manually confirmed by staff/admin. | Nobody (Archived) | `reopened`, `in_progress` | `in_progress`, `reopened` `[IMPLEMENTED]`. Closed operational outcome. SLA clock is stopped. Prompts resident for 1–5 star rating. |
| **`reopened`** | Reopened / Disputed | Resident rejected resolution or reported problem recurrence. | Desk Officer / PB | `under_review`, `routed`, `in_progress`, `needs_info`, `no_action`, `referred`, `merged`, `emergency_flagged` | `under_review`, `routed`, `in_progress`, `needs_info`, `no_action`, `referred`, `merged`, `emergency_flagged` `[IMPLEMENTED]`. Resident dispute requires $\ge 10$ char explanation `[IMPLEMENTED]`. |
| **`emergency_flagged`** | Critical Emergency | Immediate hazard to human life, physical safety, or active crime. | Captain / Tanod | `no_action` (`emergency_redirected`), `in_progress` (response monitoring), `under_review` (downgrade) | `no_action`, `under_review`, `in_progress` `[IMPLEMENTED]`. Priority score forced to 0; pins to top. Direct `emergency_flagged -> resolved` is unrepresented in code `[PENDING VALIDATION]`. |
| **`referred`** | Referred to Agency | Transferred to external public utility or city agency (Mode B). | Follow-Up Officer / Agency | `routed`, `in_progress`, `under_review`, `needs_info`, `no_action`, `merged` `[APPROVED DESIGN]` | `routed`, `in_progress`, `needs_info`, `no_action`, `merged`, `resolved` `[IMPLEMENTED]`. **Note**: `ReferralService::close` actively uses `referred -> resolved` in current code `[IMPLEMENTED]`. Routing completion through `referred -> in_progress -> awaiting_confirmation -> resolved` and adding `under_review` on rejection are `[APPROVED DESIGN]`. |
| **`merged`** | Merged (Duplicate) | Secondary duplicate report consolidated into a primary report. | Nobody (Consolidated) | `under_review` | `under_review` `[IMPLEMENTED]`. Child report points to `merged_into_id`. Unmerged via `AdminReportController@unmerge` with audit note `[IMPLEMENTED]`. |
| **`no_action`** | Closed: No Action Taken | Closed operational outcome; report cannot or will not be remediated. | Authorized Personnel | `in_progress`, `reopened`, `under_review` `[APPROVED DESIGN]` | `in_progress`, `reopened` `[IMPLEMENTED]`. Requires 1 of 6 reason codes and explanation `[IMPLEMENTED]`. |

### 5.2 Specific Status Rules & Clarifications

1. **Resolution Gateway & Manual Closure (`closeManually`)**:
   - *Current Implementation (`[PARTIALLY IMPLEMENTED]`)*:
     - `Admin\ReportController@closeManually` (`POST /admin/reports/{report}/close`) is accessible to both `admin` and `staff` (`middleware(['auth', 'role:admin,staff'])`).
     - Validation permits `'note'` to be `nullable` (defaults to `'Case closed manually by staff after confirmation period.'`); no 10-character minimum is enforced.
     - No proof-photo check is enforced by `closeManually`.
     - The transition is logged via `ReportStatusService` to `status_logs`, **not** `audit_logs`.
     - Separately, `ReferralService::close` transitions `referred -> resolved` directly when an external referral closes with outcome `'resolved'`, and `Agency\ReferralController@update` closes the `Referral` record without transitioning the `Report`.
     - In `Admin\ReportController@proposeResolution`, `photo` and `proof_photos` are `nullable`, so entering `awaiting_confirmation` does not currently guarantee uploaded proof photos (`storeProofPhotos` enforces `min:1` separately).
   - *Approved Target Design (`[APPROVED DESIGN]`)*:
     - All remediation-based closures—including completed external referrals (`referred -> in_progress -> awaiting_confirmation -> resolved`) and manual administrative closures—must follow the canonical `awaiting_confirmation -> resolved` pathway. Operational exits such as `no_action` remain separate and must follow their applicable reason-code and authorization rules.
     - Legacy direct bypasses (`routed -> awaiting_confirmation`, `routed -> resolved`, `in_progress -> resolved`, `referred -> resolved`) will be removed from `ReportStatusService::ALLOWED`.
     - `proposeResolution` will enforce $\ge 1$ proof photo in `report_proof_photos` before entering `awaiting_confirmation`.
     - Manual administrative closure (`awaiting_confirmation -> resolved`) will be restricted to `role: admin` (or authorized supervisors), require a mandatory $\ge 10$-character justification note, and record an entry in `audit_logs`.
     - *Evidence Substitution*: Whether an official agency completion certificate may substitute for a proof photo in Mode B referrals is `[PENDING VALIDATION]`.

2. **Reopening `no_action` Reports**:
   - *Current Implementation (`[PARTIALLY IMPLEMENTED]`)*:
     - `Admin\ReportController@reopenReport` allows both `admin` and `staff` (`in_array($user->role, ['admin', 'staff'], true)`).
     - Validation requires `'reason'` with a minimum of **5 characters** (`min:5`).
     - It creates an `AuditLog` entry with action `'resume_no_action_report'` and transitions the report strictly to `in_progress`.
     - `ReportStatusService::transition()` automatically clears `reports.no_action_reason = null` when transitioning out of `no_action` `[IMPLEMENTED]`.
     - The generic `Admin\ReportController@updateStatus` endpoint also permits transitions from `no_action` to `in_progress` or `reopened` without creating the dedicated `resume_no_action_report` audit entry.
   - *Approved Target Design (`[APPROVED DESIGN]`)*:
     - Restrict reopening `no_action` reports exclusively to `role: admin` or explicitly designated supervisory staff (exact permission slug `[PENDING VALIDATION]`).
     - Require a mandatory $\ge 10$-character explanation across all reopening paths and enforce uniform `audit_logs` recording (`no_action_reopened`).
     - Support three explicit reopening targets: `under_review` (re-triage on new evidence `[APPROVED DESIGN]`), `in_progress` (immediate field work `[IMPLEMENTED]`), and `reopened` (formal dispute review `[IMPLEMENTED]`).

3. **Administrative Unmerge Recovery (`merged -> under_review`)**:
   - Returning a report from `merged` to `under_review` is an **explicit administrative unmerge operation**, not a routine workflow step `[IMPLEMENTED]`.
   - Executed via `Admin\ReportController@unmerge`: requires an explanatory note ($\ge 5$ characters), sets `merged_into_id = null`, recalculates primary report corroboration, and writes an `unmerge_report` entry to `audit_logs` `[IMPLEMENTED]`.

4. **Resuming Work on `resolved` Reports (`resolved -> in_progress`)**:
   - *Current Implementation (`[PARTIALLY IMPLEMENTED]`)*: Both `admin` and `staff` may reopen a `resolved` report to `in_progress` via `Admin\ReportController@reopenReport` (`$allowedReopen = [RESOLVED, NO_ACTION]`), which validates a minimum 5-character `'reason'` and logs to `status_logs` via `ReportStatusService` (unlike `no_action`, `reopenReport` does **not** create an `audit_logs` entry when reopening from `resolved`). Separately, `Admin\ReportController@updateStatus` also permits transitioning from `resolved` to `in_progress` with a `nullable` note and no `audit_logs` entry.
   - *Approved Target Design (`[APPROVED DESIGN]`)*: Restricting `resolved -> in_progress` reopening authority, requiring a $\ge 10$-character justification, and logging to `audit_logs` across all reopening paths are `[APPROVED DESIGN]`.

5. **Handling Resident Clarification Replies (`needs_info`)**:
   - Currently `TrackController@respondToInfoRequest` only creates a `StatusLog` entry and leaves `reports.status` in `needs_info` without invoking `ReportStatusService`. Automatically resuming the report to its previous operational workflow state (`under_review` or `in_progress`) via `ReportStatusService` is `[APPROVED DESIGN]`.

6. **Handling Declined External Referrals (`referred`)**:
   - If a receiving agency declines, rejects, or fails to respond to a referral, the report **does NOT automatically close** `[IMPLEMENTED]`.
   - Creating a dedicated staff review decision point (`under_review` or explicit routing prompt) without silently selecting an arbitrary status is `[APPROVED DESIGN]`. Staff chooses between re-routing internally (Mode A), re-referring to another agency (Mode B), or closing as `no_action`.

---

## 6. Internal Assignment and External Referral

The system enforces a clear separation between internal barangay operations (Mode A) and external agency referrals (Mode B) using the `routing_rules` configuration.

### 6.1 Mode A: Internal Barangay Dispatch
- **Authority**: Triggered when `routing_rules.within_authority = true` (e.g., local drainage clearing, uncollected street trash, barangay hall facility maintenance, tanod night patrol) `[IMPLEMENTED]`.
- **Dispatch**: Assigned to an internal barangay unit (`offices.is_internal = true`) and optionally assigned to an individual staff member (`reports.assigned_to`) `[IMPLEMENTED]`.
- **SLA Clock**: The service-level agreement countdown begins immediately upon transition to `routed`, derived from `categories.sla_hours`. Overdue reports display high-contrast red warning pills across administrative queues `[IMPLEMENTED]`.

### 6.2 Mode B: External Agency Referral
- **Authority**: Triggered when `routing_rules.within_authority = false` (e.g., major water main rupture [ZCWD], high-voltage transformer failure [ZAMCELCO], national highway excavation [DPWH]) `[IMPLEMENTED]`.
- **Numbering Sequence**: Generates a sequential tracking number: `REF-YYYY-NNNN` (e.g., `REF-2026-0001`) `[IMPLEMENTED]`. Formal adoption of this exact sequence format is marked `[PENDING VALIDATION]`.
- **Endorsement Packet**: Formats a printable formal endorsement letter with institutional barangay header, coordinates map link, narrative, and dynamic signature block via `SignatoryResolver::forDocument('referral_letter')` `[IMPLEMENTED]`. Formal letterhead format and inter-agency MoUs are marked `[APPROVED DESIGN; PENDING VALIDATION]`.
- **Follow-Up Interval & Operational Context**:
  - The system is configured with a provisional default follow-up interval of **3 calendar days** from dispatch (`referral.follow_up_days = 3` in `config/reports.php` / settings) `[IMPLEMENTED]`.
  - *Origin & Status*: The 3-day interval was documented as a **recorded operational preference** during stakeholder interview questionnaire Q12 with the incumbent Barangay Secretary. It represents a configurable operational default rather than a formally enacted barangay ordinance or legally binding inter-agency service level agreement. Formal operational policy adoption and inter-agency MoUs remain `[PENDING VALIDATION]`.
- **SLA Behavior**: Internal barangay SLA countdown pauses. The issue remains monitored in the Referrals Directory (`/admin/referrals`) with alert badges when `follow_up_at <= today()` `[IMPLEMENTED]`.
- **Citizen Visibility**: The resident tracking page displays the recipient agency name, transmission status, and public progress notes. Internal staff notes, official emails, and letter drafts remain private `[IMPLEMENTED]`.

---

## 7. Emergency and Exceptional Scenarios

### 7.1 Emergency Handling & Safety Protocol
Emergency situations involve critical physical safety hazards and require clear operational distinctions between telephone guidance, physical perimeter/safety support by Tanods, and specialized external agency response:

1. **Emergency Call Guidance (Dialing 911 / Hotlines)**:
   - *Action*: When a report is flagged (`is_emergency = true`), the resident web screen immediately pops up a high-contrast dialing card for national 911, local police, and local fire stations `[IMPLEMENTED]`.
   - *Operational Exit*: If staff action consists solely of confirming the resident called 911 or providing hotline numbers, the report exits via `no_action` with reason `emergency_redirected` `[IMPLEMENTED]`.
   - *Strict Safety Rule*: **Emergency redirection to 911 must never be treated as proof that the physical hazard has been resolved.** It only documents that emergency advice was communicated.
2. **Perimeter & Public Safety Support by Barangay Tanods**:
   - *Action*: When Barangay Tanods deploy to the site to perform safe non-technical duties: securing or cordoning off an affected hazard perimeter, managing crowd and traffic control, assisting injured residents to safety, or clearing non-hazardous road obstructions (such as fallen tree branches).
   - *Safety Boundary*: **Hazardous technical remediation (such as live electrical lines, gas leaks, or structural fires) must never be handled by Tanods and must be left to qualified emergency responders or utility personnel (e.g., ZAMCELCO, BFP, CDRRMO).**
   - *Operational Path*: Staff transitions the report `emergency_flagged -> in_progress` (`in_progress – Response/Remediation Monitoring`) `[IMPLEMENTED]`. Once the safe area/obstruction is handled, staff uploads completion proof photos and proposes resolution (`awaiting_confirmation -> resolved`).
3. **Emergency Response Handled by External Agencies**:
   - *Action*: When emergency response or hazardous technical remediation is executed by qualified external statutory agencies (e.g., Bureau of Fire Protection firefighting, CDRRMO paramedic response, ZAMCELCO downed wire repair, PNP emergency response).
   - *Operational Path*: Barangay coordinates and monitors under `in_progress – Response/Remediation Monitoring`. Because `ReportStatusService` currently has no direct `emergency_flagged -> resolved` transition, staff currently route through `in_progress` (uploading documentation and proof) or record an operational exit. Whether a dedicated external emergency resolution transition should be added to the state machine is an **unresolved workflow decision** `[PENDING VALIDATION]`.
4. **De-escalation / False Alarm Downgrade (`emergency_flagged -> under_review`)**:
   - *Action*: When initial review reveals that a citizen mistakenly checked the emergency box for a routine non-life-threatening concern, or an AI keyword match triggered a false alarm.
   - *Operational Path*: Staff transitions `emergency_flagged -> under_review`, clearing the emergency override and returning the report to standard priority triage `[IMPLEMENTED]`.
5. **Public Map Concealment**: Emergency-flagged reports are **strictly excluded** from public transparency maps (`/map`) and nearby reports APIs (`/api/nearby-reports`) `[IMPLEMENTED]`.
6. **Queue Priority**: Emergency incidents bypass standard numerical priority scoring and pin to the absolute top of all administrative queues with a red accent border (`.is-emergency-row`) `[IMPLEMENTED]`.

### 7.2 Duplicate Management
- **Matching Engine**: `DuplicateService` computes Haversine spatial distance ($\le 150\text{ meters}$) and text narrative similarity ($\ge 45\%$) against active reports submitted within the past 14 days sharing the same category `[IMPLEMENTED]`.
- **Human Governance Rule**: Duplicate suggestions are displayed to staff as **advisory recommendations only** `[APPROVED DESIGN]` (currently `DuplicateService::findMatches()` is not yet wired into `AdminReportController@show`). The system never automatically merges reports.
- **Merge Execution**: Staff selects the primary canonical report and clicks "Merge". The secondary report transitions to `merged` with `merged_into_id` set `[IMPLEMENTED]`. The primary report's priority score increases by $+8$ points per supporting report.
- **Unmerge Recovery**: If an accidental merge occurs, authorized staff may execute an administrative unmerge of the child report back to `under_review` with a mandatory explanation note ($\ge 5$ characters), which is logged to `audit_logs` `[IMPLEMENTED]`.

### 7.3 Reports Outside Barangay Jurisdiction or Territory
- **Out of Authority (Non-Territorial)**: Issues within Calarian but outside barangay legal power are handled via Mode B external agency referrals `[IMPLEMENTED]`.
- **Out of Territory**: Reports physically located outside Barangay Calarian borders (e.g. neighboring Barangay Sinunuc or San Roque):
  - Handled via `no_action` with reason `not_barangay_matter` `[IMPLEMENTED]`.
  - Staff provides clear guidance directing the citizen to the proper barangay hall.
  - *Note*: Formal inter-barangay transmittal procedures are not assumed and remain `[PENDING VALIDATION]`.

### 7.4 Non-Actionable Reports (`no_action`)
Every transition to `no_action` strictly requires selecting 1 of 6 standardized reason codes plus a written justification visible to the resident `[IMPLEMENTED]`:
1. `not_barangay_matter`: Matter outside municipal authority and cannot be referred, or located outside territorial boundaries.
2. `emergency_redirected`: Immediate life-safety concern redirected to 911 / police / fire hotlines.
3. `cannot_resolve`: Valid concern that cannot be remediated due to insurmountable technical, budgetary, or private property legal barriers.
4. `invalid_report`: Spam, incomprehensible text, accidental duplicate submission, or test submission.
5. `resident_unreachable`: Necessary clarification was requested (`needs_info`), but reporter provided no valid contact or failed to respond. **This is a deliberate manual staff decision, never an automated background closure.** The observational waiting threshold before staff may select this code is provisionally set to 7 calendar days `[PENDING VALIDATION]` (see BR-09).
6. `converted_to_case`: Civil incident report converted into a formal Katarungang Pambarangay dispute case.

---

## 8. AI Responsibilities and Limitations

### 8.1 Advisory Scope
Google Gemini REST API (`gemini-2.5-flash`) provides technical acceleration and advisory triage only:
1. **Category Suggestion**: Evaluates narrative and images against active database categories; suggests most probable category with confidence score `[IMPLEMENTED]`.
2. **Urgency Recommendation**: Suggests urgency tier (`Low`, `Medium`, `High`, `Critical`) based on environmental risk factors `[IMPLEMENTED]`.
3. **Executive Summarization**: Synthesizes multi-paragraph dialect descriptions into concise 2-sentence English/Filipino summaries for fast queue scanning `[IMPLEMENTED]`.
4. **Photo Inspection & Mismatch Flag**: Analyzes image contents; sets `photo_mismatch = true` if the attached photo depicts unrelated imagery `[IMPLEMENTED]`.
5. **Voice Intake Multimodal Extraction**: Transcribes spoken audio into problem descriptions and candidate form fields without executing spoken commands `[IMPLEMENTED]`.

### 8.2 Strict Operational Limitations
1. **Zero Database Write Authority**: The AI has zero authority to execute database updates on final report columns (`reports.category_id`, `reports.urgency`, `reports.status`, `reports.office_id`). All AI outputs live strictly in `report_ai_results` `[IMPLEMENTED]`.
2. **Review Gate Barrier**: No report can move to `routed` or `referred` based solely on AI classification. A human staff member must manually verify and confirm final values `[IMPLEMENTED]`.
3. **No Direct Emergency Dispatch**: The AI cannot dispatch police, fire, or Tanods directly. It only sets an internal flag (`emergency_flag = true`), which alerts human officials `[IMPLEMENTED]`.
4. **Prompt Injection Defense**: All resident inputs are treated as untrusted data strings. The AI prompt explicitly instructs the model to ignore instructions contained within user descriptions `[IMPLEMENTED]`.
5. **Asynchronous Decoupling `[APPROVED DESIGN]`**: AI processing will run in queued background workers (`ShouldQueue`), ensuring that cloud API latency or outages never stall citizen submissions or staff page loads.

---

## 9. Security, Privacy, and Permissions

### 9.1 Data Privacy (Republic Act 10173 - Data Privacy Act of 2012)
1. **Typed Text PII Isolation**: For typed web submissions, citizen personal identification (reporter name, phone number, and email address) is stored solely in the local application database. **Typed PII is strictly excluded from AI text prompts** sent to Google Gemini or third-party APIs `[IMPLEMENTED]`.
2. **Voice Audio Third-Party Processing & Spoken PII Disclosure**:
   - When a resident utilizes the optional voice reporting prototype, the user provides explicit in-browser consent before microphone access.
   - **Data Transmission**: Because raw audio captures spoken human voice, the **original audio recording is necessarily transmitted over TLS to Google Gemini API** for speech transcription and fact extraction. If the resident voluntarily speaks their name, phone number, or address into the microphone, that spoken information is included in the audio stream sent to the cloud model `[IMPLEMENTED]`.
   - **Output Sanitization**: The extraction prompt explicitly instructs Gemini to strip or omit spoken personal contact numbers or full names from the structured incident `description` returned to the form `[IMPLEMENTED]`.
   - **Access Restrictions**: The raw audio file (`.webm`, `.wav`) and raw transcript (`report_audios.transcript`) are stored exclusively on the private storage disk (`storage/app/private/`) and are accessible only to authenticated, authorized barangay staff `[IMPLEMENTED]`.
   - **Unresolved Privacy & Retention Policy**: A formal third-party Data Processing Agreement (DPA) with Google Cloud, specific voice audio retention schedules, and resident right-to-erasure workflows have not been formally ratified and are marked `[PENDING VALIDATION]`.
3. **Anonymous Mode Integrity**: Checking "Report anonymously" completely clears and disables contact fields, logs no citizen identity, and stores zero IP address or device fingerprint `[IMPLEMENTED]`.
4. **Public Map Privacy**: The public transparency map (`/map`) and nearby reports API (`/api/nearby-reports`) display only category, purok, and status. Narratives, photos, audio clips, reporter names, and emergency incidents are completely scrubbed `[IMPLEMENTED]`.
5. **Private Disk Storage**: Voice recordings, resolution proofs, partner agency identity documents, and database backup archives reside strictly on the private storage disk (`storage/app/private/`) accessed via authenticated streaming endpoints `[IMPLEMENTED]`.

### 9.2 Roles and Access Tiers
Access tier is governed strictly by `users.role` across three allowed values: `admin`, `staff`, and `agency` (`personnel` is NOT a role; it is an assignable directory of people) `[IMPLEMENTED]`.

| Role | Operational Scope | Permission Gates |
| :--- | :--- | :--- |
| **`admin`** | Full system administration (Punong Barangay and Barangay Secretary). | Implicitly bypasses all permission gates via `User::hasPermission()`, granting equivalent technical access to both assigned accounts `[IMPLEMENTED]`. |
| **`staff`** | Operational barangay personnel (Tanods, desk officers, Lupon members). | Governed strictly by granular permissions and assigned presets `[IMPLEMENTED]`. |
| **`agency`** | External public utility / city agency representatives. | Bound strictly to `users.office_id`; isolated layout (`layouts/agency.blade.php`); access restricted to `/agency/referrals` `[IMPLEMENTED]`. |

> [!NOTE]
> **Technical Role Equivalence vs. Position-Specific Authority**: Because `role = 'admin'` grants equivalent technical access to all assigned accounts (`[IMPLEMENTED]`), the current codebase does not enforce position-specific separation of duties between the Punong Barangay and Barangay Secretary. Separate position-based authorization is additional design work (`[APPROVED DESIGN]`) if required by barangay governance.

### 9.3 Granular Permissions & Role Presets
Exactly **21 permission slugs** exist in code (seeded via `RequiredDataSeeder.php`):
- *Administration*: `settings.manage`, `fiscal_year.manage`, `templates.manage`, `backups.manage`, `audit.view`, `dashboard.view`.
- *People & Users*: `personnel.manage`, `users.manage`.
- *Community Reports*: `reports.view`, `reports.log_phone`, `reports_center.view`, `reports_center.export`.
- *Dispute Cases*: `cases.view`, `cases.view_confidential`, `cases.create`, `cases.edit`, `cases.summons`, `cases.escalate`.
- *Agency & External*: `agencies.manage`, `referrals.manage`, `imports.manage`.

**Predefined Presets (`config/permission_presets.php`)**:
- `staff_full` (18 permissions): Standard operational staff access across reports, cases, personnel, and referrals (excludes confidential cases and backups).
- `case_encoder` (5 permissions): Lupon clerk / encoder restricted to case intake, party directory, and summons for non-confidential cases.
- `reports_analyst` (3 permissions): Read-only reporting, Reports Center, and dashboard analytics exports.
- `case_reviewer` (5 permissions): Peace & Order Kagawad access to all cases (including confidential disputes), report queue, and Reports Center.

---

## 10. Technical Architecture and Important Services

### 10.1 Technical Stack & Dependency Versions (Verified from Project Files)
- **Backend Framework**: Laravel Framework `^13.17` (`composer.json`) `[IMPLEMENTED]`.
- **PHP Version Target**: PHP `^8.3` (runtime tested on PHP 8.4) `[IMPLEMENTED]`.
- **Database / ORM**: SQLite (`database/database.sqlite` / `:memory:` for automated testing), MySQL (production target) `[IMPLEMENTED]`.
- **Frontend / Asset Bundler**: Vite `^8.0.0`, Tailwind CSS `^4.0.0` with `@tailwindcss/vite` `^4.0.0` (`package.json`) `[IMPLEMENTED]`.
- **Mapping Library**: Leaflet.js `^1.9.4` bundled locally via Vite with OpenStreetMap tiles `[IMPLEMENTED]`.
- **Analytics Visualization**: Chart.js `^4.6.1` `[IMPLEMENTED]`.
- **Icon Assets & Dynamic Interactions**: FontAwesome Free `^7.3.1`, `@hotwired/turbo` `^8.0.23` `[IMPLEMENTED]`.
- **Document Export Tools**: `barryvdh/laravel-dompdf` `^3.1`, `openspout/openspout` `^5.15` `[IMPLEMENTED]`.
- **Testing & Tooling**: PHPUnit `^12.5.12`, Laravel Pint `^1.27` `[IMPLEMENTED]`.
- **AI Service Integration**: Google Gemini REST API (`gemini-2.5-flash`) via Laravel `Http` facade `[IMPLEMENTED]`.

### 10.2 Service Layer Responsibility Map
- **`ReportStatusService`**: Central status-transition service enforcing the 12-status matrix, `status_logs` creation, and `ReportStatusChanged` event dispatches within database transactions `[IMPLEMENTED]`. However, it is **not yet the single authoritative mechanism for every status change** (`[PARTIALLY IMPLEMENTED]`): `TrackController@confirmResolution` and `TrackController@reopen` update `reports.status` directly outside this service, `TrackController@respondToInfoRequest` creates a `StatusLog` without updating status, and separate controller paths apply differing validation and audit checks. Routing all status changes uniformly through `ReportStatusService` is `[APPROVED DESIGN]`.
- **`ReportReviewService`**: Handles staff review workspace saves, routing mode determination, and AI override audit logging `[IMPLEMENTED]`.
- **`RoutingService`**: Evaluates category rules against jurisdiction authority (Mode A vs Mode B) `[IMPLEMENTED]`.
- **`PriorityService`**: Computes dynamic numerical priority scores based on urgency, category risk weight, sensitive place proximity (150m), duplicate corroboration, and report age `[IMPLEMENTED]`.
- **`ReferralService`**: Generates unique referral sequence records, builds printable endorsement letters, tracks provisional 3-day follow-ups, and manages agency communication logs `[IMPLEMENTED]`.
- **`DuplicateService`**: Performs Haversine distance calculations ($\le 150\text{m}$) and narrative text similarity matching ($\ge 45\%$) `[IMPLEMENTED]`.
- **`GeminiService`**: Multimodal AI extraction wrapper with prompt injection defenses and 30-second timeout fallbacks `[IMPLEMENTED]`.
- **`FiscalYearGuard` & `FiscalYearService`**: Enforces calendar-year boundary isolation, sequence auto-resets, and prevents modifications to locked fiscal years `[IMPLEMENTED]`.

---

## 11. Business Rules and Configuration

The following business rules represent established system logic and approved operational defaults:

| Rule ID | Category | Rule Title | Operational Specification | Code / Config Reference |
| :--- | :--- | :--- | :--- | :--- |
| **BR-01** | Safe Technical | Duplicate Submission Guard | Submissions with identical description and category within 20 seconds redirect to the existing report success page. | `ReportController@store` `[IMPLEMENTED]` |
| **BR-02** | Safe Technical | Photo Upload Limits | Up to 5 images (JPG/PNG/WebP), maximum combined size of 5 MB, compressed client-side. | `config/reports.php` `[IMPLEMENTED]` |
| **BR-03** | Safe Technical | Voice Recording Limit | Spoken voice recordings capped at 120 seconds (2 minutes). | Setting `voice.max_seconds` `[IMPLEMENTED]` |
| **BR-04** | Safe Technical | Narrative Minimum Length | Minimum 10 characters on resident web form; minimum 5 characters on staff phone log. | Validation rules `[IMPLEMENTED]` |
| **BR-05** | Configurable Default | Referral Follow-Up Interval | Scheduled follow-up interval for external referrals is provisionally defaulted to **3 calendar days** from transmission, reflecting recorded staff preference (Secretary interview Q12). | Setting `referral.follow_up_days` `[IMPLEMENTED; PENDING VALIDATION]` |
| **BR-06** | Approved Default | Clarification Resumption | Submitting an answer on `/track/{code}` automatically resumes report status to its previous active operational state. | `TrackController` `[APPROVED DESIGN]` |
| **BR-07** | Approved Default | Declined Referral Decision Point | A declined referral creates a staff review decision point; it never automatically closes or forces an arbitrary status. | `ReferralService` `[APPROVED DESIGN]` |
| **BR-08** | Approved Default | Resolution Proof Requirement | Standalone proof upload requires $\ge 1$ image (`storeProofPhotos` `[IMPLEMENTED]`); entering `awaiting_confirmation` requires at least one canonical `ReportProofPhoto` record belonging to the report (either attached earlier or uploaded with `proposeResolution`) `[IMPLEMENTED]`. | `AdminReportController` `[IMPLEMENTED]` |

---

## 12. Decisions Requiring Barangay Validation

The following operational policies cannot be decided by technical teams alone and must be formally validated with the Punong Barangay, Barangay Secretary, or Lupon Leadership:

| Rule ID | Policy Topic | Provisional Default | Operational Question for Barangay Officials | Confirmation Status |
| :--- | :--- | :--- | :--- | :--- |
| **BR-09** | **Clarification Inactivity Waiting Period** | 7 calendar days | *If a resident fails to reply to an information request (`needs_info`), how many calendar days should staff wait before manually choosing to close the report as `no_action` (`resident_unreachable`)? (The system will not close automatically).* | `[PENDING VALIDATION]` |
| **BR-10** | **Resolution Confirmation Waiting Window** | 14 calendar days (Provisional) | *When resolution proof is uploaded (`awaiting_confirmation`), how many days should staff wait before manually confirming and closing the report if the resident does not reply? (The system will not close automatically).*<br><br>**Unresolved Questions & Policy Status:**<br>1. *Window Duration:* Provisional 14 calendar days versus the existing UI setting default of 5 days (`Setting::value('auto_close_days', 5)`).<br>2. *Anchor Timestamp:* Whether the waiting period should be measured from when resolution proof was uploaded / proposed (`resolution_proofs.created_at` or entry into `awaiting_confirmation`) rather than assuming the original intake SLA target date (`due_at`) is appropriate.<br>3. *Enforcement Level:* Whether the waiting window should be enforced as a strict backend block (rejecting closure before expiration) or serve as an advisory operational guideline with administrator override. *(Note: The existing UI check based on `due_at + auto_close_days` is an interface filter, not backend enforcement).* | `[PENDING VALIDATION]` |
| **BR-11** | **Dispute Reopening Policy Cap** | Maximum 2 reopenings | *How many times may a resident reopen a resolved report online before a mandatory in-person conciliation conference is required at the barangay hall?* | `[PENDING VALIDATION]` |
| **BR-12** | **Out-of-Territory Transmittal Protocol** | Guidance note only | *When a report is physically located in a neighboring barangay (Sinunuc, San Roque), should staff simply advise the resident, or does the barangay issue formal transmittal letters to the adjacent barangay?* | `[PENDING VALIDATION]` |
| **BR-13** | **Anonymous Report Field Inspection Policy** | Public hazard observable | *What criteria must an anonymous report meet before Tanods are dispatched to conduct a physical on-site inspection?* | `[PENDING VALIDATION]` |
| **BR-14** | **Emergency Physical Remediation Protocol** | Tanods secure perimeter | *When Tanods secure a hazard perimeter or clear non-hazardous debris, do officials want to record proof photos under `in_progress -> awaiting_confirmation`, or should an expedited administrative emergency closure procedure be formally added?* | `[PENDING VALIDATION]` |
| **BR-15** | **Mediation & Conciliation Statutory Counting Rules** | 15 calendar days | *Under RA 7160 Sec. 410, should mediation (15 days) and conciliation (15 days + 15-day extension) be counted in calendar days or working days (excluding weekends and official holidays)?* | `[PENDING VALIDATION]` |
| **BR-16** | **Official Docket and Referral Numbering Formats** | Proposed `{MM}-{SEQ:3}-{YEAR}` | *What is the exact physical docket numbering format used in Barangay Calarian's official blotters, complaint cases, and external transmittal letters?* | `[PENDING VALIDATION]` |
| **BR-17** | **Authorization to Reopen Non-Actioned Reports** | Captain / Secretary only (Proposed) | *Which specific personnel positions hold authority to reopen a report previously closed under `no_action`?*<br><br>**Current Implementation & Unresolved Policy:**<br>- *Current Code:* Both `admin` and `staff` roles with `permission:reports.view` can currently reopen `no_action` reports back to `in_progress` via `Admin\ReportController@reopenReport` (with mandatory $\ge 5$-char reason and `audit_logs` record).<br>- *Unresolved Policy Decision:* Whether reopening non-actioned reports should be restricted exclusively to administrators (`role: admin`, representing the Captain / Secretary), or whether operational staff should retain reopening authority when new evidence or equipment becomes available. | `[PENDING VALIDATION]` |

---

## 13. Development Priorities and Implementation Roadmap

To align the codebase with this authoritative specification, implementation must proceed through discrete, non-breaking tasks:

### Priority 1: Queue Stabilization, AI Visibility & Screening `[APPROVED DESIGN]`
- Configure Laravel database queue driver (`QUEUE_CONNECTION=database`) and update `ProcessNewReport` to implement `ShouldQueue`.
- Add explicit `ai_status = 'failed'` badges to the staff queue table (`index.blade.php`) and enforce mandatory manual emergency-screening confirmation when AI is unavailable.

### Priority 2: State Machine Hardening & Closure Controls `[APPROVED DESIGN]`
- Update `TrackController@respondToInfoRequest` and `confirmResolution()` to route all status changes through `ReportStatusService::transition()`.
- Remove legacy direct bypasses (`routed -> awaiting_confirmation`, `routed -> resolved`, `in_progress -> resolved`, `referred -> resolved`) from `ReportStatusService::ALLOWED` and update `ReferralService::close` so external completions pass through `in_progress -> awaiting_confirmation -> resolved`.
- Enforce $\ge 1$ proof photo in `proposeResolution`, and harden `closeManually` and `reopenReport` with `role:admin`/supervisor checks, $\ge 10$-char justification notes, and `audit_logs` entries.

### Priority 3: Activate Advisory Duplicate Suggestions `[APPROVED DESIGN]`
- Connect `DuplicateService::findMatches()` directly into `AdminReportController@show`.
- Inject distance-ranked candidate cards into the review workspace modal as staff-facing suggestions so staff can verify and execute duplicate merges.

### Priority 4: Route Permission Hardening `[APPROVED DESIGN]`
- Attach `middleware('permission:reports.view')` to `admin.reports.index` and `admin.reports.show` in `routes/staff.php`.
- Verify role presets in `config/permission_presets.php` to ensure desk officers, tanods, and secretaries maintain appropriate operational access.

### Priority 5: Numbering Format Alignment `[PENDING VALIDATION]`
- Keep numbering format configurable via System Settings until physical sample verification is completed (`[PENDING VALIDATION]`).

---

## 14. Acceptance Criteria

1. **Submission Performance Target**: A resident submits a report on a 375px mobile device without creating an account and receives an instant `BRG-XXXXXX` tracking code, targeting sub-second server response times under normal network conditions once asynchronous queues are activated `[APPROVED DESIGN]`.
2. **AI Failure Resilience & Screening**: If Google Gemini times out (30s) or fails, the report is saved safely in status `submitted` with `ai_status = 'failed'` `[IMPLEMENTED]`, displays an unavailable notice with retry on the detail page `[IMPLEMENTED]`, shows an explicit failure badge in the staff queue `[APPROVED DESIGN]`, and requires manual emergency screening before triage `[APPROVED DESIGN]`.
3. **Emergency Containment & Safety Integrity**: Checked emergency reports immediately surface national 911 dialing prompts, hide from public maps, alert officials, and pin to the top of the queue `[IMPLEMENTED]`. Redirection to 911 must never be treated as proof that a physical hazard is resolved.
4. **Human Review Gate**: Staff can compare resident inputs, AI recommendations, and final values side-by-side; "Confirm and route" is strictly blocked until category and urgency are verified `[IMPLEMENTED]`.
5. **Mode A vs Mode B Separation**: Issues within barangay authority route to internal maintenance units with SLA deadlines; issues beyond authority generate structured referral letters with configurable provisional 3-day follow-up tracking `[IMPLEMENTED; PENDING VALIDATION]`.
6. **Resolution Accountability**: Standalone proof uploads require at least one image (`[IMPLEMENTED]`), while enforcing $\ge 1$ proof photo on `proposeResolution` and hardening manual closure (`role:admin`, $\ge 10$-char justification, `audit_logs`) are target controls `[APPROVED DESIGN]`.
7. **Privacy and Data Protection Safeguards**:
   - *Verified Technical Safeguards (`[IMPLEMENTED]`)*: Reporter typed PII is excluded from AI text prompts, omitted from public maps (`/map`) and `/api/nearby-reports`, and cleared/disabled in anonymous submissions; voice audio transmissions occur under explicit in-browser consent with prompt instructions to omit spoken PII from extracted descriptions; raw audio files and transcripts reside on private storage (`storage/app/private/`) restricted to authenticated staff.
   - *Unresolved Legal & Governance Controls (`[PENDING VALIDATION]`)*: Formal third-party Data Processing Agreement (DPA) with Google Cloud, audio/transcript retention schedules, and citizen right-to-erasure procedures under RA 10173 remain pending legal and administrative validation.
