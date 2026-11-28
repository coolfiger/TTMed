<?php

namespace Database\Seeders\Tenant;

use App\Models\Tenant\VaccineCatalog;
use App\Models\Tenant\VaccineDoseDefinition;
use Illuminate\Database\Seeder;

/**
 * Trinidad & Tobago EPI (Expanded Programme on Immunization) schedule.
 * Source: PAHO Caribbean / T&T Ministry of Health immunization programme.
 */
class VaccineCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $vaccines = [
            [
                'name'           => 'BCG',
                'short_name'     => 'BCG',
                'description'    => 'Bacillus Calmette-Guérin vaccine',
                'disease_target' => 'Tuberculosis',
                'type'           => 'single_dose',
                'sort_order'     => 10,
                'doses'          => [
                    ['dose_number' => 1, 'dose_label' => 'Single dose', 'reference' => 'dob', 'reference_weeks' => 0, 'min_weeks' => 0, 'max_weeks' => 4],
                ],
            ],
            [
                'name'           => 'Hepatitis B (Birth Dose)',
                'short_name'     => 'HepB',
                'description'    => 'Hepatitis B monovalent birth dose',
                'disease_target' => 'Hepatitis B',
                'type'           => 'single_dose',
                'sort_order'     => 20,
                'doses'          => [
                    ['dose_number' => 1, 'dose_label' => 'Birth dose', 'reference' => 'dob', 'reference_weeks' => 0, 'min_weeks' => 0, 'max_weeks' => 1, 'notes' => 'Give within 24 hours of birth'],
                ],
            ],
            [
                'name'           => 'Pentavalent (DPT-HepB-Hib)',
                'short_name'     => 'Pentavalent',
                'description'    => 'Combined vaccine: Diphtheria, Pertussis, Tetanus, Hepatitis B, Haemophilus influenzae type b',
                'disease_target' => 'Diphtheria, Pertussis, Tetanus, Hepatitis B, Hib',
                'type'           => 'multi_dose',
                'sort_order'     => 30,
                'doses'          => [
                    ['dose_number' => 1, 'dose_label' => 'Dose 1', 'reference' => 'dob', 'reference_weeks' => 6,  'min_weeks' => 6,  'max_weeks' => 10],
                    ['dose_number' => 2, 'dose_label' => 'Dose 2', 'reference' => 'dob', 'reference_weeks' => 10, 'min_weeks' => 10, 'max_weeks' => 14],
                    ['dose_number' => 3, 'dose_label' => 'Dose 3', 'reference' => 'dob', 'reference_weeks' => 14, 'min_weeks' => 14, 'max_weeks' => 24],
                ],
            ],
            [
                'name'           => 'OPV (Oral Polio Vaccine)',
                'short_name'     => 'OPV',
                'description'    => 'Oral poliovirus vaccine (trivalent)',
                'disease_target' => 'Poliomyelitis',
                'type'           => 'multi_dose',
                'sort_order'     => 40,
                'doses'          => [
                    ['dose_number' => 1, 'dose_label' => 'Dose 1',  'reference' => 'dob', 'reference_weeks' => 6,  'min_weeks' => 6,  'max_weeks' => 10],
                    ['dose_number' => 2, 'dose_label' => 'Dose 2',  'reference' => 'dob', 'reference_weeks' => 10, 'min_weeks' => 10, 'max_weeks' => 14],
                    ['dose_number' => 3, 'dose_label' => 'Dose 3',  'reference' => 'dob', 'reference_weeks' => 14, 'min_weeks' => 14, 'max_weeks' => 24],
                    ['dose_number' => 4, 'dose_label' => 'Booster', 'reference' => 'dob', 'reference_weeks' => 78, 'min_weeks' => 72, 'max_weeks' => 104, 'notes' => '18 months booster'],
                ],
            ],
            [
                'name'           => 'Rotavirus',
                'short_name'     => 'Rota',
                'description'    => 'Rotavirus oral vaccine (Rotarix or RotaTeq)',
                'disease_target' => 'Rotavirus gastroenteritis',
                'type'           => 'multi_dose',
                'sort_order'     => 50,
                'doses'          => [
                    ['dose_number' => 1, 'dose_label' => 'Dose 1', 'reference' => 'dob', 'reference_weeks' => 6,  'min_weeks' => 6,  'max_weeks' => 10,  'notes' => 'Do not start after 15 weeks'],
                    ['dose_number' => 2, 'dose_label' => 'Dose 2', 'reference' => 'dob', 'reference_weeks' => 10, 'min_weeks' => 10, 'max_weeks' => 24,  'notes' => 'Complete before 24 weeks'],
                ],
            ],
            [
                'name'           => 'PCV-13 (Pneumococcal)',
                'short_name'     => 'PCV',
                'description'    => 'Pneumococcal Conjugate Vaccine (13-valent)',
                'disease_target' => 'Streptococcus pneumoniae (pneumococcal disease)',
                'type'           => 'multi_dose',
                'sort_order'     => 60,
                'doses'          => [
                    ['dose_number' => 1, 'dose_label' => 'Dose 1', 'reference' => 'dob', 'reference_weeks' => 6,  'min_weeks' => 6,  'max_weeks' => 10],
                    ['dose_number' => 2, 'dose_label' => 'Dose 2', 'reference' => 'dob', 'reference_weeks' => 10, 'min_weeks' => 10, 'max_weeks' => 14],
                    ['dose_number' => 3, 'dose_label' => 'Dose 3', 'reference' => 'dob', 'reference_weeks' => 14, 'min_weeks' => 14, 'max_weeks' => 24],
                ],
            ],
            [
                'name'           => 'MMR (Measles-Mumps-Rubella)',
                'short_name'     => 'MMR',
                'description'    => 'Combined measles, mumps, and rubella vaccine',
                'disease_target' => 'Measles, Mumps, Rubella',
                'type'           => 'multi_dose',
                'sort_order'     => 70,
                'doses'          => [
                    ['dose_number' => 1, 'dose_label' => 'Dose 1', 'reference' => 'dob', 'reference_weeks' => 39, 'min_weeks' => 36, 'max_weeks' => 52,  'notes' => '9 months'],
                    ['dose_number' => 2, 'dose_label' => 'Dose 2', 'reference' => 'dob', 'reference_weeks' => 78, 'min_weeks' => 72, 'max_weeks' => 104, 'notes' => '18 months booster'],
                ],
            ],
            [
                'name'           => 'Yellow Fever',
                'short_name'     => 'YF',
                'description'    => 'Yellow fever live attenuated vaccine (17D strain)',
                'disease_target' => 'Yellow Fever',
                'type'           => 'single_dose',
                'sort_order'     => 80,
                'doses'          => [
                    ['dose_number' => 1, 'dose_label' => 'Single dose', 'reference' => 'dob', 'reference_weeks' => 39, 'min_weeks' => 36, 'notes' => '9 months — single lifetime dose'],
                ],
            ],
            [
                'name'           => 'Varicella',
                'short_name'     => 'Var',
                'description'    => 'Varicella (chickenpox) live attenuated vaccine',
                'disease_target' => 'Varicella (Chickenpox)',
                'type'           => 'single_dose',
                'sort_order'     => 90,
                'doses'          => [
                    ['dose_number' => 1, 'dose_label' => 'Single dose', 'reference' => 'dob', 'reference_weeks' => 52, 'min_weeks' => 52, 'max_weeks' => 78, 'notes' => '12 months'],
                ],
            ],
            [
                'name'           => 'DPT Booster',
                'short_name'     => 'DPT',
                'description'    => 'Diphtheria-Pertussis-Tetanus booster for pre-school children',
                'disease_target' => 'Diphtheria, Pertussis, Tetanus',
                'type'           => 'single_dose',
                'sort_order'     => 100,
                'doses'          => [
                    ['dose_number' => 1, 'dose_label' => 'Pre-school booster', 'reference' => 'dob', 'reference_weeks' => 208, 'min_weeks' => 195, 'max_weeks' => 260, 'notes' => '4–5 years'],
                ],
            ],
            [
                'name'           => 'HPV (Human Papillomavirus)',
                'short_name'     => 'HPV',
                'description'    => 'HPV vaccine (Gardasil-9 or Cervarix) for pre-adolescents',
                'disease_target' => 'Human Papillomavirus (cervical cancer prevention)',
                'type'           => 'multi_dose',
                'sort_order'     => 110,
                'doses'          => [
                    ['dose_number' => 1, 'dose_label' => 'Dose 1',  'reference' => 'dob',       'reference_weeks' => 468, 'min_weeks' => 468, 'notes' => '~9–10 years'],
                    ['dose_number' => 2, 'dose_label' => 'Dose 2',  'reference' => 'prev_dose',  'reference_weeks' => 26,  'min_weeks' => 24,  'notes' => '6 months after Dose 1'],
                ],
            ],
            [
                'name'           => 'Td (Tetanus-Diphtheria)',
                'short_name'     => 'Td',
                'description'    => 'Tetanus and reduced diphtheria adult booster',
                'disease_target' => 'Tetanus, Diphtheria',
                'type'           => 'multi_dose',
                'sort_order'     => 120,
                'doses'          => [
                    ['dose_number' => 1, 'dose_label' => 'Dose 1',  'reference' => 'dob',       'reference_weeks' => 0,  'notes' => 'Given at first contact (adult/pregnancy)'],
                    ['dose_number' => 2, 'dose_label' => 'Dose 2',  'reference' => 'prev_dose', 'reference_weeks' => 4,  'notes' => '4 weeks after Dose 1'],
                    ['dose_number' => 3, 'dose_label' => 'Dose 3',  'reference' => 'prev_dose', 'reference_weeks' => 26, 'notes' => '6 months after Dose 2'],
                    ['dose_number' => 4, 'dose_label' => 'Dose 4',  'reference' => 'prev_dose', 'reference_weeks' => 52, 'notes' => '1 year after Dose 3'],
                    ['dose_number' => 5, 'dose_label' => 'Dose 5',  'reference' => 'prev_dose', 'reference_weeks' => 52, 'notes' => '1 year after Dose 4'],
                ],
            ],
            [
                'name'           => 'COVID-19',
                'short_name'     => 'COVID',
                'description'    => 'COVID-19 mRNA or viral vector vaccine (AstraZeneca / Pfizer / Moderna)',
                'disease_target' => 'SARS-CoV-2 (COVID-19)',
                'type'           => 'multi_dose',
                'sort_order'     => 130,
                'doses'          => [
                    ['dose_number' => 1, 'dose_label' => 'Dose 1',  'reference' => 'dob',       'reference_weeks' => 0, 'notes' => 'Primary dose'],
                    ['dose_number' => 2, 'dose_label' => 'Dose 2',  'reference' => 'prev_dose', 'reference_weeks' => 4, 'notes' => '4 weeks after Dose 1 (8 weeks for AstraZeneca)'],
                    ['dose_number' => 3, 'dose_label' => 'Booster', 'reference' => 'prev_dose', 'reference_weeks' => 26, 'notes' => 'Booster ~6 months after Dose 2'],
                ],
            ],
        ];

        foreach ($vaccines as $data) {
            $doses = $data['doses'];
            unset($data['doses']);

            $vaccine = VaccineCatalog::on('tenant')->firstOrCreate(
                ['name' => $data['name']],
                $data
            );

            foreach ($doses as $dose) {
                VaccineDoseDefinition::on('tenant')->firstOrCreate(
                    ['vaccine_catalog_id' => $vaccine->id, 'dose_number' => $dose['dose_number']],
                    $dose
                );
            }
        }
    }
}
