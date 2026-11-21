# PLACEHOLDER REGISTER

Every value below is an **assumption** until the Barangay Secretary confirms it. Each one lives in the database (a `settings` row or a seed table row), starts with `is_confirmed = false`, and shows a "Pending confirmation" badge in the UI. Nothing here may be hardcoded.

**Question numbers (Q#)** refer to `Barangay_Secretary_Questionnaire.docx` (42 questions, Part 1 starts at Q1 "How are records kept today?").

**How to confirm a value:** edit it in the admin screen (or seed row), then press **Confirm** on `/admin/settings/pending`. **Swap owner** is the workstream that makes the change when the answer arrives (WS1 to WS5 are defined in `TEAM_WORKSTREAMS.md`).

---

## A. Settings keys (table `settings`)

Seeded by `RequiredDataSeeder` with `updateOrCreate`. `type` is one of `string|int|bool|json|date`.

| Key | Type | Default (placeholder) | Group | Q# | Swap owner | Notes |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| `system.setup_completed` | bool | `false` | system | none | WS1 | Set to `true` by the wizard; migration sets it `true` if an admin already exists. Never shown as pending |
| `system.setup_step` | string | `welcome` | system | none | WS1 | Wizard progress. Not shown as pending |
| `barangay.name` | string | `Barangay Calarian` | barangay | none | WS1 | Entered in the wizard; confirmed when the wizard finishes |
| `barangay.city` | string | `Zamboanga City` | barangay | none | WS1 | Same |
| `barangay.address` | string | empty | barangay | none | WS1 | Required in wizard step 3 |
| `barangay.contact_number` | string | empty | barangay | none | WS1 | |
| `barangay.email` | string | empty | barangay | none | WS1 | |
| `barangay.seal_path` | string | empty | barangay | none | WS1 | Uploaded image, public disk |
| `fiscal_year.start_month` | int | `1` | fiscal_year | Q8 | WS1 | 1 = January. Start and end dates of a year derive from this |
| `fiscal_year.lock_on_close` | bool | `true` | fiscal_year | Q10 | WS1 | Closing a year locks finalized records |
| `numbering.referral.format` | string | `REF-{YEAR}-{SEQ:4}` | numbering | Q9 | WS1 | Tokens: `{YEAR}`, `{YY}`, `{SEQ:n}` |
| `numbering.case.format` | string | `CASE-{YEAR}-{SEQ:4}` | numbering | Q9 | WS1 | |
| `numbering.summons.format` | string | `SUM-{YEAR}-{SEQ:4}` | numbering | Q9 | WS1 | |
| `numbering.reset` | string | `per_fiscal_year` | numbering | Q9 | WS1 | `per_fiscal_year` or `never` |
| `referral.follow_up_days` | int | `7` | referrals | Q14 | WS5 | Move the value from `config/reports.php` into settings; `ReferralService` reads this |
| `cases.enabled` | bool | `true` | cases | adviser | WS1 | Master switch. Off hides the whole case module |
| `cases.summons_limit` | int | `3` | cases | Q22 | WS4 | Limit before a case is flagged eligible to escalate |
| `cases.summons_limit_scope` | string | `case` | cases | Q22 | WS4 | `case` (all summons of the case) or `respondent` (per respondent) |
| `cases.block_extra_summons` | bool | `false` | cases | Q22 | WS4 | `false` = warn and require an override note, never block |
| `cases.escalation_decider_position` | string | `punong_barangay` | cases | Q25 | WS4 | Position `code` that decides an escalation |
| `cases.mediation_deadline_days` | int | empty (no deadline) | cases | Q24 | WS4 | Days from `filed_on`. Empty = no overdue indicator |
| `cases.settlement_deadline_days` | int | empty (no deadline) | cases | Q24 | WS4 | Same rule |
| `cases.default_venue` | string | `Barangay Hall` | cases | Q23 | WS4 | Default on summons and hearings |
| `cases.visibility` | string | `restricted` | cases | Q37 | WS3 | `restricted` = needs `cases.view`; `confidential_only_owner` is reserved for later |
| `cases.require_residency_basis` | bool | `false` | cases | Q18 | WS3 | If true, "How verified" is mandatory when residency is set to Resident |
| `agencies.registration_open` | bool | `true` | agencies | Q29 | WS5 | Turns public agency sign-up on or off |
| `agencies.approver_position` | string | `admin` | agencies | Q31 | WS5 | Who approves accounts (informational label; permission `agencies.manage` enforces it) |
| `agencies.require_id_document` | bool | `true` | agencies | Q31 | WS5 | |
| `privacy.notice_text` | string | Draft text from `SPEC_ADDENDUM.md` section 5.1 | privacy | Q35 | WS3 | Shown on case forms and agency registration |
| `privacy.case_retention_years` | int | empty (keep until configured) | privacy | Q36 | WS1 | Informational until a purge job is approved |
| `reports.default_format` | string | `pdf` | reports | Q34 | WS2 | `pdf`, `excel`, `print` |
| `ui.form_style` | string | `single_page` | ui | Q40 | WS3 | `single_page`, `stepper`, `table`. Only `single_page` is built now |

---

## B. Seed-table placeholders (each row has `is_confirmed = false`)

### B1. `positions` (Q3, Q4, Q7)
| code | name | signatory by default |
| :--- | :--- | :--- |
| `punong_barangay` | Punong Barangay | yes |
| `barangay_secretary` | Barangay Secretary | yes |
| `barangay_treasurer` | Barangay Treasurer | no |
| `kagawad` | Kagawad | no |
| `lupon_chairperson` | Lupon Chairperson | yes |
| `lupon_secretary` | Lupon Secretary | no |
| `tanod` | Tanod | no |
| `admin_staff` | Administrative Staff | no |

Swap owner: **WS2** (rename, add, remove rows; the codes referenced by settings must be updated too).

### B2. `signatory_assignments` (Q4)
| document_type | prepared_by | noted_by | approved_by |
| :--- | :--- | :--- | :--- |
| `referral_letter` | `barangay_secretary` | none | `punong_barangay` |
| `summons` | `barangay_secretary` | none | `punong_barangay` |
| `case_endorsement` | `barangay_secretary` | none | `punong_barangay` |
| `certificate_to_file_action` | `lupon_secretary` | none | `lupon_chairperson` |
| `monthly_report` | `barangay_secretary` | none | `punong_barangay` |
| `annual_report` | `barangay_secretary` | `barangay_treasurer` | `punong_barangay` |

Swap owner: **WS2**.

### B3. `case_types` (Q20)
Physical injury, Noise disturbance, Boundary or property dispute, Theft, Domestic or family dispute, Threats or verbal abuse, Unpaid debt, Damage to property, Other. Swap owner: **WS3**.

### B4. `case_field_configs` (Q17, Q18, Q19, Q23)
| entity | field_key | required | visible | applies_to |
| :--- | :--- | :--- | :--- | :--- |
| party | `full_name` | yes | yes | all |
| party | `alias` | no | yes | all |
| party | `birth_date` | no | yes | all |
| party | `sex` | no | yes | all |
| party | `civil_status` | no | yes | all |
| party | `occupation` | no | yes | all |
| party | `contact_number` | no | yes | all |
| party | `address_line` | no | yes | all |
| party | `purok_id` | no | yes | resident |
| party | `residency_status` | yes | yes | all |
| party | `residency_basis` | no | yes | all |
| party | `home_barangay` | no | yes | non_resident |
| party | `home_city` | no | yes | non_resident |
| party | `id_type` | no | yes | all |
| party | `id_number` | no | yes | all |
| case | `case_type_id` | yes | yes | all |
| case | `filed_on` | yes | yes | all |
| case | `incident_date` | no | yes | all |
| case | `incident_location` | no | yes | all |
| case | `narrative` | yes | yes | all |
| summons | `respondent_case_party_id` | yes | yes | all |
| summons | `issued_on` | yes | yes | all |
| summons | `hearing_at` | yes | yes | all |
| summons | `venue` | no | yes | all |
| summons | `served_by_personnel_id` | no | yes | all |
| summons | `service_result` | no | yes | all |

Swap owner: **WS3** (party and case fields), **WS4** (summons fields).

### B5. `escalation_destinations` (Q26)
PNP Station, Prosecutor's Office, Other. Swap owner: **WS4**.

### B6. `report_templates` (Q32 to Q34)
Seven rows, all draft: `problem_reports_summary`, `referral_register`, `case_summary`, `summons_log`, `kp_accomplishment`, `personnel_list`, `year_over_year`. Layout details in `SPEC_ADDENDUM.md` section 4. Swap owner: **WS2** (replace layout and columns with the barangay's real formats from the Appendix documents).

### B7. Existing data to confirm (no schema change)
| Data | Q# | Swap owner | What to confirm |
| :--- | :--- | :--- | :--- |
| `categories` (six current) | Q12 | WS1 (seed edit) | Category list and risk weights |
| `routing_rules`, `offices` (who handles what) | Q12, Q13 | WS1 (seed edit) | Which problems the barangay handles vs refers, and to whom |
| `emergency_contacts` | Q15 | WS1 (seed edit) | Hotline numbers and order |
| `puroks` | Appendix | WS1 (seed edit) | Official purok list |
| Permission presets (`config/permission_presets.php`) | Q3, Q5, Q6 | WS2 | Which positions get which permissions |

### B8. Documents and wording that are drafts until the barangay's own versions arrive
| Item | Swap owner | Replaced by (Appendix document) |
| :--- | :--- | :--- |
| Printable summons layout and wording | WS4 | Summons form |
| Case endorsement letter | WS4 | Endorsement or certificate form |
| Referral letter layout | WS5 | Referral or endorsement letter sample |
| Letterhead and seal | WS1 | Letterhead and seal file |
| Report layouts (all seven) | WS2 | Monthly and quarterly report samples |
| Privacy notice | WS3 | Privacy notice or consent form |
| Response templates | WS1 | Any standard replies the secretary uses |

---

## C. Questionnaire to feature map (Q1 to Q42)

| Q# | Topic | Decides | Where it lands |
| :--- | :--- | :--- | :--- |
| Q1 | How records are kept now | Migration or import needs, training | Notes only; WS1 decides if an import is needed |
| Q2 | Biggest difficulty | Demo priorities | Notes only |
| Q3 | Positions that use the system | Position list, permission presets | B1, `permission_presets` |
| Q4 | Who signs documents | Signatory matrix | B2 |
| Q5 | Who adds or removes accounts | Who gets `users.manage` | Permission presets |
| Q6 | Records hidden from some staff | Case access rules | `cases.visibility`, `cases.view_confidential` |
| Q7 | Who updates the official list | Who gets `personnel.manage` | Permission presets |
| Q8 | Record year | `fiscal_year.start_month` | Settings |
| Q9 | Number formats | `numbering.*` | Settings |
| Q10 | Year-end handling | `fiscal_year.lock_on_close`, close flow | Settings, WS1 close modal text |
| Q11 | How residents report today | Intake copy and onboarding text | Notes only |
| Q12 | Problem types and who handles them | Categories, routing rules | B7 |
| Q13 | Outside offices | Offices and contact methods | B7 |
| Q14 | Follow-up after a letter | `referral.follow_up_days` | Settings |
| Q15 | Emergency contacts | `emergency_contacts` | B7 |
| Q16 | Who records walk-in complaints | Who gets `cases.create`; whether Lupon owns cases | Permission presets; design change if Lupon |
| Q17 | Complainant and respondent fields | `case_field_configs` (party) | B4 |
| Q18 | Residency proof | `cases.require_residency_basis`, "How verified" options | Settings, WS3 form |
| Q19 | Non-resident extras | `case_field_configs` (`home_*`) | B4 |
| Q20 | Case types | `case_types` | B3 |
| Q21 | KP first or police first | Case lifecycle, which cases are in scope | `WORKFLOW.md` draft lifecycle (design change possible) |
| Q22 | Summons count and next step | `cases.summons_limit`, scope, `block_extra_summons` | Settings |
| Q23 | Summons record fields | `case_field_configs` (summons), `cases.default_venue` | B4, settings |
| Q24 | Deadlines | `cases.*_deadline_days` | Settings |
| Q25 | Documents when unsettled, and who decides | `cases.escalation_decider_position`, escalation documents | Settings, WS4 form |
| Q26 | Where unsettled cases go | `escalation_destinations` | B5 |
| Q27 | What is recorded when settled | Settlement fields | WS4 settlement modal |
| Q28 | Reports becoming cases | Whether "Convert to case" stays | WS3 |
| Q29 | Which agencies | Offices and `agencies.registration_open` | Settings, B7 |
| Q30 | What agencies may do | Agency portal actions | WS5 |
| Q31 | Agency verification and approver | `agencies.*` | Settings |
| Q32 | Reports submitted | `report_templates` rows | B6 |
| Q33 | Filters ranking | Default filters in Reports Center | WS2 |
| Q34 | Output format | `reports.default_format` | Settings |
| Q35 | Privacy notice | `privacy.notice_text` | Settings |
| Q36 | Retention | `privacy.case_retention_years` | Settings |
| Q37 | Who may see case records | `cases.visibility`, permission presets | Settings |
| Q38 | Devices | Layout priorities (desktop first or mobile first) | Notes only |
| Q39 | Internet reliability | Whether offline support is needed | Notes; escalate to lead if often down |
| Q40 | Screen style | `ui.form_style` | Settings |
| Q41 | Staff computer comfort | Training plan | Notes only |
| Q42 | Anything else | Open items | Lead triages |

**Design-change triggers** (bring to the lead before editing code): Q16 says another office (Lupon) owns case handling; Q21 or Q26 show a different escalation route than "summons then escalate"; Q39 shows frequent outages; Q1 shows existing digital records that must be imported.
