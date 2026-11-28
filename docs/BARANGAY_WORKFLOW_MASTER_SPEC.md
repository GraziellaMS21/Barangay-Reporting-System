# Barangay Problem Reporting and Management System
# Workflow Improvement — Master Specification (Source of Truth)

| Field | Value |
|---|---|
| Structure | **Part 1** Resident Side → **Part 2** Admin / Barangay Staff Side → **Part 3** Implementation Roadmap by TR |
| Supersedes | The earlier "Decision Summary", chat-derived roadmaps, and any conflicting documents |
| Current position | TR1 ✅ · TR2 ✅ prototype (official GIS pending) · **TR3 ⏭ next (begins with TR3.1-R0 audit)** |
| Last consolidated | 2026-11-28 |

---

## How to Use This Document

1. **Parts 1 and 2 describe WHAT the system should do** (flows, information, frontend expectations, rules). **Part 3 describes WHEN and IN WHAT ORDER it is built** (TR1–TR9, with clean task lists).
2. **This document is the source of truth for intent. The code is the source of truth for what currently exists.** If they disagree, do not silently pick one. Record it in the Open Items and Discrepancy Log (end of Part 3).
3. Every flow uses the same template: **Purpose → Information → Steps → Flow → Frontend → Backend → Rules → Acceptance**.
4. Markers:
   - **[AUDIT FIRST]**: verify in the real codebase before designing or changing anything.
   - **[CONFIRM WITH BARANGAY]**: legal or procedural rule; validate with barangay personnel before automating.
5. Status legend: ✅ Done · 🟡 Partial · ⏭ Next · 🔲 Planned · ⏸ Deferred (blocked on external input).

### Corrections applied to the source material

| # | Issue in the source | Resolution |
|---|---|---|
| 1 | Stray image captions and site names (street.co.uk, nilam.io, khaleejtimes.com, lguss.dilg.gov.ph, Dribbble) | Removed; they were embedded-image artifacts. |
| 2 | Conversational text ("Yes. I would…", "I recommend…", "What should we do next?") | Converted into definitive requirements. |
| 3 | Admin-side geographic verification had been placed under TR2.5 | Corrected: TR2 is resident-only; it is **TR3.4**. |
| 4 | Staff audit called "TR2.5.1-R0" | Renamed **TR3.1-R0**. |
| 5 | Two admin structures (Concern Management A–E vs. "Concern Intake" navigation) | Reconciled: **Concern Intake = TR3; Concern Management = TR4** (see Part 2-A). |
| 6 | Messy task lists (TR2.4.x sub-IDs mixed with TR2.x; TR3 tasks mixed with scenarios and scope; TR6–TR9 had no tasks) | Rebuilt in Part 3: one table per TR with ID, task, type, deliverable, status. TR2.4.x kept only as a history map. |
| 7 | Blotter/Complaint/Case described as new while existing Cases/Blotter may already exist | Marked **[AUDIT FIRST]** (TR5.1). |
| 8 | Legal references (LGC §§408–410) appeared as unattributed links | Kept as reference, marked **[CONFIRM WITH BARANGAY]**. |
| 9 | Trailing "WHAT IF'S" chat link | Removed; its questions are covered in Part 2-A (jurisdiction scenarios). |

---

## Goal, Principles, Glossary

### Goal
Improve the **existing** system so that residents can report concerns more easily (AI assistance, automatic location detection), while barangay personnel receive properly classified, located, organized information to review, route, manage, and convert into official records when appropriate.

### Non-goals
- No new system. Reuse the existing form, Gemini AI infrastructure, Leaflet map, Purok records, referral workflow, and submission process.
- No removal or unnecessary change of working features or validation.
- No renaming of routes, tables, models, controllers, form field names, or internal identifiers when only wording changes.

### Principles
1. **Residents describe. AI assists. Staff decide.** AI never makes official legal or administrative decisions.
2. **Preserve originals.** Original description, location, and submission are never overwritten.
3. **No automatic escalation.** A submission never automatically becomes a blotter entry, formal complaint, or Barangay Case.
4. **Graceful degradation.** AI, GPS, or boundary data unavailable → manual fallback; submission still works. Emergency never waits for AI.
5. **Separate records, explicit links.** Blotter, Complaint, and Barangay Case have their own IDs, statuses, permissions, and audit trails.
6. **Reuse before build.** Audit first, extend second, create new only when nothing suitable exists.
7. **Never silently guess.** Uncertain location or classification is shown as uncertain.

### Glossary

| Term | Meaning |
|---|---|
| **Concern** | Any resident submission through *Report a Concern* (formerly "Report a Community Problem"). |
| **Community Problem** | AI intake class: community conditions/services (garbage, streetlight, drainage). |
| **Incident** | AI intake class: an event that happened and may need investigation. |
| **Possible Formal Complaint** | AI intake class: may need formal complaint handling (suggestion only). |
| **Uncertain / Needs Clarification** | AI intake class: not enough information. |
| **Blotter Entry** | Official record that an incident was reported to the barangay. |
| **Complaint** | Record of a grievance and request for formal barangay action; goes through intake and acceptance. |
| **Barangay Case** | Official file for formal Katarungang Pambarangay (KP) proceedings, created only after complaint acceptance. |
| **Referral** | Existing workflow handing a concern to an external office/agency; stays linked to its parent. |
| **Point-in-Polygon (PiP)** | Determines which supplied boundary contains a coordinate; it does not create boundaries. |

AI intake classes are **intake guidance, not official classifications.** Staff classification is the official one.

---
---

# PART 1 — RESIDENT SIDE

## 1.1 Reporting Entry Point (Flow R1)

**Purpose:** One general entry for all concerns. Residents should not have to decide whether something is a problem, incident, or complaint.

**Why "Report a Concern":** the system accommodates more than community problems: garbage accumulation, damaged streetlights, noise disturbances, neighbor disputes, physical altercations, suspicious activities, and other incidents requiring barangay attention.

**Information shown on the home screen**
- **Report a Concern** (primary entry)
- **File a Complaint** (separate; for residents who already intend to formally bring a complaint)
- **Emergency guidance** (always available)

**Flow**
```
Home ─┬─► Report a Concern ─► Step 1 ► Step 2 ► Step 3 ► Tracking code
      ├─► File a Complaint (existing, unchanged)
      └─► Emergency guidance (never gated by AI)
```

**Frontend: terminology changes**

