@props([
    'title',
    'subtitle' => null
])

<div class="mb-8">

    <h1 class="text-3xl font-bold text-slate-900">
        {{ $title }}
    </h1>

    @if($subtitle)

        <p class="mt-2 text-slate-500">
            {{ $subtitle }}
        </p>

    @endif

</div>