# TTMed — Database Design

## Strategy

Two-tier MySQL architecture:

1. **`ttmed_platform`** — Single central database. Tenant registry, subscriptions, platform admins.
2. **`ttmed_{tenant_code}`** — One database per medical centre. All clinical and operational data.

Tenant databases share the same schema definition (Laravel migrations in `database/migrations/tenant/`) applied independently to each tenant when provisioned.

---

## Platform Database (`ttmed_platform`)

### `tenants`
```sql
id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
uuid            CHAR(36) UNIQUE NOT NULL
code            VARCHAR(20) UNIQUE NOT NULL        -- login identifier e.g. MEDSOUTH
name            VARCHAR(150) NOT NULL
business_name   VARCHAR(150)
database_name   VARCHAR(100) UNIQUE NOT NULL
db_host         VARCHAR(100) NOT NULL DEFAULT 'localhost'
db_port         SMALLINT UNSIGNED NOT NULL DEFAULT 3306
db_username     VARCHAR(100) NOT NULL
db_password     TEXT NOT NULL                      -- AES encrypted
status          ENUM('active','suspended','deactivated','trial') NOT NULL DEFAULT 'trial'
subscription_id BIGINT UNSIGNED
storage_path    VARCHAR(255)                       -- absolute path to tenant file store
created_at      TIMESTAMP
updated_at      TIMESTAMP
```

### `subscription_plans`
```sql
id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
name            VARCHAR(100) NOT NULL
slug            VARCHAR(50) UNIQUE NOT NULL
features        JSON NOT NULL                      -- module flags
user_limit      SMALLINT UNSIGNED                  -- NULL = unlimited
storage_limit_gb SMALLINT UNSIGNED
monthly_price   DECIMAL(10,2)
annual_price    DECIMAL(10,2)
is_active       TINYINT(1) NOT NULL DEFAULT 1
created_at      TIMESTAMP
updated_at      TIMESTAMP
```

### `subscriptions`
```sql
id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
tenant_id       BIGINT UNSIGNED NOT NULL REFERENCES tenants(id)
plan_id         BIGINT UNSIGNED NOT NULL REFERENCES subscription_plans(id)
status          ENUM('active','expired','cancelled','suspended') NOT NULL
billing_cycle   ENUM('monthly','annual')
starts_at       DATE NOT NULL
expires_at      DATE NOT NULL
amount          DECIMAL(10,2)
notes           TEXT
created_at      TIMESTAMP
updated_at      TIMESTAMP
```

### `platform_users`
```sql
id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
uuid            CHAR(36) UNIQUE NOT NULL
name            VARCHAR(150) NOT NULL
email           VARCHAR(150) UNIQUE NOT NULL
password        VARCHAR(255) NOT NULL
role            ENUM('super_admin','admin','support') NOT NULL
is_active       TINYINT(1) NOT NULL DEFAULT 1
mfa_enabled     TINYINT(1) NOT NULL DEFAULT 0
mfa_secret      VARCHAR(100)
last_login_at   TIMESTAMP
last_login_ip   VARCHAR(45)
created_at      TIMESTAMP
updated_at      TIMESTAMP
```

### `platform_audit_logs`
```sql
id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
user_id         BIGINT UNSIGNED
action          VARCHAR(100) NOT NULL
resource_type   VARCHAR(100)
resource_id     BIGINT UNSIGNED
tenant_id       BIGINT UNSIGNED
description     TEXT
old_values      JSON
new_values      JSON
ip_address      VARCHAR(45)
user_agent      TEXT
created_at      TIMESTAMP
```

---

## Tenant Database (per medical centre)

All tables below exist in each tenant's isolated database. No cross-database foreign keys exist.

---

### Users and Access

#### `users`
```sql
id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
uuid                CHAR(36) UNIQUE NOT NULL
branch_id           BIGINT UNSIGNED REFERENCES branches(id)
username            VARCHAR(50) NOT NULL UNIQUE
email               VARCHAR(150) UNIQUE
name                VARCHAR(150) NOT NULL
password            VARCHAR(255) NOT NULL
is_active           TINYINT(1) NOT NULL DEFAULT 1
must_change_password TINYINT(1) NOT NULL DEFAULT 0
mfa_enabled         TINYINT(1) NOT NULL DEFAULT 0
mfa_secret          VARCHAR(100)
last_login_at       TIMESTAMP
last_login_ip       VARCHAR(45)
password_changed_at TIMESTAMP
created_by          BIGINT UNSIGNED
created_at          TIMESTAMP
updated_at          TIMESTAMP
deleted_at          TIMESTAMP
```

#### `roles`
```sql
id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
name        VARCHAR(100) NOT NULL
slug        VARCHAR(100) UNIQUE NOT NULL
description TEXT
is_system   TINYINT(1) NOT NULL DEFAULT 0
created_at  TIMESTAMP
updated_at  TIMESTAMP
```

#### `permissions`
```sql
id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
slug        VARCHAR(150) UNIQUE NOT NULL     -- e.g. patients.create
module      VARCHAR(50) NOT NULL
name        VARCHAR(100) NOT NULL
description TEXT
created_at  TIMESTAMP
```

#### `role_permissions`
```sql
role_id       BIGINT UNSIGNED REFERENCES roles(id)
permission_id BIGINT UNSIGNED REFERENCES permissions(id)
PRIMARY KEY (role_id, permission_id)
```