| Old | New |
|---|---|
| Report a Community Problem (home card) | Report a Concern |
| Submit Problem Report (button) | Report a Concern |
| Report a Problem (form title) | Report a Concern |
| What is the problem? (label) | What is your concern? |
| Public map reporting button | Report a Concern |
| Navigation labels | Updated to match |

Update English and Filipino translations. **Do not change** route names, table names, model names, controller names, form field names, or internal identifiers used by the admin workflow.

**Acceptance:** no resident-facing screen still says "Community Problem/Problem Report"; existing routes and tests still pass.

---

## 1.2 The Three-Step Reporting Form

The existing three-step structure is **retained**, not replaced.

### Step 1 — Describe Your Concern (AI-assisted) (Flow R2)

**Purpose:** Residents describe what happened in their own words and optionally receive AI help.

**AI assists by:** understanding the description; recommending a classification; suggesting a category; generating a concise summary; asking follow-up questions when important information is missing; identifying whether a different workflow (e.g., formal complaint) may be needed.

**Classification guidance:** Community Problem · Incident · Possible Formal Complaint · Uncertain / Needs Clarification. The resident can review and correct all suggestions.

**Information**

| Item | Source | Notes |
|---|---|---|
| Original description | Resident (typed or voice) | Always preserved verbatim |
| Voice transcript | Existing Gemini voice analysis | Preserved and integrated |
| Suggested classification / category | AI | Advisory only |
| AI summary | AI | Concise; never replaces the original |
| Clarification questions | AI | Only when important info is missing |
| Complaint-workflow hint | AI | Points to the separate File a Complaint entry |
| Confidence / failure state | AI service | Failure never blocks submission |
| Resident decision | Resident | Accepted / corrected / dismissed |

**Steps**
1. Resident types or dictates the concern.
2. Optionally taps **Analyze Concern**; a loading indicator appears.
3. Suggestions appear (classification, category, summary, questions, complaint hint).
4. Resident accepts, edits, or dismisses suggestions and answers questions if desired.
5. Resident may proceed to Step 2 at any time, with or without AI.

**Flow**
```
Describe ─► [Analyze Concern] ─► POST pre-submission analysis (rate-limited)
                                   ├─ success ─► suggestion panel ─► accept / edit / dismiss
                                   └─ failure ─► neutral notice; form fully usable
          └─► Next (always allowed) ─► Step 2
```

**Frontend expectations**
- "Analyze Concern" button, disabled while loading or when the description is empty or too short.
- Non-blocking loader; the textarea stays editable.
- Suggestion panel: classification badge, editable category dropdown, summary with use/dismiss, clarification questions with inline answers.
- If classification is Possible Formal Complaint: inline banner suggesting **File a Complaint** (link); the resident may still continue.
- Original description always visible and unchanged.
- Voice reporting and emergency guidance remain available.
- EN/FIL strings.

**Backend**
- New pre-submission method in `GeminiService`; new action in `ReportController`; rate-limited route in `routes/resident.php`. (The existing queued `ProcessNewReport` runs after submission and cannot serve interactive Step 1, so it stays unchanged.)
- Validate the description; **send only the description** to the AI (no name, phone, email, coordinates).
- Structured response: classification, category, summary, clarification_questions[], suggests_formal_complaint, confidence, emergency_flag.
- Reuse existing Gemini configuration (per audit: Gemini 3.1 Flash-Lite) and suitable complaint-detection logic.

**Acceptance:** valid recommendations; rate limiting enforced; AI failure still allows submission; no contact data reaches Gemini; voice reporting unaffected.

### Step 2 — Location (Flow R3)

**Purpose:** Capture an accurate location and detect Barangay and Purok automatically from official geography.

**Residents can:** use current GPS location; manually place a pin; move the pin to correct inaccurate GPS.

**Information**

| Item | Notes |
|---|---|
| Latitude / longitude | From GPS or pin |
| Detected Barangay | Must be Calarian to count as inside |
| Detected City | Zamboanga City (illustrative display) |
| Detected Purok | From boundary polygons, never from a bounding box |
| Street / nearby address | Optional, when available (reverse geocoding is a separate optional enhancement) |
| Landmark / extra details | Resident free text |
| Resident-confirmed/corrected Purok | Stored alongside the detected value |
| Detection status | exact / near-boundary / inside-no-purok / outside / unavailable |

Example display (illustrative, not an actual detected address): Barangay Calarian · City Zamboanga City · Purok 5 · GPS coordinates.

**Steps**
1. Resident uses GPS or places/moves the pin.
2. Frontend sends coordinates to the detection endpoint.
3. Backend: validate coordinates → inside Calarian? → which Purok polygon contains the point? → return structured result.
4. Frontend shows the detected location for confirmation.
5. Resident confirms, corrects the Purok, moves the pin, or adds a landmark.
6. On final submission the backend **revalidates** the coordinates and Purok independently.

**Flow**
```
GPS / pin ─► detect ─► result
  ├─ exact Purok match ────────────► show + confirm
  ├─ near shared boundary ─────────► show ambiguity; ask resident to verify
  ├─ inside Calarian, no Purok ────► resident selects Purok / adds landmark
  ├─ outside Calarian ─────────────► warning (not a rejection)
  ├─ GPS denied / unavailable ─────► manual pin + manual Purok fallback
  └─ boundary data missing/invalid ► manual fallback; no silent assignment
Submit ─► backend revalidates
```

**Frontend expectations**
- Leaflet + OpenStreetMap; draggable marker; Barangay boundary and Purok polygons rendered.
- Detected-location card (Barangay, City, Purok, coordinates) with plain-language confirmation.
- Distinct, non-alarming states for each result; clear outside-Calarian warning.
- Purok dropdown for manual correction; landmark field.
- UI does not claim official accuracy while boundary data is provisional.
- Mobile-friendly; GPS denial handled gracefully.

**Backend**
- `app/Services/BoundaryService.php`: validate coordinates; Calarian check; Purok match; ambiguity/unmatched detection; structured result.
- Pure PHP geometry (no SpatiaLite dependency); GeoJSON as target format.
- Server-side validation on submission, with Purok validation and deduplication.

**Rules:** uncertain results **never silently assign a Purok**. Outside Calarian → warning, not rejection. Detected and resident-selected Purok are stored separately so staff can compare.

**Acceptance:** correct Purok for test coordinates; ambiguous points handled safely; outside points warn; submitted coordinates independently validated; manual fallback works.

### Step 3 — Photo and Contact Information (Flow R4)

