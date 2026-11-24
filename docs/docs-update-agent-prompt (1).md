# AGENT PROMPT: Update the Docs for the Resident Complaint Intake and Report/Case Separation

Paste everything below into your AI coding agent, with the repo's `docs/` folder available.

---

## 0. Role and mission

You are a documentation maintainer for **Barangay-Reporting-System-1** (Laravel). Your job is to update the project's Markdown documentation (and only the documentation) so it reflects a set of design decisions made by the team. You will **not** change application code, migrations, tests, or seeders. Where the docs describe something that does not exist in code yet, mark it clearly as **Planned**.

Work step by step in the order below. Finish and verify each step before starting the next. At the end, produce the change report described in Section 6.

## 1. Ground rules

1. **Read before writing.** Read every file in `docs/` plus the root `TASKS.md` and `CONVENTIONS.md` in full before editing. Locate sections by heading text and by searching for keywords (for example "Report to Case Conversion", "record_type", "summons", "hearing", "signator", "Decision 65", "open question"). Do not trust section numbers from this prompt; they are hints only.
2. **Preserve the Core Governance Rule everywhere:** "The AI suggests, the rules route, the officials decide." Nothing in the new flow may auto-transition, auto-docket, auto-create parties, auto-issue summons, or auto-dismiss. Online submissions, summons counts, non-appearance, and deadlines only produce reminders, warnings, or nudges.
3. **Do not invent facts.** Every new statement must be one of these three kinds, and you must label it:
   - **[DECIDED]**: decided by the team (Section 2).
   - **[PROPOSED, pending Secretary/Adviser confirmation]**: working answer the team adopted for now (Section 3). Render these with the project's existing "pending" convention (`<x-ui.pending-badge>` / `is_confirmed = false` language) and list them in the pending-items register if the docs have one.
   - **[OPEN QUESTION]**: unresolved; add it to the Secretary/Adviser question list.
4. **Do not silently resolve contradictions.** If two docs disagree, fix the clear mistakes (Step 11) and list the ones that need a human decision in the report.
5. **Keep the existing doc voice, heading structure, tables, and numbering style.** Add to files; do not rewrite unrelated sections. Keep cross-references and anchors valid. If you add a numbered decision, continue the existing numbering instead of renumbering.
6. **Do not hand-edit generated files** (`ROUTES_snapshot.json` or `ROUTES.snapshot.json`, `composer.lock`, `public/build/`). See Step 10.
7. **Use the docs' existing vocabulary:** Report Queue, Barangay Cases Docket, `record_type` (`blotter` | `complaint`), `BarangayCase`, WS3/WS4, `SignatoryResolver`, `WorkingDays`, `setting()`, `cases.enabled`.

## 2. Decisions to implement [DECIDED]

These are the source of truth. Everything you write must be consistent with them.

**D1. Two separate systems, one conversion bridge.**
- **Community problems** (floods, drainage, streetlights, garbage, potholes) go to the **Report Queue** and keep the existing 12-status report lifecycle. **This flow does not change.** Document it exactly as the current docs describe it; do not add dispute logic to it.
- **Complaints** (disputes between people) go to the **Barangay Cases Docket** (Katarungang Pambarangay) with the existing 8 case statuses.
- The two are never merged in management screens. The existing Report to Case Conversion feature stays as the bridge for community reports that turn out to be disputes.
- Page names will be renamed later (Report Queue to "Community reports", Barangay Cases to "Cases & Blotter" or similar). Record this as a **Planned rename** with a note that routes, permission slugs, and database names do not change as part of it.

**D2. Resident entry screen has three buttons, not four.**
The resident create page (`resources/views/resident/create.blade.php`, called "resident/create.blade" in discussion) shows, before any form:
1. **Emergency** (reuses the existing emergency path and contacts; no new behavior)
2. **Report a Community Problem** (opens the existing community problem form unchanged)
3. **File a Complaint** (opens the new complaint intake form)

There is **no separate "File a Blotter" button.** Reason: the blotter entry becomes the record behind a complaint. A resident who files a complaint automatically creates the blotter entry for it.

**D3. Blotter still exists on the admin side as the logbook.**
- A complaint intake produces (a) a blotter logbook entry (`record_type = blotter` data: the 5Ws) and (b) the complaint/case data (respondent, how rights were violated, relief requested).
- **One case record per complaint, with the blotter entry as its logbook reference.** A complaint carries a blotter reference plus a case number (two numbers, one record). A standalone blotter (staff-recorded, no dispute) gets only the blotter number and stays at `filed` with no summons or clock unless staff upgrade it to a complaint.
- Residents do not choose between "blotter" and "complaint." Staff may still record walk-in blotter-only entries.

**D4. Resident complaint submission is a pending request only.**
- The resident form creates a **pending complaint intake**, never a case. It appears in a new **"Pending intake"** tab on the Cases Docket.
- Staff review it, check eligibility and residency, review duplicate warnings, and click **"Docket this,"** which opens the existing WS3 intake form prefilled. Only then is the case created at `filed`, and the blotter entry logged.
- The resident sees only an acknowledgement ("received, please visit the barangay hall to sign and complete filing") with **no case status, no respondent information, and no tracking of the dispute**, to protect the respondent's privacy.
- Respondent details typed by the resident are **not** auto-created as parties. Staff confirm them (same rule as reporter details in the conversion flow).
- Online submission never replaces the in-person signature and appearance.

**D4a. Confidential matters are excluded from the online form.** VAWC (violence against women and children) and child-protection matters must not go through the public complaint form. The form shows hall and hotline guidance instead. Include this as a screening step at the start of the complaint form.

**D5. Printing and signatures.** After docketing, the system fills the **Blotter** and **Complaint** printable templates from the stored data. The complainant signs in person; the Punong Barangay or Lupon Chairman acknowledges. All printables keep the DRAFT watermark (`<x-ui.draft-watermark>`) until Task 6 confirms the official forms. Optional, Planned: staff upload the signed scan to the case (the docs did not define this before; label it Planned).

**D6. The wrong-door notice.** The Community Problem form gets a short notice: "Is this a conflict with a specific person, like a neighbor, a debt, or an injury? File a Complaint instead, or visit the barangay hall." Wording is draft until confirmed.