#### `user_roles`
```sql
user_id   BIGINT UNSIGNED REFERENCES users(id)
role_id   BIGINT UNSIGNED REFERENCES roles(id)
PRIMARY KEY (user_id, role_id)
```

#### `user_sessions`
```sql
id          VARCHAR(100) PRIMARY KEY
user_id     BIGINT UNSIGNED REFERENCES users(id)
ip_address  VARCHAR(45)
user_agent  TEXT
payload     LONGTEXT
last_activity INT UNSIGNED
```

---

### Medical Centre Configuration

#### `branches`
```sql
id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
name        VARCHAR(150) NOT NULL
address     TEXT
city        VARCHAR(100)
phone       VARCHAR(30)
email       VARCHAR(150)
is_main     TINYINT(1) NOT NULL DEFAULT 0
is_active   TINYINT(1) NOT NULL DEFAULT 1
created_at  TIMESTAMP
updated_at  TIMESTAMP
```

#### `centre_settings`
```sql
id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
branch_id   BIGINT UNSIGNED REFERENCES branches(id)
key         VARCHAR(100) NOT NULL
value       TEXT
type        ENUM('string','boolean','integer','json','encrypted') NOT NULL DEFAULT 'string'
UNIQUE KEY (branch_id, key)
updated_at  TIMESTAMP
```

#### `service_catalogue`
```sql
id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
branch_id   BIGINT UNSIGNED REFERENCES branches(id)
code        VARCHAR(30)
name        VARCHAR(150) NOT NULL
category    VARCHAR(100)
description TEXT
price       DECIMAL(10,2) NOT NULL
tax_rate    DECIMAL(5,2) NOT NULL DEFAULT 0.00
is_taxable  TINYINT(1) NOT NULL DEFAULT 0
is_active   TINYINT(1) NOT NULL DEFAULT 1
created_at  TIMESTAMP
updated_at  TIMESTAMP
```

---

### Staff Profiles

#### `staff_profiles`
```sql
id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
user_id         BIGINT UNSIGNED UNIQUE REFERENCES users(id)
employee_number VARCHAR(30)
department      VARCHAR(100)
title           VARCHAR(50)
phone           VARCHAR(30)
joined_at       DATE
is_active       TINYINT(1) NOT NULL DEFAULT 1
created_at      TIMESTAMP
updated_at      TIMESTAMP
```

#### `doctors`
```sql
id                    BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
user_id               BIGINT UNSIGNED UNIQUE REFERENCES users(id)
registration_number   VARCHAR(50)
specialty             VARCHAR(100)
qualifications        VARCHAR(255)
clinic_name           VARCHAR(150)
clinic_address        TEXT
clinic_phone          VARCHAR(30)
clinic_email          VARCHAR(150)
signature_path        VARCHAR(255)
stamp_path            VARCHAR(255)
prescription_header   TEXT
prescription_footer   TEXT
logo_path             VARCHAR(255)
is_active             TINYINT(1) NOT NULL DEFAULT 1
created_at            TIMESTAMP
updated_at            TIMESTAMP
```

---

### Patients

#### `patients`
```sql
id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
uuid                CHAR(36) UNIQUE NOT NULL
patient_number      VARCHAR(20) UNIQUE NOT NULL
branch_id           BIGINT UNSIGNED REFERENCES branches(id)
first_name          VARCHAR(100) NOT NULL
middle_name         VARCHAR(100)
last_name           VARCHAR(100) NOT NULL
date_of_birth       DATE
gender              ENUM('male','female','other','not_stated')
address             TEXT
community           VARCHAR(150)
parish              VARCHAR(100)
phone               VARCHAR(30)
alt_phone           VARCHAR(30)
email               VARCHAR(150)
occupation          VARCHAR(150)
insurance_provider_id BIGINT UNSIGNED REFERENCES insurance_providers(id)
insurance_policy    VARCHAR(100)
registered_by       BIGINT UNSIGNED REFERENCES users(id)
photo_path          VARCHAR(255)
notes               TEXT
is_active           TINYINT(1) NOT NULL DEFAULT 1
created_at          TIMESTAMP
updated_at          TIMESTAMP
deleted_at          TIMESTAMP
INDEX (last_name, first_name)
INDEX (phone)
INDEX (date_of_birth)
INDEX (patient_number)
```

#### `patient_contacts`
```sql
id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
patient_id      BIGINT UNSIGNED REFERENCES patients(id)
type            ENUM('emergency','next_of_kin','guardian','other')
name            VARCHAR(150) NOT NULL
relationship    VARCHAR(100)
phone           VARCHAR(30)
alt_phone       VARCHAR(30)
email           VARCHAR(150)
address         TEXT
is_primary      TINYINT(1) NOT NULL DEFAULT 0
created_at      TIMESTAMP
updated_at      TIMESTAMP
```

#### `patient_allergies`
```sql
id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
patient_id      BIGINT UNSIGNED REFERENCES patients(id)
allergen        VARCHAR(150) NOT NULL
allergen_type   ENUM('medication','food','environmental','other')
reaction_type   VARCHAR(150)
severity        ENUM('mild','moderate','severe','life_threatening')
notes           TEXT
recorded_by     BIGINT UNSIGNED REFERENCES users(id)
recorded_at     TIMESTAMP
is_active       TINYINT(1) NOT NULL DEFAULT 1
created_at      TIMESTAMP
updated_at      TIMESTAMP
```

