# TTMed — Implementation Roadmap

Each phase must be authorized individually before implementation begins. No phase should start until the previous phase is complete and verified.

---

## Phase 0 — Architecture and Planning ✅ COMPLETE

**Deliverables:**
- README.md
- ARCHITECTURE.md
- DATABASE.md
- SECURITY.md
- DEPLOYMENT.md
- IMPLEMENTATION_ROADMAP.md
- IMPLEMENTATION_PROGRESS.md

**Dependencies for Phase 1:** None

---

## Phase 1 — Foundation

**Objective:** Working Laravel application with multi-tenant infrastructure, authentication, and permissions.

**Tasks:**

1. **Laravel project setup**
   - Initialize Laravel 11 project
   - Configure `composer.json` dependencies
   - Configure `package.json` with Livewire, Alpine.js, Tailwind
   - Set up Vite build pipeline

2. **Database configuration**
   - Configure `config/database.php` with `platform` connection and dynamic `tenant` connection
   - Configure `config/tenancy.php` with tenant resolution settings

3. **Platform database migrations**
   - `tenants`
   - `subscription_plans`
   - `subscriptions`
   - `platform_users`
   - `platform_audit_logs`

4. **Tenant database migrations (applied to each tenant DB)**
   - `users`, `roles`, `permissions`, `role_permissions`, `user_roles`
   - `user_sessions`
   - `branches`
   - `centre_settings`
   - `audit_logs`

5. **Tenant resolution middleware**
   - `ResolveTenant` — reads tenant from session, establishes DB connection
   - `EnsureTenantActive` — blocks suspended/deactivated tenants
   - `CheckPermission` — RBAC enforcement

6. **Authentication system**
   - Three-field login (tenant code, username, password)
   - Login rate limiting and account lockout
   - Remember me
   - Password reset (email via SMTP2GO)
   - MFA enrollment and verification (TOTP)
   - Session management and secure logout

7. **Platform administration foundation**
   - Separate route group `/platform` with its own middleware and guard
   - Platform login screen
   - Platform admin dashboard (skeleton)
   - Tenant CRUD (create, view, edit, activate, suspend, deactivate)
   - Subscription plan management
   - Platform audit log viewer

8. **Tenant provisioning**
   - `TenantProvisioningJob`: create DB, create MySQL user, run migrations, seed roles
   - Default roles and permissions seeder (all 13 roles, all permission slugs)
   - Initial tenant admin user creation

9. **Tenant admin portal foundation**
   - Main application layout (sidebar, navigation, header)
   - Dashboard skeleton (empty panels, role-aware navigation)
   - User management: list, create, edit, deactivate, assign roles
   - Role management: view roles, assign/remove permissions

10. **Custom artisan commands**
    - `tenants:migrate` — runs pending tenant migrations across all active tenants
    - `tenants:list` — lists all tenants with status
    - `tenants:provision {code}` — manually provisions a specific tenant

11. **Testing**
    - Tenant isolation tests: authenticated as tenant A, assert no access to tenant B data
    - Authentication tests: failed login lockout, MFA flow, session expiry
    - Permission tests: each role group tested against allowed and forbidden endpoints

**Dependencies for Phase 2:** Tenant provisioning working end-to-end, authentication verified, RBAC enforced.

---

## Phase 2 — Medical Centre Administration

**Objective:** A provisioned tenant can configure their medical centre, manage staff, and set up operational data.

**Tasks:**

1. **Additional tenant migrations**
   - `staff_profiles`, `doctors`
   - `service_catalogue`
   - `insurance_providers`
   - `document_categories` (default seeder)

2. **Medical centre settings**
   - Branch management (create/edit/deactivate branches)
   - Centre configuration UI: name, address, phone, logo, timezone, currency, tax settings
   - Operating hours configuration
   - Email settings (SMTP2GO credentials per tenant, encrypted storage, test send)

3. **Staff management**
   - Staff list with role and status
   - Create/edit staff profiles
   - Doctor profile management: registration number, specialty, qualifications, clinic details
   - Doctor prescription configuration: header, footer, logo upload, signature upload, stamp upload
   - Nurse profile management

