# TTMed — Implementation Progress

---

## Phase 0 — Architecture and Planning

**Status: COMPLETE**
**Completed:** 2026-11-18

### Completed

- [x] Environment assessed (PHP 8.3, MySQL, Redis, Node 22, Nginx/Hestia)
- [x] Final technology stack confirmed: Laravel 11, Livewire 3, Alpine.js, Tailwind, MySQL, Redis, Reverb
- [x] Multi-tenant database-per-tenant architecture designed
- [x] Central platform database schema designed
- [x] Tenant database schema designed (all entities)
- [x] Authentication and tenant resolution flow designed
- [x] Role-based access control architecture designed
- [x] Secure file storage strategy defined
- [x] Background processing and queue architecture defined
- [x] SMTP2GO integration approach designed
- [x] WebSocket/real-time architecture designed (Reverb)
- [x] Hestia deployment architecture defined
- [x] Backup and recovery strategy defined
- [x] Application directory structure defined
- [x] 12-phase implementation roadmap created

### Documents Created

- [x] README.md
- [x] ARCHITECTURE.md
- [x] DATABASE.md
- [x] SECURITY.md
- [x] DEPLOYMENT.md
- [x] IMPLEMENTATION_ROADMAP.md
- [x] IMPLEMENTATION_PROGRESS.md

### Outstanding Decisions

None — all architectural decisions resolved.

### Dependencies Confirmed for Phase 1

- MySQL 8 available on target Hestia server ✓
- PHP 8.3 FPM available ✓
- Redis available ✓
- Node 22 available (build only) ✓
- Deployment host confirmed: Hestia VPS ✓

---

## Phase 1 — Foundation

**Status: COMPLETE**
**Completed:** 2026-11-18

### Completed

- [x] Laravel 13 project initialised (PHP 8.3)
- [x] Multi-tenant database configuration (platform + tenant connections, SQLite test override)
- [x] Dual auth guards (`tenant` guard → tenant DB users, `platform` guard → platform DB)
- [x] TenantContext singleton
- [x] TenantDatabaseManager (connect, createTenantDatabase, dropTenantDatabase, encrypt/decrypt)
- [x] ResolveTenant middleware (subdomain extraction, session fallback, custom domain support)
- [x] EnsureTenantActive middleware
- [x] CheckPermission middleware
- [x] TenantNotFoundException and TenantInactiveException
- [x] Platform migrations: tenants, subscription_plans, subscriptions, platform_users, platform_audit_logs
- [x] Tenant migrations: branches, roles/permissions/role_permissions, users/user_roles, centre_settings, audit_logs
- [x] All 60+ permissions seeded (TenantDefaultRolesSeeder)
- [x] 12 system roles seeded with full permission mapping
- [x] PlatformSeeder (3 subscription plans, super admin account)
- [x] TenantProvisioningService (DB creation, migrations, seed, admin user, default branch, storage directories)
- [x] AuditService (tenant and platform audit logging)
- [x] Platform models: Tenant, SubscriptionPlan, Subscription, PlatformUser, PlatformAuditLog
- [x] Tenant models: User (RBAC methods, lockout), Role, Permission, Branch, AuditLog
- [x] Livewire: Tenant login (with MFA) and Platform login (with MFA)
- [x] Livewire: PlatformDashboard, TenantList, TenantForm, TenantShow
- [x] Livewire: PlanList, PlanForm, PlatformUserList
- [x] Livewire: TenantDashboard, UserList, UserForm, RoleList, RoleForm
- [x] AppServiceProvider: singletons registered, preventSilentlyDiscardingAttributes
- [x] Platform routes (platform.ttmed.app prefix)
- [x] Tenant routes (resolve.tenant + tenant.active middleware)
- [x] Artisan commands: tenants:migrate, tenants:list
- [x] Layouts: app.blade.php (tenant, permission-aware nav), platform.blade.php, guest.blade.php, platform-guest.blade.php
- [x] nav-item Blade component (active state highlighting)
- [x] Error views: tenant-not-found.blade.php, tenant-inactive.blade.php
- [x] .env.example (all required variables documented)
- [x] vite.config.js (Tailwind CSS, no external font dependency)
- [x] Frontend assets built (npm run build)
- [x] Tests: 27 unit/feature tests, 27/27 passing
- [x] PHP syntax validated across all 59 source files

