---
name: barangay-reporting-system
description: Guidance for building, documenting, and explaining the Barangay Reporting System, a web app where residents report community problems (garbage, broken streetlights, flooding, noise, road damage, safety concerns) to their barangay officials and track the status of each report. Make sure to use this skill whenever the user mentions the Barangay Reporting System, barangay reports, resident complaints, incident reporting for a barangay, or asks for code, database design, UI, thesis or project documents, or test cases for this project, even if they do not say the full project name.
---

# Barangay Reporting System

Project repository: https://github.com/GraziellaMS21/Barangay-Reporting-System

The repository is the source of truth. The data model, roles, and stack described below are defaults only. If the user shares code, a schema, or a file from the repository, follow that instead and do not rewrite or rename what already exists.

## Token and effort control

Use as little output as the task needs, because the user wants to avoid wasting tokens. Follow these rules in every response about this project.

| Rule | What to do |
|---|---|
| Answer first | Give the answer or the code right away, with no long introduction, no restating the question, and no closing summary |
| Stay in scope | Do only what was asked. Do not add extra features, extra files, or "future improvements" unless the user requests them |
| Edit, do not rewrite | When changing existing code, show only the changed function or lines with the file name, not the whole file |
| Read only what is needed | Open only the repository files that the task depends on. Do not browse the whole repository or re-fetch a file already seen in the conversation |
| Do not repeat | Do not paste back code or text the user already has. Refer to it by file name or function name |
| Ask once, only if blocked | If a missing detail would make the answer wrong, ask one short question. Otherwise state a one-line assumption and continue |
| Keep depth proportional | Short question gets a short answer. Give long documents or full modules only when the user clearly asks for them |
| No unnecessary tools | Do not run searches, create files, or generate diagrams unless the task needs them. Prefer a code block in chat for anything under about 20 lines |

## Purpose of the system

The system gives residents a simple way to report problems in their barangay and lets barangay officials receive, review, assign, and resolve those reports. It replaces the usual process of walking to the barangay hall, relying on word of mouth, or posting in group chats where reports get lost. The core idea is that every report has a clear owner, a clear status, and a visible history.

Keep every suggestion practical and tied to a real, observable problem. Prefer a working, simple solution over a complicated one. Assume the system may run on a local network or low-cost hosting, with users who may have slow internet and basic phones.

## Users and roles

| Role | What they can do |
|---|---|
| Resident | Register, log in, submit a report with description, category, location, and optional photo, and view the status and history of their own reports |
| Barangay Official (staff) | View all reports, filter by category, status, or date, update the status, assign a report to a person or committee, and add remarks |
| Barangay Captain / Admin | Everything staff can do, plus manage user accounts, manage categories, and view summary reports |

Always apply role-based access. A resident must never see or edit another resident's report unless the report is marked public by the system rules.

## Report lifecycle

Every report moves through these statuses in order, and the system should record who changed the status and when.

| Status | Meaning |
|---|---|
| Submitted | The resident sent the report and nobody has reviewed it yet |
| Under Review | An official has opened the report and is checking whether it is valid |
| In Progress | The report is accepted and someone is assigned to act on it |
| Resolved | The problem was fixed and the official added a closing remark |
| Rejected | The report was invalid, a duplicate, or outside barangay authority, with a required reason |

Do not allow skipping from Submitted directly to Resolved without a remark. A rejected report must always show a reason to the resident.

## Suggested data model

Use these core tables unless the user's existing schema says otherwise, and keep names consistent across code and documents.

| Table | Main fields |
|---|---|
| users | id, full_name, email, password_hash, role, purok_or_zone, contact_number, created_at |
| categories | id, name, description |
| reports | id, resident_id, category_id, title, description, location_text, photo_path, status, priority, created_at, updated_at |
| report_updates | id, report_id, updated_by, old_status, new_status, remarks, created_at |
| assignments | id, report_id, assigned_to, assigned_by, assigned_at |

Store passwords only as hashes, never as plain text. Use parameterized queries or the framework's ORM to prevent SQL injection. Keep the status history in `report_updates` so nothing is overwritten.

## Core features to include

The minimum working version should cover registration and login, report submission with photo upload, a resident dashboard showing their reports and statuses, an official dashboard with filters, status updates with remarks, and a simple summary page showing counts per category and per status. Extra features such as SMS or email notifications, a map pin for location, and printable monthly reports are optional and should be treated as future improvements unless the user asks for them.

## How to write code for this project

Detect the stack from the files the user shares or from the repository's package or configuration files, and do not ask about it if it is already clear. If the stack is unknown, default to a simple, common choice that a BSCS student can run locally, such as a web app with a relational database like MySQL or PostgreSQL. Write complete, working code rather than outlines, and include the file name and location for each piece. Validate all inputs on both the client and the server, handle errors with clear messages, and check the user's role before every protected action. Keep functions small and name things after the domain, for example `submitReport`, `updateReportStatus`, and `getReportsByResident`.

## How to write documents for this project

When the user asks for a proposal, thesis chapter, or project documentation, write the full draft directly instead of asking for ideas first. Use formal but simple English in paragraph form, and keep each section specific to the barangay problem. A typical proposal order is Background of the Study, Statement of the Problem, Objectives, Scope and Limitations, Significance of the Study, Proposed System, and Methodology. Describe the real problem first: residents do not know where or how to report, reports are not tracked, and officials have no organized record. Then show how the system answers each problem.

## How to explain things

When the user asks for an explanation, keep it short and in prose, and use a table when comparing items or listing parts. Avoid bullet points and dashes in explanations. Define technical terms briefly the first time they appear.

## Testing guidance

Suggest test cases that follow real use. Examples include a resident submitting a report with and without a photo, a resident trying to open another resident's report, an official moving a report through every status, a rejection without a reason being blocked, and a duplicate submission within a short time. Present test cases in a table with columns for test case, steps, expected result, and actual result.

## Boundaries and privacy

Reports can contain names, addresses, and photos, so treat them as personal data. Limit what is shown publicly, never expose resident contact details to other residents, and mention the Data Privacy Act of 2012 (Republic Act 10173) when the user writes about privacy or consent in documents. Do not invent barangay-specific rules, official forms, or legal procedures. If a rule depends on the actual barangay, write a clearly marked placeholder for the user to fill in.
