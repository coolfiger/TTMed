# TTMed — System Architecture

## Technology Stack

| Layer | Technology | Justification |
|---|---|---|
| Backend | Laravel 11 (PHP 8.3) | Mature, full-featured, excellent ORM and queue support |
| Frontend | Livewire 3 + Alpine.js | Server-driven reactive UI, no separate JS runtime in production |
| Styling | Tailwind CSS 3 | Utility-first, consistent design system |
| Database | MySQL 8 (database-per-tenant) | Strong multi-database support, Hestia native |
| Cache | Redis 7 | Session storage, queue backend, real-time pub/sub |
| Queues | Laravel Horizon (Redis) | Visibility into background job processing |
| Real-time | Laravel Reverb | PHP WebSocket server, no Node.js required |
| PDF | DomPDF (Blade templates) | Pure PHP, no external binary dependencies |
| Email | SMTP2GO | Reliable transactional delivery, bounce tracking |
| File Storage | Private local disk (Laravel Storage) | S3-compatible abstraction, Hestia server paths |
| Web Server | Nginx + PHP-FPM 8.3 (Hestia) | Managed by HestiaCP |

No Docker. Deployed natively on a Hestia (HestiaCP) Linux VPS.

---

## Multi-Tenant Architecture

### Strategy: Database-Per-Tenant

Each medical centre has its own isolated MySQL database. A central platform database manages tenant metadata and credentials only.

```
┌─────────────────────────────────────────────────────────────┐
│                     HESTIA VPS                               │
│                                                             │
│  ┌─────────────┐    ┌──────────────────────────────────┐   │
│  │   Nginx     │    │          Laravel App              │   │
│  │  (Hestia)   │───▶│   (single codebase, all tenants) │   │
│  └─────────────┘    └──────────┬───────────────────────┘   │
│                                │                            │
│           ┌────────────────────┼──────────────────┐        │
│           ▼                    ▼                  ▼        │
│  ┌────────────────┐  ┌──────────────┐  ┌──────────────┐   │
│  │ ttmed_platform │  │ttmed_medsouth│  │ttmed_careclinic│  │
│  │ (central DB)   │  │ (tenant DB)  │  │ (tenant DB)  │   │
│  └────────────────┘  └──────────────┘  └──────────────┘   │
│                                                             │
│  ┌────────────┐  ┌────────────┐  ┌──────────────────────┐ │
│  │   Redis    │  │  Reverb    │  │   Private File Store  │ │
│  │  (cache/q) │  │ (WebSocket)│  │  /storage/tenants/   │ │
│  └────────────┘  └────────────┘  └──────────────────────┘ │
└─────────────────────────────────────────────────────────────┘
```

### Central Platform Database (`ttmed_platform`)

Stores only:
- Tenant registry (code, name, DB credentials, status)
- Subscription plans and active subscriptions
- Platform administrator accounts
- Platform-level audit logs

### Tenant Databases (`ttmed_{tenant_code}`)

Each contains the complete operational schema for that medical centre:
- Users, roles, permissions
- Patients and clinical records
- Appointments, queues, billing
- All operational data

### Tenant File Storage

```
/home/{hestia_user}/storage/tenants/{tenant_uuid}/
    documents/
    prescriptions/
    images/
    certificates/
    backups/
```

Files are never accessible via public URLs. All downloads route through authenticated Laravel controllers that verify tenant context and user permissions before streaming the file.

---

## Request Lifecycle

```
Browser Request
      │
      ▼
   Nginx
      │
      ▼
  PHP-FPM (Laravel)
      │
      ▼
┌─── Middleware Stack ─────────────────────────────┐
│  1. EncryptCookies                               │
│  2. VerifyCsrfToken                              │
│  3. StartSession                                 │
│  4. ResolveTenant  ◀── reads session or login    │
│     └─ looks up tenant in platform DB            │
│     └─ sets active MySQL connection              │
│     └─ sets tenant context (singleton)           │
│  5. AuthenticateTenant  ◀── guards tenant routes │
│  6. EnforcePermissions  ◀── RBAC per route       │
└──────────────────────────────────────────────────┘
      │
      ▼
  Controller → Service → Repository → Tenant DB
      │
      ▼
  Livewire Component / Blade View → Response
```

