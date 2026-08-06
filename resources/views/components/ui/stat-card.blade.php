@props([
    'title',
    'value',
    'icon'
])

<div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">

    <div class="flex justify-between items-center">

        <div>

            <p class="text-slate-500 text-sm">
                {{ $title }}
            </p>

            <h2 class="text-3xl font-bold mt-2">
                {{ $value }}
            </h2>

        </div>

        <div class="text-indigo-600">

            {{ $icon }}

        </div>

    </div>

</div>