### Notes

- Tenant DB passwords encrypted at rest (AES-256-CBC, key from TENANT_ENCRYPTION_KEY)
- Storage paths outside web root: /home/{user}/ttmed/storage/tenants/{uuid}/
- locked_until cast to datetime for correct Carbon comparisons
- Laravel version: 13.x, PHP 8.3

---

## Phase 2 — Medical Centre Administration

**Status: COMPLETE**
**Completed:** 2026-11-18

### Completed

- [x] Tenant migration: staff_profiles (SoftDeletes, FK to users + branches)
- [x] Tenant migration: doctors (FK to staff_profiles, registration_number, specialty, file paths)
- [x] Tenant migration: doctor_operating_hours (7-day schedule per doctor)
- [x] Tenant migration: service_categories, services (prices in cents, tax_rate, taxable)
- [x] Tenant migration: insurance_providers
- [x] Tenant migration: document_categories (system-flagged)
- [x] Tenant migration: branch_operating_hours (7-day schedule per branch)
- [x] Models: StaffProfile (full_name accessor, SoftDeletes), Doctor, DoctorOperatingHour, BranchOperatingHour
- [x] Models: Service (price_formatted + price_with_tax accessors, cents casting), ServiceCategory
- [x] Models: InsuranceProvider, DocumentCategory
- [x] Branch model extended: operatingHours HasMany, staff HasMany
- [x] CentreSettingsService: get/set/getMany/setMany, type casting (string/boolean/integer/json/encrypted)
- [x] CentreSettingsService registered as singleton
- [x] TenantDefaultCatalogueSeeder: 8 document categories, 7 service categories, 5 default services (TTD pricing)
- [x] TenantProvisioningService updated to seed catalogue on new tenant
- [x] TenantDefaultRolesSeeder: added branches/services/insurance permissions (view + manage); assigned to manager role
- [x] Livewire: BranchList (toggleActive, blocks deactivating main branch)
- [x] Livewire: BranchForm (create/edit + 7-day operating hours via updateOrCreate)
- [x] Livewire: CentreSettings (tabbed — general settings + SMTP email config with encrypted password, test email)
- [x] Livewire: StaffList (paginated, search, branch filter)
- [x] Livewire: StaffForm (User + StaffProfile + optional Doctor + file uploads + doctor hours)
- [x] Livewire: ServiceList (paginated, category/status filters, toggleActive)
- [x] Livewire: ServiceForm (dollar input → cents, code forced uppercase)
- [x] Livewire: InsuranceList (paginated, search, toggleActive)
- [x] Livewire: InsuranceForm (code forced uppercase)
- [x] Blade views for all 9 Phase 2 Livewire components
- [x] Routes: branches, settings, staff, services, insurance with permission middleware
- [x] Sidebar nav updated: Services, Insurance, Branches links
- [x] Tests\TestCase base class created
- [x] Phase 2 tests: ServiceModelTest, StaffModelTest, CentreSettingsServiceTest (42 tests total, 42 passing)

### Notes

- SMTP password stored encrypted, never loaded back into Livewire state (write-only)
- Doctor file uploads (signature, stamp, prescription logo) stored in tenant private storage outside web root
- Prices stored as integers (cents) throughout; displayed as dollars via accessor
- Main branch cannot be deactivated

---

## Phase 3 — Patient Management

**Status: COMPLETE**
**Completed:** 2026-11-18

### Completed

- [x] Migration: patients (demographics, insurance, clinical assignment, SoftDeletes)
- [x] Migration: patient_contacts (emergency contacts, next of kin)
- [x] Migration: patient_allergies, patient_conditions, patient_medications, patient_medical_histories
- [x] Migration: patient_consents
- [x] Migration: patient_physical_files, patient_file_movements, patient_number_sequences
- [x] Model: Patient (full_name/display_name/age accessors, search scope, all relationships)
- [x] Model: PatientContact, PatientAllergy (severity_label), PatientCondition, PatientMedication
- [x] Model: PatientMedicalHistory, PatientConsent
- [x] Model: PatientPhysicalFile (location_summary accessor), PatientFileMovement
- [x] PatientNumberService: sequential numbers in DB transaction, configurable prefix + pad, never reused
- [x] PatientDuplicateDetector: DOB+name, phone, fuzzy name; excludes self on edit
- [x] Services registered as singletons
- [x] Livewire: PatientSearch (paginated, search, DOB filter, branch filter)
- [x] Livewire: PatientForm (full registration/edit, duplicate detection with confirm-bypass)
- [x] Livewire: PatientProfile (tabbed: personal + allergies + contacts, medical history, consents, physical file + movement log)
- [x] Routes: patients.index, patients.show, patients.create, patients.edit with permission middleware
- [x] Tests: 57 passing (PatientModelTest, PatientNumberServiceTest, PatientDuplicateDetectorTest)

