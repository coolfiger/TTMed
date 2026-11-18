@props(['route', 'label'])

@php
    $active = request()->routeIs($route) || request()->routeIs($route . '.*');
@endphp

<li>
    <a href="{{ route($route) }}"
       class="flex rounded-md px-3 py-2 text-sm font-medium {{ $active ? 'bg-blue-50 text-blue-700' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-800' }}">
        {{ $label }}
    </a>
</li>
