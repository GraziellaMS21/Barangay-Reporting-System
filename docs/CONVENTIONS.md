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
- **Naming Conventions**:
  - Controllers: `PascalCase` ending in `Controller` (`ReportController`).
  - Models: Singular `PascalCase` (`Report`, `StatusLog`, `Referral`).
  - Services: `PascalCase` ending in `Service` (`RoutingService`).
  - Database Tables: Snake_case plural (`reports`, `status_logs`, `routing_rules`).
  - Routes: Kebab-case named routes (`reports.show`, `admin.referrals.letter`).

---

## 2. Frontend & Styling Conventions

- **Tailwind CSS v4**: Bundled via `@tailwindcss/vite`. Avoid inline CSS where utility classes suffice.
- **Core Design Tokens**: Adhere to the established municipal color palette tokens:
  - `--ink`: Primary dark text (`#0f172a` / `#1e293b`).
  - `--blue`: Institutional primary accent (`#1d4ed8` / `#2563eb`).
  - `--gold`: Attention and review accent (`#d97706` / `#f59e0b`).
  - `--red`: Emergency alert and danger accent (`#b91c1c` / `#dc2626`).
  - `--green`: Verified resolution accent (`#15803d` / `#16a34a`).
  - `--line`: Subtle border divider (`#e2e8f0` / `#cbd5e1`).
  - Surface & Tabular Tokens: Page background `#F8FAFC`, card background `#FFFFFF`, muted text `#64748B`, table header background `#EEF2FF`, row hover `#F1F5F9`.
- **UI/UX Specification**: All page wireframes, layout patterns (A through H), button hierarchy, table column widths, and component definitions must strictly follow [**`docs/UI_UX_SPEC.md`**](file:///c:/githubFiles/Barangay-Reporting-System-1/docs/UI_UX_SPEC.md).
- **Icons**: Use FontAwesome Free 7 classes (`fa-solid fa-paper-plane`, `fa-solid fa-triangle-exclamation`).
- **Mobile First**: All resident-facing views must render cleanly down to a $375\text{px}$ viewport width without horizontal scroll.
- **Security & CSRF**: Every form must include `@csrf`. Use `{{ $var }}` blade escaping for untrusted user inputs.

---

## 3. Code Quality & Formatting

- **Pint Formatting**: Run `./vendor/bin/pint` on modified files before committing.
- **Dry-Run Check**: Run `./vendor/bin/pint --test` to detect formatting regressions.
- **No Manual Edits**: Never manually alter `composer.lock`, `package-lock.json`, or files inside `public/build/`.
