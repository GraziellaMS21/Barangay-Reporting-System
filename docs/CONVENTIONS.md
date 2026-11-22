# Code Style & Architectural Conventions

Standardized development conventions for **Barangay-Reporting-System-1**.

---

## 1. PHP & Laravel Conventions

- **PHP Version Target**: PHP 8.4+ (minimum PHP 8.3).
- **Strict Typing**: Every method must declare explicit parameter types and return type declarations:
  ```php
  public function transition(Report $report, string $toStatus, ?string $note = null): Report
  ```
- **Constructor Property Promotion**: Use PHP 8 constructor promotion; avoid empty zero-parameter constructors:
  ```php
  public function __construct(
      public GeminiService $gemini,
      public ReportStatusService $statusService,
  ) {}
  ```
- **Thin Controllers**: Controllers must only handle request validation, service delegation, and view/JSON response transformation. Move all business calculations, status mutations, and external calls into `app/Services/`.
- **Form Requests**: Use dedicated Form Requests (`app/Http/Requests/`) for non-trivial validation rules.
- **Database Transactions**: Any mutation updating multiple tables (e.g. status transition + log creation + event dispatch) must execute within `DB::transaction()`.
- **Core Governance Rule**: **"The AI suggests, the rules route, the officials decide."** Never auto-transition a report or case status. Neither AI analysis, summons counts, attendance results, deadline expirations, nor document generation may automatically alter database statuses without explicit official action.
- **Dynamic Settings & Unconfirmed Values**: Never hardcode placeholder values (deadlines, follow-up days, barangay name, signatories, fees) in controllers, services, or Blade templates. Read from `setting()` or database models. When an item has `is_confirmed = false`, render `<x-ui.pending-badge>`.
- **Naming Conventions**:
  - Controllers: `PascalCase` ending in `Controller` (`ReportController`).
  - Models: Singular `PascalCase` (`Report`, `StatusLog`, `Referral`, `BarangayCase`).
  - Services: `PascalCase` ending in `Service` (`RoutingService`, `WorkingDays`).
  - Database Tables: Snake_case plural (`reports`, `status_logs`, `routing_rules`, `cases`).
  - Routes: Kebab-case or dot-notated named routes (`admin.cases.show`, `admin.referrals.letter`).

---

## 2. Frontend & Styling Conventions

- **Tailwind CSS v4**: Bundled via `@tailwindcss/vite`. Avoid inline CSS where utility classes suffice.
- **Core Design Tokens**: Adhere to the established municipal color palette tokens:
  - `--ink`: Primary dark text (`#0f172a` / `#1e293b`).
  - `--blue`: Institutional primary accent (`#1d4ed8` / `#2563eb`).
  - `--gold`: Attention and review accent (`#d97706` / `#f59e0b`).
  - `--red`: Emergency alert and danger accent (`#b91c1c` / `#dc2626`).
  - `--green`: Verified resolution accent (`#15803d` / `#16a34a`).
  - `--purple`: Conciliation and procedural accent (`#7e22ce` / `#9333ea`).
  - `--line`: Subtle border divider (`#e2e8f0` / `#cbd5e1`).
  - Surface & Tabular Tokens: Page background `#F8FAFC`, card background `#FFFFFF`, muted text `#64748B`, table header background `#EEF2FF`, row hover `#F1F5F9`.
- **Status & Badge Colors**:
  - Reports: `submitted` (blue), `under_review` (gold), `routed` (blue), `in_progress` (amber), `needs_info` (gold), `awaiting_confirmation` (gold), `resolved` (green), `reopened` (red), `emergency_flagged` (red), `referred` (blue), `merged` (grey), `no_action` (slate).
  - Cases: `filed` (blue), `in_mediation` (gold), `in_conciliation` (purple), `settled` (green), `unsettled` (red outline), `escalated` (red solid), `dismissed` (grey), `withdrawn` (grey outline).
  - Deadlines: Normal "Day X of 15" (slate), Due soon (gold), Overdue (red), Ready for next step (grey).
  - Fiscal Years: `inactive` (grey), `active` (green), `closed` (dark grey), `locked` (dark ink).
  - Residency: `resident` (green), `non_resident` (blue), `transient` (purple), `unverified` (gold).
- **UI/UX Specification**: All page wireframes, layout patterns (A through H), button hierarchy, table column widths, and component definitions must strictly follow [**`docs/UI_UX_SPEC.md`**](file:///c:/githubFiles/Barangay-Reporting-System-1/docs/UI_UX_SPEC.md).
- **Icons**: Use FontAwesome Free 7 classes (`fa-solid fa-paper-plane`, `fa-solid fa-triangle-exclamation`).
- **Mobile First**: All resident-facing views must render cleanly down to a $375\text{px}$ viewport width without horizontal scroll.
- **Security & CSRF**: Every form must include `@csrf`. Use `{{ $var }}` blade escaping for untrusted user inputs.

---

## 3. Migration Timestamp Ranges (Per Workstream)

To prevent migration filename timestamp ordering conflicts when workstreams merge, each workstream strictly uses its assigned timestamp prefix:

| Workstream | Prefix Range |
| :--- | :--- |
| **WS1** (Setup, Fiscal Year, Settings, Backups) | `2026_12_01_10NNNN_` |
| **WS2** (People, Access, Personnel, Reports Center) | `2026_12_01_20NNNN_` |
| **WS3** (Cases Intake, Parties, Duplicates, Merging) | `2026_12_01_30NNNN_` |
| **WS4** (Summons, Hearings, Deadlines, Escalation) | `2026_12_01_40NNNN_` |
| **WS5** (Agency Accounts, Portal, Referrals) | `2026_12_01_50NNNN_` |
| **WS6** (Legacy File Import, Stretch) | `2026_12_01_60NNNN_` |

---

## 4. Code Quality & Formatting

- **Pint Formatting**: Run `./vendor/bin/pint` on modified files before committing.
- **Dry-Run Check**: Run `./vendor/bin/pint --test` to detect formatting regressions.
- **No Manual Edits**: Never manually alter `composer.lock`, `package-lock.json`, or files inside `public/build/`.
