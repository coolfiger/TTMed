# TTMed — Security Architecture

## Principles

- Defense in depth: multiple independent control layers
- Least privilege: users access only what their role requires
- Tenant isolation: architectural, not just filtered queries
- Privacy by design: patient data minimized, access logged
- Immutability: signed clinical and financial records cannot be silently altered

---

## Authentication

### Tenant-Scoped Login

Authentication always resolves tenant first. The login form accepts three fields: tenant code, username, and password. Two users at different tenants may have the same username — they are distinguished by tenant context, not username alone.

```
1. Validate tenant code exists and is active (platform DB)
2. Switch database connection to tenant DB
3. Find user by username in tenant DB
4. Verify bcrypt password hash
5. Check account is active and not locked
6. Check MFA if enabled
7. Regenerate session ID (prevents session fixation)
8. Store encrypted tenant context in session
9. Record successful login in tenant audit_log
```

Failed login attempts are counted per username per tenant. After 5 consecutive failures, the account is locked for 15 minutes. Unlocking requires either waiting or an administrator action.

### Session Security

- Driver: database (stored in `user_sessions` within tenant DB)
- Encryption: Laravel's encrypted session driver (AES-256)
- Cookie flags: `HttpOnly`, `Secure`, `SameSite=Strict`
- Lifetime: 2 hours idle timeout, configurable per tenant
- Session regenerated on login and privilege escalation
- Session invalidated on logout, password change, and admin force-logout

### Password Requirements

- Minimum 10 characters
- Mixed case, numbers, and special characters enforced
- Hashed with bcrypt (cost factor 12)
- Password history: last 5 passwords rejected
- Forced change on first login and administrator reset

### Multi-Factor Authentication (MFA)

- TOTP-based (Google Authenticator, Authy compatible)
- Mandatory for: Platform Super Admin, Platform Admin
- Optional for: all tenant users (configurable per tenant)
- Backup codes generated at enrollment (one-time use, hashed)
- Recovery via administrator reset with audit trail

---

## Authorisation

### Backend Enforcement

Permissions are enforced at three levels. All three must pass for a request to succeed.

**1. Route middleware** — `CheckPermission` middleware reads the required permission slug from the route definition and verifies the authenticated user holds that permission via their roles.

