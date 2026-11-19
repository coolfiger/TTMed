<?php

namespace App\Livewire\Platform\Plans;

use App\Models\Platform\SubscriptionPlan;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.platform')]
class PlanForm extends Component
{
    public ?SubscriptionPlan $plan = null;

    public string $name = '';
    public string $slug = '';
    public string $description = '';
    public int $userLimit = 10;
    public int $storageLimitGb = 5;
    public int $monthlyPrice = 0;
    public int $annualPrice = 0;
    public array $features = [];
    public bool $isActive = true;

    public function mount(?string $uuid = null): void
    {
        if ($uuid) {
            $this->plan = SubscriptionPlan::where('uuid', $uuid)->firstOrFail();
            $this->fill([
                'name'           => $this->plan->name,
                'slug'           => $this->plan->slug,
                'description'    => $this->plan->description ?? '',
                'userLimit'      => $this->plan->user_limit,
                'storageLimitGb' => $this->plan->storage_limit_gb,
                'monthlyPrice'   => $this->plan->monthly_price,
                'annualPrice'    => $this->plan->annual_price,
                'features'       => is_array($this->plan->features) ? $this->plan->features : (json_decode($this->plan->features, true) ?? []),
                'isActive'       => $this->plan->is_active,
            ]);
        }
    }

    public function save(): void
    {
        $this->validate([
            'name'           => 'required|string|max:100',
            'slug'           => 'required|string|max:50|alpha_dash',
            'userLimit'      => 'required|integer|min:1',
            'storageLimitGb' => 'required|integer|min:1',
            'monthlyPrice'   => 'required|integer|min:0',
            'annualPrice'    => 'required|integer|min:0',
        ]);

        $data = [
            'name'             => $this->name,
            'slug'             => $this->slug,
            'description'      => $this->description,
            'user_limit'       => $this->userLimit,
            'storage_limit_gb' => $this->storageLimitGb,
            'monthly_price'    => $this->monthlyPrice,
            'annual_price'     => $this->annualPrice,
            'features'         => $this->features,
            'is_active'        => $this->isActive,
        ];

        if ($this->plan) {
            $this->plan->update($data);
        } else {
            SubscriptionPlan::create($data);
        }

        session()->flash('message', 'Plan saved.');
        $this->redirect(route('platform.plans.index'));
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.platform.plans.plan-form');
    }
}