#### `patient_conditions`
```sql
id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
patient_id      BIGINT UNSIGNED REFERENCES patients(id)
condition       VARCHAR(255) NOT NULL
icd10_code      VARCHAR(10)
diagnosed_at    DATE
status          ENUM('active','resolved','managed','inactive') NOT NULL DEFAULT 'active'
notes           TEXT
recorded_by     BIGINT UNSIGNED REFERENCES users(id)
created_at      TIMESTAMP
updated_at      TIMESTAMP
```

#### `patient_medications`
```sql
id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
patient_id          BIGINT UNSIGNED REFERENCES patients(id)
medication_name     VARCHAR(255) NOT NULL
dose                VARCHAR(100)
frequency           VARCHAR(100)
route               VARCHAR(50)
prescribing_doctor  VARCHAR(150)
start_date          DATE
end_date            DATE
is_current          TINYINT(1) NOT NULL DEFAULT 1
notes               TEXT
recorded_by         BIGINT UNSIGNED REFERENCES users(id)
created_at          TIMESTAMP
updated_at          TIMESTAMP
```

#### `patient_medical_histories`
```sql
id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
patient_id      BIGINT UNSIGNED REFERENCES patients(id)
type            ENUM('medical','surgical','family','social','obstetric','other')
description     TEXT NOT NULL
occurred_at     DATE
recorded_by     BIGINT UNSIGNED REFERENCES users(id)
created_at      TIMESTAMP
updated_at      TIMESTAMP
```

#### `patient_consents`
```sql
id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
patient_id      BIGINT UNSIGNED REFERENCES patients(id)
consent_type    VARCHAR(100) NOT NULL
status          ENUM('given','refused','withdrawn') NOT NULL
obtained_by     BIGINT UNSIGNED REFERENCES users(id)
obtained_at     TIMESTAMP NOT NULL
notes           TEXT
revoked_at      TIMESTAMP
created_at      TIMESTAMP
```

#### `patient_physical_files`
```sql
id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
patient_id      BIGINT UNSIGNED REFERENCES patients(id)
file_number     VARCHAR(50)
cabinet         VARCHAR(50)
shelf           VARCHAR(50)
drawer          VARCHAR(50)
location_notes  TEXT
status          ENUM('in_storage','checked_out','missing','archived')
last_moved_by   BIGINT UNSIGNED REFERENCES users(id)
last_moved_at   TIMESTAMP
created_at      TIMESTAMP
updated_at      TIMESTAMP
```

#### `patient_file_movements`
```sql
id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
file_id         BIGINT UNSIGNED REFERENCES patient_physical_files(id)
from_location   VARCHAR(150)
to_location     VARCHAR(150)
reason          VARCHAR(255)
moved_by        BIGINT UNSIGNED REFERENCES users(id)
moved_at        TIMESTAMP NOT NULL
created_at      TIMESTAMP
```

#### `insurance_providers`
```sql
id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
name        VARCHAR(150) NOT NULL
code        VARCHAR(30)
contact     VARCHAR(150)
phone       VARCHAR(30)
email       VARCHAR(150)
is_active   TINYINT(1) NOT NULL DEFAULT 1
created_at  TIMESTAMP
updated_at  TIMESTAMP
```

---

### Clinical Encounters

#### `encounters`
```sql
id                      BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
uuid                    CHAR(36) UNIQUE NOT NULL
patient_id              BIGINT UNSIGNED REFERENCES patients(id)
branch_id               BIGINT UNSIGNED REFERENCES branches(id)
doctor_id               BIGINT UNSIGNED REFERENCES doctors(id)
appointment_id          BIGINT UNSIGNED REFERENCES appointments(id)
queue_entry_id          BIGINT UNSIGNED REFERENCES queue_entries(id)
type                    ENUM('walk_in','appointment','follow_up','emergency')
status                  ENUM('open','in_progress','completed','cancelled','signed')
chief_complaint         TEXT
hpi                     TEXT                        -- history of presenting illness
past_medical_history    TEXT
past_surgical_history   TEXT
family_history          TEXT
social_history          TEXT
review_of_systems       TEXT
physical_examination    TEXT
clinical_findings       TEXT
assessment              TEXT
treatment_plan          TEXT
additional_notes        TEXT
signed_at               TIMESTAMP
signed_by               BIGINT UNSIGNED REFERENCES users(id)
check_in_at             TIMESTAMP
consultation_started_at TIMESTAMP
completed_at            TIMESTAMP
created_at              TIMESTAMP
updated_at              TIMESTAMP
INDEX (patient_id)
INDEX (doctor_id)
INDEX (created_at)
```

#### `encounter_amendments`
```sql
id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
encounter_id    BIGINT UNSIGNED REFERENCES encounters(id)
field_name      VARCHAR(100) NOT NULL
old_value       LONGTEXT
new_value       LONGTEXT
reason          TEXT NOT NULL
amended_by      BIGINT UNSIGNED REFERENCES users(id)
amended_at      TIMESTAMP NOT NULL
created_at      TIMESTAMP
```