---

## Tenant Identification and Authentication

### Login Flow

```
┌─────────────────────────────────────────────────┐
│              Login Screen                        │
│   Medical Centre Code: [__________]              │
│   Username:            [__________]              │
│   Password:            [__________]              │
│   [ ] Remember me                                │
│                     [Sign In]                    │
└─────────────────────────────────────────────────┘
            │
            ▼
  1. Validate medical centre code exists (platform DB)
  2. Confirm tenant is active and subscription valid
  3. Switch DB connection to tenant database
  4. Find user by username within tenant DB
  5. Verify password
  6. Check MFA if enabled
  7. Store tenant_id + tenant_db in encrypted session
  8. Record login in tenant audit log
```

The tenant code is part of every authentication attempt. Two tenants can have users with identical usernames — they are always resolved within their own database.

### Session Storage

```php
session([
    'tenant_id'   => $tenant->id,          // platform DB ID
    'tenant_uuid' => $tenant->uuid,         // immutable identifier
    'tenant_db'   => $tenant->database_name,
    'user_id'     => $user->id,
    'user_uuid'   => $user->uuid,
])
```

`ResolveTenant` middleware reads this on every request and re-establishes the correct database connection. If session is missing or tampered, the user is redirected to login.

---

## Application Layers

### Directory Structure

```
ttmed/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Platform/           # Platform admin portal
│   │   │   └── Tenant/             # Medical centre app
│   │   ├── Middleware/
│   │   │   ├── ResolveTenant.php
│   │   │   ├── EnsureTenantActive.php
│   │   │   └── CheckPermission.php
│   │   └── Requests/
│   │       ├── Platform/
│   │       └── Tenant/
│   ├── Livewire/
│   │   ├── Platform/               # Platform admin components
│   │   └── Tenant/
│   │       ├── Reception/
│   │       ├── Queue/
│   │       ├── Nursing/
│   │       ├── Consultation/
│   │       ├── Prescription/
│   │       ├── Appointments/
│   │       ├── Billing/
│   │       ├── Documents/
│   │       └── Reports/
│   ├── Models/
│   │   ├── Platform/               # Use 'platform' DB connection
│   │   │   ├── Tenant.php
│   │   │   ├── SubscriptionPlan.php
│   │   │   ├── Subscription.php
│   │   │   └── PlatformUser.php
│   │   └── Tenant/                 # Use dynamic 'tenant' DB connection
│   │       ├── User.php
│   │       ├── Patient.php
│   │       ├── Encounter.php
│   │       └── ...
│   ├── Services/
│   │   ├── TenantService.php
│   │   ├── AuthService.php
│   │   ├── PrescriptionService.php
│   │   ├── BillingService.php
│   │   ├── DocumentService.php
│   │   ├── QueueService.php
│   │   ├── EmailService.php        # SMTP2GO wrapper
│   │   └── PdfService.php
│   ├── Policies/                   # Laravel policies per tenant model
│   └── Jobs/                       # Queue jobs
├── config/
│   ├── tenancy.php
│   └── ...
├── database/
│   ├── migrations/
│   │   ├── platform/               # Run on ttmed_platform
│   │   └── tenant/                 # Run on each tenant DB
│   └── seeders/
│       ├── PlatformSeeder.php
│       └── TenantSeeder.php
├── resources/
│   ├── views/
│   │   ├── layouts/
│   │   │   ├── platform.blade.php
│   │   │   └── app.blade.php
│   │   ├── platform/
│   │   └── livewire/
│   │       ├── reception/
│   │       ├── queue/
│   │       ├── nursing/
│   │       ├── consultation/
│   │       └── ...
│   ├── css/
│   └── js/
│       └── app.js                  # Alpine.js + minimal JS
├── routes/
│   ├── platform.php                # Platform admin routes
│   ├── web.php                     # Tenant application routes
│   └── auth.php
├── storage/
│   └── tenants/                    # Symlinked or configured per tenant
├── tests/
│   ├── Feature/
│   │   ├── TenantIsolation/
│   │   ├── Authentication/
│   │   ├── Billing/
│   │   └── Clinical/
│   └── Unit/
└── ...
```

