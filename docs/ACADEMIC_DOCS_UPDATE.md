# ACADEMIC DOCUMENTS UPDATE

Exact changes needed in the two submitted documents so they match the revised system: **Requirements Blueprint** (PDF, 10 pages) and **Final Project Proposal Documentation** (Word). Written so a teammate or an AI assistant can apply them directly.

All additions are labelled **Source: New (adviser feedback)** and are subject to the secretary's answers (see `PLACEHOLDERS.md`). Existing IDs are never renumbered; new IDs continue each sequence.

---

## 1. Requirements Blueprint

### 1.1 Section A: User Requirements (append)

| ID | User | User Requirement |
| :--- | :--- | :--- |
| UR-11 | Captain / Administrator | The administrator should be able to set up the system on first launch: barangay profile, active fiscal year, staff list, and access for each employee. |
| UR-12 | Captain / Administrator | The administrator should be able to keep a record of barangay employees and official signatories, with their positions and terms of office. |
| UR-13 | Barangay Staff | Staff should be able to record a case with its complainants and respondents, and tag each person as a resident, non-resident, or unverified. |
| UR-14 | Barangay Staff | Staff should be able to issue summons, see how many were issued for a case, and be told when the case is eligible to be escalated. |
| UR-15 | External Agency | An agency (for example the police) should be able to register, be approved by the administrator, and view and update only the referrals sent to it. |
| UR-16 | Captain / Administrator | The administrator should be able to generate reports for any fiscal year, in the barangay's formats, with the correct signatories. |
| UR-17 | Captain / Administrator | The administrator should be able to close a fiscal year so that its finished records are locked. |

Add a line to the source-column key: **New** = requirement added after adviser feedback.

### 1.2 Section B: System Requirements (append)

| ID | From | System Requirement |
| :--- | :--- | :--- |
| SR-16 | UR-11 | The system shall show a first-launch setup wizard that creates the first administrator, the barangay profile, the active fiscal year, the staff list, and each employee's access. |
| SR-17 | UR-11 | The system shall require one active fiscal year and stamp every new report, referral, and case with it. |
| SR-18 | UR-12 | The system shall keep a registry of personnel with position, term of office, signatory status, and active or inactive status, without deleting former employees. |
| SR-19 | UR-10, UR-11 | The system shall let the administrator give each employee individual permissions, in addition to the Staff and Administrator roles. |
| SR-20 | UR-13 | The system shall record a case with a case type, narrative, and one or more complainants, respondents, and witnesses, each tagged resident, non-resident, or unverified. |
| SR-21 | UR-13 | The system shall store each person once, warn about a possible duplicate before creating a new person, and show every case the person is in. |
| SR-22 | UR-14 | The system shall record each summons with its number, date, hearing time, service result, and attendance, count the non-voided summons per case, and flag the case as eligible for escalation when the count reaches the configured limit. The system shall never escalate a case on its own. |
| SR-23 | UR-14 | The system shall record each escalation with its destination, the official who decided, the reason, and, when the limit was not reached, an override note. |
| SR-24 | UR-15 | The system shall let an external agency register with an identity document, and shall keep the account inactive until an administrator approves it. |
| SR-25 | UR-15 | The system shall show an approved agency only the referrals addressed to its own office. |
| SR-26 | UR-16 | The system shall produce reports that can be filtered by fiscal year and exported as PDF or Excel, with a signatory block taken from the personnel registry. |
| SR-27 | UR-17 | The system shall let the administrator close a fiscal year with a recorded note, after which finished records of that year cannot be edited. |

### 1.3 Section C: Functional Requirements (append)

| ID | Functional Requirement | Source |
| :--- | :--- | :--- |
| FR-23 | The system shall provide a multi-step setup wizard that runs on first launch and is closed once setup is complete. | New (adviser feedback) |
| FR-24 | The system shall let the administrator add, activate, close, and reopen fiscal years, with a recorded note for closing and reopening. | New |
| FR-25 | The system shall let the administrator configure the number format for referrals, cases, and summons, including whether numbers restart each fiscal year. | New |
| FR-26 | The system shall provide a registry of positions, personnel, and signatories, and shall use the signatory in force on the document date on every printed letter and report. | New |
| FR-27 | The system shall provide user management with per-employee permissions, account deactivation, temporary passwords, and forced password change on first login. | New |
| FR-28 | The system shall show every setting or data item that has not yet been confirmed by the barangay and let the administrator confirm it. | New |
| FR-29 | The system shall let staff create, edit, assign, and change the status of cases, with a recorded note for every status change. | New |
| FR-30 | The system shall provide a person directory with duplicate warnings, residency tagging with the basis for it, and the ability to merge duplicate persons. | New |
| FR-31 | The system shall record summons per case, show a summons counter, and print a summons. | New |
| FR-32 | The system shall provide a hearing schedule with today's hearings and let staff record hearing outcomes. | New |
| FR-33 | The system shall let staff record a settlement and escalate a case, producing an endorsement letter and, optionally, a referral to the receiving office. | New |
| FR-34 | The system shall provide agency registration, email verification, administrator approval, rejection, suspension, and reinstatement. | New |
| FR-35 | The system shall provide an agency portal where agency users acknowledge, update, decline, and close the referrals sent to them and send messages to the barangay. | New |
| FR-36 | The system shall provide a Reports Center with seven standard reports, a history of generated reports, and PDF, Excel, and print output. | New |
| FR-37 | The system shall provide a secretary dashboard (hearings, summons awaiting service, cases eligible to escalate, overdue cases, referrals awaiting follow-up) and a global search by tracking code, referral number, case number, and person name. | New |
| FR-38 | The system shall make a daily backup and let the administrator download it. | New |

