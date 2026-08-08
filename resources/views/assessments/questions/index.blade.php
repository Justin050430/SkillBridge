@extends('layouts.admin')

@section('title', 'Questions')

@section('header')
<x-ui.page-header
    title="Questions"
    subtitle="{{ $assessment->title }} · {{ $assessment->lesson->course->title }}" />
@endsection

@section('content')

<div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">

    <div class="flex items-center gap-3">

        <a
            href="{{ route('assessments.index') }}"
            class="inline-flex items-center px-4 py-2 rounded-xl border border-slate-300 bg-white text-slate-700 hover:bg-slate-50 transition">

            &larr; Back to Assessments

        </a>

    </div>

    <a
        href="{{ route('assessments.questions.create', $assessment) }}"
        class="inline-flex items-center justify-center px-5 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white transition">
        + Add Question
    </a>

</div>

<div class="bg-white rounded-2xl shadow-sm border border-slate-200">

    <div class="overflow-x-auto">

        <table class="w-full min-w-[760px]">

            <thead class="bg-slate-100">

                <tr>
                    <th class="text-left p-4 w-12">#</th>
                    <th class="text-left p-4">Question</th>
                    <th class="text-left p-4">Type</th>
                    <th class="text-left p-4">Marks</th>
                    <th class="text-left p-4">Correct Answer</th>
                    <th class="text-center p-4">Action</th>
                </tr>

            </thead>

            <tbody>

                @forelse($questions as $question)

                <tr class="border-t border-slate-200 hover:bg-slate-50">

                    <td class="p-4 text-slate-500">{{ $question->order_number }}</td>

                    <td class="p-4">
                        <p class="font-medium text-slate-900 max-w-md">{{ $question->question_text }}</p>
                    </td>

                    <td class="p-4">
                        @php
                            $typeClasses = match($question->question_type) {
                                'MCQ' => 'bg-indigo-100 text-indigo-700',
                                'TrueFalse' => 'bg-emerald-100 text-emerald-700',
                                default => 'bg-amber-100 text-amber-700',
                            };
                        @endphp

                        <span class="px-3 py-1 rounded-full text-sm font-medium {{ $typeClasses }}">
                            {{ $question->question_type }}
                        </span>
                    </td>

                    <td class="p-4 text-slate-600">{{ $question->marks }}</td>

                    <td class="p-4 text-slate-600">{{ $question->correct_answer }}</td>

                    <td class="p-4 text-center">
                        <div class="flex justify-center gap-2">
                            <a
                                href="{{ route('assessments.questions.edit', [$assessment, $question]) }}"
                                class="px-3 py-1 rounded-lg bg-indigo-50 text-indigo-600 hover:bg-indigo-100">
                                Edit
                            </a>

                            <form
                                method="POST"
                                action="{{ route('assessments.questions.destroy', [$assessment, $question]) }}"
                                onsubmit="return confirm('Delete this question?')">

                                @csrf
                                @method('DELETE')

                                <button
                                    type="submit"
                                    class="px-3 py-1 rounded-lg bg-red-50 text-red-600 hover:bg-red-100">
                                    Delete
                                </button>

                            </form>
                        </div>
                    </td>

                </tr>

                @empty

                <tr>
                    <td colspan="6" class="py-16 text-center">
                        <x-heroicon-o-question-mark-circle class="w-12 h-12 mx-auto text-slate-300" />

                        <h3 class="mt-4 text-xl font-semibold text-slate-700">
                            No questions yet
                        </h3>

                        <p class="mt-2 text-slate-500">
                            Add questions to let students attempt this assessment.
                        </p>

                        <a
                            href="{{ route('assessments.questions.create', $assessment) }}"
                            class="mt-4 inline-flex items-center px-5 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white transition">
                            + Add Question
                        </a>
                    </td>
                </tr>

                @endforelse

            </tbody>

        </table>

    </div>

    <div class="p-6 border-t border-slate-200 flex justify-between items-center text-sm text-slate-500">

        <span>{{ $questions->count() }} question(s)</span>

        <span>Total marks: {{ $questions->sum('marks') }}</span>

    </div>

</div>

@endsection
