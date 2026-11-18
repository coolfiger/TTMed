<?php

namespace Database\Seeders\Tenant;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MedicationSeeder extends Seeder
{
    public function run(): void
    {
        $medications = [
            // Antihypertensives
            ['name' => 'Amlodipine',      'generic_name' => 'amlodipine',       'strength' => '5mg',   'form' => 'tablet',  'route' => 'oral', 'drug_class' => 'Calcium Channel Blocker'],
            ['name' => 'Amlodipine',      'generic_name' => 'amlodipine',       'strength' => '10mg',  'form' => 'tablet',  'route' => 'oral', 'drug_class' => 'Calcium Channel Blocker'],
            ['name' => 'Lisinopril',      'generic_name' => 'lisinopril',       'strength' => '5mg',   'form' => 'tablet',  'route' => 'oral', 'drug_class' => 'ACE Inhibitor'],
            ['name' => 'Lisinopril',      'generic_name' => 'lisinopril',       'strength' => '10mg',  'form' => 'tablet',  'route' => 'oral', 'drug_class' => 'ACE Inhibitor'],
            ['name' => 'Lisinopril',      'generic_name' => 'lisinopril',       'strength' => '20mg',  'form' => 'tablet',  'route' => 'oral', 'drug_class' => 'ACE Inhibitor'],
            ['name' => 'Losartan',        'generic_name' => 'losartan',         'strength' => '50mg',  'form' => 'tablet',  'route' => 'oral', 'drug_class' => 'ARB'],
            ['name' => 'Losartan',        'generic_name' => 'losartan',         'strength' => '100mg', 'form' => 'tablet',  'route' => 'oral', 'drug_class' => 'ARB'],
            ['name' => 'Atenolol',        'generic_name' => 'atenolol',         'strength' => '50mg',  'form' => 'tablet',  'route' => 'oral', 'drug_class' => 'Beta Blocker'],
            ['name' => 'Atenolol',        'generic_name' => 'atenolol',         'strength' => '100mg', 'form' => 'tablet',  'route' => 'oral', 'drug_class' => 'Beta Blocker'],
            ['name' => 'Hydrochlorothiazide', 'generic_name' => 'hydrochlorothiazide', 'strength' => '12.5mg', 'form' => 'tablet', 'route' => 'oral', 'drug_class' => 'Thiazide Diuretic'],
            ['name' => 'Hydrochlorothiazide', 'generic_name' => 'hydrochlorothiazide', 'strength' => '25mg', 'form' => 'tablet', 'route' => 'oral', 'drug_class' => 'Thiazide Diuretic'],
            ['name' => 'Furosemide',      'generic_name' => 'furosemide',       'strength' => '40mg',  'form' => 'tablet',  'route' => 'oral', 'drug_class' => 'Loop Diuretic'],

            // Diabetes
            ['name' => 'Metformin',       'generic_name' => 'metformin',        'strength' => '500mg', 'form' => 'tablet',  'route' => 'oral', 'drug_class' => 'Biguanide'],
            ['name' => 'Metformin',       'generic_name' => 'metformin',        'strength' => '850mg', 'form' => 'tablet',  'route' => 'oral', 'drug_class' => 'Biguanide'],
            ['name' => 'Metformin',       'generic_name' => 'metformin',        'strength' => '1000mg','form' => 'tablet',  'route' => 'oral', 'drug_class' => 'Biguanide'],
            ['name' => 'Glibenclamide',   'generic_name' => 'glibenclamide',    'strength' => '5mg',   'form' => 'tablet',  'route' => 'oral', 'drug_class' => 'Sulfonylurea'],
            ['name' => 'Glipizide',       'generic_name' => 'glipizide',        'strength' => '5mg',   'form' => 'tablet',  'route' => 'oral', 'drug_class' => 'Sulfonylurea'],
            ['name' => 'Sitagliptin',     'generic_name' => 'sitagliptin',      'strength' => '100mg', 'form' => 'tablet',  'route' => 'oral', 'drug_class' => 'DPP-4 Inhibitor'],
            ['name' => 'Empagliflozin',   'generic_name' => 'empagliflozin',    'strength' => '10mg',  'form' => 'tablet',  'route' => 'oral', 'drug_class' => 'SGLT2 Inhibitor'],

            // Statins / Lipids
            ['name' => 'Atorvastatin',    'generic_name' => 'atorvastatin',     'strength' => '10mg',  'form' => 'tablet',  'route' => 'oral', 'drug_class' => 'Statin'],
            ['name' => 'Atorvastatin',    'generic_name' => 'atorvastatin',     'strength' => '20mg',  'form' => 'tablet',  'route' => 'oral', 'drug_class' => 'Statin'],
            ['name' => 'Atorvastatin',    'generic_name' => 'atorvastatin',     'strength' => '40mg',  'form' => 'tablet',  'route' => 'oral', 'drug_class' => 'Statin'],
            ['name' => 'Rosuvastatin',    'generic_name' => 'rosuvastatin',     'strength' => '10mg',  'form' => 'tablet',  'route' => 'oral', 'drug_class' => 'Statin'],
            ['name' => 'Rosuvastatin',    'generic_name' => 'rosuvastatin',     'strength' => '20mg',  'form' => 'tablet',  'route' => 'oral', 'drug_class' => 'Statin'],

            // Analgesics / Anti-inflammatory
            ['name' => 'Paracetamol',     'generic_name' => 'paracetamol',      'strength' => '500mg', 'form' => 'tablet',  'route' => 'oral', 'drug_class' => 'Analgesic'],
            ['name' => 'Paracetamol',     'generic_name' => 'paracetamol',      'strength' => '1000mg','form' => 'tablet',  'route' => 'oral', 'drug_class' => 'Analgesic'],
            ['name' => 'Ibuprofen',       'generic_name' => 'ibuprofen',        'strength' => '200mg', 'form' => 'tablet',  'route' => 'oral', 'drug_class' => 'NSAID'],
            ['name' => 'Ibuprofen',       'generic_name' => 'ibuprofen',        'strength' => '400mg', 'form' => 'tablet',  'route' => 'oral', 'drug_class' => 'NSAID'],
            ['name' => 'Diclofenac',      'generic_name' => 'diclofenac',       'strength' => '50mg',  'form' => 'tablet',  'route' => 'oral', 'drug_class' => 'NSAID'],
            ['name' => 'Naproxen',        'generic_name' => 'naproxen',         'strength' => '250mg', 'form' => 'tablet',  'route' => 'oral', 'drug_class' => 'NSAID'],
            ['name' => 'Naproxen',        'generic_name' => 'naproxen',         'strength' => '500mg', 'form' => 'tablet',  'route' => 'oral', 'drug_class' => 'NSAID'],
            ['name' => 'Aspirin',         'generic_name' => 'aspirin',          'strength' => '75mg',  'form' => 'tablet',  'route' => 'oral', 'drug_class' => 'Antiplatelet'],
            ['name' => 'Aspirin',         'generic_name' => 'aspirin',          'strength' => '300mg', 'form' => 'tablet',  'route' => 'oral', 'drug_class' => 'Antiplatelet'],
            ['name' => 'Colchicine',      'generic_name' => 'colchicine',       'strength' => '0.5mg', 'form' => 'tablet',  'route' => 'oral', 'drug_class' => 'Antigout'],
            ['name' => 'Allopurinol',     'generic_name' => 'allopurinol',      'strength' => '100mg', 'form' => 'tablet',  'route' => 'oral', 'drug_class' => 'Xanthine Oxidase Inhibitor'],
            ['name' => 'Allopurinol',     'generic_name' => 'allopurinol',      'strength' => '300mg', 'form' => 'tablet',  'route' => 'oral', 'drug_class' => 'Xanthine Oxidase Inhibitor'],

            // Antibiotics
            ['name' => 'Amoxicillin',     'generic_name' => 'amoxicillin',      'strength' => '250mg', 'form' => 'capsule', 'route' => 'oral', 'drug_class' => 'Penicillin Antibiotic'],
            ['name' => 'Amoxicillin',     'generic_name' => 'amoxicillin',      'strength' => '500mg', 'form' => 'capsule', 'route' => 'oral', 'drug_class' => 'Penicillin Antibiotic'],
            ['name' => 'Azithromycin',    'generic_name' => 'azithromycin',     'strength' => '250mg', 'form' => 'tablet',  'route' => 'oral', 'drug_class' => 'Macrolide Antibiotic'],
            ['name' => 'Azithromycin',    'generic_name' => 'azithromycin',     'strength' => '500mg', 'form' => 'tablet',  'route' => 'oral', 'drug_class' => 'Macrolide Antibiotic'],
            ['name' => 'Ciprofloxacin',   'generic_name' => 'ciprofloxacin',    'strength' => '250mg', 'form' => 'tablet',  'route' => 'oral', 'drug_class' => 'Fluoroquinolone'],
            ['name' => 'Ciprofloxacin',   'generic_name' => 'ciprofloxacin',    'strength' => '500mg', 'form' => 'tablet',  'route' => 'oral', 'drug_class' => 'Fluoroquinolone'],
            ['name' => 'Co-Amoxiclav',    'generic_name' => 'amoxicillin/clavulanate', 'strength' => '375mg', 'form' => 'tablet', 'route' => 'oral', 'drug_class' => 'Beta-Lactam Antibiotic'],
            ['name' => 'Co-Amoxiclav',    'generic_name' => 'amoxicillin/clavulanate', 'strength' => '625mg', 'form' => 'tablet', 'route' => 'oral', 'drug_class' => 'Beta-Lactam Antibiotic'],
            ['name' => 'Metronidazole',   'generic_name' => 'metronidazole',    'strength' => '200mg', 'form' => 'tablet',  'route' => 'oral', 'drug_class' => 'Nitroimidazole Antibiotic'],
            ['name' => 'Metronidazole',   'generic_name' => 'metronidazole',    'strength' => '400mg', 'form' => 'tablet',  'route' => 'oral', 'drug_class' => 'Nitroimidazole Antibiotic'],
            ['name' => 'Doxycycline',     'generic_name' => 'doxycycline',      'strength' => '100mg', 'form' => 'capsule', 'route' => 'oral', 'drug_class' => 'Tetracycline Antibiotic'],
            ['name' => 'Nitrofurantoin',  'generic_name' => 'nitrofurantoin',   'strength' => '50mg',  'form' => 'capsule', 'route' => 'oral', 'drug_class' => 'Urinary Antibiotic'],
            ['name' => 'Nitrofurantoin',  'generic_name' => 'nitrofurantoin',   'strength' => '100mg', 'form' => 'capsule', 'route' => 'oral', 'drug_class' => 'Urinary Antibiotic'],

            // GI
            ['name' => 'Omeprazole',      'generic_name' => 'omeprazole',       'strength' => '20mg',  'form' => 'capsule', 'route' => 'oral', 'drug_class' => 'Proton Pump Inhibitor'],
            ['name' => 'Omeprazole',      'generic_name' => 'omeprazole',       'strength' => '40mg',  'form' => 'capsule', 'route' => 'oral', 'drug_class' => 'Proton Pump Inhibitor'],
            ['name' => 'Pantoprazole',    'generic_name' => 'pantoprazole',     'strength' => '40mg',  'form' => 'tablet',  'route' => 'oral', 'drug_class' => 'Proton Pump Inhibitor'],
            ['name' => 'Metoclopramide',  'generic_name' => 'metoclopramide',   'strength' => '10mg',  'form' => 'tablet',  'route' => 'oral', 'drug_class' => 'Prokinetic'],
            ['name' => 'Ondansetron',     'generic_name' => 'ondansetron',      'strength' => '4mg',   'form' => 'tablet',  'route' => 'oral', 'drug_class' => '5-HT3 Antagonist'],
            ['name' => 'Ondansetron',     'generic_name' => 'ondansetron',      'strength' => '8mg',   'form' => 'tablet',  'route' => 'oral', 'drug_class' => '5-HT3 Antagonist'],

            // Respiratory
            ['name' => 'Salbutamol Inhaler', 'generic_name' => 'salbutamol',   'strength' => '100mcg/dose', 'form' => 'inhaler', 'route' => 'inhalation', 'drug_class' => 'Short-Acting Beta Agonist'],
            ['name' => 'Beclometasone Inhaler', 'generic_name' => 'beclometasone', 'strength' => '100mcg/dose', 'form' => 'inhaler', 'route' => 'inhalation', 'drug_class' => 'Inhaled Corticosteroid'],
            ['name' => 'Prednisolone',    'generic_name' => 'prednisolone',     'strength' => '5mg',   'form' => 'tablet',  'route' => 'oral', 'drug_class' => 'Corticosteroid'],
            ['name' => 'Prednisolone',    'generic_name' => 'prednisolone',     'strength' => '25mg',  'form' => 'tablet',  'route' => 'oral', 'drug_class' => 'Corticosteroid'],
            ['name' => 'Cetirizine',      'generic_name' => 'cetirizine',       'strength' => '10mg',  'form' => 'tablet',  'route' => 'oral', 'drug_class' => 'Antihistamine'],
            ['name' => 'Loratadine',      'generic_name' => 'loratadine',       'strength' => '10mg',  'form' => 'tablet',  'route' => 'oral', 'drug_class' => 'Antihistamine'],

            // Mental health
            ['name' => 'Sertraline',      'generic_name' => 'sertraline',       'strength' => '50mg',  'form' => 'tablet',  'route' => 'oral', 'drug_class' => 'SSRI'],
            ['name' => 'Sertraline',      'generic_name' => 'sertraline',       'strength' => '100mg', 'form' => 'tablet',  'route' => 'oral', 'drug_class' => 'SSRI'],
            ['name' => 'Escitalopram',    'generic_name' => 'escitalopram',     'strength' => '10mg',  'form' => 'tablet',  'route' => 'oral', 'drug_class' => 'SSRI'],
            ['name' => 'Diazepam',        'generic_name' => 'diazepam',         'strength' => '2mg',   'form' => 'tablet',  'route' => 'oral', 'drug_class' => 'Benzodiazepine'],
            ['name' => 'Diazepam',        'generic_name' => 'diazepam',         'strength' => '5mg',   'form' => 'tablet',  'route' => 'oral', 'drug_class' => 'Benzodiazepine'],

            // Thyroid / Hormones
            ['name' => 'Levothyroxine',   'generic_name' => 'levothyroxine',    'strength' => '50mcg', 'form' => 'tablet',  'route' => 'oral', 'drug_class' => 'Thyroid Hormone'],
            ['name' => 'Levothyroxine',   'generic_name' => 'levothyroxine',    'strength' => '100mcg','form' => 'tablet',  'route' => 'oral', 'drug_class' => 'Thyroid Hormone'],

            // Vitamins / Supplements
            ['name' => 'Folic Acid',      'generic_name' => 'folic acid',       'strength' => '400mcg','form' => 'tablet',  'route' => 'oral', 'drug_class' => 'Vitamin'],
            ['name' => 'Folic Acid',      'generic_name' => 'folic acid',       'strength' => '5mg',   'form' => 'tablet',  'route' => 'oral', 'drug_class' => 'Vitamin'],
            ['name' => 'Vitamin D3',      'generic_name' => 'cholecalciferol',  'strength' => '400IU', 'form' => 'tablet',  'route' => 'oral', 'drug_class' => 'Vitamin'],
            ['name' => 'Vitamin D3',      'generic_name' => 'cholecalciferol',  'strength' => '1000IU','form' => 'tablet',  'route' => 'oral', 'drug_class' => 'Vitamin'],
            ['name' => 'Ferrous Sulphate','generic_name' => 'ferrous sulphate', 'strength' => '200mg', 'form' => 'tablet',  'route' => 'oral', 'drug_class' => 'Iron Supplement'],
        ];

        $rows = array_map(fn ($m) => array_merge([
            'brand_names'  => json_encode([]),
            'strength_unit'=> null,
            'description'  => null,
            'is_active'    => true,
            'is_custom'    => false,
            'created_at'   => now(),
            'updated_at'   => now(),
        ], $m), $medications);

        foreach (array_chunk($rows, 50) as $chunk) {
            DB::connection('tenant')->table('medications')->upsert(
                $chunk,
                ['name', 'strength', 'form'],
                ['generic_name', 'route', 'drug_class', 'is_active', 'updated_at']
            );
        }
    }
}