4. **Service catalogue**
   - Create/edit/deactivate services
   - Categories, pricing, tax configuration
   - Import from defaults

5. **Testing**
   - Configuration changes persist and reload correctly
   - Doctor profiles display correctly on prescription preview
   - Service catalogue prices used in invoice calculation (Phase 9 dependency)

**Dependencies for Phase 3:** Branches, staff, doctors, and service catalogue in place.

---

## Phase 3 — Patient Management

**Objective:** Complete patient registration, search, and profile system.

**Tasks:**

1. **Additional tenant migrations**
   - `patients`, `patient_contacts`, `patient_allergies`
   - `patient_conditions`, `patient_medications`, `patient_medical_histories`
   - `patient_consents`, `patient_physical_files`, `patient_file_movements`

2. **Patient number generation**
   - Configurable format per tenant (e.g., `MC-000001`)
   - Sequential, never reused

3. **Patient registration**
   - Full registration form with all fields
   - Validation and duplicate detection (name + DOB + phone fuzzy match)
   - Duplicate warning with option to view existing or continue registering

4. **Patient search**
   - Search by: patient number, name (partial), phone, DOB, email
   - Fuzzy name matching
   - Results list with quick-view panel

5. **Patient profile**
   - Personal information tab
   - Medical history tab: conditions, allergies, current medications, surgical/family/social history
   - Encounters tab (populated in Phase 6)
   - Documents tab (populated in Phase 7)
   - Prescriptions tab (populated in Phase 7)
   - Lab results tab (populated in Phase 10)
   - Appointments tab (populated in Phase 8)
   - Billing tab (populated in Phase 9)
   - Consents tab

6. **Physical file tracking**
   - File number assignment
   - Location record (cabinet, shelf, drawer)
   - Movement history log

7. **Testing**
   - Patient search returns correct results, no cross-tenant results
   - Duplicate detection triggers correctly
   - Patient number is unique within tenant

**Dependencies for Phase 4:** Patient records in place for check-in.

---

## Phase 4 — Reception and Queue Management

**Objective:** Receptionists can check patients in, assign doctors, and manage the live queue.

**Tasks:**

1. **Additional tenant migrations**
   - `queue_entries`, `queue_events`
   - `doctor_availability`
   - `encounters` (skeleton — full clinical fields added in Phase 6)

2. **Reception dashboard (Livewire)**
   - Doctor panel: shows each active doctor, their status, queue count
   - Patient check-in: search existing patient or register new
   - Walk-in flow: quick registration → check-in → queue assignment
   - Doctor assignment with availability awareness
   - Visit type selection
   - Queue number assignment (sequential per day per branch)

3. **Queue management (real-time)**
   - Live queue display per doctor
   - Status progression through all queue states
   - Priority flag assignment
   - Transfer between doctors
   - Cancel/no-show handling

4. **Doctor availability management**
   - Receptionist can view/set doctor status (available, busy, on break, off duty, not accepting)
   - Doctor can set own status from their portal

5. **Real-time updates (Reverb WebSockets)**
   - `QueueUpdated` event broadcast on tenant-scoped channel
   - Reception dashboard updates without page refresh
   - Nurse dashboard updates without page refresh (Phase 5)
   - Waiting room display: queue numbers only, no patient names

6. **Multiple receptionist support**
   - Each receptionist sees the same live queue
   - Actions attributed to the performing receptionist in queue_events

7. **Testing**
   - Queue state transitions follow allowed paths only
   - Two simultaneous receptionists see the same queue state
   - WebSocket channel is tenant-scoped (tenant A cannot receive tenant B events)

**Dependencies for Phase 5:** Queue states for nursing handover. Dependencies for Phase 6: Encounter creation on check-in.

---

## Phase 5 — Nursing and Triage

**Objective:** Nurses can receive patients from the queue, record vital signs, and pass patients to the doctor.

**Tasks:**

1. **Additional tenant migrations**
   - `vital_signs`, `nursing_assessments`, `procedures`

