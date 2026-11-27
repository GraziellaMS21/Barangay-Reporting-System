# Lifecycle, Statuses & State Machine Workflow

> **Canonical Source of Truth**: This document is the single authoritative source of truth for the incident report lifecycle, the external referral lifecycle, and the barangay case lifecycle, including status definitions, allowed state transitions, SLA behavior, and statutory caveats in the Barangay Problem Reporting and Management System. Any changes to system statuses must be updated here first.

---

## 1. Lifecycle Stages (Incident Reports)

> [!NOTE]
> This lifecycle covers **community problems only** (such as drainage, garbage, streetlights, potholes, flooding, or stray animals). Interpersonal disputes and peace-and-order complaints follow the Katarungang Pambarangay case lifecycle (§ 8). Community problem reports may be submitted by registered residents, guests, or anonymously ([DECIDED, D7]), with all submissions advancing through the same canonical 12 statuses.

1. **Intake**: Resident submits a report via the single-card form and receives an immediate unique tracking code (`BRG-XXXXXX`). The report is persisted immediately in status `submitted`. Submissions never hang waiting for external APIs.
2. **AI Triage (Background)**: An asynchronous listener invokes `GeminiService::analyze()`. Extracted data (suggested category, urgency, executive summary, photo audit, emergency flags) is saved to `report_ai_results`. If Gemini times out (30s) or fails, the report remains fully actionable with `ai_status = failed`.
3. **Branching**:
   - **Emergency Path**: If flagged as an emergency or crime, routing and priority scoring are bypassed; emergency hotlines are shown to the resident, Captain/tanods are alerted, and the report is pinned to the top of the queue.
   - **Standard Path**: Normal triage, duplicate radius checking, and priority calculation proceed.
4. **Staff Review**: Staff open `/admin/reports/{id}` to review a 3-way comparative card (Resident vs. AI vs. Final). The "Confirm and route" action is gated until category and urgency have been manually reviewed. Duplicates are suggested; only staff may execute a merge.
5. **Routing & Jurisdiction**:
   - **Mode A (Within Authority)**: Assigned to an internal barangay committee or personnel with an SLA deadline. Status moves to `routed`.
   - **Mode B (Beyond Authority)**: Generates a formal endorsement referral (`REF-YYYY-NNNN`) to an external agency with scheduled follow-ups. Status moves to `referred`.
   - **No Action**: If invalid, out of jurisdiction, or unreachable, moved to `no_action` with a required reason code and explanation.
6. **Action & Execution**: Assigned staff log progress updates (e.g., "Work started", "Inspection scheduled"). If material delays occur, staff log a progress note (`waiting_materials`) under `in_progress` without altering the status. If clarification is needed, status moves to `needs_info`.
7. **Resolution & Resident Verification**: Staff attach up to 5 resolution photos and a closing note, moving the case to `awaiting_confirmation`. The resident tracks the report, reviews the proof, submits a 1–5 star rating, and either **Confirms Fixed** (`resolved`) or **Reopens Case** (`reopened`).

---

## 2. Status Directory (Incident Reports - 12 Target Statuses)

| Status | Meaning | Who Acts Next | Resident Sees | SLA Clock |
| :--- | :--- | :--- | :--- | :--- |
| **`submitted`** | Received, untouched by staff | Staff | Submitted | **Open** |
| **`under_review`** | Staff has opened and is reviewing | Staff | Under Review | **Open** |
| **`routed`** | Office assigned, work not yet started | Office / Staff | Routed | **Open** |
| **`in_progress`** | Active field work underway | Office / Staff | In Progress (with progress notes) | **Open** |
| **`needs_info`** | Staff asked resident for clarification | Resident | Question response prompt | **Open** |
| **`awaiting_confirmation`** | Fix proposed with photo proof | Resident | Confirm Fixed / Reopen + 1–5 rating | **Open** |
| **`resolved`** | Resident verified fix as complete | Nobody (can reopen) | Resolved, proof gallery, rating | **Closed** |
| **`reopened`** | Resident disputed resolution | Staff | Reopened (investigating further) | **Open** |
| **`emergency_flagged`** | Critical safety emergency or crime | Captain / Tanod | 911 hotline alert banner | **Special** (bypasses SLA score) |
| **`referred`** | Transferred to external agency | External Agency | Referred (Agency name, dates, notes) | **Closed** (for barangay SLA) |
| **`merged`** | Duplicate linked into parent report | Nobody | Merged notice with primary link | **Closed** |
| **`no_action`** | Barangay takes no further action | Nobody (staff may reopen) | Closed: No Further Action + reason | **Closed** |

> [!NOTE]
> - **`on_hold` Removed**: "Waiting on materials" is recorded as a progress-update note under `in_progress`, keeping the core status list focused.
> - **`out_of_scope` & `closed` Merged**: Unified into `no_action` with mandatory reason codes.

---

## 3. Canonical Transition Table (Incident Reports)

| From Status | Allowed Target Statuses | Notes & Constraints |
| :--- | :--- | :--- |
| **`submitted`** | `under_review`, `routed`, `needs_info`, `referred`, `merged`, `no_action`, `emergency_flagged` | Initial state. |
| **`under_review`** | `routed`, `needs_info`, `referred`, `merged`, `no_action`, `emergency_flagged` | Staff triage in progress. |
| **`routed`** | `in_progress`, `needs_info`, `referred`, `awaiting_confirmation`, `resolved`, `no_action`, `emergency_flagged` | Office assigned. |
| **`in_progress`** | `in_progress`, `needs_info`, `referred`, `awaiting_confirmation`, `resolved`, `no_action`, `emergency_flagged` | Self-transition records a progress note without altering status. |
| **`needs_info`** | `under_review`, `routed`, `in_progress`, `needs_info`, `referred`, `awaiting_confirmation`, `resolved`, `no_action`, `emergency_flagged` | Self-transition records additional inquiry without altering status. |
| **`awaiting_confirmation`** | `resolved`, `in_progress`, `reopened`, `no_action`, `emergency_flagged` | Resident confirmation gate. |
| **`resolved`** | `in_progress`, `reopened` | Terminal resolution unless disputed or resumed. |
| **`reopened`** | `under_review`, `routed`, `in_progress`, `needs_info`, `referred`, `merged`, `no_action`, `emergency_flagged` | Increments `reopened_count`. |
| **`referred`** | `routed`, `in_progress`, `needs_info`, `resolved`, `merged`, `no_action` | Case remains tracked under external referrals. |
| **`emergency_flagged`** | `no_action`, `under_review`, `in_progress` | Default exit is `no_action` (`reason: emergency_redirected`); `under_review` for false alarms; `in_progress` (staff-only) if tanods handled it on-site. |
| **`merged`** | `under_review` | Staff-only recovery path for accidental merges (requires note). |
| **`no_action`** | `reopened`, `in_progress` | Staff-only (requires recorded note and security audit log). |