---

## Role-Based Access Control

Permissions are defined as named slugs grouped by module. Roles are assigned a set of permissions. Users are assigned one or more roles.

```
platform.super_admin
    └── all permissions

tenant.admin
    └── all tenant permissions

tenant.doctor
    ├── patients.view
    ├── encounters.create
    ├── encounters.sign
    ├── prescriptions.issue
    ├── diagnoses.create
    ├── lab_orders.create
    ├── referrals.create
    └── ...

tenant.receptionist
    ├── patients.register
    ├── patients.search
    ├── queue.manage
    ├── invoices.create
    ├── payments.collect
    └── ...
```

Permissions are enforced in:
1. Backend middleware (`CheckPermission`)
2. Laravel Policies (model-level authorization)
3. Blade/Livewire conditionals (UI visibility only — not a security boundary)

The backend is always the authority. Frontend hiding is convenience, not security.

---

## Real-Time Architecture

Queue management and reception screens require live updates. Laravel Reverb runs as a PHP WebSocket server daemon on the Hestia VPS.

```
Doctor calls next patient
        │
        ▼
  QueueService::callNext()
        │
        ▼
  Broadcasts: QueueUpdated event (tenant-scoped channel)
        │
        ▼
  Reverb WebSocket Server
        │
        ▼
  Reception Dashboard (Livewire)
  Waiting Room Display
  Nurse Dashboard
```

Reverb channels are prefixed by tenant UUID, ensuring one tenant's broadcasts cannot be received by another tenant's clients.

---

## Background Processing

Laravel Horizon manages Redis queues. Jobs that run asynchronously:

- PDF generation (prescriptions, certificates, reports)
- Email delivery via SMTP2GO
- Appointment reminder emails
- Backup execution
- Document malware scan (ClamAV if installed)
- Lab result notifications
- Report generation

Queue workers run as supervised daemons via Hestia's service management or a Supervisor configuration.

---

## SMTP2GO Integration

Each medical centre configures its own SMTP2GO credentials. Stored encrypted in `centre_settings`. The `EmailService` resolves the sending identity from the current tenant context before dispatching.

Delivery tracking is maintained in `email_logs` by webhook callback from SMTP2GO (delivered, bounced, failed).

---

## Platform Administration Portal

Served at `/platform` with its own route group, middleware group, and authentication guard. Platform users never share session context with tenant users. A platform admin cannot access tenant clinical data without explicit, logged support access that is time-limited and auditable.

---

## Key Architectural Risks and Mitigations

| Risk | Mitigation |
|---|---|
| Tenant isolation breach via URL manipulation | All queries scoped through tenant DB connection; no cross-DB foreign keys; automated isolation tests |
| DB credential exposure | Credentials stored encrypted in platform DB; never in .env; never logged |
| Signed record tampering | Signed encounters/prescriptions are immutable; corrections create amendment records with full audit trail |
| File access without authorization | All file access through authenticated controller; file paths never exposed in responses |
| Tenant DB failure | Each tenant DB isolated; one failure does not affect others; per-tenant restore procedures |
| Session fixation / hijacking | Session regeneration on login; encrypted session driver; HttpOnly/Secure cookies |
| Mass assignment | All models use `$fillable`; strict mode enabled in production |
