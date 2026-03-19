# TrustLoan – Coding Architecture

This document specifies all components of the system and, for each component, a listing of coding files with a one-sentence description and an **indicator** of how the code was developed.

---

## Indicators

| Indicator | Meaning |
|-----------|--------|
| **AI-only** | Code not core to the student’s work; may be boilerplate or tangential. Generated with AI without much scrutiny. The student will not be interrogated on this code but should be able to describe what the file does at a high level. |
| **AI+me** | Code developed with Generative AI assistance. The student is expected to **fully understand** the code. Inability to explain or justify any code in such a file will count against the student. |
| **Me-only** | Code developed without Generative AI assistance (e.g. straightforward or easy code where AI was not needed). The student should be ready to explain how AI could not help. As with AI+me, the student is expected to fully understand the code. |

**Allocation guide:** Easy, simple, or repetitive code (e.g. thin stubs, simple forms, basic styling) → **Me-only**. Moderate or more complex code (e.g. business logic, validation, multi-step flows) → **AI+me**. Tangential or boilerplate not central to the capstone → **AI-only**.

*Indicators should be agreed with your supervisor and updated as needed.*

---

## 1. Entry point & routing

| File | Description | Indicator |
|------|-------------|------------|
| **index.php** | Single entry point: routes POST by `action` to Actions, GET by `page` to Views, and enforces protected pages and multipart upload error handling. | AI+me |

---

## 2. Settings & bootstrap

| File | Description | Indicator |
|------|-------------|------------|
| **settings/core.php** | Session setup, inactivity timeout, auth helpers (`is_logged_in()`, `has_admin_privileges()`), and constants (e.g. TRUSTLOAN_DEV_MODE). | AI-only |
| **settings/db_class.php** | PDO database connection singleton and DB helper. | Me-only |
| **settings/db_cred.php** | Database credentials (host, user, password, database name); not committed with real secrets. | Me-only |

---

## 3. Database schema

| File | Description | Indicator |
|------|-------------|------------|
| **settings/trustloan.sql** | Full MySQL/MariaDB schema: tables (users, applications, guarantors, loans, repayments, credit_scores, admin, etc.), keys, and seed data. | AI+me |

---

## 4. Classes (business logic & data access)

| File | Description | Indicator |
|------|-------------|------------|
| **Classes/Application.php** | Application lifecycle, document paths, guarantor/MFI helpers, admin listing and single-application fetch, approval-congratulations flag. | AI+me |
| **Classes/User.php** | User lookup by phone/name, password verify, registration; phone normalization. | AI+me |
| **Classes/CreditScore.php** | Credit score calculation from loans and repayments (rule-based 0–100), persist to `credit_scores`, and breakdown for display. | AI+me |
| **Classes/Loan.php** | Loan create/fetch by application or user, repayment recording, and repayment listing. | AI+me |
| **Classes/Group.php** | Group fetch with members and meeting location. | AI+me |
| **Classes/VerificationCode.php** | Create and verify OTP/verification codes for phone sign-in. | Me-only |
| **Classes/AdminUser.php** | Admin user authentication and lookup. | AI-only |
| **Classes/AuditLog.php** | Write audit log entries for admin actions. | AI-only |
| **Classes/Settings.php** | Read/update admin settings (loan products, repayment, penalties, etc.). | AI-only |

---

## 5. Controllers

| File | Description | Indicator |
|------|-------------|------------|
| **Controllers/ApplicationController.php** | Handles document upload (Ghana Card, photos), loan amount, guarantor, and MFI submission; validates session, upload dir, and reupload flow; redirects. | AI+me |
| **Controllers/AuthController.php** | Handles send code, verify code, register, login (name/password and post-verify); session and redirects. | AI+me |

---

## 6. Actions (request entry points)

