<nav class="bg-white border-b border-gray-100">
    <div class="max-w-7xl mx-auto px-4 py-4 flex justify-between">
        <div class="font-bold">
            SkillBridge
        </div>

        <div>
            {{ Auth::user()->first_name }} {{ Auth::user()->last_name }}

            <form method="POST" action="{{ route('logout') }}" class="inline">
                @csrf
                <button type="submit" class="ml-4 text-red-600">
                    Logout
                </button>   
            </form>
        </div>
    </div>
</nav>