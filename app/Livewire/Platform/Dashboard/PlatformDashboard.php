<?php

namespace App\Livewire\Platform\Dashboard;

use App\Models\Platform\Subscription;
use App\Models\Platform\Tenant;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.platform')]
class PlatformDashboard extends Component
{
    public function render(): \Illuminate\View\View
    {
        $stats = [
            'total_tenants'  => Tenant::count(),
            'active_tenants' => Tenant::whereIn('status', ['active', 'trial'])->count(),
            'suspended'      => Tenant::where('status', 'suspended')->count(),
            'trial'          => Tenant::where('status', 'trial')->count(),
            'active_subs'    => Subscription::where('status', 'active')->count(),
        ];

        $recentTenants = Tenant::with('subscription.plan')
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        return view('livewire.platform.dashboard.index', compact('stats', 'recentTenants'));
    }
}
