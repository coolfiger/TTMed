<?php

namespace App\Livewire\Tenant\Dashboard;

use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class TenantDashboard extends Component
{
    public function render(): \Illuminate\View\View
    {
        return view('livewire.tenant.dashboard.index');
    }
}