| File | Description | Indicator |
|------|-------------|------------|
| **Actions/send_verification_code.php** | Receives phone, calls AuthController to send verification code, then redirects. | AI-only |
| **Actions/verify_code.php** | Receives code, calls AuthController to verify, then redirects to login. | AI-only |
| **Actions/register_customer_action.php** | Receives name/password, calls AuthController to register, then redirects. | AI-only |
| **Actions/login_customer_action.php** | Receives password (post-verify), calls AuthController to log in. | AI-only |
| **Actions/login_with_name.php** | Receives name and password, calls AuthController for direct name+password login. | AI-only |
| **Actions/login_with_phone.php** | Thin entry for phone-based login flow. | AI-only |
| **Actions/logout_action.php** | Calls AuthController to destroy session and redirect. | Me-only |
| **Actions/submit_loan_amount.php** | Receives amount and weeks, calls ApplicationController::submitLoanAmount(). | Me-only |
| **Actions/submit_documents.php** | Receives form and files, calls ApplicationController::submitDocuments(). | AI+me |
| **Actions/submit_guarantor.php** | Receives guarantor fields, calls ApplicationController::submitGuarantor(). | Me-only |
| **Actions/submit_mfi.php** | Receives MFI choice, calls ApplicationController::submitMfi(). | Me-only |
| **Actions/fetch_applications_action.php** | Admin: fetches application list with filters (status, search), returns JSON. | AI+me |
| **Actions/fetch_application_action.php** | Admin: fetches single application with borrower, guarantor, photos, MFI, credit score; returns JSON. | AI+me |
| **Actions/update_application_status_action.php** | Admin: updates status (in progress/rejected) with required rejection reason; returns JSON. | AI+me |
| **Actions/approve_application_action.php** | Admin: approves application, creates loan and repayments, links group; returns JSON. | AI+me |
| **Actions/admin_login_action.php** | Admin login; returns JSON. | AI-only |
| **Actions/admin_logout_action.php** | Admin logout and redirect. | AI-only |
| **Actions/admin_register_action.php** | Admin user registration. | AI-only |
| **Actions/fetch_groups_action.php** | Admin: fetches groups list; returns JSON. | AI+me |
| **Actions/fetch_group_action.php** | Admin: fetches one group with members; returns JSON. | AI+me |
| **Actions/create_group_action.php** | Admin: creates a new group; returns JSON. | AI-only |
| **Actions/add_group_member_action.php** | Admin: adds member to group; returns JSON. | AI-only |
| **Actions/set_group_head_action.php** | Admin: sets group head; returns JSON. | AI-only |
| **Actions/fetch_loans_action.php** | Admin: fetches loans list; returns JSON. | AI+me |
| **Actions/fetch_repayments_action.php** | Admin: fetches repayments for a loan; returns JSON. | AI+me |
| **Actions/record_payment_action.php** | Admin: records a repayment; returns JSON. | AI-only |
| **Actions/fetch_guarantors_action.php** | Admin: fetches guarantors list; returns JSON. | AI+me |
| **Actions/fetch_settings_action.php** | Admin: fetches settings; returns JSON. | AI+me |
| **Actions/update_settings_action.php** | Admin: updates settings; returns JSON. | AI-only |
| **Actions/fetch_admin_stats_action.php** | Admin: fetches dashboard stats; returns JSON. | AI+me |
| **Actions/fetch_audit_logs_action.php** | Admin: fetches audit logs; returns JSON. | AI+me |
| **Actions/update_application_notes_action.php** | Admin: updates application notes (if used); returns JSON. | AI-only |

---

## 7. Borrower views (View/)

| File | Description | Indicator |
|------|-------------|------------|
| **View/landing.php** | Landing page markup and hero content. | Me-only |
| **View/overview.php** | “How it works” page. | AI-only |
| **View/signin.php** | Phone input and verification code form for sign-in. | AI+me |
| **View/login.php** | Name + password login or registration form. | AI+me |
| **View/documents.php** | Documents form: Ghana Card, business details, photos; re-upload flow and SweetAlert. | AI+me |
| **View/loan-amount.php** | Loan amount and repayment weeks form. | AI-only |
| **View/guarantor.php** | Guarantor name, phone, relationship, occupation form. | AI-only |
| **View/mfi.php** | MFI selection dropdown and submit. | AI-only |
| **View/waiting.php** | “Application submitted, we’ll contact you” message. | Me-only |
| **View/home.php** | Borrower dashboard: status, congratulations/rejection, links to steps and settings. | AI+me |
| **View/creditscore.php** | Credit score display (score, breakdown, “why”) for signed-in users. | AI+me |
| **View/settings.php** | Borrower settings page. | Me-only |
| **View/view_application_image.php** | Serves Ghana Card and application photos by id and type (security checks). | AI+me |
| **View/partials/header.php** | Shared header and navigation for borrower pages. | AI+me |

