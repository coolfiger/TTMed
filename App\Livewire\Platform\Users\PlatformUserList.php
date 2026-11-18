<?php

namespace App\Livewire\Platform\Users;

use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.platform')]
class PlatformUserList extends Component
{
    public function render(): \Illuminate\View\View
    {
        return view('livewire.platform.users.user-list');
    }
}
