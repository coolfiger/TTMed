<?php

namespace App\Livewire\Tenant\Users;

use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class UserForm extends Component
{
    public function render(): \Illuminate\View\View
    {
        return view('livewire.tenant.users.user-form');
    }
}
