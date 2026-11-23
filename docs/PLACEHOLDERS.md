# PLACEHOLDER REGISTER (v2, 2026-11-22)

Every value below lives in the database (a `settings` row or a seed table row) and shows a "Pending confirmation" badge until an administrator presses **Confirm** on `/admin/settings/pending`. Nothing here may be hardcoded.

**v2 changes:** question numbers now match the real form, `Barangay_Secretary_Questionnaire (Responses)`, **43 questions** (v1 used an old 42-question draft). Values are updated with the Barangay Secretary's answers of 2026-11-22. Design consequences are explained in `REVISION_2.md`.

**Status column**

| Status | Meaning | What to do |
| :--- | :--- | :--- |
| **Answered** | The secretary gave the value | Enter it, then press Confirm |
| **Answered, check** | The answer needs an interpretation or a follow-up question | Enter it, ask the follow-up (`REVISION_2.md` section 9.2), confirm after |
| **Not asked** | Team assumption; the secretary was never asked | Keep as draft; ask only if it matters |
| **Open** | She answered "Not sure" or the answer is missing | Follow-up needed |
| **Form** | Waits for a blank form collected on 2026-11-23 | Template swap by the listed owner |

**Swap owner** is the workstream that makes the change (WS1 to WS6, defined in `TEAM_WORKSTREAMS.md`).

---

## A. Settings keys (table `settings`)

Seeded by `RequiredDataSeeder` with `updateOrCreate`. `type` is one of `string|int|bool|json|date`.

| Key | Type | Value | Group | Q# | Status | Owner | Notes |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| `system.setup_completed` | bool | `false` | system | none | n/a | WS1 | Never shown as pending |
| `system.setup_step` | string | `welcome` | system | none | n/a | WS1 | Wizard progress |
| `barangay.name` | string | `Barangay Calarian` | barangay | none | n/a | WS1 | Confirmed when the wizard finishes |
| `barangay.city` | string | `Zamboanga City` | barangay | none | n/a | WS1 | |
| `barangay.address` | string | empty | barangay | none | n/a | WS1 | Required in wizard step 3 |
| `barangay.contact_number` | string | empty | barangay | none | n/a | WS1 | |
| `barangay.email` | string | empty | barangay | none | n/a | WS1 | |
| `barangay.seal_path` | string | empty | barangay | none | Form | WS1 | Ask for the seal and letterhead file (not on her Q40 list) |
| `fiscal_year.start_month` | int | `1` | fiscal_year | Q8 | Answered | WS1 | Calendar year, January to December |
| `fiscal_year.lock_on_close` | bool | `true` | fiscal_year | Q8 | Answered, check | WS1 | Her answer: old records are boxed or archived. Read as "closed years are not edited" |
| `records.lookback_years` | int | `4` | fiscal_year | Q8 | Answered, check | WS1 / WS6 | Years staff still look up. Confirm which years (2023 to 2026 or 2022 to 2025 plus current) |
| `numbering.case.format` | string | `{MM}-{SEQ:3}-{YEAR}` | numbering | Q9 | Answered, check | WS1 | Example `11-001-2026`. Needs the `{MM}` token. Confirm per-year sequence |
| `numbering.blotter.format` | string | `{MM}-{SEQ:3}-{YEAR}` | numbering | Q9 | Open | WS1 | Same format until told otherwise; own sequence type `blotter` |
| `numbering.referral.format` | string | `REF-{YEAR}-{SEQ:4}` | numbering | Q9 | Open | WS1 | Kept because existing tests rely on it; confirm whether Q9 covers referral letters |
| `numbering.summons.format` | string | `SUM-{YEAR}-{SEQ:4}` | numbering | none | Not asked | WS1 | |
| `numbering.reset` | string | `per_fiscal_year` | numbering | Q9 | Answered | WS1 | "Yes, they restart every year". The option `never` is removed (the built schema needs a fiscal year per sequence) |
| `referral.follow_up_days` | int | `3` | referrals | Q12 | Answered | WS5 | Was 7. Read by `ReferralService`; update tests that assert 7 days |
| `cases.enabled` | bool | `true` | cases | adviser | Open | WS1 | Master switch. Off hides the whole case module |
| `cases.mediation_deadline_days` | int | `15` | cases | Q20, Q25 | Answered, check | WS4 | Working days. Confirm the start date (default `filed_on`) |
| `cases.conciliation_deadline_days` | int | `15` | cases | Q25 | Answered, check | WS4 | "Another 15 to 30 days" |
| `cases.conciliation_extension_days` | int | `15` | cases | Q25 | Answered, check | WS4 | One extension, giving a maximum of 30 |
| `cases.deadline_day_type` | string | `working` | cases | Q20, Q25 | Answered, check | WS4 | `working` or `calendar` |
| `cases.deadline_warning_days` | int | `3` | cases | Q27 | Not asked | WS4 | "Close to deadline" means this many working days or fewer |
| `calendar.holidays` | json | `[]` | cases | Q25 | Not asked | WS1 | Dates excluded from working-day counts. Needs a holiday list from the secretary |
| `cases.summons_limit` | int | empty | cases | Q20 | Answered | WS4 | **Not used.** She does not count summons. Kept empty so the counter shows a plain count |
| `cases.cfa_signer_position` | string | `pangkat_chairman` | cases | Q5 | Answered | WS4 | Replaces `cases.escalation_decider_position` |
| `cases.default_venue` | string | `Barangay Hall` | cases | none | Not asked | WS4 | Default on summons and hearings (venue is hidden on the form) |
| `cases.visibility` | string | `restricted` | cases | Q7 | Answered | WS3 | Needs `cases.view`; confidential cases follow `SPEC_ADDENDUM.md` section 7 |
| `cases.require_residency_basis` | bool | `false` | cases | Q16 | Not asked | WS3 | If true, "How verified" is mandatory for residents |
| `agencies.registration_open` | bool | `true` | agencies | Q28 | Not asked | WS5 | Public agency sign-up on or off |
| `agencies.approver_position` | string | `barangay_secretary` | agencies | Q30 | Answered | WS5 | Label only; permission `agencies.manage` enforces it. Both administrators hold it |
| `agencies.require_id_document` | bool | `true` | agencies | Q31 | Answered | WS5 | Her check today is "official letter or ID"; the upload accepts either |
| `privacy.notice_text` | string | Draft text, `SPEC_ADDENDUM.md` 5.1 | privacy | Q35 | Open | WS3 | She answered "Not sure". Keep the draft badge |
| `privacy.case_retention_years` | int | `5` | privacy | Q36 | Answered | WS1 | Informational until a purge job is approved |
| `reports.default_format` | string | `print` | reports | Q33 | Answered | WS2 | "Printed on paper". Values `print`, `pdf`, `excel` |
| `reports.paper_size` | string | `a4` | reports | none | Open | WS2 | A4, letter or long bond. Ask |
| `ui.form_style` | string | `single_page` | ui | none | Not asked | WS3 | The old "screen style" question is not on the real form. Only `single_page` is built |

