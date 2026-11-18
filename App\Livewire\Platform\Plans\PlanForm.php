<?php

namespace App\Livewire\Platform\Plans;

use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.platform')]
class PlanForm extends Component
{
    public function render(): \Illuminate\View\View
    {
        return view('livewire.platform.plans.plan-form');
    }
}
