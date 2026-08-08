@props([
    'role' => 'admin'
])

<aside class="w-64 bg-slate-900 text-white flex flex-col">

    <!-- Logo -->
    <div class="px-6 py-6 border-b border-slate-700">

        <a href="{{ route('dashboard') }}" class="block">
            <h1 class="text-2xl font-bold hover:text-indigo-300">
                SkillBridge
            </h1>
        </a>

        <p class="text-sm text-slate-400 mt-1">
            Learning Management System
        </p>

    </div>

    <!-- Navigation -->
    <nav class="flex-1 px-4 py-6 space-y-2">

        {{ $slot }}

    </nav>

    <!-- Footer -->
    <div class="border-t border-slate-700 p-4">

        <div class="mb-4">

            <div class="font-semibold">

                {{ Auth::user()->first_name }}
                {{ Auth::user()->last_name }}

            </div>

            <div class="text-sm text-slate-400">

                {{ Auth::user()->role->role_name }}

            </div>

        </div>

        <form method="POST" action="{{ route('logout') }}">
            @csrf

            <button
                class="w-full rounded-xl bg-red-600 hover:bg-red-700 py-2 transition">

                Logout

            </button>

        </form>

    </div>

</aside>