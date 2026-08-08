<nav class="bg-white border-b border-gray-100">
    <div class="max-w-7xl mx-auto px-4 py-4 flex justify-between">
        <div class="flex items-center gap-6">
            <div class="font-bold">
                SkillBridge
            </div>

            @if(Auth::user()->isStudent())
                @php
                    $unreadNotifications = Auth::user()->notifications()->where('is_read', false)->count();
                @endphp

                <a
                    href="{{ route('student.courses') }}"
                    class="text-sm font-medium {{ request()->routeIs('student.courses') ? 'text-indigo-600' : 'text-slate-600 hover:text-indigo-600' }}">
                    Browse Courses
                </a>

                <a
                    href="{{ route('student.assessments.index') }}"
                    class="text-sm font-medium {{ request()->routeIs('student.assessments.*') ? 'text-indigo-600' : 'text-slate-600 hover:text-indigo-600' }}">
                    My Assessments
                </a>

                <a
                    href="{{ route('student.progress') }}"
                    class="text-sm font-medium {{ request()->routeIs('student.progress') ? 'text-indigo-600' : 'text-slate-600 hover:text-indigo-600' }}">
                    My Progress
                </a>

                <a
                    href="{{ route('notifications.index') }}"
                    class="text-sm font-medium {{ request()->routeIs('notifications.*') ? 'text-indigo-600' : 'text-slate-600 hover:text-indigo-600' }}">
                    Notifications
                    @if($unreadNotifications > 0)
                        <span class="ml-1 inline-flex items-center justify-center rounded-full bg-indigo-600 px-1.5 text-xs font-bold text-white">
                            {{ $unreadNotifications }}
                        </span>
                    @endif
                </a>
            @endif
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
