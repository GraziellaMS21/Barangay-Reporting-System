# ACADEMIC DOCUMENTS UPDATE

> **Verification Status**: ✅ **100% Aligned & Verified with System Implementation (Phases 1–4)**  
> System architecture, two-module conversion bridge, Katarungang Pambarangay workflows, 5-step + 5b setup wizard, permission presets, and RA 10173 data privacy rules have been fully implemented and verified in the codebase.

Exact changes needed in the two submitted documents so they match the revised system: **Requirements Blueprint** (PDF, 10 pages) and **Final Project Proposal Documentation** (Word). Written so a teammate or an AI assistant can apply them directly.

All additions are labelled **Source: New (adviser feedback)** and are subject to the secretary's answers (see `docs/PROJECT.md` § 8). Existing IDs are never renumbered; new IDs continue each sequence.

---

## 1. Requirements Blueprint

### 1.1 Section A: User Requirements (append)

| ID | User | User Requirement |
| :--- | :--- | :--- |
| UR-11 | Captain / Administrator | The administrator should be able to set up the system on first launch: barangay profile, active fiscal year, staff list, and access for each employee. |
| UR-12 | Captain / Administrator | The administrator should be able to keep a record of barangay employees and official signatories, with their positions and terms of office. |
| UR-13 | Barangay Staff | Staff should be able to record a case with its complainants and respondents, and tag each person as a resident, non-resident, or unverified. |
| UR-14 | Barangay Staff | Staff should be able to issue sequential summons, track 15-working-day conciliation deadlines, and let complainants initiate case escalation when conciliation fails. |
| UR-15 | External Agency | An agency (for example the police) should be able to register, be approved by the administrator, and view and update only the referrals sent to it. |
| UR-16 | Captain / Administrator | The administrator should be able to generate reports for any fiscal year, in the barangay's formats, with the correct signatories. |
| UR-17 | Captain / Administrator | The administrator should be able to close a fiscal year so that its finished records are locked. |

Add a line to the source-column key: **New** = requirement added after adviser feedback.

### 1.2 Section B: System Requirements (append)

| ID | From | System Requirement |
| :--- | :--- | :--- |
| SR-16 | UR-11 | The system shall show a 5-step first-launch setup wizard that validates environment prerequisites, creates the root administrator, saves the barangay profile, configures and activates the fiscal year, and reviews seeded baseline personnel, locking upon completion. (Staff onboarding and permission assignment are managed post-setup, with interactive setup onboarding planned in Task 13). |
| SR-17 | UR-11 | The system shall require one active fiscal year and stamp every new report, referral, and case with it. |
| SR-18 | UR-12 | The system shall keep a registry of personnel with position, term of office, signatory status, and active or inactive status, without deleting former employees. |
| SR-19 | UR-10, UR-11 | The system shall let the administrator give each employee individual permissions, in addition to the Staff and Administrator roles. |
| SR-20 | UR-13 | The system shall record a case with a record type (blotter-only or formal complaint), case type, narrative, and one or more complainants, respondents, and witnesses, each tagged resident, non-resident, transient, or unverified. |
| SR-21 | UR-13 | The system shall store each person once, warn about possible duplicate persons and cases before saving, and show every case the person is in. |
| SR-22 | UR-14 | The system shall record each summons with its sequential number, date, hearing time, service result, and attendance, counting summons for information only without an artificial cutoff limit, and calculate conciliation deadlines based on 15 working days excluding weekends and official holidays. The system shall never transition case status automatically. |
| SR-23 | UR-14 | The system shall record case escalation upon complainant request with destination agency, request date, reason, and printable Certification to File Action (CFA) signed by the Pangkat Chairman, displaying an advisory warning if 15 working days have not elapsed without blocking officials. |
| SR-24 | UR-15 | The system shall let an external agency register with an identity document or official endorsement letter, and shall keep the account inactive until an administrator approves it. |
| SR-25 | UR-15 | The system shall show an approved agency only the referrals addressed to its own office and allow downloading case attachments. |
| SR-26 | UR-16 | The system shall produce reports that can be filtered by fiscal year and exported as PDF or Excel, with a signatory block taken from the personnel registry. |
| SR-27 | UR-17 | The system shall let the administrator close a fiscal year with a recorded note, after which finished records of that year cannot be edited. |
| SR-28 | UR-11 | The system shall provide a bulk import mechanism for historical Excel and CSV files spanning past record years, validating rows against duplicates and supporting batch rollback without altering active sequence numbers. |