**Preserve existing functionality exactly:** optional photo upload; name; mobile number; email; anonymous reporting; data privacy consent; existing validation; tracking-code generation; submission confirmation; queued post-submission AI processing.

**Steps:** add optional photo → provide contact (or anonymous per existing rules) → accept privacy consent → submit → receive tracking code.

**Frontend:** layout and behavior unchanged apart from terminology.

**Acceptance:** submission succeeds even when AI assistance and location detection are unavailable (subject to existing required-field rules); tracking code and confirmation work.

---

## 1.3 Resident-Side Safeguards

| AI and location functionality | Rule |
|---|---|
| AI classification | Recommendation only |
| AI summary | Preserves original description |
| AI clarification | Resident can answer or correct |
| Possible complaint | Does not automatically create a formal case |
| Possible blotter | Does not automatically create an official blotter |
| Purok detection | Based on official geographic boundaries |
| Location outside Calarian | Show warning |
| AI/GPS unavailable | Provide a manual fallback |
| Emergency | Must not wait for AI |
| Privacy | Personal contact details never sent to Gemini |

## 1.4 Purok Boundary Preparation (Flow R5)

Barangay Calarian has Purok boundaries that can be obtained, so real geographic data replaces manual Purok selection.

**Responsibilities:** Barangay provides official boundaries → authorized administrator manages/imports → backend performs detection → resident sees and confirms.

**Steps when official data arrives**
1. Obtain official records (GeoJSON, KML, Shapefile, or maps needing digitization).
2. Convert to GeoJSON if needed.
3. Verify the coordinate reference system.
4. Check for missing, overlapping, or invalid boundaries.
5. Match official Purok identifiers to existing canonical database records.
6. Import without breaking existing report references.
7. Deactivate provisional geometries safely.
8. Re-run detection and regression tests.

**Rules:** point-in-polygon uses supplied boundaries and does not create them. **Never invent polygon coordinates or use the current map bounding box as a substitute.** The prototype's provisional data is for demonstration only.

---
---

# PART 2 — ADMIN / BARANGAY STAFF SIDE

**Main principle:** Residents describe what happened. AI assists with understanding and classification. Barangay personnel review the information, determine the action, and create official records when necessary.

The admin side has a unified process for submissions from *Report a Concern*, but **keeps separate official workflows** for blotter recording, complaint intake, and Barangay Case processing.

**Naming reconciliation**
- **Concern Intake** (TR3): the existing Community Reports module, improved. Initial staff review, verification, classification, and routing.
- **Concern Management** (TR4): lifecycle handling of concerns that stay under ordinary barangay handling.
- **Blotter / Complaint / Barangay Case** (TR5): separate official records.

**Proposed admin navigation** (names are proposals; **inspect existing navigation before changing labels or routes**)
```
Dashboard
Concern Intake            ← TR3 (Incoming · Under Review · Needs Clarification · Needs Verification · Awaiting Next Action)
Referrals and Routing     ← existing module (TR3 integrates, TR7 extends)
Cases and Blotter         ← TR5
Monitoring and Reports    ← TR8
System Administration     ← TR6 (includes Purok Management)
```
Queue states are filters/tabs, not a page per status. Intake sub-states may be derived views rather than new database statuses **[AUDIT FIRST]**.

---

## 2-A. Concern Management (Concern Intake and Handling)

Handles submissions from *Report a Concern*: garbage, damaged streetlights, drainage, noise, stray animals, neighbor disputes, physical altercations, suspicious activities, and other incidents. All enter a common receiving and review process; classification then determines handling.

### Existing functionality to preserve (verify in TR3.1-R0)

| Existing capability | Treatment |
|---|---|
| Community Reports page | Retain; improve; consider renaming to Concern Intake |
| Incoming report queue | Preserve; improve filters/organization |
| AI classification and analysis | Preserve; clarify advisory role |
| AI failure indicators | Preserve |
| AI comparison/review | Preserve |
| Duplicate suggestions (ranked; dismiss; merge with **mandatory note**; merge controls hidden for emergency reports) | Preserve all safeguards |
| Proof and attachment handling | Preserve |
| Resolution proposal | Preserve |
| Manual report closure (authorization limits) | Preserve |
| Referrals and routing | Preserve and integrate |
| Cases and Blotter | Leave for TR5 |

### Flow S1 — Incoming Concerns Queue

**Purpose:** let staff see what needs attention without opening every report.

**Information per row:** tracking code; submitted time; AI classification and category; urgency/emergency flag; detected Purok; location verification state; jurisdiction state; assignee; referral state; **Next Action** (retain if present); AI-failure indicator; duplicate indicator.

**Frontend:** tabs/filters: New · Under Review · Needs Clarification · Unverified Location · Uncertain Jurisdiction · Awaiting Assignment/Action · Referred. Emergency items prioritized. Role-aware visibility.

**Acceptance:** every listed condition is filterable; existing filters and counts still work.

### Flow S2 — Individual Concern Review

**Purpose:** one organized page where staff verify and decide.

| # | Section | Contents |
|---|---|---|
| 1 | Concern Information | Tracking code; **original description (immutable)**; submitted date/time; photos/attachments; voice transcript; AI summary, suggested classification, confidence, failure info |
| 2 | Reporter Information | Name or anonymous status; contact (permission-gated); available residence info; residence verified? **Never assume incident Purok = reporter's residential Purok** |
| 3 | Incident Information | What happened; people/property involved; incident date/time; reported location; supporting evidence |
| 4 | Geographic Verification | Map with reported pin; Barangay boundary and Purok polygons; detected Purok; resident-selected Purok; landmark; coordinates; staff verification result |
| 5 | Classification and Jurisdiction | AI-suggested vs staff-selected classification; reporter residence assessment; incident jurisdiction assessment; staff decision and explanation |
| 6 | Related Records and Activity | Duplicate suggestions; existing referrals; related reports; staff notes; review history |

**Steps**
1. Open the concern; review sections 1–3.
2. Verify location (section 4); correct Purok/pin if needed (logged).
3. Verify, or leave pending, the reporter's residence.
4. Confirm or override classification (reason required on override).
5. Record the jurisdiction assessment.
6. Choose a next action (Flow S4).
7. Everything is written to the review history.

**Frontend:** original description always visible and read-only; AI content labeled "AI suggestion" and visually separate from staff determinations; overrides/corrections require a reason (saved with user and timestamp); sensitive fields permission-gated; referral panel embedded (Flow S5).