### Notes

- Patient numbers are tenant-scoped, sequential, and never reused (locked DB transaction)
- Duplicate detection runs before registration; user must explicitly confirm to proceed
- Physical file movement history is append-only (no deletes)

---

## Phase 4 — Reception and Queue Management

**Status: COMPLETE**
**Completed:** 2026-11-18

### Completed

- [x] Migration: encounters (skeleton — full clinical fields added in Phase 6)
- [x] Migration: queue_entries (queue_number unique per branch+date, all statuses, is_priority, FK to encounters/patients/branches/doctors)
- [x] Migration: queue_events (append-only audit trail of every status change and action)
- [x] Migration: queue_number_sequences (per-branch per-day, locked transaction)
- [x] Migration: doctor_availability (per-doctor per-branch status, upsert pattern)
- [x] Model: Encounter (relationships to patient/branch/doctor/queueEntry, timestamp casts)
- [x] Model: QueueEntry (status_label/status_color accessors, canTransitionTo(), VALID_TRANSITIONS map, scopeActive/scopeForBranchToday)
- [x] Model: QueueEvent (append-only, no timestamps, occurred_at cast)
- [x] Model: DoctorAvailability (status_label/status_color/isAvailable, explicit table name)
- [x] QueueNumberService: sequential per branch+date, lockForUpdate transaction, never repeats, currentMax()
- [x] QueueService: checkIn(), transition() with valid-state enforcement, assignDoctor(), transferToDoctor(), setPriority(), updateDoctorAvailability(); broadcasts QueueUpdated on every mutation
- [x] QueueUpdated broadcast event: tenant-scoped channel (tenant.{uuid}.branch.{branchId}.queue), implements ShouldBroadcast, entryPayload() helper
- [x] AppServiceProvider: QueueNumberService + QueueService registered as singletons
- [x] Livewire: ReceptionDashboard (patient search + check-in form, live queue list, doctor status panel, branch switcher, real-time Echo listener)
- [x] Livewire: QueueManager (advance/cancel/no-show, transfer modal, priority modal, doctor availability modal, branch+status+doctor filters, real-time Echo listener)
- [x] Blade views for ReceptionDashboard and QueueManager
- [x] Routes: /reception and /queue behind queue.view permission
- [x] Nav: Reception + Queue links under queue.view permission
- [x] Tests: 33 Phase 4 tests passing (QueueEntryModelTest, QueueNumberServiceTest, QueueServiceTest)
- [x] Full suite: 90/90 passing

### Notes

- Queue numbers reset daily per branch; never reused within a branch+date
- State machine enforced server-side via VALID_TRANSITIONS — invalid transitions throw InvalidArgumentException
- WebSocket channel is tenant-UUID-scoped; no cross-tenant broadcast possible
- doctor_availability uses explicit table name (not pluralized) to match migration
- Encounter is a skeleton here; full clinical fields (vitals, diagnoses, SOAP notes) added in Phase 6

---

## Phase 5 — Nursing, Lab, and Imaging

**Status: COMPLETE**
**Completed:** 2026-11-18

