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
                    <h1 class="text-2xl font-semibold text-slate-800">
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