---

## 8. Admin

| File | Description | Indicator |
|------|-------------|------------|
| **Admin/index.php** | Admin router: requires admin auth, routes to dashboard, applicants, groups, loans, etc. | AI+me |
| **Admin/login.php** | Admin login form and session handling. | Me-only |
| **Admin/layout.php** | Admin layout wrapper, nav, and script includes. | Me-only |
| **Admin/pages/dashboard.php** | Admin dashboard page. | AI-only |
| **Admin/pages/applicants.php** | Applicants table, filters, and application detail drawer (documents, score, reject reason, approve/reject). | AI+me |
| **Admin/pages/verifications.php** | Verifications/queue page. | AI-only |
| **Admin/pages/loans.php** | Loans list and management. | AI-only |
| **Admin/pages/groups.php** | Groups list, create, members. | AI-only |
| **Admin/pages/guarantors.php** | Guarantors list. | AI-only |
| **Admin/pages/settings.php** | Admin settings (loan products, repayment, etc.). | AI-only |

---

## 9. Frontend – JavaScript

| File | Description | Indicator |
|------|-------------|------------|
| **js/main.js** | Global or shared client-side behaviour. | Me-only |
| **js/signin.js** | Sign-in form behaviour (e.g. phone, verify step). | AI+me |
| **js/password.js** | Password validation and hashing before submit (if used). | AI+me |
| **js/admin.js** | Admin helpers: postAction, SweetAlert wrappers, formatMoney, status/risk labels, used by admin pages. | AI+me |

---

## 10. Frontend – CSS

| File | Description | Indicator |
|------|-------------|------------|
| **Css/base.css** | Base layout, typography, and shared components. | Me-only |
| **Css/signin.css** | Sign-in and login form styles. | AI-only |
| **Css/landing.css** | Landing page styles. | AI-only |
| **Css/documents.css** | Documents page and upload layout. | AI+me |
| **Css/waiting.css** | Waiting (“application submitted”) page styles. | Me-only |
| **Css/mfi.css** | MFI selection page styles. | AI-only |
| **Css/home.css** | Dashboard and rejection/congratulations styles. | AI+me |
| **Css/creditscore.css** | Credit score page (score, breakdown, why). | AI-only |
| **Css/settings.css** | Borrower settings styles. | AI-only |
| **Css/overview.css** | How-it-works page styles. | AI-only |
| **Css/admin.css** | Admin layout, tables, drawer, filters. | AI+me |

---

## Summary by indicator

- **Me-only (mix across folders):** Code you wrote without AI: **settings/** (db_class.php, db_cred.php), **Classes/** (VerificationCode.php), **Actions/** (logout_action.php, submit_loan_amount.php, submit_guarantor.php, submit_mfi.php), **View/** (landing.php, waiting.php, settings.php), **Admin/** (login.php, layout.php), **Css/** (base.css, waiting.css), **js/** (main.js). You are expected to understand this code and to explain why AI was not needed.
- **AI+me (moderate / core):** Code developed with AI assistance that you must fully understand: index.php, trustloan.sql, core Classes (Application, User, CreditScore, Loan, Group), both Controllers, submit_documents and the four applicant-related actions, **fetch actions** (fetch_applications, fetch_application, fetch_groups, fetch_group, fetch_loans, fetch_repayments, fetch_guarantors, fetch_settings, fetch_admin_stats, fetch_audit_logs), views (signin, login, documents, home, creditscore, view_application_image, header partial), Admin index and applicants page, signin/password/admin JS, and documents/home/admin CSS. You must be able to explain or justify every part of these files.
- **AI-only (boilerplate / tangential):** Code not central to your capstone: core.php, AdminUser, AuditLog, Settings, all other Actions (auth stubs, create/update/record stubs), views (overview, loan-amount, guarantor, mfi), Admin dashboard and other admin pages, and most CSS (signin, landing, mfi, creditscore, settings, overview). Understand at a high level; you will not be interrogated on implementation detail.

You and your supervisor should review and adjust indicators to match how each file was actually produced.
