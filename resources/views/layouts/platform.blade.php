<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TTMed Platform – @yield('title', 'Dashboard')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="h-full" x-data="{ sidebarOpen: false }">

<div class="min-h-full">
    {{-- Sidebar --}}
    <div class="fixed inset-y-0 z-50 flex w-64 flex-col bg-slate-900">
        <div class="flex h-16 shrink-0 items-center px-6">
            <span class="text-white font-semibold text-lg">TTMed</span>
            <span class="ml-2 text-xs text-slate-400 font-medium uppercase tracking-widest">Platform</span>
        </div>
        <nav class="flex flex-1 flex-col px-4 py-4 overflow-y-auto">
            <ul class="space-y-1">
                <li>
                    <a href="{{ route('platform.dashboard') }}" class="group flex gap-x-3 rounded-md px-3 py-2 text-sm font-medium text-slate-300 hover:bg-slate-800 hover:text-white {{ request()->routeIs('platform.dashboard') ? 'bg-slate-800 text-white' : '' }}">
                        Dashboard
                    </a>
                </li>
                <li>
                    <a href="{{ route('platform.tenants.index') }}" class="group flex gap-x-3 rounded-md px-3 py-2 text-sm font-medium text-slate-300 hover:bg-slate-800 hover:text-white {{ request()->routeIs('platform.tenants.*') ? 'bg-slate-800 text-white' : '' }}">
                        Medical Centres
                    </a>
                </li>
                <li>
                    <a href="{{ route('platform.plans.index') }}" class="group flex gap-x-3 rounded-md px-3 py-2 text-sm font-medium text-slate-300 hover:bg-slate-800 hover:text-white {{ request()->routeIs('platform.plans.*') ? 'bg-slate-800 text-white' : '' }}">
                        Subscription Plans
                    </a>
                </li>
                <li>
                    <a href="{{ route('platform.users.index') }}" class="group flex gap-x-3 rounded-md px-3 py-2 text-sm font-medium text-slate-300 hover:bg-slate-800 hover:text-white {{ request()->routeIs('platform.users.*') ? 'bg-slate-800 text-white' : '' }}">
                        Platform Users
                    </a>
                </li>
            </ul>
            <div class="mt-auto pt-4 border-t border-slate-700">
                <a href="{{ route('platform.profile') }}" class="flex items-center gap-x-3 rounded-md px-3 py-2 text-sm text-slate-400 hover:text-white hover:bg-slate-800 {{ request()->routeIs('platform.profile') ? 'text-white bg-slate-800' : '' }}">
                    <span>{{ auth()->guard('platform')->user()?->name }}</span>
                </a>
                <a href="{{ route('platform.logout') }}" class="flex gap-x-3 rounded-md px-3 py-2 text-sm text-slate-400 hover:text-white hover:bg-slate-800">
                    Sign out
                </a>
            </div>
        </nav>
    </div>

    {{-- Main content --}}
    <div class="pl-64">
        <main class="py-8 px-8">
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
