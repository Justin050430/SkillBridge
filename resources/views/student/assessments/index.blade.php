<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-2xl font-bold text-slate-800">My Assessments</h2>
            <p class="mt-1 text-slate-500">Complete the assessments for the courses you are enrolled in.</p>
        </div>
    </x-slot>

    <div class="max-w-7xl mx-auto p-6 space-y-6">

        @forelse($summaries as $summary)

        @php
            $assessment = $summary->assessment;
        @endphp

        <article class="flex flex-col gap-4 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm md:flex-row md:items-center md:justify-between">

            <div class="min-w-0">

                <div class="flex flex-wrap items-center gap-3">

                    <h3 class="text-lg font-semibold text-slate-900">
                        {{ $assessment->title }}
                    </h3>

                    @if($summary->passed)
                        <span class="rounded-full bg-emerald-100 px-3 py-1 text-sm font-medium text-emerald-700">
                            Passed
                        </span>
                    @elseif($summary->attemptCount > 0)
                        <span class="rounded-full bg-red-100 px-3 py-1 text-sm font-medium text-red-700">
                            Not passed yet
                        </span>
                    @endif

                </div>

                <p class="mt-1 text-sm text-slate-500">
                    {{ $assessment->lesson->course->title }} — {{ $assessment->lesson->title }}
                </p>

                <div class="mt-3 flex flex-wrap gap-x-6 gap-y-1 text-sm text-slate-600">

                    <span>
                        {{ $assessment->questions_count }} question(s)
                    </span>

                    <span>
                        Passing: {{ $assessment->passing_score }}%
                    </span>

                    <span>
                        Attempts used: {{ $summary->attemptCount }}
                        @if($assessment->max_attempts)
                            / {{ $assessment->max_attempts }}
                        @endif
                    </span>

                    @if($summary->hasInProgress)
                        <span class="font-medium text-amber-600">
                            An attempt is in progress
                        </span>
                    @endif

                    @if($summary->bestScore !== null)
                        <span>
                            Best score:
                            <span class="font-semibold {{ $summary->passed ? 'text-emerald-600' : 'text-red-600' }}">
                                {{ number_format($summary->bestScore, 1) }}%
                            </span>
                        </span>
                    @endif

                </div>

            </div>

            <div class="shrink-0">

                @if($summary->canTake)

                    <a
                        href="{{ route('student.assessments.take', $assessment) }}"
                        class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-5 py-2.5 font-medium text-white transition hover:bg-indigo-700">

                        <x-heroicon-o-play-circle class="h-5 w-5"/>

                        {{ $summary->hasInProgress
                            ? 'Resume Assessment'
                            : ($summary->attemptCount > 0 ? 'Retake Assessment' : 'Start Assessment') }}

                    </a>

                @else

                    <span class="inline-flex items-center gap-2 rounded-xl bg-slate-100 px-5 py-2.5 font-medium text-slate-500">
                        <x-heroicon-o-x-circle class="h-5 w-5"/>
                        No attempts left
                    </span>

                @endif

                @if($summary->attemptCount > 0)

                    <a
                        href="{{ route('student.assessments.result', $assessment) }}"
                        class="mt-2 inline-flex w-full items-center justify-center gap-2 rounded-xl border border-slate-300 px-5 py-2.5 font-medium text-slate-700 transition hover:bg-slate-50">

                        View Result

                    </a>

                @endif

            </div>

        </article>

        @empty

        <div class="rounded-2xl border border-slate-200 bg-white p-12 text-center shadow-sm">

            <x-heroicon-o-clipboard-document-list class="mx-auto h-12 w-12 text-slate-300" />

            <h3 class="mt-4 text-xl font-semibold text-slate-700">
                No assessments available
            </h3>

            <p class="mt-2 text-slate-500">
                Assessments for your enrolled courses will appear here.
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