2. **Nurse dashboard (Livewire)**
   - Patients awaiting nursing assessment
   - Open nursing assessment from queue
   - Vital signs form with real-time BMI calculation
   - Nursing assessment notes
   - Procedure documentation (injection, dressing, ECG, nebulizer, etc.)
   - Complete assessment → moves patient to "awaiting doctor" state

3. **Vital signs history**
   - View all previous vital sign records for a patient
   - Trend display for repeat visits

4. **Testing**
   - Vital signs saved against correct encounter and patient
   - BMI calculated correctly
   - Queue status updates to "awaiting doctor" on nursing completion
   - Nurse cannot access billing or financial screens

**Dependencies for Phase 6:** Nursing assessment visible to doctor before consultation.

---

## Phase 6 — Doctor Consultations

**Objective:** Doctors can conduct full consultations, record clinical notes, and complete encounters.

**Tasks:**

1. **Additional tenant migrations**
   - Full `encounters` migration (all clinical fields)
   - `encounter_amendments`, `diagnoses`

2. **Doctor dashboard (Livewire)**
   - Doctor's queue: patients waiting, called, in consultation
   - Today's appointments
   - Follow-up list
   - Outstanding incomplete consultations

3. **Patient clinical summary panel**
   - Allergies (highlighted if present)
   - Active conditions
   - Current medications
   - Most recent vital signs
   - Previous diagnoses (last 5 encounters)
   - Previous prescriptions
   - Outstanding lab results

4. **Consultation workspace**
   - SOAP sections: Subjective, Objective, Assessment, Plan
   - Or full section layout: chief complaint, HPI, PMH, PSH, family, social, ROS, examination, findings, assessment, treatment plan, notes
   - Configurable templates
   - Auto-save every 2 minutes (draft state)
   - ICD-10 diagnosis search and selection
   - Multiple diagnoses per encounter

5. **Encounter signing**
   - Doctor reviews and signs encounter
   - Signed status locks direct editing
   - Amendments tracked in `encounter_amendments`

6. **Testing**
   - Consultation data persists across page loads
   - Signed encounter cannot be edited without creating amendment
   - Doctor A cannot open Doctor B's encounters via URL manipulation
   - Clinical summary reflects actual patient history

**Dependencies for Phase 7:** Signed encounters required for prescription issuance.

---

## Phase 7 — Prescriptions and Documents

**Objective:** Doctors can issue prescriptions, generate PDFs, email to patients, and upload/view clinical documents.

**Tasks:**

1. **Additional tenant migrations**
   - `medications`, `prescriptions`, `prescription_items`
   - `prescription_templates`, `prescription_delivery_logs`
   - `patient_documents`, `document_categories`, `document_versions`, `document_access_logs`
   - `referrals`, `medical_certificates`

2. **Medication catalogue**
   - Searchable medication list (generic and brand names)
   - Add custom medications per tenant
   - Allergy cross-check at prescription time

3. **Prescription creation**
   - Prescribe from within encounter
   - Medication search with allergy warnings
   - Multiple items per prescription
   - Duplicate prescription warning (same drug, same patient, recent)
   - Doctor templates for common prescriptions
   - Preview before issuing

4. **PDF generation (DomPDF)**
   - Prescription PDF using doctor's configured header, footer, signature, stamp
   - Support A4 and smaller (A5) prescription layouts
   - Generated server-side, stored in tenant file storage
   - Never regenerated after issuance — stored PDF is the legal record

5. **SMTP2GO integration**
   - EmailService resolves tenant SMTP2GO credentials
   - Doctor selects "email to patient" with confirmation of destination address
   - Dispatches `SendPrescriptionEmail` queued job
   - Tracks status via `prescription_delivery_logs`
   - SMTP2GO webhook endpoint to receive delivery/bounce callbacks

6. **Referral letters and medical certificates**
   - Referral form with clinical summary
   - Medical certificate with date range, diagnosis, doctor signature
   - PDF generation using centre/doctor branding
   - Email delivery option
   - Track all issued documents

7. **Document storage**
   - Upload documents to patient profile (from encounter or profile)
   - Category selection, description, notes
   - File type and size validation
   - Secure streaming download (no public URL)
   - Version management on re-upload
   - Document access logged