#### `diagnoses`
```sql
id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
encounter_id    BIGINT UNSIGNED REFERENCES encounters(id)
patient_id      BIGINT UNSIGNED REFERENCES patients(id)
icd10_code      VARCHAR(10)
description     VARCHAR(255) NOT NULL
type            ENUM('primary','secondary','provisional','differential')
is_primary      TINYINT(1) NOT NULL DEFAULT 0
notes           TEXT
created_by      BIGINT UNSIGNED REFERENCES users(id)
created_at      TIMESTAMP
updated_at      TIMESTAMP
```

---

### Vital Signs and Nursing

#### `vital_signs`
```sql
id                      BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
encounter_id            BIGINT UNSIGNED REFERENCES encounters(id)
patient_id              BIGINT UNSIGNED REFERENCES patients(id)
recorded_by             BIGINT UNSIGNED REFERENCES users(id)
weight_kg               DECIMAL(6,2)
height_cm               DECIMAL(6,2)
bmi                     DECIMAL(5,2)
bp_systolic             SMALLINT UNSIGNED
bp_diastolic            SMALLINT UNSIGNED
pulse_bpm               SMALLINT UNSIGNED
respiratory_rate        SMALLINT UNSIGNED
temperature_celsius     DECIMAL(4,1)
spo2_percent            TINYINT UNSIGNED
blood_glucose_mmol      DECIMAL(5,2)
pain_score              TINYINT UNSIGNED
additional_observations TEXT
recorded_at             TIMESTAMP NOT NULL
created_at              TIMESTAMP
INDEX (patient_id)
INDEX (encounter_id)
```

#### `nursing_assessments`
```sql
id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
encounter_id    BIGINT UNSIGNED REFERENCES encounters(id)
nurse_id        BIGINT UNSIGNED REFERENCES users(id)
assessment_data JSON
notes           TEXT
completed_at    TIMESTAMP
created_at      TIMESTAMP
updated_at      TIMESTAMP
```

#### `procedures`
```sql
id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
encounter_id    BIGINT UNSIGNED REFERENCES encounters(id)
patient_id      BIGINT UNSIGNED REFERENCES patients(id)
performed_by    BIGINT UNSIGNED REFERENCES users(id)
procedure_type  VARCHAR(100) NOT NULL
description     TEXT
notes           TEXT
performed_at    TIMESTAMP NOT NULL
created_at      TIMESTAMP
```

---

### Queue Management

#### `queue_entries`
```sql
id                      BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
patient_id              BIGINT UNSIGNED REFERENCES patients(id)
encounter_id            BIGINT UNSIGNED REFERENCES encounters(id)
doctor_id               BIGINT UNSIGNED REFERENCES doctors(id)
branch_id               BIGINT UNSIGNED REFERENCES branches(id)
queue_number            SMALLINT UNSIGNED NOT NULL
status                  ENUM('registered','awaiting_nurse','with_nurse','awaiting_doctor','called','in_consultation','awaiting_payment','completed','cancelled','no_show','transferred') NOT NULL DEFAULT 'registered'
priority                ENUM('routine','priority','urgent','emergency') NOT NULL DEFAULT 'routine'
visit_type              VARCHAR(100)
receptionist_id         BIGINT UNSIGNED REFERENCES users(id)
check_in_at             TIMESTAMP NOT NULL
called_at               TIMESTAMP
consultation_started_at TIMESTAMP
completed_at            TIMESTAMP
notes                   TEXT
created_at              TIMESTAMP
updated_at              TIMESTAMP
INDEX (doctor_id, status)
INDEX (branch_id, created_at)
```

#### `queue_events`
```sql
id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
queue_entry_id  BIGINT UNSIGNED REFERENCES queue_entries(id)
from_status     VARCHAR(50)
to_status       VARCHAR(50) NOT NULL
performed_by    BIGINT UNSIGNED REFERENCES users(id)
notes           TEXT
created_at      TIMESTAMP
INDEX (queue_entry_id)
```

#### `doctor_availability`
```sql
id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
doctor_id       BIGINT UNSIGNED REFERENCES doctors(id)
date            DATE
status          ENUM('available','busy','on_break','off_duty','not_accepting') NOT NULL DEFAULT 'available'
start_time      TIME
end_time        TIME
reason          VARCHAR(255)
set_by          BIGINT UNSIGNED REFERENCES users(id)
created_at      TIMESTAMP
updated_at      TIMESTAMP
INDEX (doctor_id, date)
```

---

### Appointments

#### `doctor_schedules`
```sql
id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
doctor_id           BIGINT UNSIGNED REFERENCES doctors(id)
branch_id           BIGINT UNSIGNED REFERENCES branches(id)
day_of_week         TINYINT UNSIGNED NOT NULL        -- 0=Sunday
start_time          TIME NOT NULL
end_time            TIME NOT NULL
slot_duration_min   TINYINT UNSIGNED NOT NULL DEFAULT 15
max_patients        TINYINT UNSIGNED
is_active           TINYINT(1) NOT NULL DEFAULT 1
created_at          TIMESTAMP
updated_at          TIMESTAMP
```

