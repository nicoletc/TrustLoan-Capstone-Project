# TrustLoan – All User-Collected Data (Dataset Reference)

Everything the app asks users to provide before they can qualify for a loan. Use this list to build your dataset (export from DB + files).

---

## 1. Sign-in / Account (before application)

| Field | Form label / source | DB table.column | Type | Notes |
|-------|---------------------|------------------|------|--------|
| **phone** | Phone number (Sign in) | `users.phone` | VARCHAR(20) | E.164 or national format; required to start flow |
| **full_name** | Your name / Create account | `users.full_name` | VARCHAR(120) | Set at first login or registration |
| **password** | Password | `users.password_hash` | VARCHAR(255) | Stored hashed only; not useful as raw feature |

*Verification code is used only to verify phone; not stored as part of user profile.*

---

## 2. Documents step

| Field | Form label / source | DB table.column | Type | Notes |
|-------|---------------------|------------------|------|--------|
| **ghana_card_front** | Ghana Card – Front | `applications.ghana_card_front_path` | VARCHAR(255) | Path to image file (e.g. `uploads/applications/{id}/ghana_front.jpg`) |
| **ghana_card_back** | Ghana Card – Back | `applications.ghana_card_back_path` | VARCHAR(255) | Path to image file |
| **business_type** | Type of business | `applications.business_type` | VARCHAR(60) | e.g. Market stall, tailoring |
| **business_duration** | How long have you run it? | `applications.business_duration` | VARCHAR(40) | e.g. 2 years |
| **business_location** | Business location | `applications.business_location` | VARCHAR(120) | e.g. Adenta Market |
| **photo_1** | Photo 1 | `application_photos.file_path` (sort_order=0) | File path | Stall, goods, or at work |
| **photo_2** | Photo 2 | `application_photos.file_path` (sort_order=1) | File path | Stall, goods, or at work |
| **photo_3** | Photo 3 (optional) | `application_photos.file_path` (sort_order=2) | File path | Optional |



---

## 3. Loan amount step

| Field | Form label / source | DB table.column | Type | Notes |
|-------|---------------------|------------------|------|--------|
| **requested_amount** | Amount (GH¢) | `applications.requested_amount` | DECIMAL(12,2) | Min 100, step 50 |
| **repayment_weeks** | Repayment period (weeks) | `applications.repayment_weeks` | TINYINT UNSIGNED | Default 12; min 4, max 52 |

---

## 4. Guarantor step

| Field | Form label / source | DB table.column | Type | Notes |
|-------|---------------------|------------------|------|--------|
| **guarantor_name** | Guarantor name | `guarantors.full_name` | VARCHAR(120) | e.g. Kofi Asante |
| **guarantor_phone** | Phone number | `guarantors.phone` | VARCHAR(20) | e.g. 024 412 3456 |
| **relationship** | Relationship | `guarantors.relationship` | VARCHAR(40) | e.g. Brother, Friend |
| **occupation** | What they do | `guarantors.occupation` | VARCHAR(120) | Optional; e.g. Trader, Teacher |

---

## 5. MFI choice step

| Field | Form label / source | DB table.column | Type | Notes |
|-------|---------------------|------------------|------|--------|
| **mfi_id** | MFI (dropdown) | `application_mfi.mfi_id` | INT | FK to `mfis.id` |
| **area** | Area at time of choice | `application_mfi.area` | VARCHAR(60) | User-selected area (e.g. from hidden input / MFI’s area_slug) |

---

## Summary: per-application dataset row (conceptual)

One row per **application** can include:

**From `users` (via `application.user_id`):**  
`phone`, `full_name`

**From `applications`:**  
`requested_amount`, `repayment_weeks`, `business_type`, `business_duration`, `business_location`,  
`ghana_card_front_path`, `ghana_card_back_path`, `status`, `submitted_at`, `notes` (rejection reason if rejected)

**From `application_photos`:**  
Up to 3 `file_path` values (order by `sort_order`)

**From `guarantors`:**  
`full_name`, `phone`, `relationship`, `occupation`

**From `application_mfi` + `mfis`:**  
`mfi_id`, `mfi_name` (join), `area`

**Actual image files:**  
Under `uploads/applications/{application_id}/` as listed above.

---

## SQL to export application-level data (for dataset)

```sql
-- One row per application with all user-provided fields (no image blobs; add file paths).
SELECT
  a.id AS application_id,
  a.user_id,
  u.phone AS borrower_phone,
  u.full_name AS borrower_name,
  a.requested_amount,
  a.repayment_weeks,
  a.business_type,
  a.business_duration,
  a.business_location,
  a.ghana_card_front_path,
  a.ghana_card_back_path,
  a.status,
  a.submitted_at,
  a.notes AS rejection_reason,
  g.full_name AS guarantor_name,
  g.phone AS guarantor_phone,
  g.relationship AS guarantor_relationship,
  g.occupation AS guarantor_occupation,
  m.name AS mfi_name,
  am.area AS mfi_area
FROM applications a
JOIN users u ON u.id = a.user_id
LEFT JOIN guarantors g ON g.application_id = a.id
LEFT JOIN application_mfi am ON am.application_id = a.id
LEFT JOIN mfis m ON m.id = am.mfi_id
ORDER BY a.submitted_at DESC;
```

**Photos:** Query `application_photos` by `application_id`, order by `sort_order`; each row has `file_path` (e.g. `uploads/applications/1/photo_0.jpg`). Resolve paths relative to project root to get the actual image files for your dataset.
