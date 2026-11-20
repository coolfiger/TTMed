<?php

namespace App\Livewire\Tenant\Patients;

use App\Models\Tenant\Branch;
use App\Models\Tenant\Patient;
use App\Models\Tenant\PatientIntakeToken;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app-workspace')]
class PatientSearch extends Component
{
    use WithPagination;

    public string $search = '';
    public string $dobFilter = '';
    public string $branchFilter = '';

    // Registration QR modal
    public bool    $showRegistrationModal = false;
    public ?string $registrationQrSvg    = null;
    public ?string $registrationToken    = null;
    public ?int    $registrationPatientId = null;
    public string  $registrationStatus   = ''; // '' | 'pending' | 'complete'
    public ?string $registrationExpiresAt = null;

    public function updatingSearch(): void { $this->resetPage(); }
    public function updatingDobFilter(): void { $this->resetPage(); }

    public function openRegistrationModal(): void
    {
        $user = Auth::guard('tenant')->user();
        if (!$user || !$user->hasPermission('patients.create')) {
            abort(403);
        }

        $this->showRegistrationModal  = true;
        $this->registrationQrSvg      = null;
        $this->registrationToken      = null;
        $this->registrationPatientId  = null;
        $this->registrationStatus     = '';
        $this->registrationExpiresAt  = null;
    }

    public function closeRegistrationModal(): void
    {
        $this->showRegistrationModal = false;
        $this->registrationQrSvg     = null;
        $this->registrationToken     = null;
        $this->registrationStatus    = '';
    }

    public function generateRegistrationQr(): void
    {
        $user = Auth::guard('tenant')->user();
        if (!$user || !$user->hasPermission('patients.create')) {
            abort(403);
        }

        // Expire any previous pending registration tokens for this user
        PatientIntakeToken::on('tenant')
            ->where('type', 'registration')
            ->where('created_by', $user->id)
            ->whereNull('patient_id')
            ->whereNull('used_at')
            ->update(['used_at' => now()]);

        $token     = Str::random(40);
        $expiresAt = now()->addHours(24);

        PatientIntakeToken::on('tenant')->create([
            'type'       => 'registration',
            'patient_id' => null,
            'pin'        => null,
            'token'      => $token,
            'expires_at' => $expiresAt,
            'created_by' => $user->id,
        ]);

        $url = route('tenant.patient.register', $token);

        $renderer = new ImageRenderer(
            new RendererStyle(220),
            new SvgImageBackEnd()
        );
        $writer = new Writer($renderer);
        $this->registrationQrSvg    = $writer->writeString($url);
        $this->registrationToken    = $token;
        $this->registrationStatus   = 'pending';
        $this->registrationExpiresAt = $expiresAt->format('d M Y, H:i');
    }

    public function checkRegistrationComplete(): void
    {
        if (!$this->registrationToken) {
            return;
        }

        $record = PatientIntakeToken::on('tenant')
            ->where('token', $this->registrationToken)
            ->first();

        if ($record && $record->patient_id) {
            $this->registrationPatientId = $record->patient_id;
            $this->registrationStatus    = 'complete';
        }
    }

    public function render(): \Illuminate\View\View
    {
        $patients = Patient::query()
            ->with(['branch', 'primaryDoctor.staffProfile'])
            ->when($this->search, fn ($q) => $q->search($this->search))
            ->when($this->dobFilter, fn ($q) => $q->whereDate('date_of_birth', $this->dobFilter))
            ->when($this->branchFilter, fn ($q) => $q->where('branch_id', $this->branchFilter))
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->paginate(25);

        $branches = Branch::where('is_active', true)->orderBy('name')->get();
        $total    = Patient::count();

        return view('livewire.tenant.patients.patient-search', compact('patients', 'branches', 'total'));
    }
}