### Migrations
- [x] `vital_signs` — cardiovascular, respiratory, anthropometrics, blood glucose, pain score
- [x] `nursing_assessments` — per-encounter, allergies/medications reviewed flags, status enum
- [x] `nursing_procedures` — typed procedure log per encounter
- [x] `lab_test_categories`, `lab_tests` (LOINC codes), `lab_panels`, `lab_panel_tests` — lab catalogue
- [x] `lab_orders` — LIS placer/filler order numbers, integration_type, ordered_at / sent / collected / received timestamps
- [x] `lab_order_items` — denormalized test name for history
- [x] `lab_results` — flag enum, is_critical, source (manual/hl7_v2/fhir_r4), raw_message audit column
- [x] `lab_result_attachments` — private storage_path, never public URL
- [x] `lab_order_number_sequences` — per-day sequential number lock
- [x] `imaging_orders` — DICOM modality enum, accession_number UNIQUE, ris_order_id, hl7_placer_order_number, priority
- [x] `imaging_studies` — study_instance_uid UNIQUE (DICOM), pacs_study_url (internal), pacs_system (vepro/dcm4chee)
- [x] `imaging_reports` — report_status (preliminary/final/amended), report_pdf_path (private), source (manual/hl7_v2/vepro/fhir), is_critical
- [x] `imaging_order_number_sequences`
- [x] `integration_configs` — HMAC signing secrets encrypted at rest, api_key_encrypted, mllp_host/port, HL7 app/facility identifiers

### Models
- [x] `VitalSign` — auto-calculates BMI on save, bmi_category and bp_display accessors
- [x] `NursingAssessment` — isComplete() check, procedures() relationship
- [x] `NursingProcedure` — TYPES const, type_label accessor
- [x] `LabOrder` — STATUS_* constants, hasResults(), hasCriticalResults()
- [x] `LabOrderItem`, `LabResult` (flag_label, flag_color, isAbnormal()), `LabResultAttachment`
- [x] `LabTest` (loinc_code), `LabTestCategory`, `LabPanel`
- [x] `ImagingOrder` — MODALITIES const, modality_label/status_label accessors
- [x] `ImagingStudy`, `ImagingReport` — isFinal()
- [x] `IntegrationConfig` — setApiKey/getApiKey/setInboundSecret/getInboundSecret (Crypt), verifyHmac (hash_equals), labLis()/imagingRis()/imagingPacs() scoped finders

### Services
- [x] `Hl7MessageService` — ORU^R01 parser, ORM^O01 builder, ACK builder; handles both LOINC-first and local-code-with-alternate-LOINC CWE formats
- [x] `LabOrderService` — createOrder, markSpecimenCollected, cancelOrder, receiveHl7Result (ORU^R01), receiveFhirDiagnosticReport (R4 DiagnosticReport with contained Observations), storeManualResult; outbound via MLLP or HTTP; sequential order numbers with lockForUpdate
- [x] `ImagingOrderService` — createOrder, cancelOrder, receiveHl7Report (ORU^R01), receiveVeproReport (VEPRO JSON), storeManualReport; outbound to RIS via HL7 MLLP or VEPRO REST API; accession number generation
- [x] `NursingService` — recordVitals, getOrCreateAssessment, updateAssessment, addProcedure, completeNursingAndTransition (wraps QueueService::transition)

### API Controllers (inbound integration — HMAC-verified, no session auth)
- [x] `LabInboundController` — POST /api/lab/inbound/hl7, POST /api/lab/inbound/fhir
- [x] `ImagingInboundController` — POST /api/imaging/inbound/hl7, POST /api/imaging/inbound/vepro

### Livewire
- [x] `NurseDashboard` — queue panel, patient selection, tabbed form (Vitals / Assessment / Procedures), live BMI calculation as weight/height typed, "Complete & Send to Doctor" triggers QueueService transition

### Blade Views
- [x] `livewire/tenant/nursing/nurse-dashboard.blade.php`

### Routes
- [x] GET `/nursing` — `NurseDashboard`, behind `nursing.assess` permission
- [x] POST `/api/lab/inbound/hl7` and `/api/lab/inbound/fhir`
- [x] POST `/api/imaging/inbound/hl7` and `/api/imaging/inbound/vepro`

### AppServiceProvider
- [x] `Hl7MessageService`, `LabOrderService`, `ImagingOrderService`, `NursingService` registered as singletons

### Tests — 32 passing
- [x] `Hl7MessageServiceTest` — parsing (patient, order, results, LOINC both CWE formats, critical flags), building (ORM^O01, ACK AA/AE), edge cases (empty message, no results, 5-OBX)
- [x] `VitalSignModelTest` — BMI calculation (all categories), zero-weight/height guards, BP display
- [x] `LabOrderServiceTest` — order creation, sequential numbering, item creation, HL7 result ingestion, unknown order returns false, status update, cancel validation