#### `appointments`
```sql
id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
uuid                CHAR(36) UNIQUE NOT NULL
patient_id          BIGINT UNSIGNED REFERENCES patients(id)
doctor_id           BIGINT UNSIGNED REFERENCES doctors(id)
branch_id           BIGINT UNSIGNED REFERENCES branches(id)
appointment_date    DATE NOT NULL
start_time          TIME NOT NULL
end_time            TIME NOT NULL
type                VARCHAR(100)
status              ENUM('booked','confirmed','arrived','in_progress','completed','cancelled','no_show','rescheduled') NOT NULL DEFAULT 'booked'
notes               TEXT
booked_by           BIGINT UNSIGNED REFERENCES users(id)
confirmed_at        TIMESTAMP
cancelled_at        TIMESTAMP
cancel_reason       VARCHAR(255)
reminder_sent       TINYINT(1) NOT NULL DEFAULT 0
reminder_sent_at    TIMESTAMP
rescheduled_from    BIGINT UNSIGNED REFERENCES appointments(id)
created_at          TIMESTAMP
updated_at          TIMESTAMP
INDEX (doctor_id, appointment_date)
INDEX (patient_id)
```

---

### Prescriptions

#### `medications`
```sql
id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
generic_name    VARCHAR(255) NOT NULL
brand_names     JSON
strength        VARCHAR(100)
form            VARCHAR(100)
category        VARCHAR(100)
is_controlled   TINYINT(1) NOT NULL DEFAULT 0
is_active       TINYINT(1) NOT NULL DEFAULT 1
created_at      TIMESTAMP
updated_at      TIMESTAMP
FULLTEXT INDEX (generic_name)
```

#### `prescriptions`
```sql
id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
uuid                CHAR(36) UNIQUE NOT NULL
encounter_id        BIGINT UNSIGNED REFERENCES encounters(id)
patient_id          BIGINT UNSIGNED REFERENCES patients(id)
doctor_id           BIGINT UNSIGNED REFERENCES doctors(id)
prescription_number VARCHAR(30) UNIQUE NOT NULL
status              ENUM('draft','issued','emailed','cancelled') NOT NULL DEFAULT 'draft'
notes               TEXT
pdf_path            VARCHAR(255)
issued_at           TIMESTAMP
created_at          TIMESTAMP
updated_at          TIMESTAMP
INDEX (patient_id)
INDEX (doctor_id)
```

#### `prescription_items`
```sql
id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
prescription_id BIGINT UNSIGNED REFERENCES prescriptions(id)
medication_id   BIGINT UNSIGNED REFERENCES medications(id)
generic_name    VARCHAR(255) NOT NULL
brand_name      VARCHAR(255)
strength        VARCHAR(100)
form            VARCHAR(100)
dose            VARCHAR(100)
route           VARCHAR(50)
frequency       VARCHAR(100)
duration        VARCHAR(100)
quantity        VARCHAR(50)
instructions    TEXT
refill_info     VARCHAR(255)
sort_order      TINYINT UNSIGNED NOT NULL DEFAULT 0
created_at      TIMESTAMP
```

#### `prescription_templates`
```sql
id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
doctor_id   BIGINT UNSIGNED REFERENCES doctors(id)
name        VARCHAR(150) NOT NULL
items       JSON NOT NULL
is_active   TINYINT(1) NOT NULL DEFAULT 1
created_at  TIMESTAMP
updated_at  TIMESTAMP
```

#### `prescription_delivery_logs`
```sql
id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
prescription_id     BIGINT UNSIGNED REFERENCES prescriptions(id)
method              ENUM('email','print') NOT NULL
recipient_email     VARCHAR(150)
smtp2go_message_id  VARCHAR(100)
status              ENUM('queued','sent','delivered','bounced','failed') NOT NULL DEFAULT 'queued'
sent_at             TIMESTAMP
delivered_at        TIMESTAMP
bounced_at          TIMESTAMP
error_message       TEXT
sent_by             BIGINT UNSIGNED REFERENCES users(id)
created_at          TIMESTAMP
updated_at          TIMESTAMP
```

---

### Documents

#### `document_categories`
```sql
id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
name        VARCHAR(100) NOT NULL
description TEXT
icon        VARCHAR(50)
sort_order  TINYINT UNSIGNED NOT NULL DEFAULT 0
is_active   TINYINT(1) NOT NULL DEFAULT 1
```

#### `patient_documents`
```sql
id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
uuid                CHAR(36) UNIQUE NOT NULL
patient_id          BIGINT UNSIGNED REFERENCES patients(id)
branch_id           BIGINT UNSIGNED REFERENCES branches(id)
category_id         BIGINT UNSIGNED REFERENCES document_categories(id)
encounter_id        BIGINT UNSIGNED REFERENCES encounters(id)
description         VARCHAR(255) NOT NULL
original_filename   VARCHAR(255) NOT NULL
stored_path         VARCHAR(500) NOT NULL
file_type           VARCHAR(50)
file_size_bytes     INT UNSIGNED
mime_type           VARCHAR(100)
version             TINYINT UNSIGNED NOT NULL DEFAULT 1
notes               TEXT
uploaded_by         BIGINT UNSIGNED REFERENCES users(id)
is_deleted          TINYINT(1) NOT NULL DEFAULT 0
deleted_at          TIMESTAMP
created_at          TIMESTAMP
updated_at          TIMESTAMP
INDEX (patient_id)
```

#### `document_versions`
```sql
id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
document_id     BIGINT UNSIGNED REFERENCES patient_documents(id)
version         TINYINT UNSIGNED NOT NULL
stored_path     VARCHAR(500) NOT NULL
file_size_bytes INT UNSIGNED
notes           TEXT
uploaded_by     BIGINT UNSIGNED REFERENCES users(id)
created_at      TIMESTAMP
```

