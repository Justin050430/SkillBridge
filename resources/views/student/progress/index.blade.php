<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-2xl font-bold text-slate-800">My Progress</h2>
            <p class="mt-1 text-slate-500">Track your completion across the courses you are enrolled in.</p>
        </div>
    </x-slot>

    <div class="max-w-5xl mx-auto p-6 space-y-6">

        @forelse($enrollments as $enrollment)

        @php
            $lessons = $enrollment->course->lessons;
            $totalLessons = $lessons->count();
            $completed = $lessons->filter(fn ($lesson) => ($progressRecords->get($enrollment->id.'-'.$lesson->id)?->status) === 'Completed')->count();
            $percent = $totalLessons > 0 ? round(($completed / $totalLessons) * 100) : 0;
        @endphp

        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">

            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">

                <div>

                    <h3 class="text-lg font-semibold text-slate-900">
                        {{ $enrollment->course->title }}
                    </h3>

                    <p class="mt-1 text-sm text-slate-500">
                        {{ $completed }} of {{ $totalLessons }} lessons completed
                    </p>

                </div>

                <div class="md:w-64">

                    <div class="flex items-center justify-between text-sm font-medium">

                        <span class="text-slate-600">Progress</span>

                        <span class="{{ $percent === 100 ? 'text-emerald-600' : 'text-indigo-600' }}">
                            {{ $percent }}%
                        </span>

                    </div>

                    <div class="mt-2 h-2.5 w-full overflow-hidden rounded-full bg-slate-100">

                        <div
                            class="h-full rounded-full {{ $percent === 100 ? 'bg-emerald-500' : 'bg-indigo-500' }} transition-all"
                            style="width: {{ $percent }}%">
                        </div>

                    </div>

                </div>

            </div>

            <div class="mt-5 divide-y divide-slate-100">

                @foreach($lessons as $lesson)

                @php
                    $record = $progressRecords->get($enrollment->id.'-'.$lesson->id);
                    $status = $record?->status ?? 'Not Started';

                    $badge = match ($status) {
                        'Completed' => 'bg-emerald-100 text-emerald-700',
                        'In Progress' => 'bg-amber-100 text-amber-700',
                        default => 'bg-slate-100 text-slate-600',
                    };
                @endphp

                <div class="flex items-center justify-between gap-4 py-3">

                    <div class="flex items-center gap-3 min-w-0">

                        <span class="shrink-0 text-sm font-semibold text-slate-400">
                            {{ $lesson->order_number }}.
                        </span>

                        <div class="min-w-0">

                            <p class="truncate font-medium text-slate-800">
                                {{ $lesson->title }}
                            </p>

                            <p class="text-xs text-slate-500">
                                {{ $status }}
                            </p>

                        </div>

                    </div>

                    <div class="flex shrink-0 items-center gap-2">

                        <span class="rounded-full px-3 py-1 text-sm font-medium {{ $badge }}">
                            {{ $status }}
                        </span>

                        @if($status !== 'Completed')

                            <form method="POST" action="{{ route('student.progress.update', $lesson) }}">
                                @csrf

                                <input type="hidden" name="status" value="Completed">

                                <button
                                    type="submit"
                                    class="rounded-lg bg-indigo-50 px-3 py-1 text-sm font-medium text-indigo-600 transition hover:bg-indigo-100">
                                    Mark Complete
                                </button>
                            </form>

                        @endif

                    </div>

                </div>

                @endforeach

            </div>

        </div>

        @empty

        <div class="rounded-2xl border border-slate-200 bg-white p-12 text-center shadow-sm">

            <x-heroicon-o-academic-cap class="mx-auto h-12 w-12 text-slate-300" />

            <h3 class="mt-4 text-xl font-semibold text-slate-700">
                No enrollments yet
            </h3>

            <p class="mt-2 text-slate-500">
                Enrol in a course to start tracking your progress.
            </p>

            <a
                href="{{ route('student.courses') }}"
                class="mt-6 inline-flex items-center justify-center rounded-xl bg-indigo-600 px-5 py-2.5 font-medium text-white transition hover:bg-indigo-700">

                Browse Courses

            </a>

        </div>

        @endforelse

    </div>

</x-app-layout>
