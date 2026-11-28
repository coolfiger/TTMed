<?php

namespace App\Livewire\Tenant\Settings;

use App\Models\Tenant\User;
use App\Models\Tenant\VaccineCatalog;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class VaccineSettings extends Component
{
    public string $activeTab = 'catalog';

    public function mount(): void
    {
        $user = Auth::guard('tenant')->user();
        if (!$user || !$user->hasPermission('settings.manage')) {
            abort(403);
        }
    }

    public function toggleVaccine(int $id): void
    {
        $vaccine = VaccineCatalog::on('tenant')->findOrFail($id);
        $vaccine->update(['is_active' => !$vaccine->is_active]);
    }

    public function toggleDoctorModule(int $userId): void
    {
        $user = User::on('tenant')->findOrFail($userId);
        $user->update(['vaccine_module_enabled' => !$user->vaccine_module_enabled]);
    }

    public function render()
    {
        $vaccines = VaccineCatalog::on('tenant')
            ->with('doseDefinitions')
            ->orderBy('sort_order')
            ->get();

        $doctors = User::on('tenant')
            ->where('is_active', true)
            ->whereHas('roles', fn ($q) => $q->where('slug', 'doctor'))
            ->orderBy('name')
            ->get(['id', 'name', 'vaccine_module_enabled']);

        return view('livewire.tenant.settings.vaccine-settings', [
            'vaccines' => $vaccines,
            'doctors'  => $doctors,
        ])->layout('layouts.app', ['title' => 'Vaccine Settings']);
    }
}
