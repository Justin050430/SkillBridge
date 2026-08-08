<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-2xl font-bold text-slate-800">Notifications</h2>
            <p class="mt-1 text-slate-500">Your activity updates.</p>
        </div>
    </x-slot>

    <div class="max-w-4xl mx-auto p-6">
        @include('notifications._list', ['notifications' => $notifications])
    </div>

</x-app-layout>