**D7. Community problem reports may be made without an account and anonymously.** This answers the earlier question where the Secretary said "Not sure" (Q14). It is a team decision, not a Secretary-confirmed fact, so tell the Secretary in the question list.
- Applies to the **Community Problem form** (Report Queue) only. It changes who may submit, not the 12-status lifecycle or the Staff Review Queue.
- No login required. Name and contact are optional. A "Report anonymously" choice leaves them out. Location and description stay required, because staff need them to inspect and route the problem.
- The **Complaint form is not affected**: complainant and respondent identity are required and anonymity is not offered (D4). The Emergency path is unchanged.
- If the code currently requires an account for the resident create page, document guest and anonymous reporting as **Planned**. Do not change code.
- Supporting details are in P11 (proposed).

## 2A. Confirmed by the Secretary's questionnaire [CONFIRMED]

Source: `Barangay_Secretary_Questionnaire__Responses_.xlsx`, answered by the Barangay Secretary on 2026-11-22. Cite as "Secretary's questionnaire, Q#". **Anything in Sections 3 and 4 that conflicts with this section is overridden by this section.** Mark these items as confirmed (not pending) in the docs, but attribute them to the Secretary's answers, not to the Adviser.

- **C1. Record keeping (Q1, Q2).** Today: handwritten logbook, Excel/Word files, a computer program, loose papers. Hardest parts: too much handwriting and paperwork, hard to find old records, the same case recorded twice (supports duplicate warnings, party merge, and legacy import).
- **C2. Users and access (Q3, Q4, Q6, Q7).** Positions that need logins: Barangay Captain, Barangay Secretary, Kagawad (Peace & Order), Tanod / desk officer. Positions that decide (review, approve, close): Barangay Captain and Barangay Secretary. System administrator: Barangay Captain. Positions that may see **sensitive blotter and case records**: Barangay Captain, Barangay Secretary, Kagawad (Peace & Order), other kagawads who handle cases involving minors, and the VAW Focal person for VAW-related cases. Document how this maps onto `users.role`, the permission presets, and sensitive-record visibility. Do not change the 20-slug or 4-preset counts in the docs; if the current presets do not match, list the gap in the report.
- **C3. Signatories (Q5).** Referral/endorsement letter: Barangay Captain. Summons: Barangay Captain. Monthly or yearly reports: Barangay Captain. Certificate to File Action: the **Pangkat Chairman** usually signs it, once the case has been endorsed to the Pangkat (the Secretary chose "Other" and explained this).
- **C4. Record year (Q8).** Calendar year, January to December. Old records are boxed or archived. Staff only look up the last **4 years** (matches the legacy import scope).
- **C5. Numbering (Q9).** Numbers restart every year. Format is month, case number (starting at 001), year, which is `{MM}-{SEQ:3}-{YEAR}` (example `11-001-2026`). The answer covers **case, blotter, and referral numbers**. So `CASE-{YEAR}-{SEQ:5}` in PROJECT.md and the `CASE-2026-00001` / `BLOT-2026-00001` shown in the UI contradict the Secretary. Document `{MM}-{SEQ:3}-{YEAR}` as confirmed and flag PROJECT.md and the UI as needing correction. Still **unconfirmed**: formats for summons (`SUM-`), CFA, and the referral prefix (`REF-YYYY-NNNN`), and whether blotter and case numbers share one sequence.
- **C6. Community problems are existing barangay work (Q10 to Q13).** The barangay handles itself: drainage/canal, garbage/illegal dumping, streetlight, stray animals/noise, peace and order concerns. In charge: Kagawad on the committee, Tanod, Secretary, Punong Barangay. Referred out: water supply/pipe leaks, electric lines/power outage, fire hazard, illegal construction, serious crime or threats, medical or health emergency. Offices: City Engineer's Office, CDRRMO, water utility, electric utility, CENRO, PNP, BFP, CSWDO. Follow-up after a referral letter: **3 days**. Steps: Secretary or desk officer records it, Kagawad or tanod inspects the site, Barangay Captain is informed, a referral letter is sent if needed, the barangay follows up, the resident is told the result; if it is beyond the barangay's capacity it is referred, otherwise the barangay uses its own resources. This matches the existing community report lifecycle (Mode A internal, Mode B referral with 3-day follow-up). **Wherever the docs say the Report Queue is a new service with no basis in barangay practice, correct that:** the questionnaire shows the barangay already handles these problems, and the Report Queue organizes that existing process (the paper complaint form is a separate, dispute-only document).
- **C7. Reporting without an account (Q14).** Answered "Not sure." The team has since decided this in D7 (guest and anonymous community reports are allowed). Do not attribute D7 to the Secretary; list it in Step 13 as a team decision she should be told about.
- **C8. Intake and parties (Q15 to Q17).** Walk-in complaints and blotter entries are recorded by the Secretary and Lupon members. Residency is checked by: Barangay ID or certificate of residency, household masterlist, an ID showing a Calarian address, or Purok leader confirmation. Details recorded for each complainant and respondent: full name, alias, age or birth date, contact number, complete address. For a non-resident, also: home barangay and city, and reason for being in the barangay.
- **C9. Complaint process (Q18).** In order: details of complainant and respondent are taken; the case is classified by type; summons is issued to the respondent; hearing or mediation is held; settlement agreement is signed; Certificate to File Action is issued if not settled; the case is endorsed to the police, prosecutor, or court. The barangay also **separates blotter-only entries from formal complaints.** So the step before summons is **classification by case type plus the blotter-only vs formal decision**, made by staff.
- **C10. Case types in the logbook (Q19).** Physical injury; noise disturbance; boundary/property dispute; theft; domestic/family dispute; threats/verbal abuse; unpaid debt; damage to property; vehicular accident; BCPC and VAWC cases. Use this list as the case-type list for staff intake and the complaint form. BCPC and VAWC are real logbook case types, so they stay available to staff, but they are **not** offered on the public online form (D4a) and they follow the restricted visibility in C2.
- **C11. Summons and deadlines (Q20, Q21, Q25).** The barangay does **not count summonses**. It counts days: if there is no settlement after **15 working days** at the barangay level, the complaint is endorsed to the Pangkat. Mediation is 15 working days; conciliation is another 15 to 30 days (the Secretary did not say whether these are working days). Each summons records: date issued, hearing date and time, who delivered it, and the result (received, refused, not found).
- **C12. Unsettled and settled (Q22 to Q24).** Unsettled: a CFA is issued, and the **complainant** decides to move the case forward. Settled: record the signed written agreement, the date settled, and the terms or amount.
- **C13. Conversion (Q26).** Reports sometimes need to become formal blotter cases ("Sometimes"). This confirms the report-to-case conversion feature.
- **C14. Reminders (Q27).** Hearing dates; summons not yet delivered; cases close to their deadline; unsettled cases ready for the next step. This is the complete confirmed reminder list.
- **C15. Agencies (Q28 to Q31).** Regular agencies: PNP, CSWDO, water/electric utility. An agency login may: view only the cases sent to it, download the case papers sent to it, mark as received and add updates. The Secretary approves new agency accounts. Today an agency person is verified by an official letter or ID.
- **C16. Regular reports (Q32).** The KP accomplishment report is submitted yearly.

