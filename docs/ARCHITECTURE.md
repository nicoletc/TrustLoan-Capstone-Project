# TrustLoan — Application & system architecture

TrustLoan is a microfinance credit-assessment web app for Ghana’s informal sector: borrowers complete an application online; staff review, approve, or reject in an admin area; and an optional **ML scoring microservice** produces a probability-of-default (PD) that the PHP app turns into a **0–100 score** for borrowers and officers.

This document replaces the former **System** and **App architecture** split into one place: **how the app is structured**, **how scoring works today**, **what data you collect**, and **how PHP talks to MySQL and the ML service**.

---

## 1. System overview

| Layer | Role |
| --- | --- |
| **PHP (Apache / XAMPP)** | Routing, sessions, borrower + admin UI, business rules, PDO → MySQL |
| **MySQL / MariaDB** | Users, applications, documents paths, guarantors, MFIs, groups, loans, repayments, credit_scores, admin users, audit |
| **Python FastAPI (`ml_service/`)** | `POST /score`: PD + decision + routing metadata from trained artifacts |
| **Browser** | Server-rendered PHP views, plain CSS/JS (no PHP framework, no npm build) |

**Integration pattern:** PHP calls the ML service over HTTP (`MlScoringClient`). There is no embedded Python in PHP. If the ML URL is empty or the service is down, the UI falls back to the **last stored** row in `credit_scores` when available.

---

## 2. Entry points and routing

### 2.1 Borrower app — `index.php`

1. **Bootstrap:** `settings/core.php`, `settings/db_class.php`.
2. **POST + `action`:** `action` is sanitized to `[a-z0-9_]` and loads `Actions/{action}.php` if the file exists; the action typically calls a **Controller** method and redirects. No main layout for that path.
3. **GET `page`:** Default `landing`. Includes `View/{page}.php` from a `switch` (e.g. `documents`, `creditscore`, `home`).
4. **Protected pages** (require borrower `is_logged_in()`): `documents`, `loan-amount`, `guarantor`, `mfi`, `waiting`, `home`, `settings` — else redirect to `?page=signin`.
5. **Special:** `page=view_application_image` serves Ghana Card / photo files via `View/view_application_image.php` (not the standard shell).
6. **Upload size:** If `post_max_size` is exceeded on multipart POST, empty `$_POST`/`$_FILES` is detected and the user is redirected to `documents` with an error message.

### 2.2 Admin app — `Admin/index.php`

- Requires `is_admin_logged_in()`; otherwise redirect to `Admin/login.php`.
- `?page=` must be in the whitelist: `dashboard`, `verifications`, `applicants`, `loans`, `groups`, `guarantors`, `settings`.
- Loads `Admin/pages/{page}.php` into `Admin/layout.php`.

### 2.3 MVC-style layout (no framework)

| Area | Responsibility |
| --- | --- |
| **`Classes/`** | Domain + data access (SQL, rules). No HTML, no HTTP headers. |
| **`Controllers/`** | Orchestrate POST flows, redirects; call Classes only. |
| **`Actions/`** | Thin includes: parse input → controller → exit/redirect. |
| **`View/`** | Borrower HTML; minimal PHP for variables. |
| **`Admin/`** | Admin layout + pages; same backend Classes. |
| **`settings/`** | `core.php` (session, constants e.g. `TRUSTLOAN_ML_SCORING_URL`), `db_class.php`, credentials, `trustloan.sql`, `migrations/`. |

---

## 3. Borrower journey (data flow)

1. **Sign-in / account** — phone verification, then name + password → `users`.
2. **Documents** — Ghana Card front/back, business type / duration / location, 2–3 photos → `applications`, `application_photos`, files under `uploads/applications/{application_id}/`.
3. **Loan amount** — requested amount (GH¢), repayment weeks → `applications`.
4. **Guarantor** — name, phone, relationship, occupation → `guarantors`.
5. **MFI** — selected MFI + area context → `application_mfi` (+ `mfis`).
6. **Waiting / Home** — status-driven dashboard (submitted, in progress, approved, rejected).

Each step POSTs to a named action and redirects to the next page or home.

**Public (no login):** `landing`, `overview`, `creditscore` (prompts sign-in if needed), `signin`, `login`.

---

## 4. Admin journey (summary)

- **Applicants:** list/filter applications; detail drawer loads JSON from `Actions/fetch_application_action.php` (application + borrower + guarantor + MFI + paths to uploads). Approve/reject flows use dedicated actions (e.g. `approve_application_action.php`).
- **Credit fields on fetch:** `credit_score`, `credit_score_calculated_at`, `ml_scoring` (raw ML JSON when available), `ml_service_unreachable` (boolean).
- Other pages (loans, groups, guarantors, settings, verifications) follow the same admin router pattern.

---

## 5. Credit scoring (as implemented)

### 5.1 Behavior

- **Source of truth for a “live” score:** When `TRUSTLOAN_ML_SCORING_URL` is **non-empty**, `CreditScore::getForDisplay()` calls `MlScoringClient::scoreForBorrower($userId)`, reads `pd` from the response, converts with **`score = round((1 - pd) * 100)`** (clamped 0–100), and **persists** via `CreditScore::save()`.
- **ML disabled:** If the constant is **empty**, the app shows **only** what is already in `credit_scores` (no HTTP call).
- **ML unreachable / error:** If the HTTP call fails, the UI uses the **last stored** score when present and sets **`ml_service_unreachable`** so the front end can show “approximate / last saved” messaging.