---

## 4. `no_action` Reason Codes & Storage

Every transition to `no_action` requires both a **standard reason code** and a **mandatory free-text explanation** visible to the resident.

### Allowed Reason Codes
1. `not_barangay_matter`: Issue falls outside municipal/barangay authority and cannot be referred.
2. `emergency_redirected`: Immediate life safety concern redirected to 911 / police / CDRRMO.
3. `cannot_resolve`: Valid concern that cannot be remediated due to insurmountable technical/legal barriers.
4. `invalid_report`: Spam, incomprehensible submission, or test report.
5. `resident_unreachable`: Necessary clarification was requested (`needs_info`), but reporter provided no valid contact or response within threshold.
6. `converted_to_case`: Civil incident report converted into a formal Katarungang Pambarangay dispute case (§ 8.8) ([DECIDED], Planned code alignment in `Report::NO_ACTION_REASONS`).

### Storage Architecture
- Stored in a **dedicated indexed column** on `reports`: `reports.no_action_reason` (nullable string).
- Recorded in the immutable audit row in **`status_logs`**: `status_logs.no_action_reason` and `status_logs.note`.

---

## 5. State Machine Diagram (Incident Reports)

```mermaid
stateDiagram-v2
    [*] --> submitted
    submitted --> under_review
    submitted --> emergency_flagged
    submitted --> no_action
    
    under_review --> routed: Mode A (Internal)
    under_review --> referred: Mode B (External Agency)
    under_review --> needs_info: Ask Resident
    under_review --> merged: Link Duplicate
    under_review --> no_action: Non-actionable
    under_review --> emergency_flagged

    routed --> in_progress: Work Begins
    routed --> no_action
    
    in_progress --> in_progress: Progress Note (e.g. Waiting Materials)
    in_progress --> needs_info
    in_progress --> awaiting_confirmation: Propose Fix + Proof
    in_progress --> no_action

    needs_info --> in_progress: Resident Replies
    needs_info --> under_review
    needs_info --> no_action

    awaiting_confirmation --> resolved: Resident Confirms Fixed
    awaiting_confirmation --> reopened: Resident Disagrees
    awaiting_confirmation --> no_action

    reopened --> in_progress
    reopened --> under_review

    emergency_flagged --> no_action: Redirected to 911
    emergency_flagged --> under_review: False Alarm
    emergency_flagged --> in_progress: Tanod Handled On-Site

    merged --> under_review: Staff Unmerge Recovery
    no_action --> reopened: Staff Reopen
    no_action --> in_progress: Staff Resume Work
    resolved --> reopened: Resident Reopens
```

---

## 6. Priority Scoring & Duplicate Calculation Rules

### Priority Score Formula
The numerical priority score ($0 \text{ to } 100+$) is computed dynamically by `PriorityService`:

$$\text{Priority Score} = (\text{Urgency Points} \times w_1) + (\text{Category Risk Weight} \times w_2) + (\text{Sensitive Place Proximity} \times w_3) + (\text{Supporting Reports} \times w_4) + (\text{Age in Days} \times w_5) + \text{Signal Modifiers}$$

- **Urgency Points**: Low = 1, Medium = 2, High = 3, Critical = 4.
- **Sensitive Places**: Multiplier applied if coordinates fall within 200m of schools, hospitals, or daycares.
- **Signal Modifiers**: $+10$ points each for `is_ongoing = true`, `people_at_risk = true`, or `is_recurring = true`.
- **Emergency Exemption**: Emergency-flagged reports bypass scoring and pin straight to the top of the queue.

### Duplicate Detection Thresholds
- **Distance Radius**: Within $100\text{–}150\text{ meters}$ using the Haversine geospatial formula ($300\text{m}$ for public `/api/nearby-reports`).
- **Temporal Window**: Open reports submitted within the last $7\text{ to }14\text{ days}$.
- **Matching Rule**: Matches must share the same `category_id` and meet a text similarity threshold. Matches populate `duplicate_suggestions`; merges are strictly executed by officials.

---

## 7. Referral Conventions & External Jurisdiction Handling

When an incident falls outside barangay authority (Mode B), the system routes it to the external referral workflow:

- **Referral Sequence Format**: Each referral receives a unique tracking number in the format `REF-YYYY-NNNN` (e.g. `REF-2026-0001`), where `YYYY` is the active fiscal year [D].
- **Default Follow-Up Interval**: Automatically scheduled for `sent_at + referral.follow_up_days` (default **3 days** per Barangay Secretary answer [A Q12], configured in dynamic settings `referral.follow_up_days` with fallback to `config/reports.php`).
- **Formal Letter Structure**: A printable formal endorsement letter is generated with:
  - Official Barangay Calarian seal and institutional header.
  - Date and unique `REF-YYYY-NNNN` identifier.
  - Recipient external agency/utility office and designated contact.
  - Incident narrative, geo-coordinates, purok, and nearest landmark.
  - Follow-up owner (defaults to the staff member who prepared the referral).
  - Signature block generated dynamically via `SignatoryResolver::forDocument('referral_letter')` [D]. Left blank if unresolved.
  - Watermark: Renders a visible **`DRAFT`** watermark while unconfirmed by officials.
- **Resident Privacy During Referral**: The resident tracking page displays only the recipient agency name, transmission status, and public notes (`visible_to_resident = true`). Staff names, official email addresses, and internal notes are never exposed.
- **SLA Clock Effect**: When a report transitions to `referred`, the internal barangay SLA clock stops (`Closed` for barangay SLA) while the external agency handles the matter. If the external agency cannot remediate, staff transition the case to `no_action` with reason `not_barangay_matter` or re-route internally.

---

## 8. Barangay Case Lifecycle (Katarungang Pambarangay & Blotter)

> [!WARNING]
> Statutory timelines have not been verified against the Katarungang Pambarangay rules. All deadline calculations reflect the Secretary's administrative working guidelines [A Q20, Q25].

> [!IMPORTANT]
> **Independent State Machine & Core Governance Mandate**:
> 1. The Barangay Case lifecycle operates entirely independently from the public incident report lifecycle. Cases originate either via direct walk-in complaints or blotter entries recorded at the barangay hall (`source = 'direct'`), or via administrative escalation of an existing civil report (`source = 'report_escalation'`).
> 2. **"The AI suggests, the rules route, the officials decide."** Neither summons issuance, hearing attendance, deadline expiration, nor Certificate to File Action (CFA) generation ever triggers an automatic status transition. All status changes require explicit action by authorized personnel.

