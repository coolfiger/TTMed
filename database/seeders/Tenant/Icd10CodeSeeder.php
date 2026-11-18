<?php

namespace Database\Seeders\Tenant;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class Icd10CodeSeeder extends Seeder
{
    public function run(): void
    {
        $codes = [
            // Cardiovascular
            ['code' => 'I10',   'description' => 'Essential (primary) hypertension',                   'category_code' => 'I10-I16', 'category_description' => 'Hypertensive diseases'],
            ['code' => 'I11.0', 'description' => 'Hypertensive heart disease with heart failure',       'category_code' => 'I10-I16', 'category_description' => 'Hypertensive diseases'],
            ['code' => 'I11.9', 'description' => 'Hypertensive heart disease without heart failure',    'category_code' => 'I10-I16', 'category_description' => 'Hypertensive diseases'],
            ['code' => 'I12.9', 'description' => 'Hypertensive chronic kidney disease, unspecified',    'category_code' => 'I10-I16', 'category_description' => 'Hypertensive diseases'],
            ['code' => 'I13.10','description' => 'Hypertensive heart and chronic kidney disease',       'category_code' => 'I10-I16', 'category_description' => 'Hypertensive diseases'],
            ['code' => 'I20.9', 'description' => 'Angina pectoris, unspecified',                        'category_code' => 'I20-I25', 'category_description' => 'Ischaemic heart diseases'],
            ['code' => 'I21.9', 'description' => 'Acute myocardial infarction, unspecified',            'category_code' => 'I20-I25', 'category_description' => 'Ischaemic heart diseases'],
            ['code' => 'I25.10','description' => 'Atherosclerotic heart disease of native coronary artery', 'category_code' => 'I20-I25', 'category_description' => 'Ischaemic heart diseases'],
            ['code' => 'I50.9', 'description' => 'Heart failure, unspecified',                          'category_code' => 'I50',     'category_description' => 'Heart failure'],
            ['code' => 'I63.9', 'description' => 'Cerebral infarction, unspecified',                    'category_code' => 'I63',     'category_description' => 'Cerebral infarction'],
            ['code' => 'I64',   'description' => 'Stroke, not specified as haemorrhage or infarction',  'category_code' => 'I60-I69', 'category_description' => 'Cerebrovascular diseases'],

            // Endocrine / Diabetes
            ['code' => 'E11.9', 'description' => 'Type 2 diabetes mellitus without complications',      'category_code' => 'E11',     'category_description' => 'Type 2 diabetes mellitus'],
            ['code' => 'E11.65','description' => 'Type 2 diabetes mellitus with hyperglycaemia',        'category_code' => 'E11',     'category_description' => 'Type 2 diabetes mellitus'],
            ['code' => 'E11.40','description' => 'Type 2 diabetes with diabetic neuropathy, unspecified','category_code' => 'E11',   'category_description' => 'Type 2 diabetes mellitus'],
            ['code' => 'E11.21','description' => 'Type 2 diabetes with diabetic nephropathy',           'category_code' => 'E11',     'category_description' => 'Type 2 diabetes mellitus'],
            ['code' => 'E11.311','description' => 'Type 2 diabetes with mild nonproliferative diabetic retinopathy', 'category_code' => 'E11', 'category_description' => 'Type 2 diabetes mellitus'],
            ['code' => 'E10.9', 'description' => 'Type 1 diabetes mellitus without complications',      'category_code' => 'E10',     'category_description' => 'Type 1 diabetes mellitus'],
            ['code' => 'E78.5', 'description' => 'Hyperlipidaemia, unspecified',                        'category_code' => 'E78',     'category_description' => 'Disorders of lipoprotein metabolism'],
            ['code' => 'E78.00','description' => 'Pure hypercholesterolaemia, unspecified',             'category_code' => 'E78',     'category_description' => 'Disorders of lipoprotein metabolism'],
            ['code' => 'E11.00','description' => 'Type 2 diabetes with hyperosmolarity without coma',   'category_code' => 'E11',     'category_description' => 'Type 2 diabetes mellitus'],
            ['code' => 'E03.9', 'description' => 'Hypothyroidism, unspecified',                         'category_code' => 'E03',     'category_description' => 'Other hypothyroidism'],
            ['code' => 'E05.90','description' => 'Thyrotoxicosis, unspecified, without thyrotoxic crisis','category_code' => 'E05',   'category_description' => 'Thyrotoxicosis'],
            ['code' => 'E66.01','description' => 'Morbid (severe) obesity due to excess calories',       'category_code' => 'E66',    'category_description' => 'Obesity'],
            ['code' => 'E66.9', 'description' => 'Obesity, unspecified',                                 'category_code' => 'E66',    'category_description' => 'Obesity'],

            // Respiratory
            ['code' => 'J06.9', 'description' => 'Acute upper respiratory infection, unspecified',      'category_code' => 'J00-J06', 'category_description' => 'Acute upper respiratory infections'],
            ['code' => 'J18.9', 'description' => 'Pneumonia, unspecified organism',                     'category_code' => 'J18',     'category_description' => 'Pneumonia'],
            ['code' => 'J45.909','description' => 'Unspecified asthma, uncomplicated',                  'category_code' => 'J45',     'category_description' => 'Asthma'],
            ['code' => 'J44.1', 'description' => 'Chronic obstructive pulmonary disease with acute exacerbation', 'category_code' => 'J44', 'category_description' => 'Other COPD'],
            ['code' => 'J44.0', 'description' => 'COPD with acute lower respiratory infection',         'category_code' => 'J44',     'category_description' => 'Other COPD'],

            // Gastrointestinal
            ['code' => 'K21.0', 'description' => 'Gastroesophageal reflux disease with oesophagitis',   'category_code' => 'K21',     'category_description' => 'GERD'],
            ['code' => 'K21.9', 'description' => 'Gastroesophageal reflux disease without oesophagitis','category_code' => 'K21',     'category_description' => 'GERD'],
            ['code' => 'K29.70','description' => 'Gastritis, unspecified, without bleeding',             'category_code' => 'K29',     'category_description' => 'Gastritis and duodenitis'],
            ['code' => 'K57.30','description' => 'Diverticulosis of large intestine without perforation','category_code' => 'K57',    'category_description' => 'Diverticular disease of intestine'],
            ['code' => 'K74.60','description' => 'Unspecified cirrhosis of liver',                       'category_code' => 'K74',     'category_description' => 'Fibrosis and cirrhosis of liver'],

            // Musculoskeletal
            ['code' => 'M10.00','description' => 'Idiopathic gout, unspecified site',                   'category_code' => 'M10',     'category_description' => 'Gout'],
            ['code' => 'M10.9', 'description' => 'Gout, unspecified',                                   'category_code' => 'M10',     'category_description' => 'Gout'],
            ['code' => 'M19.90','description' => 'Unspecified osteoarthritis, unspecified site',         'category_code' => 'M15-M19', 'category_description' => 'Osteoarthritis'],
            ['code' => 'M54.5', 'description' => 'Low back pain',                                        'category_code' => 'M54',     'category_description' => 'Dorsalgia'],
            ['code' => 'M79.3', 'description' => 'Panniculitis, unspecified',                            'category_code' => 'M79',     'category_description' => 'Other soft tissue disorders'],

            // Renal
            ['code' => 'N18.3', 'description' => 'Chronic kidney disease, stage 3 (moderate)',          'category_code' => 'N18',     'category_description' => 'Chronic kidney disease'],
            ['code' => 'N18.4', 'description' => 'Chronic kidney disease, stage 4 (severe)',            'category_code' => 'N18',     'category_description' => 'Chronic kidney disease'],
            ['code' => 'N18.5', 'description' => 'Chronic kidney disease, stage 5',                     'category_code' => 'N18',     'category_description' => 'Chronic kidney disease'],
            ['code' => 'N39.0', 'description' => 'Urinary tract infection, site not specified',         'category_code' => 'N39',     'category_description' => 'Other disorders of urinary system'],

            // Infectious (tropical / T&T relevant)
            ['code' => 'A90',   'description' => 'Dengue fever [classical dengue]',                     'category_code' => 'A90-A99', 'category_description' => 'Viral haemorrhagic fevers'],
            ['code' => 'A91',   'description' => 'Dengue haemorrhagic fever',                           'category_code' => 'A90-A99', 'category_description' => 'Viral haemorrhagic fevers'],
            ['code' => 'B50.9', 'description' => 'Plasmodium falciparum malaria, unspecified',          'category_code' => 'B50-B64', 'category_description' => 'Protozoal diseases'],
            ['code' => 'A09',   'description' => 'Other and unspecified gastroenteritis and colitis of infectious origin', 'category_code' => 'A09', 'category_description' => 'Gastroenteritis'],
            ['code' => 'B34.9', 'description' => 'Viral infection, unspecified',                        'category_code' => 'B34',     'category_description' => 'Viral infection of unspecified site'],
            ['code' => 'U07.1', 'description' => 'COVID-19, virus identified',                          'category_code' => 'U07',     'category_description' => 'Emergency use of U07'],

            // Mental health
            ['code' => 'F32.9', 'description' => 'Major depressive disorder, single episode, unspecified', 'category_code' => 'F32', 'category_description' => 'Depressive episode'],
            ['code' => 'F41.1', 'description' => 'Generalized anxiety disorder',                        'category_code' => 'F41',     'category_description' => 'Other anxiety disorders'],
            ['code' => 'F43.10','description' => 'Post-traumatic stress disorder, unspecified',          'category_code' => 'F43',     'category_description' => 'Reaction to severe stress'],

            // Symptoms / General
            ['code' => 'R51',   'description' => 'Headache',                                            'category_code' => 'R51',     'category_description' => 'Headache'],
            ['code' => 'R05',   'description' => 'Cough',                                               'category_code' => 'R05',     'category_description' => 'Cough'],
            ['code' => 'R07.9', 'description' => 'Chest pain, unspecified',                             'category_code' => 'R07',     'category_description' => 'Pain in throat and chest'],
            ['code' => 'R10.9', 'description' => 'Unspecified abdominal pain',                          'category_code' => 'R10',     'category_description' => 'Abdominal and pelvic pain'],
            ['code' => 'R53.83','description' => 'Other fatigue',                                        'category_code' => 'R53',     'category_description' => 'Malaise and fatigue'],
            ['code' => 'R00.0', 'description' => 'Tachycardia, unspecified',                             'category_code' => 'R00',     'category_description' => 'Abnormalities of heart beat'],
            ['code' => 'R42',   'description' => 'Dizziness and giddiness',                             'category_code' => 'R42',     'category_description' => 'Dizziness and giddiness'],
            ['code' => 'R55',   'description' => 'Syncope and collapse',                                 'category_code' => 'R55',     'category_description' => 'Syncope and collapse'],
        ];

        $now = now();
        $rows = array_map(fn ($c) => array_merge($c, ['is_active' => true, 'created_at' => $now, 'updated_at' => $now]), $codes);

        // Upsert to allow re-running safely
        DB::connection('tenant')->table('icd10_codes')->upsert(
            $rows,
            ['code'],
            ['description', 'category_code', 'category_description', 'is_active', 'updated_at']
        );
    }
}