### Flow S3 — Geographic and Jurisdiction Verification

Keep **four separate** pieces of information: (1) reporter residence verification, (2) incident location verification, (3) jurisdiction assessment, (4) administrative handling decision.

| Scenario | Staff UI shows | Expected staff action |
|---|---|---|
| Calarian resident, incident inside Calarian | Residence and incident both inside | Verify and assess for local handling |
| Outside resident, incident inside Calarian | Residence outside; incident inside | Continue reviewing the incident |
| Calarian resident, incident outside Calarian | Residence inside; incident outside | Assess assistance or referral |
| Uncertain incident location | Missing/ambiguous evidence | Request clarification or keep verification pending |
| Outside incident involving a Calarian resident | Incident outside; Calarian connection | Assess local assistance vs referral |

**Rules:** never auto-reject based on reporter residence or detected location. The final jurisdiction decision is made by authorized personnel; the system records who, when, and why **[CONFIRM WITH BARANGAY who is authorized]**. The staff view *displays and verifies* Purok via the existing GIS; Purok CRUD and boundary import belong to TR6.

### Flow S4 — Staff Decisions, Assignment, and Routing

Possible actions (each recorded with actor, timestamp, reason):

| Action | Result |
|---|---|
| Handle as community-related concern | Goes to ordinary handling (TR4) |
| Handle as incident requiring attention | Ordinary handling with incident classification |
| Request additional information | Needs-clarification state; resident notified |
| Assign responsible personnel | Assignment recorded |
| Refer to external office/agency | Existing referral workflow (Flow S5) |
| Refer for blotter recording | Handoff only; blotter created by authorized staff in TR5 |
| Endorse for formal complaint intake | Handoff only; complaint created in TR5 |
| Other action allowed by procedure | Free text with reason |

A concern may need more than one related process (e.g., incident recorded in the blotter while its complaint is separately assessed). Until TR5 exists, handoff actions record intent only **[AUDIT FIRST]** whether existing blotter/complaint features can receive them.

### Flow S5 — Referrals Integration (preserve existing)

**Existing process (verify):** staff initiates a referral → Referral record created → report gets assigned office and status *referred* → referral appears in the referral queue → staff manages letter, marks sent, records updates → agency processes, updates, closes.

**Improvements:** the review page shows whether referred, receiving office/agency, reason, initiated date, letter sent?, latest agency update, current status, and whether barangay action is still required. Staff can initiate a referral from the review page under **existing permission rules**.

**Known issue to investigate [AUDIT FIRST]:** agency closure may leave the parent report in *referred* and not notify the resident. Trace `ReferralService::close()` and its notifications **before** changing any referral lifecycle behavior.

### Flow S6 — Review History and Audit Trail

Record classification changes, location/Purok corrections, residence/jurisdiction assessments, decisions, assignments, referrals initiated, notes, and duplicate dismiss/merge. Each entry: actor, role, timestamp, old value, new value, reason.

### Flow C1–C5 — Concern Handling (Concern Management, TR4)

| Flow | Information | Steps | Frontend |
|---|---|---|---|
| **C1 Assignment** | Assignee, office, priority, due date (if used) | Assign → assignee notified → reassignments logged | Assignee selector; workload; assignment history |
| **C2 Status and progress** | Status, updates, investigation/inspection notes, proof uploads | Update status per existing transition rules → resident tracking updates | Status timeline; proof upload (preserve existing); transition buttons |
| **C3 Staff notes** | Internal notes | Add/view; never exposed to residents | Attributed, timestamped notes panel |
| **C4 Resident notifications** | Status changes, clarification requests, referral outcomes, proposed resolution | Notify through existing channels | Resident tracking page stays current |
| **C5 Resolution and closure** | Action taken; proposed resolution; resident response | Staff records → resident confirms or disputes (existing workflow) → closure per authorization rules | Resolution form; confirm/dispute states |

**Rule:** concerns endorsed to blotter, complaint, or case keep a link to those records and are **not** shown as ordinary "resolved" reports.

---

## 2-B. Blotter, Complaint, and Barangay Case Management

**Start with an audit [AUDIT FIRST]:** determine whether the existing `BarangayCase` and KP workflow already cover the formal case stage, and whether current complaint intake is separate enough to support three records without duplicating functionality. **No new tables or migrations until that audit is approved.**

A resident submission does **not** automatically become a blotter entry, accepted complaint, or Barangay Case. Authorized personnel decide, based on applicable law and barangay procedure.

### The three records

| Record | Answers | Created when |
|---|---|---|
| Blotter Entry | What incident was reported and recorded? | Authorized staff decide to record an incident (referred from Concern Intake or reported at the office) |
| Complaint | What grievance was submitted and what was decided at intake? | Resident uses File a Complaint, staff endorse a concern, or complaint received at the office |
| Barangay Case | What happened during the formal proceedings? | **Only** after the complaint passes intake and acceptance |

These are not mandatory steps for every matter:
- Noise issue: blotter only, or community handling only.
- Neighbor dispute: blotter → complaint → case if accepted.
- A complaint may exist without any blotter (unless approved barangay procedure requires one).
- A complaint outside Lupon authority stays a complaint intake record, is referred, and creates **no** case.

### Flow B1 — Blotter Management

- **Information:** blotter number; date/time reported; date/time of incident (if known); reporting person; persons involved; description; location; receiving/recording personnel; supporting documents; actions taken; related-record links.
- **Steps:** (a) receive incident (from Concern Intake or office) → (b) authorized staff decide whether to record it → (c) create the entry → (d) record actions taken (e.g., referral to Punong Barangay, further assessment) → (e) link related complaint/case → (f) maintain: search, view, add permitted supplements, review history.
- **Frontend:** blotter list with search/filters; create form; detail view with actions log and related-records panel.
- **Rules:** the blotter is an incident record, **not** a substitute for complaint intake or case proceedings; no mediation/hearing workflow inside it; the original entry and its history are preserved.

### Flow B2 — Complaint Management

- **Sources:** File a Complaint (resident), concern endorsed from Intake, complaint received at the office.
- **Information:** complainant; respondent; nature; supporting details/documents; incident/dispute location; related blotter entries; intake status; assessment notes; acceptance decision and reason.
- **Steps:** (a) receive → (b) review → (c) assess jurisdiction, venue, subject-matter exclusions (**staff decision, not AI**) → (d) determine action: request corrections / proceed with intake and acceptance / refer elsewhere / record why it cannot proceed / other permitted action → (e) once intake and acceptance are complete, an **authorized officer endorses it for case creation**.
- **Frontend:** intake queue; review page with party details; assessment panel with required reason; endorse-for-case action restricted by role. AI may organize information but never decides.
- **Rules:** the complaint record and its intake history stay preserved after a case is created. **[CONFIRM WITH BARANGAY]** exact intake rules and forms before automating (LGC §§408–410 are the reference basis).