### Notes
- VEPRO integration type documented; real VEPRO credentials go in `integration_configs` as encrypted values, never hardcoded
- All inbound webhook endpoints verify HMAC signature with `hash_equals` before processing
- Raw HL7/FHIR/VEPRO messages stored in `raw_message` for audit trail
- `pacs_study_url` is internal-only — never exposed via public URL
- `report_pdf_path` is private — served only through authenticated file controller (Phase 7)

---

## Phase 6 — Doctor Consultations

**Status: COMPLETE**
**Completed:** 2026-11-18

### Migrations
- [x] `icd10_codes` — code UNIQUE, description, category_code/description, is_active; index on [code, description] for search
- [x] `consultation_notes` — 1:1 to encounters, SOAP fields (subjective/objective/assessment/plan), examination_findings, investigations_ordered, follow_up_instructions/days, finalization fields (is_finalized, finalized_by, finalized_at), `content_hash` VARCHAR(64)
- [x] `diagnoses` — encounter_id/patient_id FK, icd10_code_id nullable FK + denormalized icd10_code + diagnosis_text, diagnosis_type enum (primary/secondary/differential), certainty enum (confirmed/probable/suspected/ruled_out)
- [x] `referrals` — referred_to_name/specialty/facility, urgency enum (routine/urgent/emergency), reason, clinical_summary, instructions_to_patient, status enum (pending/sent/accepted/completed/cancelled), sent_at/appointment_date timestamps, feedback_notes

### Models
- [x] `ConsultationNote` — `isFinalized()`, `computeContentHash()` (SHA-256 of encounter_id + SOAP fields), `hasContent()`, `$attributes` default for `is_finalized = false`
- [x] `Icd10Code` — `scopeSearch()` (LIKE on code + description), `scopeActive()`, `getDisplayAttribute()`
- [x] `Diagnosis` — type/certainty label + color accessors
- [x] `Referral` — urgency color accessor

### Services
- [x] `ConsultationService` — `getOrCreateNote()`, `updateNote()` (blocked if finalized), `finalizeNote()` (sets hash, auditLogs, throws if already finalized or empty), `addDiagnosis()` (auto-resolves icd10_code from FK), `removeDiagnosis()`, `addReferral()`, `completeConsultationAndTransition()` (transitions queue to `completed`, marks encounter `ended_at`)

### Livewire
- [x] `DoctorDashboard` — queue showing `awaiting_doctor` patients; full SOAP note with 6 tabs (SOAP / Vitals / Diagnoses / Labs / Imaging / Referrals); ICD-10 autocomplete search; finalize flow with confirm dialog; complete encounter (requires finalized note); note read-only after finalization

### Blade Views
- [x] `livewire/tenant/doctor/doctor-dashboard.blade.php`

### Routes
- [x] GET `/consultations` — `DoctorDashboard`, behind `encounters.view` permission

### AppServiceProvider
- [x] `ConsultationService` registered as singleton

### Seeder
- [x] `Icd10CodeSeeder` — 60+ codes covering hypertension, diabetes (Types 1 & 2 with complications), dyslipidaemia, obesity, respiratory, GI, musculoskeletal (gout), renal/CKD, tropical infectious (dengue, COVID-19), mental health, common symptoms; uses upsert (re-runnable)

### Tests — 13 passing
- [x] `ConsultationServiceTest` — note creation, idempotent getOrCreate, SOAP update, update blocked when finalized, finalize sets hash and lock, hash value correctness, re-finalize blocked, empty-note finalize blocked, hash changes on content change, hash stable on no change, diagnosis add, diagnosis remove, ICD code auto-resolved from FK

### Notes
- `content_hash` is SHA-256 of `encounter_id|subjective|objective|assessment|plan|examination_findings`; stored at finalization; any post-hoc silent edit is detectable by recomputing
- Finalized notes are read-only in the UI and in the service layer
- Diagnoses store both FK and denormalized `icd10_code` column so historical accuracy is preserved if ICD-10 catalogue changes
- Nav entry for Consultations already existed in `app.blade.php` (Phase 4 scaffold)

---

