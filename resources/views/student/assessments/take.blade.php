<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-2xl font-bold text-slate-800">{{ $assessment->title }}</h2>
            <p class="mt-1 text-slate-500">
                {{ $assessment->lesson->course->title }} — Attempt #{{ $attempt->attempt_number }}
            </p>
        </div>
    </x-slot>

    <div class="max-w-4xl mx-auto p-6">

        <div class="mb-6 flex items-center justify-between rounded-2xl border border-indigo-200 bg-indigo-50 p-4 text-sm text-indigo-800">

            <span>
                Answer all questions, then submit once at the end.
            </span>

            @if($assessment->time_limit)
                <span>
                    ⏱ {{ $assessment->time_limit }} minute(s)
                </span>
            @endif

            <span>
                Passing score: {{ $assessment->passing_score }}%
            </span>

        </div>

        <form
            method="POST"
            action="{{ route('student.assessments.submit', $assessment) }}"
            onsubmit="return confirm('Are you sure you want to submit? You cannot change your answers afterwards.');">

            @csrf

            <div class="space-y-6">

                @foreach($questions as $index => $question)

                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">

                    <div class="flex items-start justify-between gap-4">

                        <div>

                            <span class="inline-flex items-center gap-2 text-sm font-semibold text-indigo-600">
                                <x-heroicon-o-question-mark-circle class="h-5 w-5"/>
                                Question {{ $index + 1 }}
                            </span>

                            <p class="mt-2 text-lg font-medium text-slate-900">
                                {{ $question->question_text }}
                            </p>

                        </div>

                        <span class="shrink-0 rounded-full bg-slate-100 px-3 py-1 text-sm font-medium text-slate-600">
                            {{ $question->marks }} mark(s)
                        </span>

                    </div>

                    <div class="mt-5">

                        @if($question->question_type === 'MCQ')

                            <div class="grid grid-cols-1 gap-3 md:grid-cols-2">

                                @foreach(['a', 'b', 'c', 'd'] as $letter)

                                    @if($question->{'option_'.$letter})

                                        <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-slate-200 p-4 transition hover:border-indigo-300 hover:bg-indigo-50">

                                            <input
                                                type="radio"
                                                name="answers[{{ $question->id }}]"
                                                value="{{ strtoupper($letter) }}"
                                                class="h-4 w-4 text-indigo-600">

                                            <span class="font-semibold text-slate-500">{{ strtoupper($letter) }}.</span>

                                            <span class="text-slate-700">{{ $question->{'option_'.$letter} }}</span>

                                        </label>

                                    @endif

                                @endforeach

                            </div>

                        @elseif($question->question_type === 'TrueFalse')

                            <div class="grid grid-cols-1 gap-3 md:grid-cols-2">

                                @foreach(['True', 'False'] as $option)

                                <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-slate-200 p-4 transition hover:border-indigo-300 hover:bg-indigo-50">

                                    <input
                                        type="radio"
                                        name="answers[{{ $question->id }}]"
                                        value="{{ $option }}"
                                        class="h-4 w-4 text-indigo-600">

                                    <span class="text-slate-700">{{ $option }}</span>

                                </label>

                                @endforeach

                            </div>

                        @else

                            <input
                                type="text"
                                name="answers[{{ $question->id }}]"
                                placeholder="Type your answer..."
                                class="block w-full rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">

                        @endif

                    </div>

                </div>

                @endforeach

            </div>

            <div class="mt-8 flex justify-end">

                <button
                    type="submit"
                    class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-6 py-3 font-medium text-white transition hover:bg-indigo-700">

                    <x-heroicon-o-paper-airplane class="h-5 w-5"/>

                    Submit Assessment

                </button>

            </div>

        </form>

    </div>

</x-app-layout>
