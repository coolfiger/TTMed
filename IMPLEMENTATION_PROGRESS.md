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

**Status: NOT STARTED**

---

## Phase 7 — Prescriptions and Documents

**Status: NOT STARTED**

---

## Phase 8 — Appointments

**Status: NOT STARTED**

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
