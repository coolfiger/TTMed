<?php

namespace App\Livewire\Tenant\Prescriptions;

use App\Models\Tenant\Medication;
use App\Models\Tenant\PatientAllergy;
use App\Models\Tenant\Prescription;
use App\Models\Tenant\PrescriptionItem;
use App\Services\PrescriptionService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class PrescriptionForm extends Component
{
    public int    $encounterId  = 0;
    public int    $patientId    = 0;
    public int    $doctorId     = 0;
    public string $patientName  = '';
    public bool   $isIssued     = false;

    // Medication search
    public string $medSearch    = '';
    public array  $medResults   = [];
    public ?int   $selectedMedId   = null;
    public string $selectedMedName = '';
    public string $selectedStrength = '';
    public string $selectedForm     = '';
    public string $selectedRoute    = '';

    // Item form
    public bool   $showItemForm  = false;
    public string $dose          = '';
    public string $frequency     = '';
    public string $duration      = '';
    public string $durationUnit  = 'days';
    public string $quantity      = '';
    public string $instructions  = '';
    public int    $refills       = 0;

    // Allergy state
    public array  $allergyWarnings = [];
    public bool   $allergyChecked  = false;

    // UI
    public string $successMessage = '';
    public string $errorMessage   = '';

    protected PrescriptionService $prescriptionService;

    public function boot(PrescriptionService $prescriptionService): void
    {
        $this->prescriptionService = $prescriptionService;
    }

    public function mount(int $encounterId): void
    {
        $this->checkPermission('prescriptions.create');

        $this->encounterId = $encounterId;

        $encounter = \App\Models\Tenant\Encounter::on('tenant')
            ->with('patient')
            ->findOrFail($encounterId);

        $this->patientId   = $encounter->patient_id;
        $this->patientName = $encounter->patient?->full_name ?? '—';
        $this->doctorId    = Auth::guard('tenant')->id();

        $prescription = Prescription::on('tenant')
            ->where('encounter_id', $encounterId)
            ->first();

        $this->isIssued = $prescription?->isIssued() ?? false;
    }

    // -------------------------------------------------------------------------
    // Medication search
    // -------------------------------------------------------------------------

    public function searchMedications(): void
    {
        if (strlen(trim($this->medSearch)) < 2) {
            $this->medResults = [];
            return;
        }

        $this->medResults = Medication::on('tenant')
            ->active()
            ->search($this->medSearch)
            ->limit(10)
            ->get()
            ->map(fn ($m) => [
                'id'       => $m->id,
                'name'     => $m->name,
                'strength' => $m->strength,
                'form'     => $m->form,
                'route'    => $m->route,
                'display'  => $m->display,
            ])
            ->toArray();
    }

    public function selectMedication(int $id): void
    {
        $med = Medication::on('tenant')->find($id);
        if (!$med) return;

        $this->selectedMedId   = $med->id;
        $this->selectedMedName = $med->name;
        $this->selectedStrength = $med->strength ?? '';
        $this->selectedForm     = $med->form ?? '';
        $this->selectedRoute    = $med->route ?? '';
        $this->medSearch        = $med->display;
        $this->medResults       = [];
        $this->showItemForm     = true;
    }

    // -------------------------------------------------------------------------
    // Item management
    // -------------------------------------------------------------------------

    public function addItem(): void
    {
        $this->checkPermission('prescriptions.create');

        $this->validate([
            'selectedMedName' => 'required|string|max:200',
            'dose'            => 'required|string|max:100',
            'frequency'       => 'required|string|max:100',
            'duration'        => 'nullable|integer|min:1|max:365',
            'refills'         => 'integer|min:0|max:10',
        ]);

        $prescription = $this->getPrescription();

        if ($prescription->isIssued()) {
            $this->errorMessage = 'Cannot edit an issued prescription.';
            return;
        }

        $this->prescriptionService->addItem($prescription, [
            'medication_id'   => $this->selectedMedId,
            'medication_name' => $this->selectedMedName,
            'strength'        => $this->selectedStrength ?: null,
            'form'            => $this->selectedForm ?: null,
            'route'           => $this->selectedRoute ?: null,
            'dose'            => $this->dose,
            'frequency'       => $this->frequency,
            'duration'        => $this->duration ?: null,
            'duration_unit'   => $this->durationUnit,
            'quantity'        => $this->quantity ?: null,
            'instructions'    => $this->instructions ?: null,
            'refills_allowed' => $this->refills,
        ]);

        $this->checkAllergies();
        $this->successMessage = 'Medication added.';
        $this->resetItemForm();
    }

    public function removeItem(int $itemId): void
    {
        $this->checkPermission('prescriptions.create');

        $prescription = $this->getPrescription();
        $item = PrescriptionItem::on('tenant')
            ->where('prescription_id', $prescription->id)
            ->findOrFail($itemId);

        $this->prescriptionService->removeItem($item);
        $this->checkAllergies();
        $this->successMessage = 'Item removed.';
    }

    // -------------------------------------------------------------------------
    // Allergy check
    // -------------------------------------------------------------------------

    private function checkAllergies(): void
    {
        $prescription = $this->getPrescription();
        $prescription->load('items.medication');

        $allergyNames = PatientAllergy::on('tenant')
            ->where('patient_id', $this->patientId)
            ->pluck('allergen')
            ->toArray();

        $this->allergyWarnings = $this->prescriptionService->checkAllergies($prescription, $allergyNames);
        $this->allergyChecked  = $prescription->allergy_checked;
    }

    public function markAllergyChecked(): void
    {
        $prescription = $this->getPrescription();
        $prescription->update(['allergy_checked' => true]);
        $this->allergyChecked = true;
    }

    // -------------------------------------------------------------------------
    // Issue
    // -------------------------------------------------------------------------

    public function issuePrescription(): void
    {
        $this->checkPermission('prescriptions.create');

        if (!empty($this->allergyWarnings) && !$this->allergyChecked) {
            $this->errorMessage = 'Allergy warnings present. Mark allergy review as complete before issuing.';
            return;
        }

        $prescription = $this->getPrescription();

        try {
            $this->prescriptionService->issuePrescription($prescription, Auth::guard('tenant')->id());
            $this->isIssued = true;
            $this->successMessage = "Prescription {$prescription->prescription_number} issued.";
            $this->errorMessage   = '';
        } catch (\LogicException|\InvalidArgumentException $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    // -------------------------------------------------------------------------
    // Rendering
    // -------------------------------------------------------------------------

    public function render()
    {
        $prescription = Prescription::on('tenant')
            ->with('items')
            ->where('encounter_id', $this->encounterId)
            ->first();

        return view('livewire.tenant.prescriptions.prescription-form', [
            'prescription' => $prescription,
        ])->layout('layouts.app-workspace');
    }

    // -------------------------------------------------------------------------
    // Private
    // -------------------------------------------------------------------------

    private function getPrescription(): Prescription
    {
        return $this->prescriptionService->getOrCreateDraft($this->encounterId, $this->doctorId);
    }

    private function checkPermission(string $perm): void
    {
        $user = Auth::guard('tenant')->user();
        if (!$user || !$user->hasPermission($perm)) {
            abort(403);
        }
    }

    private function resetItemForm(): void
    {
        $this->selectedMedId    = null;
        $this->selectedMedName  = '';
        $this->selectedStrength = '';
        $this->selectedForm     = '';
        $this->selectedRoute    = '';
        $this->medSearch        = '';
        $this->dose             = '';
        $this->frequency        = '';
        $this->duration         = '';
        $this->durationUnit     = 'days';
        $this->quantity         = '';
        $this->instructions     = '';
        $this->refills          = 0;
        $this->showItemForm     = false;
    }
}
