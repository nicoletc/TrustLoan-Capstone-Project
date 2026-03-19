# Admin Implementation – File List

**Default admin login (after running trustloan.sql):** `admin@trustloan.local` / `password` (change after first login).

## A) Admin Authentication
| File | Purpose |
|------|--------|
| **Classes/AdminUser.php** | Admin user model: getByEmail, getById, verifyPassword (admin_users table) |
| **settings/core.php** | Add is_admin_logged_in(), get_admin_user_id() |
| **Admin/login.php** | Admin login page (standalone; form posts to index.php?action=admin_login_action) |
| **Actions/admin_login_action.php** | Authenticate admin_users, set session, redirect to Admin/ |
| **Actions/admin_logout_action.php** | Clear admin session, redirect to Admin/login.php |
| **Admin/index.php** | Guard: if !is_admin_logged_in() redirect to Admin/login.php |
| **Admin/layout.php** | Add Sign out link (admin_logout_action) |

## B) Applications Management
| File | Purpose |
|------|--------|
| **Actions/fetch_admin_stats_action.php** | JSON: dashboard stats (verifications, applicants, active loans, charts data) |
| **Classes/AuditLog.php** | add(admin_user_id, action, entity_type, entity_id, details) |
| **Classes/Application.php** | Add: getAllForAdmin(filters), getByIdWithDetails(id), updateStatus, updateNotes |
| **Classes/Loan.php** | create(application_id, user_id, group_id, amount, weeks), createRepaymentSchedule, getByUser, getRepayments |
| **Actions/fetch_applications_action.php** | JSON: list applications (filters: status, date, search) |
| **Actions/fetch_application_action.php** | JSON: single application with user, guarantor, photos, mfi |
| **Actions/update_application_status_action.php** | Mark in_progress or reject; set reviewed_at, officer_id; audit |
| **Actions/update_application_notes_action.php** | Update notes field |
| **Actions/approve_application_action.php** | Assign group (or create), create loan + repayments, update application, audit |

## C) Groups Management
| File | Purpose |
|------|--------|
| **Classes/Group.php** | create, getByMfi, getByIdWithMembers, addMember, setHead, updateMeetingLocation |
| **Actions/fetch_groups_action.php** | JSON: list groups with member count, repayment_status |
| **Actions/fetch_group_action.php** | JSON: single group with members, meeting location |
| **Actions/create_group_action.php** | Create group under MFI |
| **Actions/update_group_action.php** | Update name, repayment_status, meeting location/day/time |
| **Actions/add_group_member_action.php** | Add borrower to group (only if application approved) |
| **Actions/set_group_head_action.php** | Set member as group head |

## D) Repayments Tracking
| File | Purpose |
|------|--------|
| **Actions/fetch_repayments_action.php** | JSON: upcoming/overdue repayments by date |
| **Actions/record_payment_action.php** | Mark repayment paid; update loan balance_remaining, status |
| **Classes/Loan.php** | (above) recordPayment, updateBalanceAndStatus |

## E) Settings (CRUD)
| File | Purpose |
|------|--------|
| **Actions/fetch_settings_action.php** | JSON: one type (loan_products, repayment, penalties, risk, notifications) |
| **Actions/update_settings_action.php** | Update settings by type (key/value or table rows) |

## F) Audit Logs
| File | Purpose |
|------|--------|
| **Actions/fetch_audit_logs_action.php** | JSON: audit log list |

## Admin Pages (wire to real data)
| File | Changes |
|------|--------|
| **Admin/pages/applicants.php** | Fetch applications via API, filters, view detail, approve/reject/notes |
| **Admin/pages/dashboard.php** | Fetch stats, charts from real data |
| **Admin/pages/verifications.php** | Same as applicants filtered by new, or link |
| **Admin/pages/loans.php** | Fetch active/overdue loans, repayments |
| **Admin/pages/groups.php** | Fetch groups, create group, members, set head |
| **Admin/pages/guarantors.php** | Fetch guarantors from DB |
| **Admin/pages/settings.php** | Settings CRUD, audit table from API |

## JS
| File | Purpose |
|------|--------|
| **js/admin.js** | Shared: baseUrl, fetch helpers, table fill, modals/drawers |
| **Admin pages** | Inline or per-page: call fetch_*_action, fill tables, button handlers |

## SQL
| File | Purpose |
|------|--------|
| **settings/trustloan.sql** | Add seed INSERT for one admin_users row (optional; or run setup script) |