### Flow B3 — Barangay Case Management

- **Creation:** create and docket the case; generate/assign case number; link originating complaint; carry over verified party information; link related blotter entries; record responsible officials and dates; set initial status.
- **Procedural workflow** (illustrative; follow actual law and barangay procedure):
  1. Docketing and verification
  2. Mediation before the Punong Barangay: summons/notices, schedules, attendance, records, outcomes
  3. Pangkat constitution when required: members, notices, schedules, attendance
  4. Conciliation proceedings: hearings, minutes, settlement attempts
  5. Outcome: amicable settlement, arbitration award, repudiation, failed settlement, certification to file action
  6. Disposition and closure, plus post-proceeding actions
- **Frontend:** case file with tabs (Overview · Parties · Proceedings · Notices/Summons · Pangkat · Documents · Outcome · Related Records · Audit); stage indicator.
- **Rules:** close a case only per its procedural outcome; closing never deletes the source complaint or blotter; all documents, agreements, proceedings, and audit history remain accessible to authorized personnel.

### Flow B4 — Relationships Between the Records

```
Blotter ──(optional)──► Complaint ──(accepted)──► Barangay Case
   └────────────── optional link ───────────────────┘
Concern (resident submission) ──► may link to any or all of the above
```
- A blotter may exist without a complaint; a complaint may exist without a blotter; neither automatically creates the other; a complaint is never automatically accepted.
- A blotter entry may link to more than one related record when circumstances require.
- Each record keeps its own identifier, status, permissions, and audit history, and staff can navigate between related records.
- Example (illustrative numbers only): BL-2026-0012 → CMP-2026-0008 → BC-2026-0004. Real numbering follows barangay procedure and the Numbering Schemes setting (TR6).

**Conceptual data model** (not a migration instruction; adapt to the audit findings)
```
BlotterEntry: blotter_number, incident_details, reporting_person, persons_involved, incident_location, recorded_by
Complaint:    complaint_number, complainant_details, respondent_details, complaint_details,
              intake_status, acceptance_decision, related_blotter_entry_id (optional)
BarangayCase: case_number, complaint_id, parties, case_status, proceedings,
              summons_and_notices, agreements_and_awards, disposition
```

---

## 2-C. System Administration

| Area | Responsibility |
|---|---|
| Barangay Information | Configure official barangay details |
| Purok Management | Register Puroks; manage geographic boundaries |
| Geographic Data | Import, validate, update official boundary datasets |
| Personnel | Staff, positions, assignments |
| User Accounts | Resident, staff, and agency accounts |
| Roles and Permissions | Access to functions and official records |
| Categories | Concern categories and classifications |
| Offices and Departments | Responsible offices |
| Routing Rules | Where concerns should be directed |
| Numbering Schemes | Official numbering for blotters, complaints, cases, documents |
| Agency Configuration | External agency accounts and information |

### Flow A1 — Purok Management (new; an extension of System Administration, not a separate application)

- **Information:** Purok name/ID; boundary geometry; source; import batch; validation results; modified-by/at; change history.
- **Steps:** view registered Puroks → import official dataset → display boundaries on a map → validate (missing, overlapping, invalid) → activate → update when officially revised → deactivate provisional geometry.
- **Frontend:** Purok list; map of boundaries; import wizard (upload → validate → preview → confirm); validation report; history of changes and who made them.
- **Rules:** only authorized personnel may modify official boundaries; important changes are historized; import must not break existing report references; backend PiP uses whatever active data is registered.

---

## 2-D. Agency Accounts and Referral Workflow

**Flow:** staff reviews a concern → determines external assistance is needed → selects agency and prepares referral → agency receives it through its designated account → authorized agency personnel review and respond → staff monitor progress and record the outcome.

**Three roles stay distinct:** barangay personnel (manage concerns and referrals) · barangay administrators (configure agency accounts and permissions) · agency personnel (receive and respond to assigned referrals).

**Frontend:** agency directory; referral creation with reason and supporting information; agency inbox showing only its own referrals; update/response forms; referral history and status timeline.

**Rules:** agency accounts see only what their role authorizes; every referral stays linked to its originating concern or other official record; parent and referral statuses stay synchronized (see the closure issue in Flow S5).

---

## 2-E. Monitoring and Reports

| Information | What administrators monitor |
|---|---|
| Concerns | Submitted, pending, ongoing, referred, resolved |
| Concern classifications | Community-related, incidents, other categories |
| Blotter entries | Recorded incidents and related actions |
| Complaints | Received, under review, accepted, referred, otherwise concluded |
| Barangay Cases | Docketed cases, ongoing proceedings, dispositions |
| Referrals | Agency assignments, responses, progress |
| Geographic distribution | Concerns and incidents grouped by Purok |
| Personnel | Workload and processing activity |
| Performance | Processing time, resolution time, statistics |
| Audit logs | Who performed important actions and when |

**Counting rule:** concerns, blotter entries, complaints, and Barangay Cases are counted **separately**. One concern can link to several official records, so they are never presented as separate resident submissions. Monitoring gives a connected view without merging records or statuses.

---

## 2-F. Roles and Permissions (proposed; align to existing roles after TR3.1-R0)

| Role | Main responsibility |
|---|---|
| Receiving staff | Register submissions, prepare records |
| Blotter personnel | Create and maintain blotter entries |
| Complaint intake personnel | Review and process complaint intake |
| Punong Barangay / authorized Lupon personnel | Designated KP functions |
| Lupon / Pangkat Secretary | Maintain assigned procedural records and documents |
| Administrator | Accounts, permissions, numbering, configuration |
| Authorized supervisor | Review records, monitor workflow |
| Agency personnel | Respond only to assigned referrals |

An administrator does **not** automatically gain authority to decide the substance of a complaint just because they administer the software.

## 2-G. Cross-Cutting Requirements

