# TrustLoan – App Architecture, Scoring Model & Inputs

This document describes the application architecture, how the credit scoring model is used, and every input the app collects from users. It is intended for technical documentation, onboarding, and dataset design (e.g. for training an ML scoring model).

---

## 1. Application architecture

### 1.1 Overview

TrustLoan is a **microfinance credit assessment web application** for Ghana’s informal sector. It follows **MVC (Model–View–Controller)** with a single entry point, no PHP framework, and no front-end framework.

- **Entry point:** `index.php` — routes every request (GET by `page`, POST by `action`).
- **Backend:** Plain PHP (no Laravel/Symfony). Database access via PDO (MySQL/MariaDB).
- **Frontend:** Server-rendered HTML from PHP views, plain CSS, vanilla JavaScript. Optional CDN libraries: intl-tel-input (phone), SweetAlert2 (dialogs).
- **No Composer, no npm build.** All dependencies are either zero (PHP) or loaded via CDN.

### 1.2 Request flow

1. **Every request** hits `index.php`.
2. **POST with `action`:**  
   - `$_POST['action']` is sanitized (alphanumeric + underscore) and mapped to `Actions/{action}.php`.  
   - That file includes the relevant Controller and calls one method (e.g. `ApplicationController::submitDocuments()`).  
   - The controller may redirect (e.g. to `?page=loan-amount`) or return; no view is rendered for typical form submissions.
3. **GET (or POST without a valid action):**  
   - `$page = $_GET['page']` (default `landing`).  
   - Protected pages (`documents`, `loan-amount`, `guarantor`, `mfi`, `waiting`, `home`, `settings`) require `is_logged_in()`; otherwise redirect to `?page=signin`.  
   - The corresponding view is included from `View/{page}.php` (e.g. `View/documents.php`, `View/home.php`).  
   - Special case: `page=view_application_image` serves application images (Ghana Card, photos) and returns without loading the main layout.

### 1.3 Folder structure and responsibilities