## Phase 7 — Prescriptions and Documents

**Status: COMPLETE**
**Completed:** 2026-11-18

### Migrations
- [x] `medications` — name, generic_name, brand_names (JSON), strength/strength_unit, form enum (tablet/capsule/liquid/syrup/suspension/injection/cream/ointment/gel/inhaler/drops/suppository/patch/powder/other), route enum, drug_class, is_active, is_custom
- [x] `prescriptions` — prescription_number UNIQUE, encounter/patient/doctor/branch FKs, status enum (draft/issued/cancelled), allergy_checked, pdf_path (set once at issuance, never overwritten), issued/cancelled timestamps + actors
- [x] `prescription_items` — denormalized medication fields (name/strength/form/route), dose, frequency, duration/duration_unit, quantity, instructions, refills_allowed, sort_order
- [x] `prescription_templates` — doctor-specific reusable item sets (JSON)
- [x] `prescription_number_sequences` — per-day sequential locking
- [x] `prescription_delivery_logs` — SMTP2GO message tracking, status enum (pending/sent/delivered/failed/bounced)
- [x] `patient_documents` — absolute private storage_path (never public URL), mime_type, file_size_bytes, version, is_archived
- [x] `document_versions` — version history with per-version storage paths
- [x] `document_access_logs` — view/download/print events with IP; no updated_at
- [x] `medical_certificates` — certificate_number UNIQUE, certificate_type enum (sick_leave/fit_for_work/fitness/medical_report/other), from/to dates, diagnosis_text, pdf_path
- [x] `certificate_number_sequences` — per-day sequential locking

### Models
- [x] `Medication` — scopeActive(), scopeSearch(), getDisplayAttribute()
- [x] `Prescription` — STATUS_* constants, isDraft/isIssued/isCancelled/hasItems()
- [x] `PrescriptionItem` — getDurationLabelAttribute()
- [x] `PrescriptionTemplate` — items cast to array
- [x] `PrescriptionDeliveryLog` — getStatusColorAttribute()
- [x] `PatientDocument` — getExtensionAttribute(), getFileSizeLabelAttribute(), isViewable()
- [x] `DocumentVersion`, `DocumentAccessLog` ($timestamps = false)
- [x] `MedicalCertificate` — isIssued(), getTypeLabelAttribute(), getDurationDaysAttribute()

### Services
- [x] `PrescriptionService` — getOrCreateDraft, addItem (throws if not draft), removeItem (throws if not draft), checkAllergies (case-insensitive), issuePrescription (DB transaction: generate PDF → set pdf_path once → audit), cancelPrescription, queueEmailDelivery; nextPrescriptionNumber (lockForUpdate, format RX-{Ymd}-{0001}); generatePdf (DomPDF → private storage)
- [x] `DocumentService` — upload (20MB limit, MIME allowlist), addVersion (archives current), streamDownload (file_exists check + access log + chunked streamDownload — never public URL), logAccess
- [x] `MedicalCertificateService` — create (DB transaction, number generation, PDF generation, audit); nextCertificateNumber (MC-{Ymd}-{0001})

### Jobs
- [x] `SendPrescriptionEmailJob` — ShouldQueue, 3 tries; reads SMTP credentials from CentreSettingsService (host/port/username/encrypted password/from); configures mail.mailers.smtp dynamically; attaches PDF; updates delivery log on success/failure; failed() handler marks log as failed

### Controllers
- [x] `DocumentDownloadController` — permission check (documents.view), PatientDocument::on('tenant')->findOrFail, delegates to DocumentService::streamDownload
- [x] `PrescriptionDownloadController` — permission check (prescriptions.view), only issued prescriptions, file_exists guard, chunked streamDownload
- [x] `Smtp2goWebhookController` — HMAC-SHA256 verification (hash_equals), maps event to delivery log status (delivered/bounced/failed)
- [x] `Controller` base class added (was missing)

### Livewire
- [x] `PrescriptionForm` — medication search with debounce autocomplete, selectMedication populates item form, addItem/removeItem (tenant-scoped), allergy warning banner with mark-reviewed flow, issuePrescription with confirm dialog, read-only state after issuance
- [x] `PatientDocuments` — Livewire WithFileUploads, upload form with category selector, filter by category + archived toggle, download link

