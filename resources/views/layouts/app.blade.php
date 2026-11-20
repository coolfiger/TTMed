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
            @endphp
            @if($sidebarLogo && file_exists(storage_path('app/' . $sidebarLogo)))
                <img src="{{ route('tenant.branding.logo', ['type' => 'sidebar']) }}"
                     alt="{{ app(\App\Tenancy\TenantContext::class)->get()?->name ?? 'TTMed' }}"
                     class="h-8 max-w-36 object-contain" />
            @else
                <div>
                    <p class="font-semibold text-slate-800 text-sm leading-tight">
                        {{ app(\App\Tenancy\TenantContext::class)->get()?->name ?? 'TTMed' }}
                    </p>
                    <p class="text-xs text-slate-500">Medical Practice</p>
                </div>
            @endif
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

                @if($user?->hasPermission('services.view'))
                    <x-nav-item route="tenant.services.index" label="Services" />
                @endif

                @if($user?->hasPermission('insurance.view'))
                    <x-nav-item route="tenant.insurance.index" label="Insurance" />
                @endif

                @if($user?->hasPermission('branches.view'))
                    <x-nav-item route="tenant.branches.index" label="Branches" />
                @endif

                @if($user?->hasPermission('settings.view'))
                    <x-nav-item route="tenant.settings.index" label="Settings" />
                @endif

                @if($user?->hasPermission('users.view'))
                    <x-nav-item route="tenant.users.index" label="Users" />
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
