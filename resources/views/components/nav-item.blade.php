@props(['route', 'label'])

@php
    $href = '#';
    $exists = false;
    if (\Illuminate\Support\Facades\Route::has($route)) {
        try {
            $href   = route($route);
            $exists = true;
        } catch (\Throwable $e) {
            // Route exists but requires parameters not available in a nav context
        }
    }
    $active = $exists && (request()->routeIs($route) || request()->routeIs($route . '.*'));
@endphp

<li>
    <a href="{{ $href }}"
       class="flex rounded-md px-3 py-2 text-sm font-medium {{ $active ? 'bg-blue-50 text-blue-700' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-800' }} {{ !$exists ? 'opacity-40 cursor-not-allowed' : '' }}">
        {{ $label }}
    </a>
</li>
