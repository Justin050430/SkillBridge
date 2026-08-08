<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-2xl font-bold text-slate-800">{{ $assessment->title }} — Result</h2>
            <p class="mt-1 text-slate-500">{{ $assessment->lesson->course->title }}</p>
        </div>
    </x-slot>

    <div class="max-w-4xl mx-auto p-6 space-y-6">

        {{-- Overall result --}}
        <div class="{{ $attempt->is_passed ? 'border-emerald-200 bg-emerald-50' : 'border-red-200 bg-red-50' }} rounded-2xl border p-8 text-center">

            @if($attempt->is_passed)
                <x-heroicon-o-check-circle class="mx-auto h-14 w-14 text-emerald-500" />
            @else
                <x-heroicon-o-x-circle class="mx-auto h-14 w-14 text-red-500" />
            @endif

            <h3 class="mt-4 text-2xl font-bold {{ $attempt->is_passed ? 'text-emerald-800' : 'text-red-800' }}">
                {{ $attempt->is_passed ? 'Congratulations, you passed!' : 'Not passed this time' }}
            </h3>

            <p class="mt-2 {{ $attempt->is_passed ? 'text-emerald-700' : 'text-red-700' }}">
                You scored <strong>{{ $attempt->percentage }}%</strong>
                ({{ $attempt->score }} / {{ $assessment->questions->sum('marks') }} marks) —
                passing score is {{ $assessment->passing_score }}%.
            </p>

            <div class="mt-6 flex flex-wrap justify-center gap-3">

                <a
                    href="{{ route('student.assessments.index') }}"
                    class="inline-flex items-center gap-2 rounded-xl border border-slate-300 bg-white px-5 py-2.5 font-medium text-slate-700 transition hover:bg-slate-50">

                    Back to My Assessments

                </a>

                <a
                    href="{{ route('student.assessments.take', $assessment) }}"
                    class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-5 py-2.5 font-medium text-white transition hover:bg-indigo-700">

                    <x-heroicon-o-arrow-path class="h-5 w-5"/>
                    Retake

                </a>

            </div>

        </div>

        {{-- Per-question breakdown --}}
        <h3 class="text-xl font-semibold text-slate-800">
            Answer Review
        </h3>

        <div class="space-y-4">

            @foreach($assessment->questions()->orderBy('order_number')->get() as $index => $question)

            @php
                $answer = $answers->get($question->id);
                $isCorrect = $answer?->is_correct ?? false;
                $submitted = $answer?->submitted_answer ?? 'No answer';
            @endphp

            <div class="rounded-2xl border {{ $isCorrect ? 'border-emerald-200' : 'border-red-200' }} bg-white p-6 shadow-sm">

                <div class="flex items-start justify-between gap-4">

                    <div>

                        <span class="inline-flex items-center gap-2 text-sm font-semibold {{ $isCorrect ? 'text-emerald-600' : 'text-red-600' }}">

                            @if($isCorrect)
                                <x-heroicon-o-check-circle class="h-5 w-5"/>
                            @else
                                <x-heroicon-o-x-circle class="h-5 w-5"/>
                            @endif

                            Question {{ $index + 1 }} — {{ $isCorrect ? 'Correct' : 'Incorrect' }}

                        </span>

                        <p class="mt-2 font-medium text-slate-900">
                            {{ $question->question_text }}
                        </p>

                    </div>

                    <span class="shrink-0 rounded-full bg-slate-100 px-3 py-1 text-sm font-medium text-slate-600">
                        {{ $answer?->marks_awarded ?? 0 }} / {{ $question->marks }} mark(s)
                    </span>

                </div>

                <div class="mt-4 grid grid-cols-1 gap-3 text-sm md:grid-cols-2">

                    <div class="rounded-xl bg-slate-50 p-4">

                        <p class="font-semibold text-slate-500">Your answer</p>

                        <p class="mt-1 text-slate-800">
                            {{ $submitted === '' ? 'No answer' : $submitted }}
                        </p>

                    </div>

                    <div class="rounded-xl bg-emerald-50 p-4">

                        <p class="font-semibold text-emerald-600">Correct answer</p>

                        <p class="mt-1 text-emerald-800">
                            {{ $question->correct_answer }}
                        </p>

                    </div>

                </div>

                @if($question->explanation)

                    <p class="mt-3 rounded-xl bg-indigo-50 p-4 text-sm text-indigo-800">
                        <span class="font-semibold">Explanation: </span>{{ $question->explanation }}
                    </p>

                @endif

            </div>

            @endforeach

        </div>

        {{-- Consumed from the Course module's web service --}}
        @if(!empty($relatedCourses))

        <div class="pt-4">

            <h3 class="text-xl font-semibold text-slate-800">
                Explore More Courses
            </h3>

            <p class="mt-1 text-sm text-slate-500">
                Retrieved live from the SkillBridge course web service.
            </p>

            <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">

                @foreach(array_slice($relatedCourses, 0, 6) as $course)

                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">

                    <p class="text-sm font-medium text-indigo-600">
                        {{ $course['category'] ?? 'Course' }}
                    </p>

                    <h4 class="mt-1 font-semibold text-slate-900">
                        {{ $course['title'] ?? '' }}
                    </h4>

                    <p class="mt-2 line-clamp-2 text-sm text-slate-500">
                        {{ $course['description'] ?? '' }}
                    </p>

                    <p class="mt-3 border-t border-slate-100 pt-3 text-sm text-slate-500">
                        Mentor: <span class="font-medium text-slate-700">{{ $course['mentor'] ?? '' }}</span>
                    </p>

                </div>

                @endforeach

            </div>

        </div>

        @endif

    </div>

</x-app-layout>
