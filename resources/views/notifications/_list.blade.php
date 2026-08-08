<div class="bg-white rounded-2xl shadow-sm border border-slate-200">

    <div class="p-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">

        <h2 class="text-lg font-semibold text-slate-800">
            Notifications
        </h2>

        @if($notifications->where('is_read', false)->count() > 0)

            <form method="POST" action="{{ route('notifications.read-all') }}">

                @csrf

                <button
                    type="submit"
                    class="inline-flex items-center rounded-xl bg-indigo-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-indigo-700">

                    Mark all as read

                </button>

            </form>

        @endif

    </div>

    <div class="divide-y divide-slate-100">

        @forelse($notifications as $notification)

        @php
            $typeClasses = match ($notification->notification_type) {
                'Course' => 'bg-indigo-100 text-indigo-700',
                'Lesson' => 'bg-amber-100 text-amber-700',
                'Assessment' => 'bg-emerald-100 text-emerald-700',
                'Enrollment' => 'bg-blue-100 text-blue-700',
                default => 'bg-slate-100 text-slate-600',
            };
        @endphp

        <div class="flex items-start justify-between gap-4 p-6 {{ $notification->is_read ? '' : 'bg-indigo-50/50' }}">

            <div class="min-w-0">

                <div class="flex flex-wrap items-center gap-2">

                    <p class="font-semibold {{ $notification->is_read ? 'text-slate-600' : 'text-slate-900' }}">
                        {{ $notification->title }}
                    </p>

                    @if(! $notification->is_read)
                        <span class="rounded-full bg-indigo-600 px-2 py-0.5 text-xs font-medium text-white">
                            New
                        </span>
                    @endif

                    <span class="rounded-full px-2.5 py-0.5 text-xs font-medium {{ $typeClasses }}">
                        {{ $notification->notification_type }}
                    </span>

                </div>

                <p class="mt-1 text-sm text-slate-600">
                    {{ $notification->message }}
                </p>

                <p class="mt-1 text-xs text-slate-400">
                    {{ $notification->created_at->diffForHumans() }}
                </p>

            </div>

            @if(! $notification->is_read)

                <form method="POST" action="{{ route('notifications.read', $notification) }}" class="shrink-0">

                    @csrf

                    <button
                        type="submit"
                        class="rounded-lg bg-slate-100 px-3 py-1 text-sm font-medium text-slate-600 transition hover:bg-slate-200">

                        Mark read

                    </button>

                </form>

            @endif

        </div>

        @empty

        <div class="py-16 text-center">

            <x-heroicon-o-bell class="w-12 h-12 mx-auto text-slate-300" />

            <h3 class="mt-4 text-xl font-semibold text-slate-700">
                No notifications yet
            </h3>

            <p class="mt-2 text-slate-500">
                Updates about your courses, assessments and enrollments will appear here.
            </p>

        </div>

        @endforelse

    </div>

    <div class="p-6">
        {{ $notifications->links() }}
    </div>

</div>