---

## B. Seed-table placeholders (each row has `is_confirmed = false`)

### B1. `positions` (Q3, Q4, Q5, Q7, Q15, D1)

| code | name | signatory by default | Login | Q# | Status |
| :--- | :--- | :--- | :--- | :--- | :--- |
| `punong_barangay` | Punong Barangay (Barangay Captain) | yes | yes, admin | Q3, Q5, Q6 | Answered |
| `barangay_secretary` | Barangay Secretary | no | yes, admin | Q3, Q6 | Answered (she signs nothing in Q5) |
| `kagawad_peace_order` | Kagawad (Peace & Order) | no | yes | Q3, Q7 | Answered |
| `kagawad` | Kagawad (other) | no | per person | Q7 | Answered, check (which kagawads handle minors' cases) |
| `vawc_focal_person` | VAW Focal Person | no | yes | Q7 | Answered |
| `lupon_member` | Lupon Member | no | yes (D1) | Q15, D1 | Answered |
| `pangkat_chairman` | Pangkat Chairman | yes | yes | Q5 | Answered, check (fixed person or per case) |
| `lupon_secretary` | Lupon Secretary | no | optional | none | Not asked (assumed to prepare the CFA) |
| `lupon_chairperson` | Lupon Chairperson | no | optional | none | Not asked. May be redundant with the Captain; ask |
| `tanod` | Tanod | no | yes | Q3 | Answered |
| `desk_officer` | Desk Officer | no | yes | Q3 | Answered ("Tanod / desk officer" was one choice) |
| `barangay_treasurer` | Barangay Treasurer | no | no | none | Not asked. Roster only |
| `admin_staff` | Administrative Staff | no | no | none | Not asked. Remove if not needed |

Owner: **WS2** (rename, add, remove rows; update settings that reference a code).

### B2. `signatory_assignments` (Q5)

| document_type | prepared_by | noted_by | approved_by | Status |
| :--- | :--- | :--- | :--- | :--- |
| `referral_letter` | `barangay_secretary` (assumed) | none | `punong_barangay` | Approver answered (Q5) |
| `summons` | `barangay_secretary` (assumed) | none | `punong_barangay` | Approver answered (Q5) |
| `case_endorsement` | `barangay_secretary` (assumed) | none | `punong_barangay` (assumed) | Not asked |
| `certificate_to_file_action` | `lupon_secretary` (assumed) | none | `pangkat_chairman` | Approver answered (Q5) |
| `monthly_report` | `barangay_secretary` (assumed) | none | `punong_barangay` | Approver answered (Q5) |
| `annual_report` | `barangay_secretary` (assumed) | none | `punong_barangay` | Approver answered (Q5). The old treasurer "noted by" is removed |

Q5 asked who signs, so "approved by" holds her answer; "prepared by" is our assumption. Owner: **WS2**. The Pangkat can differ per case: the resolver takes an optional case (`REVISION_2.md` 5.4).

### B3. `case_types` (Q19)

| Name | Confidential by default | Uses the 15-day clock | Status |
| :--- | :--- | :--- | :--- |
| Physical injury | no | yes | Answered |
| Noise disturbance | no | yes | Answered |
| Boundary or property dispute | no | yes | Answered |
| Theft | no | yes | Answered |
| Domestic or family dispute | no | yes | Answered; ask if confidential |
| Threats or verbal abuse | no | yes | Answered |
| Unpaid debt | no | yes | Answered |
| Damage to property | no | yes | Answered |
| Vehicular accident | no | yes | Answered (new vs v1) |
| BCPC case | **yes** | ask | Answered, check (she wrote "BCPC and VAWC cases"; seeded as two types) |
| VAWC case | **yes** | ask | Answered, check |
| Other | no | yes | Not asked. Fallback, remove if unwanted |

Owner: **WS3**. Two columns are new (`case_types.is_confidential_default`, `uses_kp_timeline`, `REVISION_2.md` 8.2).

### B4. `case_field_configs` (Q16, Q17, Q18, Q21)

| entity | field_key | required | visible | applies_to | Status |
| :--- | :--- | :--- | :--- | :--- | :--- |
| party | `full_name` | yes | yes | all | Answered (Q17) |
| party | `alias` | no | yes | all | Answered (Q17) |
| party | `birth_date` | no | yes | all | Answered (Q17 "age or birth date") |
| party | `age` | no | yes | all | Answered (Q17). Stored in `parties.extra` |
| party | `contact_number` | no | yes | all | Answered (Q17) |
| party | `address_line` | no | yes | all | Answered (Q17 "complete address") |
| party | `purok_id` | no | yes | resident | Not asked |
| party | `residency_status` | yes | yes | all | Answered (Q16) |
| party | `residency_basis` | no | yes | all | Answered (Q16 options, see B9) |
| party | `home_barangay` | no | yes | non_resident | Answered (Q17) |
| party | `home_city` | no | yes | non_resident | Answered (Q17) |
| party | `reason_in_barangay` | no | yes | non_resident | Answered (Q17). Stored in `parties.extra` |
| party | `sex`, `civil_status`, `occupation`, `id_type`, `id_number`, `email` | no | **no** | all | Not asked; off by default, can be switched on |
| case | `record_type` | yes | yes | all | Answered (Q18: blotter-only vs complaint) |
| case | `case_type_id` | yes | yes | all | Answered (Q18, Q19) |
| case | `filed_on` | yes | yes | all | Not asked |
| case | `incident_date` | no | yes | all | Not asked |
| case | `incident_location` | no | yes | all | Not asked |
| case | `narrative` | yes | yes | all | Answered (Q18 "details are taken") |
| case | `is_confidential` | no | yes | all | Answered (Q7) |
| summons | `respondent_case_party_id` | yes | yes | all | Answered (Q18) |
| summons | `issued_on` | yes | yes | all | Answered (Q21) |
| summons | `hearing_at` | yes | yes | all | Answered (Q21) |
| summons | `served_by_personnel_id` | no | yes | all | Answered (Q21 "who delivered it") |
| summons | `service_result` | no | yes | all | Answered (Q21: received, refused, not found) |
| summons | `venue` | no | **no** | all | Not asked; default from `cases.default_venue` |

Owner: **WS3** (party and case fields), **WS4** (summons fields). Final order and labels come from the complaint form (Form, section C2).

### B5. `escalation_destinations` (Q18, Q22)

PNP Station, Prosecutor's Office, Court (MTCC). Status: Answered (Q18 "police, prosecutor, or court"). The built seed also holds **CSWDO**, which no answer supports as an escalation target: remove it or keep it as unconfirmed. Each destination should link an `office` (new column) for the referral handoff. Owner: **WS4**.

### B6. `report_templates` (Q32, Q33, Q34)

| Key | Content | Frequency, recipient | Status |
| :--- | :--- | :--- | :--- |
| `kp_accomplishment` | KP accomplishment report | **Yearly**, DILG | Answered; layout from the DILG sample (Form) |
| `case_summary` | Summary by case type (and status) | **Monthly**, DILG | Answered; layout from the sample (Form) |
| `purok_category_matrix` (new) | Problems per purok and category, reports and cases | internal | Answered (Q34 "which problems happen most in each purok") |
| `problem_reports_summary` | Problem reports by category, purok, status | internal | Not asked |
| `referral_register` | Referrals register | internal | Not asked |
| `summons_log` | Summons log | internal | Not asked |
| `personnel_list` | Officials list | internal | Not asked |
| `year_over_year` | Year-over-year comparison | internal | Not asked |

Q32 also says the "blotter or incident summary" is **not submitted** (yet shows a recipient "Other"): no template. Owner: **WS2**.

### B7. Existing data to confirm (no schema change)

**Problem types the barangay handles (Q10), in charge: Kagawad on the committee, Tanod, Secretary, Punong Barangay**

Drainage or canal; Garbage or illegal dumping; Streetlight; Stray animals or noise; Peace and order concerns.

**Problem types referred out (Q11), with a proposed destination (not stated by the secretary; confirm)**

| Problem type | Proposed office |
| :--- | :--- |
| Water supply or pipe leaks | Water utility |
| Electric lines or power outage | Electric utility |
| Fire hazard | BFP |
| Illegal construction | City Engineer's Office |
| Serious crime or threats | PNP |
| Medical or health emergency | CDRRMO |

**Outside offices (Q12):** City Engineer's Office, CDRRMO, Water utility, Electric utility, Environment office (CENRO), PNP, BFP, CSWDO. **Agencies that will get portal accounts first (Q28):** PNP, CSWDO, water or electric utility.

| Data | Q# | Owner | What to confirm |
| :--- | :--- | :--- | :--- |
| `categories` | Q10, Q11 | WS1 (seed edit) | Compare the six seeded categories with the eleven problem types above, then set risk weights **[V]** |
| `routing_rules`, `offices` | Q10 to Q12 | WS1 | Within authority for Q10 types, beyond authority for Q11 types, with the proposed offices |
| `emergency_contacts` | Q40 | WS1 | Hotline sheet from the secretary |
| `puroks` | Q40 | WS1 | Official purok list |
| Permission presets | Q3 to Q7 | WS1 (config is lead-owned) | See `REVISION_2.md` 5.6; adds `case_reviewer` |

### B8. Documents and wording that stay drafts until the forms arrive (Q40)

| Item | Owner | Replaced by | On her Q40 list |
| :--- | :--- | :--- | :--- |
| Printable summons | WS4 | Summons form | yes |
| Certification to File Action | WS4 | CFA form | yes |
| Settlement printable | WS4 | Settlement or agreement form | yes |
| Case endorsement letter | WS4 | Not on her list; reuse the CFA or ask | **no** |
| Referral letter layout | WS5 | Not on her list; a report sent to PNP or City Hall may serve as a sample | partly |
| Report layouts | WS2 | Monthly or quarterly KP report; report sent to DILG | yes |
| Case intake layout | WS3 | Complaint form | yes |
| Letterhead and seal | WS1 | Ask separately | **no** |
| Privacy notice | WS3 | She was "Not sure" (Q35); ask whether one exists | **no** |
| Response templates | WS1 | Ask for standard replies | **no** |

### B9. "How verified" options for residency (Q16)

Barangay ID or certificate of residency; Household list or masterlist; ID showing a Calarian address; Purok leader confirms; Other (free text). Status: Answered.

---

## C. Questionnaire to feature map (Q1 to Q43)

| Q# | Topic | Answer in one line | Decides | Status |
| :--- | :--- | :--- | :--- | :--- |
| Q1 | Records today | Logbook, Excel/Word, a computer program, loose papers | Import (WS6, stretch) | Answered |
| Q2 | Hardest part | Paperwork, finding old records, same case twice | Duplicate flags, party merge, search | Answered |
| Q3 | Logins | Captain, Secretary, Kagawad (PO), Tanod/desk officer; Lupon added by decision | B1, presets | Answered |
| Q4 | Who decides | Captain, Secretary | Preset design | Answered |
| Q5 | Who signs | Captain for letters, summons, reports; Pangkat Chairman for CFA | B2 | Answered |
| Q6 | Administrator | Both | Two admins | Answered |
| Q7 | Sensitive records | Captain, Secretary, Kagawad (PO), kagawads on minors' cases, VAW focal | `cases.view_confidential` | Answered, check (Lupon) |
| Q8 | Record year | Calendar year; boxed; 4 years | `fiscal_year.*`, `records.lookback_years` | Answered |
| Q9 | Numbers | Yearly restart; `month-001-year` | `numbering.*` | Answered, check |
| Q10 | Handled by barangay | Five types | B7 | Answered |
| Q11 | Referred | Six types | B7 | Answered |
| Q12 | Outside offices, follow-up | Eight offices; 3 days | B7, `referral.follow_up_days` | Answered |
| Q13 | After a report | Seven steps | Existing flow | Answered |
| Q14 | Guest reporting | Not sure | No change (no resident accounts) | Open |
| Q15 | Walk-in recorder | Secretary, Lupon members | No design change; D1 | Answered |
| Q16 | Resident check | Four methods | B9, `case_field_configs` | Answered |
| Q17 | Party details | Five fields; non-resident extras | B4 | Answered |
| Q18 | Complaint steps | Eight steps incl. blotter-only split | Lifecycle, `record_type` | Answered |
| Q19 | Case types | Ten | B3 | Answered, check |
| Q20 | Summons count | Not counted; 15 working days | `cases.*_days` | Answered, check |
| Q21 | Summons record | Four fields | B4 | Answered |
| Q22 | If not settled | CFA | Escalation | Answered |
| Q23 | Who decides | The complainant | Escalation form | Answered |
| Q24 | Settlement record | Agreement, date, terms or amount | Settlement modal | Answered |
| Q25 | Deadlines | 15 working days + 15 to 30 | `cases.*_days` | Answered, check |
| Q26 | Report to case | Sometimes | Keep "Convert to case" | Answered |
| Q27 | Reminders | Four kinds | Secretary dashboard | Answered |
| Q28 | Agencies | PNP, CSWDO, utilities | Pilot accounts | Answered |
| Q29 | Agency actions | View, download, mark received, update | WS5 | Answered, check |
| Q30 | Agency approver | Secretary | `agencies.approver_position` | Answered |
| Q31 | Agency check | Official letter or ID | `agencies.require_id_document` | Answered |
| Q32 | Reports | KP yearly, case-type monthly, DILG | B6 | Answered, check |
| Q33 | Output | Printed | `reports.default_format` | Answered |
| Q34 | Priorities | Five | Demo order, WS1 and WS2 | Answered |
| Q35 | Privacy notice | Not sure | `privacy.notice_text` | Open |
| Q36 | Retention | 5 years | `privacy.case_retention_years` | Answered |
| Q37 | Devices | Desktop, laptop | Desktop-first staff screens | Answered |
| Q38 | Internet | Stable | No offline mode | Answered |
| Q39 | Anything else | Blank | none | n/a |
| Q40 | Documents | Eleven items offered | B8 | Form |
| Q41 | How to share | Email or in person | none | Answered |
| Q42 | Contact again | Blank | Ask tomorrow | Open |
| Q43 | Contact details | Blank | Ask tomorrow | Open |

---

## D. Design-change triggers (status)

| Trigger | Fired? | Result |
| :--- | :--- | :--- |
| Another office owns case handling (Q15) | **No** | Secretary and Lupon members record walk-ins |
| Escalation route is not "summons, then escalate" (Q20, Q22, Q23) | **Yes** | Days replace the summons limit; complainant decides; CFA signed by the Pangkat Chairman (`REVISION_2.md` C1, C2) |
| Frequent internet outages (Q38) | **No** | |
| Existing digital records to import (Q1) | **Partly, then decided (D2)** | WS6 import, stretch |
| Blotter-only records separate from complaints (Q18) | **Yes (new)** | `record_type` |
| Number format differs (Q9) | **Yes (new)** | `{MM}` token |
| Agencies need case papers (Q29) | **Yes (new)** | Referral attachments |

---

## E. When forms or answers arrive

1. Put each answer in a sheet (question, answer, who answered, date).
2. Classify it: **setting or data change** (edit the value, then Confirm on `/admin/settings/pending`), **template swap** (owner replaces the draft form), or **design change** (take it to the lead first).
3. Update the Blueprint and proposal; mark requirements as confirmed.
4. If the case module is out of scope, set `cases.enabled = false`. No code removal is needed.