### 8.1 State Machine Diagram (Cases)

```mermaid
stateDiagram-v2
    [*] --> filed: Direct Intake / Report Conversion
    
    filed --> in_mediation: Mediation Initiated (PB Summons)
    filed --> dismissed: Lack of Jurisdiction / Non-Appearance
    filed --> withdrawn: Complainant Withdraws
    
    in_mediation --> in_conciliation: Endorsed to Pangkat ng Tagapagkasundo
    in_mediation --> settled: Amicable Settlement Reached
    in_mediation --> unsettled: Mediation Failed / Impasse
    in_mediation --> escalated: Complainant Requests CFA & Endorsement
    in_mediation --> dismissed: Dismissed by PB
    in_mediation --> withdrawn: Complainant Withdraws
    
    in_conciliation --> settled: Amicable Settlement Reached
    in_conciliation --> unsettled: Conciliation Period Lapsed
    in_conciliation --> escalated: Complainant Requests CFA & Endorsement
    in_conciliation --> dismissed: Dismissed by Pangkat
    in_conciliation --> withdrawn: Complainant Withdraws
    
    unsettled --> escalated: Complainant Requests CFA & Endorsement
    unsettled --> in_conciliation: Re-referred to Pangkat (Staff Action)
    
    settled --> in_mediation: Reopened by Staff (Violation of Terms, Note Required)
    escalated --> in_mediation: Reopened by Staff (Procedural Defect, Note Required)
    dismissed --> filed: Reopened by Staff (Reinstatement, Note Required)
    withdrawn --> filed: Reopened by Staff (Reinstatement, Note Required)
    
    settled --> [*]
    escalated --> [*]
    dismissed --> [*]
    withdrawn --> [*]
```

### 8.2 Lifecycle Stages & Statutory Deadlines

0. **Resident Complaint Intake (Planned, [DECIDED, D4])**:
   - Residents may initiate a dispute filing via the online form ("File a Complaint"), which creates a **pending complaint intake**, never an active case.
   - No upfront 911 or VAWC screening gate is shown on the online form; VAWC-related matters are handled as agency-related referrals through the VAWC office representative in the barangay.
   - Staff review pending intakes in the "Pending intake" tab on the Cases Docket.
   - Staff check eligibility, residency, and duplicate warnings, then click **"Docket this"** to open the prefilled intake form. Only upon official confirmation is the case created at `filed` and the blotter entry logged.
   - Complainant and respondent details are **not** auto-created as parties; staff confirm them during docketing.
   - The resident receives an acknowledgement screen only ("received, please visit the barangay hall to sign and complete filing"), revealing **no case status, no respondent information, and no dispute tracking**, protecting respondent privacy. In-person appearance and physical signature remain mandatory.

1. **Intake & Docketing (`filed`)**:
   - Case is recorded with record type `blotter` or `complaint` [A Q18].
   - Docket number format: `{MM}-{SEQ:3}-{YEAR}` [A Q9, C5] (e.g., `11-001-2026`). Numbers restart every calendar year.
   - **One case record per complaint, with the blotter entry as its logbook reference** ([DECIDED, D3]): a complaint carries both a Barangay Case Number and a blotter reference number (two numbers, one record). Optional physical logbook series, page, and entry numbers may be recorded ([PROPOSED, P4]).
   - Standalone blotter-only records (staff walk-in intake, no dispute) receive only a blotter number and rest at `filed` indefinitely without summons, hearings, or deadline countdowns, until upgraded by staff to a formal complaint.

1a. **Pre-Summons Step & Eligibility Checklist ([CONFIRMED, C9; PROPOSED, P1])**:
   - Before a summons is issued, staff classify the case by type and decide blotter-only vs. formal complaint [CONFIRMED, C9].
   - Staff complete an informational **eligibility checklist** ([PROPOSED, P1]):
     1. Parties are individuals actually residing in the same barangay (or covered adjoining arrangement); juridical entities excluded.
     2. Matter is covered by Katarungang Pambarangay and not excluded (e.g. accused in police custody).
     3. Dispute is not an agency-only exclusion (VAWC-related matters are coordinated through the VAWC office representative in the barangay).
     4. Residency verification of both parties recorded.
   - Reminder: "Summons not yet delivered" ([CONFIRMED, C14]). Reminders are informational only and never block or auto-transition the case.

2. **Mediation Stage (`in_mediation`)**:
   - Presided over by the Punong Barangay (PB).
   - **Mediation clock start ([PROPOSED, P2; CONFIRMED unit, C11])**: The **15 working days** statutory mediation clock starts from the **first meeting of the parties before the Punong Barangay** (`first_meeting_on`), not docketing. If unrecorded, falls back to `filed_on`.
   - Summons are issued and hearings scheduled. Summons format: `SUM-YYYY-NNNN` with sequential `sequence_no`. Summons are counted for **information only** (no maximum cap or automated cutoff) [CONFIRMED, C11, Q20].
   - Staff record `served_by` (who delivered it) and **service result** ([CONFIRMED, C11]): `received`, `refused`, `not_found` (built-in `other` retained as "not in the Secretary's list").
   - If agreement is reached, transitions to `settled`. If unresolved after 15 working days, official endorses case to Pangkat.

2a. **Hearing Stage & Attendance Recording ([CONFIRMED, C11; PROPOSED, P3])**:
   - Hearing attendance is recorded per party as `appeared` or `failed_to_appear`.
   - Presiding officer logs hearing outcome and minutes note.
   - **Non-appearance handling ([PROPOSED, P3; CONFIRMED, C11])**:
     - *Respondent fails to appear*: absence and service result recorded in minutes; respondent loses the right to a connected counterclaim. The case does NOT end; the next step is endorsing to the Pangkat (`in_conciliation`) rather than issuing an immediate CFA.
     - *Complainant fails to appear after due notice*: staff may transition to `dismissed` with a mandatory recorded note. Dismissal is never automatic.
     - *Repeated unserved or ignored summons*: the barangay counts days, not summonses ([CONFIRMED, C11]). At 15 working days without settlement, the case displays "Ready for next step: endorse to Pangkat" (slate badge). It never auto-advances.
     - *Warning extension*: The soft warning on transitioning from `in_mediation` to `escalated` is extended to warn when the respondent never appeared at mediation ("The Pangkat usually must be constituted first").

