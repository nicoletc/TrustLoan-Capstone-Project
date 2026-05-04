# TrustLoan – MVC architecture & tech stack

Microfinance credit assessment for Ghana’s informal sector: **borrower MVC** (`index.php` + `View/`) plus a separate **Admin** area (`Admin/`) sharing the same `Classes/` and PDO layer. Same stack rules apply to new PHP UI code.

---

## 0. Tech stack

| Layer | In this project |
| --- | --- |
| **Web application** | Plain **PHP** (no framework), borrower entry `index.php`, admin under `Admin/` |
| **Database** | **MySQL/MariaDB** via **PDO** in `settings/db_class.php` |
| **Markup** | **HTML** emitted from PHP views (`View/`, `Admin/pages/`) |
| **Styles** | **Plain CSS** in `Css/` (e.g. `base`, `signin`, `landing`, `documents`, `creditscore`, `admin`, …) |
| **Client scripts** | **Vanilla JavaScript** in `js/` (e.g. `main.js`, `signin.js`, `password.js`, `admin.js`) |
| **Front-end libraries (CDN)** | **intl-tel-input** (phone), **SweetAlert2** (dialogs) where those pages load them |
| **PHP dependencies** | None — no Composer for the web app |
| **Credit scoring service (optional)** | **`ml_service/`**: **Python**, **FastAPI**; PHP calls it over HTTP when `TRUSTLOAN_ML_SCORING_URL` is set in `settings/core.php` ([ARCHITECTURE.md](ARCHITECTURE.md)) |

**Passwords (borrower `users` table):** The browser uses `js/password.js` for **length / match validation** only. **`register_customer_action`** → `User::create()` receives the **plain** password over HTTPS-in-production and stores **`password_hash(..., PASSWORD_DEFAULT)`**. **`login_customer_action`** and name-based flows use **`User::verifyPassword()`** (`password_verify`). A **legacy** path can still verify very old hashes derived from SHA-256 in PHP—do **not** send only a client SHA-256 as the shipped login path today.

---

## 1. Folder responsibilities

| Location | Responsibility |
| --- | --- |
| **`index.php`** | Borrower entry: POST `action` → `Actions/{action}.php`, then GET `page` → `View/{page}.php`; protected-route guard; multipart size guard |
| **`settings/`** | `core.php` (session, timeouts, constants e.g. `TRUSTLOAN_DEV_MODE`, `TRUSTLOAN_ML_SCORING_URL`), `db_class.php`, credentials, **`trustloan.sql`**, **`migrations/`** |
| **`Classes/`** | Domain + data access only (e.g. `User`, `Application`, `Loan`, `Group`, `CreditScore`, `VerificationCode`, `AdminUser`, `AuditLog`, `MlScoringClient`, …). No HTML, no `header()` redirects |
| **`Controllers/`** | **`AuthController`**, **`ApplicationController`** — borrower flows: parse intent, call Classes, redirect. No SQL here, no HTML output |
| **`Actions/`** | One file per `action` value. **Borrower** actions typically delegate to controllers; **admin JSON** actions often include `core.php`, gate with `is_admin_logged_in()`, call `Classes/*` directly, then `echo` JSON |
| **`View/`** | Borrower pages + `partials/` (e.g. header). **`view_application_image.php`** serves uploads for `?page=view_application_image`. Presentation only |
| **`Css/`** | Shared and page CSS: `base`, `landing`, `signin`, `overview`, `documents`, `loan-amount`/flows via `home`, `mfi`, `waiting`, `creditscore`, `settings`, **`admin`** (for borrower-facing admin-named styles where used), … |
| **`js/`** | `main.js`, `signin.js`, `password.js`, **`admin.js`**, … — validation and light UI only |
| **`Admin/`** | `Admin/index.php` router, `layout.php`, `login.php`, `pages/*.php` — staff UI |
| **`api/`** | Small JSON endpoints (e.g. ML proxy) alongside the MVC pattern |
| **`uploads/`** | Borrower uploads (e.g. `uploads/applications/{application_id}/`), not edited in MVC docs |

Static images may live beside views or branding assets as the project adds them—there is no single required top-level **`Images/`** folder for code to rely on.

---

## 2. Core rules

- **Controllers:** Request coordination only → call **`Classes`**; no SQL strings, no HTML.
- **Classes:** All reusable business logic and DB access used by controllers/actions.
- **Views:** Presentation only—no PDO, no domain rules beyond display formatting.
- **Actions:** Glue only; borrower actions **prefer** a controller method; heavier admin endpoints may orchestrate Classes in-file.
- **No SQL in views. No full page HTML templates inside controllers.**

---

## 3. Borrower auth flow

1. **Sign-in (phone):** POST `send_verification_code` → if that phone already exists as a user, redirected to **`login`** with **`existing_member`** (no new code sent); otherwise **`VerificationCode`**, **`pending_phone`**, then sign-in **`step=code`**.
2. **`verify_code`:** Valid → existing user (`pending_login_user_id`) to **`login`**, **`existing=1`**, or new user → **`login`** with register UI.
3. **Register:** `register_customer_action` → **`AuthController::register`** → **`User::create`**, session, redirect **`documents`**.
4. **Login (verified phone path):** `login_customer_action` → **`AuthController::login`** (password checked with **`User::verifyPassword`**), redirect **`home`**.
5. **Direct login (`login_with_name` / similar):** name + password; same verify path.
6. **Protected `page`s** (guest → **`signin`**): **`documents`**, **`loan-amount`**, **`guarantor`**, **`mfi`**, **`waiting`**, **`home`**, **`settings`**.

**Roles:** Borrower session uses **`user_id`** and **`role`** (2 = customer, 1 = admin **only if** someone uses borrower session as admin—which is discouraged). Staff use **`Admin/login.php`** and **`admin_user_id`** in session.

---

## 4. Application flow (after borrower login)

**Documents → Loan amount → Guarantor → MFI → Waiting** → **Home** each step POSTs an action (`submit_documents`, `submit_loan_amount`, …) and redirects forward.

Routing, scoring, ML, and schema detail: **[ARCHITECTURE.md](ARCHITECTURE.md)**.
