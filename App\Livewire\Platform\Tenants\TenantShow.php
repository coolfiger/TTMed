<?php

namespace App\Livewire\Platform\Tenants;

use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.platform')]
class TenantShow extends Component
{
    public function render(): \Illuminate\View\View
    {
        return view('livewire.platform.tenants.tenant-show');
    }
}
