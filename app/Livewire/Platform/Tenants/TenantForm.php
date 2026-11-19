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

    public string  $code         = '';
    public string  $name         = '';
    public string  $status       = 'trial';
    public string  $businessName = '';
    public string  $contactName  = '';
    public string  $contactEmail = '';
    public string  $contactPhone = '';
    public string  $customDomain = '';
    public ?int    $planId       = null;
    public string  $adminName    = '';
    public string  $adminUsername = '';
    public string  $adminEmail   = '';
    public string  $adminPassword = '';
    public string  $branchName   = '';
    public bool    $isEdit       = false;

    public function mount(?string $uuid = null): void
    {
        if ($uuid) {
            $tenant = Tenant::where('uuid', $uuid)->firstOrFail();
            $this->tenant        = $tenant;
            $this->isEdit        = true;
            $this->code          = $tenant->code;
            $this->name          = $tenant->name;
            $this->status        = $tenant->status ?? 'trial';
            $this->businessName  = $tenant->business_name ?? '';
            $this->contactName   = $tenant->contact_name ?? '';
            $this->contactEmail  = $tenant->contact_email ?? '';
            $this->contactPhone  = $tenant->contact_phone ?? '';
            $this->customDomain  = $tenant->custom_domain ?? '';
            $this->planId        = $tenant->subscription?->plan_id;
        }
    }

    public function save(TenantProvisioningService $provisioner, AuditService $audit): void
    {
        $rules = [
            'name'        => 'required|string|max:150',
            'status'      => 'required|in:trial,active,suspended,cancelled',
            'businessName' => 'nullable|string|max:150',
            'contactName'  => 'nullable|string|max:150',
            'contactEmail' => 'nullable|email|max:150',
            'contactPhone' => 'nullable|string|max:30',
            'customDomain' => 'nullable|string|max:150',
        ];

        if (! $this->isEdit) {
            $rules['code']          = 'required|string|max:20|alpha_num|unique:platform.tenants,code';
            $rules['planId']        = 'required|exists:platform.subscription_plans,id';
            $rules['adminName']     = 'required|string|max:150';
            $rules['adminUsername'] = 'required|string|max:50|alpha_dash';
            $rules['adminEmail']    = 'required|email|max:150';
            $rules['adminPassword'] = 'required|string|min:10';
            $rules['branchName']    = 'required|string|max:150';
        }

        $this->validate($rules);

        if ($this->isEdit) {
            $old = $this->tenant->toArray();
            $this->tenant->update([
                'name'          => $this->name,
                'status'        => $this->status,
                'business_name' => $this->businessName ?: null,
                'contact_name'  => $this->contactName ?: null,
                'contact_email' => $this->contactEmail ?: null,
                'contact_phone' => $this->contactPhone ?: null,
                'custom_domain' => $this->customDomain ?: null,
            ]);

            // Update subscription plan if changed
            if ($this->planId) {
                $subscription = $this->tenant->subscription;
                $plan         = SubscriptionPlan::findOrFail($this->planId);
                if ($subscription) {
                    if ($subscription->plan_id !== (int) $this->planId) {
                        $subscription->update([
                            'plan_id' => $plan->id,
                            'amount'  => $plan->monthly_price,
                        ]);
                    }
                } else {
                    Subscription::create([
                        'tenant_id'     => $this->tenant->id,
                        'plan_id'       => $plan->id,
                        'status'        => 'active',
                        'billing_cycle' => 'monthly',
                        'starts_at'     => today(),
                        'expires_at'    => today()->addMonth(),
                        'amount'        => $plan->monthly_price,
                    ]);
                }
            }

            $audit->logPlatform('tenant.updated', 'Tenant', $this->tenant->id, $this->tenant->id, $old, $this->tenant->fresh()->toArray());
            session()->flash('message', 'Medical centre updated.');
            $this->redirectRoute('platform.tenants.show', ['uuid' => $this->tenant->uuid]);
            return;
        }

        // Create new tenant
        $tenant = Tenant::create([
            'code'          => strtolower($this->code),
            'name'          => $this->name,
            'business_name' => $this->businessName ?: null,
            'contact_name'  => $this->contactName ?: null,
            'contact_email' => $this->contactEmail ?: null,
            'contact_phone' => $this->contactPhone ?: null,
            'custom_domain' => $this->customDomain ?: null,
            'status'        => $this->status,
        ]);

        // Provision the tenant database and seed it
        $provisioner->provision($tenant, [
            'name'       => $this->adminName,
            'username'   => $this->adminUsername,
            'email'      => $this->adminEmail,
            'password'   => $this->adminPassword,
            'branchName' => $this->branchName,
        ]);

        // Create subscription
        $plan = SubscriptionPlan::findOrFail($this->planId);
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
