<?php

namespace App\Livewire\Platform\Users;

use App\Models\Platform\PlatformUser;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.platform')]
class PlatformUserList extends Component
{
    use WithPagination;

    public string $search = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function render(): \Illuminate\View\View
    {
        $users = PlatformUser::query()
            ->when($this->search, fn ($q) => $q->where('email', 'like', "%{$this->search}%")
                ->orWhere('name', 'like', "%{$this->search}%"))
            ->orderBy('name')
            ->paginate(20);

        return view('livewire.platform.users.user-list', compact('users'));
    }
}