**Overrides to apply across the whole prompt:**
1. **Remove the summons-count nudge** everywhere it appears (P3, Steps 2, 7, 8, 10, 12). Replace it with a day-based indicator: when 15 working days have passed at the barangay level with no settlement, the case shows "Ready for next step: endorse to Pangkat" using the existing deadline badge states. It is an indicator only, never an automatic transition. Do keep counting summonses as a plain number for the record, with no threshold.
2. **Reminders:** use exactly the four in C14. The proposed "Summons due next working day" reminder is replaced by "Summons not yet delivered." Do not add other reminders as confirmed.
3. **Summons service result** has three confirmed values: received, refused, not found. Add `served_by` ("who delivered it"). If the docs or code also have an `other` value, keep it but label it "not in the Secretary's list."
4. **Signatories:** use C3 (Barangay Captain for summons, referral letter, and reports; Pangkat Chairman for the CFA). Resolve through `SignatoryResolver`; never hardcode.
5. **Numbering:** use C5. Do not present the numbering mismatch as undecided.
6. **Working days:** mediation is confirmed as working days. The start point of the clock is proposed as the first meeting (P2) and is still unconfirmed. The conciliation unit is proposed in P2 and is also unconfirmed.
7. **Stale wording:** any line elsewhere in this prompt that still says "nudge" in connection with summons, summons counts, or non-appearance means the day-based "Ready for next step" indicator and the extended soft warning, nothing else.

## 3. Proposed working answers [PROPOSED, pending confirmation]

Mark each as pending. These came from the Local Government Code KP provisions and secondary commentary, so they must be confirmed by the Barangay Secretary or Punong Barangay before the thesis documents treat them as fact.

**P1. Before summons (eligibility screening).** Per the KP rules, a complaint may be made orally or in writing to the Lupon Chairman (usually the Punong Barangay), who issues the summons to the respondent by the next working day, with notice to the complainant. So the only step between `filed` and summons is a short **eligibility checklist** on the filed case:
- Are the parties individuals actually residing in the same barangay (or the covered adjoining arrangement)? Corporations and other juridical entities are generally excluded.
- Is it a matter KP covers, and not excluded (for example, criminal cases where the accused is in police custody)?
- Is the dispute confidential (VAWC, child protection)? If yes, handle outside the normal flow.
- Residency verification of both parties recorded.
The checklist is informational and staff-completed. Per C9, the Secretary's own pre-summons step is classifying the case by type and deciding blotter-only vs formal complaint, so put those two items first on the checklist and mark the legal-eligibility items (same barangay, juridical entities, police custody) as proposed. The confirmed reminder is "Summons not yet delivered" (C14). It is a reminder only, never an automatic transition and never a blocker.

**P2. Mediation clock start.** The 15-day mediation period runs from the **first meeting of the parties before the Punong Barangay**, not from docketing. Add a stored **first-meeting date** and start the clock from it; `filed_on` is only the fallback if the Secretary says the barangay counts from filing. The Pangkat convenes within 3 days of being constituted, then has 15 days to settle, extendable once by up to 15 more days. This resolves PROJECT.md "open question 1" provisionally. **Working days are now confirmed for mediation (C11).** Keep the existing `WorkingDays` engine. **Conciliation period [PROPOSED]:** until the Secretary says otherwise, count the Pangkat period in working days too, and store the period lengths (15, extendable by up to 15; the Secretary said "15 to 30") as `setting()` values so a change needs no code. The sources found say "15 days" without specifying the unit, so this must be confirmed. Still [OPEN QUESTION]: whether the barangay counts conciliation in working days, and who decides the extension.

**P3. Non-appearance.**
- **Respondent does not appear at mediation:** record `failed_to_appear` and the service result; the absence is recorded in the minutes; the respondent loses the right to a connected counterclaim. The case does not end there. The proposed next step is **constituting the Pangkat** (endorse to `in_conciliation`) rather than going straight to a CFA. Willful non-appearance can later support a CFA for the complainant who attended. Willful refusal can be treated as contempt by the municipal or city court, which is **outside the system** (mention only as a note).
- **Complainant does not appear after due notice:** staff may set `dismissed` with a mandatory note. Staff choose; never automatic.
- **Repeated unserved or ignored summons:** the Secretary does not count summonses; she counts days (C11). Show a "Ready for next step: endorse to Pangkat" indicator at 15 working days with no settlement. It never blocks and never auto-advances. A plain summons count may be stored for the record only. Willful refusal to appear can be punished as contempt by the municipal or city court, which is outside the system.
- **Warning extension:** the existing soft warning on `in_mediation` to `escalated` is extended to also fire when the respondent never appeared at mediation ("The Pangkat usually must be constituted first").
- **Source caution:** the consequences above (loss of the counterclaim right, CFA support for the complainant, dismissal for complainant non-appearance) come from KP provisions plus commentary sites that are less authoritative than the statute. The Secretary or Punong Barangay must confirm them before any thesis document states them as fact. The barangay's own practice has the final word.

**P4. Numbering (partly confirmed by C5).** `{MM}-{SEQ:3}-{YEAR}` is confirmed for case, blotter, and referral numbers, restarting every year. Still proposed: the sample forms show a Barangay Case No. on the complaint and a series, page, and entry number on the blotter from the physical logbook, so add **optional** blotter page and entry fields. The Secretary did not mention page or entry numbers, so ask whether the logbook uses them and whether blotter and case numbers share one sequence.