| Path | Responsibility |
|------|----------------|
| **index.php** | Single entry point. POST action routing; GET page routing; protection checks; includes `settings/core.php`, `settings/db_class.php`. |
| **settings/** | `core.php` (session, auth helpers, constants), `db_class.php` (PDO), `db_cred.php` (credentials), `trustloan.sql` (full schema + seeds). |
| **Classes/** | Business logic and data access only. No HTML, no HTTP. Examples: `User`, `Application`, `Loan`, `CreditScore`, `Group`. |
| **Controllers/** | Request handling and redirects. Call Classes; no SQL in controller, no HTML. Examples: `AuthController`, `ApplicationController`. |
| **Actions/** | One PHP file per action. Include controller, call one method (e.g. `submit_documents` → `ApplicationController::submitDocuments()`). No business logic in the action file. |
| **View/** | One PHP file per page. HTML and minimal PHP for presentation; no SQL, no financial logic. Partials in `View/partials/` (e.g. header). |
| **Css/** | Stylesheets: base, signin, landing, documents, home, creditscore, admin, mfi, etc. |
| **js/** | Vanilla JS (e.g. main.js, signin). No framework. |
| **Admin/** | Admin area: `Admin/index.php` (router), `Admin/login.php`, `Admin/layout.php`, `Admin/pages/*.php` (applicants, groups, loans, settings, etc.). Uses same backend Classes and Actions pattern where applicable. |
| **uploads/** | User-uploaded files: `uploads/applications/{application_id}/` for Ghana Card and application photos. |
| **docs/** | Documentation (this file, DATASET-FIELDS.md, MVC-ARCHITECTURE.md, etc.). |

### 1.4 Key conventions

- **Controllers:** Coordinate requests and redirects; delegate all logic and data access to Classes.
- **Classes (models):** All SQL and business rules. Public static methods for CRUD and domain operations (e.g. `Application::createOrGetLatest()`, `CreditScore::getForDisplay()`).
- **Views:** Only presentation. Receive variables set by the page or layout; no direct DB calls.
- **Actions:** Thin entry points: read `$_POST`/`$_FILES`, call controller, exit (often after redirect).
- **Borrower auth:** Session-based. `$_SESSION['user_id']`, `full_name`, etc. set after sign-in / login. `is_logged_in()` guards protected pages.
- **Admin auth:** Separate session keys (e.g. `admin_user_id`). Admin routes live under `Admin/` and use their own layout and actions.

### 1.5 Public pages (no login)

- `landing` — Home / marketing.
- `overview` — How it works.
- `creditscore` — Check credit score (shows score if logged in; otherwise sign-in prompt).
- `signin` — Phone → verification code → then login or register.
- `login` — Name + password (existing users) or name + password to create account (new users after verify).

### 1.6 Borrower flow (after login)

1. **Documents** — Ghana Card (front/back), business type/duration/location, 2–3 photos.  
2. **Loan amount** — Requested amount (GH¢), repayment weeks.  
3. **Guarantor** — Name, phone, relationship, occupation.  
4. **MFI** — Choose MFI from dropdown (area implied).  
5. **Waiting** — “Application submitted” message.  
6. **Home** — Dashboard: status, congratulations if approved, rejection message if rejected, links to continue application or settings.

Each step POSTs to an action (e.g. `submit_documents`, `submit_loan_amount`) and redirects to the next step or home.

### 1.7 Admin flow

- **Applicants:** List applications (filters: status, search). “View” opens a drawer with full application details (borrower, business, guarantor, MFI, **documents section**: Ghana Card + photos, **rejection reason** if rejected). Approve / Reject / Mark in progress only when status is `new` or `in_progress`; for approved/rejected, only status is shown.
- **Groups, Loans, Settings, etc.** — Separate admin pages and actions; not detailed here.

---

## 2. How the scoring model is used

### 2.1 What exists in the app today

The app includes a **credit score** that is **rule-based (heuristic)**, not machine learning. It is used to:

- Show the borrower their own score and a short explanation on the **Check credit score** page.
- Show the **same score** to admins when they open an application in the Applicants view (in the application detail drawer).

So in the current codebase, “the scoring model” is this rule-based calculator. There is no trained ML model or external scoring API; the score is computed on demand from stored loan/repayment and account data.

### 2.2 Where the score is computed and stored

- **Class:** `Classes/CreditScore.php`.
- **Storage:** Table `credit_scores` (columns: `user_id`, `score`, `calculated_at`). One row per user; score is 0–100.
- **Computation:** `CreditScore::calculateForUser($userId)`:
  - Reads **loans** and **loan_repayments** for that user.
  - Derives: account age (months), number of loans completed (status = closed), repayment counts (on-time vs late), and percentage of instalments paid on time.
  - Applies a **fixed formula** (see below) to produce a 0–100 score and a label (Excellent / Good / Fair / Poor).
- **When it runs:** When the borrower opens **Check credit score**, the view calls `CreditScore::getForDisplay($userId)`, which:
  1. Calls `calculateForUser($userId)`.
  2. Saves the result into `credit_scores` with `CreditScore::save()`.
  3. Returns the same result (score, label, breakdown, summary text) for the view.
- **Admin:** When fetching a single application (`fetch_application_action`), the backend loads the application and attaches the **stored** score from `credit_scores` (via `Application::getByIdWithDetails()` which joins/reads `credit_scores` for the application’s user). So admins see the last calculated score for that borrower; they do not trigger a fresh calculation themselves.

### 2.3 Current rule-based formula (no ML)

The score is built from:

- **Base:** 50.
- **Repayment history:** up to +35 from `(paid_on_time / total_due) * 0.35` (capped at 35).
- **Loans completed:** up to +15 from `loans_completed * 8` (capped at 15).
- **On-time share:** up to +25 from the fraction of due instalments that were paid (on time or late), scaled to 25.
- **Account age:** up to +12 from months since user creation (capped at 12).

Final score is clamped to 0–100. Labels:  
≥85 Excellent, ≥70 Good, ≥50 Fair, &lt;50 Poor.

So the **inputs to this rule-based model** are:

- User creation date (account age).
- For each loan of the user: id, status (to count “closed” as completed).
- For each repayment: due_date, status, paid_at (to count on-time vs late).

No application-level data (documents, business, guarantor, requested amount) is used in this heuristic; it is purely **post-disbursement behaviour** (repayment and loan completion).

### 2.4 How an ML scoring model could be integrated

For a **machine learning** scoring model (e.g. for approval/risk at application time, in a data-constrained setting), the intended use would be different:

- **Inputs:** The **application and user inputs** listed in Section 3 (documents metadata, business info, loan amount, guarantor, MFI, and optionally the same images/text you collect for the dataset). Optionally, the current rule-based score could be one more feature if the user has prior loans.
- **When to call it:** For example when an officer opens an application, or when the application is submitted, to get an ML-derived score or risk label. That would require:
  - A separate service or PHP code that runs your trained model (or calls an API).
  - Feeding it the same structured inputs (and optionally image paths or features) you use for your dataset.
- **Current app:** The app does **not** yet call any ML model. Approval/rejection is done manually by the admin (with a required rejection reason). The only “scoring” used in the app is the rule-based credit score above, which is based on repayment history and account age, not on application form data.

So in short:

- **Scoring model in the app today:** Rule-based credit score from loans/repayments; used on “Check credit score” and in admin application view.
- **Possible future use:** An ML model could consume the same inputs as in Section 3 (and your dataset) to produce a score or risk label used for decision support or automation; that integration is not implemented yet.

---

## 3. Inputs the app collects

This section lists **every input** the app collects from users (borrowers), in the order they appear in the flow. These are the same inputs you would use to build a dataset for training or evaluating an ML scoring model. Stored file paths point to the actual images (Ghana Card, photos) on disk.

### 3.1 Sign-in and account (before application)

| Input | Form / step | DB / storage | Type | Notes |
|-------|-------------|--------------|------|--------|
| **Phone** | Sign in — “Phone number” | `users.phone` | VARCHAR(20) | Required to start; verified by code. |
| **Full name** | Login / Create account — “Your name” | `users.full_name` | VARCHAR(120) | Set at first login or registration. |
| **Password** | Login / Create account | `users.password_hash` | VARCHAR(255) | Stored hashed only; not used as a model input. |

Verification code is used only to verify phone; it is not stored as a permanent user attribute.

### 3.2 Documents step

| Input | Form / step | DB / storage | Type | Notes |
|-------|-------------|--------------|------|--------|
| **Ghana Card – front** | Documents — “Front” | `applications.ghana_card_front_path` | File path | Image file under `uploads/applications/{id}/ghana_front.*`. |
| **Ghana Card – back** | Documents — “Back” | `applications.ghana_card_back_path` | File path | Image file under same folder, `ghana_back.*`. |
| **Business type** | Documents — “Type of business” | `applications.business_type` | VARCHAR(60) | e.g. Market stall, tailoring. |
| **Business duration** | Documents — “How long have you run it?” | `applications.business_duration` | VARCHAR(40) | e.g. 2 years. |
| **Business location** | Documents — “Business location” | `applications.business_location` | VARCHAR(120) | e.g. Adenta Market. |
| **Photo 1** | Documents — “Photo 1” | `application_photos.file_path` (sort_order 0) | File path | Stall, goods, or at work. |
| **Photo 2** | Documents — “Photo 2” | `application_photos.file_path` (sort_order 1) | File path | Same. |
| **Photo 3** | Documents — “Photo 3 (optional)” | `application_photos.file_path` (sort_order 2) | File path | Optional. |

File storage: project root → `uploads/applications/{application_id}/`; allowed image extensions: jpg, jpeg, png, gif, webp.

### 3.3 Loan amount step

| Input | Form / step | DB / storage | Type | Notes |
|-------|-------------|--------------|------|--------|
| **Requested amount** | Loan amount — “Amount (GH¢)” | `applications.requested_amount` | DECIMAL(12,2) | Min 100, step 50. |
| **Repayment weeks** | Loan amount — “Repayment period (weeks)” | `applications.repayment_weeks` | TINYINT | Default 12; min 4, max 52. |

### 3.4 Guarantor step

| Input | Form / step | DB / storage | Type | Notes |
|-------|-------------|--------------|------|--------|
| **Guarantor name** | Guarantor — “Guarantor name” | `guarantors.full_name` | VARCHAR(120) | Required. |
| **Guarantor phone** | Guarantor — “Phone number” | `guarantors.phone` | VARCHAR(20) | Required. |
| **Relationship** | Guarantor — “Relationship” | `guarantors.relationship` | VARCHAR(40) | e.g. Brother, Friend. |
| **Occupation** | Guarantor — “What they do” | `guarantors.occupation` | VARCHAR(120) | Optional. |

### 3.5 MFI choice step

| Input | Form / step | DB / storage | Type | Notes |
|-------|-------------|--------------|------|--------|
| **MFI** | MFI — dropdown “MFI” | `application_mfi.mfi_id` | INT | FK to `mfis.id`. |
| **Area** | MFI — hidden / context | `application_mfi.area` | VARCHAR(60) | Area at time of choice (e.g. MFI’s area_slug). |

### 3.6 Summary: one row per application (for a dataset)

For each application, a single conceptual row can be built from:

- **users (via application.user_id):** phone, full_name  
- **applications:** requested_amount, repayment_weeks, business_type, business_duration, business_location, ghana_card_front_path, ghana_card_back_path, status, submitted_at, notes (rejection reason if rejected)  
- **application_photos:** up to 3 file_path values (order by sort_order)  
- **guarantors:** full_name, phone, relationship, occupation  
- **application_mfi + mfis:** mfi_id, mfi name (join), area  

Actual image files live under `uploads/applications/{application_id}/` as described above. For a full list of fields and an example SQL export query, see **docs/DATASET-FIELDS.md**.

---

## 4. References

- **MVC and routing:** `docs/MVC-ARCHITECTURE.md`, `.cursor/rules/trustloan-mvc.mdc`
- **Dataset fields and export SQL:** `docs/DATASET-FIELDS.md`
- **Schema:** `settings/trustloan.sql`
- **Credit score logic:** `Classes/CreditScore.php`
- **Application flow and document upload:** `Controllers/ApplicationController.php`, `Classes/Application.php`
