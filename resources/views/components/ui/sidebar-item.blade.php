@props([
    'href',
    'active' => false
])

<a
    href="{{ $href }}"
    class="flex items-center gap-3 px-4 py-3 rounded-xl transition
    {{ $active
        ? 'bg-indigo-600 text-white'
        : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">

    {{ $slot }}

</a>