<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-2xl font-bold text-slate-800">{{ $assessment->title }}</h2>
            <p class="mt-1 text-slate-500">
                {{ $assessment->lesson?->course?->title ?? $assessment->title }} — Attempt #{{ $attempt->attempt_number }}
            </p>
        </div>
    </x-slot>

    <div class="max-w-4xl mx-auto p-6 pb-44"
        x-data="assessmentTimer({{ Js::from([
            'attemptId' => $attempt->id,
            'expiresAt' => $attempt->expires_at?->getTimestampMs(),
            'serverNow' => now()->getTimestampMs(),
            'expireUrl' => route('student.assessments.expire', $assessment),
            'resultUrl' => route('student.assessments.result', ['assessment' => $assessment, 'attempt' => $attempt->id]),
        ]) }})"
        x-on:pageshow.window="sync()" x-on:focus.window="sync()" x-on:online.window="sync()"
        x-on:visibilitychange.document="if (!document.hidden) sync()">

        <div class="mb-6 flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-indigo-200 bg-indigo-50 p-4 text-sm text-indigo-800">

            <span>
                Answer all questions, then submit once at the end.
            </span>

            @if($attempt->expires_at)
                <span>
                    The timer continues if you leave this page.
                </span>
            @endif

            <span>
                Passing score: {{ $assessment->passing_score }}%
            </span>

        </div>

        <form
            id="submit-assessment-form"
            method="POST"
            action="{{ route('student.assessments.submit', $assessment) }}"
            x-on:submit.prevent="if (canSubmit()) $dispatch('open-modal', 'confirm-submit-assessment')">

            @csrf
            <input type="hidden" name="attempt_id" value="{{ $attempt->id }}">

            <fieldset :disabled="ended || submitting">

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

            </fieldset>

        </form>

        <x-modal name="confirm-submit-assessment" :show="false" focusable>
            <div
                class="p-6"
                role="dialog"
                aria-modal="true"
                aria-labelledby="confirm-submit-title"
                aria-describedby="confirm-submit-description">

                <div class="flex items-start gap-4">

                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-indigo-100">
                        <x-heroicon-o-paper-airplane class="h-6 w-6 text-indigo-600"/>
                    </div>

                    <div class="min-w-0">
                        <h2 id="confirm-submit-title" class="text-lg font-semibold text-slate-900">
                            {{ __('Submit Assessment?') }}
                        </h2>

                        <p id="confirm-submit-description" class="mt-1 text-sm text-slate-600">
                            {{ __('Are you sure you want to submit your assessment? You will not be able to change your answers after submission.') }}
                        </p>
                    </div>

                </div>

                <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">

                    <button
                        type="button"
                        x-on:click="$dispatch('close')"
                        class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-300 bg-white px-5 py-2.5 font-medium text-slate-700 transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                        {{ __('Cancel') }}
                    </button>

                    <button
                        type="button"
                        x-on:click="submitAnswers()"
                        :disabled="submitting || ended"
                        class="inline-flex items-center justify-center gap-2 rounded-xl bg-indigo-600 px-5 py-2.5 font-medium text-white transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-75">
                        <x-heroicon-o-paper-airplane x-show="!submitting" class="h-5 w-5"/>
                        <x-heroicon-o-arrow-path x-show="submitting" class="h-5 w-5 animate-spin"/>
                        <span x-text="submitting ? 'Submitting...' : 'Submit Assessment'"></span>
                    </button>

                </div>

            </div>
        </x-modal>

        @if($attempt->expires_at)
            <aside x-cloak class="fixed bottom-4 right-4 z-50 w-64 rounded-2xl border-2 p-4 shadow-xl"
                :class="warning ? 'assessment-timer-warning border-red-700 bg-red-50 text-red-800' : 'border-indigo-200 bg-white text-indigo-900'"
                aria-label="Assessment countdown">
                <p class="text-sm font-semibold">Time remaining</p>
                <p role="timer" aria-live="off" class="mt-1 font-mono text-3xl font-bold tabular-nums" x-text="display"></p>
                <p role="status" class="mt-1 text-sm font-semibold" x-text="message || (warning ? '10 seconds or less remaining!' : '')"></p>
            </aside>
            <noscript>
                <p class="mt-4 rounded-xl bg-amber-50 p-4 text-amber-900">The countdown display requires JavaScript. Your time limit still applies and late submissions will be rejected.</p>
            </noscript>
        @endif
        <div x-cloak x-show="ended" role="alert" class="fixed inset-0 z-40 flex items-center justify-center bg-white/95 p-6">
            <div class="max-w-md text-center">
                <h2 class="text-2xl font-bold text-red-800">This attempt has ended</h2>
                <p class="mt-3 text-slate-700" x-text="message"></p>
                <a href="{{ route('student.assessments.index') }}" class="mt-4 inline-block font-semibold text-indigo-700 underline">Back to My Assessments</a>
            </div>
        </div>

    </div>

</x-app-layout>