**2. Laravel Policies** — Model-level authorization. Every controller action on a sensitive model calls `$this->authorize()`. Policies verify both the permission and the tenant context (the model must belong to the current tenant's DB).

**3. Service layer checks** — Critical operations (signing an encounter, finalizing an invoice, issuing a prescription) contain explicit authorization assertions before executing.

Frontend visibility (Blade/Livewire conditionals) is a convenience layer only, not a security boundary.

### Tenant Isolation Enforcement

The `ResolveTenant` middleware sets a Laravel database connection named `tenant` pointing to the authenticated user's database. All tenant models use `protected $connection = 'tenant'`. No cross-database foreign keys exist.

An attacker manipulating URL parameters or API payloads to reference another tenant's IDs will fail because:
- The query executes against the wrong database (their own)
- The ID simply does not exist in their database
- No data from another tenant is returned or modified

Automated isolation tests create two tenants, create records in each, and assert that requests authenticated as tenant A cannot read, write, or enumerate records belonging to tenant B.

### Platform Admin Access to Tenant Data

Platform administrators cannot access tenant clinical records through the normal application. Any support access must be:
- Explicitly created as a time-limited access grant (maximum 24 hours)
- Justified with a written reason
- Recorded in `platform_audit_logs` with start time, end time, tenant, and administrator
- Reviewed by a second platform administrator for access to clinical records
- Revocable immediately

---

## Data Integrity

### Signed Clinical Records

Consultation encounters are signed when the doctor marks them complete. After signing:
- The `encounters.signed_at` and `encounters.signed_by` fields are set
- The encounter `status` changes to `signed`
- Direct edits to signed encounter fields are blocked at the service layer
- Any correction must create a row in `encounter_amendments` with the previous value, new value, reason, amending user, and timestamp
- The original signed content is preserved and viewable

### Finalized Financial Records

Invoices move from `draft` to `finalized`. After finalization:
- Invoice fields cannot be edited
- Items cannot be added or removed
- Corrections require a credit note or cancellation with a replacement invoice
- All such actions are audit logged

Payments are immutable after creation. Errors are corrected via refund records, never by editing the original payment row.

---

## Input Validation and Injection Prevention

- All user input validated via Laravel Form Request classes before reaching controllers
- Eloquent ORM used exclusively — no raw SQL queries
- Where raw queries are unavoidable, PDO parameterized bindings are mandatory
- Mass assignment protection: all models use `$fillable` (never `$guarded = []`)
- Strict mode enabled: unexpected columns throw exceptions in development
- XSS: Laravel's Blade templating auto-escapes output by default; `{!! !!}` unescaped syntax restricted to trusted content only
- CSRF: Laravel's CSRF token verification on all state-changing requests
- File uploads: MIME type validated against an allowlist; filename sanitized; stored with a generated UUID path, never the original filename as the storage path

---

## File Storage Security

Medical documents, prescription PDFs, and diagnostic images are stored outside the web root at:

```
/home/{hestia_user}/storage/tenants/{tenant_uuid}/
```

This path is never served directly by Nginx. Every file access passes through a Laravel controller that:

1. Verifies the user is authenticated
2. Verifies the user's tenant matches the file's tenant
3. Verifies the user has the required permission (`documents.view` or equivalent)
4. Records the access in `document_access_logs`
5. Streams the file with `response()->file()` using secure headers

File paths are never exposed in API responses or HTML. The frontend receives a signed temporary URL that routes through the controller, not a direct filesystem path.

Uploaded files are validated for:
- MIME type against allowed list (PDF, JPEG, PNG, DICOM subset)
- File size against tenant storage limit
- Filename sanitization (UUID-based storage name)

ClamAV integration is planned for Phase 12 if available on the Hestia server.

---

## Network and Transport

- HTTPS enforced on all routes; HTTP redirected to HTTPS (Nginx + Hestia SSL)
- HSTS header set with appropriate max-age
- Security headers configured in Nginx: `X-Content-Type-Options`, `X-Frame-Options`, `X-XSS-Protection`, `Referrer-Policy`, `Content-Security-Policy`
- CSP policy restricts script sources to self and Alpine.js CDN or bundled assets
- Rate limiting applied on:
  - Login endpoint: 5 attempts per minute per IP per tenant
  - Password reset: 3 requests per hour per email
  - File download: 60 per minute per user
  - API endpoints: 120 per minute per user

---

## Audit Logging

The `audit_logs` table in each tenant database records:

| Event Category | Events Logged |
|---|---|
| Authentication | Login, logout, failed login, lockout, MFA events, password change |
| Patient records | View, create, edit, delete (soft) |
| Clinical | Encounter open, consultation complete, encounter signed, amendment created |
| Prescriptions | Issued, emailed, cancelled, reprinted |
| Documents | Upload, view, download, print, delete |
| Billing | Invoice created, finalized, cancelled; payment collected, refunded |
| Cash management | Shift opened, closed, reconciled, approved; discrepancy recorded |
| User management | User created, edited, deactivated, role changed, password reset |
| Permissions | Role created, modified; permission assigned or removed |
| Settings | Centre configuration changed |
| Support access | Platform admin access granted and revoked |

Every audit log entry records: user ID, action, resource type and ID, old values (JSON), new values (JSON), IP address, user agent, and timestamp.

Audit logs are append-only. No application code deletes or modifies audit log rows. The database user for the application does not have `DELETE` or `UPDATE` privileges on `audit_logs`.

---

## Credential and Secrets Management

- Application secrets stored in `.env` (not committed to version control)
- `.env` file permissions: `600` (owner read/write only)
- Tenant database passwords: encrypted with AES-256 using `APP_KEY` before storage in `ttmed_platform.tenants.db_password`
- SMTP2GO credentials per tenant: stored encrypted in `centre_settings` using the tenant's configuration key
- Prescription signature images: stored outside web root, served only through authenticated controller
- No credentials hardcoded anywhere in the codebase
- `.gitignore` excludes `.env`, `storage/tenants/`, and all generated files

---

## Backup Security

- Database backups encrypted with AES-256 before writing to disk
- Encrypted backups copied to off-server location (see BACKUP.md)
- Backup encryption keys stored separately from backup files
- Restoration procedures documented and tested quarterly
- Backup access logged

---

## Privacy and Data Protection

The system is designed to support review against Trinidad and Tobago's Data Protection Act. Features relevant to compliance:

- **Consent records**: `patient_consents` table records what was consented to, by whom, and when
- **Purpose limitation**: access control restricts which roles see clinical vs. administrative data
- **Data minimization**: optional fields are truly optional; no unnecessary data collected
- **Subject access**: patient profiles aggregate all data held; administrators can export
- **Correction**: patient data can be corrected with audit trail
- **Secure deletion**: soft delete implemented; hard delete process with audit trail available
- **Privacy notices**: configurable per tenant, displayed at registration

Formal legal compliance review must be completed by qualified legal counsel before commercial deployment. This system provides the technical controls; legal, regulatory, and clinical professional obligations are the responsibility of the deploying medical centre and the platform owner.