| Area | Requirement |
|---|---|
| Privacy | Contact details never sent to AI; sensitive fields permission-gated; consent preserved |
| AI | Advisory only; failures visible to staff but never blocking; rate-limited |
| Audit | Important actions logged with actor, time, old/new value, reason |
| Preservation | Originals never overwritten; closing never deletes source records |
| Localization | EN and FIL for all resident-facing text |
| Mobile/accessibility | Resident flow works on mobile with GPS fallback |
| Testing | Existing tests keep passing; each phase adds regression tests |
| Legal | KP rules validated by the barangay before automation |

---
---

# PART 3 — IMPLEMENTATION ROADMAP BY TR

The TR1–TR9 structure is the master roadmap and is **not renumbered**. Each TR below has an objective, status, dependencies, a clean task table, scope boundaries, and exit criteria.

Task **Type** values: **Audit** (read-only) · **Design** (no code; for approval) · **Build** (implementation) · **Test** (verification).

## Roadmap Overview

| TR | Name | Status | Depends on | Covers (Part) |
|---|---|---|---|---|
| TR1 | Existing System Audit | ✅ Complete | — | Part 1, Part 2 groundwork |
| TR2 | Resident Reporting Improvements | ✅ Prototype · 🟡 official GIS pending | TR1 | Part 1 |
| TR3 | Staff Intake and Classification | ⏭ Next | TR1, TR2 | Part 2-A (S1–S6) |
| TR4 | Concern Management | 🔲 | TR3 | Part 2-A (C1–C5) |
| TR5 | Complaint, Blotter, Barangay Case | 🔲 | TR3, TR5.1 audit | Part 2-B |
| TR6 | System Administration | 🔲 | TR3 (TR2.4 official data) | Part 2-C |
| TR7 | Agency Workflow | 🔲 | TR3, TR6 | Part 2-D |
| TR8 | Monitoring and Reports | 🔲 | TR3–TR7 | Part 2-E |
| TR9 | Full-System Verification | 🔲 | TR1–TR8 | All |

TR2.4 (official Purok data) can proceed in parallel with anything else; it only blocks *verification against official data*, not development.

---

## TR1 — Existing System Audit and Implementation Planning  ✅

**Objective:** understand the existing resident reporting workflow, AI architecture, and location system before any change.

| ID | Task | Type | Deliverable | Status |
|---|---|---|---|---|
| TR1.1 | Resident reporting and AI integration audit | Audit | Architecture map: `resident/index`, `resident/create`, `ReportController`, `GeminiService`, `ProcessNewReport`, `VoiceAnalyzeController`, `routes/resident.php`; decision to build a separate pre-submission AI endpoint | ✅ |
| TR1.2 | Geographic boundary and Purok detection audit | Audit | Findings on `Purok` model/migration/seeder, `RoutingService`, `routes/engine.php`; decision to use GeoJSON + backend PiP; list of prerequisites | ✅ |

**Key findings retained:** typed reports had no pre-submission AI; classification ran only post-submission via queue; voice analysis already Gemini-powered; AI failure never blocks submission; Leaflet/OSM, GPS, draggable pin, lat/long already exist; seven Puroks registered with **no polygon data**; no reverse geocoding; no PiP.

---

## TR2 — Resident Reporting Improvements  ✅ prototype

**Objective:** implement Part 1 (Report a Concern, AI-assisted Step 1, automatic location in Step 2, preserved Step 3).

| ID | Task | Type | Deliverable | Status |
|---|---|---|---|---|
| TR2.1 | Change resident-facing terminology | Build | "Report a Concern" everywhere resident-facing; EN/FIL; no internal renames | ✅ |
| TR2.2 | Pre-submission AI classification backend | Build | `GeminiService` method, controller action, rate-limited route, validation, description-only payload | ✅ |
| TR2.3 | AI assistance interface in Step 1 | Build | Analyze Concern button, suggestion panel, clarification, complaint hint, accept/correct/dismiss | ✅ |
| TR2.4 | Prepare official Purok boundary data | Build/Data | Provisional boundaries and Purok setup done; **official import pending** | 🟡 |
| TR2.5 | Backend Point-in-Polygon detection | Build | `BoundaryService`, ambiguity handling, server-side coordinate validation | ✅ |
| TR2.6 | Integrate detection into Step 2 | Build | Detected-location display, Purok polygons on map, resident correction and fallbacks | ✅ |
| TR2.7 | Preserve Step 3 and submission behavior | Build/Test | Optional photos, contact, anonymity, consent, validation, tracking code intact; Purok validation and deduplication | ✅ |
| TR2.8 | Testing and regression verification | Test | Browser, backend, AI, resident regression, failure modes | ✅ prototype |

**Dependency note:** TR2.1–TR2.3 are independent of GIS. TR2.5 can be verified only with provisional data until TR2.4 official data exists.

**Remaining TR2 work: TR2.4 official integration (deferred)**
1. Obtain official geographic records.
2. Validate CRS and geometry.
3. Match official Purok identifiers to canonical database records.
4. Import verified geometry without breaking report references.
5. Deactivate provisional geometries safely.
6. Re-run detection and regression tests.

(Import tooling itself is delivered in TR6.2/TR6.3.)

**History map (implementation sub-IDs retained, not for new planning)**

| Sub-ID used during development | Belongs to |
|---|---|
| TR2.4.1 GIS architecture and system audit | TR2.4 / TR2.5 prep |
| TR2.4.2 Detection backend and boundary engine | TR2.5 |
| TR2.4.3 Provisional Calarian boundary and Purok integration | TR2.4 / TR2.6 |
| TR2.4.3-FIX Resident location info and detection display | TR2.6 |
| TR2.4.3-FIX-2 Render Purok polygons on resident map | TR2.6 |
| TR2.4.4 Browser interaction and failure-mode verification | TR2.8 |
| TR2.4.5 Official GIS integration | TR2.4 ⏸ |
| TR2.4.6 Server-side location validation | TR2.5 / TR2.7 |
| TR2.4.6-FIX Purok validation and deduplication | TR2.7 (**uncommitted; preserve**) |

**Exit criteria:** resident flow works end to end with AI and location features on **and** off; no contact data sent to Gemini; all existing resident and AI tests pass.

---

## TR3 — Staff Intake and Classification  ⏭ NEXT

**Objective:** improve the existing Community Reports workflow into a Concern Intake workspace where staff review, verify, classify, and decide next actions, including internal handling and external referrals. Details: Part 2-A, Flows S1–S6.

