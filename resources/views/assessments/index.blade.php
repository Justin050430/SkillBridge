@extends('layouts.admin')

@section('title', 'Assessment Management')

@section('header')
<x-ui.page-header
    title="Assessment Management"
    subtitle="Create and manage assessments and their questions." />
@endsection

@section('content')

<div class="bg-white rounded-2xl shadow-sm border border-slate-200">

    <div class="p-6 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">

        <form method="GET" class="flex flex-col gap-3 sm:flex-row">

            <x-text-input
                name="search"
                :value="request('search')"
                placeholder="Search by assessment or course..."
                class="w-full sm:w-80" />

            <select
                name="status"
                onchange="this.form.submit()"
                class="rounded-lg border-gray-300">

                <option value="">All Statuses</option>
                <option value="Draft" @selected(request('status') === 'Draft')>Draft</option>
                <option value="Published" @selected(request('status') === 'Published')>Published</option>

            </select>

        </form>

        <a
            href="{{ route('assessments.create') }}"
            class="inline-flex items-center justify-center px-5 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white transition">
            + Add Assessment
        </a>

    </div>

    <div class="overflow-x-auto">

        <table class="w-full min-w-[820px]">

            <thead class="bg-slate-100">

                <tr>
                    <th class="text-left p-4">Assessment</th>
                    <th class="text-left p-4">Course / Lesson</th>
                    <th class="text-left p-4">Mentor</th>
                    <th class="text-left p-4">Passing</th>
                    <th class="text-left p-4">Questions</th>
                    <th class="text-left p-4">Status</th>
                    <th class="text-center p-4">Action</th>
                </tr>

            </thead>

            <tbody>

                @forelse($assessments as $assessment)

                @php
                    $statusClasses = match($assessment->status) {
                        'Published' => 'bg-emerald-100 text-emerald-700',
                        default => 'bg-amber-100 text-amber-700',
                    };
                @endphp

                <tr class="border-t border-slate-200 hover:bg-slate-50">

                    <td class="p-4">
                        <p class="font-medium text-slate-900">{{ $assessment->title }}</p>
                        <p class="mt-1 text-sm text-slate-500">
                            {{ $assessment->questions_count }} questions · {{ $assessment->attempts_count }} attempt(s)
                        </p>
                    </td>

                    <td class="p-4">
                        <p class="text-slate-700">{{ $assessment->lesson->course->title }}</p>
                        <p class="mt-1 text-sm text-slate-500">{{ $assessment->lesson->title }}</p>
                    </td>

                    <td class="p-4 text-slate-600">
                        {{ $assessment->lesson->course->mentor->first_name }}
                        {{ $assessment->lesson->course->mentor->last_name }}
                    </td>

                    <td class="p-4 text-slate-600">
                        {{ $assessment->passing_score }}%
                    </td>

                    <td class="p-4 text-slate-600">
                        {{ $assessment->questions_count }}
                    </td>

                    <td class="p-4">
                        <span class="px-3 py-1 rounded-full text-sm font-medium {{ $statusClasses }}">
                            {{ $assessment->status }}
                        </span>

                        @if($assessment->questions_count === 0)
                            <p class="mt-1 text-xs text-amber-600">
                                Add questions before publishing
                            </p>
                        @endif
                    </td>

                    <td class="p-4 text-center">
                        <div class="flex justify-center gap-2">

                            <a
                                href="{{ route('assessments.questions.index', $assessment) }}"
                                class="px-3 py-1 rounded-lg bg-slate-100 text-slate-700 hover:bg-slate-200">
                                Questions
                            </a>

                            <a
                                href="{{ route('assessments.edit', $assessment) }}"
                                class="px-3 py-1 rounded-lg bg-indigo-50 text-indigo-600 hover:bg-indigo-100">
                                Edit
                            </a>

                            <a
                                href="{{ route('assessments.delete', $assessment) }}"
                                class="px-3 py-1 rounded-lg bg-red-50 text-red-600 hover:bg-red-100">
                                Delete
                            </a>

                        </div>
                    </td>

                </tr>

                @empty

                <tr>
                    <td colspan="7" class="py-16 text-center">
                        <x-heroicon-o-clipboard-document-list class="w-12 h-12 mx-auto text-slate-300" />

                        <h3 class="mt-4 text-xl font-semibold text-slate-700">
                            No assessments found
                        </h3>

                        <p class="mt-2 text-slate-500">
                            Assessments will appear here once they are created.
                        </p>
                    </td>
                </tr>

                @endforelse

            </tbody>

        </table>

    </div>

    <div class="p-6">
        {{ $assessments->links() }}
    </div>

</div>

@endsection