### Blade Views
- [x] `livewire/tenant/prescriptions/prescription-form.blade.php`
- [x] `livewire/tenant/documents/patient-documents.blade.php`
- [x] `pdf/prescription.blade.php` — DejaVu Sans, centre header, ℞ banner, patient block, items table, footer with signature line
- [x] `pdf/medical-certificate.blade.php` — certificate title (uppercase, bordered), body text varies by certificate_type, details box, footer

### Routes
- [x] GET `/prescriptions/encounter/{encounterId}` — `PrescriptionForm`, behind `prescriptions.create`
- [x] GET `/prescriptions/{id}/download` — `PrescriptionDownloadController`, behind `prescriptions.view`
- [x] GET `/patients/{patientId}/documents` — `PatientDocuments`, behind `documents.view`
- [x] GET `/documents/{id}/download` — `DocumentDownloadController`, behind `documents.view`
- [x] POST `/api/smtp2go/webhook` — `Smtp2goWebhookController` (no session auth — HMAC-verified)

### AppServiceProvider
- [x] `PrescriptionService`, `DocumentService`, `MedicalCertificateService` registered as singletons

### Seeders
- [x] `MedicationSeeder` — 70+ medications covering antihypertensives, diabetes, statins, analgesics/NSAIDs, antibiotics, GI agents, respiratory (inhalers/steroids/antihistamines), mental health (SSRIs/benzodiazepines), thyroid, vitamins; upsert on [name, strength, form]

### Tests — 13 passing
- [x] `PrescriptionServiceTest` — draft creation, idempotent getOrCreate, number format (RX-{Ymd}-{0001}), sequential numbers, add item, cannot add to non-draft, remove item, cannot remove from non-draft, cannot issue empty prescription, allergy conflict detected, no conflict on no match, cancel, cannot cancel twice

### Security
- [x] Documents never served via public URL — always through authenticated controller with access logging
- [x] Prescription PDF set once at issuance inside DB transaction; no overwrite path exists
- [x] SMTP credentials read from encrypted centre_settings at job runtime — never hardcoded
- [x] SMTP2GO webhook verified with HMAC-SHA256 (hash_equals) before processing
- [x] DocumentDownloadController and PrescriptionDownloadController both check tenant guard + permission before serving any file

### Notes
- Prescription numbers: RX-{Ymd}-{0001}, resets daily, lockForUpdate prevents duplicate numbers under concurrency
- Certificate numbers: MC-{Ymd}-{0001}, same pattern
- PDF files stored in {tenantStorage}/prescriptions/ and {tenantStorage}/certificates/ respectively
- Document storage at {tenantStorage}/documents/ — absolute path stored in DB, never relative

---

## Phase 8 — Appointments

**Status: COMPLETE** — 14/14 tests passing

### Deliverables
- Migration: `doctor_schedules`, `doctor_schedule_breaks`, `doctor_unavailability`, `appointments`, `appointment_number_sequences`, `follow_ups`, `appointment_reminders`
- Models: `Appointment`, `DoctorSchedule`, `DoctorScheduleBreak`, `DoctorUnavailability`, `FollowUp`, `AppointmentReminder`
- `AppointmentService`: schedule management, slot generation, booking with `lockForUpdate` double-booking prevention, per-day cap enforcement, cancel, reschedule (self-FK linking), markArrived, markNoShow, follow-up scheduling, appointment number sequences (`APT-{Ymd}-{0001}`)
- `SendAppointmentReminderJob`: queued, 3 retries, SMTP credentials from `CentreSettingsService`
- Livewire: `AppointmentCalendar`, `DoctorScheduleConfig`, `FollowUpDashboard`
- Routes: `/appointments`, `/appointments/follow-ups`, `/appointments/schedule/{doctorId}`
- Permissions: `appointments.view`, `appointments.create`, `appointments.edit`, `appointments.cancel`, `appointments.manage` added to seeder and role assignments

---

## Phase 9 — Billing and Cash Management

**Status: NOT STARTED**

---

## Phase 10 — Laboratory and Inventory

**Status: NOT STARTED**

---

## Phase 11 — Reporting

**Status: NOT STARTED**

---

## Phase 12 — Security, Testing, and Production Deployment

**Status: NOT STARTED**
