# UI/UX Specification: Barangay Reporting & Case Management System

> **Application**: Barangay-Reporting-System-1 (Barangay Calarian, Zamboanga City)  
> **Target Stack**: Laravel 13, Blade Templates, Tailwind CSS v4, FontAwesome Free 7  
> **Status**: Authoritative UI/UX Design Specification for Workstreams WS1 through WS5  
> **Core Mandate**: "The AI suggests, the rules route, the officials decide." Consistent layout, predictable button placement, and accessible tabular density across all modules.

---

## 1. Purpose & How to Use This Spec

This document is the single layout, visual, and interaction standard for the 5-person development team. Because multiple engineers build features in parallel across discrete workstreams, all pages must look and behave as if crafted by a single product designer.

### Rules for Engineers & Coding Agents
1. **Layout Integrity**: Follow the designated Pattern (A through H) for every screen. Never invent arbitrary card wrappers, custom button placements, or inconsistent table column splits.
2. **Button Hierarchy**: Primary action buttons are **always the rightmost** control. Secondary, outline, and ghost buttons sit to the left of the primary action. Destructive actions sit isolated or in a "More" dropdown.
3. **Column Width Sums**: Table column widths are calibrated for visual weight and **must total exactly 100%**.
4. **Documentation Only**: This document guides front-end view authoring; do not alter existing resident-facing pages (`resources/views/resident/*`), which are preserved under the "existing, keep" policy.
5. **Precedence**: This specification governs visual representation. Status transition logic, permission checks, and database state constraints remain governed by [`docs/WORKFLOW.md`](file:///c:/githubFiles/Barangay-Reporting-System-1/docs/WORKFLOW.md), [`docs/API.md`](file:///c:/githubFiles/Barangay-Reporting-System-1/docs/API.md), and [`docs/CONVENTIONS.md`](file:///c:/githubFiles/Barangay-Reporting-System-1/docs/CONVENTIONS.md).

---

## 2. Global Design Rules

### 2.1 Design Tokens
Adhere strictly to the design tokens. Do not introduce extraneous brand colors.

| Token | Hex / Value | Purpose & Application |
| :--- | :--- | :--- |
| `--ink` | `#0F172A` | Primary dark text, sidebar background, high-contrast borders. |
| `--blue` | `#1D4ED8` | Institutional primary accent, links, active navigation, focus rings. |
| `--gold` | `#D97706` | Attention, pending confirmation, review accent, SLA warning. |
| `--red` | `#B91C1C` | Emergency alert, destructive danger actions, overdue indicators. |
| `--green` | `#15803D` | Verified resolution, within-barangay authority badge, active status. |
| `--line` | `#E2E8F0` | Subtle borders, table dividers, card boundaries (`slate-200`). |
| Page Background | `#F8FAFC` | Base canvas background for all admin, agency, and wizard pages (`slate-50`). |
| Card Background | `#FFFFFF` | White card surfaces, modals, popovers, dropdown menus. |
| Muted Text | `#64748B` | Secondary labels, captions, metadata, breadcrumb links (`slate-500`). |
| Table Header Bg | `#EEF2FF` | Indigo-50 / Slate-100 tinted header row for high readability. |
| Row Hover Bg | `#F1F5F9` | Hover highlight state for interactive table rows (`slate-100`). |

### 2.2 Spacing Scale
Use **only** the canonical spacing steps: **4, 8, 12, 16, 24, 32, 48 px**.
- **Section Gap**: 24 px (`gap-6` or `space-y-6`).
- **Card Padding**: 24 px desktop (`p-6`), 16 px mobile (`p-4`).
- **Field Gap**: 16 px (`gap-4` or `space-y-4`).
- **Button Gap**: 8 px (`gap-2`).
- **Table Cell Padding**: 12 px vertical, 16 px horizontal (`py-3 px-4`).

### 2.3 Typography
Rendered with system sans-serif font stack (`ui-sans-serif, system-ui, sans-serif, "Inter"`).
- **Page Title**: 24 px font size / 32 px line height, semibold (`text-2xl font-semibold leading-8`).
- **Section Title**: 18 px font size / 28 px line height, semibold (`text-lg font-semibold leading-7`).
- **Body & Table Text**: 14 px font size / 22 px line height, regular (`text-sm leading-relaxed`).
- **Helper & Error Text**: 12 px font size / 16 px line height, regular (`text-xs leading-4`).
- **Badges**: 12 px font size, medium weight (`text-xs font-medium`).
- **Absolute Minimum**: Never render UI text below 12 px.

### 2.4 Shapes, Borders & Elevation
- **Card Radius**: 8 px (`rounded-lg`).
- **Input & Button Radius**: 8 px (`rounded-lg`).
- **Badge Radius**: Full pill (`rounded-full`).
- **Card Elevation**: 1 px border `--line` (`border border-slate-200`) with subtle drop shadow `0 1px 2px rgba(15,23,42,.06)` (`shadow-sm`).

### 2.5 Control Sizes & Touch Targets
- **Input & Select Height**: 40 px (`h-10 px-3 py-2`).
- **Button Height**: Standard 40 px (`h-10 px-4 py-2`); Small 32 px (`h-8 px-3 py-1 text-xs`).
- **Touch Target Floor**: Minimum 44 px tap target dimension on mobile viewports (`min-h-[44px]`).

### 2.6 Responsive Breakpoints
- **Mobile**: `< 640 px` (`sm`).
- **Tablet**: `640 px` to `1023 px` (`md`).
- **Desktop**: `1024 px` to `1279 px` (`lg`).
- **Wide**: `1280 px and up` (`xl` / `2xl`).

### 2.7 Application Shells

#### Authenticated Shells (Admin & Agency)
- **Hover-Expand Icon Rail Overlay (Desktop >= 1024 px)**:
  - Base state: Centered icon rail with permanent $4.5\text{rem}$ ($72\text{px}$) width (`w-[4.5rem]`). The main content column is offset by `margin-left: 4.5rem` (`w-full min-w-0`), preventing layout shifts.
  - Hover / focus-within state: Expands smoothly to $16\text{rem}$ ($256\text{px}$) as an overlay (`position: fixed; left: 0; top: 0; bottom: 0; z-index: 30;`) with $120\text{ms}$ expand delay and $200\text{ms}$ collapse delay. The content does not reflow or push sideways.
  - Navigation container has dedicated thin scrolling (`scrollbar-width: thin; overflow-y: auto;`) ensuring bottom items (e.g. Operations and Account) are never clipped.
  - Theme styling: Navy (`--ink` `#0f172a`) for Admin/Staff and Slate (`#0f172a` / `#1e293b`) for the Agency Partner Portal.
- **Top Header**: Height 64 px (`h-16`), sticky position (`sticky top-0 z-20`), white background with bottom border `--line`. Layout (left to right):
  1. Mobile menu toggle button (`lg:hidden`).
  2. Page heading & subtitle, or global search input.
  3. Fiscal year switcher dropdown (`Viewing: FY 2026`).
  4. Pending confirmation badge link (`N items pending confirmation`, gold pill, admin-only).
  5. User profile / logout account menu.
- **Content Area**: Canvas `#F8FAFC`, full width with `min-w-0`, zero double padding (`w-full min-w-0` wrapping `p-4 md:p-6`).
- **Mobile Drawer (< 1024 px)**: Sidebar collapses into off-canvas slide-over drawer triggered by the hamburger menu button with a 50% opacity backdrop overlay (`.staff-drawer-overlay`).

#### Zero Horizontal Scroll & Responsive Table Cards
- **Tabular Layout**: Every table in admin, staff, and agency views is `table-fixed w-full` with explicit column width percentages summing to exactly $100\%$.
- **No Overflow Hiding**: Viewport boundaries and card wrappers use `overflow-hidden` (never masking horizontal overflow on `body`).
- **Mobile Stacked Cards (< 768 px)**: Below $768\text{px}$, tables transform into vertically stacked card lists using `.responsive-card-table`. Each cell renders as a key-value row with `td::before { content: attr(data-label); }`, completely eliminating horizontal scrolling down to $375\text{px}$ viewports while maintaining table semantic DOM integrity for automated test suites.

#### Turbo Drive Navigation (Authenticated Scoping)
- **Scoped Drive**: `Turbo.session.drive = false` globally. Only authenticated layouts opt in via `data-turbo="true"` on `<body>`. Public resident pages, login/logout, setup wizard, and printable documents are strictly excluded.
- **Persistent Sidebar**: The navigation rail is marked `id="staff-sidebar" data-turbo-permanent` so only main content swaps on link clicks. Active navigation links update client-side on `turbo:load` and `turbo:render`.
- **Cache & Script Controls**: Layouts declare `<meta name="turbo-cache-control" content="no-cache">` and `<meta name="turbo-prefetch" content="false">`.
- **Lifecycle Cleanup**: Chart.js charts and Leaflet maps are explicitly destroyed/removed on `turbo:before-render` and `turbo:before-cache`.
- **Download & Form Exclusions**: File exports (CSV, Excel, backups) and logout forms carry `data-turbo="false"`.

#### Print Layout
- Page format: Standard Letter size (8.5" x 11").
- Margins: 0.75 in left/right/bottom, 1.0 in top margin.
- Typography: Arial 11 pt body text (line height 1.5), 9.5 pt table cells.

### 2.8 Accessibility Mandates (WCAG 2.1 AA)
- **Contrast**: Minimum 4.5:1 text-to-background contrast ratio for normal prose; 3:1 for large headers.
- **Focus Rings**: Unconditional 2 px royal blue focus ring with 2 px offset (`focus:ring-2 focus:ring-blue-600 focus:ring-offset-2`).
- **Multi-channel Status**: Never rely solely on color. Pair color badges with text labels and FontAwesome iconography.
- **Keyboard Navigation**: Modals trap focus, cycle through focusable inputs, and dismiss cleanly on `Escape`.
- **Form Labels**: Every input, select, and textarea must carry an explicitly linked `<label for="...">`.
- **Screen Readers**: Asynchronous alerts and flash toasts must carry `role="status"` or `role="alert"` and `aria-live="polite"`.

---

## 3. Layout Patterns (A through H)

### Pattern A: List Page
Used for: Case docket, parties, personnel, users, referrals, hearings, and audit logs.

```text
+----------------------------------------------------------------------+
| Breadcrumbs: Home / Cases                                            |
| Cases Docket                                      [Export] [+ New Case]
| Manage and track community complaints and Katarungang Pambarangay    |
+----------------------------------------------------------------------+
| Filter Bar Card                                                      |
| [Search case, party, title...   ] [Type: All v] [Status: All v] [Clr]|
+----------------------------------------------------------------------+
| Table Card                                                           |
| Case No.    Filed    Type     Parties    Status   Summons   Actions  |
| -------------------------------------------------------------------- |
| CASE-0001   Nov 12   Boundary Dela Cruz  [Medtn]  [ 1 / 3 ] [V] [E] [:]
| CASE-0002   Nov 14   Noise    Ramos      [Filed]  [ 0 / 3 ] [V] [E] [:]
+----------------------------------------------------------------------+
| Pagination: Showing 1 to 20 of 142 records            [< Prev] [Next >]|
+----------------------------------------------------------------------+
```
- **Margins & Rhythm**: 24 px between title row, filter card, and table card.
- **Button Placement**: Header holds secondary export button on the left, **PRIMARY action button rightmost**.
- **Table Card**: Header row sticky (`bg-indigo-50/70`), row height 48 px, subtle hover highlight (`hover:bg-slate-50`), zero zebra striping.
- **Row Actions**: Rightmost column holds at most 2 direct icon buttons (View, Edit) plus a `...` More Actions dropdown. Destructive items sit last in red.

---

### Pattern B: Form Page
Used for: Case creation, party editing, personnel setup, response template editing, and escalation certification.

```text
+----------------------------------------------------------------------+
| Breadcrumbs: Home / Cases / New                                      |
| File New Barangay Case                                               |
| Docket a direct community dispute or escalate an intake report       |
+----------------------------------------------------------------------+
| Error Summary Banner (Shown only on validation failure)              |
| ! Please correct the 2 errors highlighted below.                     |
+----------------------------------------------------------------------+
| Section Card 1: Case Particulars                                     |
| Case Type *                   Incident Date                          |
| [ Boundary Dispute        v ] [ YYYY-MM-DD                         ] |
|                                                                      |
| Complaint Narrative *                                                |
| [ Detailed narrative statement...                                  ] |
+----------------------------------------------------------------------+
| Section Card 2: Involved Parties                                     |
| ...                                                                  |
+----------------------------------------------------------------------+
| [Sticky Footer Bar: Max-w-3xl]                                       |
| [Delete Case Draft]                        [Cancel] [ Save & Proceed ]
+----------------------------------------------------------------------+
```
- **Width**: Single centered column, max width 720 px (`max-w-3xl mx-auto`).
- **Internal Grids**: 2-column grid (`gap-4`) used strictly for short companion inputs (dates, dropdowns, telephone numbers) at `>= 1024 px`.
- **Field Anatomy**: Label (14 px semibold) with red asterisk `*` -> Input control (40 px height) -> Helper text (12 px muted) / Inline error text (12 px red).
- **Sticky Footer Bar**: Pinned to the viewport bottom (`sticky bottom-0 bg-white border-t border-slate-200 p-4 shadow-lg`). Destructive text button left-aligned; **Cancel (ghost) and PRIMARY Save rightmost**.

---

### Pattern C: Detail Page with Tabs
Used for: Barangay Case show page, Agency detail view, External Referral management.

```text
+----------------------------------------------------------------------+
| Breadcrumbs: Home / Cases / 11-002-2026                              |
| 11-002-2026: Ramos vs. Bautista                [Print] [Issue Summons]
| FY 2026 · Boundary Dispute · Filed Nov 12, 2026 · Status: [In Mediation]|
+----------------------------------------------------------------------+
| [Overview]   [Parties (2)]   [Summons (1)]   [Hearings (1)]   [Notes]|
+---------------------------------------------------+------------------+
| Main Column (2/3 width)                           | Side Column (1/3)|
| Section Card: Case Information                    | Deadline Panel   |
| Narrative, dates, assigned mediator...            | [=== Day 8/15 ==]|
|                                                   | 7 days remain    |
| Section Card: Recent Proceedings                  | Confidentiality  |
| Hearing schedules and service confirmations...    | [ Public Case  ] |
+---------------------------------------------------+------------------+
```
- **Header Card**: Case number and title left, status badge inline, metadata row below; action button cluster right-aligned (**PRIMARY rightmost**, max 3 visible buttons, additional actions in a "More" dropdown).
- **Tab Navigation**: Underline style border tab strip (`border-b border-slate-200`), active tab highlighted with a 2 px `--blue` bottom border and bold ink text.
- **Split Layout (>= 1024 px)**: Main content column 66% width (`lg:col-span-2`), right summary / metadata column 33% width (`lg:col-span-1`). Stacks into a single column on `< 1024 px`.

---

### Pattern D: Wizard / Centered Card
Used for: Onboarding Setup Wizard (`/setup`), Agency Registration (`/agency/register`), Forced Password Reset (`/account/password`).

```text
+----------------------------------------------------------------------+
|                               [ SEAL ]                               |
|                       BARANGAY CALARIAN SYSTEM                       |
+----------------------------------------------------------------------+
|                        +--------------------+                        |
|                        | Card: Max Width 640|                        |
|                        | Step 3 of 5        |                        |
|                        | [=========>------] |                        |
|                        | Numbering Schemes  |                        |
|                        | Configure masks... |                        |
|                        |                    |                        |
|                        | [ Input Fields... ]|                        |
|                        |                    |                        |
|                        | [< Back]    [Next>]|                        |
|                        +--------------------+                        |
+----------------------------------------------------------------------+
```
- **Canvas**: Clean `#F8FAFC` slate background, minimal municipal header.
- **Card**: Centered container, width 640 px (`max-w-xl`, 560 px for registration), 32 px interior padding (`p-8`).
- **Header**: Step count label ("Step 3 of 5"), visual progress bar, bold step heading, one-line explanation.
- **Card Footer**: `< Back` secondary button left-aligned (hidden on Step 1); **PRIMARY `Save & Continue` rightmost** (or `Finish Setup` on the terminal step).

---

### Pattern E: Settings Page
Used for: General Barangay Settings, Numbering Schemes, Fiscal Year Configuration.

```text
+----------------------------------------------------------------------+
| Breadcrumbs: Home / Settings / Numbering                             |
| Official Numbering Schemes                                           |
| Configure sequence counters and mask tokens across document types    |
+----------------------------------------------------------------------+
| Card 1: Referral Tracking Number Scheme                              |
| Left 1/3: Description & Rules    | Right 2/3: Form Fields            |
| Controls format for Mode B       | Format Mask *                     |
| endorsements to outside agencies | [ REF-{YEAR}-{SEQ:4}            ] |
|                                  | Preview: REF-2026-0001            |
|                                  |               [Save Numbering Box]|
+----------------------------------------------------------------------+
| Card 2: Barangay Dispute Case Number Scheme                          |
| ...                              | ...                               |
+----------------------------------------------------------------------+
```
- **Stacked Cards**: Grouped settings partitioned into dedicated cards separated by 24 px.
- **2-Column Split**: On `>= 1024 px`, left 33% holds descriptive title and explanation; right 66% holds form controls and live preview badges.
- **Independent Action**: Each card carries its own **Save button right-aligned** at the bottom of the right pane.

---

### Pattern F: Modal Dialog
Used for: Voiding summons, recording hearing outcomes, closing fiscal years, deactivating accounts, confirming escalations.

```text
+----------------------------------------------------------------------+
| Semi-transparent Backdrop (50% --ink opacity)                        |
|                  +--------------------------------+                  |
|                  | Modal Header: Title        [X] |                  |
|                  |--------------------------------|                  |
|                  | Body: Explanatory text,        |                  |
|                  | mandatory note textarea,       |                  |
|                  | live character count (0/1000)  |                  |
|                  |--------------------------------|                  |
|                  | Footer:    [Cancel] [ Confirm ]|                  |
|                  +--------------------------------+                  |
+----------------------------------------------------------------------+
```
- **Dimensions**: Small 480 px (`max-w-md`) for simple confirmations; Medium 640 px (`max-w-xl`) for inputs and data summaries.
- **Header**: Modal title (18 px semibold), close button `X` top-right.
- **Body**: Description of consequence, required reason/note textarea with character counter.
- **Footer**: `Cancel` ghost button on the left, **PRIMARY Confirm button rightmost** (or `DANGER` red button for destructive operations).
- **Focus Trap**: Initial focus moves to the first interactive field; `Esc` key cancels.

---

### Pattern G: Dashboard Cards & KPI Widgets
Used for: Secretary Dashboard, Administrator Dashboard.

```text
+----------------------------------------------------------------------+
| Executive Dashboard                                    Viewing: FY 2026
| Barangay Calarian conciliation overview and case throughput metrics  |
+----------------------------------------------------------------------+
| KPI Grid (3 columns desktop, 2 tablet, 1 mobile)                     |
| +------------------+ +------------------+ +------------------+       |
| | Active Cases     | | Hearings Today   | | Escalation Alert |       |
| | 24               | | 3                | | 2 Eligible       |       |
| | [View Docket ->] | | [View Schedule->]| | [Review CFA ->]  |       |
| +------------------+ +------------------+ +------------------+       |
+----------------------------------------------------------------------+
| Split Widget Row (2 equal columns)                                   |
| [ Today's Conciliation Hearings  ] [ Overdue External Referrals    ] |
| - 10:00 AM: Case 0002 Session Hall| - REF-2026-0004 CSWDO (3d ovd) |
| - 02:00 PM: Case 0005 PB Office   | - REF-2026-0007 CEO   (1d ovd) |
+----------------------------------------------------------------------+
```
- **KPI Metrics**: 3-column grid (`grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4`).
- **Card Structure**: Metric title (14 px muted text) -> Big Number (32 px semibold ink) -> "View all ->" drill-down link bottom-left.
- **Content Widgets**: 2-column layout below KPI cards for actionable schedules and overdue watchlists.

---

### Pattern H: Printable Legal Document
Used for: Official Summons, Case Endorsements, Referral Letters, Amicable Settlement Agreements, Compliance Reports.

```text
+----------------------------------------------------------------------+
| [ SEAL ]                 Republic of the Philippines                 |
|                            City of Zamboanga                         |
|                            BARANGAY CALARIAN                         |
|                   Office of the Lupong Tagapamayapa                  |
| -------------------------------------------------------------------- |
|                                                                      |
|                                                     Date: 2026-11-22 |
|                                               Ref No: SUM-2026-00001 |
|                           OFFICIAL SUMMONS                           |
|                                                                      |
| To: PEDRO S. PENDUKO                                                 |
| Address: 126 Sampaguita St., Purok 1, Calarian, Zamboanga City       |
|                                                                      |
| You are hereby summoned to appear before the Punong Barangay...      |
|                                                                      |
| +------------------------------------------------------------------+ |
| | Summary Data Table (0.5 pt slate border, #EEF2FF header)         | |
| +------------------------------------------------------------------+ |
|                                                                      |
| Prepared by:                  Attested by:              Approved by: |
|                                                                      |
| _____________________    _____________________    __________________ |
| MARIA CLARA DELOS SANTOS HON. EDUARDO V. ALVAREZ                     |
| Barangay Secretary       Punong Barangay                             |
|                                                                      |
|                                                         Page 1 of 1  |
+----------------------------------------------------------------------+
```
- **Letterhead**: Official seal left (80 px height), centered municipal hierarchy headers, thin divider rule.
- **Document Title**: Centered, 14 pt bold uppercase, 24 px below letterhead rule.
- **Reference Block**: Date and unique number right-aligned.
- **Typography & Spacing**: Arial 11 pt, 1.5 line height, 8 pt paragraph spacing.
- **Signatory Block**: Up to 3 balanced columns (`Prepared by`, `Noted by`, `Approved by`) with 40 px blank signature clearance, horizontal rule, bold official name, and position title. Blank rules render when signatories are unconfirmed. *(Note: in current code, `reports-center/print.blade.php:137,142` and `show.blade.php:269,274` hardcode "Barangay Secretary" and "Punong Barangay" in `@empty` fallbacks; remediation to standard blank rules is tracked in TASKS.md Task 14).*
- **Draft Watermark**: Semi-transparent 96 pt diagonal watermark (`DRAFT`) renders across print canvas while template or signatories remain unconfirmed.

---

### Pattern I: Queue Filter Bar (`<x-ui.queue-filters>`)
- **Container**: Single white elevated card (`bg-white border border-slate-200 rounded-2xl shadow-sm p-4 sm:p-5 space-y-4`). Eliminates legacy dark navy container and floating emergency pills.
- **Row 1 (Lifecycle Status Tabs)**: Mutually exclusive status tabs (`All`, `Needs review`, `In progress`, `Waiting on others`, `Done`).
  - Active tab: 2 px dark bottom border (`border-slate-900 font-bold text-slate-900`) and solid dark count pill (`bg-slate-900 text-white rounded-full px-2 py-0.5`).
  - Inactive tabs: Transparent border with muted count pill (`bg-slate-100 text-slate-600 rounded-full px-2 py-0.5`).
  - Responsive: Renders as horizontal underline tab strip on `lg` (>= 1024 px); collapses to a single select dropdown (`Status: All (19)`) below 1024 px.
- **Row 2 (Search & Secondary Popover)**: Full-width search bar (`flex-1 min-w-[200px]`), secondary "Filters" drawer button with active count badge, and a single dark primary "Apply" button (`bg-slate-900 text-white`).
- **Row 3 (Toggles & Active Filter Chips)**:
  - Independent quick-filter toggle chips: `My cases`, `Overdue`, and `Emergency` (neutral when inactive and count 0; red when active or count > 0).
  - Removable active secondary filter chips with `✕` dismiss icons (Purok, Category, Status, Assignee, Fiscal Year, Search term).
  - "Clear all" link resets all secondary filters and chips back to the active stage.
- **Navigation Architecture**: "My Cases" is an independent filter chip on the queue, **not a sidebar item**. The sidebar retains only the centralized "Report Queue" (`admin.reports.*`), while `/admin/my-cases` issues a 302 redirect to `/admin/reports?my_cases=1`.
- **Scoped Counts Mandate**: When `my_cases` is active, all tab counts in Row 1 dynamically scope to the current authenticated user's assigned cases so tab counts match the exact number of rows displayed.

---

### Pattern J: Compact Queue Rows (`.data-table-queue`)
- **Container & Layout**: Table layout is `table-fixed` across full width (`w-full`), never wrapped in horizontal scrollbars (`overflow-x-auto` forbidden).
- **Target Row Height**: ~50 px row height on `lg`+ viewports (down from legacy ~120 px), allowing 8-10 reports to be visible above the fold on a 1366x768 screen.
- **Two-Line Data Density**:
  - **Column 1: Select** (`2.5rem` / `w-10`): Centered checkbox; header includes select-all toggle with indeterminate support.
  - **Column 2: Report** (flexible width):
    - *Line 1*: Monospace tracking code (`text-blue-700 font-mono font-bold text-xs`) with stretched link (`after:absolute after:inset-0 after:z-10`), optional emergency warning icon, priority badge (only for `Critical` in red and `High` in amber), reopened count badge, screen-reader text (`<span class="sr-only">Priority: ...</span>`), and one-line truncated description (`truncate`).
    - *Line 2*: Muted metadata (`text-[11px] text-slate-500`): `Category · Purok · short relative age` (e.g. `Road damage · Purok 1 · 3h ago`). Full ISO timestamp provided in tooltip `title`.
  - **Column 3: Status** (`10.5rem`): Single clean status badge without icons (`:show-icon="false"`), preventing label truncation. Column header is sortable and includes "?" legend modal toggle. Hidden on mobile (`hidden md:table-cell`).
  - **Column 4: Next Action** (`13rem`):
    - *Line 1*: Bold action name (`text-xs font-semibold text-slate-800 truncate`).
    - *Line 2*: Muted actor name (`text-[11px] text-slate-500`, omitted if matching assignee) with inline bold red due text (`Overdue 9h` with `title="Overdue"`).
  - **Column 5: Assignee** (`8rem`, `xl:w-44`): 28 px circular avatar displaying at most 2 uppercase initials stripped of honorifics (e.g. "Hon. Eduardo V. Alvarez" → `EA`, unassigned renders dashed circle with `—`). Full name appears on `xl`+ screens. Sortable by assignee.
  - **Column 6: Actions** (`3.5rem`): Kebab (`...`) dropdown menu (`relative z-20`) providing secondary actions ("Assign to me", "Change status", "Ask for info", "Mark urgent"). Legacy separate "Review" button is removed.
- **Row Clickability & Accessibility**: Entire row is clickable via stretched link on the tracking code anchor (`after:absolute after:inset-0 after:z-10`), enabling native Ctrl/Cmd-click, middle-click, and Tab/Enter keyboard navigation. Interactive controls (checkboxes, kebab toggles) have `relative z-20` so clicking them never triggers navigation.
- **Priority Signaling & Left Stripe**: Rows feature a 4 px left border stripe (`border-l-4 border-l-red-600` for Critical, `border-l-amber-500` for High, transparent otherwise). Color is never the sole indicator; `sr-only` priority text is rendered on every row.
- **Toolbar & Density Toggle**:
  - Toolbar features live report count, emergency pin toggle (`pin=1`), Export CSV button, and a segmented "Compact / Comfortable" density toggle (`#density-compact`, `#density-comfortable`).
  - Density selection persists in `localStorage.getItem('queue_density')` and applies `.is-comfortable` to `.data-table-queue` (increasing vertical padding to `0.85rem`).
- **Bulk Action Bar**: Appears above the table when 1 or more rows are selected. Includes selected count, quick assign, status change, "Confirm & route" button, and a "Clear" button.
- **Mobile Card Transformation (< 768 px)**: Below `md` (375 px viewport), the table switches to CSS grid cards:
  - Header is hidden; `<tr>` becomes an elevated card (`border border-line rounded-xl p-3`).
  - Select checkbox anchors to top right (`top-3 right-3 z-20`).
  - *Line 1*: Tracking code, priority/emergency badges, and inline status badge.
  - *Line 2*: Description clamped to 2 lines max (`line-clamp-2`).
  - *Line 3*: Meta line (`Category · Purok · age`).
  - *Line 4*: 3-column bottom bar: Next action (left), Assignee avatar (center), Kebab menu (right).
  - Zero horizontal scroll (`scrollWidth <= innerWidth`).

---

### Pattern K: Voice Reporting & Verification Components (Planned Prototype)

#### 1. Audio Recorder Component (`<x-voice.recorder>`)
- **Resident Form Placement ("Quick Report by Voice" Strip)**: Located in a dedicated strip at the **TOP** of the single-card resident form, directly above *"Choose a category (optional)"*, with an anchor link *"or fill in the form yourself below ↓"*.
- **Consent Line & Button Label**: Explicit consent notice placed directly above the record button: *"My recording is processed by AI and seen by barangay staff only"* with primary button label *"I agree, start recording"*. Sets `voice_consent_at` timestamp. The bottom Data Privacy Notice checkbox is **never** auto-ticked.
- **States & Visual Progressions**:
  1. `idle`: Displays the consent line, primary record button (`fa-solid fa-microphone`), *"Upload audio file"* fallback link, and *"Load demo sample"* button (rotating between `full`, `partial`, `no_contact`, `emergency`, and `unclear` scenarios).
  2. `asking_permission`: Displays an informational prompt (*"Allow microphone access when prompted by your browser"*). Friendly alert if microphone access is denied.
  3. `recording`: Pulsing red recording dot, live countdown timer (`00:00 / 02:00`), live audio level indicator bars (Web Audio API `AnalyserNode`), and high-contrast *"Done / Stop"* (primary) and *"Cancel"* (ghost) buttons. Enforces maximum duration (`voice.max_seconds`, default 120s) with auto-stop. Cross-browser codec compatibility (`audio/webm;codecs=opus` on Chrome, `audio/mp4` on Safari).
  4. `transcribing`: Processing spinner with status label *"Listening to your recording..."*. Releases microphone stream immediately.
  5. `done`: Audio preview player with scrubber, *"Discard & record again"* link, and aria-live feedback banner:
     - Clear: *"Filled {N} field(s) from your recording. Please check them."* (green banner).
     - Partial: *"Parts of this recording were unclear. Please check every field."* (amber banner).
     - Unclear / Failed / Throttled / Limit: *"We couldn't understand this recording. Please type your report or record again."* (amber banner; leaves form empty).
- **Form Pre-Fill Engine ("From recording" & "Not provided")**:
  - Pre-fills **only empty fields**; never overwrites user-typed inputs. Re-recording only updates previously AI-filled, unedited fields.
  - Adds an emerald *"From recording"* badge with microphone icon to filled fields; badge dynamically disappears as soon as the resident edits the field.
  - Spoken PII isolation: Description textarea receives sanitized, PII-free `description`, never the raw transcript.
  - Category chip: Pre-selected if speaker clearly described that problem type, marked "From recording", and can be deselected.
  - Safety alert: Pre-ticks *"Someone is in danger right now"* with *"From recording, please confirm"* badge and reveals 911 emergency prompt. Untickable by resident.
  - Contact auto-expand: If caller name, phone, or email are extracted, automatically expands collapsed `<details class="card-collapsible">` sections.
  - Strict anonymity: If resident toggles anonymous mode on after voice fill, AI-filled contact inputs and badges are immediately cleared.
- **Mobile & Accessibility Rules**:
  - Full responsiveness down to 375 px viewport with zero horizontal overflow.
  - All interactive controls enforce a minimum touch target of 44 px $\times$ 44 px.
  - Screen reader announcements via `aria-live="polite"` regions.
  - Browser microphone streams are explicitly closed on stop, cancel, window `pagehide`, and `turbo:before-render`.

#### 2. Staff Hotline Phone Intake Form (`GET /admin/reports/phone`)
- Follows Pattern B single-column form architecture with a **Record-First Intake** workflow.
- Opened from a dedicated button (*"Record phone report"*) on the Report Queue header toolbar (requires `reports.log_phone` permission; no new sidebar item).
- **Secondary Manual Link**: Header features a *"Fill in manually instead"* anchor link directly to Step 2 for desk officers who choose not to record.
- **Staff Audio Reality**: Acknowledges no direct telephony infrastructure; desk officers either record the call on speakerphone or dictate a summary after the call. UI instruction states: *"Record the call (speakerphone) or dictate a summary"*. The recorder is not labeled optional.
- **Verbal Consent Gate & Script**:
  - Mandatory verbal consent checkbox strictly **ABOVE** the audio recorder: *"I certify that the caller gave verbal consent to record and process this report under RA 10173."*
  - Read-aloud spoken script card displayed for desk officers: *"Before we begin, do you give consent for this call to be recorded and processed to log your barangay report under the Data Privacy Act?"*
  - Recorder controls remain disabled (`pointer-events-none opacity-50`) until the verbal consent checkbox is checked.
- **Clarity & Emergency Banners**:
  - **Partial Clarity**: Displays an amber warning banner: *"Parts of this recording were unclear. Please listen again and check every field."*
  - **Unclear / Verification**: When audio cannot be transcribed or clarity is unclear, displays: *"We couldn't understand this recording clearly. Listen again and enter the details."* Pre-filled fields remain unset ("Not provided").
  - **Emergency Prompt**: When emergency indicators are detected in audio, surfaces a red emergency callout banner advising immediate 911/hotline dispatch, and pre-ticks the emergency checkbox with a *"From recording, please confirm"* badge.
- **Audio Review & Staff Transcript Panel**:
  - Embedded HTML5 audio player preview for verifying recorded audio prior to submission.
  - Collapsible staff-only transcript panel (*"What was said"*) displaying raw verbatim transcript (`report_audios.transcript`).
- **Pre-Fill Engine ("From recording" & "Not provided")**:
  - AI-filled inputs display an emerald *"From recording"* badge with microphone icon.
  - Badges dynamically disappear as soon as staff edit or type in a field.
  - Fields not mentioned in recording display a muted italic *"Not provided"* placeholder and remain `null`.
  - Required unstated fields (Purok, Description) show helper cues: *"Not mentioned in recording, please choose/enter it"*.
  - Manual edits by staff are strictly preserved across re-recordings (only previously AI-filled, unedited fields are updated).
  - Spoken PII isolation: The description textarea receives the PII-free sanitized `description`, never the raw transcript.
- **Submission & Consent Attribution**:
  - Submits through standard `POST /admin/reports/phone` storing `consent_at = now()` and `consent_recorded_by = auth()->id()`.
  - Confirmation screen displays generated tracking code (`BRG-XXXXXX`) with one-click copy button and quick-action links (*"Open in Queue"*, *"Log another call"*).

#### 3. Staff Review Page Voice Card & Provenance Chips
- Rendered in the 3-way triage workspace if `reports.source` is `voice` or `phone_log`.
- Features an embedded HTML5 audio player streaming from `GET /admin/reports/{report}/audio` with HTTP Range scrubbing.
- Displays `transcript_status` badge (`done`, `needs_verification`, `failed`, `mock`) and detected language.
- **Audio Provenance Chips**: On Category, Purok, Landmark, and Incident Description cards, displays a small chip:
  - *"From recording"* (emerald/blue chip with microphone icon) when the value was accepted directly from recording.
  - *"Edited after recording"* (amber chip with pencil icon) when the resident or staff modified the field after voice intake.
- **Optional Category Handling**: If the resident did not select a category on the form, the Category card displays *"Not chosen by resident"* next to the AI suggestion. Forward routing remains blocked until staff select and confirm a category.
- **Edited by Resident Marker**: If the resident edited the problem description after voice transcription, displays an *"Edited by resident"* marker on the Incident Description comparison row.
- **Verification Banner & Action**: When `transcript_status` is `needs_verification` or `failed`, displays an amber callout banner:
  > *"Voice report needs verification. Listen to the recording and confirm or enter what was said before routing."*
  Provides an inline text input to confirm or enter the verified transcript, and an action button (*"Verify transcript"*). Blocks "Confirm and route" until verified. Verification logs `verified_by`, `verified_at`, and an audit event.

#### 4. Report Queue Marker
- On `/admin/reports`, voice reports display a small microphone icon (`fa-solid fa-microphone text-blue-600`) beside the tracking code.
- Unverified voice reports display `Verify audio` in the Next Action cell with due/overdue styling, without adding a new table column or increasing row height (~48px maintained).

---

### Responsive Rules Summary (All Patterns)
1. **Title Row**: Under 768 px, action buttons wrap below the page title. Under 480 px, the primary button expands to 100% full width.
2. **Tables**: Wrap inside an `overflow-x-auto` card container. The first identifier column pins sticky left with an elevation divider.
3. **Forms**: Two-column layouts collapse to a single column on `< 1024 px`.
4. **Sticky Footers**: Collapse to stacked full-width buttons on mobile with the **PRIMARY button on top**.
5. **Wizards**: Reduce horizontal padding to 16 px on mobile viewports; form cards occupy 100% width.

---

## 4. Page-by-Page Specifications

### Workstream 1: Setup Wizard & Administration (WS1)

#### 4.1 Onboarding Setup Wizard
- **ID & Route**: `WS1-P01` | `GET /setup` (`setup.show`) | **Pattern D** | **Owner: WS1**
- **ASCII Wireframe**:
```text
+----------------------------------------------------------------------+
| [Seal]                  BARANGAY CALARIAN ONBOARDING                 |
| +------------------------------------------------------------------+ |
| | Step 1 of 5: System Verification                                 | |
| | [=========>----------------------------------------------------] | |
| | Server & Storage Prerequisites                                   | |
| | Confirm that the environment is ready for initial configuration. | |
| |                                                                  | |
| | [x] Database Connected (SQLite/MySQL)                            | |
| | [x] Private Storage Directory Writable                           | |
| | [x] Active Mail Driver (Log / SMTP)                              | |
| |                                                                  | |
| |                                               [ Start Setup -> ] | |
| +------------------------------------------------------------------+ |
+----------------------------------------------------------------------+
```
- **Sections in Order**:
  1. Municipal Brand Header.
  2. Wizard Progress Indicator ("Step N of 5").
  3. Prerequisites & Baseline Verification.
  4. Step Form Content (Step 1: Verification, Step 2: Admin Account, Step 3: Barangay Profile, Step 4: Fiscal Year, Step 5: Personnel & Review).
  5. Navigation Action Footer.
- **Buttons Table**:
  | Label | Variant | Position | Rule |
  | :--- | :--- | :--- | :--- |
  | `< Back` | Secondary / Ghost | Bottom-left of card | Hidden on Step 1; enabled on Steps 2–5. |
  | `Save and Continue ->` | Primary (`--blue`) | Bottom-right of card | Enabled when required fields validate. |
  | `Complete Setup` | Primary Amber (`--gold`) | Bottom-right on Step 5 | Finalizes configuration, seeds baseline reference data, and redirects to `/login`. |
- **Step 5: Personnel & Review**:
  Displays a summary of verified environment checks, administrator credentials, barangay profile, activated fiscal year, and seeded baseline positions (Punong Barangay, Secretary, Lupon, Tanod). Provisions the root Administrator user account. Staff account creation and permission preset assignments are performed post-setup via `/admin/users` (optional interactive Step 5b staff onboarding during setup is tracked in TASKS.md Task 13).
- **Mobile Notes**: Table converts to stacked card rows on `< 640 px`; inputs expand full width.

---

#### 4.2 Fiscal Years Management
- **ID & Route**: `WS1-P02` | `GET /admin/fiscal-years` (`admin.fiscal-years.index`) | **Pattern A** | **Owner: WS1**
- **ASCII Wireframe**:
```text
+----------------------------------------------------------------------+
| Breadcrumbs: Home / Settings / Fiscal Years                          |
| Fiscal Years Management                            [+ Add Fiscal Year]
| Configure statutory operational years, active status, and locks      |
+----------------------------------------------------------------------+
| Fiscal Year Directory                                                |
| Year   Start Date   End Date     Status    Locked  Records   Actions |
| -------------------------------------------------------------------- |
| 2026   2026-01-01   2026-12-31   [Active]   [ ]    142 rec   [Lock][:]
| 2025   2025-01-01   2025-12-31   [Closed]   [x]    819 rec   [Audit] 
+----------------------------------------------------------------------+
```
- **Sections in Order**: Title Row -> Table Card -> Close Year Confirmation Modal.
- **Buttons Table**:
  | Label | Variant | Position | Rule |
  | :--- | :--- | :--- | :--- |
  | `+ Add Fiscal Year` | Primary (`--blue`) | Top-right of header | Opens creation modal. |
  | `Activate` | Outline Blue | Row actions dropdown | Disabled if year is already active or locked. |
  | `Close Fiscal Year` | Danger Outline | Row actions dropdown | Opens Modal F with carry-over audit. |
- **Table Columns Table**:
  | Column | Width % | Align | Notes |
  | :--- | :--- | :--- | :--- |
  | Year | 12% | Left | Bold year identifier (e.g. `2026`). |
  | Start | 16% | Left | ISO date `YYYY-MM-DD`. |
  | End | 16% | Left | ISO date `YYYY-MM-DD`. |
  | Status | 14% | Left | Pill badge (`Active` green, `Closed` slate). |
  | Locked | 8% | Center | Padlock icon if `is_locked = true`. |
  | Records | 22% | Left | Summary counts of reports, referrals, cases. |
  | Actions | 12% | Right | View, Activate, Lock/Close dropdown. |
  *(Columns sum: 12 + 16 + 16 + 14 + 8 + 22 + 12 = 100%)*
- **States**: Empty state prompts to create initial year; Closing a year opens Modal F.
- **Mobile Notes**: Under 768 px, Start/End dates collapse into a single range string.

---

#### 4.3 Numbering Schemes Configuration
- **ID & Route**: `WS1-P03` | `GET /admin/settings/numbering` (`admin.numbering.edit`) | **Pattern E** | **Owner: WS1**
- **ASCII Wireframe**:
```text
+----------------------------------------------------------------------+
| Breadcrumbs: Home / Settings / Numbering                             |
| Sequential Numbering Schemes                                         |
| Configure format masks and annual reset policies                     |
+----------------------------------------------------------------------+
| Card 1: External Referral Scheme                                     |
| Generates reference numbers      | Format Mask *                     |
| for Mode B referrals             | [ REF-{YEAR}-{SEQ:4}            ] |
|                                  | Live Preview: [ REF-2026-0001   ] |
|                                  | Reset Policy: (x) Annual ( ) Cont |
|                                  | Next Number:  REF-2026-0012       |
|                                  |               [ Save Referrals  ] |
+----------------------------------------------------------------------+
```
- **Sections in Order**: Title Row -> Referral Scheme Card -> Case Scheme Card -> Summons Scheme Card.
- **Buttons Table**:
  | Label | Variant | Position | Rule |
  | :--- | :--- | :--- | :--- |
  | `Save Numbering Scheme` | Primary (`--blue`) | Bottom-right of each card | Enabled when mask tokens validate. |
  | `Test Sequence Next` | Ghost / Small | Inline beside preview | Simulates sequence increment without consuming. |
- **States**: Live JavaScript preview badge re-evaluates as user types format mask tokens.

---

#### 4.4 Pending Confirmation Register
- **ID & Route**: `WS1-P04` | `GET /admin/settings/pending` (`admin.pending.index`) | **Pattern A Variant** | **Owner: WS1**
- **ASCII Wireframe**:
```text
+----------------------------------------------------------------------+
| Breadcrumbs: Home / Settings / Pending Confirmation                  |
| Unconfirmed Placeholder Register              [Export Register (CSV)]
| Review items awaiting official confirmation from the Barangay        |
+----------------------------------------------------------------------+
| Banner: [!] 12 items currently use placeholder values (Q1 to Q28).   |
+----------------------------------------------------------------------+
| Collapsible Group: General Identity & Hall (4 items)  [Confirm Group]|
| Item           Current Value       Question   Area    Actions        |
| -------------------------------------------------------------------- |
| Hall Address   Calarian Hall Rd    Q5         General [Edit] [Confirm]
| Contact Number 0917-123-4567       Q6         General [Edit] [Confirm]
+----------------------------------------------------------------------+
```
- **Buttons Table**:
  | Label | Variant | Position | Rule |
  | :--- | :--- | :--- | :--- |
  | `Export Register (CSV)` | Secondary | Top-right of header | Downloads CSV audit of unconfirmed items. |
  | `Confirm Group` | Outline Blue | Right side of group header | Marks all items in section confirmed. |
  | `Confirm` | Ghost / Green | Row action right | Confirms single setting key. |
- **Table Columns Table**:
  | Column | Width % | Align | Notes |
  | :--- | :--- | :--- | :--- |
  | Item | 28% | Left | Setting label or entity description. |
  | Current Placeholder Value | 28% | Left | Monospace or bold placeholder string. |
  | Question | 10% | Left | Questionnaire reference (e.g. `Q05`). |
  | Area | 12% | Left | Category badge (`General`, `Cases`, `SLA`). |
  | Actions | 22% | Right | Edit link and Confirm check button. |
  *(Columns sum: 28 + 28 + 10 + 12 + 22 = 100%)*

---

#### 4.5 General Settings Form
- **ID & Route**: `WS1-P05` | `GET /admin/settings/general` (`admin.settings.general.edit`) | **Pattern B** | **Owner: WS1**
- **Sections in Order**: Title Row -> Barangay Identity Card -> Physical Hall Location Card -> Contact Details Card -> Sticky Footer Bar.
- **Buttons Table**:
  | Label | Variant | Position | Rule |
  | :--- | :--- | :--- | :--- |
  | `Cancel` | Ghost | Sticky footer right-left | Resets form to stored values. |
  | `Save Settings` | Primary (`--blue`) | Sticky footer rightmost | Validates inputs and updates settings. |

---

#### 4.6 Case Types Configuration
- **ID & Route**: `WS1-P06` | `GET /admin/settings/case-types` (`admin.case-types.index`) | **Pattern A** | **Owner: WS1**
- **Buttons Table**:
  | Label | Variant | Position | Rule |
  | :--- | :--- | :--- | :--- |
  | `+ Add Case Type` | Primary (`--blue`) | Top-right of header | Opens creation modal. |
- **Table Columns Table**:
  | Column | Width % | Align | Notes |
  | :--- | :--- | :--- | :--- |
  | Name | 34% | Left | Dispute category name (e.g. `Boundary Dispute`). |
  | Slug | 20% | Left | Monospace system key (`boundary_dispute`). |
  | Active | 14% | Center | Status badge (`Active` green, `Inactive` slate). |
  | Sort Order | 12% | Right | Integer display sequence. |
  | Actions | 20% | Right | Edit and Deactivate buttons. |
  *(Columns sum: 34 + 20 + 14 + 12 + 20 = 100%)*

---

#### 4.7 Case Form Fields Configuration
- **ID & Route**: `WS1-P07` | `GET /admin/settings/case-fields` (`admin.case-fields.index`) | **Pattern A** | **Owner: WS1**
- **Table Columns Table**:
  | Column | Width % | Align | Notes |
  | :--- | :--- | :--- | :--- |
  | Field Label | 30% | Left | Display name on form. |
  | Entity | 16% | Left | Pill badge (`Case` or `Party`). |
  | Required | 14% | Center | Boolean icon check. |
  | Visible | 14% | Center | Boolean icon check. |
  | Applies To | 12% | Left | `All` or specific case type. |
  | Actions | 14% | Right | Edit field modal trigger. |
  *(Columns sum: 30 + 16 + 14 + 14 + 12 + 14 = 100%)*

---

#### 4.8 Escalation Destinations Settings
- **ID & Route**: `WS1-P08` | `GET /admin/settings/escalation-destinations` (`admin.escalation-destinations.index`) | **Pattern A** | **Owner: WS1**
- **Buttons Table**:
  | Label | Variant | Position | Rule |
  | :--- | :--- | :--- | :--- |
  | `+ Add Venue` | Primary (`--blue`) | Top-right of header | Opens venue creation modal. |
- **Table Columns Table**:
  | Column | Width % | Align | Notes |
  | :--- | :--- | :--- | :--- |
  | Venue Name | 32% | Left | Institutional name (e.g. `MTCC Branch 1`). |
  | Contact Person | 22% | Left | Clerk of court or officer in charge. |
  | Contact Phone | 18% | Left | Telephone / mobile number. |
  | Status | 14% | Center | Active status indicator. |
  | Actions | 14% | Right | Edit and archive actions. |
  *(Columns sum: 32 + 22 + 18 + 14 + 14 = 100%)*

---

#### 4.9 System Backups Management
- **ID & Route**: `WS1-P09` | `GET /admin/settings/backups` (`admin.backups.index`) | **Pattern A** | **Owner: WS1**
- **Buttons Table**:
  | Label | Variant | Position | Rule |
  | :--- | :--- | :--- | :--- |
  | `Run Backup Now` | Primary (`--blue`) | Top-right of header | Triggers SQLite/database snapshot. |
- **Table Columns Table**:
  | Column | Width % | Align | Notes |
  | :--- | :--- | :--- | :--- |
  | Backup Date | 30% | Left | Timestamp `YYYY-MM-DD HH:MM`. |
  | File Size | 15% | Left | Human-readable size (e.g. `14.2 MB`). |
  | Status | 15% | Left | Badge (`Completed` green, `Failed` red). |
  | Actions | 40% | Right | Download archive button right-aligned. |
  *(Columns sum: 30 + 15 + 15 + 40 = 100%)*

---

### Workstream 2: People, Access & Reports Center (WS2)

#### 4.10 Personnel Directory
- **ID & Route**: `WS2-P01` | `GET /admin/personnel` (`admin.personnel.index`) | **Pattern A** | **Owner: WS2**
- **Buttons Table**:
  | Label | Variant | Position | Rule |
  | :--- | :--- | :--- | :--- |
  | `+ Add Official` | Primary (`--blue`) | Top-right of header | Redirects to `admin.personnel.create`. |
- **Table Columns Table**:
  | Column | Width % | Align | Notes |
  | :--- | :--- | :--- | :--- |
  | Name | 24% | Left | Full name with bold styling. |
  | Position | 18% | Left | Assigned municipal office. |
  | Term | 18% | Left | Date range `YYYY - YYYY`. |
  | Signatory | 8% | Center | Checkmark if authorized document signer. |
  | Login | 8% | Center | Checkmark if user account is linked. |
  | Status | 10% | Left | Pill badge (`Active` green, `Inactive` slate). |
  | Actions | 14% | Right | View, Edit, Deactivate buttons. |
  *(Columns sum: 24 + 18 + 18 + 8 + 8 + 10 + 14 = 100%)*

---

#### 4.11 Personnel Form (Create / Edit)
- **ID & Route**: `WS2-P02` | `GET /admin/personnel/create` (`admin.personnel.create`) | **Pattern B** | **Owner: WS2**
- **Sections in Order**: Title Row -> Basic Particulars Card -> Term of Office Card -> Signatory Authorization Card (with signature upload & live preview) -> Login Account Link Card -> Sticky Footer Bar.
- **Buttons Table**:
  | Label | Variant | Position | Rule |
  | :--- | :--- | :--- | :--- |
  | `Cancel` | Ghost | Sticky footer right-left | Returns to personnel directory. |
  | `Save Official` | Primary (`--blue`) | Sticky footer rightmost | Submits validated personnel profile. |

---

#### 4.12 Positions Management
- **ID & Route**: `WS2-P03` | `GET /admin/positions` (`admin.positions.index`) | **Pattern A** | **Owner: WS2**
- **Buttons Table**:
  | Label | Variant | Position | Rule |
  | :--- | :--- | :--- | :--- |
  | `+ Add Position` | Primary (`--blue`) | Top-right of header | Opens position creation modal. |
- **Table Columns Table**:
  | Column | Width % | Align | Notes |
  | :--- | :--- | :--- | :--- |
  | Name | 34% | Left | Official title (e.g. `Barangay Secretary`). |
  | Code | 20% | Left | Monospace slug (`barangay_secretary`). |
  | Signatory by Default | 14% | Center | Checkmark icon. |
  | Order | 8% | Right | Sorting integer. |
  | Status | 12% | Left | Active status indicator. |
  | Actions | 12% | Right | Edit and Deactivate actions. |
  *(Columns sum: 34 + 20 + 14 + 8 + 12 + 12 = 100%)*

---

#### 4.13 Signatories Matrix Configuration
- **ID & Route**: `WS2-P04` | `GET /admin/signatories` (`admin.signatories.edit`) | **Pattern A Variant** | **Owner: WS2**
- **Buttons Table**:
  | Label | Variant | Position | Rule |
  | :--- | :--- | :--- | :--- |
  | `Save Signatories Matrix` | Primary (`--blue`) | Top-right of header | Persists matrix assignments. |
- **Table Columns Table**:
  | Column | Width % | Align | Notes |
  | :--- | :--- | :--- | :--- |
  | Document Type | 28% | Left | Title (Summons, Endorsement, Settlement). |
  | Prepared by | 18% | Left | Select dropdown of eligible positions. |
  | Noted by | 18% | Left | Select dropdown of eligible positions. |
  | Approved by | 18% | Left | Select dropdown of eligible positions. |
  | Currently Resolves To | 18% | Left | Read-only live name of current incumbent. |
  *(Columns sum: 28 + 18 + 18 + 18 + 18 = 100%)*

---

#### 4.14 User Accounts Directory
- **ID & Route**: `WS2-P05` | `GET /admin/users` (`admin.users.index`) | **Pattern A** | **Owner: WS2**
- **Buttons Table**:
  | Label | Variant | Position | Rule |
  | :--- | :--- | :--- | :--- |
  | `+ Add User` | Primary (`--blue`) | Top-right of header | Opens user creation screen. |
- **Table Columns Table**:
  | Column | Width % | Align | Notes |
  | :--- | :--- | :--- | :--- |
  | Name | 22% | Left | User display name. |
  | Email | 26% | Left | Contact email address. |
  | Role | 10% | Left | Role pill badge (`Admin`, `Staff`, `Agency`). |
  | Permissions | 12% | Left | Count indicator (e.g. `14 perms`). |
  | Status | 10% | Left | Active / Inactive pill badge. |
  | Last Login | 12% | Left | Relative time (e.g. `2 hours ago`). |
  | Actions | 8% | Right | Edit, Permissions, Reset Password. |
  *(Columns sum: 22 + 26 + 10 + 12 + 10 + 12 + 8 = 100%)*

---

#### 4.15 User Form & Granular Permission Matrix
- **ID & Route**: `WS2-P06` | `GET /admin/users/{id}/permissions` (`admin.users.permissions.edit`) | **Pattern B** | **Owner: WS2**
- **Sections in Order**: Title Row -> Preset Selector Card (Dropdown: `staff_full`, `case_encoder`, `reports_analyst`) -> 2-Column Grouped Permission Checkbox Card (Each group header carries a "Select All" ghost link) -> Sticky Footer Bar.
- **Buttons Table**:
  | Label | Variant | Position | Rule |
  | :--- | :--- | :--- | :--- |
  | `Cancel` | Ghost | Sticky footer right-left | Reverts permission selections. |
  | `Save Permissions` | Primary (`--blue`) | Sticky footer rightmost | Syncs user permissions via `PermissionService`. |

---

#### 4.16 Forced Password Change
- **ID & Route**: `WS2-P07` | `GET /account/password` (`account.password.edit`) | **Pattern D** | **Owner: WS2**
- **Card Content**: Width 560 px centered, warning banner ("Security Notice: You must set a permanent password before continuing"), current password field, new password field (min 10 chars), confirm password field.
- **Buttons Table**:
  | Label | Variant | Position | Rule |
  | :--- | :--- | :--- | :--- |
  | `Update Password & Proceed` | Primary (`--blue`) | Full width inside card | Enabled when passwords match and meet length rules. |

---

#### 4.17 Response Templates Management
- **ID & Route**: `WS2-P08` | `GET /admin/templates` (`admin.templates.index`) | **Pattern A** | **Owner: WS2**
- **Buttons Table**:
  | Label | Variant | Position | Rule |
  | :--- | :--- | :--- | :--- |
  | `+ Add Template` | Primary (`--blue`) | Top-right of header | Opens template creation modal. |
- **Table Columns Table**:
  | Column | Width % | Align | Notes |
  | :--- | :--- | :--- | :--- |
  | Title | 34% | Left | Formal template identifier. |
  | Kind | 16% | Left | Category badge (`Status Update`, `Info Request`). |
  | Category | 18% | Left | Applicable problem category or `All`. |
  | Active | 10% | Center | Boolean active checkmark. |
  | Actions | 22% | Right | Edit and Delete actions. |
  *(Columns sum: 34 + 16 + 18 + 10 + 22 = 100%)*

---

#### 4.18 Reports Center Index
- **ID & Route**: `WS2-P09` | `GET /admin/reports-center` (`admin.reports-center.index`) | **Pattern A Variant (Card Grid)** | **Owner: WS2**
- **ASCII Wireframe**:
```text
+----------------------------------------------------------------------+
| Breadcrumbs: Home / Reports Center                                   |
| Official Reports & Statutory Compliance Exports   [Generated History]
| Generate DILG, Katarungang Pambarangay, and internal audit reports   |
+----------------------------------------------------------------------+
| Report Template Catalog (3 columns desktop, 2 tablet, 1 mobile)      |
| +-------------------------+ +-------------------------+              |
| | KP Monthly Compliance   | | Cases Summary Register  |              |
| | DILG Form 1 monthly     | | Comprehensive list of   |              |
| | dispute resolution audit| | filed disputes & status |              |
| | [DRAFT: Unconfirmed]    | | [Official Template]     |              |
| |            [Open Report]| |            [Open Report]|              |
| +-------------------------+ +-------------------------+              |
+----------------------------------------------------------------------+
```
- **Buttons Table**:
  | Label | Variant | Position | Rule |
  | :--- | :--- | :--- | :--- |
  | `Generated History` | Secondary | Top-right of header | Navigates to history table. |
  | `Open Report` | Primary Outline | Bottom-right of each card | Opens report runner. |

---

#### 4.19 Report Preview & Export Page
- **ID & Route**: `WS2-P10` | `GET /admin/reports-center/{key}` (`admin.reports-center.show`) | **Pattern H Integration** | **Owner: WS2**
- **Sections in Order**: Title Row -> Parameter Filter Bar Card -> Document Preview Panel (White Letter-proportioned paper surface with drop shadow, Pattern H styling, and `<x-ui.draft-watermark>` if unconfirmed).
- **Buttons Table**:
  | Label | Variant | Position | Rule |
  | :--- | :--- | :--- | :--- |
  | `Reset Filters` | Ghost | Far left of filter bar | Resets parameters to default month/year. |
  | `Preview` | Secondary | Right-aligned in filter bar | Refreshes live preview panel. |
  | `Export PDF` | Secondary | Beside preview button | Triggers DomPDF download stream. |
  | `Export Excel` | Secondary | Beside PDF button | Triggers OpenSpout XLSX download. |
  | `Print` | Primary (`--blue`) | Rightmost in filter bar | Triggers browser native print dialog. |

---

#### 4.20 Reports Center Generated History
- **ID & Route**: `WS2-P11` | `GET /admin/reports-center/history` (`admin.reports-center.history`) | **Pattern A** | **Owner: WS2**
- **Table Columns Table**:
  | Column | Width % | Align | Notes |
  | :--- | :--- | :--- | :--- |
  | Generation Date | 16% | Left | Timestamp `YYYY-MM-DD HH:MM`. |
  | Report Name | 26% | Left | Template title. |
  | Applied Filters | 28% | Left | Summary pill tags (e.g. `FY: 2026`). |
  | Format | 10% | Left | Pill badge (`PDF`, `XLSX`, `CSV`). |
  | Generated by | 12% | Left | Staff user name. |
  | Action | 8% | Right | Download file button. |
  *(Columns sum: 16 + 26 + 28 + 10 + 12 + 8 = 100%)*

---

### Workstream 3: Cases — Intake & Parties (WS3)

#### 4.21 Barangay Cases Docket List
- **ID & Route**: `WS3-P01` | `GET /admin/cases` (`admin.cases.index`) | **Pattern A** | **Owner: WS3**
- **Tabs Available**: `Active Cases`, `Blotter Records`, `Pending Intake` (Planned, [DECIDED, D4]), `Archived / Closed`.
- **Buttons Table**:
  | Label | Variant | Position | Rule |
  | :--- | :--- | :--- | :--- |
  | `Export CSV` | Secondary | Header right-left | Streams filtered case roster. |
  | `+ File New Case` | Primary (`--blue`) | Top-right of header | Navigates to `admin.cases.create`. |
- **Table Columns Table**:
  | Column | Width % | Align | Notes |
  | :--- | :--- | :--- | :--- |
  | Case No. | 12% | Left | Monospace link to detail view (`{MM}-{SEQ:3}-{YEAR}`). |
  | Filed | 9% | Left | Short date `MMM DD`. |
  | Type | 13% | Left | Case category badge. |
  | Complainant(s) | 17% | Left | Truncated primary complainant names. |
  | Respondent(s) | 17% | Left | Truncated primary respondent names. |
  | Status | 9% | Left | Case status pill badge (Pattern A). |
  | Summons / Deadline | 8% | Center | Deadline state ("Day X of 15", gold near deadline, red overdue, grey ready for next step) and plain summons count. |
  | Assigned | 9% | Left | Assigned mediator / clerk name. |
  | Actions | 6% | Right | View and Edit icon actions. |
  *(Columns sum: 12 + 9 + 13 + 17 + 17 + 9 + 8 + 9 + 6 = 100%)*

#### 4.21a Pending Complaint Intake Tab (Planned, [DECIDED, D4])
- **ID & Route**: `WS3-P01b` | `GET /admin/cases?tab=pending_intake` | **Pattern A** | **Owner: WS3**
- **Description**: Displays citizen complaint requests submitted via the online public form awaiting official staff docketing.
- **Table Columns Table**:
  | Column | Width % | Align | Notes |
  | :--- | :--- | :--- | :--- |
  | Received | 12% | Left | Date/time submitted `MMM DD, HH:MM`. |
  | Complainant | 20% | Left | Citizen name, contact, and residency claim badge. |
  | Respondent | 20% | Left | Free-text respondent name and address (unlinked). |
  | Case Type | 15% | Left | Suggested category chip. |
  | Narrative / Relief | 18% | Left | Truncated 5Ws snippet and relief requested. |
  | Actions | 15% | Right | Primary "Docket this" button + Secondary "Redirect/Reject" dropdown. |
  *(Columns sum: 12 + 20 + 20 + 15 + 18 + 15 = 100%)*
- **Action Modal**: "Redirect / Reject" opens modal requiring standard outcome reason and mandatory written explanation. "Docket this" opens prefilled WS3 case intake form (`admin.cases.create`).

---

#### 4.22 Case Filing Form (Intake)
- **ID & Route**: `WS3-P02` | `GET /admin/cases/create` (`admin.cases.create`) | **Pattern B** | **Owner: WS3**
- **ASCII Wireframe**:
```text
+----------------------------------------------------------------------+
| Breadcrumbs: Home / Cases / New                                      |
| File Barangay Blotter Case                                           |
| Docket an amicable dispute complaint under Katarungang Pambarangay   |
+----------------------------------------------------------------------+
| Card A: Case Particulars                                             |
| Case Type *                   Filing Date *          Incident Date   |
| [ Boundary Dispute        v ] [ 2026-11-22         ] [ 2026-11-18  ] |
| Case Title *                                                         |
| [ Dela Cruz vs. Penduko - Boundary Line Dispute                    ] |
| Complaint Narrative *                                                |
| [ Enter detailed statement of grievance...                         ] |
+----------------------------------------------------------------------+
| Card B: Complainant(s) *                                             |
| [ Search existing resident name / alias...                        v] |
| Selected Complainants:                                               |
| [ Juan Dela Cruz (Purok 1 · Resident)                            [x] |
| [+ Add New Person Record]                                            |
+----------------------------------------------------------------------+
| Card C: Respondent(s) *                                              |
| [ Search existing party name...                                   v] |
+----------------------------------------------------------------------+
| Card D: Witnesses (Optional)                                         |
| ...                                                                  |
+----------------------------------------------------------------------+
| [Sticky Footer Bar]                                                  |
| [Cancel]               [Save & Issue Summons] [ Save & Docket Case ] |
+----------------------------------------------------------------------+
```
- **Party Finder Interaction**:
  - Full-width search input with autocomplete drop-down.
  - Rows in drop-down 56 px height: Name in bold, age and purok in muted text, residency badge on the right.
  - Selected parties appear as compact tag cards with a remove `x` button.
  - Clicking "Add New Person" expands a nested creation sub-form.
- **Buttons Table**:
  | Label | Variant | Position | Rule |
  | :--- | :--- | :--- | :--- |
  | `Cancel` | Ghost | Sticky footer left | Returns to docket without saving. |
  | `Save & Issue Summons` | Secondary (`--blue`) | Sticky footer right-left | Saves case and immediately opens summons wizard. |
  | `Save & Docket Case` | Primary (`--blue`) | Sticky footer rightmost | Validates parties and dockets case in status `filed`. |

---

#### 4.23 Master Party Directory
- **ID & Route**: `WS3-P03` | `GET /admin/parties` (`admin.parties.index`) | **Pattern A** | **Owner: WS3**
- **Buttons Table**:
  | Label | Variant | Position | Rule |
  | :--- | :--- | :--- | :--- |
  | `+ Add Person Record` | Primary (`--blue`) | Top-right of header | Opens person modal. |
- **Table Columns Table**:
  | Column | Width % | Align | Notes |
  | :--- | :--- | :--- | :--- |
  | Name | 24% | Left | Full legal name and alias. |
  | Residency | 12% | Left | Status badge (`Resident`, `Non-Resident`). |
  | Purok | 12% | Left | Assigned purok or city. |
  | Contact | 16% | Left | Telephone number or email. |
  | Cases | 8% | Right | Lifetime case involvement count. |
  | Last Case | 14% | Left | Most recent case number. |
  | Actions | 14% | Right | View and Edit profile buttons. |
  *(Columns sum: 24 + 12 + 12 + 16 + 8 + 14 + 14 = 100%)*

---

#### 4.24 Party Profile Page
- **ID & Route**: `WS3-P04` | `GET /admin/parties/{id}` (`admin.parties.show`) | **Pattern C** | **Owner: WS3**
- **Header**: Party name, residency verification badge, address, primary phone.
- **Tabs**: 1. Case History (Table of cases as Complainant/Respondent), 2. Verification Documents, 3. Contact & Address Notes.
- **Buttons Table**:
  | Label | Variant | Position | Rule |
  | :--- | :--- | :--- | :--- |
  | `Edit Profile` | Secondary | Header action right | Opens editing modal. |
  | `Verify Residency` | Primary (`--blue`) | Header action rightmost | Confirms residency status with officer note. |

---

#### 4.25 Secretary Dashboard
- **ID & Route**: `WS4-P00` | `GET /admin/secretary` (`admin.secretary`) | **Pattern G** | **Owner: WS4**
- **Sections in Order**: Page Header with FY indicator -> 8 Reminder KPI Cards -> 2 Split Action List Widgets (Hearings Today, Referrals Awaiting Follow-Up).
- **KPI Metrics** (Operational reminders per Q27):
  1. Hearings Scheduled Today
  2. Upcoming Hearings (Next 7 Days)
  3. Summonses Awaiting Service Record
  4. Cases Near Deadline (Due Soon, <= 3 working days left)
  5. Overdue Cases (`OverdueService`, working days exceeded without settlement)
  6. Ready for Next Step (Mediation/conciliation concluded without consensus, ready for CFA or conciliation referral)
  7. Referrals Awaiting Follow-Up (Sent > 3 days ago per Q12)
  8. High-Priority Open Problem Reports (Emergency or Critical)

---

### Workstream 4: Cases — Summons, Hearings, Escalation (WS4)

#### 4.26 Case Detail Workspace
- **ID & Route**: `WS4-P01` | `GET /admin/cases/{id}` (`admin.cases.show`) | **Pattern C** | **Owner: WS4**
- **ASCII Wireframe**:
```text
+----------------------------------------------------------------------+
| Breadcrumbs: Home / Cases / 11-001-2026                              |
| 11-001-2026: Dela Cruz vs. Penduko                    [Issue Summons]
| FY 2026 · Boundary Dispute · Status: [In Mediation] · Source: [Resident Intake]|
| Blotter Ref: 11-001-2026 (Logbook Series A, Page 12, Entry 3)        |
+----------------------------------------------------------------------+
| [Overview]  [Parties (2)]  [Summons (1)]  [Hearings]  [Escalation]   |
+---------------------------------------------------+------------------+
| Main Tab View (2/3 width)                         | Side Cards (1/3) |
| (Summons Tab Selected)                            | Pre-Summons Gate |
| Sequential Summons (No maximum limit)             | [x] Same brgy    |
|                                                   | [x] KP scope     |
| Seq No.  Respondent  Issued   Hearing   Service   | [x] Non-confid.  |
| ------------------------------------------------- | [x] Verified     |
| 1   001  P. Penduko  Nov 18   Nov 25    [Received]|                  |
|          Served by: Tanod J. Perez                | Mediation Clock  |
|                                                   | 1st Mtg: Nov 18  |
| [!] Warning: Respondent failed to appear at       | [=== Day 8/15 =] |
|     mediation. Constitute Pangkat before CFA.     | 7 days remain    |
|                                                   |                  |
|                                                   | Confidentiality  |
|                                                   | [ Standard Pub ] |
|                                                   |                  |
|                                                   | Assigned Officer |
|                                                   | Sec. Delos Santos|
+---------------------------------------------------+------------------+
```
- **Action Cluster**:
  | Label | Variant | Position | Rule |
  | :--- | :--- | :--- | :--- |
  | `Print Summary` | Secondary | Header right-left | Generates case summary PDF. |
  | `Issue Summons` | Primary (`--blue`) | Header rightmost | Enabled when case is active and not settled. |
  | `More Actions...` | Ghost Dropdown | Beside primary | Settlement agreement, Mark Dismissed, Void. |
- **Summons Tab Table Columns**:
  | Column | Width % | Align | Notes |
  | :--- | :--- | :--- | :--- |
  | Seq | 6% | Left | Sequential number (`1`, `2`, `3`...). |
  | Number | 14% | Left | Formatted ID (`SUM-2026-0001`). |
  | Respondent | 20% | Left | Respondent party name. |
  | Issued | 12% | Left | Issuance date `YYYY-MM-DD`. |
  | Hearing | 16% | Left | Hearing timestamp. |
  | Service | 12% | Left | Service result badge (`Received`, `Refused`, `Not Found`, `Other` [not in confirmed list]) + `Served by`. |
  | Attendance | 12% | Left | Attendance badge (`Appeared`, `Failed to Appear`). |
  | Actions | 8% | Right | Print notice, Void action (requires note). |
  *(Columns sum: 6 + 14 + 20 + 12 + 16 + 12 + 12 + 8 = 100%. Voided rows display with line-through styling).*
- **Procedural Safeguards & Indicators**:
  - **Source Badges**: Displays `[Walk-in]`, `[From Report]` (for cases created via Report-to-Case conversion), or `[Resident Intake]` (for cases created from citizen online intake).
  - **Pre-Summons Eligibility Card**: Side card displaying checklist ([PROPOSED, P1]): parties natural persons residing in Calarian, dispute within KP jurisdiction, matter non-confidential, residency verified.
  - **Mediation Clock Start**: Starts from `first_meeting_on` before the Punong Barangay ([PROPOSED, P2]), falling back to `filed_on`.
  - **Soft Warning Banners**: Extended soft warning triggers if staff attempt `in_mediation` -> `escalated` when the respondent never appeared at mediation (*"The Pangkat usually must be constituted first"*).
  - **Day-Based Indicator**: At 15 working days with no settlement, the deadline panel renders "Ready for next step: endorse to Pangkat" (slate badge). Summons counts are informational only; no summons-count cutoff exists ([CONFIRMED, C11]).

---

#### 4.27 Issue Summons Form
- **ID & Route**: `WS4-P02` | `GET /admin/cases/{id}/summons/create` (`admin.cases.summons.create`) | **Pattern B** | **Owner: WS4**
- **Sections in Order**: Title Row -> Summons Metadata Card (Respondent selection, sequence number preview, hearing date/time, venue) -> Serving Officer Card (Assigned Tanod) -> Sticky Footer Bar.
- **Buttons Table**:
  | Label | Variant | Position | Rule |
  | :--- | :--- | :--- | :--- |
  | `Cancel` | Ghost | Sticky footer left | Returns to case show view. |
  | `Issue & Print Summons` | Primary (`--blue`) | Sticky footer rightmost | Validates hearing date and generates sequential summons. |

---

#### 4.28 Conciliation Hearings Calendar
- **ID & Route**: `WS4-P03` | `GET /admin/hearings` (`admin.hearings.index`) | **Pattern A** | **Owner: WS4**
- **Buttons Table**:
  | Label | Variant | Position | Rule |
  | :--- | :--- | :--- | :--- |
  | `Export Schedule` | Secondary | Header right-left | Downloads daily session schedule. |
- **Table Columns Table**:
  | Column | Width % | Align | Notes |
  | :--- | :--- | :--- | :--- |
  | Date and Time | 16% | Left | `YYYY-MM-DD HH:MM`. |
  | Case No. | 14% | Left | Link to case detail. |
  | Parties | 24% | Left | Complainant vs. Respondent. |
  | Venue | 14% | Left | Room / Session hall name. |
  | Presided by | 14% | Left | Punong Barangay / Lupon Member. |
  | Status | 9% | Left | Badge (`Scheduled`, `Concluded`, `Cancelled`). |
  | Actions | 9% | Right | Record outcome modal trigger. |
  *(Columns sum: 16 + 14 + 24 + 14 + 14 + 9 + 9 = 100%)*

---

#### 4.29 Case Escalation & Certificate to File Action (CFA)
- **ID & Route**: `WS4-P04` | `GET /admin/cases/{id}/escalate` (`admin.cases.escalate.create`) | **Pattern B / Modal F** | **Owner: WS4**
- **Sections in Order**: Title Row -> Complainant Request Card (complainant selector, request date) -> Destination Agency Card (PNP, Prosecutor, Court) -> Advisory Deadline Notice Card (soft advisory warning if within 15 working days; does not block) -> Pangkat Chairman Signatory Card -> Documentation Packet Checkboxes (CFA, Endorsement Letter, Case Summary) -> Sticky Footer Bar.
- **Buttons Table**:
  | Label | Variant | Position | Rule |
  | :--- | :--- | :--- | :--- |
  | `Cancel` | Ghost | Sticky footer left | Dismisses escalation. |
  | `Certify & Issue CFA` | Red Outline Primary | Sticky footer rightmost | Transitions case to `escalated` and generates CFA signed by Pangkat Chairman. |

---

#### 4.30 Amicable Settlement Agreement
- **ID & Route**: `WS4-P05` | `POST /admin/cases/{id}/settle` (`admin.cases.settle`) | **Pattern F (Medium Modal)** | **Owner: WS4**
- **Modal Content**: Title "Record Amicable Settlement Agreement", settled on date, agreed terms textarea with live character counter, optional monetary settlement amount (decimal), signed agreement document upload (PDF/JPG/PNG, max 5 MB, stored on private disk).
- **Buttons Table**:
  | Label | Variant | Position | Rule |
  | :--- | :--- | :--- | :--- |
  | `Cancel` | Ghost | Modal footer left | Closes dialog without changes. |
  | `Confirm Settlement Agreement` | Primary (`--blue`) | Modal footer rightmost | Calls `CaseStatusService::transition` to `settled`. |

---

### Workstream 5: Partner Agency Portal & Referrals (WS5)

#### 4.31 External Agency Self-Registration
- **ID & Route**: `WS5-P01` | `GET /agency/register` (`agency.register`) | **Pattern D (560 px width)** | **Owner: WS5**
- **Card Content**:
  - Single column form layout.
  - Fields: Agency claimed name (select from registered offices or "Other"), Applicant full name, Official position title, Government email, Contact phone, Government ID / endorsement letter file upload (`.jpg`, `.png`, `.pdf`, max 5 MB), Password, Confirm password.
  - Mandatory Data Privacy Act (RA 10173) consent checkbox.
- **Buttons Table**:
  | Label | Variant | Position | Rule |
  | :--- | :--- | :--- | :--- |
  | `Submit Application for Approval` | Primary (`--blue`) | Full width inside card | Enabled when all required fields and upload are validated. |

---

#### 4.32 Agency Pending Approval Landing
- **ID & Route**: `WS5-P02` | `GET /agency/pending` (`agency.pending`) | **Pattern D** | **Owner: WS5**
- **Card Content**: Clock illustration icon, centered heading "Application Under Barangay Review", brief explanatory text, and a `Log Out` ghost button.

---

#### 4.33 Partner Agency Referral Inbox
- **ID & Route**: `WS5-P03` | `GET /agency/referrals` (`agency.referrals.index`) | **Pattern A (Agency Shell)** | **Owner: WS5**
- **Table Columns Table**:
  | Column | Width % | Align | Notes |
  | :--- | :--- | :--- | :--- |
  | Referral No. | 16% | Left | Link to referral detail (`REF-2026-0001`). |
  | Date Sent | 14% | Left | `YYYY-MM-DD`. |
  | Category | 20% | Left | Civic issue category. |
  | Status | 14% | Left | Badge (`Sent`, `Acknowledged`, `Action Taken`, `Closed`). |
  | Follow-up | 14% | Left | Scheduled SLA follow-up date. |
  | Updated | 14% | Left | Timestamp of latest progress update. |
  | Actions | 8% | Right | View referral button. |
  *(Columns sum: 16 + 14 + 20 + 14 + 14 + 14 + 8 = 100%)*

---

#### 4.34 Partner Agency Referral Workspace
- **ID & Route**: `WS5-P04` | `GET /agency/referrals/{id}` (`agency.referrals.show`) | **Pattern C Variant (2 Columns)** | **Owner: WS5**
- **ASCII Wireframe**:
```text
+----------------------------------------------------------------------+
| [Agency Shell Topbar]  Agency: CSWDO Zamboanga      [Team] [Account] |
+----------------------------------------------------------------------+
| Breadcrumbs: Referrals / REF-2026-0004                               |
| Referral REF-2026-0004                                Status: [Sent] |
+---------------------------------------+------------------------------+
| Left Column: Case Information (2/3)   | Right Column: Updates (1/3)  |
| Formal Endorsement Letter             | Action Stepper               |
| Incident Narrative                    | (Sent) -> (Ack) -> (Done)    |
| Location: Purok 2, Calarian           |                              |
| Note: Reporter PII is masked          | [ Acknowledge Receipt (Pri) ]|
|                                       | [ Send Progress Update (Sec) ]|
|                                       | [ Decline Referral (Danger)  ]|
|                                       | ---------------------------- |
|                                       | Message Thread (Newest First)|
+---------------------------------------+------------------------------+
```
- **Action Buttons (Stacked in Right Column)**:
  - If status = `sent`: PRIMARY button is **Acknowledge Receipt** (full width).
  - If status = `acknowledged`: PRIMARY button is **Mark Action Taken** (full width).
  - Secondary button: **Send Message to Barangay**.
  - Destructive button: **Decline Referral** (red outline, prompts for mandatory explanation modal).

---

#### 4.35 Agency Team Management
- **ID & Route**: `WS5-P05` | `GET /agency/team` (`agency.team.index`) | **Pattern A (Agency Shell)** | **Owner: WS5**
- **Buttons Table**:
  | Label | Variant | Position | Rule |
  | :--- | :--- | :--- | :--- |
  | `+ Invite Team Member` | Primary (`--blue`) | Top-right of header | Visible only to Agency Office Admins. |
- **Table Columns Table**:
  | Column | Width % | Align | Notes |
  | :--- | :--- | :--- | :--- |
  | Member Name | 30% | Left | Officer full name. |
  | Official Email | 30% | Left | Login email address. |
  | Position Title | 20% | Left | Agency role. |
  | Role | 10% | Left | Badge (`Office Admin` or `Member`). |
  | Actions | 10% | Right | Revoke access button. |
  *(Columns sum: 30 + 30 + 20 + 10 + 10 = 100%)*

---

#### 4.36 Admin Partner Agency Approvals Directory
- **ID & Route**: `WS5-P06` | `GET /admin/agencies` (`admin.agencies.index`) | **Pattern A with Tabs** | **Owner: WS5**
- **Tabs**: 1. Pending (count), 2. Approved (count), 3. Rejected (count), 4. Suspended (count).
- **Table Columns Table**:
  | Column | Width % | Align | Notes |
  | :--- | :--- | :--- | :--- |
  | Agency | 24% | Left | Claimed agency title. |
  | Contact Person & Position | 22% | Left | Officer name & title. |
  | Email | 22% | Left | Address with verified badge icon. |
  | Submitted | 12% | Left | Date of application. |
  | Status | 10% | Left | Status badge. |
  | Actions | 10% | Right | Review / View Document button. |
  *(Columns sum: 24 + 22 + 22 + 12 + 10 + 10 = 100%)*

---

#### 4.37 Admin Partner Agency Detail View
- **ID & Route**: `WS5-P07` | `GET /admin/agencies/{id}` (`admin.agencies.show`) | **Pattern C** | **Owner: WS5**
- **Header Actions**:
  | Label | Variant | Position | Rule |
  | :--- | :--- | :--- | :--- |
  | `View Submitted ID Proof` | Secondary | Header right-left | Opens streamed private file in new tab. |
  | `Reject Application` | Danger Outline | Header right-center | Opens rejection modal with required reason. |
  | `Approve Agency Account` | Primary (`--blue`) | Header rightmost | Opens office link/create confirmation modal. |

---

#### 4.38 Staff Referrals & Routing Directory
- **ID & Route**: `WS5-P08` | `GET /admin/referrals` (`admin.referrals.index`) | **Pattern A (Existing, Updated)** | **Owner: WS5**
- **Table Columns Table**:
  | Column | Width % | Align | Notes |
  | :--- | :--- | :--- | :--- |
  | Ref No. & Tracking Code | 20% | Left | Monospace reference ID & tracking code. |
  | Report & Priority | 22% | Left | Incident category & priority score. |
  | Destination | 20% | Left | Office name & internal/external badge. |
  | Agency Portal Status | 12% | Left | Portal status badge (`Pending`, `Ack`, `Closed`). |
  | Status & Follow-up | 16% | Left | SLA follow-up combined pill. |
  | Actions | 10% | Right | View case and printable letter actions. |
  *(Columns sum: 20 + 22 + 20 + 12 + 16 + 10 = 100%)*

---

### Workstream 6: Historical Excel/CSV Import (WS6, Stretch)

#### 4.39 Legacy Import Wizard
- **ID & Route**: `WS6-P01` | `GET /admin/imports/create`, `/admin/imports/{id}/map`, `/admin/imports/{id}/preview` | **Pattern D / Pattern B Variant** | **Owner: WS6**
- **Sections in Order**:
  1. **Upload Card**: File drag-and-drop (Excel/CSV, max 10 MB, stored on private disk), download link for blank Excel template, target fiscal year selector.
  2. **Column Mapping Card**: Interactive mapper (Source column -> Target system field: Case No, Filed Date, Case Type, Complainant Full Name, Address, Respondent Full Name, Narrative, Status), save mapping template option.
  3. **Validation & Preview Card**: Summary tiles (Total rows, Valid, Warnings, Conflicts/Duplicates), paginated table with error pills and duplicate party/case flags.
  4. **Commit & Rollback Bar**: Primary button **Commit Batch** (chunked background execution, retains original case numbers), secondary **Roll Back Entire Batch** button.

---

### Shared & Existing Screens

#### 4.40 Global Search Results
- **ID & Route**: `SHR-P01` | `GET /admin/search` (`admin.search`) | **Owner: WS1**
- **Layout**: Stack of 4 cards partitioned by entity: **Problem Reports**, **External Referrals**, **Barangay Cases**, **People & Parties**.
- **Group Rules**: Multi-year cross-search [A Q8, C10]; max 5 search results per group with fiscal year badges; "See all N results ->" link at top-right of each card.

#### 4.41 Error Pages (403 Forbidden & 404 Not Found)
- **ID & Route**: `SHR-P02` | `403` & `404` views | **Pattern D Variant** | **Owner: WS1 / Shared**
- **Layout**: Centered card, width 480 px, status code illustration, brief plain-language explanation, **PRIMARY "Return to Dashboard" button**.

#### 4.42 Resident Entry Screen & Community Problem Form
- **ID & Routes**:
  - `GET /` (`reports.create`): Resident entry portal & community problem form.
  - `GET /track/{tracking_code}` (`reports.track`): Resident public tracking & verification timeline.
  - `GET /map` (`reports.map`): Public incident transparency map.
- **Entry Screen Architecture (Three Buttons, [DECIDED, D2])**:
  Before opening any form, the entry view presents three prominent, touch-friendly action cards:
  ```text
  +-------------------------------------------------------------+
  |                   BARANGAY CALARIAN                         |
  |             Citizen Service & Incident Portal               |
  +-------------------------------------------------------------+
  | [!] EMERGENCY (911 & Barangay Hotlines)                     |
  |     Life-safety emergencies, active crimes, urgent rescue   |
  +-------------------------------------------------------------+
  | [*] REPORT A COMMUNITY PROBLEM                              |
  |     Potholes, broken streetlights, floods, garbage, drainage|
  +-------------------------------------------------------------+
  | [i] FILE A COMPLAINT                                        |
  |     Disputes with neighbors, unpaid debt, property, injury  |
  +-------------------------------------------------------------+
  ```
  - **Emergency Button**: Uses high-visibility `--red` accent (`bg-red-600 text-white hover:bg-red-700`). Triggers immediate hotline overlay and tanod dispatch guidance.
  - **Report a Community Problem**: Reveals the single-card community problem report form.
  - **File a Complaint**: Navigates to the resident complaint intake form (`GET /complaint`).
  - *No "File a Blotter" button* ([DECIDED, D2]): Complaints automatically create the blotter entry behind them.
- **Community Problem Form Safeguards**:
  - **Wrong-Door Notice Banner ([DECIDED, D6])**: Non-blocking gold info banner at top of form:
    *"Is this a conflict with a specific person, like a neighbor, a debt, or an injury? File a Complaint instead, or visit the barangay hall."*
  - **Safety Banner ([PROPOSED, P9])**: Notice linking to offline emergency and domestic support hotlines.
  - **Guest & Anonymous Reporting ([DECIDED, D7; PROPOSED, P11])**:
    - No account or login required.
    - Optional reporter name, mobile number, and email.
    - "Report anonymously" checkbox clears and disables contact inputs, accompanied by helper text: *"If you leave out your contact details, we cannot tell you the result."*
    - Stores no reporter name, contact, IP address, or device identifier.
    - Post-submit confirmation card displays optional one-time tracking code for viewing status updates via `/track/{code}`.

#### 4.43 Resident Complaint Intake Form (Planned, [DECIDED, D4])
- **ID & Route**: `RES-C01` | `GET /complaint` / `POST /complaint` | **Pattern B Variant (Single Card)** | **Owner: WS3 / Resident UI**
- **Form Structure (Direct Single Compact Card, 375px Safe — no upfront 911/VAWC screening gate)**:
  1. **Complainant Block**: Full legal name *, Alias, Age / Date of Birth, Contact number *, Complete residential address *, Residency declaration. *(Anonymity is strictly not offered on dispute complaints)*.
  2. **Respondent Block**: Full name *, Alias if known, Complete address (for summons service) *, Contact number if known. *(Stored as unlinked free text; not added to party directory)*.
  3. **Incident Facts (5Ws)**: What happened *, Who was involved *, When did it occur *, Where did it occur *, Why / How it happened *.
  4. **Rights Violated & Relief Requested**: Specific description of rights violated and damages suffered *; specific remedies or settlement terms requested *.
  5. **Data Privacy Consent**: Mandatory RA 10173 consent checkbox: *"I certify the facts stated are true and correct, and consent to barangay conciliation processing under RA 10173."* (Never auto-ticked).
  6. **Closing Notice**: *"This online submission is a pending request only. Under Republic Act 7160, you must appear in person at the Barangay Calarian Hall to sign your official complaint before conciliation proceedings begin."*
  7. **Primary Action**: `[ Submit Complaint Request ]` (`--blue`, full-width on mobile).

#### 4.44 Complaint Acknowledgement Page (Planned, [DECIDED, D4])
- **ID & Route**: `RES-C02` | `GET /complaint/received` | **Pattern D Variant** | **Owner: WS3 / Resident UI**
- **Layout**: Centered card, width 480 px.
- **Content**:
  - Green checkmark icon.
  - Heading: *"Complaint Request Received"*
  - Body: *"Your dispute complaint request has been received by the Barangay Calarian Lupon Tagapamayapa desk. Please visit the barangay hall within 3 working days with a valid ID to physically sign your complaint and complete docketing."*
  - **Strict Privacy Rule**: Renders **NO case status, NO respondent information, and NO public timeline/tracking link**, safeguarding respondent privacy and complying with RA 10173.
  - Primary button: `[ Return to Home ]`.

---

## 5. UI Component Specifications (`<x-ui.*>`)

| Component | Props | Variants / Sizes | States Handled | Usage Rule |
| :--- | :--- | :--- | :--- | :--- |
| `<x-ui.page-header>` | `title`, `subtitle`, `badge` | Default | Standard | Always placed at top of content area; slots hold breadcrumbs and header action buttons. |
| `<x-ui.card>` | `padding` (`p-6` default), `title` | Default | Standard | Universal white surface container; do not nest cards unless creating person tags or code blocks. |
| `<x-ui.button>` | `variant`, `size`, `href`, `disabled`, `type` | `primary`, `secondary`, `outline`, `danger`, `ghost`, `amber` / `sm`, `md`, `lg` | Default, hover, active, focus, disabled, loading (spinner) | Primary is always rightmost; destructive operations use `danger` or red-outline. |
| `<x-ui.badge>` | `variant`, `dot`, `size` | `success`, `info`, `warning`, `danger`, `slate`, `amber` | Static | Color tokens reference [`docs/CONVENTIONS.md`](file:///c:/githubFiles/Barangay-Reporting-System-1/docs/CONVENTIONS.md) Section 4.2. Always pair with text. |
| `<x-ui.table>` | `headers` (array), `empty` (string) | Default | Default, empty state, hover row | Responsive wrapper with horizontal scroll card containment. First column sticky on mobile. |
| `<x-ui.form-field>` | `label`, `name`, `required`, `hint`, `error` | Default | Default, focused, valid, invalid (red outline) | Encapsulates accessible label, required asterisk, input slot, helper text, and error span. |
| `<x-ui.modal>` | `id`, `title`, `size` (`sm`, `md`, `lg`) | Small (480 px), Medium (640 px), Large (800 px) | Open, closed | Native dialog or plain JS backdrop; traps focus; auto-focuses first input; closes on Esc. |
| `<x-ui.empty-state>` | `title`, `subtitle`, `icon`, `actionText`, `actionUrl` | Default | Static | Displayed when table query returns 0 rows. Includes primary action if user has creation rights. |
| `<x-ui.flash>` | `status`, `message` | `success`, `error`, `warning`, `info` | Slide-in, persistent, auto-dismiss | Fixed at top-right; success dismisses after 5s; errors persist until user dismisses. |
| `<x-ui.pending-badge>`| `count` | Amber pill (`bg-amber-100 text-amber-900 border-amber-300`) | Static | Renders in topbar to alert administrators of unconfirmed placeholder configurations. |
| `<x-ui.draft-watermark>`| None | Diagonal 12% opacity grey | Print / PDF | Watermark rendering across documents when template or signatories are unconfirmed. |
| `<x-year-switcher>` | None | Dropdown select | Session-bound | Swaps session viewing context across all fiscal-year scoped models. |
| `<x-ui.global-search>` | `placeholder` | Max width 480 px | Collapsed, expanded, searching | Topbar search bar indexing cases, reports, parties, and referrals across all years. |
| `<x-ui.person-finder>` | `name`, `entity`, `selected` | Default autocomplete | Empty, searching, selected | 56 px autocomplete row showing full name, age, residency badge, and selection tags. |
| `<x-ui.deadline-panel>`| `daysElapsed`, `totalDays`, `rule` | Slate (normal), gold (due soon <=3d), red (overdue), grey (ready for next step) | Dynamic | Visual meter indicating working day progress towards statutory mediation/conciliation deadlines. |
| `<x-ui.kpi-card>` | `title`, `value`, `icon`, `url`, `trend` | Standard dashboard card | Static | Standardized executive dashboard tile with bottom-left drilldown link. |
| `<x-ui.signatory-block>`| `documentType`, `incumbents` | 2 or 3 equal columns | Draft, verified | Letterhead signature block printing blank rules when signatories are unconfirmed (two fallback views with hardcoded titles flagged for cleanup in Task 14). |
| `<x-ui.tab-bar>` | `tabs` (array), `active` | Underline border style | Active, inactive | Segmented tab strip highlighting active section with 2 px blue bottom border. |
| `<x-ui.queue-filters>` | `currentStage`, `stageCounts`, `statusFilter`, `emergencyOnly`, `emergencyCount`, `isMyCases`, `myCasesCount`, `overdueOnly`, `overdueCount` | 3-row white card | Responsive (tabs >= 1024px, select < 1024px) | Unified queue filter bar. My cases is a toggle chip, not a sidebar item; tab counts scope to active view. |
| `<x-ui.stepper>` | `steps` (array), `current` | Horizontal (desktop), vertical (mobile) | Pending, active, completed | Step progression bar for multi-step wizards and agency referral progress tracking. |
| `<x-voice.recorder>` *(Planned)* | `maxDuration`, `audioToken`, `fallback` | Compact (resident), Full (desk officer) | Idle, requesting, recording, preview, transcribing, uncertain, error | Voice recording component using MediaRecorder API with audio file upload fallback and Turbo lifecycle cleanup. |

> [!NOTE]
> **Badge Color Matrix**: All status pill variants reference the authoritative status-to-token mappings in [`docs/CONVENTIONS.md`](file:///c:/githubFiles/Barangay-Reporting-System-1/docs/CONVENTIONS.md) Section 4.2.

---

## 6. Interaction Behavior Rules & Manual Checklist

### 6.1 Interaction Behavior Rules
1. **Asynchronous Form Submissions**: Primary buttons disable immediately on submit, display a FontAwesome spinner icon, and switch label to `"Saving..."` or `"Preparing your file..."`.
2. **Destructive Operation Safety**: Any action causing record deletion, voiding, cancellation, rejection, or closure requires a confirmation modal (Pattern F) explicitly naming the consequences and requiring an explanatory note.
3. **Dirty Form Guards**: Forms with modified inputs trigger a `beforeunload` browser prompt if the user attempts to navigate away without saving.
4. **Flash Notification Toasting**: Success toasts appear fixed at top-right (`z-50`) and automatically fade out after 5,000 ms. Error banners persist indefinitely until dismissed.

### 6.2 Manual Multi-Device Verification Checklist
Before submitting any pull request, developers and AI agents must manually verify screens across **375 px (Mobile)**, **768 px (Tablet)**, and **1280 px (Desktop)**:

- [ ] **No Horizontal Canvas Scroll**: Under 375 px, the overall page document never generates horizontal scrolling (`overflow-x: hidden`).
- [ ] **Primary Button Positioning**: The primary call-to-action is consistently rightmost in headers and footers. On mobile footers, the primary button stacks on top at full width.
- [ ] **Table Column Verification**: Columns match the specified order and percentage widths summing to 100%. Tables scroll horizontally inside their card wrapper with sticky left columns.
- [ ] **Sticky Footer Clearances**: Sticky footers never obscure or overlap the bottom-most input field of a form page.
- [ ] **Modal Viewport Boundaries**: Modals never exceed 90vh; interior content scrolls gracefully when overflowing on mobile.
- [ ] **Logical Keyboard Tab Order**: Tab cycles linearly from top-to-bottom, left-to-right through all interactive inputs.
- [ ] **Contrast Compliance**: Normal text displays at least 4.5:1 contrast against `#FFFFFF` and `#F8FAFC` backgrounds.

---

## 7. Differences from Current UI

This section highlights intentional differences between the existing application implementation and this comprehensive design specification:

| Area | Current Implementation | This Specification | Rationale & Migration Strategy |
| :--- | :--- | :--- | :--- |
| **Canvas Background** | Admin shell uses `x-hall-background` rendering `Calarian_Barangay_Hall.jpg` with a dark backdrop filter. | Standardizes on clean `#F8FAFC` slate background for all admin, agency, and wizard pages. | Eliminates visual noise, reduces memory footprint, and ensures 4.5:1 text contrast compliance. |
| **Agency Portal Layout** | `layouts/agency.blade.php` has a left sidebar navigation like admin. | Topbar-only navigation (64 px) with centered content area (max width 1080 px). | Differentiates external agency portal from internal municipal back-office and simplifies navigation. |
| **Card Border Radius** | Several existing templates use `rounded-2xl` (16 px) or `rounded-xl` (12 px). | Standardizes on `rounded-lg` (8 px) for cards, inputs, buttons, and modals. | Professional GovTech aesthetic; matches institutional municipal forms. |
| **Topbar Header** | Header combines page title and action buttons inside topbar. | Standardizes topbar for global tools (search, year switcher, account menu); page title moves to `<x-ui.page-header>`. | Prevents header clipping when long titles collide with header action clusters. |
| **Case Show Skeleton** | Case detail view was generated as a bare tab placeholder in Step 6. | Formalized into Pattern C with a 2/3 main column and 1/3 side column (Deadline Panel & Confidentiality cards). | Provides immediate visual context and progress tracking for Katarungang Pambarangay conciliation. |

### Planned Page and Navigation Label Renames ([DECIDED, D1])
To better match municipal operational vocabulary, page titles and navigation labels will be renamed as a presentation-only update (Task 12). Underlying route paths, controller names, database tables, and permission slugs remain unchanged:

| Current Label | Planned Renamed Label | Context / Affected Blade Views | Underlying Route / Permission Slug (Unchanged) |
| :--- | :--- | :--- | :--- |
| **Report Queue** | **Community reports** | `<x-layout.sidebar>`, `<x-ui.page-header>`, breadcrumbs on `/admin/reports` | Route `admin.reports.*`, slug `reports.view` |
| **Barangay Cases** | **Cases & Blotter** | `<x-layout.sidebar>`, `<x-ui.page-header>`, breadcrumbs on `/admin/cases` | Route `admin.cases.*`, slug `cases.view` |
| **Incident Reports** (Public) | **Community Problems** | Public entry buttons (`/`), public tracking header | Routes `reports.create`, `reports.track` |