8. **Testing**
   - PDF renders correctly with doctor configuration
   - Allergy warning triggers for contraindicated medications
   - Email queued and delivery status updates via webhook
   - Document download requires authentication and correct tenant
   - Issued prescription PDF cannot be replaced silently

**Dependencies for Phase 8:** Encounter-prescription linkage in place.

---

## Phase 8 — Appointments

**Objective:** Full appointment scheduling with doctor calendars, availability management, and reminders.

**Tasks:**

1. **Additional tenant migrations**
   - `doctor_schedules`, `appointments`

2. **Doctor schedule configuration**
   - Working days and hours per doctor
   - Slot duration
   - Maximum patients per slot/day
   - Breaks and recurring unavailability

3. **Calendar views (Livewire)**
   - Day, week, month views per doctor
   - Colour-coded appointment status
   - Click to book, reschedule, cancel

4. **Appointment booking**
   - Available slot calculation based on schedule and existing bookings
   - Prevent double booking
   - Walk-in vs. appointment type
   - Booking notes

5. **Appointment workflows**
   - Rescheduling with history
   - Cancellation with reason
   - No-show recording
   - Convert appointment arrival to queue entry (Phase 4 integration)

6. **Reminders**
   - Scheduled email reminders 24 hours before appointment (SMTP2GO)
   - Tracking in email_logs

7. **Follow-up scheduling**
   - `follow_ups` table migration
   - Follow-up dashboard for doctors
   - Book follow-up directly from encounter

8. **Testing**
   - Double booking prevented
   - Appointment reminders dispatched for correct tenant email config
   - Arrived appointment creates queue entry correctly

**Dependencies for Phase 9:** No hard dependencies.

---

## Phase 9 — Billing and Cash Management

**Objective:** Complete invoicing, payment collection, and receptionist shift reconciliation.

**Tasks:**

1. **Additional tenant migrations**
   - `invoices`, `invoice_items`, `payments`, `receipts`, `refunds`, `credit_notes`
   - `cash_drawers`, `cashier_shifts`, `shift_reconciliations`
   - `insurance_providers`, `corporate_accounts`

2. **Invoice generation**
   - Auto-generate from encounter services
   - Manual invoice creation
   - Service catalogue price lookup
   - Tax calculation
   - Discounts (line and total level)
   - Draft → finalized workflow
   - Sequential unique invoice numbers per tenant

3. **Invoice immutability**
   - Finalized invoices cannot be edited
   - Corrections via credit note or cancellation + replacement

4. **Payment collection**
   - Collect against invoice
   - Partial payments
   - Split payment methods
   - Payment receipt PDF
   - Refund processing with authorization

5. **Cashier shift management**
   - Shift open/close workflow
   - All payments assigned to active shift
   - Closing reconciliation: expected vs. actual per method
   - Discrepancy recording with explanation
   - Supervisor review and approval
   - Finalized shifts locked against editing

6. **Financial reports**
   - Daily revenue by method
   - Revenue by doctor
   - Revenue by service
   - Revenue by receptionist
   - Outstanding invoices list
   - Shift reconciliation report

7. **Testing**
   - Invoice total calculation accurate with tax and discounts
   - Payment reduces invoice balance correctly
   - Shift totals match sum of payments assigned to that shift
   - Finalized invoice cannot be edited

**Dependencies for Phase 10:** No hard dependencies.

---

## Phase 10 — Laboratory and Inventory

**Objective:** Lab order management with results tracking, and optional inventory module.

**Tasks:**

1. **Additional tenant migrations**
   - `laboratory_orders`, `laboratory_results`
   - `inventory_items`, `suppliers`, `stock_batches`, `stock_movements`

2. **Lab orders**
   - Order tests from within encounter
   - Configurable test catalogue
   - Order status tracking
   - Result entry by authorized staff
   - Result attachment (upload PDF report)
   - Flag out-of-range values
   - Doctor acknowledgement

