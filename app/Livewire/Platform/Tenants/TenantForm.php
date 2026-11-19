<?php

namespace App\Livewire\Platform\Tenants;

use App\Models\Platform\Subscription;
use App\Models\Platform\SubscriptionPlan;
use App\Models\Platform\Tenant;
use App\Services\AuditService;
use App\Services\TenantProvisioningService;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.platform')]
class TenantForm extends Component
{
    public ?Tenant $tenant = null;

    public string $code          = '';
    public string $name          = '';
    public string $business_name = '';
    public string $contact_name  = '';
    public string $contact_email = '';
    public string $contact_phone = '';
    public string $custom_domain = '';
    public ?int   $plan_id       = null;
    public string $admin_name    = '';
    public string $admin_username = '';
    public string $admin_email   = '';
    public string $admin_password = '';
    public bool   $isEdit        = false;

    public function mount(?string $uuid = null): void
    {
        if ($uuid) {
            $tenant = Tenant::where('uuid', $uuid)->firstOrFail();
            $this->tenant        = $tenant;
            $this->isEdit        = true;
            $this->code          = $tenant->code;
            $this->name          = $tenant->name;
            $this->business_name = $tenant->business_name ?? '';
            $this->contact_name  = $tenant->contact_name ?? '';
            $this->contact_email = $tenant->contact_email ?? '';
            $this->contact_phone = $tenant->contact_phone ?? '';
            $this->custom_domain = $tenant->custom_domain ?? '';
        }
    }

    public function save(TenantProvisioningService $provisioner, AuditService $audit): void
    {
        $rules = [
            'name'          => 'required|string|max:150',
            'business_name' => 'nullable|string|max:150',
            'contact_name'  => 'nullable|string|max:150',
            'contact_email' => 'nullable|email|max:150',
            'contact_phone' => 'nullable|string|max:30',
            'custom_domain' => 'nullable|string|max:150',
        ];

        if (! $this->isEdit) {
            $rules['code']           = 'required|string|max:20|alpha_num|unique:platform.tenants,code';
            $rules['plan_id']        = 'required|exists:platform.subscription_plans,id';
            $rules['admin_name']     = 'required|string|max:150';
            $rules['admin_username'] = 'required|string|max:50|alpha_dash';
            $rules['admin_email']    = 'required|email|max:150';
            $rules['admin_password'] = 'required|string|min:10';
        }

        $this->validate($rules);

        if ($this->isEdit) {
            $old = $this->tenant->toArray();
            $this->tenant->update([
                'name'          => $this->name,
                'business_name' => $this->business_name ?: null,
                'contact_name'  => $this->contact_name ?: null,
                'contact_email' => $this->contact_email ?: null,
                'contact_phone' => $this->contact_phone ?: null,
                'custom_domain' => $this->custom_domain ?: null,
            ]);

            $audit->logPlatform('tenant.updated', 'Tenant', $this->tenant->id, $this->tenant->id, $old, $this->tenant->fresh()->toArray());
            session()->flash('message', 'Medical centre updated.');
            $this->redirectRoute('platform.tenants.show', $this->tenant);
            return;
        }

        // Create new tenant
        $tenant = Tenant::create([
            'code'          => strtolower($this->code),
            'name'          => $this->name,
            'business_name' => $this->business_name ?: null,
            'contact_name'  => $this->contact_name ?: null,
            'contact_email' => $this->contact_email ?: null,
            'contact_phone' => $this->contact_phone ?: null,
            'custom_domain' => $this->custom_domain ?: null,
            'status'        => 'trial',
        ]);

        // Provision the tenant database and seed it
        $provisioner->provision($tenant, [
            'name'     => $this->admin_name,
            'username' => $this->admin_username,
            'email'    => $this->admin_email,
            'password' => $this->admin_password,
        ]);

        // Create subscription
        $plan = SubscriptionPlan::findOrFail($this->plan_id);
        Subscription::create([
            'tenant_id'     => $tenant->id,
            'plan_id'       => $plan->id,
            'status'        => 'active',
            'billing_cycle' => 'monthly',
            'starts_at'     => today(),
            'expires_at'    => today()->addMonth(),
            'amount'        => $plan->monthly_price,
        ]);

        $audit->logPlatform('tenant.created', 'Tenant', $tenant->id, $tenant->id, newValues: $tenant->toArray());
        session()->flash('message', "Medical centre '{$tenant->name}' created successfully. Login URL: {$tenant->login_url}");
        $this->redirectRoute('platform.tenants.index');
    }

    public function render(): \Illuminate\View\View
    {
        $plans = SubscriptionPlan::where('is_active', true)->get();

        return view('livewire.platform.tenants.tenant-form', compact('plans'));
    }
}
