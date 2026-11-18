<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TenantDefaultCatalogueSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedDocumentCategories();
        $this->seedServiceCategories();
        $this->seedDefaultServices();
    }

    private function seedDocumentCategories(): void
    {
        $categories = [
            ['name' => 'Medical Certificate',   'code' => 'medical_cert',    'is_system' => true,  'sort_order' => 1],
            ['name' => 'Referral Letter',        'code' => 'referral',        'is_system' => true,  'sort_order' => 2],
            ['name' => 'Lab Results',            'code' => 'lab_results',     'is_system' => true,  'sort_order' => 3],
            ['name' => 'Imaging Results',        'code' => 'imaging',         'is_system' => true,  'sort_order' => 4],
            ['name' => 'Insurance Document',     'code' => 'insurance',       'is_system' => true,  'sort_order' => 5],
            ['name' => 'Consent Form',           'code' => 'consent',         'is_system' => true,  'sort_order' => 6],
            ['name' => 'ID / Identification',    'code' => 'identification',  'is_system' => false, 'sort_order' => 7],
            ['name' => 'Other',                  'code' => 'other',           'is_system' => false, 'sort_order' => 99],
        ];

        foreach ($categories as $cat) {
            DB::connection('tenant')->table('document_categories')->updateOrInsert(
                ['code' => $cat['code']],
                array_merge($cat, ['is_active' => true, 'created_at' => now(), 'updated_at' => now()])
            );
        }
    }

    private function seedServiceCategories(): void
    {
        $categories = [
            ['name' => 'Consultation',    'code' => 'consultation',   'sort_order' => 1],
            ['name' => 'Procedure',       'code' => 'procedure',      'sort_order' => 2],
            ['name' => 'Laboratory',      'code' => 'laboratory',     'sort_order' => 3],
            ['name' => 'Imaging',         'code' => 'imaging',        'sort_order' => 4],
            ['name' => 'Medication',      'code' => 'medication',     'sort_order' => 5],
            ['name' => 'Administration',  'code' => 'admin',          'sort_order' => 6],
            ['name' => 'Other',           'code' => 'other_service',  'sort_order' => 99],
        ];

        foreach ($categories as $cat) {
            DB::connection('tenant')->table('service_categories')->updateOrInsert(
                ['code' => $cat['code']],
                array_merge($cat, ['is_active' => true, 'created_at' => now(), 'updated_at' => now()])
            );
        }
    }

    private function seedDefaultServices(): void
    {
        $consultationCategoryId = DB::connection('tenant')
            ->table('service_categories')->where('code', 'consultation')->value('id');

        $labCategoryId = DB::connection('tenant')
            ->table('service_categories')->where('code', 'laboratory')->value('id');

        $services = [
            [
                'category_id' => $consultationCategoryId,
                'code'        => 'CONSULT_GP',
                'name'        => 'General Practitioner Consultation',
                'price'       => 40000, // $400.00 TTD
                'taxable'     => false,
                'is_default'  => true,
                'sort_order'  => 1,
            ],
            [
                'category_id' => $consultationCategoryId,
                'code'        => 'CONSULT_SPEC',
                'name'        => 'Specialist Consultation',
                'price'       => 60000, // $600.00 TTD
                'taxable'     => false,
                'is_default'  => true,
                'sort_order'  => 2,
            ],
            [
                'category_id' => $consultationCategoryId,
                'code'        => 'CONSULT_FOLLOW',
                'name'        => 'Follow-up Consultation',
                'price'       => 25000, // $250.00 TTD
                'taxable'     => false,
                'is_default'  => true,
                'sort_order'  => 3,
            ],
            [
                'category_id' => $labCategoryId,
                'code'        => 'LAB_FBC',
                'name'        => 'Full Blood Count',
                'price'       => 15000, // $150.00 TTD
                'taxable'     => false,
                'is_default'  => true,
                'sort_order'  => 10,
            ],
            [
                'category_id' => $labCategoryId,
                'code'        => 'LAB_CMP',
                'name'        => 'Comprehensive Metabolic Panel',
                'price'       => 25000,
                'taxable'     => false,
                'is_default'  => true,
                'sort_order'  => 11,
            ],
        ];

        foreach ($services as $svc) {
            DB::connection('tenant')->table('services')->updateOrInsert(
                ['code' => $svc['code']],
                array_merge($svc, [
                    'cost'      => 0,
                    'tax_rate'  => 0,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }
    }
}
