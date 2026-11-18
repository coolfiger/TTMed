<?php

namespace App\Livewire\Platform\Plans;

use App\Models\Platform\SubscriptionPlan;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.platform')]
class PlanList extends Component
{
    public function render(): \Illuminate\View\View
    {
        return view('livewire.platform.plans.plan-list', [
            'plans' => SubscriptionPlan::orderBy('monthly_price')->get(),
        ]);
    }
}
