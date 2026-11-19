<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DemoDataSeeder extends Seeder
{
    private string $db = 'tenant';
    private array $branchIds = [];
    private array $userIds   = [];
    private array $doctorIds = [];
    private array $patientIds = [];
    private array $serviceIds = [];
    private array $insuranceIds = [];
    private array $encounterIds = [];

    public function run(): void
    {
        $this->command->info('Seeding demo data for Shiva Medical Centre…');

        $this->truncate();
        $this->seedBranches();
        $this->seedInsuranceProviders();
        $this->seedServiceCatalogue();
        $this->seedUsers();
        $this->seedPatients();
        $this->seedInventory();
        $this->seedCashierShift();
        $this->seedEncounters();
        $this->seedTodaysQueue();
        $this->seedWeekSchedule();
        $this->seedFollowUps();

        $this->command->info('Done.');
    }

    private function truncate(): void
    {
        DB::connection($this->db)->getSchemaBuilder()->disableForeignKeyConstraints();
        foreach ([
            'appointments','follow_ups','imaging_reports','imaging_studies','imaging_orders',
            'lab_results','lab_result_attachments','lab_order_items','lab_orders',
            'payments','invoice_items','invoices','cashier_shifts',
            'prescription_items','prescriptions',
            'diagnoses','consultation_notes','nursing_assessments','nursing_procedures',
            'vital_signs','queue_entries','queue_events','queue_number_sequences',
            'encounters','patient_medical_histories','patient_medications',
            'patient_conditions','patient_allergies','patients',
            'inventory_transactions','inventory_items','inventory_categories',
            'doctor_availability','doctor_schedules','doctor_operating_hours',
            'doctors','staff_profiles','user_roles','users',
            'services','service_categories','insurance_providers',
            'branch_operating_hours','branches',
            'invoice_number_sequences','payment_reference_sequences',
            'prescription_number_sequences','appointment_number_sequences',
            'lab_order_number_sequences','imaging_order_number_sequences',
        ] as $table) {
            DB::connection($this->db)->table($table)->truncate();
        }
        DB::connection($this->db)->getSchemaBuilder()->enableForeignKeyConstraints();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Branches
    // ─────────────────────────────────────────────────────────────────────────
    private function seedBranches(): void
    {
        $branches = [
            [
                'key'      => 'MAIN',
                'name'     => 'Shiva Main — Port of Spain',
                'phone'    => '868-622-1234',
                'email'    => 'main@shivamed.tt',
                'address'  => '14 Frederick Street, Port of Spain',
                'city'     => 'Port of Spain',
                'is_active'=> true,
                'is_main'  => true,
            ],
            [
                'key'      => 'NORTH',
                'name'     => 'Shiva North — Maraval',
                'phone'    => '868-628-5678',
                'email'    => 'north@shivamed.tt',
                'address'  => '8 Saddle Road, Maraval',
                'city'     => 'Maraval',
                'is_active'=> true,
                'is_main'  => false,
            ],
        ];

        foreach ($branches as $b) {
            $key = $b['key'];
            unset($b['key']);
            $id = DB::connection($this->db)->table('branches')->insertGetId(
                array_merge($b, ['created_at' => now(), 'updated_at' => now()])
            );
            $this->branchIds[$key] = $id;
        }

        // Branch operating hours Mon–Sat 08:00–17:00
        foreach ($this->branchIds as $branchId) {
            foreach (range(1, 6) as $day) {
                DB::connection($this->db)->table('branch_operating_hours')->insert([
                    'branch_id'   => $branchId,
                    'day_of_week' => $day,
                    'open_time'   => '08:00:00',
                    'close_time'  => '17:00:00',
                    'is_closed'   => false,
                ]);
            }
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Insurance
    // ─────────────────────────────────────────────────────────────────────────
    private function seedInsuranceProviders(): void
    {
        $providers = [
            ['name' => 'Sagicor Life', 'code' => 'SAG', 'phone' => '868-625-3200', 'is_active' => true],
            ['name' => 'Guardian Life', 'code' => 'GDN', 'phone' => '868-623-5433', 'is_active' => true],
            ['name' => 'Trinre Insurance', 'code' => 'TRI', 'phone' => '868-622-0000', 'is_active' => true],
            ['name' => 'NLCB Health Plan', 'code' => 'NLC', 'phone' => '868-625-6262', 'is_active' => true],
        ];

        foreach ($providers as $p) {
            $id = DB::connection($this->db)->table('insurance_providers')->insertGetId(
                array_merge($p, ['created_at' => now(), 'updated_at' => now()])
            );
            $this->insuranceIds[$p['code']] = $id;
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Service catalogue
    // ─────────────────────────────────────────────────────────────────────────
    private function seedServiceCatalogue(): void
    {
        $catId = fn(string $code) => DB::connection($this->db)
            ->table('service_categories')->where('code', $code)->value('id');

        $services = [
            // Consultations
            ['code' => 'CONS-GP',    'name' => 'General Consultation',         'price' => 30000,  'category' => 'consultation'],
            ['code' => 'CONS-SPEC',  'name' => 'Specialist Consultation',      'price' => 60000,  'category' => 'consultation'],
            ['code' => 'CONS-URGENT','name' => 'Urgent / After-hours Visit',   'price' => 45000,  'category' => 'consultation'],
            ['code' => 'CONS-CHILD', 'name' => 'Paediatric Consultation',      'price' => 30000,  'category' => 'consultation'],
            // Procedures
            ['code' => 'PROC-DRESS', 'name' => 'Wound Dressing',               'price' => 10000,  'category' => 'procedure'],
            ['code' => 'PROC-INJ',   'name' => 'Intramuscular Injection',       'price' => 8000,   'category' => 'procedure'],
            ['code' => 'PROC-SUTURE','name' => 'Suture / Laceration Repair',   'price' => 25000,  'category' => 'procedure'],
            ['code' => 'PROC-ECG',   'name' => 'Electrocardiogram (ECG)',       'price' => 20000,  'category' => 'procedure'],
            ['code' => 'PROC-PICO',  'name' => 'Nebulisation',                  'price' => 15000,  'category' => 'procedure'],
            // Lab
            ['code' => 'LAB-FBC',    'name' => 'Full Blood Count',              'price' => 18000,  'category' => 'laboratory'],
            ['code' => 'LAB-BMP',    'name' => 'Basic Metabolic Panel',         'price' => 22000,  'category' => 'laboratory'],
            ['code' => 'LAB-LFT',    'name' => 'Liver Function Tests',          'price' => 25000,  'category' => 'laboratory'],
            ['code' => 'LAB-LIPID',  'name' => 'Fasting Lipid Profile',         'price' => 20000,  'category' => 'laboratory'],
            ['code' => 'LAB-HBA1C',  'name' => 'Glycated Haemoglobin (HbA1c)', 'price' => 18000,  'category' => 'laboratory'],
            ['code' => 'LAB-TSH',    'name' => 'Thyroid Stimulating Hormone',  'price' => 20000,  'category' => 'laboratory'],
            ['code' => 'LAB-UA',     'name' => 'Urinalysis with M&S',           'price' => 12000,  'category' => 'laboratory'],
            ['code' => 'LAB-PREG',   'name' => 'Urine Pregnancy Test',          'price' => 8000,   'category' => 'laboratory'],
            ['code' => 'LAB-CRP',    'name' => 'C-Reactive Protein',            'price' => 15000,  'category' => 'laboratory'],
            ['code' => 'LAB-PSA',    'name' => 'PSA (Prostate)',                'price' => 18000,  'category' => 'laboratory'],
            // Imaging
            ['code' => 'IMG-CXR',    'name' => 'Chest X-Ray (PA)',              'price' => 35000,  'category' => 'imaging'],
            ['code' => 'IMG-LSPX',   'name' => 'Lumbar Spine X-Ray',            'price' => 38000,  'category' => 'imaging'],
            ['code' => 'IMG-ABDX',   'name' => 'Abdominal X-Ray',               'price' => 35000,  'category' => 'imaging'],
            ['code' => 'IMG-USS-ABD','name' => 'Abdominal Ultrasound',          'price' => 55000,  'category' => 'imaging'],
            ['code' => 'IMG-USS-KUB','name' => 'KUB Ultrasound',                'price' => 55000,  'category' => 'imaging'],
            // Other
            ['code' => 'CERT-SICK',  'name' => 'Sick Leave Certificate',        'price' => 5000,   'category' => 'other_service'],
            ['code' => 'CERT-MED',   'name' => 'Medical Certificate',            'price' => 8000,   'category' => 'other_service'],
        ];

        foreach ($services as $s) {
            $catIdVal = $catId($s['category']);
            $id = DB::connection($this->db)->table('services')->insertGetId([
                'category_id' => $catIdVal,
                'code'        => $s['code'],
                'name'        => $s['name'],
                'price'       => $s['price'],
                'cost'        => 0,
                'tax_rate'    => 0,
                'taxable'     => false,
                'is_active'   => true,
                'is_default'  => false,
                'sort_order'  => 0,
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);
            $this->serviceIds[$s['code']] = $id;
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Users / Staff
    // ─────────────────────────────────────────────────────────────────────────
    private function seedUsers(): void
    {
        $mainBranch  = $this->branchIds['MAIN'];
        $northBranch = $this->branchIds['NORTH'];

        $staff = [
            // [name, username, email, role_slug, title, first, last, specialty]
            ['Dr. Ramesh Sharma',    'rsharma',   'rsharma@shivamed.tt',   'tenant.admin',          'Dr.',  'Ramesh',   'Sharma',   'General Practice',    true,  $mainBranch],
            ['Dr. Priya Ramlogan',   'pramlogan', 'pramlogan@shivamed.tt', 'tenant.doctor',         'Dr.',  'Priya',    'Ramlogan', 'Internal Medicine',   true,  $mainBranch],
            ['Dr. Kevin Ali',        'kali',      'kali@shivamed.tt',      'tenant.specialist',     'Dr.',  'Kevin',    'Ali',      'Cardiology',          true,  $northBranch],
            ['Dr. Sandra Baptiste',  'sbaptiste', 'sbaptiste@shivamed.tt', 'tenant.doctor',         'Dr.',  'Sandra',   'Baptiste', 'General Practice',    true,  $northBranch],
            ['Nurse Kamini Singh',   'ksingh',    'ksingh@shivamed.tt',    'tenant.nurse',          'Ms.',  'Kamini',   'Singh',    null,                  false, $mainBranch],
            ['Nurse Marcus Thomas',  'mthomas',   'mthomas@shivamed.tt',   'tenant.nurse',          'Mr.',  'Marcus',   'Thomas',   null,                  false, $northBranch],
            ['Aisha Mohammed',       'amohammed', 'amohammed@shivamed.tt', 'tenant.receptionist',   'Ms.',  'Aisha',    'Mohammed', null,                  false, $mainBranch],
            ['Carlos Hernandez',     'chernandez','chernandez@shivamed.tt','tenant.receptionist',   'Mr.',  'Carlos',   'Hernandez',null,                  false, $northBranch],
            ['Tricia Gomez',         'tgomez',    'tgomez@shivamed.tt',    'tenant.cashier',        'Ms.',  'Tricia',   'Gomez',    null,                  false, $mainBranch],
            ['Derek Charles',        'dcharles',  'dcharles@shivamed.tt',  'tenant.accountant',     'Mr.',  'Derek',    'Charles',  null,                  false, $mainBranch],
        ];

        foreach ($staff as [$name, $username, $email, $roleSlug, $title, $first, $last, $specialty, $isDoctor, $branchId]) {
            $userId = DB::connection($this->db)->table('users')->insertGetId([
                'uuid'                => Str::uuid(),
                'branch_id'           => $branchId,
                'username'            => $username,
                'email'               => $email,
                'name'                => $name,
                'password'            => Hash::make('Demo1234!'),
                'is_active'           => true,
                'must_change_password'=> false,
                'created_at'          => now(),
                'updated_at'          => now(),
            ]);

            $roleId = DB::connection($this->db)->table('roles')->where('slug', $roleSlug)->value('id');
            DB::connection($this->db)->table('user_roles')->insert([
                'user_id' => $userId,
                'role_id' => $roleId,
            ]);

            $staffId = DB::connection($this->db)->table('staff_profiles')->insertGetId([
                'user_id'    => $userId,
                'branch_id'  => $branchId,
                'title'      => $title,
                'first_name' => $first,
                'last_name'  => $last,
                'gender'     => in_array($title, ['Ms.', 'Mrs.']) ? 'female' : 'male',
                'is_active'  => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->userIds[$username] = $userId;

            if ($isDoctor) {
                $doctorId = DB::connection($this->db)->table('doctors')->insertGetId([
                    'staff_profile_id'          => $staffId,
                    'registration_number'       => 'TT-' . strtoupper(Str::random(6)),
                    'specialty'                 => $specialty,
                    'available_for_appointments'=> true,
                    'created_at'                => now(),
                    'updated_at'                => now(),
                ]);
                $this->doctorIds[$username] = $doctorId;

                // Mon–Fri 08:00–16:00 schedule
                foreach (range(1, 5) as $day) {
                    DB::connection($this->db)->table('doctor_operating_hours')->insert([
                        'doctor_id'   => $doctorId,
                        'day_of_week' => $day,
                        'start_time'  => '08:00:00',
                        'end_time'    => '16:00:00',
                        'is_active'   => true,
                    ]);
                }

                // Doctor schedules (slot-based)
                DB::connection($this->db)->table('doctor_schedules')->insert([
                    'doctor_id'              => $doctorId,
                    'branch_id'              => $branchId,
                    'day_of_week'            => 1,
                    'is_working_day'         => true,
                    'start_time'             => '08:00:00',
                    'end_time'               => '16:00:00',
                    'slot_duration_minutes'  => 15,
                    'max_patients_per_slot'  => 1,
                    'created_at'             => now(),
                    'updated_at'             => now(),
                ]);

                // Doctor availability record
                DB::connection($this->db)->table('doctor_availability')->insert([
                    'doctor_id'   => $doctorId,
                    'branch_id'   => $branchId,
                    'status'      => 'available',
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ]);
            }
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Patients (20 realistic TT patients)
    // ─────────────────────────────────────────────────────────────────────────
    private function seedPatients(): void
    {
        $patients = [
            // first, last, gender, dob, blood, national_id, phone, email, insurance_code, address, occupation, conditions, allergies, meds
            ['Ravi',    'Moonilal',   'M', '1968-03-14', 'O+', '19680314-001', '868-767-1001', 'ravi.moonilal@gmail.com',   'SAG', '5 Acacia Drive, Chaguanas',    'Teacher',           ['Hypertension','Type 2 Diabetes Mellitus'],          ['Penicillin'],                     ['Metformin 500mg','Amlodipine 5mg']],
            ['Sita',    'Rampersad',  'F', '1975-07-22', 'A+', '19750722-002', '868-767-1002', 'sita.rampersad@yahoo.com',  'GDN', '12 Poinsettia Ave, Chaguanas', 'Nurse',             ['Hypothyroidism','Hyperlipidaemia'],                  ['Sulfa drugs'],                    ['Levothyroxine 100mcg','Atorvastatin 20mg']],
            ['Marcus',  'Williams',   'M', '1990-11-05', 'B+', '19901105-003', '868-726-2003', 'marcus.w@hotmail.com',      null,  '8 Main Road, San Fernando',    'Mechanic',          ['Asthma'],                                            ['Aspirin','NSAIDs'],               ['Salbutamol inhaler']],
            ['Keisha',  'Joseph',     'F', '1985-04-18', 'AB-','19850418-004', '868-726-2004', 'keisha.j@gmail.com',        'SAG', '3 Lady Young Rd, POS',         'Accountant',        ['Iron Deficiency Anaemia'],                           [],                                 ['Ferrous sulphate 200mg']],
            ['Dev',     'Seecharan',  'M', '1955-09-01', 'O-', '19550901-005', '868-767-3005', 'dev.s@gmail.com',           'TRI', '21 Eastern Main Rd, Arima',    'Retired',           ['Hypertension','Ischaemic Heart Disease','Gout'],     ['Codeine'],                        ['Lisinopril 10mg','Allopurinol 300mg','Aspirin 75mg']],
            ['Alicia',  'Ferguson',   'F', '1992-02-28', 'A-', '19920228-006', '868-726-3006', 'alicia.f@gmail.com',        null,  '7 Coconut Drive, Couva',       'HR Manager',        [],                                                    [],                                 []],
            ['Rishi',   'Persad',     'M', '2001-06-10', 'B-', '20010610-007', '868-487-4007', 'rishi.p@gmail.com',         'GDN', '45 Freeport Road, Freeport',   'Student',           ['Allergic Rhinitis'],                                 ['Dust mite','Pollen'],             ['Cetirizine 10mg','Fluticasone nasal spray']],
            ['Pauline', 'Alexis',     'F', '1948-12-03', 'O+', '19481203-008', '868-726-4008', 'pauline.a@outlook.com',     'NLC', '2 Clifton Hill, Maraval',      'Retired Teacher',   ['Type 2 Diabetes Mellitus','Hypertension','CKD Stage 3'],['Morphine'],               ['Metformin 1000mg','Ramipril 5mg','Furosemide 40mg']],
            ['Joel',    'Springer',   'M', '1978-08-19', 'A+', '19780819-009', '868-767-5009', 'joel.s@gmail.com',          null,  '15 Mucurapo Rd, Westmoorings', 'Engineer',          ['Dyslipidaemia'],                                     [],                                 ['Rosuvastatin 10mg']],
            ['Nadine',  'Chin',       'F', '1983-05-25', 'AB+','19830525-010', '868-726-5010', 'nadine.chin@gmail.com',     'SAG', '33 Saddle Road, Maraval',      'Physician',         ['Migraine'],                                          ['Erythromycin'],                   ['Topiramate 50mg']],
            ['Curtis',  'Phillip',    'M', '1965-01-30', 'O+', '19650130-011', '868-767-6011', 'curtis.p@yahoo.com',        'TRI', '6 Coffee Street, San Fernando','Police Officer',    ['Hypertension','BPH'],                                ['Penicillin'],                     ['Amlodipine 10mg','Tamsulosin 0.4mg']],
            ['Linda',   'Ramoutar',   'F', '1970-10-11', 'B+', '19701011-012', '868-726-6012', 'linda.r@gmail.com',         'GDN', '9 High Street, Princes Town',  'Social Worker',     ['Rheumatoid Arthritis'],                              ['Ibuprofen'],                      ['Methotrexate 15mg','Folic acid 5mg']],
            ['Aaron',   'Noriega',    'M', '1998-03-07', 'O+', '19980307-013', '868-487-7013', 'aaron.n@gmail.com',         null,  '28 Cipero Street, San Fernando','Barber',           [],                                                    [],                                 []],
            ['Tricia',  'Mohammed',   'F', '1988-09-14', 'A+', '19880914-014', '868-726-7014', 'tricia.m@gmail.com',        'SAG', '11 Tranquillity Street, POS',  'Lawyer',            ['Anxiety Disorder','GERD'],                           ['Latex'],                          ['Escitalopram 10mg','Omeprazole 20mg']],
            ['Stephen', 'Lalla',      'M', '1950-06-20', 'B+', '19500620-015', '868-767-8015', 'stephen.l@outlook.com',     'TRI', '4 Circular Road, San Fernando','Retired Engineer',  ['COPD','Type 2 Diabetes Mellitus','Hypertension'],    ['Aspirin','Sulfa drugs'],          ['Tiotropium inhaler','Metformin 500mg','Enalapril 10mg']],
            ['Cheryl',  'Ramkhelawan','F', '1995-11-02', 'O+', '19951102-016', '868-726-8016', 'cheryl.rk@gmail.com',       null,  '7 Lady Chancellor Rd, POS',    'Graphic Designer',  [],                                                    [],                                 []],
            ['Farouk',  'Ali',        'M', '1972-04-16', 'A+', '19720416-017', '868-767-9017', 'farouk.ali@gmail.com',      'GDN', '17 Eastern Main Rd, Tunapuna', 'Bus Driver',        ['Hypertension','Sleep Apnoea'],                       [],                                 ['Hydrochlorothiazide 25mg','CPAP therapy']],
            ['Grace',   'Scott',      'F', '1945-07-08', 'AB+','19450708-018', '868-726-9018', 'grace.s@gmail.com',         'NLC', '1 Richmond Street, POS',       'Retired',           ['Osteoporosis','Hypertension','Atrial Fibrillation'], ['Penicillin','Sulfa drugs'],       ['Alendronate 70mg weekly','Warfarin 5mg','Bisoprolol 5mg']],
            ['Jason',   'Browne',     'M', '1987-12-22', 'O+', '19871222-019', '868-487-0019', 'jason.b@gmail.com',         null,  '22 Cipriani Blvd, POS',        'Sales Rep',         [],                                                    [],                                 []],
            ['Maria',   'Garcia',     'F', '1960-02-14', 'A-', '19600214-020', '868-726-0020', 'maria.g@gmail.com',         'SAG', '5 Cascadia Ave, St Anns',      'Nurse Manager',     ['Type 2 Diabetes Mellitus','Neuropathy'],              ['Codeine','Morphine'],             ['Insulin glargine','Pregabalin 75mg']],
        ];

        $adminUserId    = $this->userIds['rsharma'];
        $mainBranch     = $this->branchIds['MAIN'];
        $northBranch    = $this->branchIds['NORTH'];
        $drSharmId      = $this->doctorIds['rsharma'];

        foreach ($patients as $i => [$first, $last, $gender, $dob, $blood, $nid, $phone, $email, $insCode, $address, $occupation, $conditions, $allergies, $meds]) {
            $num = 'SHV-' . str_pad($i + 1, 5, '0', STR_PAD_LEFT);
            $branch = ($i % 2 === 0) ? $mainBranch : $northBranch;
            $insId  = $insCode ? ($this->insuranceIds[$insCode] ?? null) : null;

            $patientId = DB::connection($this->db)->table('patients')->insertGetId([
                'patient_number'          => $num,
                'first_name'              => $first,
                'last_name'               => $last,
                'gender'                  => $gender,
                'date_of_birth'           => $dob,
                'blood_type'              => $blood,
                'national_id'             => $nid,
                'phone_primary'           => $phone,
                'email'                   => $email,
                'address'                 => $address,
                'city'                    => explode(',', $address)[1] ?? 'Port of Spain',
                'country'                 => 'Trinidad and Tobago',
                'occupation'              => $occupation,
                'insurance_provider_id'   => $insId,
                'insurance_policy_number' => $insId ? 'POL-' . str_pad($i + 100, 6, '0', STR_PAD_LEFT) : null,
                'registered_by'           => $adminUserId,
                'primary_doctor_id'       => $drSharmId,
                'branch_id'               => $branch,
                'is_active'               => true,
                'is_deceased'             => false,
                'created_at'              => now()->subDays(rand(30, 365)),
                'updated_at'              => now(),
            ]);

            $this->patientIds[] = $patientId;

            // Allergies
            foreach ($allergies as $allergen) {
                DB::connection($this->db)->table('patient_allergies')->insert([
                    'patient_id'  => $patientId,
                    'allergen'    => $allergen,
                    'reaction'    => 'Rash / Anaphylaxis',
                    'severity'    => 'moderate',
                    'is_active'   => true,
                    'recorded_by' => $adminUserId,
                    'created_at'  => now()->subDays(rand(10, 300)),
                    'updated_at'  => now(),
                ]);
            }

            // Conditions
            foreach ($conditions as $cond) {
                DB::connection($this->db)->table('patient_conditions')->insert([
                    'patient_id'    => $patientId,
                    'condition_name'=> $cond,
                    'status'        => 'chronic',
                    'recorded_by'   => $adminUserId,
                    'created_at'    => now()->subDays(rand(30, 500)),
                    'updated_at'    => now(),
                ]);
            }

            // Current medications
            foreach ($meds as $med) {
                [$medName, $dose] = explode(' ', $med, 2) + ['', ''];
                DB::connection($this->db)->table('patient_medications')->insert([
                    'patient_id'   => $patientId,
                    'medication_name' => $medName . ($dose ? ' ' . $dose : ''),
                    'dosage'       => $dose,
                    'frequency'    => 'Once daily',
                    'is_current'   => true,
                    'recorded_by'  => $adminUserId,
                    'created_at'   => now()->subDays(rand(10, 180)),
                    'updated_at'   => now(),
                ]);
            }

            // Medical history
            DB::connection($this->db)->table('patient_medical_histories')->insert([
                'patient_id'           => $patientId,
                'surgical_history'     => $i % 3 === 0 ? 'Appendicectomy ' . (2010 + ($i % 10)) : null,
                'family_history'       => count($conditions) > 0 ? 'Father: Hypertension. Mother: Diabetes.' : null,
                'social_history'       => 'Non-smoker. Occasional alcohol.',
                'immunisation_history' => 'Up to date per national schedule.',
                'updated_by'           => $adminUserId,
                'created_at'           => now()->subDays(rand(10, 300)),
                'updated_at'           => now(),
            ]);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Inventory
    // ─────────────────────────────────────────────────────────────────────────
    private function seedInventory(): void
    {
        $catId = DB::connection($this->db)->table('inventory_categories')->insertGetId([
            'name' => 'Consumables', 'code' => 'CONS', 'is_active' => true, 'sort_order' => 1,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $catMedId = DB::connection($this->db)->table('inventory_categories')->insertGetId([
            'name' => 'Medications', 'code' => 'MEDS', 'is_active' => true, 'sort_order' => 2,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $items = [
            ['Disposable Gloves (Box 100)', 'GLOVE-100', 'box',   350, 20,  50,  200, $catId,   false],
            ['3-ply Surgical Masks (Box 50)','MASK-50',  'box',   120, 10,  20,  100, $catId,   false],
            ['10ml Syringe',                 'SYR-10',   'unit',  800, 50,  100, 500, $catId,   false],
            ['Gauze Pads 4x4 (Pkt 10)',      'GAUZE-4X4','packet',500, 20,  40,  200, $catId,   false],
            ['IV Cannula 18G',               'CANN-18G', 'unit',  300, 30,  50,  200, $catId,   false],
            ['Metformin 500mg (Tabs 100)',    'MET-500',  'bottle',200, 15,  30,  100, $catMedId, true],
            ['Amlodipine 5mg (Tabs 28)',      'AML-5',    'pack',  180, 10,  20,   80, $catMedId, true],
            ['Salbutamol Inhaler 100mcg',     'SAL-INH',  'unit',  100, 5,   10,   50, $catMedId, true],
            ['Paracetamol 500mg (Tabs 100)',  'PARA-500', 'bottle',400, 20,  40,  200, $catMedId, false],
            ['Amoxicillin 500mg (Caps 21)',   'AMOX-500', 'pack',  150, 8,   15,   60, $catMedId, true],
        ];

        $adminUser = $this->userIds['rsharma'];
        $branch    = $this->branchIds['MAIN'];

        foreach ($items as [$name, $code, $unit, $qty, $reorder, $reorderQty, $cost, $catItemId, $trackExpiry]) {
            $itemId = DB::connection($this->db)->table('inventory_items')->insertGetId([
                'category_id'       => $catItemId,
                'name'              => $name,
                'code'              => $code,
                'unit'              => $unit,
                'quantity_on_hand'  => $qty,
                'reorder_level'     => $reorder,
                'reorder_quantity'  => $reorderQty,
                'unit_cost'         => $cost * 100,
                'is_active'         => true,
                'track_expiry'      => $trackExpiry,
                'expiry_date'       => $trackExpiry ? now()->addMonths(rand(6, 24))->format('Y-m-d') : null,
                'supplier'          => 'TT Pharma Supplies Ltd.',
                'created_at'        => now(),
                'updated_at'        => now(),
            ]);

            DB::connection($this->db)->table('inventory_transactions')->insert([
                'inventory_item_id' => $itemId,
                'branch_id'         => $branch,
                'transaction_type'  => 'receive',
                'quantity'          => $qty,
                'quantity_before'   => 0,
                'quantity_after'    => $qty,
                'reference'         => 'Opening stock',
                'notes'             => 'Initial inventory load',
                'performed_by'      => $adminUser,
                'created_at'        => now()->subDays(30),
                'updated_at'        => now()->subDays(30),
            ]);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Cashier shift
    // ─────────────────────────────────────────────────────────────────────────
    private function seedCashierShift(): void
    {
        DB::connection($this->db)->table('cashier_shifts')->insert([
            'branch_id'       => $this->branchIds['MAIN'],
            'cashier_id'      => $this->userIds['tgomez'],
            'status'          => 'open',
            'opened_at'       => now()->startOfDay()->addHours(8),
            'opening_float'   => 50000,
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Encounters — full clinical workflows
    // ─────────────────────────────────────────────────────────────────────────
    private function seedEncounters(): void
    {
        $receptionUser = $this->userIds['amohammed'];
        $nurseUser     = $this->userIds['ksingh'];
        $drSharmaUser  = $this->userIds['rsharma'];
        $drRamlogan    = $this->userIds['pramlogan'];
        $drSharmaId    = $this->doctorIds['rsharma'];
        $drRamloganId  = $this->doctorIds['pramlogan'];
        $mainBranch    = $this->branchIds['MAIN'];
        $cashierId     = $this->userIds['tgomez'];

        $shiftId = DB::connection($this->db)->table('cashier_shifts')->value('id');

        // We'll create 10 completed encounters (past visits) across 8 patients
        $scenarios = [
            // [patientIndex, daysAgo, doctorUserId, doctorId, complaint, diagnosis_text, icd10, prescription, labTests, imaging, invoiceServices]
            [0, 45, $drSharmaUser, $drSharmaId,  'Routine diabetes and BP review',
                'Type 2 Diabetes Mellitus — well controlled', 'E11.9',
                [['Metformin', '500mg', 'tablet', 'oral', '1 tablet', 'twice daily', 90, 'days', '180 tablets']],
                ['FBC', 'BMP', 'HBA1C'],
                null,
                ['CONS-GP', 'LAB-FBC', 'LAB-BMP', 'LAB-HBA1C']],

            [1, 30, $drRamlogan, $drRamloganId, 'Fatigue, weight gain, feeling cold',
                'Hypothyroidism — inadequate replacement dose', 'E03.9',
                [['Levothyroxine', '125mcg', 'tablet', 'oral', '1 tablet', 'once daily (morning)', 90, 'days', '90 tablets']],
                ['TSH', 'FBC', 'LIPID'],
                null,
                ['CONS-GP', 'LAB-TSH', 'LAB-FBC', 'LAB-LIPID']],

            [2, 14, $drSharmaUser, $drSharmaId,  'Acute asthma exacerbation — wheeze and SOB',
                'Acute severe asthma exacerbation', 'J45.901',
                [['Salbutamol', '2.5mg', 'solution', 'nebulisation', '2.5mg', 'every 20 min x3 then 4 hourly', 5, 'days', '1 nebule bottle'],
                 ['Prednisolone', '40mg', 'tablet', 'oral', '8 tablets', 'once daily', 5, 'days', '40 tablets']],
                ['FBC', 'CRP'],
                'CXR',
                ['CONS-URGENT', 'PROC-PICO', 'LAB-FBC', 'LAB-CRP', 'IMG-CXR']],

            [4, 60, $drSharmaUser, $drSharmaId,  'Gout flare — right great toe',
                'Acute gouty arthritis — right 1st MTP joint', 'M10.071',
                [['Colchicine', '0.5mg', 'tablet', 'oral', '1 tablet', 'twice daily for 3 days then once daily', 14, 'days', '21 tablets'],
                 ['Allopurinol', '300mg', 'tablet', 'oral', '1 tablet', 'once daily', 90, 'days', '90 tablets']],
                ['BMP', 'UA'],
                null,
                ['CONS-GP', 'LAB-BMP', 'LAB-UA']],

            [7, 20, $drRamlogan, $drRamloganId, 'Poorly controlled diabetes, oedema, hypertension',
                'Type 2 DM with CKD Stage 3; Hypertension', 'E11.65',
                [['Furosemide', '40mg', 'tablet', 'oral', '1 tablet', 'once daily (morning)', 90, 'days', '90 tablets'],
                 ['Ramipril', '5mg', 'tablet', 'oral', '1 tablet', 'once daily', 90, 'days', '90 tablets']],
                ['BMP', 'FBC', 'UA', 'LIPID'],
                'USS-ABD',
                ['CONS-SPEC', 'LAB-BMP', 'LAB-FBC', 'LAB-UA', 'LAB-LIPID', 'IMG-USS-ABD']],

            [9, 10, $drSharmaUser, $drSharmaId,  'Severe headache, nausea, photophobia',
                'Migraine without aura — acute attack', 'G43.009',
                [['Sumatriptan', '50mg', 'tablet', 'oral', '1 tablet', 'at onset, may repeat after 2h', 1, 'days', '6 tablets'],
                 ['Metoclopramide', '10mg', 'tablet', 'oral', '1 tablet', 'three times daily with meals', 5, 'days', '15 tablets']],
                [],
                null,
                ['CONS-GP', 'CERT-SICK']],

            [13, 7, $drRamlogan, $drRamloganId, 'Epigastric pain, heartburn, anxiety symptoms',
                'Gastro-oesophageal reflux disease; Generalised anxiety disorder', 'K21.0',
                [['Omeprazole', '20mg', 'capsule', 'oral', '1 capsule', 'once daily before breakfast', 60, 'days', '60 capsules'],
                 ['Escitalopram', '10mg', 'tablet', 'oral', '1 tablet', 'once daily', 90, 'days', '90 tablets']],
                [],
                null,
                ['CONS-GP']],

            [17, 90, $drSharmaUser, $drSharmaId, 'Palpitations, dizziness, shortness of breath',
                'Atrial fibrillation — permanent; Hypertension', 'I48.21',
                [['Warfarin', '5mg', 'tablet', 'oral', '1 tablet', 'once daily (adjust per INR)', 30, 'days', '30 tablets'],
                 ['Bisoprolol', '5mg', 'tablet', 'oral', '1 tablet', 'once daily', 90, 'days', '90 tablets']],
                ['FBC', 'BMP'],
                'CXR',
                ['CONS-GP', 'PROC-ECG', 'LAB-FBC', 'LAB-BMP', 'IMG-CXR']],

            [3, 5, $drSharmaUser, $drSharmaId,   'Fatigue, pale conjunctivae, dyspnoea on exertion',
                'Iron deficiency anaemia — moderate severity', 'D50.9',
                [['Ferrous sulphate', '200mg', 'tablet', 'oral', '1 tablet', 'twice daily with meals', 90, 'days', '180 tablets'],
                 ['Vitamin C', '500mg', 'tablet', 'oral', '1 tablet', 'twice daily', 90, 'days', '180 tablets']],
                ['FBC', 'BMP'],
                null,
                ['CONS-GP', 'LAB-FBC', 'LAB-BMP']],

            [14, 25, $drRamlogan, $drRamloganId, 'Productive cough, dyspnoea, reduced exercise tolerance',
                'COPD exacerbation — moderate', 'J44.1',
                [['Tiotropium', '18mcg', 'capsule', 'inhalation', '1 capsule', 'once daily', 90, 'days', '30 capsules'],
                 ['Amoxicillin', '500mg', 'capsule', 'oral', '1 capsule', 'three times daily', 7, 'days', '21 capsules'],
                 ['Prednisolone', '30mg', 'tablet', 'oral', '6 tablets', 'once daily', 5, 'days', '30 tablets']],
                ['FBC', 'CRP'],
                'CXR',
                ['CONS-GP', 'LAB-FBC', 'LAB-CRP', 'IMG-CXR']],
        ];

        foreach ($scenarios as $si => $scenario) {
            [$patIdx, $daysAgo, $doctorUser, $doctorId, $complaint, $diagText, $icd10, $rxItems, $labCodes, $imagingCode, $serviceCodes] = $scenario;

            $patientId   = $this->patientIds[$patIdx];
            $encounterAt = now()->subDays($daysAgo)->setTime(rand(8, 14), rand(0, 59));
            $branch      = $mainBranch;

            // Encounter
            $encounterId = DB::connection($this->db)->table('encounters')->insertGetId([
                'patient_id'        => $patientId,
                'branch_id'         => $branch,
                'doctor_id'         => $doctorId,
                'visit_type'        => 'walk_in',
                'status'            => 'completed',
                'encounter_date'    => $encounterAt->format('Y-m-d'),
                'checked_in_at'     => $encounterAt,
                'seen_by_nurse_at'  => $encounterAt->copy()->addMinutes(10),
                'seen_by_doctor_at' => $encounterAt->copy()->addMinutes(25),
                'completed_at'      => $encounterAt->copy()->addMinutes(60),
                'checked_in_by'     => $receptionUser,
                'chief_complaint'   => $complaint,
                'created_at'        => $encounterAt,
                'updated_at'        => $encounterAt->copy()->addMinutes(60),
            ]);
            $this->encounterIds[] = $encounterId;

            // Queue entry
            DB::connection($this->db)->table('queue_entries')->insert([
                'encounter_id'   => $encounterId,
                'patient_id'     => $patientId,
                'branch_id'      => $branch,
                'doctor_id'      => $doctorId,
                'queue_number'   => $si + 1,
                'queue_date'     => $encounterAt->format('Y-m-d'),
                'visit_type'     => 'walk_in',
                'status'         => 'completed',
                'is_priority'    => false,
                'checked_in_by'  => $receptionUser,
                'created_at'     => $encounterAt,
                'updated_at'     => $encounterAt->copy()->addMinutes(60),
            ]);

            // Vital signs
            DB::connection($this->db)->table('vital_signs')->insert([
                'patient_id'           => $patientId,
                'encounter_id'         => $encounterId,
                'recorded_by'          => $nurseUser,
                'systolic_bp'          => rand(110, 160),
                'diastolic_bp'         => rand(70, 100),
                'pulse_rate'           => rand(60, 100),
                'pulse_rhythm'         => 'regular',
                'respiratory_rate'     => rand(14, 20),
                'oxygen_saturation'    => rand(94, 100),
                'temperature'          => round(rand(366, 375) / 10, 1),
                'temperature_site'     => 'oral',
                'weight_kg'            => round(rand(55, 110) + rand(0, 9) / 10, 1),
                'height_cm'            => round(rand(155, 185)),
                'bmi'                  => round(rand(195, 310) / 10, 1),
                'blood_glucose'        => round(rand(45, 120) / 10, 1),
                'blood_glucose_timing' => 'random',
                'pain_score'           => rand(0, 6),
                'recorded_at'          => $encounterAt->copy()->addMinutes(12),
                'created_at'           => $encounterAt->copy()->addMinutes(12),
                'updated_at'           => $encounterAt->copy()->addMinutes(12),
            ]);

            // Nursing assessment
            DB::connection($this->db)->table('nursing_assessments')->insert([
                'encounter_id'         => $encounterId,
                'patient_id'           => $patientId,
                'nurse_id'             => $nurseUser,
                'chief_complaint'      => $complaint,
                'nursing_notes'        => 'Patient alert and oriented. Vitals taken. Allergies reviewed.',
                'allergies_reviewed'   => true,
                'medications_reviewed' => true,
                'status'               => 'completed',
                'completed_at'         => $encounterAt->copy()->addMinutes(20),
                'created_at'           => $encounterAt->copy()->addMinutes(10),
                'updated_at'           => $encounterAt->copy()->addMinutes(20),
            ]);

            // Consultation note (SOAP)
            $noteId = DB::connection($this->db)->table('consultation_notes')->insertGetId([
                'encounter_id'          => $encounterId,
                'patient_id'            => $patientId,
                'doctor_id'             => $doctorId,
                'subjective'            => "Patient presents with {$complaint}. Duration: approximately " . rand(1, 7) . " days. No recent travel. No sick contacts reported.",
                'objective'             => 'Vitals as recorded by nursing. Examination findings: patient appears in no acute distress. Cardiorespiratory examination within normal limits for presentation.',
                'assessment'            => $diagText,
                'plan'                  => 'Prescriptions issued. Investigations ordered. Patient counselled on medication compliance. Follow-up in ' . rand(2, 8) . ' weeks.',
                'is_finalized'          => true,
                'finalized_by'          => $doctorUser,
                'finalized_at'          => $encounterAt->copy()->addMinutes(55),
                'content_hash'          => hash('sha256', $diagText . $encounterAt->toISOString()),
                'created_at'            => $encounterAt->copy()->addMinutes(25),
                'updated_at'            => $encounterAt->copy()->addMinutes(55),
            ]);

            // Diagnosis
            DB::connection($this->db)->table('diagnoses')->insert([
                'encounter_id'   => $encounterId,
                'patient_id'     => $patientId,
                'icd10_code'     => $icd10,
                'diagnosis_text' => $diagText,
                'diagnosis_type' => 'primary',
                'certainty'      => 'confirmed',
                'diagnosed_by'   => $doctorUser,
                'created_at'     => $encounterAt->copy()->addMinutes(40),
                'updated_at'     => $encounterAt->copy()->addMinutes(40),
            ]);

            // Prescription
            if (!empty($rxItems)) {
                $rxNum = 'RX-' . $encounterAt->format('Ymd') . '-' . str_pad($si + 1, 3, '0', STR_PAD_LEFT);
                DB::connection($this->db)->table('prescription_number_sequences')->updateOrInsert(
                    ['date_key' => $encounterAt->format('Y-m-d')],
                    ['last_number' => $si + 1]
                );
                $prescId = DB::connection($this->db)->table('prescriptions')->insertGetId([
                    'prescription_number' => $rxNum,
                    'encounter_id'        => $encounterId,
                    'patient_id'          => $patientId,
                    'doctor_id'           => $doctorId,
                    'branch_id'           => $branch,
                    'status'              => 'issued',
                    'allergy_checked'     => true,
                    'issued_by'           => $doctorUser,
                    'issued_at'           => $encounterAt->copy()->addMinutes(50),
                    'created_at'          => $encounterAt->copy()->addMinutes(50),
                    'updated_at'          => $encounterAt->copy()->addMinutes(50),
                ]);
                foreach ($rxItems as $sortOrder => [$medName, $strength, $form, $route, $dose, $freq, $duration, $durUnit, $qty]) {
                    DB::connection($this->db)->table('prescription_items')->insert([
                        'prescription_id' => $prescId,
                        'medication_name' => $medName,
                        'strength'        => $strength,
                        'form'            => $form,
                        'route'           => $route,
                        'dose'            => $dose,
                        'frequency'       => $freq,
                        'duration'        => $duration,
                        'duration_unit'   => $durUnit,
                        'quantity'        => $qty,
                        'refills_allowed' => 0,
                        'sort_order'      => $sortOrder,
                        'created_at'      => now(),
                        'updated_at'      => now(),
                    ]);
                }
            }

            // Lab order
            if (!empty($labCodes)) {
                $labOrderNum = 'LAB-' . $encounterAt->format('Ymd') . '-' . str_pad($si + 1, 3, '0', STR_PAD_LEFT);
                $labOrderId = DB::connection($this->db)->table('lab_orders')->insertGetId([
                    'order_number'       => $labOrderNum,
                    'patient_id'         => $patientId,
                    'encounter_id'       => $encounterId,
                    'ordered_by'         => $doctorUser,
                    'branch_id'          => $branch,
                    'status'             => 'results_reviewed',
                    'order_type'         => 'routine',
                    'clinical_notes'     => "Ordered for: {$complaint}",
                    'ordered_at'         => $encounterAt->copy()->addMinutes(45),
                    'collected_at'       => $encounterAt->copy()->addMinutes(65),
                    'collected_by'       => $nurseUser,
                    'sent_to_lab_at'     => $encounterAt->copy()->addHours(2),
                    'results_received_at'=> $encounterAt->copy()->addHours(6),
                    'reviewed_at'        => $encounterAt->copy()->addHours(8),
                    'reviewed_by'        => $doctorUser,
                    'created_at'         => $encounterAt->copy()->addMinutes(45),
                    'updated_at'         => $encounterAt->copy()->addHours(8),
                ]);

                $labResultDefs = $this->labResultDefinitions();
                foreach ($labCodes as $code) {
                    if (!isset($labResultDefs[$code])) continue;
                    $itemId = DB::connection($this->db)->table('lab_order_items')->insertGetId([
                        'lab_order_id' => $labOrderId,
                        'test_name'    => $labResultDefs[$code]['name'],
                        'test_code'    => $code,
                        'created_at'   => now(),
                        'updated_at'   => now(),
                    ]);
                    foreach ($labResultDefs[$code]['results'] as $result) {
                        $flag = $result['flag'] ?? 'normal';
                        DB::connection($this->db)->table('lab_results')->insert([
                            'lab_order_id'       => $labOrderId,
                            'lab_order_item_id'  => $itemId,
                            'patient_id'         => $patientId,
                            'test_name'          => $result['test'],
                            'test_code'          => $code . '-' . Str::slug($result['test']),
                            'value'              => $result['value'],
                            'value_unit'         => $result['unit'],
                            'reference_range'    => $result['range'],
                            'flag'               => $flag,
                            'is_critical'        => $flag === 'critical_high' || $flag === 'critical_low',
                            'performing_lab'     => 'Caribbean Medical Labs Ltd.',
                            'result_datetime'    => $encounterAt->copy()->addHours(5),
                            'source'             => 'manual',
                            'created_at'         => now(),
                            'updated_at'         => now(),
                        ]);
                    }
                }
            }

            // Imaging order
            if ($imagingCode) {
                $imgDefs = $this->imagingDefinitions();
                $def = $imgDefs[$imagingCode] ?? null;
                if ($def) {
                    $imgOrderNum = 'IMG-' . $encounterAt->format('Ymd') . '-' . str_pad($si + 1, 3, '0', STR_PAD_LEFT);
                    $imgOrderId = DB::connection($this->db)->table('imaging_orders')->insertGetId([
                        'order_number'        => $imgOrderNum,
                        'patient_id'          => $patientId,
                        'encounter_id'        => $encounterId,
                        'ordered_by'          => $doctorUser,
                        'branch_id'           => $branch,
                        'modality'            => $def['modality'],
                        'body_part'           => $def['body_part'],
                        'clinical_indication' => $complaint,
                        'priority'            => 'routine',
                        'status'              => 'report_reviewed',
                        'accession_number'    => 'ACC' . str_pad($si + 1, 8, '0', STR_PAD_LEFT),
                        'ordered_at'          => $encounterAt->copy()->addMinutes(45),
                        'performed_at'        => $encounterAt->copy()->addHours(3),
                        'report_received_at'  => $encounterAt->copy()->addHours(5),
                        'reviewed_at'         => $encounterAt->copy()->addHours(7),
                        'reviewed_by'         => $doctorUser,
                        'created_at'          => $encounterAt->copy()->addMinutes(45),
                        'updated_at'          => $encounterAt->copy()->addHours(7),
                    ]);

                    DB::connection($this->db)->table('imaging_studies')->insert([
                        'imaging_order_id'    => $imgOrderId,
                        'patient_id'          => $patientId,
                        'modality'            => $def['modality'],
                        'study_description'   => $def['description'],
                        'number_of_series'    => rand(1, 3),
                        'number_of_instances' => rand(2, 10),
                        'study_datetime'      => $encounterAt->copy()->addHours(3),
                        'created_at'          => now(),
                        'updated_at'          => now(),
                    ]);

                    DB::connection($this->db)->table('imaging_reports')->insert([
                        'imaging_order_id' => $imgOrderId,
                        'patient_id'       => $patientId,
                        'radiologist_name' => 'Dr. Sunil Ramdass',
                        'report_status'    => 'final',
                        'findings'         => $def['findings'],
                        'impression'       => $def['impression'],
                        'recommendations'  => $def['recommendations'],
                        'is_critical'      => false,
                        'source'           => 'manual',
                        'report_datetime'  => $encounterAt->copy()->addHours(5),
                        'reviewed_at'      => $encounterAt->copy()->addHours(7),
                        'reviewed_by'      => $doctorUser,
                        'created_at'       => now(),
                        'updated_at'       => now(),
                    ]);
                }
            }

            // Invoice + payment
            $invoiceNum = 'INV-' . $encounterAt->format('Ymd') . '-' . str_pad($si + 1, 3, '0', STR_PAD_LEFT);
            DB::connection($this->db)->table('invoice_number_sequences')->updateOrInsert(
                ['date_key' => $encounterAt->format('Y-m-d')],
                ['last_number' => $si + 1]
            );

            $subtotal = 0;
            $lineItems = [];
            foreach ($serviceCodes as $code) {
                $svcId = $this->serviceIds[$code] ?? null;
                if (!$svcId) continue;
                $price = DB::connection($this->db)->table('services')->where('id', $svcId)->value('price');
                $name  = DB::connection($this->db)->table('services')->where('id', $svcId)->value('name');
                $subtotal += $price;
                $lineItems[] = ['service_id' => $svcId, 'name' => $name, 'price' => $price];
            }

            $invoiceId = DB::connection($this->db)->table('invoices')->insertGetId([
                'invoice_number'  => $invoiceNum,
                'patient_id'      => $patientId,
                'branch_id'       => $branch,
                'encounter_id'    => $encounterId,
                'status'          => 'paid',
                'subtotal'        => $subtotal,
                'tax_total'       => 0,
                'discount'        => 0,
                'total'           => $subtotal,
                'amount_paid'     => $subtotal,
                'amount_due'      => 0,
                'insurance_portion'=> 0,
                'patient_portion'  => $subtotal,
                'created_by'      => $cashierId,
                'finalized_by'    => $cashierId,
                'finalized_at'    => $encounterAt->copy()->addMinutes(70),
                'created_at'      => $encounterAt->copy()->addMinutes(60),
                'updated_at'      => $encounterAt->copy()->addMinutes(80),
            ]);

            foreach ($lineItems as $line) {
                DB::connection($this->db)->table('invoice_items')->insert([
                    'invoice_id'  => $invoiceId,
                    'service_id'  => $line['service_id'],
                    'description' => $line['name'],
                    'quantity'    => 1,
                    'unit_price'  => $line['price'],
                    'tax_rate'    => 0,
                    'tax_amount'  => 0,
                    'discount'    => 0,
                    'line_total'  => $line['price'],
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ]);
            }

            // Payment
            $payRef = 'PAY-' . $encounterAt->format('Ymd') . '-' . str_pad($si + 1, 3, '0', STR_PAD_LEFT);
            DB::connection($this->db)->table('payment_reference_sequences')->updateOrInsert(
                ['date_key' => $encounterAt->format('Y-m-d')],
                ['last_number' => $si + 1]
            );

            DB::connection($this->db)->table('payments')->insert([
                'payment_reference'  => $payRef,
                'invoice_id'         => $invoiceId,
                'patient_id'         => $patientId,
                'branch_id'          => $branch,
                'cashier_shift_id'   => $shiftId,
                'payment_method'     => ['cash', 'card', 'linx'][$si % 3],
                'amount'             => $subtotal,
                'collected_by'       => $cashierId,
                'voided'             => false,
                'created_at'         => $encounterAt->copy()->addMinutes(75),
                'updated_at'         => $encounterAt->copy()->addMinutes(75),
            ]);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Upcoming appointments (future)
    // ─────────────────────────────────────────────────────────────────────────
    // ─────────────────────────────────────────────────────────────────────────
    // Today's live queue — patients in various stages right now
    // ─────────────────────────────────────────────────────────────────────────
    private function seedTodaysQueue(): void
    {
        $receptionUser = $this->userIds['amohammed'];
        $nurseUser     = $this->userIds['ksingh'];
        $drSharmaId    = $this->doctorIds['rsharma'];
        $drRamloganId  = $this->doctorIds['pramlogan'];
        $mainBranch    = $this->branchIds['MAIN'];
        $today         = now()->format('Y-m-d');

        // [patientIndex, queueNum, encounterStatus, queueStatus, checkedInMinsAgo, complaint, doctorId, isPriority, priorityReason]
        $livePatients = [
            [5,  1, 'completed',       'completed',       195, 'High blood pressure — routine check', $drSharmaId,   false, null],
            [11, 2, 'with_nurse',      'with_nurse',      75,  'Lower back pain — 3 days duration',   $drSharmaId,   false, null],
            [16, 3, 'awaiting_doctor', 'awaiting_doctor', 60,  'Follow-up after hospital discharge',  $drRamloganId, true,  'Post-hospitalisation'],
            [18, 4, 'with_doctor',     'with_doctor',     45,  'Persistent cough and fever',          $drSharmaId,   false, null],
            [8,  5, 'checked_in',      'waiting',         20,  'Routine pre-employment medical',      $drSharmaId,   false, null],
            [19, 6, 'checked_in',      'waiting',         5,   'Chest tightness — shortness of breath', $drRamloganId, true, 'Cardiac symptoms'],
        ];

        // Seed today's queue number sequence
        DB::connection($this->db)->table('queue_number_sequences')->insert([
            'branch_id'  => $mainBranch,
            'queue_date' => $today,
            'last_number'=> count($livePatients),
            'created_at' => now()->startOfDay()->addHours(8),
            'updated_at' => now(),
        ]);

        foreach ($livePatients as [$patIdx, $qNum, $encStatus, $qStatus, $minsAgo, $complaint, $doctorId, $isPriority, $priorityReason]) {
            $checkedInAt = now()->subMinutes($minsAgo);

            $seenByNurseAt  = in_array($encStatus, ['with_nurse', 'awaiting_doctor', 'with_doctor', 'completed'])
                ? $checkedInAt->copy()->addMinutes(12) : null;
            $seenByDoctorAt = in_array($encStatus, ['with_doctor', 'completed'])
                ? $checkedInAt->copy()->addMinutes(35) : null;
            $completedAt    = $encStatus === 'completed'
                ? $checkedInAt->copy()->addMinutes(65) : null;

            $encounterId = DB::connection($this->db)->table('encounters')->insertGetId([
                'patient_id'        => $this->patientIds[$patIdx],
                'branch_id'         => $mainBranch,
                'doctor_id'         => $doctorId,
                'visit_type'        => 'walk_in',
                'status'            => $encStatus,
                'encounter_date'    => $today,
                'checked_in_at'     => $checkedInAt,
                'seen_by_nurse_at'  => $seenByNurseAt,
                'seen_by_doctor_at' => $seenByDoctorAt,
                'completed_at'      => $completedAt,
                'checked_in_by'     => $receptionUser,
                'chief_complaint'   => $complaint,
                'created_at'        => $checkedInAt,
                'updated_at'        => now(),
            ]);

            DB::connection($this->db)->table('queue_entries')->insert([
                'encounter_id'   => $encounterId,
                'patient_id'     => $this->patientIds[$patIdx],
                'branch_id'      => $mainBranch,
                'doctor_id'      => $doctorId,
                'queue_number'   => $qNum,
                'queue_date'     => $today,
                'visit_type'     => 'walk_in',
                'status'         => $qStatus,
                'is_priority'    => $isPriority,
                'priority_reason'=> $priorityReason,
                'checked_in_by'  => $receptionUser,
                'created_at'     => $checkedInAt,
                'updated_at'     => now(),
            ]);

            // Vitals for patients past the waiting stage
            if ($seenByNurseAt) {
                DB::connection($this->db)->table('vital_signs')->insert([
                    'patient_id'           => $this->patientIds[$patIdx],
                    'encounter_id'         => $encounterId,
                    'recorded_by'          => $nurseUser,
                    'systolic_bp'          => rand(110, 165),
                    'diastolic_bp'         => rand(68, 100),
                    'pulse_rate'           => rand(58, 102),
                    'pulse_rhythm'         => 'regular',
                    'respiratory_rate'     => rand(14, 20),
                    'oxygen_saturation'    => rand(94, 100),
                    'temperature'          => round(rand(366, 376) / 10, 1),
                    'temperature_site'     => 'oral',
                    'weight_kg'            => round(rand(55, 110) + rand(0, 9) / 10, 1),
                    'height_cm'            => rand(155, 185),
                    'bmi'                  => round(rand(195, 310) / 10, 1),
                    'blood_glucose'        => round(rand(45, 115) / 10, 1),
                    'blood_glucose_timing' => 'random',
                    'pain_score'           => rand(0, 7),
                    'recorded_at'          => $seenByNurseAt->copy()->addMinutes(5),
                    'created_at'           => $seenByNurseAt->copy()->addMinutes(5),
                    'updated_at'           => $seenByNurseAt->copy()->addMinutes(5),
                ]);
            }

            // Queue events for status transitions
            $events = [];
            $events[] = ['checked_in', null, 'waiting', $receptionUser, $checkedInAt];
            if ($seenByNurseAt) {
                $events[] = ['called_to_nurse', 'waiting', 'with_nurse', $nurseUser, $seenByNurseAt];
            }
            if ($encStatus === 'awaiting_doctor' || $encStatus === 'with_doctor' || $encStatus === 'completed') {
                $events[] = ['nurse_done', 'with_nurse', 'awaiting_doctor', $nurseUser, $seenByNurseAt ? $seenByNurseAt->copy()->addMinutes(15) : now()];
            }
            if ($seenByDoctorAt) {
                $events[] = ['called_to_doctor', 'awaiting_doctor', 'with_doctor', $this->userIds['rsharma'], $seenByDoctorAt];
            }
            if ($completedAt) {
                $events[] = ['completed', 'with_doctor', 'completed', $this->userIds['rsharma'], $completedAt];
            }

            foreach ($events as [$eventType, $fromStatus, $toStatus, $performedBy, $occurredAt]) {
                DB::connection($this->db)->table('queue_events')->insert([
                    'queue_entry_id' => DB::connection($this->db)->table('queue_entries')->where('encounter_id', $encounterId)->value('id'),
                    'event_type'     => $eventType,
                    'from_status'    => $fromStatus,
                    'to_status'      => $toStatus,
                    'performed_by'   => $performedBy,
                    'occurred_at'    => $occurredAt,
                ]);
            }
        }

        // Doctor availability — both doctors on duty
        DB::connection($this->db)->table('doctor_availability')->insert([
            ['doctor_id' => $drSharmaId,   'branch_id' => $mainBranch, 'status' => 'busy',      'status_note' => 'With patient', 'updated_by' => $this->userIds['rsharma'],  'created_at' => now(), 'updated_at' => now()],
            ['doctor_id' => $drRamloganId, 'branch_id' => $mainBranch, 'status' => 'available', 'status_note' => null,           'updated_by' => $this->userIds['pramlogan'],'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->command->info("  → Today's queue: 6 patients (1 completed, 1 with nurse, 1 awaiting doctor, 1 with doctor, 2 waiting)");
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Appointments — today through end of week + next two weeks of follow-ups
    // ─────────────────────────────────────────────────────────────────────────
    private function seedWeekSchedule(): void
    {
        $bookingUser  = $this->userIds['amohammed'];
        $drSharmaId   = $this->doctorIds['rsharma'];
        $drRamloganId = $this->doctorIds['pramlogan'];
        $drAliId      = $this->doctorIds['kali'];
        $drBaptisteId = $this->doctorIds['sbaptiste'];
        $mainBranch   = $this->branchIds['MAIN'];
        $northBranch  = $this->branchIds['NORTH'];

        // [patientIndex, daysFromNow, time, doctorId, branchId, reason, type, status, durationMins]
        $appts = [
            // ── Today's remaining scheduled appointments ──────────────────────
            [6,  0, '14:00', $drSharmaId,   $mainBranch,  'Annual wellness check',                   'scheduled', 'confirmed',  30],
            [12, 0, '15:30', $drRamloganId, $mainBranch,  'Thyroid result review',                   'follow_up', 'confirmed',  15],
            [15, 0, '16:00', $drSharmaId,   $mainBranch,  'Blood pressure medication adjustment',    'follow_up', 'confirmed',  15],

            // ── Tomorrow ──────────────────────────────────────────────────────
            [0,  1, '08:30', $drSharmaId,   $mainBranch,  'Diabetes — HbA1c review',                 'follow_up', 'confirmed',  20],
            [3,  1, '09:00', $drRamloganId, $mainBranch,  'Iron deficiency anaemia — recheck FBC',   'follow_up', 'confirmed',  15],
            [7,  1, '10:30', $drRamloganId, $mainBranch,  'CKD Stage 3 — quarterly monitoring',      'follow_up', 'confirmed',  30],
            [10, 1, '11:00', $drAliId,      $northBranch, 'Hypertension + BPH — cardiology review',  'follow_up', 'confirmed',  30],
            [13, 1, '14:00', $drSharmaId,   $mainBranch,  'GORD — symptom reassessment',             'follow_up', 'confirmed',  15],
            [17, 1, '15:00', $drAliId,      $northBranch, 'Atrial fibrillation — INR and rate control','follow_up','confirmed', 20],

            // ── Day after tomorrow ────────────────────────────────────────────
            [1,  2, '09:30', $drRamloganId, $mainBranch,  'Hypothyroidism — TSH recheck',            'follow_up', 'confirmed',  15],
            [2,  2, '10:00', $drSharmaId,   $mainBranch,  'Post-asthma exacerbation check',          'follow_up', 'confirmed',  20],
            [4,  2, '11:30', $drSharmaId,   $mainBranch,  'Gout — urate and renal function',         'follow_up', 'confirmed',  15],
            [9,  2, '14:30', $drSharmaId,   $mainBranch,  'Migraine prophylaxis — 4-week review',    'follow_up', 'confirmed',  15],
            [11, 2, '16:00', $drBaptisteId, $mainBranch,  'New patient — GP registration',           'scheduled', 'confirmed',  30],

            // ── 3 days from now ───────────────────────────────────────────────
            [5,  3, '08:00', $drSharmaId,   $mainBranch,  'General health check — results review',   'scheduled', 'confirmed',  30],
            [14, 3, '09:00', $drRamloganId, $mainBranch,  'COPD — post-exacerbation spirometry',     'follow_up', 'confirmed',  30],
            [16, 3, '10:30', $drAliId,      $northBranch, 'Post-hospitalisation cardiac review',      'follow_up', 'confirmed',  30],
            [18, 3, '14:00', $drSharmaId,   $mainBranch,  'Respiratory — chest X-ray result review', 'follow_up', 'confirmed',  20],
            [6,  3, '15:00', $drBaptisteId, $mainBranch,  'Routine cervical smear',                  'scheduled', 'confirmed',  20],

            // ── 4 days from now ───────────────────────────────────────────────
            [0,  4, '08:30', $drSharmaId,   $mainBranch,  'Diabetes — insulin dose adjustment',      'follow_up', 'confirmed',  20],
            [8,  4, '09:00', $drSharmaId,   $mainBranch,  'Pre-employment medical — Oilfields',      'scheduled', 'confirmed',  45],
            [19, 4, '11:00', $drAliId,      $northBranch, 'Cardiology new referral — chest pain',    'urgent',    'confirmed',  30],
            [1,  4, '14:00', $drRamloganId, $mainBranch,  'Levothyroxine dose review',               'follow_up', 'confirmed',  15],
            [15, 4, '15:30', $drBaptisteId, $mainBranch,  'Routine wellness check — female',         'scheduled', 'confirmed',  30],

            // ── 5 days from now ───────────────────────────────────────────────
            [7,  5, '09:00', $drRamloganId, $mainBranch,  'CKD — nephrology liaison update',         'follow_up', 'confirmed',  30],
            [9,  5, '10:00', $drSharmaId,   $mainBranch,  'Migraine diary review',                   'follow_up', 'confirmed',  15],
            [3,  5, '11:30', $drRamloganId, $mainBranch,  'Iron studies — repeat panel',             'follow_up', 'confirmed',  15],
            [12, 5, '14:00', $drSharmaId,   $mainBranch,  'Anxiety disorder — medication review',    'follow_up', 'confirmed',  20],
            [17, 5, '15:00', $drAliId,      $northBranch, 'AF — Warfarin INR check',                 'follow_up', 'confirmed',  15],

            // ── 6 days from now ───────────────────────────────────────────────
            [4,  6, '08:30', $drSharmaId,   $mainBranch,  'Gout management — allopurinol titration', 'follow_up', 'confirmed',  15],
            [10, 6, '09:30', $drAliId,      $northBranch, 'BPH — IPSS score and flow rate',          'follow_up', 'confirmed',  30],
            [14, 6, '10:00', $drSharmaId,   $mainBranch,  'COPD — inhaler technique review',         'follow_up', 'confirmed',  20],
            [18, 6, '14:00', $drBaptisteId, $mainBranch,  'Walk-in slot — available',               'walk_in',   'scheduled',  15],
            [5,  6, '15:00', $drSharmaId,   $mainBranch,  'Hypertension — ambulatory BP result',    'follow_up', 'confirmed',  20],

            // ── Next week — days 7–13 ─────────────────────────────────────────
            [0,  7, '09:00', $drSharmaId,   $mainBranch,  'Diabetes and BP 3-month review',          'follow_up', 'confirmed',  20],
            [1,  8, '10:30', $drRamloganId, $mainBranch,  'Thyroid levels — 3-month check',          'follow_up', 'confirmed',  15],
            [2,  8, '14:00', $drSharmaId,   $mainBranch,  'Asthma — peak flow diary review',         'follow_up', 'confirmed',  20],
            [7,  9, '11:00', $drRamloganId, $mainBranch,  'CKD — renal function panel',              'follow_up', 'confirmed',  30],
            [9,  10,'09:30', $drSharmaId,   $mainBranch,  'Migraine prophylaxis — 8-week review',    'follow_up', 'confirmed',  15],
            [16, 11,'10:00', $drAliId,      $northBranch, '6-week post-discharge cardiac review',    'follow_up', 'confirmed',  30],
            [4,  12,'08:30', $drSharmaId,   $mainBranch,  'Gout — uric acid recheck',                'follow_up', 'confirmed',  15],
            [13, 13,'09:00', $drSharmaId,   $mainBranch,  'GORD and anxiety — 3-month review',       'follow_up', 'confirmed',  20],
        ];

        $apptNum = 0;
        foreach ($appts as [$patIdx, $days, $time, $doctorId, $branchId, $reason, $apptType, $status, $duration]) {
            $apptNum++;
            $apptDate = now()->addDays($days)->format('Y-m-d');
            $dateKey  = now()->addDays($days)->format('Y-m-d');
            $apptNo   = 'APT-' . now()->addDays($days)->format('Ymd') . '-' . str_pad($apptNum, 3, '0', STR_PAD_LEFT);

            DB::connection($this->db)->table('appointment_number_sequences')->updateOrInsert(
                ['date_key' => $dateKey],
                ['last_number' => DB::raw('last_number + 1')]
            );

            DB::connection($this->db)->table('appointments')->insert([
                'appointment_number' => $apptNo,
                'patient_id'         => $this->patientIds[$patIdx],
                'doctor_id'          => $doctorId,
                'branch_id'          => $branchId,
                'appointment_date'   => $apptDate,
                'appointment_time'   => $time . ':00',
                'duration_minutes'   => $duration,
                'appointment_type'   => $apptType,
                'status'             => $status,
                'reason'             => $reason,
                'booked_by'          => $bookingUser,
                'reminder_sent'      => $days > 1 ? false : true,
                'reminder_sent_at'   => $days <= 1 ? now()->subHours(24) : null,
                'created_at'         => now()->subDays(rand(1, 14)),
                'updated_at'         => now(),
            ]);
        }

        $this->command->info('  → Week schedule: ' . $apptNum . ' appointments seeded (today through +13 days)');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Follow-ups — pending next-visit orders tied to completed encounters
    // ─────────────────────────────────────────────────────────────────────────
    private function seedFollowUps(): void
    {
        $drSharmaId    = $this->doctorIds['rsharma'];
        $drRamloganId  = $this->doctorIds['pramlogan'];
        $drAliId       = $this->doctorIds['kali'];
        $mainBranch    = $this->branchIds['MAIN'];
        $northBranch   = $this->branchIds['NORTH'];

        // Pull the encounter IDs we seeded — first 10 are from seedEncounters
        $encounterIds = DB::connection($this->db)->table('encounters')
            ->where('status', 'completed')
            ->orderBy('id')
            ->limit(10)
            ->pluck('id', 'patient_id')
            ->all();

        $followUps = [
            // [patientIndex, encIdOffset, daysToTarget, followUpDays, reason, instructions, drId, status]
            [0,  0, 90, 90,  'Diabetes and hypertension 3-month review', 'Repeat HbA1c, BMP, lipid panel before visit. Bring glucose diary.', $drSharmaId,   'pending'],
            [1,  1, 90, 90,  'Hypothyroidism — TSH recheck',             'Repeat TSH, free T4 two weeks before appointment.',                   $drRamloganId, 'pending'],
            [2,  2, 42, 42,  'Post-exacerbation asthma follow-up',       'Peak flow diary daily. Return sooner if symptoms worsen.',             $drSharmaId,   'booked'],
            [4,  3, 30, 30,  'Gout — urate-lowering therapy review',     'Fasting uric acid and renal function one week before visit.',          $drSharmaId,   'pending'],
            [7,  4, 90, 90,  'CKD Stage 3 quarterly monitoring',         'Repeat BMP, FBC, urinalysis, spot protein:creatinine ratio.',          $drRamloganId, 'pending'],
            [9,  5, 28, 28,  'Migraine prophylaxis — 4-week check',      'Headache diary to be completed and brought to visit.',                 $drSharmaId,   'booked'],
            [13, 6, 60, 60,  'GORD and GAD — 2-month medication review', 'Note whether heartburn has resolved. Mood screening questionnaire.',   $drSharmaId,   'pending'],
            [17, 7, 30, 30,  'AF — INR and rate control',                'INR check 1 week prior. Bring current Warfarin booklet.',              $drAliId,      'booked'],
            [3,  8, 60, 60,  'Iron deficiency anaemia — recheck FBC',    'Repeat FBC and iron studies. Ensure ferrous sulphate taken with food.', $drRamloganId,'pending'],
            [14, 9, 42, 42,  'COPD post-exacerbation — spirometry',      'Bring all inhalers to appointment. Post-bronchodilator spirometry.',   $drRamloganId, 'booked'],
        ];

        $encounterList = DB::connection($this->db)->table('encounters')
            ->where('status', 'completed')
            ->orderBy('id')
            ->limit(10)
            ->get(['id', 'patient_id', 'doctor_id', 'branch_id']);

        foreach ($followUps as $i => [$patIdx, $encOffset, $daysToTarget, $followUpDays, $reason, $instructions, $drId, $status]) {
            $enc = $encounterList[$encOffset] ?? null;
            if (! $enc) continue;

            DB::connection($this->db)->table('follow_ups')->insert([
                'encounter_id'   => $enc->id,
                'patient_id'     => $this->patientIds[$patIdx],
                'doctor_id'      => $drId,
                'branch_id'      => $mainBranch,
                'target_date'    => now()->addDays($daysToTarget)->format('Y-m-d'),
                'follow_up_days' => $followUpDays,
                'reason'         => $reason,
                'instructions'   => $instructions,
                'status'         => $status,
                'created_by'     => $drId === $drSharmaId ? $this->userIds['rsharma'] : ($drId === $drRamloganId ? $this->userIds['pramlogan'] : $this->userIds['kali']),
                'created_at'     => now()->subDays(rand(1, 60)),
                'updated_at'     => now(),
            ]);
        }

        $this->command->info('  → Follow-ups: 10 records seeded (pending and booked)');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Lab result definitions (realistic values)
    // ─────────────────────────────────────────────────────────────────────────
    private function labResultDefinitions(): array
    {
        return [
            'FBC' => ['name' => 'Full Blood Count', 'results' => [
                ['test' => 'Haemoglobin',         'value' => '11.2', 'unit' => 'g/dL',  'range' => '12.0–17.0',  'flag' => 'low'],
                ['test' => 'White Cell Count',    'value' => '8.4',  'unit' => '×10⁹/L','range' => '4.0–11.0',   'flag' => 'normal'],
                ['test' => 'Platelets',           'value' => '245',  'unit' => '×10⁹/L','range' => '150–400',    'flag' => 'normal'],
                ['test' => 'Haematocrit',         'value' => '33.5', 'unit' => '%',      'range' => '36–50',      'flag' => 'low'],
                ['test' => 'MCV',                 'value' => '72',   'unit' => 'fL',     'range' => '80–100',     'flag' => 'low'],
                ['test' => 'MCH',                 'value' => '23.5', 'unit' => 'pg',     'range' => '27–33',      'flag' => 'low'],
            ]],
            'BMP' => ['name' => 'Basic Metabolic Panel', 'results' => [
                ['test' => 'Sodium',              'value' => '138',  'unit' => 'mmol/L', 'range' => '135–145',    'flag' => 'normal'],
                ['test' => 'Potassium',           'value' => '4.1',  'unit' => 'mmol/L', 'range' => '3.5–5.0',   'flag' => 'normal'],
                ['test' => 'Creatinine',          'value' => '138',  'unit' => 'µmol/L', 'range' => '62–106',     'flag' => 'high'],
                ['test' => 'Urea',                'value' => '9.2',  'unit' => 'mmol/L', 'range' => '2.5–7.5',   'flag' => 'high'],
                ['test' => 'eGFR',                'value' => '44',   'unit' => 'mL/min/1.73m²','range' => '>60', 'flag' => 'low'],
                ['test' => 'Random Blood Glucose','value' => '12.4', 'unit' => 'mmol/L', 'range' => '3.9–7.8',   'flag' => 'high'],
            ]],
            'HBA1C' => ['name' => 'Glycated Haemoglobin', 'results' => [
                ['test' => 'HbA1c',               'value' => '8.9',  'unit' => '%',      'range' => '<7.0',       'flag' => 'high'],
                ['test' => 'Estimated avg glucose','value' => '11.4', 'unit' => 'mmol/L', 'range' => '<8.6',      'flag' => 'high'],
            ]],
            'TSH' => ['name' => 'Thyroid Stimulating Hormone', 'results' => [
                ['test' => 'TSH',                 'value' => '8.6',  'unit' => 'mIU/L',  'range' => '0.4–4.0',   'flag' => 'high'],
                ['test' => 'Free T4',             'value' => '10.2', 'unit' => 'pmol/L', 'range' => '12–22',      'flag' => 'low'],
            ]],
            'LIPID' => ['name' => 'Fasting Lipid Profile', 'results' => [
                ['test' => 'Total Cholesterol',   'value' => '6.2',  'unit' => 'mmol/L', 'range' => '<5.0',       'flag' => 'high'],
                ['test' => 'LDL Cholesterol',     'value' => '4.1',  'unit' => 'mmol/L', 'range' => '<3.0',       'flag' => 'high'],
                ['test' => 'HDL Cholesterol',     'value' => '0.9',  'unit' => 'mmol/L', 'range' => '>1.0',       'flag' => 'low'],
                ['test' => 'Triglycerides',       'value' => '2.8',  'unit' => 'mmol/L', 'range' => '<1.7',       'flag' => 'high'],
            ]],
            'UA' => ['name' => 'Urinalysis', 'results' => [
                ['test' => 'Protein',             'value' => '2+',   'unit' => '',        'range' => 'Negative',   'flag' => 'abnormal'],
                ['test' => 'Glucose',             'value' => '1+',   'unit' => '',        'range' => 'Negative',   'flag' => 'abnormal'],
                ['test' => 'Blood',               'value' => 'Trace','unit' => '',        'range' => 'Negative',   'flag' => 'abnormal'],
                ['test' => 'pH',                  'value' => '5.5',  'unit' => '',        'range' => '5.0–8.0',    'flag' => 'normal'],
                ['test' => 'Specific Gravity',    'value' => '1.018','unit' => '',        'range' => '1.005–1.030','flag' => 'normal'],
            ]],
            'CRP' => ['name' => 'C-Reactive Protein', 'results' => [
                ['test' => 'CRP',                 'value' => '48',   'unit' => 'mg/L',   'range' => '<10',        'flag' => 'high'],
            ]],
            'PSA' => ['name' => 'PSA', 'results' => [
                ['test' => 'PSA Total',           'value' => '5.2',  'unit' => 'ng/mL',  'range' => '<4.0',       'flag' => 'high'],
            ]],
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Imaging report definitions
    // ─────────────────────────────────────────────────────────────────────────
    private function imagingDefinitions(): array
    {
        return [
            'CXR' => [
                'modality'   => 'CR',
                'body_part'  => 'Chest',
                'description'=> 'Chest X-Ray PA',
                'findings'   => "The cardiac silhouette is mildly enlarged with a cardiothoracic ratio of approximately 0.55. The lung fields demonstrate increased peribronchial markings bilaterally, consistent with the clinical presentation. No focal consolidation, pleural effusion or pneumothorax identified. The diaphragms are mildly flattened bilaterally, in keeping with air trapping. Bony thorax intact. Mediastinal contour unremarkable.",
                'impression' => "1. Mildly enlarged cardiac silhouette — clinical correlation recommended.\n2. Peribronchial cuffing with air trapping bilaterally — in keeping with known asthma/COPD. No acute consolidation or effusion.",
                'recommendations' => "Echocardiogram advised to evaluate cardiac function. Repeat CXR in 4–6 weeks if clinically indicated.",
            ],
            'USS-ABD' => [
                'modality'   => 'US',
                'body_part'  => 'Abdomen',
                'description'=> 'Abdominal Ultrasound',
                'findings'   => "Liver: Mildly increased echogenicity consistent with hepatic steatosis. No focal lesion. Bile ducts not dilated.\nGallbladder: Distended, containing multiple echogenic foci with posterior shadowing, consistent with cholelithiasis. No pericholecystic fluid.\nKidneys: Right kidney measures 9.8cm, left 9.5cm. Bilateral increased cortical echogenicity with mild loss of corticomedullary differentiation, in keeping with chronic renal parenchymal disease. No hydronephrosis. No renal calculi identified.\nSpleen: Mildly enlarged at 13.2cm. Normal echotexture.\nPancreas: Partially visualised; no focal lesion identified.\nAscites: None.",
                'impression' => "1. Hepatic steatosis.\n2. Cholelithiasis — surgical review recommended.\n3. Bilateral chronic renal parenchymal changes — consistent with known CKD.\n4. Mild splenomegaly — clinical correlation required.",
                'recommendations' => "Surgical consultation for cholelithiasis. Nephrology review for CKD progression.",
            ],
        ];
    }
}
