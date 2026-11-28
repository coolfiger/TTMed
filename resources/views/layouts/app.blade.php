<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ app(\App\Tenancy\TenantContext::class)->get()?->name ?? config('app.name') }} – @yield('title', 'Dashboard')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="h-full" x-data="{ sidebarOpen: false }">

<div class="min-h-full flex">
    {{-- Sidebar --}}
    <div class="fixed inset-y-0 z-50 flex w-64 flex-col bg-white shadow-sm border-r border-slate-200">
        <div class="flex h-16 shrink-0 items-center px-6 border-b border-slate-200">
            @php
                $sidebarLogo = app(\App\Services\CentreSettingsService::class)->get('branding_sidebar_logo_path');
                $sidebarTz   = app(\App\Services\CentreSettingsService::class)->get('timezone', 'America/Port_of_Spain');
            @endphp
            <div class="flex-1 min-w-0 flex items-start justify-between gap-2"
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
                 }">
                @if($sidebarLogo && file_exists(storage_path('app/' . $sidebarLogo)))
                    <img src="{{ route('tenant.branding.logo', ['type' => 'sidebar']) }}"
                         alt="{{ app(\App\Tenancy\TenantContext::class)->get()?->name ?? 'TTMed' }}"
                         class="h-8 max-w-36 object-contain" />
                @else
                    <div class="min-w-0">
                        <p class="font-semibold text-slate-800 text-sm leading-tight truncate">
                            {{ app(\App\Tenancy\TenantContext::class)->get()?->name ?? 'TTMed' }}
                        </p>
                        <p class="text-xs text-slate-500">Medical Practice</p>
                    </div>
                @endif
                <p class="text-xs text-slate-400 tabular-nums flex-shrink-0 mt-0.5" x-text="time"></p>
            </div>
        </div>
        <nav class="flex flex-1 flex-col px-3 py-4 overflow-y-auto">
            <ul class="space-y-1 text-sm">
                @php $user = auth()->guard('tenant')->user(); @endphp

                <x-nav-item route="tenant.dashboard" label="Dashboard" />

                @if($user?->hasAnyPermission(['patients.view', 'patients.create']))
                    <x-nav-item route="tenant.patients.index" label="Patients" />
                @endif

                @if($user?->hasPermission('queue.view'))
                    <x-nav-item route="tenant.reception.dashboard" label="Reception" />
                    <x-nav-item route="tenant.queue.manage" label="Queue" />
                @endif

                @if($user?->hasPermission('nursing.assess'))
                    <x-nav-item route="tenant.nursing.dashboard" label="Nursing" />
                @endif

                @if($user?->hasPermission('encounters.view'))
                    <x-nav-item route="tenant.consultations.dashboard" label="Consultations" />
                @endif

                @if($user?->hasPermission('appointments.view'))
                    <x-nav-item route="tenant.appointments.calendar" label="Appointments" />
                @endif

                @if($user?->hasPermission('prescriptions.view'))
                    <x-nav-item route="tenant.prescriptions.index" label="Prescriptions" />
                @endif

                @if($user?->hasPermission('documents.view'))
                @endif

                @if($user?->hasPermission('billing.view'))
                    <x-nav-item route="tenant.billing.index" label="Billing" />
                @endif

                @if($user?->hasPermission('payments.view'))
                    <x-nav-item route="tenant.payments.index" label="Payments" />
                @endif

                @if($user?->hasPermission('shifts.view'))
                    <x-nav-item route="tenant.billing.shifts" label="Cash Management" />
                @endif

                @if($user?->hasPermission('reports.operational'))
                    <x-nav-item route="tenant.reports.index" label="Reports" />
                @endif

                @if($user?->hasPermission('staff.view'))
                    <x-nav-item route="tenant.staff.index" label="Staff" />
                @endif

                @if($user?->hasPermission('insurance.view'))
                    <x-nav-item route="tenant.insurance.index" label="Insurance" />
                @endif

                @if($user?->hasPermission('settings.view'))
                @php
                    $inSettings = request()->routeIs('tenant.settings.*')
                        || request()->routeIs('tenant.users.*')
                        || request()->routeIs('tenant.branches.*')
                        || request()->routeIs('tenant.services.*');
                @endphp
                <li x-data="{ open: {{ $inSettings ? 'true' : 'false' }} }">
                    <button @click="open = !open"
                            class="w-full flex items-center justify-between rounded-md px-3 py-2 text-sm font-medium {{ $inSettings ? 'text-blue-700' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-800' }}">
                        <span>Settings</span>
                        <svg :class="open ? 'rotate-90' : ''" class="h-4 w-4 transition-transform duration-150" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                        </svg>
                    </button>
                    <ul x-show="open" x-cloak class="mt-0.5 ml-3 border-l border-slate-200 pl-2 space-y-0.5">
                        <li>
                            <a href="{{ route('tenant.settings.index') }}"
                               class="flex rounded-md px-3 py-1.5 text-sm {{ request()->routeIs('tenant.settings.index') ? 'bg-blue-50 text-blue-700 font-medium' : 'text-slate-500 hover:bg-slate-100 hover:text-slate-800' }}">
                                General
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('tenant.settings.templates') }}"
                               class="flex rounded-md px-3 py-1.5 text-sm {{ request()->routeIs('tenant.settings.templates') || request()->routeIs('tenant.settings.templates.*') ? 'bg-blue-50 text-blue-700 font-medium' : 'text-slate-500 hover:bg-slate-100 hover:text-slate-800' }}">
                                Templates
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('tenant.users.index') }}"
                               class="flex rounded-md px-3 py-1.5 text-sm {{ request()->routeIs('tenant.users.*') ? 'bg-blue-50 text-blue-700 font-medium' : 'text-slate-500 hover:bg-slate-100 hover:text-slate-800' }}">
                                Users
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('tenant.branches.index') }}"
                               class="flex rounded-md px-3 py-1.5 text-sm {{ request()->routeIs('tenant.branches.*') ? 'bg-blue-50 text-blue-700 font-medium' : 'text-slate-500 hover:bg-slate-100 hover:text-slate-800' }}">
                                Branches
                            </a>
                        </li>
                        @if($user?->hasPermission('services.view'))
                        <li>
                            <a href="{{ route('tenant.services.index') }}"
                               class="flex rounded-md px-3 py-1.5 text-sm {{ request()->routeIs('tenant.services.*') ? 'bg-blue-50 text-blue-700 font-medium' : 'text-slate-500 hover:bg-slate-100 hover:text-slate-800' }}">
                                Services
                            </a>
                        </li>
                        @endif
                        @if($user?->hasPermission('settings.manage'))
                        <li>
                            <a href="{{ route('tenant.settings.integrations') }}"
                               class="flex rounded-md px-3 py-1.5 text-sm {{ request()->routeIs('tenant.settings.integrations') ? 'bg-blue-50 text-blue-700 font-medium' : 'text-slate-500 hover:bg-slate-100 hover:text-slate-800' }}">
                                Integrations
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('tenant.settings.observation-rooms') }}"
                               class="flex rounded-md px-3 py-1.5 text-sm {{ request()->routeIs('tenant.settings.observation-rooms') ? 'bg-blue-50 text-blue-700 font-medium' : 'text-slate-500 hover:bg-slate-100 hover:text-slate-800' }}">
                                Obs. Rooms
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('tenant.settings.vaccines') }}"
                               class="flex rounded-md px-3 py-1.5 text-sm {{ request()->routeIs('tenant.settings.vaccines') ? 'bg-blue-50 text-blue-700 font-medium' : 'text-slate-500 hover:bg-slate-100 hover:text-slate-800' }}">
                                Vaccines
                            </a>
                        </li>
                        @endif
                    </ul>
                </li>
                @endif
            </ul>

            <div class="mt-auto pt-4 border-t border-slate-200">
                <div class="px-3 py-2">
                    <p class="text-sm font-medium text-slate-700">{{ auth()->guard('tenant')->user()?->name }}</p>
                    <p class="text-xs text-slate-500">{{ auth()->guard('tenant')->user()?->roles->first()?->name }}</p>
                </div>
                <a href="{{ route('tenant.logout') }}" class="flex gap-x-3 rounded-md px-3 py-2 text-sm text-slate-500 hover:text-slate-800 hover:bg-slate-100">
                    Sign out
                </a>
            </div>
        </nav>
    </div>

    {{-- Main content --}}
    <div class="pl-64 flex-1">
        <header class="h-16 flex items-center px-8 bg-white border-b border-slate-200">
            <h1 class="text-lg font-semibold text-slate-800">@yield('heading', 'Dashboard')</h1>
        </header>
        <main class="p-8">
            @if(session('message'))
                <div class="mb-4 rounded-md bg-green-50 p-4 text-sm text-green-800 ring-1 ring-green-200">
                    {{ session('message') }}
                </div>
            @endif
            {{ $slot }}
        </main>
    </div>
</div>

@livewireScripts
</body>
</html>