| ID | Task | Type | Deliverable | Flows |
|---|---|---|---|---|
| **TR3.1-R0** | Existing staff and admin reporting workflow audit | Audit | Read-only report (checklist below); Discrepancy Log updated | all |
| TR3.2 | Staff concern review interface design | Design | Approved queue layout, review-page sections, action set | S1, S2 |
| TR3.3 | Concern verification and classification | Build | AI-recommendation review; staff classification with override reason | S2 |
| TR3.4 | Incident location and jurisdiction verification | Build | Geographic Verification section; Purok verification/correction; jurisdiction assessment and decision | S3 |
| TR3.5 | Reporter and incident information verification | Build | Separated reporter vs incident details; residence verification | S2, S3 |
| TR3.6 | Staff decisions, assignment, and routing | Build | Next actions, assignment, clarification requests, referral initiation from review page, escalation/handoff | S4, S5 |
| TR3.7 | Review history and audit trail | Build | Logged decisions, corrections, overrides | S6 |
| TR3.8 | Staff workflow testing and regression | Test | Intake + referral integration verified; existing duplicate-review and closure tests pass | all |

Overlap is allowed (design may reveal backend gaps for TR3.3–TR3.6), but TR3.2 must be approved before any Build task starts.

**TR3.1-R0 audit checklist (read-only)**
1. **Interface:** queue, filters, review page, displayed information, staff actions, duplicate review, AI comparison/failure indicators, resolution and closure.
2. **Referrals:** how initiated; permissions; status change; appearance in referral module; effect of agency updates/closure on the parent report; resident notifications and history.
3. **Geography:** stored location fields; what admin sees; reuse of GIS data; can staff correct Purok/location; is correction logged.
4. **Classification and jurisdiction:** AI categories and staff fields; statuses and transitions; is jurisdiction in the database; can staff record a reason.
5. **Permissions:** roles that can review, modify, assign, close, refer; sensitive-report limits; staff vs administrator differences.
6. **Related modules:** current relation of Complaint, Cases/Blotter, Purok management, Agency management to Community Reports (understand only).

**Audit restrictions:** no code edits, migrations, database changes, seeders, commits, or pushes. **Preserve all uncommitted TR2.4.6-FIX changes.**

**Scope**

| In TR3 | Not in TR3 (later) |
|---|---|
| Improve review UI and queue | Blotter creation/management → TR5 |
| Admin-side terminology alignment | Complaint management → TR5 |
| AI classification review/override | Barangay Case proceedings → TR5 |
| Location, reporter, jurisdiction verification | Purok CRUD / boundary import → TR6 |
| Assignment and routing | Full agency management → TR7 |
| Existing referral integration | Comprehensive analytics → TR8 |
| Review history and audit trail | Broader system audit reporting → TR8 |

**Exit criteria:** staff can review, verify, classify, assign, request clarification, and refer from the review page; every override or correction is logged with a reason; existing duplicate-review safeguards and referral behavior unchanged (or deliberately fixed after tracing); no official record is created automatically.

---

## TR4 — Concern Management  🔲

**Objective:** manage verified concerns and incidents that remain under ordinary barangay handling through one unified workflow. Details: Flows C1–C5.

| ID | Task | Type | Deliverable | Flows |
|---|---|---|---|---|
| TR4.1 | Design unified Concern Management | Design | Approved lifecycle, status model aligned to existing transitions | C1–C5 |
| TR4.2 | Concern assignment and responsibility | Build | Assignment, reassignment, workload view | C1 |
| TR4.3 | Concern status and progress tracking | Build | Status timeline, progress updates, proof uploads (preserved) | C2 |
| TR4.4 | Staff notes and internal communication | Build | Internal notes panel | C3 |
| TR4.5 | Resident notifications and updates | Build | Notifications aligned to the workflow | C4 |
| TR4.6 | Resolution and closure | Build | Action taken, proposed resolution, resident confirm/dispute, closure; link retention for endorsed concerns | C5 |
| TR4.7 | Concern Management testing | Test | Lifecycle regression suite | all |

**Exit criteria:** a concern can be assigned, progressed, resolved, confirmed/disputed, and closed; endorsed concerns keep links and are not counted as ordinarily resolved.

---

## TR5 — Complaint, Blotter, and Barangay Case Management  🔲

**Objective:** implement three distinct but related official records. Details: Part 2-B, Flows B1–B4.

| ID | Task | Type | Deliverable | Flow |
|---|---|---|---|---|
| **TR5.1** | Audit existing Complaint, Blotter, and BarangayCase functionality | Audit | Report: what exists, separation of intake vs case, reuse vs gaps. **Gate: approval before any schema work** | all |
| TR5.2 | Design Blotter Management | Design | Approved fields, screens, permissions | B1 |
| TR5.3 | Implement Blotter recording and management | Build | Create/search/view/supplement; actions log; links | B1 |
| TR5.4 | Design formal Complaint intake | Design | Approved intake/assessment/acceptance flow (**[CONFIRM WITH BARANGAY]**) | B2 |
| TR5.5 | Implement Complaint Management | Build | Intake queue, review, assessment, outcomes, endorse-for-case | B2 |
| TR5.6 | Design Barangay Case Management | Design | Approved case file, stages, documents | B3 |
| TR5.7 | Implement Barangay Case records and proceedings | Build | Docketing, mediation, Pangkat, conciliation, outcomes, disposition (reusing existing KP workflow where it exists) | B3 |
| TR5.8 | Relationships between concerns, blotters, complaints, and cases | Build | Explicit links, navigation between related records, no auto-creation | B4 |
| TR5.9 | Test formal administrative workflows | Test | Separate IDs/statuses/permissions/audit verified; closure never deletes sources | all |

**Exit criteria:** the three records exist separately with explicit links; none is created or accepted automatically; the existing KP workflow is reused, not duplicated.

---

## TR6 — System Administration  🔲

**Objective:** manage configuration and authorized personnel; deliver Purok and boundary management. Details: Part 2-C, Flow A1. *(Task list below is proposed; the source defined scope only.)*

| ID | Task | Type | Deliverable | Status |
|---|---|---|---|---|
| TR6.1 | Audit existing admin configuration, accounts, and roles | Audit | Inventory of what exists vs Part 2-C table | Proposed |
| TR6.2 | Purok Management | Build | Purok list, map view, change history, authorization rules | Proposed |
| TR6.3 | Official boundary import and validation | Build | Import wizard, validation (missing/overlapping/invalid), safe activation/deactivation; completes TR2.4 official integration | Proposed |
| TR6.4 | Personnel and user accounts | Build | Staff, positions, assignments, resident/staff/agency accounts | Proposed |
| TR6.5 | Roles and permissions | Build | Role matrix aligned to Part 2-F and audit findings | Proposed |
| TR6.6 | Categories, offices, routing rules, numbering schemes | Build | Managed configuration | Proposed |
| TR6.7 | Barangay information and agency configuration | Build | Official details; agency registration and permissions | Proposed |
| TR6.8 | Administrative audit logs and testing | Build/Test | Logged admin actions; regression tests | Proposed |

