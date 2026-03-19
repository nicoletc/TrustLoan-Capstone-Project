# TrustLoan – MVC Architecture & Tech Stack

This project is a **microfinance credit assessment application** for Ghana's informal sector. It uses **strict MVC architecture** and a single, consistent tech stack.

---

## 0. TECH STACK (USE THIS ONLY – STAY CONSISTENT)

| Layer | Technology | Do NOT use |
|-------|------------|------------|
| **Backend** | **PHP** (plain PHP, no framework) | Laravel, Symfony, Slim, etc. |
| **Database** | MySQL/MariaDB via PDO | Raw mysqli, ORMs |
| **Frontend markup** | **HTML** (from PHP Views) | Blade, Twig, JSX |
| **Frontend styles** | **Plain CSS** | Sass, Tailwind, CSS-in-JS |
| **Frontend behaviour** | **Vanilla JavaScript** | React, Vue, Angular, jQuery |
| **Libraries (JS)** | CDN only: intl-tel-input, SweetAlert2 | npm bundles unless we add a build |
| **PHP dependencies** | None (no Composer) | Composer packages unless we introduce it |

- **One backend language:** PHP only.
- **One frontend script approach:** Vanilla JS only; no JS frameworks.
- **Password:** Validation and SHA-256 hashing in `js/password.js`; server stores bcrypt(hash).

---

## 1. FOLDER RESPONSIBILITIES

| Folder | Purpose |
|--------|---------|
| **index.php** | Entry point. Route POST actions first, then load View by `page`. |
| **settings/** | core.php (session, is_logged_in, TRUSTLOAN_DEV_MODE), db_cred.php, db_class.php, trustloan.sql |
| **Classes/** | User, VerificationCode, Application. Business logic and data; no HTML, no HTTP. |
| **Controllers/** | AuthController, ApplicationController. Request handling and redirects only. |
| **Actions/** | One file per action (send_verification_code, verify_code, register_customer_action, login_customer_action, submit_documents, etc.). Call Controller; no business logic. |
| **View/** | One PHP file per page (landing, signin, login, documents, loan-amount, guarantor, mfi, waiting, home, etc.). Presentation only. |
| **Css/** | base.css, signin.css, landing.css, mfi.css, admin.css. |
| **js/** | main.js, signin.js, password.js. Validation and hashing in js folder. |
| **Admin/** | Admin layout and pages (dashboard, verifications, applicants, loans, groups, guarantors, settings). |
| **Images/** | Static assets. |

---

## 2. CORE RULES

- **Controllers:** Handle requests and coordination only. Call Classes. No SQL, no HTML.
- **Classes (Models):** All business logic and data access. No HTML, no HTTP.
- **Views:** Presentation only. No SQL, no financial calculations.
- **Actions:** Receive request → call Controller → redirect or return. No business logic.
- **No SQL in Views. No HTML in Controllers.**

---

## 3. AUTH FLOW

1. **Sign in:** Phone → send code (stored in DB; dev mode shows code in session on sign-in page) → verify code → redirect to **login**.
2. **New user:** Full name + password (validated and hashed in js/password.js) → register → redirect to **documents**.
3. **Existing user:** Password (hashed in JS) → login → redirect to **home**.
4. **Protected pages:** documents, loan-amount, guarantor, mfi, waiting, home require `is_logged_in()`; else redirect to signin.

---

## 4. APPLICATION FLOW (POST LOGIN)

Documents → Loan amount → Guarantor → MFI → Waiting. Each step POSTs to an action and redirects to the next page.
