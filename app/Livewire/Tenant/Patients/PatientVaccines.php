<?php

namespace App\Livewire\Tenant\Patients;

use App\Models\Tenant\PatientVaccination;
use App\Models\Tenant\VaccineCatalog;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class PatientVaccines extends Component
{
    public int   $patientId;
    public ?string $dob = null;      // passed from parent as ISO date string
    public int   $branchId;
    public ?int  $encounterId = null;

    // Record form
    public bool   $showForm             = false;
    public ?int   $formVaccineId        = null;
    public ?int   $formDoseDefinitionId = null;
    public int    $formDoseNumber       = 1;
    public string $formDate             = '';
    public string $formLotNumber        = '';
    public string $formSite             = '';
    public string $formRoute            = 'IM';
    public string $formNotes            = '';

    public function mount(int $patientId, int $branchId, ?string $dob = null, ?int $encounterId = null): void
    {
        $this->patientId   = $patientId;
        $this->branchId    = $branchId;
        $this->dob         = $dob;
        $this->encounterId = $encounterId;
        $this->formDate    = now()->toDateString();
    }

    public function openRecord(int $vaccineId, int $doseDefinitionId, int $doseNumber): void
    {
        $this->formVaccineId        = $vaccineId;
        $this->formDoseDefinitionId = $doseDefinitionId;
        $this->formDoseNumber       = $doseNumber;
        $this->formDate             = now()->toDateString();
        $this->formLotNumber        = '';
        $this->formSite             = '';
        $this->formRoute            = 'IM';
        $this->formNotes            = '';
        $this->showForm             = true;
        $this->resetValidation();
    }

    public function saveVaccination(): void
    {
        $this->validate([
            'formDate'      => 'required|date|before_or_equal:today',
            'formLotNumber' => 'nullable|string|max:60',
            'formSite'      => 'nullable|string|max:60',
            'formRoute'     => 'nullable|string|max:30',
            'formNotes'     => 'nullable|string|max:500',
        ]);

        $user = Auth::guard('tenant')->user();

        PatientVaccination::on('tenant')->create([
            'patient_id'               => $this->patientId,
            'vaccine_catalog_id'       => $this->formVaccineId,
            'vaccine_dose_definition_id' => $this->formDoseDefinitionId,
            'dose_number'              => $this->formDoseNumber,
            'encounter_id'             => $this->encounterId,
            'branch_id'                => $this->branchId,
            'administered_by'          => $user->id,
            'administered_on'          => $this->formDate,
            'lot_number'               => $this->formLotNumber ?: null,
            'site'                     => $this->formSite ?: null,
            'route'                    => $this->formRoute ?: null,
            'notes'                    => $this->formNotes ?: null,
        ]);

        $this->showForm = false;
        session()->flash('vaccine_saved', 'Vaccination recorded.');
    }

    public function cancelForm(): void
    {
        $this->showForm = false;
        $this->resetValidation();
    }

    public function render()
    {
        $dob = $this->dob ? Carbon::parse($this->dob) : null;

        // All active vaccines with their dose definitions
        $vaccines = VaccineCatalog::on('tenant')
            ->where('is_active', true)
            ->with('doseDefinitions')
            ->orderBy('sort_order')
            ->get();

        // All vaccinations recorded for this patient
        $given = PatientVaccination::on('tenant')
            ->where('patient_id', $this->patientId)
            ->with(['administeredBy', 'vaccine'])
            ->orderBy('administered_on')
            ->get()
            ->groupBy('vaccine_catalog_id');

        // Build schedule rows
        $schedule = [];
        foreach ($vaccines as $vaccine) {
            $givenForVaccine = $given->get($vaccine->id, collect());

            foreach ($vaccine->doseDefinitions as $dose) {
                $givenDose = $givenForVaccine->firstWhere('dose_number', $dose->dose_number);

                // Calculate due date
                if ($dose->reference === 'dob') {
                    $dueDate = $dob?->copy()->addWeeks($dose->reference_weeks);
                } else {
                    $prevDose = $givenForVaccine->firstWhere('dose_number', $dose->dose_number - 1);
                    $dueDate  = $prevDose
                        ? Carbon::parse($prevDose->administered_on)->addWeeks($dose->reference_weeks)
                        : null;
                }

                $status = 'pending';
                if ($givenDose) {
                    $status = 'given';
                } elseif ($dueDate && $dueDate->isPast()) {
                    $status = 'overdue';
                } elseif ($dueDate) {
                    $status = 'upcoming';
                }

                // Block recording if previous dose not yet given (for interval-based)
                $canRecord = true;
                if ($dose->reference === 'prev_dose' && $dose->dose_number > 1) {
                    $prevGiven = $givenForVaccine->firstWhere('dose_number', $dose->dose_number - 1);
                    $canRecord = $prevGiven !== null;
                }

                $schedule[] = [
                    'vaccine'          => $vaccine,
                    'dose'             => $dose,
                    'given'            => $givenDose,
                    'due_date'         => $dueDate,
                    'status'           => $status,
                    'can_record'       => $canRecord && !$givenDose,
                ];
            }
        }

        return view('livewire.tenant.patients.patient-vaccines', [
            'schedule' => $schedule,
        ]);
    }
}