### 5.2 Labels and borrower copy

- Word labels from **score bands:** ≥85 Excellent, ≥70 Good, ≥55 Fair, otherwise Poor (see `CreditScore::labelForScore()`).
- Risk tier, tips, and short blurbs are derived from the same score (borrower vs admin wording via `getForDisplay(..., $forAdmin)`).

### 5.3 Features sent to the model

`Application::buildMlFeaturesForBorrower()` builds a dict aligned with `ml_service/exports/feature_columns.json`. **Heavily populated from the latest application + user today:** e.g. requested amount, term (weeks stored as `loan_term_months` key), business type/location → `employment_type` / `region`, MFI `area_slug` when present, account age from `users.created_at`, `has_mobile_money` from phone length. **Many other keys** are honest **placeholders (0 / `unknown`)** until you wire more fields or bureau data — the pipeline still runs for demos and routing experiments.

**Routing label count:** `Application::countLabeledApplicationsApprox()` (approved + rejected applications) is sent as `n_labeled` so the Python layer can pick zero-shot / one-shot / supervised modes (`ml_service/app/scoring.py`, thresholds in `ml_service/app/config.py`).

### 5.4 Optional HTTP surface

- **`api/ml_score.php`** — JSON proxy to the same scoring path (borrower session or admin session; admin may pass `user_id` in the body). Useful for fetch/XHR without duplicating client code.

---

## 6. ML service (`ml_service/`)

- **App:** `app/main.py` (FastAPI).
- **Endpoints:** `GET /health`, `POST /score`.
- **Core logic:** `app/scoring.py` — load preprocessor, support set, pipelines; align features; `route_model(n_labeled)` → `zero_shot_proto` | `one_shot_proto` | `supervised` (XGBoost or logistic regression per `preferred_supervised`).
- **Artifacts (default `ml_service/exports/`):** `preprocessor.pkl`, `xgboost_pipeline.pkl`, `lr_pipeline.pkl`, `support_X.parquet`, `support_y.parquet`, `feature_columns.json`. Override path with env `TRUSTLOAN_ML_EXPORTS`.
- **Thresholds:** `TRUSTLOAN_N_ZERO_SHOT_MAX`, `TRUSTLOAN_N_ONE_SHOT_MAX` (see `app/config.py`; defaults e.g. 0 and 50).

---

## 7. Schema and migrations

- **Full baseline:** `settings/trustloan.sql`
- **Incremental SQL:** `settings/migrations/` (run in your environment as needed)

---

## 8. Deployment shape (local / typical student setup)

- Apache + PHP (XAMPP) serving the repo `htdocs` path  
- MySQL (XAMPP)  
- ML API: `uvicorn` on e.g. `http://127.0.0.1:8000`, matching `TRUSTLOAN_ML_SCORING_URL` in `settings/core.php`

---

## 9. Inputs collected (borrower-facing)

Use these for dataset design and for extending `buildMlFeaturesForBorrower()`.

### 9.1 Account (sign-in / registration)

| Input | Storage | Notes |
| --- | --- | --- |
| Phone | `users.phone` | Verified with code |
| Full name | `users.full_name` | |
| Password | `users.password_hash` | Hashed; not a model feature |

### 9.2 Documents

| Input | Storage |
| --- | --- |
| Ghana Card front / back | `applications.ghana_card_*_path`; files under `uploads/applications/{id}/` |
| Business type, duration, location | `applications.business_*` |
| Photos (2 required, 3 optional) | `application_photos.file_path`, `sort_order` |

Allowed image types are enforced in upload logic (e.g. common web image extensions).

### 9.3 Loan amount

| Input | Storage |
| --- | --- |
| Amount (GH¢) | `applications.requested_amount` |
| Repayment weeks | `applications.repayment_weeks` |

### 9.4 Guarantor

| Input | Storage |
| --- | --- |
| Name, phone, relationship, occupation | `guarantors.*` |

### 9.5 MFI

| Input | Storage |
| --- | --- |
| Selected MFI | `application_mfi.mfi_id` → `mfis` |
| Area / slug context | Via `mfis.area_slug` (used in ML `region` when linked) |

### 9.6 One conceptual dataset row per application

Join `users` → `applications` → optional `application_photos`, `guarantors`, `application_mfi` / `mfis`; add paths to files under `uploads/applications/{application_id}/`. Labels for supervised training typically come from final **approved/rejected** outcome (plus timestamps) as stored on the application and related tables.

---

## 10. Related documentation

| File | Contents |
| --- | --- |
| [MVC-ARCHITECTURE.md](MVC-ARCHITECTURE.md) | MVC conventions in more detail |
| [README.md](README.md) | Index of docs in `docs/` |

**Primary code references:** `index.php`, `Admin/index.php`, `Classes/Application.php`, `Classes/CreditScore.php`, `Classes/MlScoringClient.php`, `api/ml_score.php`, `ml_service/app/main.py`, `ml_service/app/scoring.py`.
