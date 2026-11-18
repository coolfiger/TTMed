<?php

use App\Models\Tenant\Permission;
use App\Models\Tenant\Role;
use Illuminate\Database\Seeder;

class TenantDefaultRolesSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedPermissions();
        $this->seedRoles();
        $this->assignPermissionsToRoles();
    }

    private function seedPermissions(): void
    {
        foreach ($this->permissions() as $perm) {
            Permission::on('tenant')->firstOrCreate(
                ['slug' => $perm['slug']],
                $perm
            );
        }
    }

    private function seedRoles(): void
    {
        foreach ($this->roles() as $role) {
            Role::on('tenant')->firstOrCreate(
                ['slug' => $role['slug']],
                array_merge($role, ['is_system' => true])
            );
        }
    }

    private function assignPermissionsToRoles(): void
    {
        foreach ($this->rolePermissions() as $roleSlug => $permSlugs) {
            $role  = Role::on('tenant')->where('slug', $roleSlug)->first();
            $perms = Permission::on('tenant')->whereIn('slug', $permSlugs)->pluck('id');

            if ($role) {
                $role->permissions()->syncWithoutDetaching($perms);
            }
        }
    }

    private function roles(): array
    {
        return [
            ['name' => 'Medical Centre Administrator', 'slug' => 'tenant.admin',         'description' => 'Full access to all tenant features'],
            ['name' => 'Medical Centre Manager',       'slug' => 'tenant.manager',       'description' => 'Operational management, staff and reports'],
            ['name' => 'Senior Medical Staff',         'slug' => 'tenant.senior_medical', 'description' => 'Senior clinical staff'],
            ['name' => 'Doctor',                       'slug' => 'tenant.doctor',         'description' => 'General practitioner'],
            ['name' => 'Specialist Doctor',            'slug' => 'tenant.specialist',     'description' => 'Specialist doctor'],
            ['name' => 'Nurse',                        'slug' => 'tenant.nurse',          'description' => 'Nursing staff'],
            ['name' => 'Junior Medical Staff',         'slug' => 'tenant.junior_medical', 'description' => 'Junior clinical staff'],
            ['name' => 'Receptionist',                 'slug' => 'tenant.receptionist',   'description' => 'Front desk and reception'],
            ['name' => 'Cashier',                      'slug' => 'tenant.cashier',        'description' => 'Payment collection only'],
            ['name' => 'Records Clerk',                'slug' => 'tenant.records_clerk',  'description' => 'Patient records management'],
            ['name' => 'Accountant',                   'slug' => 'tenant.accountant',     'description' => 'Financial records and reports'],
            ['name' => 'Read-only / Auditor',          'slug' => 'tenant.auditor',        'description' => 'Read-only access for auditing'],
        ];
    }

    private function permissions(): array
    {
        return [
            // Patients
            ['slug' => 'patients.view',            'module' => 'patients',      'name' => 'View patients'],
            ['slug' => 'patients.create',           'module' => 'patients',      'name' => 'Register patients'],
            ['slug' => 'patients.edit',             'module' => 'patients',      'name' => 'Edit patient details'],
            ['slug' => 'patients.delete',           'module' => 'patients',      'name' => 'Delete patients'],
            ['slug' => 'patients.search',           'module' => 'patients',      'name' => 'Search patients'],
            ['slug' => 'patients.medical_history',  'module' => 'patients',      'name' => 'View/edit medical history'],
            // Queue
            ['slug' => 'queue.view',                'module' => 'queue',         'name' => 'View queue'],
            ['slug' => 'queue.manage',              'module' => 'queue',         'name' => 'Manage queue entries'],
            ['slug' => 'queue.checkin',             'module' => 'queue',         'name' => 'Check in patients'],
            // Nursing
            ['slug' => 'nursing.view',              'module' => 'nursing',       'name' => 'View nursing records'],
            ['slug' => 'nursing.assess',            'module' => 'nursing',       'name' => 'Perform nursing assessments'],
            ['slug' => 'nursing.vitals',            'module' => 'nursing',       'name' => 'Record vital signs'],
            ['slug' => 'nursing.procedures',        'module' => 'nursing',       'name' => 'Record procedures'],
            // Encounters / Consultations
            ['slug' => 'encounters.view',           'module' => 'encounters',    'name' => 'View consultations'],
            ['slug' => 'encounters.create',         'module' => 'encounters',    'name' => 'Create encounters'],
            ['slug' => 'encounters.edit',           'module' => 'encounters',    'name' => 'Edit encounters'],
            ['slug' => 'encounters.sign',           'module' => 'encounters',    'name' => 'Sign encounters'],
            ['slug' => 'encounters.amend',          'module' => 'encounters',    'name' => 'Amend signed encounters'],
            // Diagnoses
            ['slug' => 'diagnoses.create',          'module' => 'encounters',    'name' => 'Add diagnoses'],
            // Prescriptions
            ['slug' => 'prescriptions.view',        'module' => 'prescriptions', 'name' => 'View prescriptions'],
            ['slug' => 'prescriptions.issue',       'module' => 'prescriptions', 'name' => 'Issue prescriptions'],
            ['slug' => 'prescriptions.email',       'module' => 'prescriptions', 'name' => 'Email prescriptions'],
            ['slug' => 'prescriptions.cancel',      'module' => 'prescriptions', 'name' => 'Cancel prescriptions'],
            // Documents
            ['slug' => 'documents.view',            'module' => 'documents',     'name' => 'View documents'],
            ['slug' => 'documents.upload',          'module' => 'documents',     'name' => 'Upload documents'],
            ['slug' => 'documents.download',        'module' => 'documents',     'name' => 'Download documents'],
            ['slug' => 'documents.delete',          'module' => 'documents',     'name' => 'Delete documents'],
            // Referrals / Certificates
            ['slug' => 'referrals.create',          'module' => 'clinical',      'name' => 'Create referrals'],
            ['slug' => 'certificates.create',       'module' => 'clinical',      'name' => 'Create medical certificates'],
            // Lab
            ['slug' => 'lab.orders.create',         'module' => 'laboratory',    'name' => 'Create lab orders'],
            ['slug' => 'lab.results.enter',         'module' => 'laboratory',    'name' => 'Enter lab results'],
            ['slug' => 'lab.results.acknowledge',   'module' => 'laboratory',    'name' => 'Acknowledge lab results'],
            // Appointments
            ['slug' => 'appointments.view',         'module' => 'appointments',  'name' => 'View appointments'],
            ['slug' => 'appointments.create',       'module' => 'appointments',  'name' => 'Book appointments'],
            ['slug' => 'appointments.edit',         'module' => 'appointments',  'name' => 'Edit appointments'],
            ['slug' => 'appointments.cancel',       'module' => 'appointments',  'name' => 'Cancel appointments'],
            ['slug' => 'appointments.manage',       'module' => 'appointments',  'name' => 'Manage doctor schedules'],
            // Billing
            ['slug' => 'billing.view',              'module' => 'billing',       'name' => 'View invoices'],
            ['slug' => 'billing.create',            'module' => 'billing',       'name' => 'Create invoices'],
            ['slug' => 'billing.finalize',          'module' => 'billing',       'name' => 'Finalize invoices'],
            ['slug' => 'billing.cancel',            'module' => 'billing',       'name' => 'Cancel invoices'],
            // Payments
            ['slug' => 'payments.collect',          'module' => 'payments',      'name' => 'Collect payments'],
            ['slug' => 'payments.refund',           'module' => 'payments',      'name' => 'Process refunds'],
            ['slug' => 'payments.view',             'module' => 'payments',      'name' => 'View payments'],
            // Cash management
            ['slug' => 'shifts.open',               'module' => 'cash',          'name' => 'Open cashier shift'],
            ['slug' => 'shifts.close',              'module' => 'cash',          'name' => 'Close cashier shift'],
            ['slug' => 'shifts.approve',            'module' => 'cash',          'name' => 'Approve shift reconciliation'],
            ['slug' => 'shifts.view',               'module' => 'cash',          'name' => 'View shifts'],
            // Inventory
            ['slug' => 'inventory.view',            'module' => 'inventory',     'name' => 'View inventory'],
            ['slug' => 'inventory.manage',          'module' => 'inventory',     'name' => 'Manage inventory'],
            // Reports
            ['slug' => 'reports.operational',       'module' => 'reports',       'name' => 'View operational reports'],
            ['slug' => 'reports.financial',         'module' => 'reports',       'name' => 'View financial reports'],
            ['slug' => 'reports.clinical',          'module' => 'reports',       'name' => 'View clinical reports'],
            ['slug' => 'reports.export',            'module' => 'reports',       'name' => 'Export reports'],
            // Staff
            ['slug' => 'staff.view',                'module' => 'staff',         'name' => 'View staff'],
            ['slug' => 'staff.manage',              'module' => 'staff',         'name' => 'Manage staff'],
            // Services
            ['slug' => 'services.view',             'module' => 'services',      'name' => 'View service catalogue'],
            ['slug' => 'services.manage',           'module' => 'services',      'name' => 'Manage service catalogue'],
            // Insurance
            ['slug' => 'insurance.view',            'module' => 'insurance',     'name' => 'View insurance providers'],
            ['slug' => 'insurance.manage',          'module' => 'insurance',     'name' => 'Manage insurance providers'],
            // Branches
            ['slug' => 'branches.view',             'module' => 'branches',      'name' => 'View branches'],
            ['slug' => 'branches.manage',           'module' => 'branches',      'name' => 'Manage branches'],
            // Settings
            ['slug' => 'settings.view',             'module' => 'settings',      'name' => 'View settings'],
            ['slug' => 'settings.manage',           'module' => 'settings',      'name' => 'Manage settings'],
            // User management
            ['slug' => 'users.view',                'module' => 'users',         'name' => 'View users'],
            ['slug' => 'users.manage',              'module' => 'users',         'name' => 'Manage users'],
            ['slug' => 'roles.manage',              'module' => 'users',         'name' => 'Manage roles and permissions'],
        ];
    }

    private function rolePermissions(): array
    {
        $all = collect($this->permissions())->pluck('slug')->toArray();

        $doctorPerms = [
            'patients.view', 'patients.search', 'patients.medical_history',
            'queue.view',
            'nursing.view',
            'encounters.view', 'encounters.create', 'encounters.edit', 'encounters.sign', 'encounters.amend',
            'diagnoses.create',
            'prescriptions.view', 'prescriptions.issue', 'prescriptions.email', 'prescriptions.cancel',
            'documents.view', 'documents.upload', 'documents.download',
            'referrals.create', 'certificates.create',
            'lab.orders.create', 'lab.results.acknowledge',
            'appointments.view', 'appointments.create', 'appointments.edit', 'appointments.cancel', 'appointments.manage',
            'billing.view', 'reports.clinical',
        ];

        $nursePerms = [
            'patients.view', 'patients.search', 'patients.medical_history',
            'queue.view',
            'nursing.view', 'nursing.assess', 'nursing.vitals', 'nursing.procedures',
            'encounters.view',
            'documents.view', 'documents.upload',
            'appointments.view',
        ];

        $receptionistPerms = [
            'patients.view', 'patients.create', 'patients.edit', 'patients.search',
            'queue.view', 'queue.manage', 'queue.checkin',
            'appointments.view', 'appointments.create', 'appointments.edit', 'appointments.cancel', 'appointments.manage',
            'billing.view', 'billing.create',
            'payments.collect', 'payments.view',
            'shifts.open', 'shifts.close', 'shifts.view',
            'documents.view',
        ];

        $accountantPerms = [
            'billing.view', 'billing.create', 'billing.finalize', 'billing.cancel',
            'payments.view', 'payments.refund',
            'shifts.view', 'shifts.approve',
            'reports.financial', 'reports.export',
        ];

        $recordsClerkPerms = [
            'patients.view', 'patients.create', 'patients.edit', 'patients.search',
            'documents.view', 'documents.upload', 'documents.download',
            'appointments.view',
        ];

        $managerPerms = array_merge($receptionistPerms, $accountantPerms, [
            'staff.view', 'staff.manage',
            'services.view', 'services.manage',
            'insurance.view', 'insurance.manage',
            'branches.view', 'branches.manage',
            'settings.view', 'settings.manage',
            'reports.operational', 'reports.clinical', 'reports.financial', 'reports.export',
            'users.view', 'users.manage',
            'inventory.view', 'inventory.manage',
            'shifts.approve',
        ]);

        return [
            'tenant.admin'          => $all,
            'tenant.manager'        => $managerPerms,
            'tenant.senior_medical' => array_merge($doctorPerms, $nursePerms),
            'tenant.doctor'         => $doctorPerms,
            'tenant.specialist'     => $doctorPerms,
            'tenant.nurse'          => $nursePerms,
            'tenant.junior_medical' => array_diff($doctorPerms, ['encounters.sign', 'prescriptions.issue']),
            'tenant.receptionist'   => $receptionistPerms,
            'tenant.cashier'        => ['payments.collect', 'payments.view', 'shifts.open', 'shifts.close', 'shifts.view', 'billing.view'],
            'tenant.records_clerk'  => $recordsClerkPerms,
            'tenant.accountant'     => $accountantPerms,
            'tenant.auditor'        => ['patients.view', 'billing.view', 'payments.view', 'reports.financial', 'reports.operational'],
        ];
    }
}
