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

## Phase 5 — Nursing and Triage

**Status: NOT STARTED**

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