### 1.3 Section C: Functional Requirements (append)

| ID | Functional Requirement | Source |
| :--- | :--- | :--- |
| FR-23 | The system shall provide a 5-step setup wizard (environment checks, administrator account, barangay profile, fiscal year, and baseline personnel review) that runs on first launch and locks permanently once complete. | New (adviser feedback) |
| FR-24 | The system shall let the administrator add, activate, close, and reopen fiscal years, with a recorded note for closing and reopening. | New |
| FR-25 | The system shall let the administrator configure the number format for referrals, cases, and summons, including whether numbers restart each fiscal year. | New |
| FR-26 | The system shall provide a registry of positions, personnel, and signatories, and shall use the signatory in force on the document date on every printed letter and report. | New |
| FR-27 | The system shall provide user management with 21 granular permission slugs, 4 predefined role presets (`staff_full: 18`, `case_encoder: 5`, `reports_analyst: 3`, `case_reviewer: 5`), account deactivation, temporary passwords, and forced password change on first login. | New |
| FR-28 | The system shall show every setting or data item that has not yet been confirmed by the barangay and let the administrator confirm it. | New |
| FR-29 | The system shall let staff create, edit, assign, and change the status of cases (blotter or formal complaint), with a recorded note for every status change, and allow upgrading blotters to formal complaints. | New |
| FR-30 | The system shall provide a person directory with duplicate warnings, residency tagging with the basis for it, and the ability to merge duplicate persons. | New |
| FR-31 | The system shall record sequential summons per case, display a working-day deadline countdown panel (with due-soon and overdue indicators), and print official summons notices. | New |
| FR-32 | The system shall provide a hearing schedule with today's hearings and let staff record hearing outcomes. | New |
| FR-33 | The system shall let staff record an amicable settlement with terms, amount, and signed agreement upload, and let staff record complainant-initiated escalation, generating a Certification to File Action (CFA) signed by the Pangkat Chairman and dispatching an external agency referral with attachments. | New |
| FR-34 | The system shall provide agency registration, email verification, administrator approval, rejection, suspension, and reinstatement. | New |
| FR-35 | The system shall provide an agency portal where agency users acknowledge, update, decline, and close the referrals sent to them and send messages to the barangay. | New |
| FR-36 | The system shall provide a Reports Center with standard DILG/KP reports (yearly accomplishment, monthly case summary, purok-problem matrix), a history of generated reports, and PDF, Excel, and print output. | New |
| FR-37 | The system shall provide a secretary dashboard (today's hearings, upcoming hearings, summons awaiting service, cases near deadline, overdue cases, referrals awaiting follow-up) and a global search across all fiscal years by tracking code, referral number, case number, and person name. | New |
| FR-38 | The system shall make a daily backup and let the administrator download it. | New |
| FR-39 | The system shall provide an Excel and CSV bulk import wizard (upload, column mapping, validation preview, chunked commit, batch rollback) for historical records across past fiscal years without AI involvement. | New |
| FR-40 | The system shall provide an online complaint intake form where residents submit dispute complaint requests (capturing 5Ws, rights violated, relief requested, complainant and respondent free-text info, and RA 10173 consent), creating a pending intake without automatically docketing or creating parties. | New (intake update) |
| FR-41 | The system shall screen out confidential matters (VAWC, child abuse) from the online complaint form, directing residents to 911, PNP Aleng Pulis, Bantay Bata, or the Barangay VAW Desk without storing database records. | New (confidentiality) |
| FR-42 | The system shall provide a staff pending intake queue on the Cases Docket with "Docket this" (prefilling intake form and creating case at `filed`) and "Redirect/Reject" (mandatory reason) actions. | New (docketing) |
| FR-43 | The system shall allow residents to submit community problem reports without creating an account (guest mode) and anonymously without storing personal details or IP addresses. | New (guest reporting) |

**Edits to existing rows**
- **FR-06:** replace "email residents ... and email officials" scope note with: "Notifications are sent by email only; SMS is deferred."
- **FR-17:** replace "out-of-scope" with "no action (with a reason code)". Statuses `on_hold`, `out_of_scope`, and `closed` no longer exist; the system has 12 statuses (see `WORKFLOW.md`).
- **FR-20:** add "positions, personnel, fiscal years, numbering, escalation destinations, case types, and case fields" to the list of things the administrator manages.
- **FR-22 and UR-04:** change from "Deferred (not built)" to **Prototype: record and transcribe (no telephony)**. Spoken audio in English and Filipino (with partial Bisaya and limited Chavacano support) is transcribed via Gemini as an untrusted advisory suggestion; uncertain or failed audio does not auto-fill description and blocks routing until staff verification. Real telephony, softphone dialers, live call recording, SMS, and dialect-specific acoustic speech models remain **Deferred (not built)**.

### 1.4 Section D: Non-Functional Requirements (append)

| ID | Category | Non-Functional Requirement |
| :--- | :--- | :--- |
| NFR-11 | Privacy | The system shall restrict case and person records to authorized staff, allow a case to be marked confidential, record who opened a confidential case, and never send case or person data to the AI service or to agencies except what a referral requires. |
| NFR-12 | Reliability | The system shall make a backup every day and keep the latest 14. |
| NFR-13 | Security | The system shall throttle agency registration, require email verification, store identity documents in private storage, and give each agency access only to its own referrals. |
| NFR-14 | Auditability | The system shall record every change to settings, permissions, fiscal years, agency approvals, and case status, with who, when, old value, new value, and note. |
| NFR-15 | Performance | The system shall generate a standard report of up to 5,000 rows within 5 seconds. |
| NFR-16 | Maintainability | Values that the barangay has not yet confirmed shall be stored as editable settings or data, never hardcoded. |
| NFR-17 | Privacy | The system shall keep respondent details unlinked and unlisted from public tracking, and shall strictly withhold case status and dispute timelines on public intake confirmation screens. |
| NFR-18 | Security | The system shall enforce IP-based rate limiting (`throttle:3,10`) and honeypot field validation on public dispute complaint and guest reporting forms to mitigate spam submissions. |

### 1.5 Section E: Use Case Diagram and table

**Changes to the diagram header:** "5 actors, 50 use cases, 1 system boundary". Add the **External Agency** actor on the **right side**, below the Administrator, as an external system or role (stick figure). The system architecture comprises **three layers** (Public Problem Reporting, Case Management, Administration) and **two distinct operational modules joined by conversion** (Community Reports and Dispute Cases).

**23 new use cases** (draw inside the boundary):

| # | New use case | Actor |
| :--- | :--- | :--- |
| 1 | Run First-Time Setup | Administrator |
| 2 | Manage Fiscal Year | Administrator |
| 3 | Manage Personnel and Signatories | Administrator |
| 4 | Manage Permissions | Administrator |
| 5 | Record Case | Staff |
| 6 | Manage Parties | Staff |
| 7 | Issue Summons | Staff |
| 8 | Schedule and Record Hearing | Staff |
| 9 | Record Settlement | Staff |
| 10 | Escalate Case | Staff |
| 11 | Generate Official Report | Staff (permitted) and Administrator |
| 12 | Approve Agency Account | Administrator |
| 13 | Register Agency Account | External Agency |
| 14 | View Assigned Referrals | External Agency |
| 15 | Update Referral Status | External Agency |
| 16 | Back Up Data | Administrator |
| 17 | Submit Complaint Request | Resident |
| 18 | Review Pending Intake | Staff |
| 19 | Docket Complaint | Staff |
| 20 | Record Hearing Attendance | Staff |
| 21 | Handle Non-Appearance | Staff |
| 22 | Submit Community Problem as Guest or Anonymously | Resident |
| 23 | Convert Report to Dispute Case | Staff |

Relationships: **Escalate Case** `<<include>>` **Issue Summons** history check; **Docket Complaint** `<<extend>>` **Review Pending Intake**; **Convert Report to Dispute Case** bridges community reports to dispute cases; **Update Referral Status** `<<include>>` **View Assigned Referrals**; **Approve Agency Account** `<<extend>>` **Register Agency Account**. Keep the hollow arrow from Administrator to Staff. Report by Phone stays dashed (planned).

**Actor table (page 10): replace with**

| Actor | Use Cases | Related FR |
| :--- | :--- | :--- |
| Resident | Submit Community Problem Report, Track Report, Confirm/Reopen Resolution, Submit Complaint Request, Submit Community Problem as Guest or Anonymously | FR-01 to FR-05, FR-18, FR-22, FR-40, FR-41, FR-43 |
| Barangay Staff | Review Queue (problem reports), Route/Refer Report, Review Pending Intake, Docket Complaint, Record Case, Manage Parties, Issue Summons, Schedule and Record Hearing, Record Hearing Attendance, Handle Non-Appearance, Record Settlement, Escalate Case, Generate Official Report, Convert Report to Dispute Case | FR-07 to FR-09, FR-13 to FR-19, FR-29 to FR-33, FR-36, FR-37, FR-42 |
| Captain / Administrator | existing plus Run First-Time Setup, Manage Fiscal Year, Manage Personnel and Signatories, Manage Permissions, Approve Agency Account, Back Up Data | FR-19 to FR-21, FR-23 to FR-28, FR-34, FR-36, FR-38 |
| External Agency | Register Agency Account, View Assigned Referrals, Update Referral Status | FR-34, FR-35 |
| AI Service (external) | unchanged | FR-10 to FR-12, FR-14, FR-19 |

### 1.6 Deferred and scope statement (add after Section E or in the introduction)

| Item | Status |
| :--- | :--- |
| Voice call reporting (UR-04, FR-22) | Prototype: record-first intake (resident web + staff hotline), AI form field extraction, optional category, human verification gate, zero auto-routing, no telephony |
| Live telephony, softphone integration, and live call recording | Deferred, not built |
| SMS notifications | Deferred, not built (email only) |
| Dialect-specific acoustic speech models | Deferred, not built (uses generalized cloud transcription with staff verification for uncertain audio) |
| Resident accounts and identity verification | Deferred, not built (residents use the tracking code; residency is a staff-recorded label) |
| Lupon and Pangkat modelling, formal KP stage sequence | Deferred pending the secretary's answers |
| Hearing email reminders | Built but switched off |
| Backup restore from the screen | Deferred (command line only) |
| Case management, external agency accounts, setup, fiscal year, personnel registry, per-employee permissions, Reports Center | **In scope** (added after adviser feedback) |

---

## 2. Final Project Proposal Documentation (Word)

| Section | Change |
| :--- | :--- |
| **1.1 Product Overview** | Add one paragraph: the system now has an administrative foundation (first-time setup, fiscal year, personnel and signatories, per-employee permissions), a case management module (parties, summons, hearings, escalation), and an external agency portal for referrals |
| **1.3 Background of the Client** | Add the adviser-driven needs: records tied to a fiscal year, official signatories on documents, case records with complainants and respondents, summons tracking, and referrals shared with outside agencies |
| **1.4 Proposed Solution** and **1.4.1 Product Summary** | Rewrite from "two layers" to **three layers** (Public Problem Reporting, Case Management, Administration) and describe the system as **two distinct operational modules joined by conversion** (Community Reports and Dispute Cases); keep the existing "(problem reports)" wording for Staff Review Queue. Keep the principle "The AI suggests, the rules route, the officials decide" |
| **1.4.2 List of Features** (Table 1.1) | Add a **Layer 3, Barangay Administration and Case Management** block with the rows below. In "Notifications" delete "and SMS" (email only). In "Voice Call Reporting" update to "Voice reporting prototype (record-first intake for resident form and staff hotline, AI pre-fills form with human verification, category optional, no telephony); real telephony, live call recording, and softphone remain deferred". In "Staff Review Queue and Case Management" add "(problem reports)" to avoid confusion with cases. Update the intro sentence and the table number/caption if the count changes |
| **List of Tables / Table of Contents** | Refresh page numbers and the feature count after the edit |

**New Table 1.1 rows (Layer 3):**

| Feature | Description | Intended User |
| :--- | :--- | :--- |
| First-Time Setup Wizard | Guides the administrator through environment verification, administrator account creation, barangay profile, active fiscal year, and seeded baseline personnel review on first launch | Administrator |
| Fiscal Year Management | Groups records and numbers by fiscal year; closing a year locks finished records | Administrator |
| Personnel and Signatory Registry | Keeps employees, positions, terms of office, and the signatories used on letters and reports | Administrator |
| Per-Employee Access Control | Gives each employee individual permissions on top of the Staff and Administrator roles | Administrator |
| Case Management | Records cases with complainants, respondents, and witnesses, tagged resident or non-resident | Staff |
| Summons and Hearing Tracking | Counts summons per case, flags eligibility for escalation, and keeps a hearing schedule | Staff |
| Case Escalation | Records who decided to escalate a case, to whom, and why, and prepares the endorsement | Staff |
| External Agency Portal | Lets approved agencies view and update only the referrals sent to them | External Agency, Staff, Administrator |
| Reports Center | Produces standard reports by fiscal year with signatory blocks in PDF and Excel | Administrator, Staff |
| Secretary Dashboard and Search | Shows hearings, pending summons, overdue cases, and follow-ups, and searches across records | Staff |
| Backup | Makes daily backups that the administrator can download | Administrator |
| Resident Complaint Intake | Allows residents to submit dispute complaint requests online as a pending intake, with confidential screening gate (VAWC/child protection) and privacy-preserving receipt | Resident |
| Pending Intake Review Queue | Allows staff to review complaint requests and execute "Docket this" into the case docket or "Redirect/Reject" with mandatory justification | Staff |
| Guest & Anonymous Community Reporting | Frictionless civic reporting without creating an account and optional anonymous mode with one-time tracking code | Resident |

Also check the "Intended User" column: add **External Agency** wherever it applies. Do not change Chapter 2 (company) unless the team roster or workstream assignments change.

---

## 3. Owners and deadlines

| Task | Owner | Draft due | Final due |
| :--- | :--- | :--- | :--- |
| Requirements Blueprint: sections 1.1 to 1.4 (tables A to D) and 1.6 | WS5 member | End of week 1 | After the secretary's answers, before the demo |
| Use case diagram redraw and actor table (1.5) | WS2 member | End of week 2 | Same |
| Proposal document updates (section 2) | Lead | End of week 2 | Same |
| Cross-check IDs between the Blueprint, `API.md`, and `PROJECT.md` | Lead | Week 3 | Before the demo |

Rules: keep the existing fonts, tables, and page headers; regenerate the PDF after editing; mark any requirement that depends on an unanswered question with "(pending barangay confirmation)" until the secretary answers; update the documents again after the placeholder swap (`TEAM_WORKSTREAMS.md` section 8).

---

## 4. Voice Intake Accuracy-Test Checklist (Chapter 6 Academic Evidence)

Empirical evaluation protocol for multimodal speech intake and structured field extraction in Barangay Calarian, Zamboanga City. Serves as primary experimental evidence for Chapter 6 (Testing, Evaluation, and Results).

### 4.1 Evaluation Objectives
1. **Linguistic Robustness**: Evaluate transcription fidelity and concept accuracy across the quad-lingual spoken landscape of Zamboanga City: **Filipino (Tagalog)**, **Bisaya (Cebuano)**, **Chavacano (Zamboangueño)**, and **Philippine English**.
2. **Field Extraction Accuracy**: Measure extraction precision and recall across core report entities (`description`, `category`, `purok`, `landmark`, `caller_name`, `caller_phone`, and `is_emergency`).
3. **Privacy & PII Isolation**: Confirm zero spoken contact details leak into the report description or resident payload, and 100% of contact fields drop when anonymous mode is engaged.
4. **Uncertainty Rule Adherence**: Confirm 100% compliance with the core mandate: unclear audio, low clarity, acoustic noise, or invalid format yields `needs_verification` with empty fields and forward routing strictly blocked.

---

### 4.2 Benchmark Test Dataset Matrix (12 Test Scenarios)

| ID | Language / Dialect | Acoustic Environment | Incident Scenario | Stated Fields | Expected Clarity | Expected Outcome & Verification |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| **TC-V01** | Filipino (Tagalog) | Clean audio (quiet room) | Clogged drainage overflowing onto walkway in Purok 2 | Name, Phone, Purok, Landmark, Category | `clear` | Pre-fills description, category `drainage`, purok `Purok 2`, landmark, caller details. Badges show "From recording". |
| **TC-V02** | Bisaya (Cebuano) | Clean audio | Guba nga dalan (pothole/road damage) near public market | Purok, Landmark, Problem description | `clear` | Pre-fills description, category `road_damage`, purok, landmark. Caller name/phone marked "Not provided". |
| **TC-V03** | Chavacano | Moderate background (outdoor breeze) | Basura hinde pa ta recoji na Purok 4 | Purok, Problem description | `clear` | Pre-fills description, category `garbage`, purok `Purok 4`. Landmark and caller details marked "Not provided". |
| **TC-V04** | Taglish (Code-switched) | Phone speakerphone artifact | Broken streetlight causing dark alleyway | Name, Phone, Purok, Street | `clear` | Pre-fills description, category `streetlight`, purok, landmark, and contact details. |
| **TC-V05** | English (Philippine) | Clean audio | Severe flooding trapping residents (Emergency) | Name, Phone, Purok, Danger keyword | `clear` | Pre-fills description, category `flooding`, ticks emergency with "From recording, please confirm", surfaces 911 hotline banner. |
| **TC-V06** | Bislish (Bisaya + English) | Traffic noise | Fallen tree blocking driveway, no injuries | Purok, Problem description | `partial` | Pre-fills stated fields; displays amber partial clarity banner: *"Parts of this recording were unclear. Please listen again and check every field."* |
| **TC-V07** | Filipino (Tagalog) | Low volume / muffled | Leaking water pipe, speaker forgets to state purok | Problem description, Landmark only | `clear` | Pre-fills description and landmark. Purok helper indicates *"Not mentioned in the recording, please choose it."* Required gate enforces purok selection before submission. |
| **TC-V08** | Chavacano | Ambient street noise (tricycle engine) | Disputa de terreno / property boundary (walk-in advice) | Description, caller name | `partial` | Pre-fills description and name. Category left unselected (optional). Category card displays *"Not chosen by resident"*. |
| **TC-V09** | Heavy static / Inaudible | Heavy phone line static / wind distortion | Unintelligible caller audio | None | `unclear` | Sets `transcript_status = needs_verification`, all form fields empty ("Not provided"), routing blocked until staff listens and verifies. |
| **TC-V10** | Non-standard dialect / Mumble | Low signal-to-noise ratio | Mumbled speech in unknown dialect | None | `unclear` | Zero hallucination. Sets `needs_verification`. Resident form shows friendly fallback encouraging typing or re-recording. |
| **TC-V11** | Filipino (Anonymous) | Clean audio | Citizen reports illegal dumping, explicitly says *"Huwag nyo po ilagay pangalan ko"* | Caller states name in audio, but ticks Anonymous | `clear` | Strict Anonymity Rule: Server drops caller name and phone from resident response; form fields stay blank; audio stores zero contact provenance. |
| **TC-V12** | Bisaya (Edited by Citizen) | Clean audio | Damaged culvert; user records audio then modifies text box to add extra context | All fields | `clear` | Review page displays `"From recording"` for unmodified fields and `"Edited by resident"` on the Incident Description card. |

---

### 4.3 Evaluation Metrics for Thesis Chapter 6

1. **Word Error Rate (WER)**:
   $$\text{WER} = \frac{S + D + I}{N}$$
   Target: $\le 15\%$ on Clean Filipino/English; $\le 25\%$ on Bisaya/Chavacano with regional accents.
2. **Concept & Intent Accuracy**:
   $$\text{Concept Accuracy} = \frac{\text{Correctly Extracted Core Problem Concepts}}{\text{Total Stated Problem Concepts}} \times 100\%$$
   Target: $\ge 90\%$ across all clear recordings.
3. **Entity Extraction Precision & Recall**:
   - Calculated independently for `category`, `purok`, `landmark`, `caller_name`, and `caller_phone`.
   - Recall target: $\ge 85\%$ for explicitly spoken entities.
   - Precision target: $\ge 95\%$ (strict prohibition against inventing unstated entities).
4. **Safety & Uncertainty Gate Compliance**:
   - Hallucination Rate on Unclear Audio: Target **0.0%**. Every ambiguous or unclear audio MUST yield `needs_verification`.
   - Forward Transition Prevention: 100% of unverified audio reports must be blocked from routing until human staff verification.