3. **Inventory management**
   - Item catalogue with categories
   - Supplier management
   - Stock receipt (create batch)
   - Stock movement logging
   - Dispensing (links to encounter if enabled)
   - Low stock and expiry alerts (scheduled job)

4. **Testing**
   - Lab results flagged correctly when outside reference range
   - Doctor acknowledgement recorded
   - Inventory quantity updated correctly on movement
   - Low stock alert triggers at reorder level

**Dependencies for Phase 11:** No hard dependencies.

---

## Phase 11 — Reporting

**Objective:** Operational, financial, clinical, and administrative reports with export capability.

**Tasks:**

1. **Report engine**
   - Reusable report query service
   - Date range, branch, doctor, and other filters
   - Authorized PDF export (DomPDF)
   - Excel export (Laravel Excel / PhpSpreadsheet)

2. **Dashboard**
   - Admin: today's stats (patients, revenue, consultations, waiting time)
   - Doctor: patients seen, diagnoses, prescriptions, follow-ups
   - Finance: daily revenue, outstanding, payment methods
   - Reception: today's queue, completed, pending

3. **Report library**
   - Patient volume by period, doctor, branch
   - Average wait times
   - Doctor utilization
   - Appointment cancellation and no-show rates
   - Revenue by period, doctor, service, payment method
   - Outstanding invoices aging
   - Shift reconciliation summary
   - Prescription frequency
   - Lab order and result volumes
   - Inventory valuation and movement

4. **Testing**
   - Report totals match sum of underlying records
   - Date filters applied correctly
   - Exported PDF matches screen data
   - Unauthorized users cannot access financial reports

**Dependencies for Phase 12:** No hard dependencies.

---

## Phase 12 — Security Hardening, Testing, and Production Deployment

**Objective:** Harden, test, and deploy to production.

**Tasks:**

1. **Security review**
   - Full RBAC permission audit
   - Tenant isolation penetration testing
   - Input validation review
   - File upload security review
   - Audit log completeness check

2. **Performance**
   - Query profiling on high-volume queries
   - Add missing indexes
   - Enable Laravel query cache where appropriate
   - Optimize Livewire component polling

3. **Automated test suite completion**
   - All critical workflows covered
   - Tenant isolation tests automated
   - Financial reconciliation accuracy tests
   - Prescription issuance workflow tests

4. **Backup and recovery**
   - Full backup procedure documented and tested
   - Per-tenant restoration tested
   - Off-server backup transfer configured and verified

5. **Production configuration**
   - `APP_DEBUG=false`
   - `APP_ENV=production`
   - Config, route, and view caches enabled
   - Queue workers and Reverb supervised
   - Cron configured
   - Log rotation configured
   - Error reporting configured

6. **Documentation**
   - API documentation (internal service contracts)
   - User guide skeletons per role
   - Deployment runbook finalized
   - Backup runbook finalized

7. **Go-live checklist**
   - SSL certificate active and auto-renewing
   - Backups confirmed running
   - Queue workers running
   - WebSocket server running
   - Test tenant created and walked through full workflow
   - Platform admin credentials rotated
   - Monitoring active

---

## Phase Dependencies Summary

```
Phase 0 (Architecture)
    └─▶ Phase 1 (Foundation)
            └─▶ Phase 2 (Centre Admin)
                    └─▶ Phase 3 (Patients)
                            └─▶ Phase 4 (Reception/Queue)
                                    ├─▶ Phase 5 (Nursing)
                                    │       └─▶ Phase 6 (Consultations)
                                    │               └─▶ Phase 7 (Prescriptions/Docs)
                                    │                       └─▶ Phase 8 (Appointments)
                                    │                               └─▶ Phase 9 (Billing)
                                    │                                       └─▶ Phase 10 (Lab/Inventory)
                                    │                                               └─▶ Phase 11 (Reporting)
                                    │                                                       └─▶ Phase 12 (Production)
                                    └─▶ Phase 9 (Billing) [can begin in parallel with Phase 5]
```

Phases 5 through 10 must be implemented in sequence due to clinical workflow dependencies. Billing (Phase 9) can begin in parallel with Phase 5 if two development tracks are running.
