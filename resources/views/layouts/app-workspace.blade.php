<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ app(\App\Tenancy\TenantContext::class)->get()?->name ?? config('app.name') }} – @yield('title', 'Dashboard')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    @stack('head')
</head>
<body class="h-full bg-slate-50">

<div class="flex h-screen overflow-hidden"
     x-data="navSidebar()"
     x-init="init()">

    {{-- ── Sidebar ───────────────────────────────────────────────────────── --}}
    <div :style="'width:' + (collapsed ? '56px' : '256px')"
         class="flex-shrink-0 flex flex-col bg-white border-r border-slate-200 overflow-hidden transition-[width] duration-200 ease-in-out z-40 relative">

        {{-- Logo / name --}}
        @php
            $sidebarLogo   = app(\App\Services\CentreSettingsService::class)->get('branding_sidebar_logo_path');
            $sidebarTz     = app(\App\Services\CentreSettingsService::class)->get('timezone', 'America/Port_of_Spain');
        @endphp
        <div class="flex h-14 shrink-0 items-center border-b border-slate-200 overflow-hidden"
             :class="collapsed ? 'justify-center px-0' : 'px-4 gap-2'">
            <div x-show="!collapsed"
                 x-data="{
                     time: '',
                     tz: '{{ $sidebarTz }}',
                     tick() {
                         this.time = new Intl.DateTimeFormat('en-US', {
                             hour: 'numeric', minute: '2-digit', hour12: true,
                             timeZone: this.tz
                         }).format(new Date());
                     },
                     init() { this.tick(); setInterval(() => this.tick(), 10000); }
                 }"
                 class="min-w-0 flex-1">
                @if($sidebarLogo && file_exists(storage_path('app/' . $sidebarLogo)))
                    <img src="{{ route('tenant.branding.logo', ['type' => 'sidebar']) }}"
                         alt="{{ app(\App\Tenancy\TenantContext::class)->get()?->name ?? 'TTMed' }}"
                         class="h-7 max-w-[160px] object-contain" />
                @else
                    <p class="font-semibold text-slate-800 text-sm leading-tight truncate">
                        {{ app(\App\Tenancy\TenantContext::class)->get()?->name ?? 'TTMed' }}
                    </p>
                @endif
                <p class="text-xs text-slate-400 tabular-nums" x-text="time"></p>
            </div>
            {{-- Toggle button --}}
            <button @click="toggle()"
                    class="flex-shrink-0 w-7 h-7 rounded-md flex items-center justify-center text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition"
                    :title="collapsed ? 'Expand menu' : 'Collapse menu'">
                <svg x-show="!collapsed" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7M18 19l-7-7 7-7"/>
                </svg>
                <svg x-show="collapsed" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M6 5l7 7-7 7"/>
                </svg>
            </button>
        </div>

        {{-- Nav --}}
        <nav class="flex flex-1 flex-col py-2 overflow-y-auto overflow-x-hidden">
            @php $user = auth()->guard('tenant')->user(); @endphp

            @php
            $navItems = [
                ['route' => 'tenant.dashboard',              'label' => 'Dashboard',       'perm' => null,                   'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>'],
                ['route' => 'tenant.patients.index',         'label' => 'Patients',         'perm' => ['patients.view','patients.create'], 'any' => true, 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>'],
                ['route' => 'tenant.reception.dashboard',    'label' => 'Reception',        'perm' => 'queue.view',           'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>'],
                ['route' => 'tenant.queue.manage',           'label' => 'Queue',            'perm' => 'queue.view',           'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>'],
                ['route' => 'tenant.nursing.dashboard',      'label' => 'Nursing',          'perm' => 'nursing.assess',       'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>'],
                ['route' => 'tenant.consultations.dashboard','label' => 'Consultations',    'perm' => 'encounters.view',      'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>'],
                ['route' => 'tenant.appointments.calendar',  'label' => 'Appointments',     'perm' => 'appointments.view',    'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>'],
                ['route' => 'tenant.prescriptions.index',    'label' => 'Prescriptions',    'perm' => 'prescriptions.view',   'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>'],
                ['route' => 'tenant.imaging.index',          'label' => 'Imaging',          'perm' => 'imaging.view',         'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>'],
                ['route' => 'tenant.document.tracker',       'label' => 'Doc Tracker',      'perm' => 'documents.view',       'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>'],
                ['route' => 'tenant.reports.index',          'label' => 'Reports',          'perm' => 'reports.operational',  'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>'],
                ['route' => 'tenant.staff.index',            'label' => 'Staff',            'perm' => 'staff.view',           'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>'],
                ['route' => 'tenant.insurance.index',        'label' => 'Insurance',        'perm' => 'insurance.view',       'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>'],
            ];
            @endphp

            <ul class="space-y-0.5 px-1.5">
                @foreach($navItems as $item)
                    @php
                        $show = true;
                        if (!empty($item['perm'])) {
                            if (!empty($item['any'])) {
                                $show = $user?->hasAnyPermission($item['perm']);
                            } else {
                                $show = $user?->hasPermission($item['perm']);
                            }
                        }
                        $routeExists = \Illuminate\Support\Facades\Route::has($item['route']);
                        $active = $routeExists && (request()->routeIs($item['route']) || request()->routeIs($item['route'] . '.*'));
                    @endphp
                    @if($show && $routeExists)
                    <li>
                        <a href="{{ route($item['route']) }}"
                           class="flex items-center gap-2.5 rounded-md py-1.5 text-sm font-medium transition-colors
                                  {{ $active ? 'bg-blue-50 text-blue-700' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-800' }}"
                           :class="collapsed ? 'justify-center px-0' : 'px-2.5'"
                           :title="collapsed ? '{{ $item['label'] }}' : ''">
                            <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">{!! $item['icon'] !!}</svg>
                            <span x-show="!collapsed" class="truncate">{{ $item['label'] }}</span>
                        </a>
                    </li>
                    @endif
                @endforeach

                {{-- Accounts (sub-menu) --}}
                @php
                    $canAccounts = $user?->hasAnyPermission(['billing.view','payments.view','shifts.view']);
                    $inAccounts  = request()->routeIs('tenant.billing.*') || request()->routeIs('tenant.payments.*');
                @endphp
                @if($canAccounts)
                <li x-data="{ open: {{ $inAccounts ? 'true' : 'false' }} }">
                    <button @click="collapsed ? (collapsed = false) : (open = !open)"
                            class="w-full flex items-center gap-2.5 rounded-md py-1.5 text-sm font-medium transition-colors
                                   {{ $inAccounts ? 'bg-blue-50 text-blue-700' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-800' }}"
                            :class="collapsed ? 'justify-center px-0' : 'px-2.5'"
                            :title="collapsed ? 'Accounts' : ''">
                        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                        </svg>
                        <span x-show="!collapsed" class="flex-1 text-left">Accounts</span>
                        <svg x-show="!collapsed" :class="open ? 'rotate-90' : ''" class="h-3.5 w-3.5 flex-shrink-0 transition-transform duration-150" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                        </svg>
                    </button>
                    <ul x-show="open && !collapsed" x-cloak class="mt-0.5 ml-6 border-l border-slate-200 pl-2 space-y-0.5">
                        @if($user?->hasPermission('billing.view'))
                        <li>
                            <a href="{{ route('tenant.billing.index') }}"
                               class="flex rounded-md px-2.5 py-1 text-sm {{ request()->routeIs('tenant.billing.index') ? 'text-blue-700 font-medium' : 'text-slate-500 hover:bg-slate-100 hover:text-slate-800' }}">
                                Billing
                            </a>
                        </li>
                        @endif
                        @if($user?->hasPermission('payments.view'))
                        <li>
                            <a href="{{ route('tenant.payments.index') }}"
                               class="flex rounded-md px-2.5 py-1 text-sm {{ request()->routeIs('tenant.payments.*') ? 'text-blue-700 font-medium' : 'text-slate-500 hover:bg-slate-100 hover:text-slate-800' }}">
                                Payments
                            </a>
                        </li>
                        @endif
                        @if($user?->hasPermission('shifts.view'))
                        <li>
                            <a href="{{ route('tenant.billing.shifts') }}"
                               class="flex rounded-md px-2.5 py-1 text-sm {{ request()->routeIs('tenant.billing.shifts') ? 'text-blue-700 font-medium' : 'text-slate-500 hover:bg-slate-100 hover:text-slate-800' }}">
                                Cash Mgmt
                            </a>
                        </li>
                        @endif
                    </ul>
                </li>
                @endif

                {{-- Settings (sub-menu) --}}
                @if($user?->hasPermission('settings.view'))
                @php
                    $inSettings = request()->routeIs('tenant.settings.*')
                        || request()->routeIs('tenant.users.*')
                        || request()->routeIs('tenant.branches.*')
                        || request()->routeIs('tenant.services.*');
                @endphp
                <li x-data="{ open: {{ $inSettings ? 'true' : 'false' }} }">
                    <button @click="collapsed ? (collapsed = false) : (open = !open)"
                            class="w-full flex items-center gap-2.5 rounded-md py-1.5 text-sm font-medium transition-colors
                                   {{ $inSettings ? 'bg-blue-50 text-blue-700' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-800' }}"
                            :class="collapsed ? 'justify-center px-0' : 'px-2.5'"
                            :title="collapsed ? 'Settings' : ''">
                        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        <span x-show="!collapsed" class="flex-1 text-left">Settings</span>
                        <svg x-show="!collapsed" :class="open ? 'rotate-90' : ''" class="h-3.5 w-3.5 flex-shrink-0 transition-transform duration-150" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                        </svg>
                    </button>
                    <ul x-show="open && !collapsed" x-cloak class="mt-0.5 ml-6 border-l border-slate-200 pl-2 space-y-0.5">
                        @foreach([
                            ['tenant.settings.index'        => 'General'],
                            ['tenant.settings.templates'    => 'Templates'],
                            ['tenant.users.index'           => 'Users'],
                            ['tenant.branches.index'        => 'Branches'],
                        ] as $sub)
                            @php [$sr, $sl] = [array_key_first($sub), $sub[array_key_first($sub)]]; @endphp
                            <li>
                                <a href="{{ route($sr) }}"
                                   class="flex rounded-md px-2.5 py-1 text-sm {{ request()->routeIs($sr) || request()->routeIs($sr . '.*') ? 'text-blue-700 font-medium' : 'text-slate-500 hover:bg-slate-100 hover:text-slate-800' }}">
                                    {{ $sl }}
                                </a>
                            </li>
                        @endforeach
                        @if($user?->hasPermission('services.view'))
                        <li>
                            <a href="{{ route('tenant.services.index') }}"
                               class="flex rounded-md px-2.5 py-1 text-sm {{ request()->routeIs('tenant.services.*') ? 'text-blue-700 font-medium' : 'text-slate-500 hover:bg-slate-100 hover:text-slate-800' }}">
                                Services
                            </a>
                        </li>
                        @endif
                        @if($user?->hasPermission('settings.manage'))
                        <li>
                            <a href="{{ route('tenant.settings.integrations') }}"
                               class="flex rounded-md px-2.5 py-1 text-sm {{ request()->routeIs('tenant.settings.integrations') ? 'text-blue-700 font-medium' : 'text-slate-500 hover:bg-slate-100 hover:text-slate-800' }}">
                                Integrations
                            </a>
                        </li>
                        @endif
                    </ul>
                </li>
                @endif
            </ul>

            {{-- User / sign-out --}}
            <div class="mt-auto pt-2 border-t border-slate-200 mx-1.5">
                <div x-show="!collapsed" class="px-2.5 py-2">
                    <p class="text-sm font-medium text-slate-700 truncate">{{ auth()->guard('tenant')->user()?->name }}</p>
                    <p class="text-xs text-slate-400 truncate">{{ auth()->guard('tenant')->user()?->roles->first()?->name }}</p>
                </div>
                <a href="{{ route('tenant.logout') }}"
                   class="flex items-center gap-2.5 rounded-md py-1.5 text-sm text-slate-500 hover:text-slate-800 hover:bg-slate-100 transition-colors"
                   :class="collapsed ? 'justify-center px-0' : 'px-2.5'"
                   title="Sign out">
                    <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                    </svg>
                    <span x-show="!collapsed">Sign out</span>
                </a>
            </div>
        </nav>
    </div>

    {{-- ── Main workspace ────────────────────────────────────────────────── --}}
    <div class="flex-1 flex flex-col min-h-0 overflow-hidden">
        {{ $slot }}
    </div>

</div>

<script>
function navSidebar() {
    return {
        collapsed: false,
        init() {
            try {
                this.collapsed = localStorage.getItem('nav_collapsed') === '1';
            } catch {}
        },
        toggle() {
            this.collapsed = !this.collapsed;
            try { localStorage.setItem('nav_collapsed', this.collapsed ? '1' : '0'); } catch {}
        },
    };
}
</script>

@stack('body-scripts')
@livewireScripts
</body>
</html>