**Edits to existing rows**
- **FR-06:** replace "email residents ... and email officials" scope note with: "Notifications are sent by email only; SMS is deferred."
- **FR-17:** replace "out-of-scope" with "no action (with a reason code)". Statuses `on_hold`, `out_of_scope`, and `closed` no longer exist; the system has 12 statuses (see `WORKFLOW.md`).
- **FR-20:** add "positions, personnel, fiscal years, numbering, escalation destinations, case types, and case fields" to the list of things the administrator manages.
- **FR-22 and UR-04:** keep marked **Deferred (not built)**.

### 1.4 Section D: Non-Functional Requirements (append)

| ID | Category | Non-Functional Requirement |
| :--- | :--- | :--- |
| NFR-11 | Privacy | The system shall restrict case and person records to authorized staff, allow a case to be marked confidential, record who opened a confidential case, and never send case or person data to the AI service or to agencies except what a referral requires. |
| NFR-12 | Reliability | The system shall make a backup every day and keep the latest 14. |
| NFR-13 | Security | The system shall throttle agency registration, require email verification, store identity documents in private storage, and give each agency access only to its own referrals. |
| NFR-14 | Auditability | The system shall record every change to settings, permissions, fiscal years, agency approvals, and case status, with who, when, old value, new value, and note. |
| NFR-15 | Performance | The system shall generate a standard report of up to 5,000 rows within 5 seconds. |
| NFR-16 | Maintainability | Values that the barangay has not yet confirmed shall be stored as editable settings or data, never hardcoded. |

### 1.5 Section E: Use Case Diagram and table

**Changes to the diagram header:** "5 actors, 43 use cases, 1 system boundary". Add the **External Agency** actor on the **right side**, below the Administrator, as an external system or role (stick figure). Existing 27 use cases stay.

**16 new use cases** (draw inside the boundary):

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

Relationships: **Escalate Case** `<<include>>` **Issue Summons** history check; **Record Case** `<<extend>>` from **Convert Report to Case** (the existing problem report review); **Update Referral Status** `<<include>>` **View Assigned Referrals**; **Approve Agency Account** `<<extend>>` **Register Agency Account**. Keep the hollow arrow from Administrator to Staff. Report by Phone stays dashed (planned).

**Actor table (page 10): replace with**

| Actor | Use Cases | Related FR |
| :--- | :--- | :--- |
| Resident | unchanged | FR-01 to FR-05, FR-18, FR-22 |
| Barangay Staff | existing plus Record Case, Manage Parties, Issue Summons, Schedule and Record Hearing, Record Settlement, Escalate Case, Generate Official Report | FR-07 to FR-09, FR-13 to FR-19, FR-29 to FR-33, FR-36, FR-37 |
| Captain / Administrator | existing plus Run First-Time Setup, Manage Fiscal Year, Manage Personnel and Signatories, Manage Permissions, Approve Agency Account, Back Up Data | FR-19 to FR-21, FR-23 to FR-28, FR-34, FR-36, FR-38 |
| External Agency | Register Agency Account, View Assigned Referrals, Update Referral Status | FR-34, FR-35 |
| AI Service (external) | unchanged | FR-10 to FR-12, FR-14, FR-19 |

### 1.6 Deferred and scope statement (add after Section E or in the introduction)

| Item | Status |
| :--- | :--- |
| Voice call reporting (UR-04, FR-22) | Deferred, not built |
| SMS notifications | Deferred, not built (email only) |
| Dialect audio and speech-to-text | Deferred, not built |
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
| **1.4 Proposed Solution** and **1.4.1 Product Summary** | Rewrite from "two layers" to **three layers**; add Layer 3 below. Keep the principle "The AI suggests, the rules route, the officials decide" |
| **1.4.2 List of Features** (Table 1.1) | Add a **Layer 3, Barangay Administration and Case Management** block with the rows below. In "Notifications" delete "and SMS" (email only). In "Voice Call Reporting" add the word "(deferred)". In "Staff Review Queue and Case Management" add "(problem reports)" to avoid confusion with cases. Update the intro sentence and the table number/caption if the count changes |
| **List of Tables / Table of Contents** | Refresh page numbers and the feature count after the edit |

**New Table 1.1 rows (Layer 3):**

| Feature | Description | Intended User |
| :--- | :--- | :--- |
| First-Time Setup Wizard | Guides the administrator through the barangay profile, active fiscal year, staff list, and access on first launch | Administrator |
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
