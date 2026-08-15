<x-app-layout>
    <x-slot name="header">
        <div>
            <a href="{{ route('student.progress') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-700">
                ← My Progress
            </a>
            <h2 class="mt-1 text-2xl font-bold text-slate-800">{{ $course->title }}</h2>
            <p class="mt-1 text-slate-500">{{ $course->description }}</p>
        </div>
    </x-slot>

    <div class="mx-auto max-w-4xl space-y-6 p-6">

        {{-- Progress summary --}}
        <div class="flex flex-col gap-4 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm md:flex-row md:items-center md:justify-between">

            <div>
                <h3 class="text-lg font-semibold text-slate-900">Course Progress</h3>
                <p class="mt-1 text-sm text-slate-500">
                    {{ $completedLessons }} of {{ $totalLessons }} lessons completed
                </p>
            </div>

            <div class="md:w-64">
                <div class="flex items-center justify-between text-sm font-medium">
                    <span class="text-slate-600">Progress</span>
                    <span class="{{ $percent === 100 ? 'text-emerald-600' : 'text-indigo-600' }}">{{ $percent }}%</span>
                </div>
                <div class="mt-2 h-2.5 w-full overflow-hidden rounded-full bg-slate-100">
                    <div class="h-full rounded-full {{ $percent === 100 ? 'bg-emerald-500' : 'bg-indigo-500' }} transition-all" style="width: {{ $percent }}%"></div>
                </div>
            </div>

        </div>

        {{-- Lesson list --}}
        <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">

            <div class="flex items-center gap-3 border-b border-slate-100 p-5">
                <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-100 text-indigo-600">
                    <x-heroicon-o-book-open class="h-6 w-6"/>
                </span>
                <div>
                    <h3 class="text-xl font-bold text-slate-800">Lessons</h3>
                    <p class="text-sm text-slate-500">Work through the lessons in order.</p>
                </div>
            </div>

            <div class="divide-y divide-slate-100">

                @forelse($lessons as $lesson)

                @php
                    $record = $progressRecords->get($lesson->id);
                    $status = $record?->status ?? 'Not Started';

                    $badge = match ($status) {
                        'Completed' => 'bg-emerald-100 text-emerald-700',
                        'In Progress' => 'bg-amber-100 text-amber-700',
                        default => 'bg-slate-100 text-slate-600',
                    };

                    $assessment = $lesson->assessments
                        ->first(fn ($assessment) => $assessment->questions_count > 0);
                @endphp

                <div class="flex flex-col gap-4 p-5 sm:flex-row sm:items-center sm:justify-between">

                    <div class="flex min-w-0 items-start gap-4">

                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-indigo-50 text-sm font-bold text-indigo-600">
                            {{ $lesson->order_number }}
                        </span>

                        <div class="min-w-0">
                            <a
                                href="{{ route('student.lessons.show', [$course, $lesson]) }}"
                                class="font-semibold text-slate-900 hover:text-indigo-700">
                                {{ $lesson->title }}
                            </a>

                            <div class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-slate-500">
                                <span>{{ $status }}</span>

                                @if($lesson->estimated_duration)
                                    <span>· {{ $lesson->estimated_duration }} min</span>
                                @endif

                                @if($lesson->resources_count > 0)
                                    <span class="inline-flex items-center gap-1">
                                        <x-heroicon-o-paper-clip class="h-3.5 w-3.5"/>
                                        {{ $lesson->resources_count }} material{{ $lesson->resources_count === 1 ? '' : 's' }}
                                    </span>
                                @endif

                                @if($assessment)
                                    <a
                                        href="{{ route('student.assessments.take', $assessment) }}"
                                        class="inline-flex items-center gap-1 font-medium text-indigo-600 hover:text-indigo-700">
                                        <x-heroicon-o-clipboard-document-check class="h-3.5 w-3.5"/>
                                        {{ $assessment->title }} · {{ $assessment->questions_count }} question{{ $assessment->questions_count === 1 ? '' : 's' }}
                                    </a>
                                @endif
                            </div>
                        </div>

                    </div>

                    <div class="flex shrink-0 items-center gap-2 pl-14 sm:pl-0">
                        <span class="rounded-full px-3 py-1 text-sm font-medium {{ $badge }}">{{ $status }}</span>

                        <a
                            href="{{ route('student.lessons.show', [$course, $lesson]) }}"
                            class="inline-flex items-center gap-1.5 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-indigo-700">
                            Open Lesson
                            <x-heroicon-o-arrow-right class="h-4 w-4"/>
                        </a>
                    </div>

                </div>

                @empty

                <div class="py-16 text-center">
                    <x-heroicon-o-book-open class="mx-auto h-12 w-12 text-slate-300"/>
                    <h3 class="mt-4 text-xl font-semibold text-slate-700">No lessons published yet</h3>
                    <p class="mt-2 text-slate-500">The mentor has not published any lessons for this course yet.</p>
                </div>

                @endforelse

            </div>

        </div>

    </div>
</x-app-layout>
