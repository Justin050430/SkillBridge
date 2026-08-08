@props([
    'href' => null,
    'iconClass' => 'h-8 w-8 text-indigo-600',
    'textClass' => 'text-lg',
])

@if($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => 'inline-flex items-center gap-2 group']) }}>
@else
    <span {{ $attributes->merge(['class' => 'inline-flex items-center gap-2']) }}>
@endif

    <x-heroicon-o-academic-cap class="{{ $iconClass }}" />

    <span class="font-bold text-slate-900 {{ $textClass }} group-hover:text-indigo-600 transition">
        SkillBridge
    </span>

@if($href)
    </a>
@else
    </span>
@endif