#### `document_access_logs`
```sql
id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
document_id BIGINT UNSIGNED REFERENCES patient_documents(id)
patient_id  BIGINT UNSIGNED REFERENCES patients(id)
accessed_by BIGINT UNSIGNED REFERENCES users(id)
action      ENUM('view','download','print','upload','delete') NOT NULL
ip_address  VARCHAR(45)
created_at  TIMESTAMP
INDEX (document_id)
INDEX (patient_id)
```

---

### Laboratory

#### `laboratory_orders`
```sql
id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
encounter_id    BIGINT UNSIGNED REFERENCES encounters(id)
patient_id      BIGINT UNSIGNED REFERENCES patients(id)
doctor_id       BIGINT UNSIGNED REFERENCES doctors(id)
test_name       VARCHAR(255) NOT NULL
test_code       VARCHAR(50)
priority        ENUM('routine','urgent','stat') NOT NULL DEFAULT 'routine'
status          ENUM('ordered','specimen_collected','processing','resulted','acknowledged','cancelled') NOT NULL DEFAULT 'ordered'
instructions    TEXT
laboratory_name VARCHAR(150)
ordered_at      TIMESTAMP NOT NULL
created_at      TIMESTAMP
updated_at      TIMESTAMP
INDEX (patient_id)
INDEX (encounter_id)
```

#### `laboratory_results`
```sql
id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
order_id            BIGINT UNSIGNED REFERENCES laboratory_orders(id)
patient_id          BIGINT UNSIGNED REFERENCES patients(id)
result_value        VARCHAR(255)
unit                VARCHAR(50)
reference_range_low VARCHAR(50)
reference_range_high VARCHAR(50)
is_flagged          TINYINT(1) NOT NULL DEFAULT 0
flag_type           ENUM('high','low','critical_high','critical_low','abnormal')
notes               TEXT
report_path         VARCHAR(500)
resulted_at         TIMESTAMP
resulted_by         VARCHAR(150)
acknowledged_by     BIGINT UNSIGNED REFERENCES users(id)
acknowledged_at     TIMESTAMP
created_at          TIMESTAMP
updated_at          TIMESTAMP
```

---

### Referrals and Certificates

#### `referrals`
```sql
id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
encounter_id        BIGINT UNSIGNED REFERENCES encounters(id)
patient_id          BIGINT UNSIGNED REFERENCES patients(id)
referring_doctor_id BIGINT UNSIGNED REFERENCES doctors(id)
referral_type       ENUM('specialist','hospital','laboratory','imaging','other')
referred_to         VARCHAR(255) NOT NULL
specialty           VARCHAR(100)
reason              TEXT NOT NULL
clinical_summary    TEXT
urgency             ENUM('routine','urgent','emergency') NOT NULL DEFAULT 'routine'
pdf_path            VARCHAR(500)
issued_at           TIMESTAMP NOT NULL
delivered_via       ENUM('print','email')
delivered_at        TIMESTAMP
created_at          TIMESTAMP
updated_at          TIMESTAMP
```

#### `medical_certificates`
```sql
id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
encounter_id        BIGINT UNSIGNED REFERENCES encounters(id)
patient_id          BIGINT UNSIGNED REFERENCES patients(id)
doctor_id           BIGINT UNSIGNED REFERENCES doctors(id)
certificate_type    ENUM('sick_leave','fitness','attendance','other') NOT NULL
content             TEXT NOT NULL
days_off            TINYINT UNSIGNED
from_date           DATE
to_date             DATE
pdf_path            VARCHAR(500)
issued_at           TIMESTAMP NOT NULL
delivered_via       ENUM('print','email')
created_at          TIMESTAMP
updated_at          TIMESTAMP
```

#### `follow_ups`
```sql
id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
encounter_id    BIGINT UNSIGNED REFERENCES encounters(id)
patient_id      BIGINT UNSIGNED REFERENCES patients(id)
doctor_id       BIGINT UNSIGNED REFERENCES doctors(id)
reason          TEXT NOT NULL
due_date        DATE NOT NULL
priority        ENUM('routine','priority','urgent') NOT NULL DEFAULT 'routine'
status          ENUM('pending','contacted','booked','completed','missed','cancelled') NOT NULL DEFAULT 'pending'
notes           TEXT
appointment_id  BIGINT UNSIGNED REFERENCES appointments(id)
created_at      TIMESTAMP
updated_at      TIMESTAMP
INDEX (patient_id, due_date)
INDEX (doctor_id, due_date)
```

---

### Billing

#### `invoices`
```sql
id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
uuid            CHAR(36) UNIQUE NOT NULL
invoice_number  VARCHAR(30) UNIQUE NOT NULL
patient_id      BIGINT UNSIGNED REFERENCES patients(id)
branch_id       BIGINT UNSIGNED REFERENCES branches(id)
encounter_id    BIGINT UNSIGNED REFERENCES encounters(id)
issued_by       BIGINT UNSIGNED REFERENCES users(id)
status          ENUM('draft','finalized','paid','partially_paid','outstanding','cancelled') NOT NULL DEFAULT 'draft'
subtotal        DECIMAL(10,2) NOT NULL DEFAULT 0.00
discount_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00
tax_amount      DECIMAL(10,2) NOT NULL DEFAULT 0.00
total           DECIMAL(10,2) NOT NULL DEFAULT 0.00
amount_paid     DECIMAL(10,2) NOT NULL DEFAULT 0.00
balance         DECIMAL(10,2) NOT NULL DEFAULT 0.00
notes           TEXT
finalized_at    TIMESTAMP
issued_at       TIMESTAMP
due_at          DATE
created_at      TIMESTAMP
updated_at      TIMESTAMP
INDEX (patient_id)
INDEX (issued_at)
```