3. **Conciliation Stage (`in_conciliation`)**:
   - Presided over by the Pangkat ng Tagapagkasundo (3 Lupon members).
   - Before conciliation begins, the PB constitutes the Pangkat (recording constitution date and members). The Pangkat convenes within 3 days of constitution.
   - Conciliation clock: **15 working days** [CONFIRMED, C11; PROPOSED unit, P2] starting from `endorsed_to_pangkat_on`.
   - Conciliation extension: One extension of up to 15 working days (max 30 working days total conciliation) may be granted by the Pangkat [CONFIRMED, C11, Q25; PROPOSED unit, P2]. Managed via dynamic `setting()` values.

4. **Resolution or Impasse (`settled` / `unsettled`)**:
   - `settled`: Signed written agreement terms recorded, settlement date noted, agreed amount/terms logged, signed PDF uploaded ([CONFIRMED, C12]).
   - `unsettled`: Conciliation period elapsed without agreement. Awaiting complainant decision.

5. **Escalation & Endorsement (`escalated`)**:
   - The complainant formally decides whether and when to move the case forward ([CONFIRMED, C12, Q22, Q23]).
   - Official issues Certification to File Action (CFA): signed by the **Pangkat Chairman** once endorsed to the Pangkat ([CONFIRMED, C3, Q5]), or by the Punong Barangay as Lupon Chairman if no Pangkat was constituted ([PROPOSED, P8]).
   - Case is endorsed to court, prosecutor, or PNP ([CONFIRMED, C9, Q18]).
   - Case papers and attachments dispatched via `ReferralService::createFromCase` [A Q29, P].

