<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name') }} – @yield('title', 'Sign In')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="h-full">
    <div class="min-h-full flex flex-col justify-center py-12 sm:px-6 lg:px-8">
        <div class="sm:mx-auto sm:w-full sm:max-w-md">
            <div class="text-center mb-6">
                @if(app(\App\Tenancy\TenantContext::class)->resolved())
                    @php
                        $loginLogo = app(\App\Services\CentreSettingsService::class)->get('branding_login_logo_path');
                    @endphp
                    @if($loginLogo && file_exists(storage_path('app/' . $loginLogo)))
                        <img src="{{ route('tenant.branding.logo', ['type' => 'login']) }}"
                             alt="{{ app(\App\Tenancy\TenantContext::class)->get()->name }}"
                             class="mx-auto mb-3 max-h-20 max-w-48 object-contain" />
                    @else
                        <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-blue-600 mb-3">
                            <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                            </svg>
                        </div>
                    @endif
                    <h1 class="text-xl font-bold text-slate-800">
                        {{ app(\App\Tenancy\TenantContext::class)->get()->name }}
                    </h1>
                    <p class="text-sm text-slate-500 mt-1">Medical Practice Management</p>
                @else
                    <h1 class="text-2xl font-semibold text-slate-800">TTMed</h1>
                @endif
            </div>
        </div>
        <div class="mt-2 sm:mx-auto sm:w-full sm:max-w-md">
            <div class="bg-white py-8 px-4 shadow-sm ring-1 ring-slate-200 sm:rounded-lg sm:px-10">
                {{ $slot }}
            </div>
        </div>
    </div>
    @livewireScripts
</body>
</html>