**Exit criteria:** only authorized personnel can change official boundaries; boundary changes are historized; official data replaces provisional without breaking report references.

---

## TR7 — Agency Workflow  🔲

**Objective:** extend the **existing** referral workflow for external coordination. Details: Part 2-D. *(Task list proposed.)*

| ID | Task | Type | Deliverable | Status |
|---|---|---|---|---|
| TR7.1 | Audit existing referral and agency functionality; resolve closure-sync issue findings | Audit | Report and agreed fix plan | Proposed |
| TR7.2 | Agency directory | Build | Registered agencies with contact and scope | Proposed |
| TR7.3 | Referral and endorsement | Build | Referral creation, letters, supporting info | Proposed |
| TR7.4 | Agency assignment and status tracking | Build | Agency inbox, status timeline, staff monitoring | Proposed |
| TR7.5 | Agency responses and feedback | Build | Response/update forms; outcome recording | Proposed |
| TR7.6 | Referral history | Build | Full history linked to parent record | Proposed |
| TR7.7 | Agency workflow testing | Test | Access isolation, status sync, notifications | Proposed |

**Exit criteria:** agencies see only their assigned referrals; parent and referral statuses stay synchronized; resident is notified of outcomes.

---

## TR8 — Monitoring and Reports  🔲

**Objective:** administrative monitoring across all records without merging them. Details: Part 2-E. *(Task list proposed.)*

| ID | Task | Type | Deliverable | Status |
|---|---|---|---|---|
| TR8.1 | Define metrics and counting rules | Design | Approved definitions (separate counts for concerns, blotters, complaints, cases) | Proposed |
| TR8.2 | Concern statistics | Build | Submitted/pending/ongoing/referred/resolved; classification breakdown | Proposed |
| TR8.3 | Incident and blotter statistics | Build | Entries and related actions | Proposed |
| TR8.4 | Complaint and case reports | Build | Complaint outcomes, docketed cases, dispositions | Proposed |
| TR8.5 | Purok-based and geographic monitoring | Build | Concerns/incidents by Purok, geographic summaries | Proposed |
| TR8.6 | Agency referral reports | Build | Assignments, responses, progress | Proposed |
| TR8.7 | Performance and personnel reports | Build | Processing/resolution time, workload | Proposed |
| TR8.8 | Audit log reporting and testing | Build/Test | Who did what, when; verification of counts | Proposed |

**Exit criteria:** counts never double-count linked records; Purok grouping uses detected/verified Purok.

---

## TR9 — Full-System Verification  🔲

**Objective:** verify the entire resident-to-administration workflow before deployment.

| ID | Task | Type | Status |
|---|---|---|---|
| TR9.1 | Resident workflow testing | Test | Proposed |
| TR9.2 | Staff workflow testing | Test | Proposed |
| TR9.3 | Complaint, blotter, and case testing | Test | Proposed |
| TR9.4 | Role and permission testing | Test | Proposed |
| TR9.5 | Data privacy and security testing | Test | Proposed |
| TR9.6 | Integration testing | Test | Proposed |
| TR9.7 | User acceptance testing | Test | Proposed |
| TR9.8 | Deployment preparation | Build | Proposed |

**Exit criteria:** all workflows pass; no automatic escalation paths exist; privacy requirements verified; UAT signed off.

---

## Testing Matrix (carried forward)

| Area | Expected |
|---|---|
| Terminology | All resident-facing labels updated |
| AI analysis / rate limit / failure | Valid output; throttled; submission still works |
| Voice reporting | Preserved |
| Location detection | Correct Purok for test points; boundary cases safe |
| Outside location | Warning, no accidental rejection |
| Backend validation | Coordinates independently checked |
| Privacy | No personal contact data to Gemini |
| Submission | Tracking code and confirmation work |
| Staff review | Overrides logged; duplicate and merge safeguards intact |
| Referrals | Parent/child status sync; resident notified |
| Formal records | No automatic creation; links preserved |
| Regression | Existing resident, AI, and staff tests pass |

## Immediate Next Step

**TR3.1-R0** — read-only staff workflow audit (checklist under TR3). Then TR3.2 design for approval before any TR3 code.

## Open Items and Discrepancy Log

| # | Item | Owner | Status |
|---|---|---|---|
| 1 | Official Purok boundary dataset from Barangay Calarian | Barangay / Admin | ⏸ |
| 2 | Referral closure may leave parent report in *referred* without resident notification | TR3.1 / TR7.1 | Open |
| 3 | Do Blotter/Complaint/Case features already exist; how do they relate to reports? | TR3.1 / TR5.1 | Open |
| 4 | Who is authorized to make the final jurisdiction decision? | Barangay | Open |
| 5 | Exact KP intake rules, forms, numbering formats | Barangay | Open |
| 6 | Intake sub-states: new DB statuses or derived views? | TR3.1 / TR3.2 | Open |
| 7 | Reverse geocoding (street/address) wanted? | Product | Optional |
| 8 | Existing role names vs proposed roles (Part 2-F) | TR3.1 | Open |
| 9 | TR6–TR9 task lists are proposals; confirm or revise | Project owner | Open |

*(Add an entry whenever code and this document disagree.)*

## Documentation Update Checklist

- [ ] README / project overview (terminology: Report a Concern, Concern Intake)
- [ ] Master roadmap (TR1–TR9 statuses and task tables per Part 3)
- [ ] Resident workflow docs (Part 1)
- [ ] AI architecture doc (pre-submission endpoint vs post-submission listener; privacy rule)
- [ ] GIS / boundary docs (provisional vs official; PiP service)
- [ ] Admin/staff workflow docs (Part 2-A)
- [ ] Referral workflow doc (integration and sync issue)
- [ ] Formal records docs (Part 2-B), after TR5.1 audit
- [ ] Permissions/roles doc
- [ ] Data model/ERD (only after TR5.1 approval)
- [ ] Test plans and regression checklists
- [ ] Changelog
