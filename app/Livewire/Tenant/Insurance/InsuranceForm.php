<?php

namespace App\Livewire\Tenant\Insurance;

use App\Models\Tenant\InsuranceProvider;
use App\Services\AuditService;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class InsuranceForm extends Component
{
    public ?InsuranceProvider $provider = null;

    public string $name = '';
    public string $code = '';
    public string $contactName = '';
    public string $phone = '';
    public string $email = '';
    public string $address = '';
    public string $notes = '';
    public bool $isActive = true;

    public function mount(?int $id = null): void
    {
        if ($id) {
            $this->provider = InsuranceProvider::findOrFail($id);
            $this->fill([
                'name'        => $this->provider->name,
                'code'        => $this->provider->code,
                'contactName' => $this->provider->contact_name ?? '',
                'phone'       => $this->provider->phone ?? '',
                'email'       => $this->provider->email ?? '',
                'address'     => $this->provider->address ?? '',
                'notes'       => $this->provider->notes ?? '',
                'isActive'    => $this->provider->is_active,
            ]);
        }
    }

    public function save(): void
    {
        $this->validate([
            'name'  => 'required|string|max:150',
            'code'  => 'required|string|max:50|alpha_dash',
            'email' => 'nullable|email|max:150',
        ]);

        $data = [
            'name'         => $this->name,
            'code'         => strtoupper($this->code),
            'contact_name' => $this->contactName ?: null,
            'phone'        => $this->phone ?: null,
            'email'        => $this->email ?: null,
            'address'      => $this->address ?: null,
            'notes'        => $this->notes ?: null,
            'is_active'    => $this->isActive,
        ];

        if ($this->provider) {
            $this->provider->update($data);
            $id = $this->provider->id;
        } else {
            $id = InsuranceProvider::create($data)->id;
        }

        app(AuditService::class)->logTenant(
            auth()->guard('tenant')->id(),
            $this->provider ? 'insurance.updated' : 'insurance.created',
            'InsuranceProvider',
            $id
        );

        session()->flash('message', 'Insurance provider saved.');
        $this->redirect(route('tenant.insurance.index'));
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.tenant.insurance.insurance-form');
    }
}