### 8.2.1 Statutory KP Procedure Sequence ([CONFIRMED, C9; PROPOSED, P7])
1. **Intake & Classification**: Complaint is made orally, in writing, or via pending online intake. Staff record narrative, classify case type, and decide blotter-only vs. formal complaint ([CONFIRMED, C9]).
2. **Summons Issuance**: Summons issued to respondent with notice of hearing to complainant, signed by the Punong Barangay ([CONFIRMED, C3]).
3. **Mediation**: Mediation before the Punong Barangay. 15-working-day clock begins on first meeting date (`first_meeting_on`) ([PROPOSED, P2; CONFIRMED, C11]).
4. **Endorsement to Pangkat**: If unresolved after 15 working days, case shows "Ready for next step: endorse to Pangkat". PB constitutes the Pangkat from the Lupon roster, and case is formally moved to `in_conciliation`. (Respondent's failure to appear leads here, not straight to CFA).
5. **Conciliation**: Pangkat convenes within 3 days; holds conciliation hearings for 15 working days (extendable by up to 15 more).
6. **Final Disposition**:
   - *Settlement*: Amicable settlement signed by parties and attested (`settled`).
   - *Impasse*: Case marked `unsettled`. Upon complainant request, CFA is issued by Pangkat Chairman and case is endorsed to PNP / Prosecutor / MTCC (`escalated`).

### 8.3 Case Status Directory

| Status Slug | Label | Stage Meaning | Next Allowed Statuses | SLA / Deadline Clock |
| :--- | :--- | :--- | :--- | :--- |
| **`filed`** | Filed | Docketed case awaiting initial action. Blotter entries rest here. | `in_mediation`, `dismissed`, `withdrawn` | Inactive for blotter; Active for complaint |
| **`in_mediation`** | In Mediation | Under active mediation before Punong Barangay. Summons and hearings issued. | `in_conciliation`, `settled`, `unsettled`, `escalated`, `dismissed`, `withdrawn` | **15 working days** |
| **`in_conciliation`** | In Conciliation | Under conciliation before Pangkat ng Tagapagkasundo. | `settled`, `unsettled`, `escalated`, `dismissed`, `withdrawn` | **15 working days** (+15d ext) |
| **`settled`** | Settled | Amicable settlement signed and binding. Terminal. | `in_mediation` (Reopened by staff with note) | **Closed** |
| **`unsettled`** | Unsettled | Proceedings concluded without consensus. Awaiting complainant decision. | `escalated`, `in_conciliation` | **Closed** (Awaiting action) |
| **`escalated`** | Escalated | CFA issued; case endorsed to PNP, Prosecutor, or Court. Terminal. | `in_mediation` (Reopened by staff with note) | **Closed** |
| **`dismissed`** | Dismissed | Dismissed for lack of interest, lack of jurisdiction, or non-appearance. Terminal. | `filed` (Reopened by staff with note) | **Closed** |
| **`withdrawn`** | Withdrawn | Complainant submitted voluntary withdrawal. Terminal. | `filed` (Reopened by staff with note) | **Closed** |

### 8.4 Special Case Types & Timeline Exemptions

- Case types with `uses_kp_timeline = false` (e.g. administrative matters, urgent child protection referrals) do not run the 15-working-day mediation clock [P].
- Case types with `is_confidential_default = true` (e.g. BCPC, VAWC matters) automatically pre-tick the `is_confidential` flag during intake [A Q7, C6].

### 8.5 Working Days & Deadline Calculation Rules

- Deadlines use the `App\Support\WorkingDays` calculation engine [D].
- Excludes Saturdays, Sundays, and official non-working holidays loaded dynamically from setting `calendar.holidays` [D].
- Warning indicators:
  - **Normal**: "Day X of 15" (slate badge).
  - **Due soon**: 3 or fewer working days remaining (gold badge `cases.deadline_warning_days = 3`).
  - **Overdue**: Working day count exceeded (red badge "Overdue X d").
  - **Ready for next step**: Impasse reached or deadline passed without settlement (slate badge).

### 8.6 Summons & Hearing Governance

- **Summons Counting & Numbering**: Summons are formatted as `SUM-YYYY-NNNN` and numbered sequentially for administrative tracking (`sequence_no`), but there is **no maximum summons limit** [CONFIRMED, C11, Q20]. The system does not lock or force status changes based on summons count; setting `cases.summons_limit` in `SummonsCounterService` is strictly display-only/legacy. The statutory trigger is governed entirely by the 15-working-day mediation clock, evaluated via `WorkingDays` and `OverdueService`, rendering the day-based "Ready for next step: endorse to Pangkat" indicator without automated status transitions ([CONFIRMED, C11]).
- **Summons Delivery & Service Results**: Each summons records `served_by` ("who delivered it") and service outcome ([CONFIRMED, C11]):
  - `received` — Respondent personally accepted and signed summons.
  - `refused` — Respondent refused to acknowledge or accept service.
  - `not_found` — Respondent could not be located at the given address.
  - `other` — Additional administrative exceptions (*retained from prototype, not in the Secretary's confirmed list*).
- **Hearing Outcomes & Attendance**: Attendance is recorded per party as `appeared` or `failed_to_appear`. Hearing status is tracked as `scheduled`, `in_progress`, `concluded`, `rescheduled`, `cancelled`.
  - Non-appearance of respondent: recorded in minutes; respondent loses right to a connected counterclaim ([PROPOSED, P3]); case moves to Pangkat constitution rather than an immediate CFA.
  - Non-appearance of complainant: staff may dismiss with mandatory written note; never automatic.
- **Voiding**: Any issued summons may be voided by authorized staff only with a **mandatory written explanation note**. Voiding decrements active summons display and logs an audit entry.

### 8.7 Intake Flow & Docketing Rules

1. **Intake Channels**:
   - **Direct Walk-in**: Recorded by staff/Lupon members at the barangay hall. Staff select **Blotter-only** (`blotter`) or **Formal Complaint** (`complaint`) as the first input field [CONFIRMED, C9].
   - **Resident Online Submission (Planned, [DECIDED, D4])**: Citizen submits online request; creates a pending intake; staff review eligibility, residency, and duplicate warnings, then click **"Docket this"** to open prefilled intake form.
2. **Party Search & Verification**: Staff searches existing persons by name or contact before creating new parties. Residency status is verified using Barangay ID, household masterlist, government ID with Calarian address, or Purok leader confirmation [CONFIRMED, C8, Q16]. Complainant and respondent profiles record: full name, alias, age or birth date, contact number, and complete address. For non-residents, also records home barangay/city and reason for being in the barangay ([CONFIRMED, C8]).
3. **Duplicate Warnings**:
   - **Person duplicates**: Matches on normalized name and contact number display existing person cards.
   - **Case duplicates**: Matches where both complainant and respondent match an existing open case within 30 days trigger a prominent warning: "Case #XX-XXX already filed between these parties on YYYY-MM-DD. Open existing or save anyway?" [A Q2, C8]. Staff decide whether to proceed.
4. **Docket Numbering & Two-Number Record**: Formatted as `{MM}-{SEQ:3}-{YEAR}` (e.g. `11-001-2026`) via `NumberingService::next('case', $fiscalYear)`. Sequence resets every fiscal year [CONFIRMED, C5, Q9]. A complaint carries both a Case Number and a Blotter Reference Number on the single case record ([DECIDED, D3]); optional physical logbook series, page, and entry fields are supported ([PROPOSED, P4, P4a]).
5. **Confidentiality**: Pre-ticked if case type defaults to confidential [A Q7, C2]. VAWC and child-protection cases remain accessible only to authorized personnel (Captain, Secretary, Peace & Order Kagawad, designated child-handling kagawads, VAW Focal person) [CONFIRMED, C2].
6. **Transaction**: Intake persists case, parties, initial narrative, and audit log in a single atomic database transaction.

### 8.8 Report to Case Conversion

- A civil incident report in `under_review` or `routed` may be converted to a case by staff with permission `cases.create`.
- **The Conversion Bridge ([DECIDED, D1])**: The Report Queue (community problems) and Cases Docket (disputes) remain strictly separated. Report-to-Case Conversion serves as the explicit bridge when a community report turns out to be an interpersonal dispute ([CONFIRMED, C13]).
- **Status Independence**: The report and the resulting case maintain distinct, decoupled lifecycles. The civil report transitions to `no_action` with `reason: converted_to_case` (or `routed` if municipal field work continues), while the resulting case starts at `filed`.
- **Cross-links**: `reports.case_id` and `cases.source_report_id` record the reciprocal relationship.
- **Privacy & PII**: Reporter details are not automatically copied to case parties without explicit staff confirmation, protecting resident anonymity where requested. Respondent details follow the exact same rule (no auto-created parties).

### 8.9 Settlement Agreement Capture

- Accessible via the **Record Settlement** action when status is `in_mediation` or `in_conciliation`.
- Required fields:
  - `settled_on`: Date agreement was signed [A Q24].
  - `terms`: Detailed narrative of agreed stipulations [A Q24].
  - `settlement_amount`: Optional monetary settlement figure (decimal 12,2).
  - `agreement_file`: Signed amicable settlement agreement PDF/image upload (stored on private disk).
- Personnel recording: Authorized staff member or Punong Barangay.
- Execution: `CaseStatusService::transition($case, 'settled', $note, $user)`.

### 8.10 Escalation & CFA Generation Flow

1. **Trigger**: Complainant formally requests escalation after failed conciliation or mediation impasse [A Q22, Q23].
2. **Form Data**: Destination agency (PNP, Prosecutor, MTCC), Complainant requesting, Request date, Reason code, and Pangkat Chairman signatory.
3. **Validation**: If mediation or conciliation deadline has not elapsed, system shows a soft advisory warning ("15 working days not yet completed. Proceed anyway?"). Officials decide.
4. **Printables**: Generates printable Certification to File Action (CFA) signed by Pangkat Chairman [A Q5] and Endorsement Letter signed by Punong Barangay.
5. **Agency Handoff**: If `case_referral_handoff` feature flag is active, calls `ReferralService::createFromCase()`, creating an external referral with case documents attached for agency portal pickup [A Q29, C7].

### 8.11 Canonical Case Transition Matrix

| From Status | Allowed Target Statuses | Required Permission Slug | Note / Input Required | Audit Log Written |
| :--- | :--- | :--- | :--- | :--- |
| **`filed`** | `in_mediation` | `cases.summons` | Summons date & hearing schedule | Yes (`case.mediated`) |
| **`filed`** | `dismissed` | `cases.edit` | Mandatory dismissal reason | Yes (`case.dismissed`) |
| **`filed`** | `withdrawn` | `cases.edit` | Signed withdrawal notice / note | Yes (`case.withdrawn`) |
| **`in_mediation`** | `in_conciliation` | `cases.edit` | Pangkat endorsement date | Yes (`case.conciliated`) |
| **`in_mediation`** | `settled` | `cases.edit` | Settlement terms, date, signed PDF | Yes (`case.settled`) |
| **`in_mediation`** | `unsettled` | `cases.edit` | Reason for mediation impasse | Yes (`case.unsettled`) |
| **`in_mediation`** | `escalated` | `cases.escalate` | CFA details, destination, complainant | Yes (`case.escalated`) |
| **`in_mediation`** | `dismissed` | `cases.edit` | Mandatory dismissal reason | Yes (`case.dismissed`) |
| **`in_mediation`** | `withdrawn` | `cases.edit` | Complainant withdrawal note | Yes (`case.withdrawn`) |
| **`in_conciliation`**| `settled` | `cases.edit` | Settlement terms, date, signed PDF | Yes (`case.settled`) |
| **`in_conciliation`**| `unsettled` | `cases.edit` | Conciliation impasse note | Yes (`case.unsettled`) |
| **`in_conciliation`**| `escalated` | `cases.escalate` | CFA details, destination, complainant | Yes (`case.escalated`) |
| **`in_conciliation`**| `dismissed` | `cases.edit` | Dismissal reason | Yes (`case.dismissed`) |
| **`in_conciliation`**| `withdrawn` | `cases.edit` | Complainant withdrawal note | Yes (`case.withdrawn`) |
| **`unsettled`** | `escalated` | `cases.escalate` | CFA details, destination, complainant | Yes (`case.escalated`) |
| **`unsettled`** | `in_conciliation` | `cases.edit` | Re-referral to Pangkat explanation | Yes (`case.reconciliated`)|
| **`settled`** | `in_mediation` | `cases.edit` | Mandatory reopening note (breach) | Yes (`case.reopened`) |
| **`escalated`** | `in_mediation` | `cases.edit` | Mandatory reopening note (defect) | Yes (`case.reopened`) |
| **`dismissed`** | `filed` | `cases.edit` | Mandatory reinstatement explanation | Yes (`case.reinstated`)|
| **`withdrawn`** | `filed` | `cases.edit` | Mandatory reinstatement explanation | Yes (`case.reinstated`)|

### 8.12 Confirmation Status Table

| Item / Rule | Status | Source Tag | Caveat / Note |
| :--- | :--- | :--- | :--- |
| 15 working days mediation | Confirmed by Secretary | **[CONFIRMED, C11, Q20, Q25]** | Mediation clock is strictly working days. |
| 15 working days conciliation | Proposed working days | **[CONFIRMED days C11, Q25; PROPOSED unit P2]** | Period is 15 days, extendable by up to 15 ("15 to 30 days"); working-day unit is proposed. |
| Conciliation extension max 15d | Confirmed by Secretary | **[CONFIRMED, C11, Q25]** | "Another 15 to 30 days" per Secretary. Setting-configurable. |
| Complainant requests CFA | Confirmed by Secretary | **[CONFIRMED, C12, Q22, Q23]** | Complainant chooses whether to move the case forward. |
| Pangkat Chairman signs CFA | Confirmed by Secretary | **[CONFIRMED, C3, Q5]** | Signs once case is endorsed to Pangkat. |
| Docket format `{MM}-{SEQ:3}-{YEAR}` | Confirmed by Secretary | **[CONFIRMED, C5, Q9]** | Sequence resets each calendar fiscal year. Applies to cases, blotters, and referrals. |
| Summons count is info-only | Confirmed by Secretary | **[CONFIRMED, C11, Q20]** | No automatic cutoff after 3 summons. Day-based indicator used instead. |
| Mediation clock start date | Proposal | **[PROPOSED, P2]** | Starts from `first_meeting_on` before Punong Barangay, with fallback to `filed_on`. |
| Non-KP cases skip clock | Proposal | **[PROPOSED, P1]** | `case_types.uses_kp_timeline = false`. |
| Pangkat Chairman per case | Follow-up Question | **[OPEN QUESTION]** | Whether Pangkat Chairman is standing or elected per-case from 3-member panel. |
| Summons service results & deliverer | Confirmed by Secretary | **[CONFIRMED, C11]** | `received`, `refused`, `not_found` and `served_by`. |
| Reminders list | Confirmed by Secretary | **[CONFIRMED, C14]** | Exactly four: hearing dates, summons not yet delivered, cases close to deadline, unsettled ready for next step. |

### 8.13 Code Alignment (`App\Support\CaseStatus`)

| Status in Section 8.3 | In `CaseStatus` (Built) | Target Constant in Phase 0.5 | Note |
| :--- | :--- | :--- | :--- |
| `filed` | `CaseStatus::FILED` | `CaseStatus::FILED = 'filed'` | Matches built code. |
| `in_mediation` | `CaseStatus::IN_MEDIATION` | `CaseStatus::IN_MEDIATION = 'in_mediation'` | Matches built code. |
| `in_conciliation`| **Missing** | `CaseStatus::IN_CONCILIATION = 'in_conciliation'` | To be added in Phase 0.5 migration/code. |
| `settled` | `CaseStatus::SETTLED` | `CaseStatus::SETTLED = 'settled'` | Matches built code. |
| `unsettled` | `CaseStatus::UNSETTLED` | `CaseStatus::UNSETTLED = 'unsettled'` | Matches built code. |
| `escalated` | `CaseStatus::ESCALATED` | `CaseStatus::ESCALATED = 'escalated'` | Matches built code. |
| `dismissed` | `CaseStatus::DISMISSED` | `CaseStatus::DISMISSED = 'dismissed'` | Matches built code. |
| `withdrawn` | `CaseStatus::WITHDRAWN` | `CaseStatus::WITHDRAWN = 'withdrawn'` | Matches built code. |

### 8.14 Printable Templates & Signatory Mapping (Step 9)

All printed forms and legal documents dynamically resolve authorized signatories through `SignatoryResolver::forDocument($documentType, $context)` without hardcoding personal names or positions. Printable documents carry the visible DRAFT watermark (`<x-ui.draft-watermark>`) until officially verified forms are ingested (Task 6).

| Printable Document | Key Fields & Mapping | Signatories ([PROPOSED], confirm against official forms) |
| :--- | :--- | :--- |
| **Barangay Blotter entry / certified extract** | Series, page no., entry no., date and time entered, facts of the case (5Ws), parties involved | Prepared by the Barangay Secretary; certified by the Punong Barangay |
| **Complaint form** | Barangay Case No., complainant details, respondent details, nature of rights violated, relief requested, date filed | Complainant signature; received by the Barangay Secretary or Lupon member who took it [CONFIRMED, C8]; acknowledged by the Punong Barangay [PROPOSED, P8] |
| **Summons (mediation)** | `SUM-YYYY-NNNN`, sequence no., case no., complainant, respondent, nature of complaint, hearing date/time/venue, instruction to appear | Punong Barangay / Barangay Captain [CONFIRMED, C3, Q5] |
| **Notice of hearing / conciliation summons** | Same as summons plus designated 3 Pangkat members and presiding chair | Pangkat Chairman [PROPOSED, P8] |
| **Notice to complainant** | Case no., parties, scheduled mediation/conciliation hearing date, time, and venue | Punong Barangay / Barangay Captain [PROPOSED, follows summons signatory] |
| **Amicable settlement** | Case no., parties, agreed terms, monetary settlement, date signed | Both parties; attested by the Punong Barangay (mediation stage) or Pangkat Chairman (conciliation stage) [PROPOSED, P8; CONFIRMED terms C12] |
| **Certificate to File Action (CFA)** | CFA number, case no., parties, dispute summary, non-settlement or non-appearance finding | Pangkat Chairman signs once case was endorsed to Pangkat [CONFIRMED, C3, Q5]; Punong Barangay signs if no Pangkat constituted, prepared by Barangay Secretary [PROPOSED, P8] |

---

## 9. Referral Lifecycle (External Agency Portal)

External referrals dispatched to partner agencies (PNP, CSWDO, City Engineer, Utilities) follow a dedicated lifecycle tracked in `referrals` and `referral_updates`:

```mermaid
stateDiagram-v2
    [*] --> prepared: Referral Drafted by Staff
    prepared --> sent: Letter Generated & Transmitted
    sent --> acknowledged: Agency Confirms Receipt / Accesses Portal
    acknowledged --> action_taken: Agency Logs Action / Resolution Update
    action_taken --> closed: Final Remediated or Endorsed
    sent --> closed: Closed Without Action / Inactive
```

1. **`prepared`**: Referral created by staff from report review Mode B or case escalation. Tracking number `REF-YYYY-NNNN` reserved.
2. **`sent`**: Letter finalized, signed via `SignatoryResolver`, and transmitted via agency email / portal. Follow-up reminder scheduled for `sent_at + 3 days` [A Q12].
3. **`acknowledged`**: Partner agency logs into portal (`/agency/referrals/{id}`), views case/report details, and downloads case papers [A Q29].
4. **`action_taken`**: Agency posts progress notes or remediation milestones via `agency.referrals.updates.store` [A Q29].
5. **`closed`**: Work completed or referred back to barangay. Handled in consultation with barangay staff.

---

## 10. Fiscal Year Effects & Boundary Rules

1. **Finalized Definition**: A fiscal year corresponds to one calendar year (January 1 to December 31) [A Q8].
2. **Locked Year Immutability (`FiscalYearGuard`)**:
   - When a fiscal year is set to `status = 'locked'` or `is_locked = true`, all records associated with that fiscal year become strictly immutable.
   - Any attempt to update, transition status, delete, or add summons/hearings/updates to a locked year's case or report throws `FiscalYearLockedException` and is aborted by `FiscalYearGuard`.
3. **Roll-over Mechanics**:
   - The Barangay Secretary or Admin activates the new fiscal year via `/admin/fiscal-years`.
   - Only one fiscal year may have `status = 'active'` at any given time.
   - Ongoing cases in `in_mediation` or `in_conciliation` remain open and editable in their originating fiscal year until concluded, but do not block the activation of the new fiscal year.
4. **Numbering Sequence Restart**:
   - `NumberingService` resets sequence numbers to `001` at the commencement of each fiscal year.
   - Sequence uniqueness is scoped strictly to `(fiscal_year_id, type)`.
   - Imported historical records (WS6) retain their original docket numbers and do not consume active fiscal year sequences.

---

## 11. Staff Screens & Status-to-Action Mapping

To ensure operational consistency across UI redesigns (queue filters, action panels, and review workspaces), staff actions are strictly bound to lifecycle states:

### 11.1 Report Status-to-Action Mapping (Community Problems)

| Report Status | Staff's Operational Job | Primary Action | Secondary / Exception Actions |
| :--- | :--- | :--- | :--- |
| **`submitted`** | Initial intake & comparative triage (Resident vs. AI). | **Confirm and Route** (verifies category, urgency, office); *for reports with unverified audio, shows **Listen and verify** as primary action prior to routing* | Ask reporter for info (`needs_info`), Flag emergency, Take no action (`no_action`), Mark duplicate (`merged`). |
| **`under_review`** | In-depth triage, jurisdiction verification, and assignment. | **Route Internally** or **Prepare Referral** | Ask reporter for info, Take no action, Flag emergency, Mark duplicate, Convert to dispute case (`cases.create`). |
| **`needs_info`** | Awaiting resident clarification response. | **Review Clarification** (resumes to `under_review`) | Re-route internally, Take no action with `resident_unreachable`, Re-ask info. |
| **`routed`** | Formal assignment dispatch to Tanods / maintenance crew. | **Start Work** (transitions to `in_progress`) | Re-assign office or personnel, Prepare referral, Ask info, Take no action, Convert to dispute case (`cases.create`). |
| **`in_progress`** | Active field inspection, repairs, or municipal coordination. | **Propose Resolution** (requires proof photos & notes) | Add progress milestone note (self-transition), Ask info, Refer upward, Take no action. |
| **`awaiting_confirmation`** | Awaiting resident satisfaction rating or 14-day SLA expiry. | **Confirm Resolution** (transitions to `resolved`) | Reopen upon resident dispute (`reopened`), Resume work (`in_progress`). |
| **`resolved`** | Archived completed incident record. | **View Resolution Dossier** | Reopen on recurrence or dispute (`reopened` or `in_progress`). |
| **`reopened`** | Investigate unresolved or recurring failure. | **Resume Work** (transitions to `in_progress`) | Re-route internally, Ask info, Take no action with explanation. |
| **`referred`** | Monitor external agency action and 3-day follow-up. | **Log Agency Update** (milestone or portal message) | Re-route internally (`routed`), Resolve upon agency fix (`resolved`), Take no action (`not_barangay_matter`). |
| **`merged`** | Consolidated secondary duplicate incident. | **View Canonical Parent** | Staff-only recovery unmerge (`under_review`). |
| **`no_action`** | Non-actionable report with mandatory reason code. | **View Reason & Justification** | Reopen/recover report (`in_progress` or `reopened`). |
| **`emergency_flagged`** | High-priority safety coordination with 911 / CDRRMO. | **Dispatch Emergency Response** (`in_progress`) | Transfer to review (`under_review`), Conclude redirection (`no_action` with `emergency_redirected`). |

### 11.2 Case Status-to-Action Mapping (Barangay Dispute Cases)

| Case Status | Staff / Official Operational Job | Primary Action | Secondary / Exception Actions |
| :--- | :--- | :--- | :--- |
| **`filed`** | Docketed intake; classification & eligibility check. | **Issue Summons** (`cases.summons`; moves to `in_mediation` with hearing schedule) | Dismiss (`cases.edit`, note required), Complainant withdraws (`cases.edit`), Edit parties / narrative. |
| **`in_mediation`** | Active mediation before Punong Barangay (15 working days). | **Record Hearing / Summons** or **Record Settlement** (`settled`) | Endorse to Pangkat (`in_conciliation` at 15 working days), Mark unsettled (`unsettled`), Dismiss for non-appearance, Void summons. |
| **`in_conciliation`** | Conciliation before 3-member Pangkat (15 working days + ext). | **Record Conciliation Hearing** or **Record Settlement** (`settled`) | Mark conciliation impasse (`unsettled`), Extend conciliation (+15d max), Dismiss (`cases.edit`), Complainant withdraws. |
| **`settled`** | Amicable settlement signed and binding. | **View Settlement Dossier & PDF** | Reopen by staff upon breach of terms (`in_mediation`, mandatory note). |
| **`unsettled`** | Proceedings concluded without consensus. | **Issue CFA & Endorse** (`cases.escalate`; complainant requested) | Re-refer to Pangkat (`in_conciliation`, explanation note). |
| **`escalated`** | Formal CFA issued; case endorsed to Court / PNP. | **View Endorsement Packet & CFA** | Reopen by staff upon procedural defect (`in_mediation`, note required). |
| **`dismissed`** | Dismissed for lack of jurisdiction or complainant non-appearance. | **View Dismissal Note** | Reinstate case (`filed`, mandatory explanation note). |
| **`withdrawn`** | Complainant submitted voluntary withdrawal. | **View Withdrawal Record** | Reinstate case (`filed`, mandatory explanation note). |

### 11.3 Pending Complaint Intake Actions (Staff Review Queue)

| Intake State | Staff Operational Job | Primary Action | Secondary / Exception Actions |
| :--- | :--- | :--- | :--- |
| **`pending`** | Resident-submitted complaint request awaiting verification. | **Docket this** (prefills WS3 intake form; creates case at `filed` + blotter entry) | Redirect/Reject (requires mandatory explanation note and selected outcome reason), View confidential safety details. |
| **`docketed`** | Successfully docketed into Barangay Cases Docket. | **View Docketed Case** (links to `/admin/cases/{id}`) | Archive intake record. |
| **`redirected`** / **`rejected`** | Rejected as ineligible, confidential, or out-of-scope. | **View Rejection Reason** | Staff override / re-open for review. |

---

## 12. Voice Report Handling (Record-First Pipeline)

### 12.1 Lifecycle Integration & Transcript Statuses
Voice intake provides a "record first, AI fills the form" intake workflow for both resident submissions (`reports.source = voice`) and staff hotline phone intake (`reports.source = phone_log`), with audio records stored in `report_audios`. Transcript status is tracked via `report_audios.transcript_status`:
- `pending`: Audio uploaded/recorded; awaiting Gemini analysis.
- `done`: Clean, high-confidence analysis completed; form fields pre-filled with "From recording" labels.
- `partial`: Partially clear audio; fields stated in intelligible sections are pre-filled, with an advisory banner warning staff or residents to review carefully.
- `needs_verification`: Speech was unclear, empty, invalid JSON, or spoken in an unrecognized dialect. No candidate fields are pre-filled (all show "Not provided"); forward routing transitions are blocked until staff listen and verify.
- `failed`: Technical error, network timeout, or unreadable audio container format.
- `mock`: Generated canned extractions for local demo/testing mode (`voice.mock_transcription` enabled or mock scenario requested).

### 12.2 Human-in-the-Loop Verification Gate
**Core Rule**: **If the audio is uncertain or the AI cannot understand it, nothing is guessed and the decision goes to staff.**
- **Record First, AI Fills the Form**: The recording comes first and drives the form. One Gemini call extracts candidate form fields (`caller_name`, `caller_phone`, `caller_email`, `purok`, `landmark`, `category`, `urgency`, `emergency`, `not_stated`). AI-filled fields are labeled "From recording" and are editable. Unstated fields display muted italic "Not provided" (stored as `NULL`).
- **PII-Free Description**: The model returns both raw `transcript` and cleaned `description` (with caller names, phone numbers, and emails removed). The description input receives `description`, never the raw transcript.
- **Staff-Only Transcript**: Raw transcript is returned only by the staff intake endpoint (`/admin/reports/phone/analyze`) and is stored in `report_audios.transcript`. The resident endpoint never returns it.
- **No Guessed Text on Unclear Audio**: When `transcript_status` is `needs_verification` or `failed`, no pre-filled values are populated. All fields display "Not provided". A partial transcript, if any, is stored solely as an "unverified draft".
- **Report Acceptance**: The report is never blocked from submission. It enters `submitted` with `source = voice` or `phone_log`. If the reporter typed nothing, a system placeholder description is stored: `"(Voice recording attached — needs staff audio verification)"`.
- **Review Gate**: The report is flagged as "needs staff verification". On the review page, a prominent banner appears: *"Voice report needs verification. Listen to the recording and confirm or enter what was said before routing."*
- **Action Blocking**: "Confirm and route" and all forward transitions are strictly blocked until an authorized staff member listens to the audio and either confirms the unverified draft or inputs the verified text (stored as the staff description; the resident's original input is never overwritten). The verifier's user ID and timestamp (`verified_by`, `verified_at`) are logged.
- **No New Statuses**: Voice verification is implemented strictly within the existing human-in-the-loop review gate. It adds **no new report status** and **no automatic transition**.
- **Queue Representation**: On the report queue, reports needing audio verification display a small microphone indicator beside the tracking code and a Next Action hint (`Verify audio`), without increasing row height or requiring an extra table column.

### 12.3 Resident Voice Intake & Optional Category Flow
- **Quick Report by Voice**: Positioned at the top of the resident form. Citizens consent to AI analysis via *"My recording is processed by AI and seen by barangay staff only"* before recording.
- **Optional Category**: Category selection is optional ("Choose a category (optional)"). Citizens can submit without selecting a category.
- **Unclassified Handling**: If submitted without a category (`category_id = null`), the report is accepted into `submitted`. The downstream asynchronous text-classification AI pipeline suggests a candidate category for staff review.
- **Route Gate Integrity**: Forward routing remains strictly blocked until staff manually select and confirm the final Category and Urgency on the review workspace. The review page displays *"Not chosen by resident"* next to the AI category suggestion.
- **Provenance Tracking**: AI-filled fields (`description`, `category`, `purok`, `landmark`, `emergency`, contact flags) are recorded with submit-time `edited` flags on `report_audios.provenance`. Never stores contact values in provenance JSON.

### 12.4 Export and Review Page Integrity
- **Review Page Field Display**: On `/admin/reports/{report}`, unstated contact fields (`caller_name`, `caller_phone`, `caller_email`) and unstated location fields on voice and phone-log reports display muted italic *"Not provided"* (`.review-not-provided`). Anonymous reports display *"Not stated (anonymous report)"* without revealing or attempting to parse identities.
- **Export and Print Cleanliness**: When generating CSV reports (`GET /admin/reports/export`, `GET /admin/cases/export/csv`) or printable report slips (`/reports/{report}/print`), unstated or unverified fields are emitted strictly as empty/blank strings (`""`). The UI placeholder string *"Not provided"* and verification draft tags are never written into data exports or printed receipts.