#### `invoice_items`
```sql
id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
invoice_id      BIGINT UNSIGNED REFERENCES invoices(id)
service_id      BIGINT UNSIGNED REFERENCES service_catalogue(id)
description     VARCHAR(255) NOT NULL
quantity        DECIMAL(8,2) NOT NULL DEFAULT 1
unit_price      DECIMAL(10,2) NOT NULL
discount_pct    DECIMAL(5,2) NOT NULL DEFAULT 0.00
tax_rate        DECIMAL(5,2) NOT NULL DEFAULT 0.00
line_total      DECIMAL(10,2) NOT NULL
sort_order      TINYINT UNSIGNED NOT NULL DEFAULT 0
created_at      TIMESTAMP
```

#### `payments`
```sql
id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
uuid                CHAR(36) UNIQUE NOT NULL
payment_reference   VARCHAR(50) UNIQUE NOT NULL
invoice_id          BIGINT UNSIGNED REFERENCES invoices(id)
patient_id          BIGINT UNSIGNED REFERENCES patients(id)
shift_id            BIGINT UNSIGNED REFERENCES cashier_shifts(id)
amount              DECIMAL(10,2) NOT NULL
method              ENUM('cash','credit_card','debit_card','linx','bank_transfer','cheque','insurance','corporate','other') NOT NULL
received_by         BIGINT UNSIGNED REFERENCES users(id)
transaction_ref     VARCHAR(100)
notes               TEXT
payment_date        DATE NOT NULL
created_at          TIMESTAMP
updated_at          TIMESTAMP
INDEX (invoice_id)
INDEX (shift_id)
INDEX (payment_date)
```

#### `receipts`
```sql
id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
payment_id      BIGINT UNSIGNED UNIQUE REFERENCES payments(id)
receipt_number  VARCHAR(30) UNIQUE NOT NULL
pdf_path        VARCHAR(500)
issued_at       TIMESTAMP NOT NULL
issued_by       BIGINT UNSIGNED REFERENCES users(id)
created_at      TIMESTAMP
```

#### `refunds`
```sql
id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
payment_id      BIGINT UNSIGNED REFERENCES payments(id)
invoice_id      BIGINT UNSIGNED REFERENCES invoices(id)
amount          DECIMAL(10,2) NOT NULL
reason          TEXT NOT NULL
authorized_by   BIGINT UNSIGNED REFERENCES users(id)
processed_by    BIGINT UNSIGNED REFERENCES users(id)
refunded_at     TIMESTAMP NOT NULL
notes           TEXT
created_at      TIMESTAMP
```

#### `credit_notes`
```sql
id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
invoice_id          BIGINT UNSIGNED REFERENCES invoices(id)
credit_note_number  VARCHAR(30) UNIQUE NOT NULL
amount              DECIMAL(10,2) NOT NULL
reason              TEXT NOT NULL
issued_by           BIGINT UNSIGNED REFERENCES users(id)
issued_at           TIMESTAMP NOT NULL
created_at          TIMESTAMP
```

---

### Cash Management

#### `cash_drawers`
```sql
id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
branch_id   BIGINT UNSIGNED REFERENCES branches(id)
name        VARCHAR(100) NOT NULL
is_active   TINYINT(1) NOT NULL DEFAULT 1
created_at  TIMESTAMP
updated_at  TIMESTAMP
```

#### `cashier_shifts`
```sql
id                      BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
receptionist_id         BIGINT UNSIGNED REFERENCES users(id)
drawer_id               BIGINT UNSIGNED REFERENCES cash_drawers(id)
branch_id               BIGINT UNSIGNED REFERENCES branches(id)
status                  ENUM('open','closed','approved') NOT NULL DEFAULT 'open'
opened_at               TIMESTAMP NOT NULL
closed_at               TIMESTAMP
opening_balance         DECIMAL(10,2) NOT NULL DEFAULT 0.00
expected_closing_balance DECIMAL(10,2)
actual_closing_balance  DECIMAL(10,2)
discrepancy             DECIMAL(10,2)
discrepancy_explanation TEXT
notes                   TEXT
created_at              TIMESTAMP
updated_at              TIMESTAMP
```