**P4a. One record, two numbers.** A complaint carries a Barangay Case No. plus a blotter reference (and optional logbook series, page, and entry). A standalone blotter gets only the blotter number. Whether blotter and case numbers share one sequence is still to be confirmed; the Secretary's `{MM}-{SEQ:3}-{YEAR}` answer covers both but did not say.

**P5. Which blotter document is used at intake.** Likely the logbook entry is the intake record and the "Barangay Blotter" sample form is a **certified extract** issued on request. Model it as: store the entry, generate the extract as a printable. Confirm by asking the Secretary for a filled-in logbook page.

**P6. Online complaint intake (answer proposed for the Adviser).** Yes, as a pending request only (D4). The law allows complaints in writing, but signature and in-person appearance are still required, so staff always docket and print. The Adviser must still confirm.

**P7. Exact sequence (answer proposed for Q18's missing Pangkat step).** Based on the KP provisions in the Local Government Code, in general knowledge terms; confirm with the Secretary:
1. Complaint is made, orally or in writing, to the Lupon Chairman (the Punong Barangay). Staff record it, classify the case type, and decide blotter-only vs formal complaint (C9).
2. Summons is issued to the respondent, with notice to the complainant.
3. Mediation before the Punong Barangay. The 15-working-day period starts at the first meeting (P2).
4. If there is no settlement by the end of the period, the case shows "Ready for next step: endorse to Pangkat" (C11). An official then constitutes the Pangkat (members come from the Lupon) and the case moves to `in_conciliation` by explicit action. Record the constitution date and the Pangkat members (check existing fields first).
5. The Pangkat convenes within 3 days of being constituted, then has 15 days to settle, extendable by up to 15 more (P2 for the unit).
6. Settled: `settled`. Not settled: `unsettled`, then a CFA on the complainant's request (`escalated`), and the case is endorsed to the police, prosecutor, or court (C9).
Willful non-appearance of the respondent at mediation leads to step 4 (P3), not to a CFA.

**P8. Signatories (answer proposed; resolve through `SignatoryResolver`, never hardcode).**
- Summons (mediation) and notice to complainant: Barangay Captain (confirmed for the summons, Q5).
- Pangkat notice of hearing / conciliation summons: Pangkat Chairman.
- Amicable settlement: signed by the parties; attested by the Punong Barangay at the mediation stage, or by the Pangkat Chairman at the conciliation stage.
- Blotter extract: prepared by the Barangay Secretary; certified by the Punong Barangay.
- Complaint form: signed by the complainant; recorded and received by the Barangay Secretary or the Lupon member who took it (C8); acknowledged by the Punong Barangay as Lupon Chairman.
- CFA with a Pangkat: the Pangkat Chairman signs (confirmed, Q5). KP commentary describes the Lupon or Pangkat Secretary preparing it and the Chairman attesting, so give the printable a "prepared by" line as well. This detail is from general knowledge and is unverified.
- CFA when no Pangkat was constituted: Barangay Secretary (as Lupon Secretary) prepares it and the Punong Barangay (as Lupon Chairman) signs.

**P9. VAWC, child-protection, and BCPC guidance on the public forms (D4a).** Show a screening step first; if the person answers yes, show a guidance screen **instead of the form** and store nothing.
- Screening question (draft): "Is this about violence against a woman or her child, abuse of a child, or an emergency?" Buttons: "Yes, I need help" and "No, continue."
- Guidance text (draft, English): "If you are in danger, call 911 now. It is free and open 24/7. You can also go to the Barangay VAW Desk at the barangay hall, or to the PNP Women and Children Protection Desk. Please do not use this online form for this concern." For a child: "For a child who is being harmed or needs protection, go to the barangay hall and ask for the BCPC, or call 911 or Bantay Bata 163."
- Draft Filipino: "Kung nasa panganib ka o kailangan mo ng agarang tulong, tumawag sa 911 (libre at 24/7). Maaari ka ring pumunta sa VAW Desk ng barangay o sa Women and Children Protection Desk ng PNP. Huwag gamitin ang online form na ito para sa ganitong kaso." Have the Secretary review both languages.
- Hotline numbers are **`setting()` values, never hardcoded in views**, with these defaults: 911 (national emergency); PNP Aleng Pulis 0919-777-7377 or 0966-725-5961 (listed by DSWD); Bantay Bata 163. Leave the city CSWDO and the barangay VAW Desk numbers blank for the Secretary to fill in. Numbers change, so the Secretary must verify them before go-live.
- Add a "Leave this page" quick-exit button on the guidance screen, and do not keep the screening answer or any record of a "yes."
- On the staff side, VAWC and BCPC remain available only to staff, with the restricted visibility in C2.
- Add a short safety banner on the Community Problem form too, linking to the same guidance, next to the wrong-door notice (D6).

**P10. Reminders and the 3-day follow-up.**
- The 3-day follow-up applies to **referral letters only** (Mode B, C6). It is part of the existing report lifecycle and is not one of the four reminders in Q27.
- Keep the four confirmed reminders (C14) as the complete confirmed list.
- Optional suggestions, **off by default and not confirmed**, to show the Secretary: (a) the settlement compliance date, when the agreed terms fall due; (b) the Pangkat convening deadline, 3 days after constitution; (c) the yearly KP accomplishment report (C16). Label them "not in the Secretary's list." They are reminders only and never change status.

**P11. Guest and anonymous community reports (supports D7) [PROPOSED].**
- An anonymous report stores no name, contact, account link, IP address, or device identifier. Rate limiting uses transient keys only.
- After submitting, show an optional **tracking code** once, so the reporter can check the report's status without an account. It contains no personal data. The page shows the report status only.
- Staff do not use `resident_unreachable` just because a report is anonymous, and the "resident is told the result" step (C6) is skipped when there is no contact.
- Duplicate warnings, inspection, referral, and the 3-day follow-up work as for any report.
- Spam protection: rate limiting, a honeypot field, and optional captcha. Mark all as Planned.

## 4. Step-by-step instructions

### Step 1. Inventory and baseline

1. List every file you will touch: `PROJECT.md`, `WORKFLOW.md`, `DECISIONS.md`, `DATABASE.md`, `API.md`, `ARCHITECTURE.md`, `CURRENT-STATE.md`, `UI_UX_SPEC.md`, `TESTING.md`, `TEAM_WORKSTREAMS.md`, `ACADEMIC_DOCS_UPDATE.md`, `TASKS.md`, `CONVENTIONS.md`.
2. Write a short working note (not committed) listing for each file which sections will change. Keep it for the Section 6 report.
3. Record the current counts you must not break: 12 report statuses, 8 case statuses, 288 tests (as stated in TASKS.md), the 20 permission slugs, and the 4 permission presets.

### Step 2. `WORKFLOW.md`

1. **Report lifecycle sections:** leave the content unchanged. Add one sentence at the top of the report lifecycle section stating that it covers **community problems only** and that disputes use the case lifecycle. Add a second sentence that a report may come from a guest or an anonymous reporter (D7), with the same 12 statuses.
2. **Case lifecycle section (the one that says it "operates entirely independently"):** keep that statement. Then add, in order:
   - **Complaint intake (resident-initiated, Planned):** the pending-intake path from D4 as numbered steps: resident submits, pending intake created, staff review, "Docket this," case created at `filed`, blotter entry logged. Include the confidentiality screen (D4a) and the rule that the resident only sees an acknowledgement.
   - **Pre-summons step** (C9, P1): classification by case type and the blotter-only vs formal decision, plus the proposed eligibility checklist (pending), with the "Summons not yet delivered" reminder (C14).
   - **Summons stage:** summons numbered `SUM-YYYY-NNNN` with `sequence_no`; issuing a summons with a hearing schedule moves `filed` to `in_mediation` by an official action; record `served_by` and the **service result** (received, refused, not found; keep `other` only if it already exists, labeled "not in the Secretary's list").
   - **Hearing stage:** attendance recorded per party (`appeared` or `failed_to_appear`), outcome, and minutes note.
   - **Outcome branches:** agreement means `settled` (terms, date, signed agreement PDF); no agreement means endorse to the Pangkat (`in_conciliation`) and repeat hearing steps; still no agreement means `unsettled`, then the complainant may request a CFA (`escalated`).
   - **Non-appearance handling** per P3 as revised by C11: the extended soft warning, and the day-based "Ready for next step: endorse to Pangkat" indicator (no summons-count nudge).
   - **Clock start** per P2.
   - **Sequence** per P7 (proposed), as a numbered list, including the Pangkat constitution step before `in_conciliation`.
3. Re-check the status-to-action matrix section (the one referenced by TASKS.md Task 2, "§ 11"). Add a row/section for case statuses if the matrix only covers reports. Do not change report rows.
4. **Report to Case Conversion section (§8.8 or equivalent):** keep it. Add a note that this is the bridge for community reports that are really disputes, that it is separate from the new resident complaint intake, and that the respondent rule matches the reporter rule (no auto-created parties). Fix the reason-code naming here only if it contradicts Step 11.

### Step 3. `PROJECT.md`

1. Update the resident-facing scope: three entry buttons (D2), what each does, and that the Emergency button reuses the existing path.
2. Update the product scope description so it states plainly that the system has **two modules joined by conversion** (community reports and Katarungang Pambarangay cases), plus administration. Keep the `cases.enabled` switch and the adviser-pending note; add that the resident complaint intake is also pending adviser confirmation (the adviser question: "Should residents be able to start a dispute complaint online, or should disputes stay walk-in only?").
3. Add the resident complaint intake to the feature list as **Planned**, and the "Pending intake" tab to the staff feature list.
4. Update **open question 1** (when the 15-day clock starts) with the proposed answer P2, keeping it marked unresolved until confirmed.
5. Add new open questions (see Step 13 question list).
6. **Numbering formats:** document `{MM}-{SEQ:3}-{YEAR}` as confirmed (C5) for case, blotter, and referral numbers, restarting yearly. Add the optional blotter series/page/entry rule as pending (P4, P4a). Flag the PROJECT.md and UI mismatches per Step 11.
7. **Guest and anonymous community reports (D7):** mark as Decided. Update any statement that residents must register or log in to report a community problem, and close the Q14 open question by pointing to D7. If the code requires login today, label the guest path Planned.

### Step 4. `DECISIONS.md`

Add new numbered decisions, continuing the existing numbering and style (keep "Decision 65" untouched). Suggested entries, each with Context, Decision, Status (Decided or Proposed-pending):
1. Reports and cases stay separate modules; conversion is the bridge.
2. Resident entry screen has three buttons; no blotter button.
3. A complaint intake creates a blotter entry as the logbook reference for the same case record; two numbers, one record.
4. Resident complaint intake is a pending request only; staff docket; no auto-created parties; resident sees acknowledgement only.
5. Confidential matters (VAWC, child protection) are excluded from the public form.
6. Pre-summons classification step, proposed eligibility checklist, and the "Summons not yet delivered" reminder (reminders only).
7. First-meeting date starts the mediation clock (Proposed).
8. Non-appearance handling and the extended soft warning (Proposed).
9. Page rename is Planned; routes and permissions unchanged.
10. Wrong-door notice on the community form.
11. Community problem reports may be made as a guest or anonymously (Decided, D7; complaints keep identity required).
12. VAWC and child-protection guidance screen with quick exit; hotline numbers are settings (Proposed, P9).
13. Reminder set: the four confirmed reminders, the 3-day follow-up for referral letters only, and optional off-by-default suggestions (Proposed, P10).
14. Signatory and sequence proposals (Proposed, P7 and P8).

Update any older decision that these supersede by adding "Superseded by Decision N" rather than deleting it.

### Step 5. `DATABASE.md`

Document **planned** schema changes only (do not claim they exist). Follow naming conventions (snake_case plural, WS3/WS4 migration prefixes `2026_12_01_30NNNN_` and `2026_12_01_40NNNN_` from CONVENTIONS.md). Mark each item **Planned**.

1. **Pending complaint intake** (suggested table `complaint_intakes`, WS3 range; adapt to existing naming):
   - Resident-submitted fields: complainant details (full name, alias, age or birth date, contact, complete address, residency claim), respondent details (full name, alias if known, address, contact if known), the **5Ws** (what, who, when, where, why/how), how the complainant's rights were violated, relief requested, nature of dispute/case type, confidentiality screen answer, data privacy consent timestamp, submitted_at.
   - Intake status values (for example `pending`, `docketed`, `redirected`, `rejected`) with `reviewed_by`, `reviewed_at`, `outcome_reason` (mandatory when not docketed), and `case_id` once docketed.
   - Note: respondent fields are free text and are **not** linked to the `parties` table until staff confirm.
2. **Case table additions** (check what already exists first; add only what is missing): `first_meeting_on` (P2), `eligibility_checklist` (JSON or normalized, P1), `source_intake_id`, optional `blotter_series`, `blotter_page`, `blotter_entry`, and a reference to the blotter record. Keep the existing `source_report_id`.
3. **Summons:** confirm and document `sequence_no`, the summons number (format unconfirmed, see C5), stage (mediation or conciliation), issued_by/signatory reference (Barangay Captain), date issued, hearing date and time (and venue), `served_by` (who delivered it), service result enum (received, refused, not_found; keep `other` only if it already exists and label it "not in the Secretary's list"), served_on, remarks. Also document party fields per C8 (including non-resident home barangay/city and reason for being in the barangay) and the case-type list per C10.
4. **Hearings:** confirm and document attendance per party (`appeared` or `failed_to_appear`), outcome, presiding officer (Punong Barangay or Pangkat), minutes note, and the counterclaim-bar note field (proposed, free text).
5. **Parties:** confirm the fields that match what the Secretary said the barangay collects (Q16): full name, alias, age or birth date, contact, complete address, residency verification.
6. **Reason codes:** see Step 11 (add `converted_to_case`).
7. Add an ERD or relationship note (text is fine) showing: intake to case, case to blotter entry, case to summons to hearings, case to parties, report to case link.
8. **Guest and anonymous community reports (D7, P11), Planned:** check what the reports table already has, then add only what is missing: nullable reporter link, `is_anonymous`, optional reporter name and contact, and an optional hashed `tracking_code`. No IP or device identifier is stored.
9. **Settings (Planned):** hotline numbers for the guidance screen (P9), and the optional reminder switches (P10), as `setting()` keys. Also the Pangkat constitution date and members if they do not exist yet (P7).

### Step 6. `API.md` and routes

1. Document **planned** routes (mark Planned, with auth/guard and permission slug):
   - Resident: show the entry screen (3 buttons), show/submit the complaint form, and the acknowledgement page. Respect existing resident-route conventions (Turbo Drive disabled, `@csrf`, 375px mobile first).
   - Staff: list/view pending intakes (Pending intake tab), "Docket this" (prefill WS3 intake), mark as redirected/rejected with reason, record first-meeting date, update eligibility checklist, summons and hearing actions (document the existing ones if present), upload signed scan (Planned, optional).
   - Printables: blotter entry/extract, complaint form, summons (mediation and conciliation variants), notice of hearing, amicable settlement, CFA.
2. Use existing permission slugs (for example `cases.create`) wherever they fit. If a new slug is needed, **propose** it in the doc as Planned and note that the 20-slug count would change. Do not change the count in the docs unless you also flag it in the report.
3. Document rate limiting and spam protection for the public complaint form (the form is public and creates records about a named third party) and for the guest community problem form (P11). Mark as Planned.
3a. Document the guest community problem route (no login, optional anonymous mode) and an optional tracking-code status page that shows report status only. Mark as Planned.
4. Do not edit the routes snapshot (Step 10).

### Step 7. `ARCHITECTURE.md`

1. Show the two modules and the bridge (conversion and, Planned, complaint intake) in the architecture overview or diagram text.
2. Add the new service responsibilities as **Planned**, following "thin controllers, logic in `app/Services/`": complaint intake service (create, docket, redirect), eligibility checklist helper, clock start logic (first-meeting date), non-appearance warning and "Ready for next step" indicator evaluation, printable data mappers.
3. State that none of these services transition status automatically; all call the existing case status service only on explicit official action, inside `DB::transaction()`.
4. Record that the Pending intake tab and the rename affect views only.

### Step 8. `UI_UX_SPEC.md`

1. **Resident entry screen wireframe:** three large buttons (Emergency, Community Problem, File a Complaint), plain-language one-line descriptions, 375px safe. Emergency button uses `--red`.
2. **Complaint form wireframe** (follow the spec's page layout patterns A through H and the "single compact card, no stepper" resident-form style the team already chose). Include, in order:
   - Screening step and guidance screen per P9 (draft wording in English and Filipino, hotline numbers from settings, quick-exit button, nothing stored on "yes") (D4a).
   - Complainant section (full name, alias, age or birth date, contact, complete address; anonymity is not offered).
   - Respondent section (full name, alias, address, contact if known).
   - Incident section (5Ws: what happened, who was involved, when, where, why/how).
   - How my rights were violated, and what outcome I want (relief requested).
   - Data privacy consent checkbox (never auto-ticked).
   - Final text: "This is a request only. Please visit the barangay hall to sign and complete your complaint."
3. **Acknowledgement page:** receipt wording only; no status, no respondent details.
4. **Staff "Pending intake" tab** on the Cases Docket: table fixed layout, columns summing to 100%, no horizontal scroll, card stack below 768px; columns such as received date, complainant, respondent, case type, age; one primary action "Docket this" and secondary "Redirect/Reject".
5. **Case detail:** eligibility checklist card, first-meeting date field, blotter reference and case number display, summons/hearing panels showing service result and attendance, non-appearance warning banners (soft, gold) and the "Ready for next step: endorse to Pangkat" indicator using the existing deadline badge states (no summons-count nudge). Keep case status colors from CONVENTIONS.md.
6. **Wrong-door notice** on the community form (non-blocking info banner).
7. **"From report" source badge** on cases created via conversion; add a similar source badge for cases created from a resident intake.
8. Planned rename of page titles and nav labels. List old and new labels in a small table.
9. Keep everything within the existing design tokens, badge colors, and button hierarchy.
10. **Community Problem form (D7, P11):** no login; optional name and contact; a "Report anonymously" checkbox that clears and disables those fields; helper text ("If you leave out your contact details, we cannot tell you the result."); optional tracking code on the confirmation page; the safety banner from P9 next to the wrong-door notice.

### Step 9. Printable templates and signatories

Document the printable set and the signatory for each in the docs that already describe printables and the `SignatoryResolver` (likely `WORKFLOW.md`, `DATABASE.md`, `UI_UX_SPEC.md`). Resolve signatories through `SignatoryResolver` from the signatory matrix; never hardcode names or positions. All are DRAFT-watermarked and listed as pending until Task 6.

For each printable, list: purpose, data fields (the mapping), and signatory/signature lines (all **[PROPOSED]**, confirm against the Secretary's real forms):

| Printable | Key fields | Signatories (proposed) |
| :--- | :--- | :--- |
| **Barangay Blotter entry / certified extract** | series, page no., entry no., date and time entered, facts of the case (5Ws), parties involved | Prepared by the Barangay Secretary; certified by the Punong Barangay |
| **Complaint form** | Barangay Case No., complainant, respondent, how rights were violated, relief requested, date filed | Complainant signature; received by the Barangay Secretary or the Lupon member who took it; acknowledged by the Punong Barangay [PROPOSED, P8] |
| **Summons (mediation)** | `SUM-YYYY-NNNN`, case no., complainant, respondent, nature of complaint, hearing date, time, venue, instruction to appear in person | Barangay Captain (Punong Barangay) [CONFIRMED, Q5] |
| **Notice of hearing / conciliation summons** | same plus Pangkat members and presiding chair | Pangkat Chairman [PROPOSED] |
| **Notice to complainant** (accompanies summons) | case no., hearing schedule | Barangay Captain (Punong Barangay) [PROPOSED, follows the summons signatory] |
| **Amicable settlement** | case no., parties, agreed terms, date | Both parties; attested by the Punong Barangay (mediation stage) or the Pangkat Chairman (conciliation stage) [PROPOSED, P8; the Secretary confirmed what is recorded (C12) but not who attests] |
| **Certificate to File Action (CFA)** | CFA number, case no., parties, nature of dispute, statement that conciliation failed or the respondent did not appear | Pangkat Chairman usually signs once the case is endorsed to the Pangkat [CONFIRMED, Q5]; if no Pangkat was constituted, the Punong Barangay as Lupon Chairman signs, prepared by the Barangay Secretary [PROPOSED, P8] |

Also document, in the case form field list: case type, parties block (with residency status badge), 5Ws block, relief requested, blotter reference, source (walk-in, resident intake, converted report, legacy import), eligibility checklist, first-meeting date, and per-summons and per-hearing records.

### Step 10. `ROUTES_snapshot.json` and `TESTING.md`

1. **Routes snapshot:** do **not** hand-edit. Because the routes are Planned, leave the snapshot as is and add a short note in `API.md` that the snapshot must be regenerated by the project's existing command when the routes are implemented.
2. **`TESTING.md`:** add a **Planned test plan** (no code), grouped:
   - Resident entry screen (three buttons, no blotter button, emergency path unchanged).
   - Complaint intake: creates only a pending intake; no case, no parties, no summons; confidentiality screen; anonymity not offered; consent required; acknowledgement reveals no status or respondent data; rate limiting.
   - Staff docketing: "Docket this" prefill; duplicate warnings; creates one case at `filed` and logs the blotter entry; redirect/reject needs a reason.
   - Governance: nothing auto-transitions (summons counts, non-appearance, deadlines, intake review).
   - Clock: starts from `first_meeting_on`, fallback to `filed_on`; working-day math through `WorkingDays`.
   - Non-appearance: `failed_to_appear` recorded; Pangkat warning on `in_mediation` to `escalated`; complainant non-appearance allows `dismissed` only with a note; at 15 working days with no settlement the case shows "Ready for next step" and nothing changes status by itself; no summons-count threshold exists.
   - Printables: field mapping, signatories via `SignatoryResolver`, DRAFT watermark present, CSV exports leave unstated fields empty and never emit the UI placeholder.
   - Guest and anonymous community reports: no account needed; anonymous mode stores no name, contact, account link, or IP; the complaint form still rejects anonymity; a "yes" on the screening step stores nothing and shows guidance; hotline numbers come from settings; tracking code shows status only.
   - Regression: community report lifecycle unchanged (12 statuses), conversion flow unchanged.
   Do not change the "288 tests" figure; note that the new tests are planned.

### Step 11. Fix documented inconsistencies

1. **Reason codes [DECIDED]:** WORKFLOW.md uses `no_action` with reason `converted_to_case`, but DATABASE.md lists only five reasons (`not_barangay_matter`, `emergency_redirected`, `cannot_resolve`, `invalid_report`, `resident_unreachable`). Add `converted_to_case` to the DATABASE.md list, since WORKFLOW.md already uses it, and make every doc agree. Check the code read-only; if the code lacks it, mark it Planned and flag it as a code fix in the report.
2. **Numbering mismatch (now resolved by C5):** the Secretary confirmed `{MM}-{SEQ:3}-{YEAR}` for case, blotter, and referral numbers, restarting yearly. Make every doc use it. Flag PROJECT.md (`CASE-{YEAR}-{SEQ:5}`) and the UI (`CASE-2026-00001`, `BLOT-2026-00001`) as wrong and list them in the report as code/UI fixes for the team. Compile a small table for the formats that remain unconfirmed: summons (`SUM-YYYY-NNNN`), CFA, and referral (`REF-YYYY-NNNN`).
3. Look for other contradictions you notice while editing (for example the permission slug counts, status counts, deadline units) and list them. Fix only unambiguous typos.

### Step 12. `ACADEMIC_DOCS_UPDATE.md`, `CURRENT-STATE.md`, `TEAM_WORKSTREAMS.md`, `TASKS.md`, `CONVENTIONS.md`

1. **`ACADEMIC_DOCS_UPDATE.md`:** add the thesis wording changes: describe the system as **three layers** (public problem reporting, case management, administration) and as two distinct modules joined by conversion; keep the existing "(problem reports)" wording for the Staff Review Queue. Add the resident complaint intake as a requirement (functional requirement: resident submits complaint request; staff dockets; no auto-docketing), the confidentiality exclusion, the use-case-diagram changes (new use cases: Submit complaint request, Review pending intake, Docket complaint, Issue summons, Record hearing attendance, Handle non-appearance, Submit community problem as guest or anonymously), and non-functional notes (privacy of the respondent, rate limiting). Keep the voice-reporting accuracy-test material intact.
2. **`CURRENT-STATE.md`:** keep it factual about what is built. Add a short "Planned, not yet implemented" subsection listing the items in this prompt. Do not move planned items into the built list.
3. **`TEAM_WORKSTREAMS.md`:** assign the Planned work to existing workstreams without inventing owners: intake form and Pending intake tab and eligibility checklist and first-meeting date to **WS3**; summons service-result, hearing attendance, non-appearance warning, "Ready for next step" indicator, clock start to **WS4**; resident entry screen and complaint form views to the resident-UI owner. Mention new migrations must use the WS3/WS4 timestamp prefixes. Do not add or reassign people unless the file already names them.
4. **`TASKS.md`:** add new pending tasks after Task 7, in the same format (Goal, Scope), for example:
   - **Task 8: Resident Entry Screen (3 buttons), Wrong-Door Notice, Guest/Anonymous Community Reports, and VAWC/BCPC Guidance Screen**
   - **Task 9: Resident Complaint Intake and Staff Pending Intake Tab**
   - **Task 10: Case Pre-Summons Eligibility, First-Meeting Clock Start, and Non-Appearance Handling**
   - **Task 11: Printable Templates and Signatory Mapping (blotter, complaint, summons, notice, settlement, CFA)**
   - **Task 12: Page Rename (Community reports / Cases & Blotter)**
   Also update **Task 6** to add: blotter logbook page (or a filled-in sample), the complaint form, and the question of which blotter document is used at intake. Update **Task 5** only if `ACADEMIC_DOCS_UPDATE.md` changes require it. Keep checkboxes unchecked for new work.
5. **`CONVENTIONS.md`:** add short rules only: (a) the resident complaint intake never creates cases or parties automatically; (b) resident-facing complaint pages never display case status or respondent data; (c) confidential case types are excluded from public forms; (d) new complaint-related migrations use the WS3/WS4 prefixes; (e) nudges and reminders on summons or non-appearance must never change status; (f) anonymous community reports store no identifying data and are never marked `resident_unreachable` for being anonymous; (g) hotline and guidance numbers come from `setting()`, never from views. Do not alter existing rules.

### Step 13. Secretary / Adviser question list

The Secretary's questionnaire already answered many earlier questions (signatories, working days for mediation, non-dispute concerns, number format, what is recorded per summons). Add only what is still unconfirmed, to `PROJECT.md`'s open questions (or the Secretary question list) and to your report. Split it in two groups.

**A. Working answers adopted for now (Section 3). Confirm with the Secretary or Punong Barangay:**
1. Mediation clock start: the first meeting before the Punong Barangay (P2), versus summons service or filing.
2. Conciliation: proposed working days, 15 plus an extension of up to 15 (the Secretary said "15 to 30"). Is it working days, and who decides the extension?
3. Respondent does not appear: record `failed_to_appear` and the service result, then endorse to the Pangkat rather than issue a CFA; the counterclaim bar (P3). What does the barangay actually record and do within the 15 working days? Complainant does not appear: staff may dismiss with a note.
4. Logbook: does it use series, page, and entry numbers (P4)? Can we see a filled-in page, and is the logbook entry the intake record with the "Barangay Blotter" form as a certified extract (P5)?
5. Do blotter and case numbers share one sequence (P4a)? Do summons, CFA, and referral numbers use the same `{MM}-{SEQ:3}-{YEAR}`?
6. Pre-summons eligibility items (same barangay, juridical entities, police custody; P1).
7. The full sequence: complaint, summons, mediation, endorsement to the Pangkat after 15 working days, Pangkat constitution, conciliation, then CFA (P7). Q18 did not list the Pangkat step.
8. Signatories (P8): notice of hearing, amicable settlement, blotter extract, who receives the complaint form, and who signs the CFA with and without a Pangkat.
9. VAWC and BCPC guidance wording in English and Filipino, and the hotline numbers to show, including the barangay VAW Desk and the city CSWDO (P9).
10. Reminders (P10): is the 3-day follow-up only for referral letters? Does the Secretary want any of the optional reminders (settlement compliance date, Pangkat convening deadline, yearly report)?
11. Adviser: should residents start a complaint online as a pending request only (P6), or should disputes stay walk-in only?

**B. Decided by the team, to tell the Secretary (not questions):**
12. Community problem reports may be made without an account and anonymously (D7). She answered "Not sure" in Q14. Ask her only whether the barangay has any objection, and whether tracking codes are wanted (P11).

## 5. Final verification (before reporting)

1. Search all edited docs for the strings "four buttons", "File a Blotter", "auto-docket", "automatically transition", and make sure nothing contradicts D2 to D6 or the governance rule. Also search for "nudge", "summons-count", `CASE-{YEAR}-{SEQ:5}`, `CASE-2026-00001`, and `BLOT-2026-00001`: every remaining hit must be either the day-based indicator, a flagged mismatch, or removed.
2. Confirm the community report flow description is unchanged in substance.
3. Confirm every new item is labeled Decided, Proposed-pending, Planned, or Open, and that nothing Planned is described as implemented.
4. Confirm the counts (12 report statuses, 8 case statuses, 20 permission slugs, 288 tests) are unchanged unless you flagged a change.
5. Check that all internal links, anchors, and tables render (no broken Markdown).
6. Make sure no application code, migration, seeder, test, lock file, or build file was modified.
7. Confirm no doc still says an account is required to report a community problem, no doc offers anonymity on the complaint form, and no view hardcodes a hotline number.

## 6. Output: change report

Reply with:
1. A file-by-file summary of what you changed (one or two lines each).
2. A list of contradictions found and how you handled them (reason codes, numbering table, anything else).
3. Every [PROPOSED] item and every [OPEN] question, grouped for the Secretary and for the Adviser.
4. Anything in this prompt you could not apply because the docs lacked a matching section, and where you put it instead.
5. Confirmation that no code or generated files were touched.