#### `shift_reconciliations`
```sql
id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
shift_id            BIGINT UNSIGNED UNIQUE REFERENCES cashier_shifts(id)
cash_sales          DECIMAL(10,2) NOT NULL DEFAULT 0.00
card_sales          DECIMAL(10,2) NOT NULL DEFAULT 0.00
linx_sales          DECIMAL(10,2) NOT NULL DEFAULT 0.00
transfer_sales      DECIMAL(10,2) NOT NULL DEFAULT 0.00
cheque_sales        DECIMAL(10,2) NOT NULL DEFAULT 0.00
insurance_payments  DECIMAL(10,2) NOT NULL DEFAULT 0.00
refunds_total       DECIMAL(10,2) NOT NULL DEFAULT 0.00
adjustments         DECIMAL(10,2) NOT NULL DEFAULT 0.00
total_expected      DECIMAL(10,2) NOT NULL
total_actual        DECIMAL(10,2) NOT NULL
discrepancy         DECIMAL(10,2) NOT NULL DEFAULT 0.00
discrepancy_explanation TEXT
reconciled_by       BIGINT UNSIGNED REFERENCES users(id)
reviewed_by         BIGINT UNSIGNED REFERENCES users(id)
reviewed_at         TIMESTAMP
status              ENUM('pending','approved','queried') NOT NULL DEFAULT 'pending'
created_at          TIMESTAMP
updated_at          TIMESTAMP
```

---

### Inventory

#### `inventory_items`
```sql
id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
branch_id       BIGINT UNSIGNED REFERENCES branches(id)
sku             VARCHAR(50)
name            VARCHAR(150) NOT NULL
category        VARCHAR(100)
unit            VARCHAR(30)
current_quantity DECIMAL(10,2) NOT NULL DEFAULT 0.00
reorder_level   DECIMAL(10,2) NOT NULL DEFAULT 0.00
notes           TEXT
is_active       TINYINT(1) NOT NULL DEFAULT 1
created_at      TIMESTAMP
updated_at      TIMESTAMP
```

#### `suppliers`
```sql
id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
name        VARCHAR(150) NOT NULL
contact     VARCHAR(150)
phone       VARCHAR(30)
email       VARCHAR(150)
address     TEXT
notes       TEXT
is_active   TINYINT(1) NOT NULL DEFAULT 1
created_at  TIMESTAMP
updated_at  TIMESTAMP
```

#### `stock_batches`
```sql
id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
item_id         BIGINT UNSIGNED REFERENCES inventory_items(id)
supplier_id     BIGINT UNSIGNED REFERENCES suppliers(id)
batch_number    VARCHAR(50)
quantity        DECIMAL(10,2) NOT NULL
purchase_cost   DECIMAL(10,2)
expiry_date     DATE
received_at     DATE NOT NULL
received_by     BIGINT UNSIGNED REFERENCES users(id)
created_at      TIMESTAMP
```

#### `stock_movements`
```sql
id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
item_id         BIGINT UNSIGNED REFERENCES inventory_items(id)
batch_id        BIGINT UNSIGNED REFERENCES stock_batches(id)
type            ENUM('received','dispensed','adjusted','expired','damaged','returned') NOT NULL
quantity        DECIMAL(10,2) NOT NULL
reference_type  VARCHAR(50)
reference_id    BIGINT UNSIGNED
notes           TEXT
performed_by    BIGINT UNSIGNED REFERENCES users(id)
created_at      TIMESTAMP
INDEX (item_id)
```

---

### System

#### `notifications`
```sql
id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
user_id     BIGINT UNSIGNED REFERENCES users(id)
type        VARCHAR(100) NOT NULL
title       VARCHAR(255)
message     TEXT
data        JSON
read_at     TIMESTAMP
created_at  TIMESTAMP
INDEX (user_id, read_at)
```

#### `email_logs`
```sql
id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
recipient           VARCHAR(150) NOT NULL
subject             VARCHAR(255)
type                VARCHAR(100)
status              ENUM('queued','sent','delivered','bounced','failed') NOT NULL DEFAULT 'queued'
smtp2go_message_id  VARCHAR(100)
sent_at             TIMESTAMP
delivered_at        TIMESTAMP
bounced_at          TIMESTAMP
error_message       TEXT
created_at          TIMESTAMP
INDEX (recipient)
INDEX (status)
```

#### `audit_logs`
```sql
id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
user_id         BIGINT UNSIGNED
action          VARCHAR(100) NOT NULL
resource_type   VARCHAR(100)
resource_id     BIGINT UNSIGNED
old_values      JSON
new_values      JSON
description     TEXT
ip_address      VARCHAR(45)
user_agent      TEXT
created_at      TIMESTAMP
INDEX (user_id)
INDEX (resource_type, resource_id)
INDEX (created_at)
```

---

## Tenant Provisioning Process

When a new medical centre is created:

1. Platform admin creates tenant record in `ttmed_platform.tenants`
2. `TenantProvisioningJob` runs:
   - Creates new MySQL database: `ttmed_{tenant_code}`
   - Creates MySQL user with limited privileges on that database only
   - Stores encrypted credentials in `tenants.db_password`
   - Runs all tenant migrations against the new database
   - Creates default roles and permissions
   - Creates initial admin user
   - Creates default branch record
   - Creates file storage directory at `storage/tenants/{tenant_uuid}/`
3. Subscription record created
4. Welcome email dispatched

---

## Indexing Strategy

- All foreign keys indexed
- Patient search fields indexed: `last_name`, `phone`, `date_of_birth`, `patient_number`
- Queue lookups: `(doctor_id, status)`, `(branch_id, created_at)`
- Encounter lookups: `patient_id`, `doctor_id`, `created_at`
- Appointment lookups: `(doctor_id, appointment_date)`
- Financial: `invoice_number` unique, `payment_date`, `shift_id`
- Medication search: `FULLTEXT(generic_name)`
- Audit logs: `user_id`, `created_at`, `(resource_type, resource_id)`
